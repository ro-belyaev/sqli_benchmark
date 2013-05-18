#!/usr/bin/env python

"""
$Id: charencode.py 2304 2010-11-08 09:20:02Z stamparm $

Copyright (c) 2006-2010 sqlmap developers (http://sqlmap.sourceforge.net/)
See the file 'doc/COPYING' for copying permission
"""

import string

from lib.core.enums import PRIORITY
from lib.core.exception import sqlmapUnsupportedFeatureException

__priority__ = PRIORITY.LOWEST

def tamper(value):
    """
    Replaces value with urlencode of non-encoded chars in value
    Example: 'SELECT%20FIELD%20FROM%20TABLE' becomes '%53%45%4c%45%43%54%20%46%49%45%4c%44%20%46%52%4f%4d%20%54%41%42%4c%45'
    """

    retVal = value

    if value:
        retVal = ""
        i = 0

        while i < len(value):
            if value[i] == '%' and (i < len(value) - 2) and value[i+1] in string.hexdigits and value[i+2] in string.hexdigits:
                retVal += value[i:i+3]
                i += 3
            else:
                retVal += '%%%X' % ord(value[i])
                i += 1

    return retVal
