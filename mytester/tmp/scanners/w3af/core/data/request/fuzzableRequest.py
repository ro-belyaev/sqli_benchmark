'''
fuzzableRequest.py

Copyright 2006 Andres Riancho

This file is part of w3af, w3af.sourceforge.net .

w3af is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation version 2 of the License.

w3af is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with w3af; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

'''

from core.controllers.w3afException import w3afException
import core.controllers.outputManager as om
from core.data.dc.dataContainer import dataContainer as dc
from core.data.dc.cookie import cookie as cookie
import core.data.kb.config as cf
from core.data.parsers.urlParser import *
import copy
import urllib

#CR = '\r'
CR = ''
LF = '\n'
CRLF = CR + LF
SP = ' '

class fuzzableRequest(object):
    '''
    This class represents a fuzzable request. Fuzzable requests where created to allow w3af plugins
    to be much simpler and dont really care if the vulnerability is in the postdata, querystring, header, cookie
    or some other variable.
    
    Other classes should inherit from this one and change the behaviour of getURL() and getData(). For
    example: the class httpQsRequest should return the _dc in the querystring ( getURL ) and httpPostDataRequest
    should return the _dc in the POSTDATA ( getData() ).
    
    @author: Andres Riancho ( andres.riancho@gmail.com )
    '''

    def __init__(self):
        
        # Internal variables
        self._url = ''
        self._method = 'GET'
        self._uri = ''
        self._data = ''
        self._headers = {}
        self._cookie = None
        self._dc = dc()

        # Set the internal variables
        self._sent_information_comparable = None
    
    def dump( self ):
        '''
        @return: a DETAILED str representation of this fuzzable request.
        '''
        result_string = ''
        result_string += self.dumpRequestHead()
        result_string += CRLF
        if self.getData():
            result_string += str( self.getData() )
        return result_string
    
    def getRequestLine(self):
        '''Return request line.'''
        return self.getMethod() + SP + self.getURI() + SP + 'HTTP/1.1' + CRLF

    def dumpRequestHead( self ):
        '''
        @return: A string with the head of the request
        '''
        res = ''
        res += self.getRequestLine()
        res += self.dumpHeaders()
        return res
    
    def dumpHeaders( self ):
        '''
        @return: a str representation of the headers.
        '''
        result_string = ''
        for header in self._headers:
            result_string += header + ': ' + self._headers[ header ] + CRLF
        return result_string

    def export( self ):
        '''
        METHOD,URL,DC
        Examples:
        GET,http://localhost/index.php?abc=123&def=789,
        POST,http://localhost/index.php,abc=123&def=789
        
        @return: a csv str representation of the request
        '''
        #
        #   FIXME: What if a comma is inside the URL or DC?
        #   TODO: Why don't we export headers and cookies?
        #
        str_res = ''
        str_res += self._method + ',' 
        str_res += self._url

        if self._method == 'GET': 
            if self._dc:
                str_res += '?'
                str_res += str(self._dc)         
            str_res += ','
        else:
            str_res += ','
            if self._dc:
                str_res += str(self._dc)
        return str_res
                    
    def sent(self, smth_interesting):
        '''
        Checks if something similar to smth_interesting was sent in the request.
        This is used to remove false positives, e.g. if a grep plugin finds a "strange"
        string and wants to be sure it was not generated by an audit plugin.
        
        This method should only be used by grep plugins which often have false
        positives.
        
        The following example shows that we sent d'z"0 but d\'z"0 will
        as well be recognised as sent
        
        >>> f = fuzzableRequest()
        >>> f._uri = 'http://example.com/a?p=d\'z"0&paged=2'
        >>> f.sent('d%5C%27z%5C%220')
        True
        >>> f._data = 'p=<SCrIPT>alert("bsMs")</SCrIPT>'
        >>> f.sent('<SCrIPT>alert(\"bsMs\")</SCrIPT>')
        True
        >>> f = fuzzableRequest()
        >>> f._uri = 'http://example.com/?p=<ScRIPT>a=/PlaO/%0Afake_alert(a.source)</SCRiPT>'
        >>> f.sent('<ScRIPT>a=/PlaO/fake_alert(a.source)</SCRiPT>')
        True

        @parameter smth_interesting: The string
        @return: True if something similar was sent
        '''
        def make_comparable(heterogen_string):
            '''
            This basically removes characters that are hard to compare
            '''
            heterogen_characters = ['\\', '\'', '"', '+',' ', chr(0), 
                                    chr(int("0D",16)), chr(int("0A",16)) ]
            #heterogen_characters.extend(string.whitespace)
            for hetero_char in heterogen_characters:
                if hetero_char in heterogen_string:
                    heterogen_string = heterogen_string.replace(hetero_char,'')
            return heterogen_string
        
        #This is the easy part. If it was exactly like this in the request
        if smth_interesting in self._data or smth_interesting in self.getURI() or \
        smth_interesting in urllib.unquote(self._data) or smth_interesting in urllib.unquote(self.getURI()):
            return True
        
        return False
        
        #Ok, it's not in it but maybe something similar
        #Let's set up something we can compare
        if self._sent_information_comparable is None:
            data = self._uri + self._data + str(self._dc)
            self._sent_information_comparable = make_comparable(data + urllib.unquote(data))
        
        minLength = 3
        #make the smth_interesting comparable
        smth_interesting_comparables = []
        smth_interesting_comparables.append(make_comparable(smth_interesting))
        smth_interesting_comparables.append(make_comparable(urllib.unquote(smth_interesting)))
        for smth_interesting_comparable in smth_interesting_comparables:
            #We don't want false negatives just because the string is short after making comparable
            if len(smth_interesting_comparable) >= minLength and \
            smth_interesting_comparable in self._sent_information_comparable:
                return True
        print str(smth_interesting_comparables), "not in", self._sent_information_comparable
        # I didn't sent the smth_interesting in any way
        return False

    def __str__( self ):
        '''
        Return a str representation of this fuzzable request.
        '''
        result_string = ''
        result_string += self._url
        result_string += ' | Method: ' + self._method
        
        if self._dc:
            result_string += ' | Parameters: ('
            
            # Mangle the value for printing
            for param_name in self._dc:

                #
                # Because of repeated parameter names, we need to add this:
                #
                for the_value in self._dc[param_name]:

                    # the_value is always a string
                    if len(the_value) > 10:
                        the_value = the_value[:10] + '...'
                    the_value = '"' + the_value + '"'
                    
                    result_string += param_name + '=' + the_value + ', '
                    
            result_string = result_string[: -2]
            result_string += ')'
        return result_string
        
    def __eq__( self, other ):
        '''
        Two requests are equal if:
            - They have the same URL
            - They have the same method
            - They have the same parameters
            - The values for each parameter is equal
        
        @return: True if the requests are equal.
        '''
        if self._uri == other._uri and\
        self._method == other._method and\
        self._dc == other._dc:
            return True
        else:
            return False
            
    def is_variant_of(self, other):
        '''
        Two requests are loosely equal (or variants) if:
            - They have the same URL
            - They have the same HTTP method
            - They have the same parameter names
            - The values for each parameter have the same type (int / string)
            
        @return: True if self and other are variants.
        '''
        if self._uri == other._uri and\
        self._method == other._method and\
        self._dc.keys() == other._dc.keys():
                
            #
            #   Ok, so it has the same URI, method, dc:
            #   I need to work now :(
            #
            
            #   What I do now, is check if the values for each parameter has the same
            #   type or not.
            for param_name in self._dc:
                
                #   repeated parameter names
                for index in xrange(len(self._dc[param_name])):
                    try:
                        #   I do it in a try, because "other" might not have that many repeated
                        #   parameters, and index could be out of bounds.
                        value_self = self._dc[param_name][index]
                        value_other = other._dc[param_name][index]
                    except IndexError, e:
                        return False
                    else:
                        if value_other.isdigit() and not value_self.isdigit():
                            return False
                        elif value_self.isdigit() and not value_other.isdigit():
                            return False

            return True
        else:
            return False
        
    
    def __ne__( self,other):
        return not self.__eq__( other )
    
    def setURL( self , url ):
        self._url = url.replace(' ', '%20')
        self._uri = self._url
    
    def setURI( self, uri ):
        self._uri = uri.replace(' ', '%20')
        self._url = uri2url( uri )
        
    def setMethod( self , method ):
        self._method = method
        
    def setDc( self , dataCont ):
        if isinstance(dataCont, dc):
            self._dc = dataCont
        else:
            msg = 'Invalid call to fuzzableRequest.setDc(), the argument must be a'
            msg += ' dataContainer instance.'
            raise w3afException( msg )
        
    def setHeaders( self , headers ):
        self._headers = headers
    
    def setReferer( self, referer ):
        self._headers[ 'Referer' ] = referer
    
    def setCookie( self , c ):
        '''
        @parameter cookie: A cookie object as defined in core.data.dc.cookie, or a string.
        '''
        if isinstance( c, cookie):
            self._cookie = c
        elif isinstance( c, basestring ):
            self._cookie = cookie( c )
        elif c is None:
            self._cookie = None
        else:
            om.out.error('[fuzzableRequest error] setCookie received: "' + str(type(c)) + '" , "' + repr(c) + '"'  )
            raise w3afException('Invalid call to fuzzableRequest.setCookie()')
            
    def getURL( self ):
        return self._url
    
    def getURI( self ):
        return self._uri
        
    def setData( self, d ):
        '''
        The data is the string representation of the dataContainer, in most cases it wont be set.
        '''
        self._data = d
        
    def getData( self ):
        '''
        The data is the string representation of the dataContainer, in most cases it will be used as the POSTDATA for requests.
        Sometimes it is also used as the query string data.
        '''
        return self._data
        
    def getMethod( self ):
        return self._method
        
    def getDc( self ):
        return self._dc
        
    def getHeaders( self ):
        return self._headers
    
    def getReferer( self ):
        if 'Referer' in self._headers['headers']:
            return self._headers['Referer']
        else:
            return ''
    
    def getCookie( self ):
        if self._cookie:
            return self._cookie
        else:
            return None
    
    def getFileVariables( self ):
        return []
    
    def copy( self ):
        newFr = copy.deepcopy( self )
        return newFr

    def __repr__( self ):
        return '<fuzzable request | '+ self.getMethod() +' | '+ self.getURI() +' >'
