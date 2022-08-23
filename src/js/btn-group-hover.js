$(document).ready(function(){
    $('tr').on({
        mouseenter: function(){
            $(this)
                .find('.btn-group-hover').stop().fadeTo('fast',1)
                .find('.icon-white').addClass('icon-white-temp').removeClass('icon-white');
        },
        mouseleave: function(){
            $(this)
                .find('.btn-group-hover').stop().fadeTo('fast',0);
        }
    });

    $('.btn-group-hover').on({
        mouseenter: function(){
            $(this).removeClass('btn-group-hover')
                .find('.icon-white-temp').addClass('icon-white');
        },
        mouseleave: function(){
            $(this).addClass('btn-group-hover')
                .find('.icon-white').addClass('icon-white-temp').removeClass('icon-white');
        }
    });
})