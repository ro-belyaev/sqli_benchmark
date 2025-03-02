'''
gzip_handler.py

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
import urllib2
from cStringIO import StringIO
import gzip


class HTTPGzipProcessor(urllib2.BaseHandler):
    handler_order = 200  # response processing before HTTPEquivProcessor

    def http_request(self, request):
        request.add_header("Accept-Encoding", "gzip")
        return request

    def http_response(self, request, response):
        # post-process response
        enc_hdrs = response.info().getheaders("Content-encoding")
        for enc_hdr in enc_hdrs:
            if ("gzip" in enc_hdr) or ("compress" in enc_hdr):
                # Decompress
                try:
                    data = gzip.GzipFile(fileobj=StringIO(response.read())).read()
                except IOError:
                    # I get here when the response came from the cache
                    # where the responses are saved unziped but with the
                    # original headers
                    return response
                else:
                    # The response was successfully unziped
                    response.setBody(data)
                    return response
        return response

    https_request = http_request
    https_response = http_response
