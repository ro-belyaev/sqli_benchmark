'''
unSSL.py

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

import core.controllers.outputManager as om

# options
from core.data.options.option import option
from core.data.options.optionList import optionList

from core.controllers.basePlugin.baseAuditPlugin import baseAuditPlugin
from core.controllers.w3afException import w3afException
from core.data.parsers.urlParser import getProtocol, allButScheme

import core.data.kb.knowledgeBase as kb
import core.data.kb.vuln as vuln
import core.data.constants.severity as severity


class unSSL(baseAuditPlugin):
    '''
    Find out if secure content can also be fetched using http.
    @author: Andres Riancho ( andres.riancho@gmail.com )
    '''

    def __init__(self):
        baseAuditPlugin.__init__(self)
        
        # Internal variables
        self._first_run = True
        self._ignore_next_calls = False

    def audit(self, freq ):
        '''
        Check if the protocol specified in freq is https and fetch the same URL using http. 
        ie:
            - input: https://a/
            - check: http://a/
        
        @param freq: A fuzzableRequest
        '''
        if self._ignore_next_calls:
            return
        else:            
            # Define some variables
            secure = freq.getURL()
            insecure = secure.replace('https://', 'http://')
            
            if self._first_run:
                try:
                    self._urlOpener.GET( insecure )
                except:
                    # The request failed because the HTTP port is closed or something like that
                    # we shouldn't test any other fuzzable requests.
                    self._ignore_next_calls = True
                    msg = 'HTTP port seems to be closed. Not testing any other URLs in unSSL.'
                    om.out.debug( msg )
                    return
                else:
                    # Only perform the initial check once.
                    self._first_run = False
                
            # It seems that we can request the insecure HTTP URL
            # (checked with the GET request)
            if 'HTTPS' == getProtocol( freq.getURL() ).upper():

                # We are going to perform requests that (in normal cases)
                # are going to fail, so we set the ignore errors flag to True
                self._urlOpener.ignore_errors( True )
                
                https_response = self._sendMutant( freq )
                freq.setURL( insecure )
                http_response = self._sendMutant( freq )
                
                if http_response.getCode() == https_response.getCode():
                    if http_response.getBody() == https_response.getBody():
                        v = vuln.vuln( freq )
                        v.setPluginName(self.getName())
                        v.setName( 'Secure content over insecure channel' )
                        v.setSeverity(severity.MEDIUM)
                        msg = 'Secure content can be accesed using the insecure protocol HTTP.'
                        msg += ' The vulnerable URLs are: "' + secure + '" - "' + insecure + '" .'
                        v.setDesc( msg )
                        v.setId( [http_response.id, https_response.id] )
                        kb.kb.append( self, 'unSSL', v )
                        om.out.vulnerability( v.getDesc(), severity=v.getSeverity() )

                # Disable error ignoring
                self._urlOpener.ignore_errors( False )
    
    def _analyzeResult( self, fuzzableRequest, res ):
        pass
        
    def getOptions( self ):
        '''
        @return: A list of option objects for this plugin.
        '''    
        ol = optionList()
        return ol

    def setOptions( self, OptionList ):
        '''
        This method sets all the options that are configured using the user interface 
        generated by the framework using the result of getOptions().
        
        @parameter OptionList: A dictionary with the options for the plugin.
        @return: No value is returned.
        ''' 
        pass

    def getPluginDeps( self ):
        '''
        @return: A list with the names of the plugins that should be runned before the
        current one.
        '''
        return []

    def getLongDesc( self ):
        '''
        @return: A DETAILED description of the plugin functions and features.
        '''
        return '''
        This plugin verifies that URL's that are available using HTTPS aren't available over an insecure
        HTTP protocol.

        To detect this, the plugin simply requests "https://abc/a.asp" and "http://abc.asp" and if both are 
        equal, a vulnerability is found.
        '''
