//<!--

$(function() {
    var params = getParams();
    $.ajax({
        url: "./transform_scanner_xml.php",
        dataType: "json"
    })
        .done(function(data) {
            $('#scanners')
                .jstree({
                    "xml_data": {
                        "data": data['xml'],
                        "xsl": "nest"
                    },
                    "plugins" : ["themes", "xml_data", "ui", "languages", "checkbox"]
                });
            $('#start')
                .on('click', function() {
                    var scanner_ids = [];
                    $('#scanners')
                        .jstree('get_checked', $('#scanners li[id="-1"]'), true)
                        .each(function() {
                            scanner_ids.push(this.id);
                        });
                    if(scanner_ids.length == 0) {
                        var warning = 'Вы должны выбрать хотя бы один сканер';
                        createAlertMessage(warning);
                    } else {
                        $.ajax({
                            url: './run_tests_new.php',
                            type: 'POST',
                            data: {
                                scanner_id: scanner_ids,
                                set: params['set']
                            },
                            dataType: 'text'
                        })
                            .done(function(launchId) {
                                var url = './run.php?launch=' + launchId;
                                window.open(url, '_top', '', true);
//                                console.log(launchId);
                            })
                            .fail(function() {
                                alert('При отправке ajax-запроса произошла ошибка');
                            });
                    }
                });
        })
        .fail(function() {
            alert('На ajax-запрос не пришёл ответ с сервера. Пожалуйста, перезагрузите страницу');
        });
});

function createAlertMessage(str) {
    var message = '\<div id="alert-message" class="alert alert-error" style="width: 80%;"\>' +
        '\<a class="close" data-dismiss="alert"\>x\</a\>' + str + '\</div\>';
    $('#alert-message').alert('close');
    $(message).insertBefore('#start');
    $('#alert-message').alert();
}

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
//-->