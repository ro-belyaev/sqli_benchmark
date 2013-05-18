function buildTreeFromJSON(nodeArray) {
    var notParsed = [];  //queue of items that don't have children yet
    var root;
    for(var i=0; i<nodeArray.length; i++){
        if(nodeArray[i].attr['data-parent_id'] == -1){
            root = nodeArray[i];
            nodeArray.splice(i,1);
            break;
        }
    }
    if(root!=undefined){
        notParsed.push(root);

        while(notParsed.length){
            var currentNode = notParsed.shift();
            for(var j=nodeArray.length; j>=0; --j){
                if(nodeArray[j]!= undefined && nodeArray[j].attr['data-parent_id'] == currentNode.attr['id']){
                    currentNode.children.push(nodeArray[j]);
                    notParsed.push(nodeArray[j]);
                    nodeArray.splice(j,1);
                }
            }
        }
    }
    return root;
}