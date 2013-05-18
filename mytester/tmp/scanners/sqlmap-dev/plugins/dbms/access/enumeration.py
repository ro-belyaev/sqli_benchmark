#!/usr/bin/env python

"""
$Id: enumeration.py 2344 2010-11-11 17:09:31Z stamparm $

Copyright (c) 2006-2010 sqlmap developers (http://sqlmap.sourceforge.net/)
See the file 'doc/COPYING' for copying permission
"""

from lib.core.data import logger
from lib.core.enums import DBMS

from plugins.generic.enumeration import Enumeration as GenericEnumeration

class Enumeration(GenericEnumeration):
    def __init__(self):
        GenericEnumeration.__init__(self, DBMS.ACCESS)

    def getDbs(self):
        warnMsg = "on Microsoft Access it is not possible to enumerate databases"
        logger.warn(warnMsg)

        return []

    def getBanner(self):
        warnMsg = "on Microsoft Access it is not possible to get a banner"
        logger.warn(warnMsg)

        return None

    def getCurrentDb(self):
        warnMsg = "on Microsoft Access it is not possible to get name of the current database"
        logger.warn(warnMsg)

    def getPasswordHashes(self):
        warnMsg = "on Microsoft Access it is not possible to enumerate the user password hashes"
        logger.warn(warnMsg)

        return {}

    def searchDb(self):
        warnMsg = "on Microsoft Access it is not possible to search databases"
        logger.warn(warnMsg)

        return []

    def dumpTable(self):
        warnMsg = "on Microsoft Access it is not yet implemented dumping of tables"
        logger.warn(warnMsg)

        return None