$(document).ready(function(){

    $('body').on('mouseover', 'span[data-id]', function(){
        var id = $(this).attr('data-id');
        $('div[data-id="'+id+'"]').css({'border' : 'dotted 1px blue'});
    });

    $('body').on('mouseleave', 'span[data-id]', function(){
        var id = $(this).attr('data-id');
        $('div[data-id="'+id+'"]').css({'border' : ''});
    });

    $('body').on('click', 'span[data-id]', function(){
        var r = window.confirm("Czy na pewno chcesz usunąć tę wiadomość?");
        if(!r){
            return false;
        }
        var id = $(this).attr('data-id');
        var params = {
            'messageId' : id
        };
        $.ajax({
            type: "POST",
            dataType: "json",
            url: deletePath,
            data: params
        }).done(function(msg) {
            if(msg.status === 1) {
                $('div[data-id="' + id + '"]').remove();
            }
        });
    });

});