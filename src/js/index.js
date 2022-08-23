/**
 * Listen to scroll to change header opacity class
 */
function checkScroll(){
    var startY = $('.navbar').height() * 2; //The point where the navbar changes in px

    if($(window).scrollTop() > startY){
        $('.navbar').addClass("scrolled");
    }else{
        $('.navbar').removeClass("scrolled");
    }
}

if($('.navbar').length > 0){
    $(window).on("scroll load resize", function(){
        checkScroll();
    });
}



function goto_login()
{
    window.location.href = "login.php";
}

function goto_virtual_catechesis()
{
    window.location.href = "virtual/index.php";
}


function goto_online_enrollments()
{
    window.location.href = "publico/inscricoes.php";
}