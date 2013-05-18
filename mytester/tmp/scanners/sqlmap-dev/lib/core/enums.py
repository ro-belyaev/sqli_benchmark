#!/usr/bin/env python

"""
$Id: enums.py 2312 2010-11-08 12:36:48Z inquisb $

Copyright (c) 2006-2010 sqlmap developers (http://sqlmap.sourceforge.net/)
See the file 'doc/COPYING' for copying permission
"""

class PRIORITY:
    LOWEST  = -100
    LOWER   = -50
    LOW     = -10
    NORMAL  = 0
    HIGH    = 10
    HIGHER  = 50
    HIGHEST = 100

class DBMS:
    MYSQL       = "MySQL"
    ORACLE      = "Oracle"
    POSTGRESQL  = "PostgreSQL"
    MSSQL       = "Microsoft SQL Server"
    SQLITE      = "SQLite"
    ACCESS      = "Microsoft Access"
    FIREBIRD    = "Firebird"
    MAXDB       = "SAP MaxDB"
    SYBASE      = "Sybase"

class PLACE:
    GET     = "GET"
    POST    = "POST"
    URI     = "URI"
    COOKIE  = "Cookie"
    UA      = "User-Agent"

class HTTPMETHOD:
    GET     = "GET"
    POST    = "POST"
    HEAD    = "HEAD"

class NULLCONNECTION:
    HEAD    = "HEAD"
    RANGE   = "Range"
