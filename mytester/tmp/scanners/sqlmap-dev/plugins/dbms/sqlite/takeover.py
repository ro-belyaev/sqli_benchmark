#!/usr/bin/env python

"""
$Id: takeover.py 2009 2010-10-14 23:18:29Z stamparm $

Copyright (c) 2006-2010 sqlmap developers (http://sqlmap.sourceforge.net/)
See the file 'doc/COPYING' for copying permission
"""

from lib.core.exception import sqlmapUnsupportedFeatureException

from plugins.generic.takeover import Takeover as GenericTakeover

class Takeover(GenericTakeover):
    def __init__(self):
        GenericTakeover.__init__(self)

    def osCmd(self):
        errMsg = "on SQLite it is not possible to execute commands"
        raise sqlmapUnsupportedFeatureException, errMsg

    def osShell(self):
        errMsg = "on SQLite it is not possible to execute commands"
        raise sqlmapUnsupportedFeatureException, errMsg

    def osPwn(self):
        errMsg  = "on SQLite it is not possible to establish an "
        errMsg += "out-of-band connection"
        raise sqlmapUnsupportedFeatureException, errMsg

    def osSmb(self):
        errMsg  = "on SQLite it is not possible to establish an "
        errMsg += "out-of-band connection"
        raise sqlmapUnsupportedFeatureException, errMsg
