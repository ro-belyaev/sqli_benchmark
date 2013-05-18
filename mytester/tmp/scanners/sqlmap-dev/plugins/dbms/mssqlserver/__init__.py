#!/usr/bin/env python

"""
$Id: __init__.py 2009 2010-10-14 23:18:29Z stamparm $

Copyright (c) 2006-2010 sqlmap developers (http://sqlmap.sourceforge.net/)
See the file 'doc/COPYING' for copying permission
"""

from lib.core.settings import MSSQL_SYSTEM_DBS
from lib.core.unescaper import unescaper

from plugins.dbms.mssqlserver.enumeration import Enumeration
from plugins.dbms.mssqlserver.filesystem import Filesystem
from plugins.dbms.mssqlserver.fingerprint import Fingerprint
from plugins.dbms.mssqlserver.syntax import Syntax
from plugins.dbms.mssqlserver.takeover import Takeover
from plugins.generic.misc import Miscellaneous


class MSSQLServerMap(Syntax, Fingerprint, Enumeration, Filesystem, Miscellaneous, Takeover):
    """
    This class defines Microsoft SQL Server methods
    """

    def __init__(self):
        self.excludeDbsList = MSSQL_SYSTEM_DBS

        Syntax.__init__(self)
        Fingerprint.__init__(self)
        Enumeration.__init__(self)
        Filesystem.__init__(self)
        Miscellaneous.__init__(self)
        Takeover.__init__(self)

        unescaper.setUnescape(MSSQLServerMap.unescape)
