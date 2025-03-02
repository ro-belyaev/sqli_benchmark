#!/usr/bin/env python

"""
$Id: handler.py 2304 2010-11-08 09:20:02Z stamparm $

Copyright (c) 2006-2010 sqlmap developers (http://sqlmap.sourceforge.net/)
See the file 'doc/COPYING' for copying permission
"""

from lib.core.common import popValue
from lib.core.common import pushValue
from lib.core.data import conf
from lib.core.data import kb
from lib.core.data import logger
from lib.core.enums import DBMS
from lib.core.settings import MSSQL_ALIASES
from lib.core.settings import MYSQL_ALIASES
from lib.core.settings import ORACLE_ALIASES
from lib.core.settings import PGSQL_ALIASES
from lib.core.settings import SQLITE_ALIASES
from lib.core.settings import ACCESS_ALIASES
from lib.core.settings import FIREBIRD_ALIASES
from lib.core.settings import MAXDB_ALIASES
from lib.core.settings import SYBASE_ALIASES

from plugins.dbms.mssqlserver import MSSQLServerMap
from plugins.dbms.mssqlserver.connector import Connector as MSSQLServerConn
from plugins.dbms.mysql import MySQLMap
from plugins.dbms.mysql.connector import Connector as MySQLConn
from plugins.dbms.oracle import OracleMap
from plugins.dbms.oracle.connector import Connector as OracleConn
from plugins.dbms.postgresql import PostgreSQLMap
from plugins.dbms.postgresql.connector import Connector as PostgreSQLConn
from plugins.dbms.sqlite import SQLiteMap
from plugins.dbms.sqlite.connector import Connector as SQLiteConn
from plugins.dbms.access import AccessMap
from plugins.dbms.access.connector import Connector as AccessConn
from plugins.dbms.firebird import FirebirdMap
from plugins.dbms.firebird.connector import Connector as FirebirdConn
from plugins.dbms.maxdb import MaxDBMap
from plugins.dbms.maxdb.connector import Connector as MaxDBConn
from plugins.dbms.sybase import SybaseMap
from plugins.dbms.sybase.connector import Connector as SybaseConn

def setHandler():
    """
    Detect which is the target web application back-end database
    management system.
    """

    count     = 0
    dbmsNames = ( "MySQL", "Oracle", "PostgreSQL", "Microsoft SQL Server", "SQLite", "Microsoft Access", "Firebird", "SAP MaxDB", "Sybase" )
    dbmsMap   = [
                  ( MYSQL_ALIASES, MySQLMap, MySQLConn ),
                  ( ORACLE_ALIASES, OracleMap, OracleConn ),
                  ( PGSQL_ALIASES, PostgreSQLMap, PostgreSQLConn ),
                  ( MSSQL_ALIASES, MSSQLServerMap, MSSQLServerConn ),
                  ( SQLITE_ALIASES, SQLiteMap, SQLiteConn ),
                  ( ACCESS_ALIASES, AccessMap, AccessConn ),
                  ( FIREBIRD_ALIASES, FirebirdMap, FirebirdConn ),
                  ( MAXDB_ALIASES, MaxDBMap, MaxDBConn ),
                  ( SYBASE_ALIASES, SybaseMap, SybaseConn ),
                ]

    if kb.htmlFp:
        for i in xrange(len(dbmsMap)):
            dbmsAliases, _, _ = dbmsMap[i]
            if kb.htmlFp[-1].lower() in dbmsAliases:
                if i > 0:
                    pushValue(dbmsMap[i])
                    dbmsMap.remove(dbmsMap[i])
                    dbmsMap.insert(0, popValue())
                break


    for dbmsAliases, dbmsMap, dbmsConn in dbmsMap:
        if conf.dbms and conf.dbms not in dbmsAliases:
            debugMsg  = "skipping test for %s" % dbmsNames[count]
            logger.debug(debugMsg)

            count += 1

            continue

        kb.misc.handler = handler = dbmsMap()
        conf.dbmsConnector = dbmsConn()

        if conf.direct:
            logger.debug("forcing timeout to 10 seconds")
            conf.timeout = 10

            conf.dbmsConnector.connect()

        if handler.checkDbms():
            if not conf.dbms or conf.dbms in dbmsAliases:
                kb.dbmsDetected = True

                conf.dbmsHandler = handler

                return
        else:
            conf.dbmsConnector = None
