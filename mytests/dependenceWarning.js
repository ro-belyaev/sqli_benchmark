//<!--


function createAlertMessage(str) {
    var message = '\<div id="alert-message" class="alert alert-error" style="margin-top: 15px; width: 80%;"\>' +
        '\<a class="close" data-dismiss="alert"\>x\</a\>' + str + '\</div\>';
    $('#alert-message').alert('close');
    $(message).insertAfter('#tests');
    $('#alert-message')
        .alert();
}


function badDependenceBetweenClasses(criterionID, xml) {
    var message = "Вы должны отметить хотя бы один из следующих классов:";
    var parser = new DOMParser();
    var criterion = xml.evaluate("/tree/criterions/criterion[@id='" + criterionID + "']", xml.documentElement, null,
        XPathResult.FIRST_ORDERED_NODE_TYPE, null).singleNodeValue;
    if(criterion != null) {
        var classes = xml.evaluate('condition', criterion, null, XPathResult.ORDERED_NODE_ITERATOR_TYPE, null);
        var classNode = classes.iterateNext();
        while(classNode) {
            var messagePostfix = xml.evaluate('description', classNode, null, XPathResult.STRING_TYPE, null)
                .stringValue;
            message += "\<br\>- " + messagePostfix;
            classNode = classes.iterateNext();
        }
    }
    createAlertMessage(message);
}

function badDependenceBetweenCriterions(dependence, xml, dependenceType) {
    var message;
    switch(dependenceType) {
        case "at-least-one":
            message = "Вы должны выбрать хотя бы один класс одного из следующих критериев ";
            break;
        case "each":
            message = "Вы должны выбрать хотя бы по одному классу из следующих критериев: ";
            break;
    }
    var parser = new DOMParser();
    var criterions = xml.evaluate('dependent-criterion', dependence, null, XPathResult.ORDERED_NODE_ITERATOR_TYPE, null);
    if(criterions != null) {
        var arrayOfMessages = [];
        var criterion = criterions.iterateNext();
        while(criterion) {
            var criterionID = xml.evaluate('@id', criterion, null, XPathResult.STRING_TYPE, null).stringValue;
            var nodeName = xml.evaluate("/tree/nodes/node[@id=/tree/criterions/criterion[@id='" +  criterionID + "']/@container]/name",
                criterion, null, XPathResult.STRING_TYPE, null).stringValue;
            if(arrayOfMessages.indexOf(nodeName) == -1) {
                arrayOfMessages.push(nodeName);
            }
//            to ask why it doesn't work
//            var node = xml.evaluate("/tree/nodes/node[@id=/tree/criterions/criterion[@id=./@id]/@container]",
//                criterion, null, XPathResult.FIRST_ORDERED_NODE_TYPE, null).singleNodeValue;
            criterion = criterions.iterateNext();
        }
    }
    for(var i in arrayOfMessages) {
        message += '\<br\>- ' + arrayOfMessages[i];
    }
    createAlertMessage(message);
}

//-->