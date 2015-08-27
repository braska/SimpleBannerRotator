function init() {
    var banner_w = Number($('input#width').val());
    var banner_h = Number($('input#height').val());
    $('.zone').each(function(){
        var h = Number($(this).find('#height').text());
        var w = Number($(this).find('#width').text());
        if (w >= banner_w && h >= banner_h) {
            $(this).find('.status').text('входит в размер этой зоны');
        }
        else{
            $(this).find('.status').text('');
        }
    });
}

$(document).ready(function(){
    init();
    $('input#width, input#height').change(function(){
        init();
    })
});