//<!--

$(function () {
    var xml;
    $.ajax({
        url: './convert_from_xml.php',
        dataType: 'json'
    })
        .done(function(data) {
            xml = data.xml;
            var root = buildTreeFromJSON(data.nodes);
            if(root!=undefined){

                $('#tests').jstree({
                    'json_data': {
                        'data': root
                    },
                    'plugins': ["themes", "json_data", "ui", "checkbox"]
                });


                $('#start').on('click', function () {
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
                    if(checkDependencesBetweenCriterions(tests_ids, xml) && checkDependencesBetweenClasses(tests_ids, xml)) {
                        $.ajax({
                            type: "POST",
                            url: "start_generation.php",
                            data: {
                                tests: tests_ids
                            },
                            success: function(data) {
                                console.log(data);
                            }
                        })
                            .fail(function () {
                                alert("При запуске генерации тестового набора произошла ошибка.");
                            });
                    }
                });

            }
            else {
                alert('С сервера пришла XML плохой структуры. Не удалось построить дерево тестового набора.');
            }
    })
        .fail(function () {
            alert('На сервере произошла ошибка. Пожалуйста, перезагрузите страницу');
        });



});