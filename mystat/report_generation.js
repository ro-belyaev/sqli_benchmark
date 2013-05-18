//<!--

function getParams() {
    var getParams = window.location.search.substr(1);
    var getParamsArr = getParams.split ("&");
    var params = {};

    for ( var i = 0; i < getParamsArr.length; i++) {
        var tmpArr = getParamsArr[i].split("=");
        params[tmpArr[0]] = tmpArr[1];
    }
    return params;
}



$(function () {

    var tree = null;
    var params = getParams();
    var launchId = params['launch'];
    if(launchId == undefined) {
        alert('you should specify GET-parametr "launch", which corresponds to id of scanners launch,' +
            ' and then restart page');
    } else {
        $.ajax({
            url : "../mytests/get_launch_xml_tree.php",
//            url: "../mytests/get_whole_xml_tree.php",
            data: {
                "launch_id": launchId
            },
            dataType : "json"
        })
            .done(function(data) {
                console.log(data);
                var xml = data['xml'];
                tree = data['tree'];
                $('#tests')
                    .on('loaded.jstree', function(event) {
                        $('[data-type="hidden"]')
                            .each(function() {
                                $(this).hide();
                            });
                        setHandlers(tree);

                        $('#report-button')
                            .on('click', function() {
                                console.log(application.getNum());
                                if(application.getNum() == 0) {
                                    alert('You should add at least one custom class');
                                } else {
                                    var customClasses = JSON.stringify(application.getTests());
                                    $.ajax({
                                        type: "POST",
                                        url: "save_custom_classes.php",
                                        data: {
                                            classes: customClasses,
                                            launch_id: launchId
                                        },
                                        dataType: "text"
                                    })
                                        .done(function(reportId) {
                                            var url = './choose_report_scanners.html?report_id=' + reportId;
                                            window.open(url, '_top', '', true);
                                        })
                                        .fail(function() {
                                            alert('При отправке запроса на сервер для сохранения custom-классов ' +
                                                'произошла ошибка. Попробуйте перезагрузить страницу');
                                        })
                                }
                            });


                    })
                    .jstree({
                        "xml_data" : {
                            "data" : xml,
                            "xsl" : "nest"
                        },
                        "plugins" : ["themes", "xml_data", "ui", "languages", "checkbox"]
                    });
            })
            .fail(function() {
                alert('На ajax-запрос с сервера не пришёл ответ. Пожалуйста, перезагрузите страницу');
            });

    }

});

//-->