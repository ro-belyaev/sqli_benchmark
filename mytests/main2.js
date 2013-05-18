$(function() {

    $.ajax({
        url : './choose_lang.php',
        dataType : 'text'
    })
        .done(function(data) {
            console.log(data);
        });


    var lang = 'ru';

    $.ajax({
        url : "./get_whole_xml_tree.php",
        data: {
            "lang": lang
        },
        dataType : "json"
    })
        .done(function(data) {
            console.log(data);
            var xml = data['xml'];
            var tree = data['tree'];
            $('#tests')
                .on('loaded.jstree', function(event) {
                    $('[data-type="hidden"]')
                        .each(function() {
//                            console.log(this);
                            $(this).hide();
                        });

                    $('#start').on('click', function () {
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
                        if(checkDependencesBetweenCriterions(tests_ids, tree) &&
                            checkDependencesBetweenClasses(tests_ids, tree)) {
                            $.ajax({
                                type: "POST",
                                url: "start_generation.php",
//                                beforeSend: function(jqXHR) {
//                                    jqXHR.setRequestHeader('Accept-Encoding', '');
//                                },
                                data: {
                                    tests: tests_ids
                                },
                                success: function(generationID) {
                                    console.log(generationID);
                                    console.log('hello');
                                    var generationWindow = "http://localhost/generation/mytests/generation.html?id=" +
                                        generationID;
                                    var target = "generationWindow";
                                    window.open(generationWindow, "_top", "", true);
                                }
                            })
                                .fail(function () {
                                    alert("При запуске генерации тестового набора произошла ошибка.");
                                });
                        }
                    });
                })
                .jstree({
                    "xml_data" : {
                        "data" : xml,
                        "xsl" : "nest"
                    },
//                    "languages" : ["en", "ru"],
                    "plugins" : ["themes", "xml_data", "ui", "languages", "checkbox"]
                });
        })
        .fail(function() {
            alert('На ajax-запрос с сервера не пришёл ответ. Пожалуйста, перезагрузите страницу');
        });

});
