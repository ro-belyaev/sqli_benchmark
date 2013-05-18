//<!--

$(function() {
    $('#continue').hide();
    var getParams = window.location.search.substr(1);
    var getParamsArr = getParams.split ("&");
    var params = {};

    for ( var i = 0; i < getParamsArr.length; i++) {
        var tmpArr = getParamsArr[i].split("=");
        params[tmpArr[0]] = tmpArr[1];
    }

    var launchWasCanceled = false;

    var launchId = params['launch'];
    if(launchId == undefined) {
        alert('you should specify GET-parameter launch and restart this page');
    } else {
        $('#continue').on('click', function() {
            var url = '../mystat/report_generation.html?launch=' + launchId;
            window.open(url, '_top', '', true);
        });

        $('#cancel').on('click', function() {
//        $.ajax({
//            type: "GET",
//            url: "./generation_cancel",
//            data: {
//                "id": params['id']
//            }
//        })
//            .done(function() {
            launchWasCanceled = true;
//                window.open('./main.php', '_top', '', true);
//            });
        });

        var intervalID = setInterval(function() {
            console.log('before ajax');
            $.ajax({
                type: "POST",
                url: './check_scanners_process.php',
                timeout: 3900,
                data: {
                    "launch": params['launch']
                },
                dataType: "json"
            })
                .done(function(progress) {
                    updateIndicators(progress);
                    if(scannersFinished(progress)) {
                        $('#continue').show();
                        clearInterval(intervalID);
                    }
                    if(launchWasCanceled) {
                        clearInterval(intervalID);
                    }
                })
                .fail(function() {
                    clearInterval(intervalID);
                    alert('При отправке ajax-запроса на сервер произошла ошибка');
                })
        }, 4000);
    }


});

function scannersFinished(progress) {
    var finish = true;
    for(var sc in progress) {
        if(progress[sc] != 100) {
            finish = false;
        }
    }
    return finish;
}

function updateIndicators(progress) {
    for(var scannerId in progress) {
        var w = progress[scannerId];
        $('dd#' + scannerId +' > div')
            .width(progress[scannerId] + '%');
    }
}

//-->
