//<!--

$(function () {
    $.ajax({
        url: 'history.json',
        dataType: 'json'
    })
        .done(function(data) {
            var items = data['items'];
            if(items.length == 0){
                var text = 'Ваша история запусков сканеров пуста';
                $('<div><h3>' + text +'</h3></div>').appendTo('#accordion');
            }
            else{
                for(var i=0; i<items.length; i++){
                    var $this = items[i];
                    var innerBodyText = $('<div></div>')
                        .append('<p>' + 'Время работы каждого сканера:' + '</p>');
                    for(var j=0; j<$this['scanners'].length; j++){
                        var scanner = $this['scanners'][j];
                        innerBodyText
                            .append('<p>' + scanner + ' - ' + $this['info'][scanner] + '</p>');
                    }
                    var anchor = $('<a></a>')
                        .text('Отчёт')
                        .on('click', function () {
                            $.ajax({
                                url: 'report_generation.php',
                                data: $this['id']
                            })
                                .fail(function () {
                                    alert('На сервере произошла ошибка. Пожалуйста, перезагрузите страницу');
                                });
                        });
                    innerBodyText.append(anchor);
                    var innerBody = $('<div></div>')
                        .addClass('accordion-inner')
                        .append(innerBodyText);
//                        .text(innerBodyText);
                    var bodyId = 'collapse_' + $this.id;
                    var body = $('<div></div>')
                        .addClass('accordion-body collapse')
                        .attr('id', bodyId)
                        .append(innerBody);
                    var innerHeader = $('<a></a>')
                        .addClass('accordion-toggle')
                        .attr({
                            'data-toggle': 'collapse',
                            'data-parent': '#accordion',
                            href: '#' + bodyId
                        })
                        .text($this.name);
                    var header = $('<div></div>')
                        .addClass('accordion-heading')
                        .append(innerHeader);
                    $('<div></div>')
                        .addClass('accordion-group')
                        .append(header, body)
                        .appendTo('#accordion');
                }
            }
        })
        .fail(function () {
            alert('На сервере произошла ошибка. Пожалуйста, перезагрузите страницу');
        });
});

//-->