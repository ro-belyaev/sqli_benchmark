#!/usr/bin/env python

"""
$Id: parenthesis.py 1360 2010-03-03 15:26:27Z inquisb $

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

from lib.core.agent import agent
from lib.core.common import randomInt
from lib.core.common import randomStr
from lib.core.data import conf
from lib.core.data import kb
from lib.core.data import logger
from lib.core.exception import sqlmapNoneDataException
from lib.core.session import setParenthesis
from lib.request.connect import Connect as Request

def checkForParenthesis():
    """
    This method checks if the SQL injection affected parameter
    is within the parenthesis.
    """

    logMsg = "testing for parenthesis on injectable parameter"
    logger.info(logMsg)

    count = 0

    if kb.parenthesis is not None:
        return

    if conf.prefix or conf.postfix:
        kb.parenthesis = 0
        return

    for parenthesis in range(1, 4):
        query  = agent.prefixQuery("%s " % (")" * parenthesis))
        query += "AND %s" % ("(" * parenthesis)

        randInt = randomInt()
        randStr = randomStr()

        if kb.injType == "numeric":
            query += "%d=%d" % (randInt, randInt)
        elif kb.injType == "stringsingle":
            query += "'%s'='%s" % (randStr, randStr)
        elif kb.injType == "likesingle":
            query += "'%s' LIKE '%s" % (randStr, randStr)
        elif kb.injType == "stringdouble":
            query += "\"%s\"=\"%s" % (randStr, randStr)
        elif kb.injType == "likedouble":
            query += "\"%s\" LIKE \"%s" % (randStr, randStr)
        else:
            raise sqlmapNoneDataException, "unsupported injection type"

        payload = agent.payload(newValue=query)
        result = Request.queryPage(payload)

        if result:
            count = parenthesis

    logMsg = "the injectable parameter requires %d parenthesis" % count
    logger.info(logMsg)

    setParenthesis(count)
