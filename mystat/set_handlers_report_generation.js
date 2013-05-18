//<!--

function close_modal() {
    $('#modal').modal('hide');
    var root = $('#tests').children('ul').children('li');
    $('#tests')
        .jstree('deselect_all')
        .jstree('uncheck_all')
        .jstree('close_all',-1)
        .jstree('open_node', root);
    $('#alert-message').alert('close');
    $('#class-name').val('');
}


function setHandlers(tree) {

    $('#add-cancel').on('click', function () {
        close_modal();
    });

    $('#action-add').on('click', function () {
//        $('#tests').jstree('deselect_all');
        var customClassName = $('#class-name').val().replace(' ','');
        if(customClassName == ''){
            createAlertMessage('Введите имя custom-класса');
            $('.modal-body').scrollTop(document.getElementsByClassName('modal-body')[0].scrollHeight);  //to see the error message in the bottom of div
            return false;
        }
        unCheckHiddenNodes(tree);
        var tests_ids = [],
            tests = $.jstree._reference('#tests');
        $('#alert-message').alert('close');
        $('#tests')
            .jstree('deselect_all')
            .jstree('get_checked', null, true).each(function () {
                if(tests._get_children(this).length == 0){
                    tests_ids.push(this.id);
                }
            });
        checkHiddenNodes(tests_ids, tree);
        if(checkDependencesBetweenCriterions(tests_ids, tree)
            && checkDependencesBetweenClasses(tests_ids, tree)) {
            var type = $('input[name=tests-type]:checked').val();
            console.log(tests_ids);
            console.log(type);
            add_custom_class($('#class-name').val(), type, tests_ids);
            close_modal();
        } else {
            setTimeout(function () {
                $('.modal-body').scrollTop(document.getElementsByClassName('modal-body')[0].scrollHeight);
            },500);
            return false;
        }
    });

    $('#remove-cancel').on('click', function () {
        $('#modal-remove').modal('hide');
    });

    $('#remove-custom-class').on('click', function () {
        remove_custom_class();
        $('#modal-remove').modal('hide');
    });




    $('#add')
        .popover({
            title: "Добавить custom-класс",
            content: "Вы можете создать до 4 custom-классов для получения по ним статистики"
        })
        .on('click', function () {
            $('#header').text('Создание custom-класса');
            $('#class-name').val('');
            $('#modal').modal('show');
    });
    $('#delete')
        .popover({
            title: 'Удалить custom-класс',
            content: 'Для запуска анализатора вы должны указать хотя бы 1 custom-класс'
        })
        .on('click', function () {
            if($('.active').is('*')) {
                $('#modal-remove').modal('show');
//                return false;
            }
        });

}




//-->
