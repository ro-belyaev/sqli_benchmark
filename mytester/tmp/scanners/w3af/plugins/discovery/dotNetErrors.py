'''
dotNetErrors.py

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

from core.controllers.basePlugin.baseDiscoveryPlugin import baseDiscoveryPlugin

import core.data.kb.knowledgeBase as kb
import core.data.kb.vuln as vuln
import core.data.constants.severity as severity

from core.data.bloomfilter.pybloom import ScalableBloomFilter

import core.data.parsers.urlParser as urlParser
from core.controllers.w3afException import w3afException


class dotNetErrors(baseDiscoveryPlugin):
    '''
    Request specially crafted URLs that generate ASP.NET errors in order to gather information.
    @author: Andres Riancho ( andres.riancho@gmail.com )
    '''

    def __init__(self):
        baseDiscoveryPlugin.__init__(self)

        # Internal variables
        self._already_tested = ScalableBloomFilter()

    def discover(self, fuzzableRequest ):
        '''
        Requests the special filenames.
        
        @parameter fuzzableRequest: A fuzzableRequest instance that contains (among other things) the URL to test.
        '''
        if fuzzableRequest.getURL() not in self._already_tested:
            self._already_tested.add( fuzzableRequest.getURL() )

            # Generate the URLs to GET
            to_test = self._generate_URLs( fuzzableRequest.getURL() )
            for url in to_test:
                try:
                    response = self._urlOpener.GET( url, useCache=True )
                except KeyboardInterrupt,e:
                    raise e
                except w3afException,w3:
                    om.out.error( str(w3) )
                else:
                    self._analyze_response( response )

    def _generate_URLs(self, original_url):
        '''
        Generate new URLs based on original_url.

        @parameter original_url: The original url that has to be modified in order to trigger errors in the remote application.
        '''
        res = []
        special_chars = ['|', '~']

        filename = urlParser.getFileName( original_url )
        if filename != '' and '.' in filename:
            splitted_filename = filename.split('.')
            extension = splitted_filename[-1:][0]
            name = '.'.join( splitted_filename[0:-1] )

            for char in special_chars:
                new_filename = name + char + '.' + extension
                new_url = urlParser.urlJoin( urlParser.getDomainPath(original_url), new_filename)
                res.append( new_url )

        return res
                

    def _analyze_response(self, response):
        '''
        @parameter response: The httpResponse object that holds the content of the response to analyze.
        '''
        # Remember that httpResponse objects have a faster "__in__" than
        # the one in strings; so string in response.getBody() is slower than
        # string in response        
        viewable_remote_machine = '<b>Details:</b> To enable the details of this'
        viewable_remote_machine += ' specific error message to be viewable on remote machines'
        if viewable_remote_machine not in response\
        and '<h2> <i>Runtime Error</i> </h2></span>' in response:
            v = vuln.vuln( response )
            v.setPluginName(self.getName())
            v.setId( response.id )
            v.setSeverity(severity.LOW)
            v.setName( 'Information disclosure' )
            msg = 'Detailed information about ASP.NET error messages can be viewed from remote'
            msg += '  sites. The URL: "' + response.getURL() + '" discloses detailed error'
            msg += ' messages.'
            v.setDesc( msg )
            kb.kb.append( self, 'dotNetErrors', v )
                
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
        return ['grep.errorPages']
        
    def getLongDesc( self ):
        '''
        @return: A DETAILED description of the plugin functions and features.
        '''
        return '''
        Request specially crafted URLs that generate ASP.NET errors in order to gather information
        like the ASP.NET version. Some examples of URLs that generate errors are:
            - default|.aspx
            - default~.aspx
        '''
