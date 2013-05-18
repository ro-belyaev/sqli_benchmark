#!/usr/bin/env python

"""
$Id: unescaper.py 2009 2010-10-14 23:18:29Z stamparm $

Copyright (c) 2006-2010 sqlmap developers (http://sqlmap.sourceforge.net/)
See the file 'doc/COPYING' for copying permission
"""

class Unescaper:
    def __init__(self):
        self.__unescaper = None

    def setUnescape(self, unescapeFunction):
        self.__unescaper = unescapeFunction

    def unescape(self, expression, quote=True):
        return self.__unescaper(expression, quote=quote)

unescaper = Unescaper()
