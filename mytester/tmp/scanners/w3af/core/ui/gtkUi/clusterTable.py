'''
clusterTable.py

Copyright 2008 Andres Riancho

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

# The clustering stuff
try:
    from extlib.cluster.cluster import HierarchicalClustering
except Exception, e:
    from cluster import HierarchicalClustering

# For window creation
import pygtk, gtk
import gobject
from . import helpers, entries

import threading

# For testing
from core.data.url.httpResponse import httpResponse as httpResponse
    
class clusterCellWindow(entries.RememberingWindow):
    def __init__ ( self, w3af, data=[] ):
        '''
        A window that stores the clusterCellData and the level changer.
        
        @parameter data: A list with the httpResponse objects to be clustered.
        '''
        # First we save the data        
        self._data = data
        self.w3af = w3af
        self._cl_data_widget = None
        
        # The level used in the process of clustering
        self._level = 50
        
        # Create a new window
        super(clusterCellWindow,self).__init__(
            w3af, "clusterWindow", "w3af - HTTP Response Clustering",
            "ClusterResponseTool")
        self.set_icon_from_file('core/ui/gtkUi/data/w3af_icon.png')
        self.set_size_request(400, 400)

        # Quit event.
        self.connect("delete_event", self.delete_event)
        
        # Create the main vbox
        main_vbox = gtk.VBox()
        
        # Create the distance pager
        dist_hbox = gtk.HBox()
        
        distanceLabel = gtk.Label()
        distanceLabel.set_text('Distance between clusters: ')
        
        distanceBackButton = gtk.Button(stock=gtk.STOCK_GO_BACK)
        distanceBackButton.connect("clicked", self._go_back)

        self._distanceLabelNumber = gtk.Label()
        self._distanceLabelNumber.set_text( str(self._level) )
        
        distanceNextButton = gtk.Button(stock=gtk.STOCK_GO_FORWARD)
        distanceNextButton.connect("clicked", self._go_forward)
        
        dist_hbox.pack_start( distanceLabel )
        dist_hbox.pack_start( distanceBackButton )
        dist_hbox.pack_start( self._distanceLabelNumber )
        dist_hbox.pack_start( distanceNextButton )
        main_vbox.pack_start(dist_hbox, False, False)

        # I'm going to store the cl_data_widget inside this scroll window
        self._sw = gtk.ScrolledWindow()
        self._sw.set_shadow_type(gtk.SHADOW_ETCHED_IN)
        self._sw.set_policy(gtk.POLICY_AUTOMATIC, gtk.POLICY_AUTOMATIC)
        main_vbox.pack_start( self._sw )

        self._createClusterCellDataWidget()       
        
        self.vbox.pack_start(main_vbox)
        self.vbox.pack_start(self._progressHBox)
        self.show_all()
        return

    def _createClusterCellDataWidget( self ):
        self._showThrobber()
        self._cl_data_widget = None
        def _helper():
            self._cl_data_widget = clusterCellData( self._data, level=self._level )
            self._sw.add( self._cl_data_widget )            
            
        # Create the widget that shows the data in a different thread
        threading.Thread(target=_helper).start()
        gobject.timeout_add(200, self._verify_if_finished)

    def _showThrobber( self ):
        # Create a throbber that indicates that we are calculating the clusters
        self.throbber = helpers.Throbber()
        self.throbber.running(True)
        self.calculating_label = gtk.Label()
        self.calculating_label.set_markup('<i>Creating clusters...</i>')
        self._progressHBox = gtk.HBox()
        self._progressHBox.pack_start( self.throbber )
        self._progressHBox.pack_start( self.calculating_label )
        self._progressHBox.show_all()
    
    def _hideThrobber( self ):
        self.throbber.hide()
        self.calculating_label.hide()
        self._progressHBox.hide()
        
    def _verify_if_finished( self ):
        if self._cl_data_widget:
            self._hideThrobber()
            self.throbber.running(False)
            return False
        else:
            return True
    
    def _go_back( self, i ):
        if self._level != 10:
            self._level -= 10
        self._distanceLabelNumber.set_text( str(self._level) )        
        # Load the new level
        self._cl_data_widget.setNewLevel( self._level )

    def _go_forward( self, i ):
        if self._level != 100:
            self._level += 10
        self._distanceLabelNumber.set_text( str(self._level) ) 
        # Load the new level
        self._cl_data_widget.setNewLevel( self._level )
        
    def delete_event(self, widget, event, data=None):
        #gtk.main_quit()
        return False

class clusterCellData(gtk.TreeView):

    def __init__ ( self, data, level=50 ):
        '''
        @parameter clusteredData: A list of objects that are clustered.
        '''
        # Save the data
        self._data = data
        
        # A cache of distances between httpResponses
        self._distance_cache = {}
        
        self.setNewLevel(level)
        
        self._add_tooltip_support()
        
        # Show it ! =)
        self.show_all()

    def setNewLevel(self, level):
        # Create the clusters
        cl = HierarchicalClustering(self._data, self._relative_levenshtein)
        clusteredData = cl.getlevel( level )
        
        self._parsed_clusteredData = self._parse( clusteredData )
        self._column_names = [ 'Group %d' % i for i in xrange(len(clusteredData)) ]

        # Start with the treeview and liststore creation
        dynamicListStoreTypes = [ str for i in xrange(len(self._column_names)) ]
        self.liststore = apply( gtk.ListStore, dynamicListStoreTypes )

        gtk.TreeView.__init__( self, self.liststore )

        # Show horizontal and vertical lines        	
        self.set_grid_lines(gtk.TREE_VIEW_GRID_LINES_BOTH)

        # First clear the treeview
        for col in self.get_columns():
            self.remove_column( col )
        
        # Internal variables
        self.current_path = None
        self.current_column = None

        self._colDict = {}
        for i, cname in enumerate( self._column_names ):
            colObject = gtk.TreeViewColumn( cname )
            self.append_column( colObject )
            textRenderer = gtk.CellRendererText()
            colObject.pack_start(textRenderer, True)
            colObject.set_attributes(textRenderer, text=i)
            # Save this for later. See FIXME below.
            self._colDict[ colObject ] = i
        
        for i in self._parsed_clusteredData:
            self.liststore.append( i )
        

    def _relative_levenshtein(self, a, b):
        '''
        Computes the relative levenshtein distance between two strings. Its in the range
        (0-1] where 1 means total equality.
        @param a: httpResponse object
        @param b: httpResponse object
        @return: A float with the distance
        '''
        # After some tests I realized that the amount of calls to this method was HUGE
        # It seems that python-cluster compares each pair (a,b) more than once!!
        # So I implemented this ratio cache...
        in_cache = self._distance_cache.get( (a.getId(), b.getId()), None )
        if in_cache is not None:
            return in_cache
            
        # Not in cache, perform calculations...
        a_str = a.getBody()
        b_str = b.getBody()

        m, n = (len(a_str),a_str), (len(b_str),b_str)
        #ensure that the 'm' tuple holds the longest string
        if(m[0] < n[0]):                
            m, n = n, m
        #assume distance = length of longest string (worst case)
        dist = m[0]
        # reduce the distance for each char match in shorter string   
        for i in range(0, n[0]):
            if m[1][i] == n[1][i]:
                dist = dist - 1
        
        # make it relative
        longer = float(max((len(a_str), len(b_str))))
        shorter = float(min((len(a_str), len(b_str))))    
        r = ((longer - dist) / longer) * (shorter / longer)
        r = 100 - r * 100
        
        # Save in cache
        self._distance_cache[ (a.getId(), b.getId()) ] = r
        self._distance_cache[ (b.getId(), a.getId()) ] = r
        return r
        
    def _add_tooltip_support( self ):
        # Add the "tool tips"
        popup_win = gtk.Window(gtk.WINDOW_POPUP)
        label = gtk.Label()
        popup_win.add(label)
        
        if "path-cross-event" not in gobject.signal_list_names( gtk.TreeView ):
            gobject.signal_new("path-cross-event", gtk.TreeView, gobject.SIGNAL_RUN_LAST, gobject.TYPE_BOOLEAN, (gtk.gdk.Event,))
            gobject.signal_new("column-cross-event", gtk.TreeView, gobject.SIGNAL_RUN_LAST, gobject.TYPE_BOOLEAN, (gtk.gdk.Event,))
            gobject.signal_new("cell-cross-event", gtk.TreeView, gobject.SIGNAL_RUN_LAST, gobject.TYPE_BOOLEAN, (gtk.gdk.Event,))
            
        self.connect("leave-notify-event", self.onTreeviewLeaveNotify, popup_win)
        self.connect("motion-notify-event", self.onTreeviewMotionNotify)
        
        self.connect("path-cross-event", self.emitCellCrossSignal)
        self.connect("column-cross-event", self.emitCellCrossSignal)
        
        self.connect("cell-cross-event", self.handlePopup, popup_win)
        
        # Handle double click on a row
        self.connect("row-activated", self.handleDoubleClick )    
        
    def _parse( self, clusteredData ):
        '''
        Takes a list like this one:
            [1 , 3, [4,5], [4, 5, 5], 9 ]
        
        It first creates a list like this one:
            [ [1, '', ''] ,
              [3, '', ''],
              [4, 5, ''],
              [4, 5, 5],
              [9, '', '']
            ]
        
        And finally it transposes it in order to return it.
            [ [1, 3, 4, 4, 9], 
              ['', '', 5, 5, ''],
              ['', '', '', 5, '']
            ]
        '''
        # First we find the largest list inside the original list
        largerList = [ len(i) for i in clusteredData if isinstance( i, type([]) ) ]
        largerList.sort()
        largerList.reverse()
        
        if len(largerList) == 0:
            # We don't have lists inside this list!
            largerList = 0
        else:
            largerList = largerList[0]
            
        paddedList = []
        for i in clusteredData:
            if isinstance( i, type([]) ):
                # We have a list to pad
                i = [ w.getId() for w in i]
                for j in xrange(largerList-len(i)):
                    i.append('')
                paddedList.append( i )
            else:
                # Its an object, create a list and pad it.
                tmp = []
                tmp.append( i.getId() )
                for j in xrange(largerList-len(tmp)):
                    tmp.append('')
                paddedList.append( tmp )
        
        # transpose
        resList = [ [ '' for w in range(len( paddedList ) ) ] for i in range(len( paddedList [0] ) ) ]
        
        for x, paddedList in enumerate(paddedList):
            for y, paddedItem in enumerate( paddedList ):
                resList[y][x] = str(paddedItem)
        return resList
            
    def onTreeviewLeaveNotify(self, treeview, event, popup_win):
        self.current_column = None
        self.current_path = None
        popup_win.hide()
    
    def onTreeviewMotionNotify(self, treeview, event):
    
        current_path, current_column = self.getCurrentCellData(treeview, event)[:2]
        
        if self.current_path != current_path:
            self.current_path = current_path
            treeview.emit("path-cross-event", event)
        
        if self.current_column != current_column:
            self.current_column = current_column
            treeview.emit("column-cross-event", event)
    
    def getCurrentCellData(self, treeview, event):
    
        try:
            current_path, current_column = treeview.get_path_at_pos(int(event.x), int(event.y))[:2]
        except:
            return (None, None, None, None, None, None)
            
        current_cell_area = treeview.get_cell_area(current_path, current_column)
        treeview_root_coords = treeview.get_bin_window().get_origin()
        
        cell_x = treeview_root_coords[0] + current_cell_area.x
        cell_y = treeview_root_coords[1] + current_cell_area.y
        
        cell_x_ = cell_x + current_cell_area.width
        cell_y_ = cell_y + current_cell_area.height
        
        return (current_path, current_column, cell_x, cell_y, cell_x_, cell_y_)

    def handleDoubleClick(self, treeview, path, view_column):
        # FIXME: I'm sure there is another way to do this... but... what a hell... nobody reads the code ;)
        # I'm talking about the self._colDict[ current_column ]!
        currentId = self.liststore[ path[0] ][ self._colDict[ view_column ] ]
        # Search the Id and show the data
        print 'I should show the data for',currentId, 'in a different window.'
    
    def _getInfoForId( self, id ):
        '''
        @return: A string with information about the request with id == id
        '''
        try:
            obj = [ i for i in self._data if i.getId() == int(id) ][0]
        except Exception, e:
            return ''
        else:
            return '<b><i>Code: </i></b>' + str(obj.getCode()) + '\n<b><i>Message: </i></b>' + obj.getMsg()\
           + '\n<b><i>URI: </i></b>' + obj.getURI()
        
    def handlePopup(self, treeview, event, popup_win):
        current_path, current_column, cell_x, cell_y, cell_x_, cell_y_ = self.getCurrentCellData(treeview, event)
        if cell_x is not None:
            # Search the Id and show the data
            # FIXME: I'm sure there is another way to do this... but... what a hell... nobody reads the code ;)
            # I'm talking about the self._colDict[ current_column ]!
            currentId = self.liststore[ current_path[0] ][ self._colDict[ current_column ] ]
            info = self._getInfoForId( currentId )
            if not info:
                # hide!
                popup_win.hide()
            else:
                # Use the info and display the window
                popup_win.get_child().set_markup( info )
                popup_width, popup_height = popup_win.get_size()
                pos_x, pos_y = self.computeTooltipPosition(treeview, cell_x, cell_y, cell_x_, cell_y_, popup_width, popup_height, event)
                popup_win.move(int(pos_x) , int(pos_y))
                popup_win.show_all()
        else:
            popup_win.hide()
    
    def emitCellCrossSignal(self, treeview, event):
        treeview.emit("cell-cross-event", event)
    
    def computeTooltipPosition(self, treeview, cell_x, cell_y, cell_x_, cell_y_, popup_width, popup_height, event):
        screen_width = gtk.gdk.screen_width()
        screeen_height = gtk.gdk.screen_height()
    
        pos_x = treeview.get_bin_window().get_origin()[0] + event.x - popup_width/2
        if pos_x < 0:
            pos_x = 0
        elif pos_x + popup_width > screen_width:
            pos_x = screen_width - popup_width
            
        pos_y = cell_y_ + 3
        if pos_y + popup_height > screeen_height:
            pos_y = cell_y - 3 - popup_height
        
        return (pos_x , pos_y)
        
def main():
    gtk.main()

if __name__ == "__main__":
    # We create the data
    data = [
        httpResponse(200, 'my data1 looks like this and has no errors', {}, 'http://a/index.html', 'http://a/index.html', id=1),
        httpResponse(200, 'errors? i like errors like this one: SQL', {}, 'http://a/index.html', 'http://a/index.html', id=2),
        httpResponse(200, 'my data is really happy', {}, 'http://a/index.html', 'http://a/index.html', id=3),
        httpResponse(200, 'my data1 loves me', {}, 'http://a/index.html', 'http://a/index.html', id=4),
        httpResponse(200, 'my data likes me', {}, 'http://a/index.html', 'http://a/index.html', id=5)
        ]
            
    cl_win = clusterCellWindow( data=data )
    main()
