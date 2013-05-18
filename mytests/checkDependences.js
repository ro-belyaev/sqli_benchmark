//<!--

function checkDependencesBetweenCriterions(checkedNodes, xmlString) {
    var parser = new DOMParser();
    var xml = parser.parseFromString(xmlString, "text/xml");
    var allDependences = xml.evaluate("//dependences-between-criterions/dependence", xml.documentElement, null,
        XPathResult.ORDERED_NODE_ITERATOR_TYPE, null);
    if(allDependences !== null) {
        var dependence = allDependences.iterateNext();
        while(dependence) {
            var typeOfDependence = xml.evaluate("@type", dependence, null, XPathResult.STRING_TYPE, null)
                .stringValue;
            if(typeOfDependence == 'at-least-one') {
                var dependentCriterions = xml.evaluate(".//dependent-criterion", dependence, null,
                    XPathResult.ORDERED_NODE_ITERATOR_TYPE, null);
                if(dependentCriterions !== null) {
                    var isOK = false;
                    var criterion = dependentCriterions.iterateNext();
                    while(criterion) {
                        var criterionID = xml.evaluate("@id", criterion, null, XPathResult.STRING_TYPE, null)
                            .stringValue;
                        var pattern = new RegExp(criterionID);
                        for(var nodeID in checkedNodes) {
                            if(checkedNodes[nodeID].search(pattern) != -1) {
                                isOK = true;
                            }
                        }
                        criterion = dependentCriterions.iterateNext();
                    }
                    if(!isOK) {
                        badDependenceBetweenCriterions(dependence, xml, "at-least-one");
                        return false;
                    }
                }
            }
            else if(typeOfDependence == 'each') {
                var dependentCriterions = xml.evaluate(".//dependent-criterion", dependence, null,
                    XPathResult.ORDERED_NODE_ITERATOR_TYPE, null);
                if(dependentCriterions !== null) {
                    var dependenceIsOK = true;
                    var criterion = dependentCriterions.iterateNext();
                    while(criterion) {
                        var criterionIsOK = false;
                        var criterionID = xml.evaluate("@id", criterion, null, XPathResult.STRING_TYPE, null)
                            .stringValue;
                        for(var nodeID in checkedNodes) {
                            var pattern = new RegExp(criterionID);
                            if(checkedNodes[nodeID].search(pattern) != -1) {
                                criterionIsOK = true;
                            }
                        }
                        criterion = dependentCriterions.iterateNext();
                        dependenceIsOK = dependenceIsOK && criterionIsOK;
                    }
                    if(!dependenceIsOK) {
                        badDependenceBetweenCriterions(dependence, xml, "each");
                        return false;
                    }
                }
            }
            dependence = allDependences.iterateNext();
        }
    }
    return true;
}

//todo what is faster: xpath request or array iteration?

function checkDependencesBetweenClasses(checkedNodes, xmlString) {
    var parser = new DOMParser();
    var xml = parser.parseFromString(xmlString, "text/xml");
    var alreadySeen = [];
    for(var node in checkedNodes) {
        var nodeID = checkedNodes[node].split('_')[0];
        if(alreadySeen.indexOf(nodeID) == -1) {
            alreadySeen.push(nodeID);
            var criterion = xml.evaluate("//dependences-between-classes/dependence/dependent-criterion[@id='" + nodeID + "']",
                xml.documentElement, null, XPathResult.FIRST_ORDERED_NODE_TYPE, null).singleNodeValue;
            if(criterion != null) {
                var criterionSiblings = xml.evaluate('following-sibling::* | preceding-sibling::*', criterion, null,
                    XPathResult.ORDERED_NODE_ITERATOR_TYPE, null);
                if(criterionSiblings != null) {
                    var oneSibling = criterionSiblings.iterateNext();
                    while(oneSibling) {
                        var isOK = false;
                        alreadySeen.push(oneSibling);
                        var siblingID = xml.evaluate('@id', oneSibling, null, XPathResult.STRING_TYPE, null).stringValue;
                        for(var someNodeID in checkedNodes) {
                            var pattern = new RegExp(siblingID);
                            if(checkedNodes[someNodeID].search(pattern) != -1) {
                                isOK = true;
                            }
                        }
                        if(!isOK) {
                            badDependenceBetweenClasses(siblingID, xml);
                            return false;
                        }
                        oneSibling = criterionSiblings.iterateNext();
                    }
                }
            }
        }
    }
    return true;
}


function unCheckHiddenNodes(xmlString) {
    var parser = new DOMParser();
    var xml = parser.parseFromString(xmlString, "text/xml");

    //uncheck all hidden nodes
    var xpathString = "//criterions/criterion[@type='hidden']";
    var criterions = xml.evaluate(xpathString, xml.documentElement, null, XPathResult.ORDERED_NODE_ITERATOR_TYPE, null);
    var criterion = criterions.iterateNext();
    while(criterion) {
        var criterionId = xml.evaluate("@id", criterion, null, XPathResult.STRING_TYPE, null).stringValue;
        var classes = xml.evaluate("condition", criterion, null, XPathResult.ORDERED_NODE_ITERATOR_TYPE, null);
        var oneClass = classes.iterateNext();
        while(oneClass) {
            var classValue = xml.evaluate("@value", oneClass, null, XPathResult.STRING_TYPE, null).stringValue;
            var hiddenNodeID = criterionId + '_' + classValue;
            var li = $('li#' + hiddenNodeID);
            $('#tests')
                .jstree('uncheck_node', li);
            oneClass = classes.iterateNext();
        }
        criterion = criterions.iterateNext();
    }
}

function checkHiddenNodes(checkedNodes, xmlString) {
    var parser = new DOMParser();
    var xml = parser.parseFromString(xmlString, "text/xml");

    //check all hidden nodes that should be passed to the server
    var xpathString = "//dependences-between-classes/dependence/" +
        "dependent-criterion[@id=//criterions/criterion[@type='hidden']/@id]";
    var criterions = xml.evaluate(xpathString, xml.documentElement, null, XPathResult.ORDERED_NODE_ITERATOR_TYPE, null);
    var criterion = criterions.iterateNext();
    while(criterion) {
        var criterionIsChecked = false;
        var criterionID = xml.evaluate("@id", criterion, null, XPathResult.STRING_TYPE, null).stringValue;
        var getSiblingsXPathString = "following-sibling::* | preceding-sibling::*";
        var siblings = xml.evaluate(getSiblingsXPathString, criterion, null, XPathResult.ORDERED_NODE_ITERATOR_TYPE, null);
        var sibling = siblings.iterateNext();
        while(sibling && !criterionIsChecked) {
            var siblingID = xml.evaluate("@id", sibling, null, XPathResult.STRING_TYPE, null).stringValue;
            var pattern = new RegExp('^' + siblingID + '_.+');
            for(var node in checkedNodes) {
                if(!criterionIsChecked) {
                    if(pattern.test(checkedNodes[node])) {
                        criterionIsChecked = true;
                    }
                }
            }
            sibling = siblings.iterateNext();
        }
        if(criterionIsChecked) {
            var shouldBeCheckedNodesXPathString = "//criterions/criterion[@id='" + criterionID + "']/condition";
            var shouldBeCheckedNodes = xml.evaluate(shouldBeCheckedNodesXPathString, xml.documentElement, null,
                XPathResult.ORDERED_NODE_ITERATOR_TYPE, null);
            var shouldBeCheckedNode = shouldBeCheckedNodes.iterateNext();
            while(shouldBeCheckedNode) {
                var classValue = xml.evaluate("@value", shouldBeCheckedNode, null,
                    XPathResult.STRING_TYPE, null).stringValue;
                var shouldBeCheckedNodeID = criterionID + '_' + classValue;
                $('#tests')
                    .jstree('check_node', $('#tests #' + shouldBeCheckedNodeID));
                checkedNodes.push(shouldBeCheckedNodeID);
                shouldBeCheckedNode = shouldBeCheckedNodes.iterateNext();
            }
        }
        criterion = criterions.iterateNext();
    }
}

//-->