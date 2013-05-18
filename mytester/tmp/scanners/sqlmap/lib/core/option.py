#!/usr/bin/env python

"""
$Id: option.py 1451 2010-03-12 12:46:26Z stamparm $

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
import ctypes
import difflib
import logging
import os
import re
import socket
import urllib2
import urlparse

from ConfigParser import ConfigParser

from lib.core.common import getFileType
from lib.core.common import normalizePath
from lib.core.common import ntToPosixSlashes
from lib.core.common import parseTargetUrl
from lib.core.common import paths
from lib.core.common import randomRange
from lib.core.common import sanitizeStr
from lib.core.data import conf
from lib.core.data import kb
from lib.core.data import logger
from lib.core.data import paths
from lib.core.datatype import advancedDict
from lib.core.exception import sqlmapFilePathException
from lib.core.exception import sqlmapGenericException
from lib.core.exception import sqlmapMissingDependence
from lib.core.exception import sqlmapMissingMandatoryOptionException
from lib.core.exception import sqlmapMissingPrivileges
from lib.core.exception import sqlmapSyntaxException
from lib.core.exception import sqlmapUnsupportedDBMSException
from lib.core.optiondict import optDict
from lib.core.settings import MSSQL_ALIASES
from lib.core.settings import MYSQL_ALIASES
from lib.core.settings import PGSQL_ALIASES
from lib.core.settings import ORACLE_ALIASES
from lib.core.settings import IS_WIN
from lib.core.settings import PLATFORM
from lib.core.settings import SITE
from lib.core.settings import SUPPORTED_DBMS
from lib.core.settings import SUPPORTED_OS
from lib.core.settings import VERSION_STRING
from lib.core.update import update
from lib.parse.configfile import configFileParser
from lib.parse.queriesfile import queriesParser
from lib.request.proxy import ProxyHTTPSHandler
from lib.request.certhandler import HTTPSCertAuthHandler
from lib.utils.google import Google

authHandler  = urllib2.BaseHandler()
proxyHandler = urllib2.BaseHandler()

def __urllib2Opener():
    """
    This function creates the urllib2 OpenerDirector.
    """

    global authHandler
    global proxyHandler

    debugMsg = "creating HTTP requests opener object"
    logger.debug(debugMsg)
    
    if conf.dropSetCookie:
        opener  = urllib2.build_opener(proxyHandler, authHandler)
    else:
        conf.cj = cookielib.LWPCookieJar()
        opener  = urllib2.build_opener(proxyHandler, authHandler, urllib2.HTTPCookieProcessor(conf.cj))

    urllib2.install_opener(opener)

def __feedTargetsDict(reqFile, addedTargetUrls):
    fp = open(reqFile, "r")

    fread = fp.read()
    fread = fread.replace("\r", "")

    reqResList = fread.split("======================================================")

    port   = None
    scheme = None

    if conf.scope:
        logger.info("using regular expression '%s' for filtering targets" % conf.scope)

    for request in reqResList:
        if scheme is None:
            schemePort = re.search("\d\d[\:|\.]\d\d[\:|\.]\d\d\s+(http[\w]*)\:\/\/.*?\:([\d]+)", request, re.I)

            if schemePort:
                scheme = schemePort.group(1)
                port   = schemePort.group(2)

        if not re.search ("^[\n]*(GET|POST).*?\sHTTP\/", request, re.I):
            continue

        if re.search("^[\n]*(GET|POST).*?\.(gif|jpg|png)\sHTTP\/", request, re.I):
            continue

        getPostReq = False
        url        = None
        host       = None
        method     = None
        data       = None
        cookie     = None
        params     = False
        lines      = request.split("\n")

        for line in lines:
            if len(line) == 0 or line == "\n":
                continue

            if line.startswith("GET ") or line.startswith("POST "):
                if line.startswith("GET "):
                    index = 4
                else:
                    index = 5

                url    = line[index:line.index(" HTTP/")]
                method = line[:index-1]

                if "?" in line and "=" in line:
                    params = True

                getPostReq = True

            # GET parameters
            elif "?" in line and "=" in line and ": " not in line:
                data   = line
                params = True

            # Cookie and Host headers
            elif ": " in line:
                key, value = line.split(": ", 1)

                if key.lower() == "cookie":
                    cookie = value
                elif key.lower() == "host":
                    host = value

            # POST parameters
            elif method is not None and method == "POST" and "=" in line:
                data   = line
                params = True

        if conf.scope:
            getPostReq &= re.search(conf.scope, host) is not None

        if getPostReq and params:
            if not url.startswith("http"):
                url    = "%s://%s:%s%s" % (scheme or "http", host, port or "80", url)
                scheme = None
                port   = None

            if not kb.targetUrls or url not in addedTargetUrls:
                kb.targetUrls.add(( url, method, data, cookie ))
                addedTargetUrls.add(url)

def __setMultipleTargets():
    """
    Define a configuration parameter if we are running in multiple target
    mode.
    """

    initialTargetsCount = len(kb.targetUrls)
    addedTargetUrls = set()

    if not conf.list:
        return

    debugMsg = "parsing targets list from '%s'" % conf.list
    logger.debug(debugMsg)

    if not os.path.exists(conf.list):
        errMsg = "the specified list of targets does not exist"
        raise sqlmapFilePathException, errMsg

    if os.path.isfile(conf.list):
        __feedTargetsDict(conf.list, addedTargetUrls)

    elif os.path.isdir(conf.list):
        files = os.listdir(conf.list)
        files.sort()

        for reqFile in files:
            if not re.search("([\d]+)\-request", reqFile):
                continue

            __feedTargetsDict(os.path.join(conf.list, reqFile), addedTargetUrls)

    else:
        errMsg  = "the specified list of targets is not a file "
        errMsg += "nor a directory"
        raise sqlmapFilePathException, errMsg

    updatedTargetsCount = len(kb.targetUrls)

    if updatedTargetsCount > initialTargetsCount:
        infoMsg  = "sqlmap parsed %d " % (updatedTargetsCount - initialTargetsCount)
        infoMsg += "testable requests from the targets list"
        logger.info(infoMsg)

def __setGoogleDorking():
    """
    This function checks if the way to request testable hosts is through
    Google dorking then requests to Google the search parameter, parses
    the results and save the testable hosts into the knowledge base.
    """

    global proxyHandler

    if not conf.googleDork:
        return

    debugMsg = "initializing Google dorking requests"
    logger.debug(debugMsg)

    logMsg = "first request to Google to get the session cookie"
    logger.info(logMsg)

    googleObj = Google(proxyHandler)
    googleObj.getCookie()

    matches = googleObj.search(conf.googleDork)

    if not matches:
        errMsg  = "unable to find results for your "
        errMsg += "Google dork expression"
        raise sqlmapGenericException, errMsg

    googleObj.getTargetUrls()

    if kb.targetUrls:
        logMsg  = "sqlmap got %d results for your " % len(matches)
        logMsg += "Google dork expression, "

        if len(matches) == len(kb.targetUrls):
            logMsg += "all "
        else:
            logMsg += "%d " % len(kb.targetUrls)

        logMsg += "of them are testable targets"
        logger.info(logMsg)
    else:
        errMsg  = "sqlmap got %d results " % len(matches)
        errMsg += "for your Google dork expression, but none of them "
        errMsg += "have GET parameters to test for SQL injection"
        raise sqlmapGenericException, errMsg

def __setRequestFromFile():
    """
    This function checks if the way to make a HTTP request is through supplied
    textual file, parses it and saves the information into the knowledge base.
    """

    if not conf.requestFile:
        return
    
    conf.requestFile = os.path.expanduser(conf.requestFile)
    
    infoMsg = "parsing HTTP request from '%s'" % conf.requestFile
    logger.info(infoMsg)

    if not os.path.isfile(conf.requestFile):
        errMsg  = "the specified HTTP request file "
        errMsg += "'%s' does not exist" % conf.requestFile
        raise sqlmapFilePathException, errMsg
    
    fp = open(conf.requestFile, "r")
    fread = fp.read()
    fread = fread.replace("\r", "")
    fp.close()
    
    lines = fread.split("\n")
    
    if len(lines) == 0:
        errMsg  = "the specified HTTP request file "
        errMsg += "'%s' has no content" % conf.requestFile
        raise sqlmapFilePathException, errMsg
    
    if not (lines[0].upper().startswith("GET ") or lines[0].upper().startswith("POST ")):
        errMsg =  "the specified HTTP request file "
        errMsg += "doesn't start with GET or POST keyword"
        raise sqlmapFilePathException, errMsg

    
    if lines[0].upper().startswith("GET "):
        index = 4
    else:
        index = 5

    if lines[0].upper().find(" HTTP/") == -1:
        errMsg  = "the specified HTTP request file " 
        errMsg += "has a syntax error at line: 1"
        raise sqlmapFilePathException, errMsg
        
    host = None
    headers = ""
    page = lines[0][index:lines[0].index(" HTTP/")]
    
    if conf.method:
        warnMsg  = "HTTP method previously set. overriding it with "
        warnMsg += "the value supplied from the HTTP request file"
        logger.warn(warnMsg)
    conf.method = lines[0][:index-1]

    for index in xrange(1, len(lines) - 1):
        line = lines[index]
        valid = True
        
        if len(line) == 0:
            break
        
        headers += line + "\n"
        
        items = line.split(': ')
        if len(items) != 2:
            valid = False
        else:
            if items[0].upper() == "HOST":
                host = items[1]
                
        if not valid:
            errMsg  = "the specified HTTP request file" 
            errMsg += "has a syntax error at line: %d" % (index + 1)
            raise sqlmapFilePathException, errMsg
    
    if conf.headers and headers:
        warnMsg  = "HTTP headers previously set. overriding it with "
        warnMsg += "the value(s) supplied from the HTTP request file"
        logger.warn(warnMsg)
    conf.headers = headers.strip("\n")
    
    if fread.find("\n\n") != -1:
        if conf.data:
            warnMsg  = "HTTP POST data previously set. overriding it with "
            warnMsg += "the value supplied from the HTTP request file"
            logger.warn(warnMsg)
        conf.data = fread[fread.index('\n\n')+2:].strip("\n")
    
    if conf.url:
        warnMsg  = "target url previously set. overriding it with "
        warnMsg += "the value supplied from the HTTP request file"
        logger.warn(warnMsg)
        
    if host:
        conf.url = "%s%s" % (host, page)
    else:
        errMsg  = "mandatory HTTP header HOST is missing in "
        errMsg += "the HTTP request file"
        raise sqlmapFilePathException, errMsg
            
def __setMetasploit():
    if not conf.osPwn and not conf.osSmb and not conf.osBof:
        return

    debugMsg = "setting the takeover out-of-band functionality"
    logger.debug(debugMsg)

    msfEnvPathExists = False

    if IS_WIN:
        warnMsg  = "Metasploit's msfconsole and msfcli are not supported "
        warnMsg += "on the native Windows Ruby interpreter. Please "
        warnMsg += "install Metasploit, Python interpreter and sqlmap on "
        warnMsg += "Cygwin or use Linux in VMWare to use sqlmap takeover "
        warnMsg += "out-of-band features. sqlmap will now continue "
        warnMsg += "without calling any takeover feature"
        logger.warn(warnMsg)

        conf.osPwn = None
        conf.osSmb = None
        conf.osBof = None

        return

    if conf.osSmb:
        isAdmin = False

        if "linux" in PLATFORM or "darwin" in PLATFORM:
            isAdmin = os.geteuid()

            if isinstance(isAdmin, (int, float, long)) and isAdmin == 0:
                isAdmin = True

        elif IS_WIN:
            isAdmin = ctypes.windll.shell32.IsUserAnAdmin()

            if isinstance(isAdmin, (int, float, long)) and isAdmin == 1:
                isAdmin = True

        else:
            warnMsg  = "sqlmap is not able to check if you are running it "
            warnMsg += "as an Administrator accout on this platform. "
            warnMsg += "sqlmap will assume that you are an Administrator "
            warnMsg += "which is mandatory for the SMB relay attack to "
            warnMsg += "work properly"
            logger.warn(warnMsg)

            isAdmin = True

        if isAdmin is not True:
            errMsg  = "you need to run sqlmap as an Administrator/root "
            errMsg += "user if you want to perform a SMB relay attack "
            errMsg += "because it will need to listen on a user-specified "
            errMsg += "SMB TCP port for incoming connection attempts"
            raise sqlmapMissingPrivileges, errMsg

    if conf.msfPath:
        condition  = os.path.exists(normalizePath(conf.msfPath))
        condition &= os.path.exists(normalizePath(os.path.join(conf.msfPath, "msfcli")))
        condition &= os.path.exists(normalizePath(os.path.join(conf.msfPath, "msfconsole")))
        condition &= os.path.exists(normalizePath(os.path.join(conf.msfPath, "msfencode")))
        condition &= os.path.exists(normalizePath(os.path.join(conf.msfPath, "msfpayload")))

        if condition:
            debugMsg  = "provided Metasploit Framework 3 path "
            debugMsg += "'%s' is valid" % conf.msfPath
            logger.debug(debugMsg)

            msfEnvPathExists = True
        else:
            warnMsg  = "the provided Metasploit Framework 3 path "
            warnMsg += "'%s' is not valid. The cause could " % conf.msfPath
            warnMsg += "be that the path does not exists or that one "
            warnMsg += "or more of the needed Metasploit executables "
            warnMsg += "within msfcli, msfconsole, msfencode and "
            warnMsg += "msfpayload do not exist"
            logger.warn(warnMsg)
    else:
        warnMsg  = "you did not provide the local path where Metasploit "
        warnMsg += "Framework 3 is installed"
        logger.warn(warnMsg)

    if not msfEnvPathExists:
        warnMsg  = "sqlmap is going to look for Metasploit Framework 3 "
        warnMsg += "installation into the environment paths"
        logger.warn(warnMsg)

        envPaths = os.environ["PATH"]

        if IS_WIN:
            envPaths = envPaths.split(";")
        else:
            envPaths = envPaths.split(":")

        for envPath in envPaths:
            envPath    = envPath.replace(";", "")
            condition  = os.path.exists(normalizePath(envPath))
            condition &= os.path.exists(normalizePath(os.path.join(envPath, "msfcli")))
            condition &= os.path.exists(normalizePath(os.path.join(envPath, "msfconsole")))
            condition &= os.path.exists(normalizePath(os.path.join(envPath, "msfencode")))
            condition &= os.path.exists(normalizePath(os.path.join(envPath, "msfpayload")))

            if condition:
                infoMsg  = "Metasploit Framework 3 has been found "
                infoMsg += "installed in the '%s' path" % envPath
                logger.info(infoMsg)

                msfEnvPathExists = True
                conf.msfPath     = envPath

                break

    if not msfEnvPathExists:
        errMsg  = "unable to locate Metasploit Framework 3 installation. "
        errMsg += "Get it from http://metasploit.com/framework/download/"
        raise sqlmapFilePathException, errMsg

def __setWriteFile():
    if not conf.wFile:
        return

    debugMsg = "setting the write file functionality"
    logger.debug(debugMsg)

    if not os.path.exists(conf.wFile):
        errMsg = "the provided local file '%s' does not exist" % conf.wFile
        raise sqlmapFilePathException, errMsg

    if not conf.dFile:
        errMsg  = "you did not provide the back-end DBMS absolute path "
        errMsg += "where you want to write the local file '%s'" % conf.wFile
        raise sqlmapMissingMandatoryOptionException, errMsg

    conf.wFileType = getFileType(conf.wFile)

def __setUnionTech():
    if conf.uTech is None:
        conf.uTech = "NULL"

        return

    debugMsg = "setting the UNION query SQL injection detection technique"
    logger.debug(debugMsg)

    uTechOriginal = conf.uTech
    conf.uTech    = conf.uTech.lower()

    if conf.uTech and conf.uTech not in ( "null", "orderby" ):
        infoMsg  = "resetting the UNION query detection technique to "
        infoMsg += "'NULL', '%s' is not a valid technique" % uTechOriginal
        logger.info(infoMsg)

        conf.uTech = "NULL"

    else:
        debugMsg  = "setting UNION query detection technique to "
        debugMsg += "'%s'" % uTechOriginal
        logger.debug(debugMsg)

def __setOS():
    """
    Force the back-end DBMS operating system option.
    """

    if not conf.os:
        return

    debugMsg = "forcing back-end DBMS operating system to user defined value"
    logger.debug(debugMsg)

    conf.os = conf.os.lower()

    if conf.os not in SUPPORTED_OS:
        errMsg  = "you provided an unsupported back-end DBMS operating "
        errMsg += "system. The supported DBMS operating systems for OS "
        errMsg += "and file system access are Linux and Windows. "
        errMsg += "If you do not know the back-end DBMS underlying OS, "
        errMsg += "do not provide it and sqlmap will fingerprint it for "
        errMsg += "you."
        raise sqlmapUnsupportedDBMSException, errMsg

def __setDBMS():
    """
    Force the back-end DBMS option.
    """

    if not conf.dbms:
        return

    debugMsg = "forcing back-end DBMS to user defined value"
    logger.debug(debugMsg)

    conf.dbms = conf.dbms.lower()
    firstRegExp = "(%s|%s|%s|%s)" % ("|".join([alias for alias in MSSQL_ALIASES]),
                                     "|".join([alias for alias in MYSQL_ALIASES]),
                                     "|".join([alias for alias in PGSQL_ALIASES]),
                                     "|".join([alias for alias in ORACLE_ALIASES]))
    dbmsRegExp = re.search("%s ([\d\.]+)" % firstRegExp, conf.dbms)

    if dbmsRegExp:
        conf.dbms      = dbmsRegExp.group(1)
        kb.dbmsVersion = [ dbmsRegExp.group(2) ]

    if conf.dbms not in SUPPORTED_DBMS:
        errMsg  = "you provided an unsupported back-end database management "
        errMsg += "system. The supported DBMS are MySQL, PostgreSQL, "
        errMsg += "Microsoft SQL Server and Oracle. If you do not know "
        errMsg += "the back-end DBMS, do not provide it and sqlmap will "
        errMsg += "fingerprint it for you."
        raise sqlmapUnsupportedDBMSException, errMsg

def __setThreads():
    if not isinstance(conf.threads, int) or conf.threads <= 0:
        conf.threads = 1

def __setHTTPProxy():
    """
    Check and set the HTTP proxy to pass by all HTTP requests.
    """

    global proxyHandler

    if not conf.proxy: 
        if conf.hostname in ('localhost', '127.0.0.1') or conf.ignoreProxy:
            proxyHandler = urllib2.ProxyHandler({})
        return

    debugMsg = "setting the HTTP proxy to pass by all HTTP requests"
    logger.debug(debugMsg)

    __proxySplit   = urlparse.urlsplit(conf.proxy)
    __hostnamePort = __proxySplit[1].split(":")

    __scheme       = __proxySplit[0]
    __hostname     = __hostnamePort[0]
    __port         = None

    if len(__hostnamePort) == 2:
        try:
            __port = int(__hostnamePort[1])
        except:
            pass #drops into the next check block

    if not __scheme or not __hostname or not __port:
        errMsg = "proxy value must be in format 'http://url:port'"
        raise sqlmapSyntaxException, errMsg

    __proxyString = "%s:%d" % (__hostname, __port)

    # Workaround for http://bugs.python.org/issue1424152 (urllib/urllib2:
    # HTTPS over (Squid) Proxy fails) as long as HTTP over SSL requests
    # can't be tunneled over an HTTP proxy natively by Python (<= 2.5)
    # urllib2 standard library
    if conf.scheme == "https":
        proxyHandler = ProxyHTTPSHandler(__proxyString)
    else:
        proxyHandler = urllib2.ProxyHandler({"http": __proxyString})

def __setHTTPAuthentication():
    """
    Check and set the HTTP(s) authentication method (Basic, Digest, NTLM or Certificate),
    username and password for first three methods, or key file and certification file for
    certificate authentication
    """

    global authHandler

    if not conf.aType and not conf.aCred and not conf.aCert:
        return

    elif conf.aType and not conf.aCred:
        errMsg  = "you specified the HTTP authentication type, but "
        errMsg += "did not provide the credentials"
        raise sqlmapSyntaxException, errMsg

    elif not conf.aType and conf.aCred:
        errMsg  = "you specified the HTTP authentication credentials, "
        errMsg += "but did not provide the type"
        raise sqlmapSyntaxException, errMsg

    if not conf.aCert:
        debugMsg = "setting the HTTP authentication type and credentials"
        logger.debug(debugMsg)
    
        aTypeLower = conf.aType.lower()
    
        if aTypeLower not in ( "basic", "digest", "ntlm" ):
            errMsg  = "HTTP authentication type value must be "
            errMsg += "Basic, Digest or NTLM"
            raise sqlmapSyntaxException, errMsg
    
        aCredRegExp = re.search("^(.*?)\:(.*?)$", conf.aCred)
    
        if not aCredRegExp:
            errMsg  = "HTTP authentication credentials value must be "
            errMsg += "in format username:password"
            raise sqlmapSyntaxException, errMsg
    
        authUsername = aCredRegExp.group(1)
        authPassword = aCredRegExp.group(2)
    
        passwordMgr = urllib2.HTTPPasswordMgrWithDefaultRealm()
        passwordMgr.add_password(None, "%s://%s" % (conf.scheme, conf.hostname), authUsername, authPassword)
    
        if aTypeLower == "basic":
            authHandler = urllib2.HTTPBasicAuthHandler(passwordMgr)
    
        elif aTypeLower == "digest":
            authHandler = urllib2.HTTPDigestAuthHandler(passwordMgr)
    
        elif aTypeLower == "ntlm":
            try:
                from ntlm import HTTPNtlmAuthHandler
            except ImportError, _:
                errMsg  = "sqlmap requires Python NTLM third-party library "
                errMsg += "in order to authenticate via NTLM, "
                errMsg += "http://code.google.com/p/python-ntlm/"
                raise sqlmapMissingDependence, errMsg
    
            authHandler = HTTPNtlmAuthHandler.HTTPNtlmAuthHandler(passwordMgr)
    else:
        debugMsg = "setting the HTTP(s) authentication certificate"
        logger.debug(debugMsg)
        
        aCertRegExp = re.search("^(.+?),\s*(.+?)$", conf.aCert)
    
        if not aCertRegExp:
            errMsg  = "HTTP authentication certificate option "
            errMsg += "must be in format key_file,cert_file"
            raise sqlmapSyntaxException, errMsg
    
        #os.path.expanduser for support of paths with ~
        key_file = os.path.expanduser(aCertRegExp.group(1))
        cert_file = os.path.expanduser(aCertRegExp.group(2))
        
        for file in (key_file, cert_file):
            if not os.path.exists(file):
                errMsg  = "File '%s' doesn't exist" % file
                raise sqlmapSyntaxException, errMsg
        
        authHandler = HTTPSCertAuthHandler(key_file, cert_file)

def __setHTTPMethod():
    """
    Check and set the HTTP method to perform HTTP requests through.
    """

    if conf.method:
        conf.method = conf.method.upper()

        if conf.method not in ("GET", "POST"):
            warnMsg  = "'%s' " % conf.method
            warnMsg += "is an unsupported HTTP method, "
            warnMsg += "setting to default method, GET"
            logger.warn(warnMsg)

            conf.method = "GET"
    else:
        conf.method = "GET"

    debugMsg = "setting the HTTP method to %s" % conf.method
    logger.debug(debugMsg)

def __setHTTPExtraHeaders():
    if conf.hostname:
        conf.httpHeaders.append(("Host", conf.hostname))

    if conf.headers:
        debugMsg = "setting extra HTTP headers"
        logger.debug(debugMsg)

        conf.headers = conf.headers.split("\n")

        for headerValue in conf.headers:
            header, value = headerValue.split(": ")

            if header and value:
                conf.httpHeaders.append((header, value))

    else:
        conf.httpHeaders.append(("Accept", "text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5"))
        conf.httpHeaders.append(("Accept-Language", "en-us,en;q=0.5"))
        conf.httpHeaders.append(("Accept-Charset", "ISO-8859-15,utf-8;q=0.7,*;q=0.7"))

def __defaultHTTPUserAgent():
    """
    @return: default sqlmap HTTP User-Agent header
    @rtype: C{str}
    """

    return "%s (%s)" % (VERSION_STRING, SITE)

    # Firefox 3 running on Ubuntu 9.04 updated at April 2009
    #return "Mozilla/5.0 (X11; U; Linux i686; en-GB; rv:1.9.0.9) Gecko/2009042113 Ubuntu/9.04 (jaunty) Firefox/3.0.9"

    # Internet Explorer 7.0 running on Windows 2003 Service Pack 2 english
    # updated at March 2009
    #return "Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.2; .NET CLR 1.1.4322; .NET CLR 2.0.50727; .NET CLR 3.0.04506.30; .NET CLR 3.0.04506.648; .NET CLR 3.0.4506.2152; .NET CLR 3.5.30729)"

def __setHTTPUserAgent():
    """
    Set the HTTP User-Agent header.
    Depending on the user options it can be:

        * The default sqlmap string
        * A default value read as user option
        * A random value read from a list of User-Agent headers from a
          file choosed as user option
    """

    if conf.agent:
        debugMsg = "setting the HTTP User-Agent header"
        logger.debug(debugMsg)

        conf.httpHeaders.append(("User-Agent", conf.agent))
        return

    if not conf.userAgentsFile:
        conf.httpHeaders.append(("User-Agent", __defaultHTTPUserAgent()))
        return

    debugMsg  = "fetching random HTTP User-Agent header from "
    debugMsg += "file '%s'" % conf.userAgentsFile
    logger.debug(debugMsg)

    try:
        fd = open(conf.userAgentsFile, "r")
    except IOError:
        warnMsg  = "unable to read HTTP User-Agent header "
        warnMsg += "file '%s'" % conf.userAgentsFile
        logger.warn(warnMsg)

        conf.httpHeaders.append(("User-Agent", __defaultHTTPUserAgent()))

        return

    __count = 0
    __userAgents = []

    while True:
        line = fd.readline()

        if not line:
            break

        __userAgents.append(line)
        __count += 1

    fd.close()

    if __count == 1:
        __userAgent = __userAgents[0]
    else:
        __userAgent = __userAgents[randomRange(stop=__count)]

    __userAgent = sanitizeStr(__userAgent)
    conf.httpHeaders.append(("User-Agent", __userAgent))

    logMsg  = "fetched random HTTP User-Agent header from "
    logMsg += "file '%s': %s" % (conf.userAgentsFile, __userAgent)
    logger.info(logMsg)

def __setHTTPReferer():
    """
    Set the HTTP Referer
    """

    if conf.referer:
        debugMsg = "setting the HTTP Referer header"
        logger.debug(debugMsg)

        conf.httpHeaders.append(("Referer", conf.referer))

def __setHTTPCookies():
    """
    Set the HTTP Cookie header
    """

    if conf.cookie:
        debugMsg = "setting the HTTP Cookie header"
        logger.debug(debugMsg)
        
        conf.httpHeaders.append(("Connection", "Keep-Alive"))
        conf.httpHeaders.append(("Cookie", conf.cookie))

def __setHTTPTimeout():
    """
    Set the HTTP timeout
    """

    if conf.timeout:
        debugMsg = "setting the HTTP timeout"
        logger.debug(debugMsg)

        conf.timeout = float(conf.timeout)

        if conf.timeout < 3.0:
            warnMsg  = "the minimum HTTP timeout is 3 seconds, sqlmap "
            warnMsg += "will going to reset it"
            logger.warn(warnMsg)

            conf.timeout = 3.0
    else:
        conf.timeout = 30.0

    socket.setdefaulttimeout(conf.timeout)

def __cleanupOptions():
    """
    Cleanup configuration attributes.
    """

    debugMsg = "cleaning up configuration parameters"
    logger.debug(debugMsg)

    if conf.testParameter:
        conf.testParameter = conf.testParameter.replace(" ", "")
        conf.testParameter = conf.testParameter.split(",")
    else:
        conf.testParameter = []

    if conf.db:
        conf.db = conf.db.replace(" ", "")

    if conf.tbl:
        conf.tbl = conf.tbl.replace(" ", "")

    if conf.col:
        conf.col = conf.col.replace(" ", "")

    if conf.user:
        conf.user = conf.user.replace(" ", "")

    if conf.delay:
        conf.delay = float(conf.delay)

    if conf.rFile:
        conf.rFile = normalizePath(ntToPosixSlashes(conf.rFile))

    if conf.wFile:
        conf.wFile = normalizePath(ntToPosixSlashes(conf.wFile))

    if conf.dFile:
        conf.dFile = normalizePath(ntToPosixSlashes(conf.dFile))

    if conf.msfPath:
        conf.msfPath = normalizePath(ntToPosixSlashes(conf.msfPath))

    if conf.tmpPath:
        conf.tmpPath = normalizePath(ntToPosixSlashes(conf.tmpPath))

    if conf.googleDork or conf.list:
        conf.multipleTargets = True

def __setConfAttributes():
    """
    This function set some needed attributes into the configuration
    singleton.
    """

    debugMsg = "initializing the configuration"
    logger.debug(debugMsg)

    conf.cj              = None
    conf.dbmsHandler     = None
    conf.dumpPath        = None
    conf.httpHeaders     = []
    conf.hostname        = None
    conf.loggedToOut     = None
    conf.matchRatio      = None
    conf.md5hash         = None
    conf.multipleTargets = False
    conf.outputPath      = None
    conf.paramDict       = {}
    conf.parameters      = {}
    conf.paramFalseCond  = False
    conf.paramNegative   = False
    conf.path            = None
    conf.port            = None
    conf.progressWidth   = 54
    conf.retriesCount    = 0
    conf.scheme          = None
    #conf.seqMatcher      = difflib.SequenceMatcher(lambda x: x in " \t")
    conf.seqMatcher      = difflib.SequenceMatcher(None)
    conf.seqLock         = None
    conf.sessionFP       = None
    conf.start           = True
    conf.threadContinue  = True
    conf.threadException = False
    conf.wFileType       = None

def __setKnowledgeBaseAttributes():
    """
    This function set some needed attributes into the knowledge base
    singleton.
    """

    debugMsg = "initializing the knowledge base"
    logger.debug(debugMsg)

    kb.absFilePaths   = set()
    kb.bannerFp       = advancedDict()
    kb.data           = advancedDict()

    # Basic back-end DBMS fingerprint
    kb.dbms           = None
    kb.dbmsDetected   = False

    # Active (extensive) back-end DBMS fingerprint
    kb.dbmsVersion    = [ "Unknown" ]

    kb.dep            = None
    kb.docRoot        = None
    kb.headersCount   = 0
    kb.headersFp      = {}
    kb.htmlFp         = []
    kb.injParameter   = None
    kb.injPlace       = None
    kb.injType        = None

    # Back-end DBMS underlying operating system fingerprint via banner (-b)
    # parsing
    kb.os             = None
    kb.osVersion      = None
    kb.osSP           = None

    kb.parenthesis    = None
    kb.resumedQueries = {}
    kb.stackedTest    = None
    kb.targetUrls     = set()
    kb.timeTest       = None
    kb.unionComment   = ""
    kb.unionCount     = None
    kb.unionPosition  = None

def __saveCmdline():
    """
    Saves the command line options on a sqlmap configuration INI file
    format.
    """

    if not conf.saveCmdline:
        return

    debugMsg = "saving command line options on a sqlmap configuration INI file"
    logger.debug(debugMsg)

    config   = ConfigParser()
    userOpts = {}

    for family in optDict.keys():
        userOpts[family] = []

    for option, value in conf.items():
        for family, optionData in optDict.items():
            if option in optionData:
                userOpts[family].append((option, value, optionData[option]))

    for family, optionData in userOpts.items():
        config.add_section(family)

        optionData.sort()

        for option, value, datatype in optionData:
            if value is None:
                if datatype == "boolean":
                    value = "False"
                elif datatype in ( "integer", "float" ):
                    if option in ( "threads", "verbose" ):
                        value = "1"
                    elif option == "timeout":
                        value = "10"
                    else:
                        value = "0"
                elif datatype == "string":
                    value = ""

            if isinstance(value, str):
                value = value.replace("\n", "\n ")

            config.set(family, option, value)

    confFP = open(paths.SQLMAP_CONFIG, "wb")
    config.write(confFP)

    infoMsg = "saved command line options on '%s' configuration file" % paths.SQLMAP_CONFIG
    logger.info(infoMsg)

def __setVerbosity():
    """
    This function set the verbosity of sqlmap output messages.
    """

    if conf.verbose is None:
        conf.verbose = 1

    conf.verbose = int(conf.verbose)

    if conf.verbose == 1:
        logger.setLevel(logging.INFO)
    elif conf.verbose > 2 and conf.eta:
        conf.verbose = 2
        logger.setLevel(logging.DEBUG)
    elif conf.verbose == 2:
        logger.setLevel(logging.DEBUG)
    elif conf.verbose == 3:
        logger.setLevel(9)
    elif conf.verbose >= 4:
        logger.setLevel(8)

def __mergeOptions(inputOptions):
    """
    Merge command line options with configuration file options.

    @param inputOptions: optparse object with command line options.
    @type inputOptions: C{instance}
    """

    if inputOptions.configFile:
        configFileParser(inputOptions.configFile)

    if hasattr(inputOptions, "items"):
        inputOptionsItems = inputOptions.items()
    else:
        inputOptionsItems = inputOptions.__dict__.items()

    for key, value in inputOptionsItems:
        if not conf.has_key(key) or conf[key] is None or value is not None:
            conf[key] = value

def init(inputOptions=advancedDict()):
    """
    Set attributes into both configuration and knowledge base singletons
    based upon command line and configuration file options.
    """

    __mergeOptions(inputOptions)
    __setVerbosity()
    __saveCmdline()
    __setConfAttributes()
    __setKnowledgeBaseAttributes()
    __cleanupOptions()

    __setRequestFromFile()
    
    parseTargetUrl()

    __setHTTPTimeout()
    __setHTTPCookies()
    __setHTTPReferer()
    __setHTTPUserAgent()
    __setHTTPExtraHeaders()
    __setHTTPMethod()
    __setHTTPAuthentication()
    __setHTTPProxy()
    __setThreads()
    __setDBMS()
    __setOS()
    __setUnionTech()
    __setWriteFile()
    __setMetasploit()
    __setGoogleDorking()
    __setMultipleTargets()
    __urllib2Opener()

    update()
    queriesParser()
