#!/usr/bin/env python

"""
$Id: urlencode.py 2304 2010-11-08 09:20:02Z stamparm $

Copyright (c) 2006-2010 sqlmap developers (http://sqlmap.sourceforge.net/)
See the file 'doc/COPYING' for copying permission
"""

from lib.core.convert import urlencode
from lib.core.enums import PRIORITY
from lib.core.exception import sqlmapUnsupportedFeatureException

__priority__ = PRIORITY.LOWER

def tamper(value):
    """
    Replaces value with urlencode(value)
    Example: 'SELECT FIELD FROM TABLE' becomes 'SELECT%20FIELD%20FROM%20TABLE'
    """

    if value:
        value = urlencode(value, convall=True)

    return value
