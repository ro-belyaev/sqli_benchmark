#!/usr/bin/env python

"""
$Id: data.py 2097 2010-10-19 23:00:19Z stamparm $

Copyright (c) 2006-2010 sqlmap developers (http://sqlmap.sourceforge.net/)
See the file 'doc/COPYING' for copying permission
"""

from lib.core.datatype import advancedDict
from lib.core.settings import LOGGER

# sqlmap paths
paths = advancedDict()

# object to share within function and classes command
# line options and settings
conf = advancedDict()

# object to share within function and classes results
kb = advancedDict()

# object with each database management system specific queries
queries = {}

# logger
logger = LOGGER
