#!/usr/bin/env python

"""
$Id: direct.py 2304 2010-11-08 09:20:02Z stamparm $

Copyright (c) 2006-2010 sqlmap developers (http://sqlmap.sourceforge.net/)
See the file 'doc/COPYING' for copying permission
"""

from lib.core.agent import agent
from lib.core.common import dataToSessionFile
from lib.core.common import getUnicode
from lib.core.convert import base64pickle
from lib.core.convert import base64unpickle
from lib.core.convert import utf8decode
from lib.core.data import conf
from lib.core.data import kb
from lib.core.data import logger
from lib.core.enums import DBMS
from lib.core.settings import SQL_STATEMENTS
from lib.utils.timeout import timeout

def direct(query, content=True):
    output = None
    select = False
    query = agent.payloadDirect(query)

    if kb.dbms == DBMS.ORACLE and query.startswith("SELECT ") and " FROM " not in query:
        query = "%s FROM DUAL" % query

    for sqlTitle, sqlStatements in SQL_STATEMENTS.items():
        for sqlStatement in sqlStatements:
            if query.lower().startswith(sqlStatement) and sqlTitle == "SQL SELECT statement":
                select = True
                break

    logger.debug("query: %s" % query)

    if not select:
        output = timeout(func=conf.dbmsConnector.execute, args=(query,), duration=conf.timeout, default=None)
    elif conf.hostname in kb.resumedQueries and query in kb.resumedQueries[conf.hostname] and "sqlmapoutput" not in query and "sqlmapfile" not in query:
        try:
            output = base64unpickle(kb.resumedQueries[conf.hostname][query][:-1])
        except:
            output = timeout(func=conf.dbmsConnector.select, args=(query,), duration=conf.timeout, default=None)

        infoMsg  = "resumed from file '%s': " % conf.sessionFile
        infoMsg += "%s..." % getUnicode(output)[:20]
        logger.info(infoMsg)
    else:
        output = timeout(func=conf.dbmsConnector.select, args=(query,), duration=conf.timeout, default=None)

    if output is None or len(output) == 0:
        return None
    elif content:
        if conf.hostname not in kb.resumedQueries or ( conf.hostname in kb.resumedQueries and query not in kb.resumedQueries[conf.hostname] ):
            dataToSessionFile("[%s][%s][%s][%s][%s]\n" % (conf.hostname, kb.injPlace, conf.parameters[kb.injPlace], query, base64pickle(output)))

        if len(output) == 1:
            if len(output[0]) == 1:
                out = list(output)[0][0]
                if isinstance(out, str):
                    out = utf8decode(out)
                return getUnicode(out)
            else:
                return list(output)
        else:
            return output
    else:
        for line in output:
            if line[0] in (1, -1):
                return True
            else:
                return False
