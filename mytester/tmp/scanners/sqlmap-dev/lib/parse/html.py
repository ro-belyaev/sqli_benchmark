#!/usr/bin/env python

"""
$Id: html.py 2009 2010-10-14 23:18:29Z stamparm $

Copyright (c) 2006-2010 sqlmap developers (http://sqlmap.sourceforge.net/)
See the file 'doc/COPYING' for copying permission
"""

import re

from xml.sax.handler import ContentHandler

from lib.core.common import checkFile
from lib.core.common import parseXmlFile
from lib.core.common import sanitizeStr
from lib.core.data import kb
from lib.core.data import paths

class htmlHandler(ContentHandler):
    """
    This class defines methods to parse the input HTML page to
    fingerprint the back-end database management system
    """

    def __init__(self, page):
        self.__dbms   = None
        self.__page   = page
        self.__regexp = None
        self.__match  = None

        self.dbms     = None

    def startElement(self, name, attrs):
        if name == "dbms":
            self.__dbms = attrs.get("value")

        if name == "error":
            self.__regexp = attrs.get("regexp")
            self.__match = re.search(self.__regexp, self.__page, re.I)

            if self.__match:
                self.dbms = self.__dbms
                self.__match = None
                kb.lastErrorPage = (kb.lastRequestUID, self.__page)

def htmlParser(page):
    """
    This function calls a class that parses the input HTML page to
    fingerprint the back-end database management system
    """

    xmlfile = paths.ERRORS_XML
    checkFile(xmlfile)
    page = sanitizeStr(page)
    handler = htmlHandler(page)
    parseXmlFile(xmlfile, handler)

    if handler.dbms and handler.dbms not in kb.htmlFp:
        kb.htmlFp.append(handler.dbms)

    return handler.dbms
