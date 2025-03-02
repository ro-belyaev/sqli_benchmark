#!/usr/bin/env python

"""
$Id: __init__.py 2009 2010-10-14 23:18:29Z stamparm $

Copyright (c) 2006-2010 sqlmap developers (http://sqlmap.sourceforge.net/)
See the file 'doc/COPYING' for copying permission
"""

from lib.core.settings import MYSQL_SYSTEM_DBS
from lib.core.unescaper import unescaper

from plugins.dbms.mysql.enumeration import Enumeration
from plugins.dbms.mysql.filesystem import Filesystem
from plugins.dbms.mysql.fingerprint import Fingerprint
from plugins.dbms.mysql.syntax import Syntax
from plugins.dbms.mysql.takeover import Takeover
from plugins.generic.misc import Miscellaneous

class MySQLMap(Syntax, Fingerprint, Enumeration, Filesystem, Miscellaneous, Takeover):
    """
    This class defines MySQL methods
    """

    def __init__(self):
        self.excludeDbsList = MYSQL_SYSTEM_DBS
        self.sysUdfs        = {
                                # UDF name:    UDF return data-type
                                "sys_exec":    { "return": "int" },
                                "sys_eval":    { "return": "string" },
                                "sys_bineval": { "return": "int" }
                              }

        Syntax.__init__(self)
        Fingerprint.__init__(self)
        Enumeration.__init__(self)
        Filesystem.__init__(self)
        Miscellaneous.__init__(self)
        Takeover.__init__(self)

        unescaper.setUnescape(MySQLMap.unescape)
