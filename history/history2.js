//<!--

$(function() {
    var lastTreeId = -1;
    $("[id ^= 'tests-actions']").each(function() {
        $(this).on('click', function(event) {
            var target = $(event.target);
            var accordionId = target.
                closest('.accordion-body')
                .attr('id');
            var testsId = accordionId.split('_')[1];
            var action = target
                .attr('id');

            if(action == 'tree') {
                if(lastTreeId != testsId) {
                    $.ajax({
                        type: 'GET',
                        url: './transform_slice_xml.php',
                        data: {
                            id: testsId
                        },
                        dataType: 'html'
                    })
                        .done(function(xml) {
                            $('#modal #some-tree')
                                .jstree({
                                    "xml_data" : {
                                        "data" : xml,
                                        "xsl" : "nest"
                                    },
                                    "plugins" : ["themes", "xml_data", "ui", "languages"]
                                });
                        })
                        .fail(function() {
                            alert('fail to load xml from server');
                        });
                    lastTreeId = testsId;
                }
                $('#modal').modal('show');

            } else if(action == 'delete') {
//                console.log('delete action');
                $.ajax({
                    url: '../mytests/generation_delete.php',
                    data: {
                        id: testsId
                    },
                    dataType: 'html'
                })
                    .done(function(status) {
                        console.log(status);
                        if(status == 'progress') {
                            alert('Этот тестовый набор в процессе генерации. Вы можете отменить генерацию');
                        } else if(status == 'ok') {
                            target
                                .closest('.accordion-group')
                                .remove();
                            alert('Тестовый набор успешно удалён!');
                        }
                    })
                    .fail(function() {
                        alert('при отправке ajax-запроса для удаления/отмены генерации произошла ошибка');
                    });
            }
        });
    });


    $('#close-tree')
        .on('click', function() {
            $('#modal').modal('hide');
        });
});

//-->