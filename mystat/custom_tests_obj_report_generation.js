//<!--

var application = (function () {
    var num = 0;
    var tests = [];
    return {
        addAppClass : function(name, tests_type, arr_with_id) {
            if(typeof name == 'string' && typeof arr_with_id!='undefined'){
                var obj = {
                    name: name,
                    type: tests_type,
                    id: arr_with_id
                };
                tests.push(obj);
                num++;
                return true;
            }
            else{
                return false;
            }
        },
        removeClass : function(number) {
            if(tests[number]!=undefined){
                tests.splice(number, 1);
                num--;
            }
        },
        getNum : function () {
            return num;
        },
        getTests : function () {
            return tests;
        }
    }
}());

function add_custom_class(name, tests_type, id) {
    console.log('in add');
    console.log(name, id);
    if(!application.addAppClass(name, tests_type, id)){
        return;
    }
    var a =  $('<a>' + application.getNum() + '. ' + name + '</a>');
    a.on('click', function () {
        $(this)
            .parent().siblings().removeAttr('class')
            .end().attr('class', 'active');

    });
    $('<li></li>')
        .append(a)
        .appendTo('#custom-classes');
}


function remove_custom_class () {
    var pos = $('.active').index();
    console.log('pos');
    console.log(pos);
    application.removeClass(pos);
    $('.active').remove();
    $('#custom-classes').children('li').each(function () {
        var $this = $(this);
        $this.children('a').text(
            $this.children('a').text().replace(/\d+/, function (match) {
                console.log(match,$this);
                return $this.index() + 1;
            })
        );
    });
}




//-->