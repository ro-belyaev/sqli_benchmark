#!/usr/bin/env python

"""
$Id: __init__.py 2009 2010-10-14 23:18:29Z stamparm $

Copyright (c) 2006-2010 sqlmap developers (http://sqlmap.sourceforge.net/)
See the file 'doc/COPYING' for copying permission
"""

from lib.core.settings import ACCESS_SYSTEM_DBS
from lib.core.unescaper import unescaper

from plugins.dbms.access.enumeration import Enumeration
from plugins.dbms.access.filesystem import Filesystem
from plugins.dbms.access.fingerprint import Fingerprint
from plugins.dbms.access.syntax import Syntax
from plugins.dbms.access.takeover import Takeover
from plugins.generic.misc import Miscellaneous

class AccessMap(Syntax, Fingerprint, Enumeration, Filesystem, Miscellaneous, Takeover):
    """
    This class defines Microsoft Access methods
    """

    def __init__(self):
        self.excludeDbsList = ACCESS_SYSTEM_DBS

        Syntax.__init__(self)
        Fingerprint.__init__(self)
        Enumeration.__init__(self)
        Filesystem.__init__(self)
        Miscellaneous.__init__(self)
        Takeover.__init__(self)

        unescaper.setUnescape(AccessMap.unescape)
