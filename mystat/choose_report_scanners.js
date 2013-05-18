//<!--

$(function() {
    var params = getParams();
    var reportId = params['report_id'];
    if(reportId == undefined) {
        alert("you should specify GET parameter report_id and reload page!");
    } else {
        $.ajax({
            type: "POST",
            url: "./get_report_scanners.php",
            data: {
                report_id: reportId
            },
            dataType: "json"
        })
            .done(function(data) {
//                console.log(data);
                $('#scanners')
                    .on('loaded.jstree', function(event) {
                        $('#start')
                            .on('click', function() {
                                var scanner_ids = [];
                               $('#scanners')
                                   .jstree('get_checked', $('#scanners li[id="-1"]'), true)
                                   .each(function() {
                                       scanner_ids.push(this.id);
                                   });
                                if(scanner_ids.length == 0) {
                                    var warning = "Вы должны выбрать хотя бы один сканер/комбинацию сканеров";
                                    createAlertMessage(warning);
                                } else {
                                    $.ajax({
                                        type: "POST",
                                        url: "save_scanners.php",
                                        data: {
                                            report_id: reportId,
                                            scanners: scanner_ids
                                        },
                                        dataType: "text"
                                    })
                                        .done(function(data) {
//                                            console.log(data);
                                            if(data != "OK") {
                                                alert("Вы отправили неверные данные на сервер");
                                            } else {
//                                                open new page
                                            }
                                        })
                                        .fail(function() {
                                            alert("При отправке ajax-запроса на сервере произошла ошибка");
                                        })
                                }
                            });
                    })
                    .jstree({
                        xml_data: {
                            data: data,
                            xsl: "nest"
                        },
                        "plugins" : ["themes", "xml_data", "ui", "languages", "checkbox"]
                    });
            })
            .fail(function() {
                alert("Ошибка при отправки ajax-запроса. Перезагрузите страницу");
            });
    }
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