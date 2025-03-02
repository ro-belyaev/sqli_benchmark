'''
export.py

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
import core.data.kb.config as cf
import core.data.request.wsPostDataRequest as wsPostDataRequest
import core.data.request.httpPostDataRequest as httpPostDataRequest
from core.controllers.w3afException import w3afException, w3afMustStopException

class export:
    """
    This class helps to export stuff.
    
    @author: Kevin Denver ( muffysw@hotmail.com )
    """

    def __init__(self):
        pass

    def exportFuzzableRequestList( self, fuzzableRequestList ):
        '''
        Exports a list of fuzzable requests to a user configured file.
        '''
        if not hasattr(fuzzableRequestList,'__iter__'):
            return
        filename = cf.cf.getData('exportFuzzableRequests')
        try:
            file = open(filename, 'w')
            file.write('HTTP-METHOD,URI,POSTDATA\n')
        
            for fuzzRequest in fuzzableRequestList:
                # TODO: How shall we export wsPostDataRequests?
                if not isinstance(fuzzRequest, wsPostDataRequest.wsPostDataRequest):
                    file.write(fuzzRequest.export() + '\n')
            
            file.close()
        except Exception, e:
            msg = 'An exception was raised while trying to export fuzzable requests to the'
            msg += ' output file.' + str(e)
            raise w3afException( msg )
