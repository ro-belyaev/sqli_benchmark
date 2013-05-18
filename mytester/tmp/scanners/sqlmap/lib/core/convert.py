#!/usr/bin/env python

"""
$Id: convert.py 1360 2010-03-03 15:26:27Z inquisb $

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

try:
    import hashlib
except:
    import md5
    import sha
    
import sys
import struct
import urllib

def base64decode(string):
    return string.decode("base64")

def base64encode(string):
    return string.encode("base64")[:-1]

def hexdecode(string):
    string = string.lower()

    if string.startswith("0x"):
        string = string[2:]

    return string.decode("hex")
    
def hexencode(string):
    return string.encode("hex")

def md5hash(string):
    if sys.modules.has_key('hashlib'):
        return hashlib.md5(string).hexdigest()
    else:
        return md5.new(string).hexdigest()

def orddecode(string):
    packedString = struct.pack("!"+"I" * len(string), *string)
    return "".join([chr(char) for char in struct.unpack("!"+"I"*(len(packedString)/4), packedString)])

def ordencode(string):
    return tuple([ord(char) for char in string])

def sha1hash(string):
    if sys.modules.has_key('hashlib'):
        return hashlib.sha1(string).hexdigest()
    else:
        return sha.new(string).hexdigest()

def urldecode(string):
    result = None
    
    if string:
        result = urllib.unquote_plus(string)

    return result

def urlencode(string, safe=":/?%&=", convall=False):
    result = None

    if string is None:
        return result

    if convall:
        result = urllib.quote(string)
    else:
        result = urllib.quote(string, safe)

    return result
