//<!--

function createAlertMessage(str) {
    var message = '\<div id="alert-message" class="alert alert-error" style="margin-top: 15px; width: 80%;"\>' +
        '\<a class="close" data-dismiss="alert"\>x\</a\>' + str + '\</div\>';
    $('#alert-message').alert('close');
    $(message).insertAfter('#tests');
    $('#alert-message')
        .alert();
}

function check_scanners(scanners) {                         // check a tree of scanners
    if(scanners.length == 0){
        createAlertMessage("Выберите хотя бы 1 сканер для запуска!");
        return false;
    }
    else{
        return true;
    }
}

function check_tests(tests) {                                //check a tree of tests
    var result = true;
    var all_tests = tests._get_children(-1);
    all_tests
        .children('ul').children().each(function () {
//            to ask how to improve ???
            var $this = this;
//            to ask does it matter to save pointer this in the variable ???
            if($($this).hasClass('at-least-one')){
                if($('#tests').jstree('get_checked', $this, true).length == 0){
                    $('#tests')
                        .jstree('open_node',this)
                        .jstree('select_node',this);
                    tests._get_children(this).each(function () {
                        $('#tests').jstree('select_node',this);
                    });
                    createAlertMessage('Выберите хотя бы один пункт из множества:' + $($this).children('a').text());
                    result = false;
                    return false;
                }
            }
        })
        .find("[class *= 'depend']").each(function () {
            var dependences = $(this).attr('class').match(/depend-[^\s]+/)[0].split('-');
            var checked = $('#tests').jstree('get_checked', this, true);
            if(checked.length !=0){
                for(var i=1; i<dependences.length; i++){
                    var special_dependence = checked.filter("[class~='" + dependences[i] +"']");
                    if(special_dependence.length == 0){         //we don't have even one checked node of class dependence[i]!
                        //we should have at least one cheched node of this class (it's demand of json structure)
                        var message = "Вы должны выбрать хотя бы один из следующих пунктов:";
                        $(this).find("[class~='" + dependences[i] + "']").each(function () {
                            $('#tests').jstree('select_node', this);
                            message += "\<br\>-" + $(this).children('a').text();
                        });
                        createAlertMessage(message);
                        result = false;
                        return false;
                    }
                }
            }
        });
    return result;
}

//-->