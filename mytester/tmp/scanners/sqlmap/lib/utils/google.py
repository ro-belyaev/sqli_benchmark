#!/usr/bin/env python

"""
$Id: google.py 1395 2010-03-05 14:59:33Z inquisb $

This file is part of the sqlmap project, http://sqlmap.sourceforge.net.

Copyright (c) 2007-2010 Bernardo Damele A. G. <bernardo.damele@gmail.com>
Copyright (c) 2006 Daniele Bellucci <daniele.bellucci@gmail.com>

sqlmap is free software; you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free
Software Foundation version 2 of the License.

sqlmap is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more
details.

You should have received a copy of the GNU General Public License along
with sqlmap; if not, write to the Free Software Foundation, Inc., 51
Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
"""

import cookielib
import re
import urllib2

from lib.core.convert import urlencode
from lib.core.data import conf
from lib.core.data import kb
from lib.core.data import logger
from lib.core.exception import sqlmapConnectionException
from lib.request.basic import decodePage

class Google:
    """
    This class defines methods used to perform Google dorking (command
    line option '-g <google dork>'
    """

    def __init__(self, proxyHandler):
        self.__googleCookie = None
        self.__matches = []
        self.__cj = cookielib.LWPCookieJar()
        self.opener = urllib2.build_opener(proxyHandler, urllib2.HTTPCookieProcessor(self.__cj))
        self.opener.addheaders = conf.httpHeaders

    def __parsePage(self, page):
        """
        Parse Google dork search results page to get the list of
        HTTP addresses
        """

        matches = []

        regExpr = "class=r\076\074a href=\042(http[s]*://.+?)\042\sclass=l"
        matches = re.findall(regExpr, page, re.I | re.M)

        return matches

    def getTargetUrls(self):
        """
        This method returns the list of hosts with parameters out of
        your Google dork search results
        """

        for match in self.__matches:
            if re.search("(.*?)\?(.+)", match, re.I):
                kb.targetUrls.add(( match, None, None, None ))

    def getCookie(self):
        """
        This method is the first to be called when initializing a
        Google dorking object through this library. It is used to
        retrieve the Google session cookie needed to perform the
        further search
        """

        try:
            conn = self.opener.open("http://www.google.com/ncr")
            _ = conn.info()
        except urllib2.HTTPError, e:
            _ = e.info()
        except urllib2.URLError, e:
            errMsg = "unable to connect to Google"
            raise sqlmapConnectionException, errMsg

    def search(self, googleDork):
        """
        This method performs the effective search on Google providing
        the google dork and the Google session cookie
        """

        gpage = conf.googlePage if conf.googlePage > 1 else 1
        logger.info("using Google result page #%d" % gpage)
        
        if not googleDork:
            return None

        url  = "http://www.google.com/search?"
        url += "q=%s&" % urlencode(googleDork)
        url += "num=100&hl=en&safe=off&filter=0&btnG=Search"
        url += "&start=%d" % ((gpage-1) * 100)

        try:
            conn = self.opener.open(url)

            requestMsg = "HTTP request:\nGET %s HTTP/1.1" % url
            #requestHeaders = "\n".join(["%s: %s" % (header, value) for header, value in conn.headers.items()])
            #requestMsg += "\n%s" % requestHeaders
            requestMsg += "\n"
            logger.log(9, requestMsg)

            page            = conn.read()
            code            = conn.code
            status          = conn.msg
            responseHeaders = conn.info()

            encoding = responseHeaders.get("Content-Encoding")
            page = decodePage(page, encoding)

            responseMsg = "HTTP response (%s - %d):\n" % (status, code)
    
            if conf.verbose <= 4:
                responseMsg += str(responseHeaders)
            elif conf.verbose > 4:
                responseMsg += "%s\n%s\n" % (responseHeaders, page)
    
            logger.log(8, responseMsg)
        except urllib2.HTTPError, e:
            page = e.read()
        except urllib2.URLError, e:
            errMsg = "unable to connect to Google"
            raise sqlmapConnectionException, errMsg

        self.__matches = self.__parsePage(page)

        return self.__matches
