#!/usr/bin/env python

"""
$Id: enumeration.py 2304 2010-11-08 09:20:02Z stamparm $

Copyright (c) 2006-2010 sqlmap developers (http://sqlmap.sourceforge.net/)
See the file 'doc/COPYING' for copying permission
"""

from lib.core.data import conf
from lib.core.data import logger
from lib.core.enums import DBMS
from lib.core.exception import sqlmapUnsupportedFeatureException

from plugins.generic.enumeration import Enumeration as GenericEnumeration

class Enumeration(GenericEnumeration):
    def __init__(self):
        GenericEnumeration.__init__(self, DBMS.SYBASE)
