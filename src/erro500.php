<?php
require_once(__DIR__ . '/core/config/catechesis_config.inc.php');
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <title>CatecheSis - Erro interno</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="<?= constant('CATECHESIS_BASE_URL')?>/css/bootstrap.min.css">
  <link rel="stylesheet" href="<?= constant('CATECHESIS_BASE_URL')?>/css/custom-navbar-colors.css">
  <link rel="stylesheet" href="<?= constant('CATECHESIS_BASE_URL')?>/css/forkit.custom.css">
  <script src="<?= constant('CATECHESIS_BASE_URL')?>/js/jquery.min.js"></script>
  <script src="<?= constant('CATECHESIS_BASE_URL')?>/js/bootstrap.min.js"></script>

  
  
  <style>
  	@media print
	{    
	    .no-print, .no-print *
	    {
		display: none !important;
	    }
	    
	    
	    a[href]:after {
		    content: none;
		  }
		  
	    /*@page {
		    size: 297mm 210mm;*/ /* landscape */
		    /* you can also specify margins here: */
		    /*margin: 35mm;*/
		    /*margin-right: 45mm;*/ /* for compatibility with both A4 and Letter */
		 /* }*/
		  
	}
	@media screen
	{
		.only-print, .only-print *
		{
			display: none !important;
		}
	}

    body {
        background-color: #000000;
    }

    #img_texto
    {
        max-width: 60%;
        margin-left: 10%;
    }


    #img_igreja_parent
    {
        position: relative;
        float: right;
        height: 30%;
        width: 30%;
    }

    #img_igreja_apagada
    {
        position: relative;
        top: 0;
        left: 0;
    }

    #img_igreja_acesa
    {
        position: absolute;
        top: 0px;
        left: 0px;
        opacity: 1;
    }

  </style>

</head>
<body>
<link rel="shortcut icon" href="<?= constant('CATECHESIS_BASE_URL')?>/img/favicon.png" type="image/png">
<link rel="icon" href="<?= constant('CATECHESIS_BASE_URL')?>/img/favicon.png" type="image/png">

<!-- The contents (if there's no contents the ribbon acts as a link) -->
<div class="forkit-curtain">
</div>

<!-- The light string -->
<a class="forkit" data-text="" data-text-detached="" href=""></a>

<img id="img_texto" src="<?= constant('CATECHESIS_BASE_URL')?>/img/Erro_500_texto.png" class="img-responsive">

<div class="container no-print" id="">

    <div id="img_igreja_parent">
        <img id="img_igreja_acesa" src="<?= constant('CATECHESIS_BASE_URL')?>/img/Error_500_igreja_acesa.png" class="img-responsive">
        <img id="img_igreja_apagada" src="<?= constant('CATECHESIS_BASE_URL')?>/img/Error_500_igreja_apagada.png" class="img-responsive">
    </div>

</div>


<script src="<?= constant('CATECHESIS_BASE_URL')?>/js/forkit.custom.js"></script>

<script>
    var luzes = "off";

    function animate_lights_on()
    {
        document.getElementById("img_igreja_acesa").style.opacity = 1.0;
        document.getElementById("img_igreja_acesa").style.animation="blinkUpChurch 1s normal ease-in-out";
    }

    function animate_lights_off()
    {
        document.getElementById("img_igreja_acesa").style.opacity = 0.0;
        document.getElementById("img_igreja_acesa").style.animation="blinkUpChurch 1s reverse ease-in-out";
    }

    function toggle_igreja_luzes()
    {
        if(luzes === "on")
        {
            var audio = new Audio('<?= constant('CATECHESIS_BASE_URL')?>/sound/light-switch-pull-chain-daniel_simon - reverse.mp3');
            audio.volume = 0.5;
            audio.play();
            animate_lights_off();
            luzes = "off";
        }
        else
        {
            var audio = new Audio('<?= constant('CATECHESIS_BASE_URL')?>/sound/light-switch-pull-chain-daniel_simon.mp3');
            audio.volume = 0.5;
            audio.play();
            animate_lights_on();
            luzes = "on";
        }
    }

    document.addEventListener( 'forkit-open', toggle_igreja_luzes, false );
    animate_lights_off();


</script>

</body>
</html>