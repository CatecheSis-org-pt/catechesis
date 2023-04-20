<?php

require_once(__DIR__ . "/core/config/catechesis_config.inc.php");
require_once(__DIR__ . "/authentication/utils/authentication_verify.php");
require_once(__DIR__ . "/core/Utils.php");
require_once(__DIR__ . '/core/UserData.php');
require_once(__DIR__ . "/core/PdoDatabaseManager.php");
require_once(__DIR__ . '/core/Configurator.php');
require_once(__DIR__ . "/gui/widgets/WidgetManager.php");
require_once(__DIR__ . '/gui/widgets/Navbar/MainNavbar.php');
require_once(__DIR__ . "/gui/widgets/CatechumensList/CatechumensListWidget.php");


use catechesis\OrderCatechumensBy;
use catechesis\PdoDatabaseManager;
use catechesis\Configurator;
use catechesis\SacramentFilter;
use catechesis\gui\WidgetManager;
use catechesis\gui\MainNavbar;
use catechesis\gui\MainNavbar\MENU_OPTION;
use catechesis\gui\CatechumensListWidget;
use catechesis\UserData;
use catechesis\Utils;


// Create the widgets manager
$pageUI = new WidgetManager();

// Instantiate the widgets used in this page and register them in the manager
$menu = new MainNavbar(null, MENU_OPTION::CATECHUMENS);
$pageUI->addWidget($menu);
$searchResults = new CatechumensListWidget();
$pageUI->addWidget($searchResults);

?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <title>Pesquisar catequizandos</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php $pageUI->renderCSS(); // Render the widgets' CSS ?>
  <link rel="stylesheet" href="css/custom-navbar-colors.css">

  
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

		 
		 /* Para imprimir os crachas dos sacramentos a cores */
	      .label-success
	      {
	        color: white !important;
	        background-color: #5cb85c !important;
	      }
	      /*.label-default
	      {
	        color: white !important;
	        background-color: #777 !important;
	      }*/
		  
	}
	
	@media screen
	{
		.only-print, .only-print *
		{
			display: none !important;
		}
	}
  </style>
  
  <style>
	  .btn-group-hover .btn {
	    /*border-color: white;*/
	    background: white;
	    text-shadow: 0 1px 1px white;
	    -webkit-box-shadow: inset 0 1px 0 white;
	    -moz-box-shadow: inset 0 1px 0 white;
	    box-shadow: inset 0 1px 0 white;
	}
	  .btn-group-hover {
		    opacity: 0;
	}
	
	
	
		.rowlink {
	  
		    cursor: pointer;
	
		}
  </style>
</head>
<body>

<?php
$menu->renderHTML();
?>


<div class="only-print" style="position: fixed; top: 0;">
	<img src="<?= UserData::getParishLogoQueryURL() ?>" style="height: 50px;">
	<h3>Pesquisa de catequizandos</h3>
	<div class="row" style="margin-bottom:20px; "></div>
</div>

<div class="row only-print" style="margin-bottom:150px; "></div>


<div class="container" id="contentor">

  <h2 class="no-print"> Pesquisar catequizandos</h2>

  <div class="no-print">
      <div class="row" style="margin-top:20px; "></div>
      <ul class="nav nav-tabs">
          <li role="presentation"><a href="pesquisarNome.php">Por nome / data nascimento</a></li>
          <li role="presentation" class="active"><a href="">Por ano / catecismo</a></li>
          <li role="presentation"><a href="pesquisarCatequista.php">Por catequista</a></li>
      </ul>
  </div>
  

  <div class="well well-lg" style="position:relative; z-index:2;">
  	<form role="form" action="pesquisarAno.php" method="post">
  
  <div class="form-group">
    <div class="col-xs-5">
 	 <label for="ano_catequetico">Ano catequético: </label> 
 	 <select name="ano_catequetico" >
    		<option value="" <?php if (!$_POST['ano_catequetico'] || $_POST['ano_catequetico']=="") echo("selected"); ?>>Todos</option>
	<?php

    //Funcao para criar link de retorno em caso de erro



    $db = new PdoDatabaseManager();


        //Get catechetical years
        $result = null;
        try
        {
            $result = $db->getCatecheticalYears();
        }
        catch (Exception $e)
        {
            echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
            die();
        }

        foreach($result as $row)
        {
            echo("<option value='" . $row['ano_lectivo'] . "'");
            if ($_SERVER["REQUEST_METHOD"] == "POST" && $_POST['ano_catequetico']==$row['ano_lectivo'])
                echo(" selected");
            echo(">");
            echo(Utils::formatCatecheticalYear($row['ano_lectivo']) . "</option>\n");
        }

		$result = null;
	?>
	  </select>
	</div>
   </div>
   
   	<div class="form-group">
   	<div class="col-xs-4">
   		<label for="catecismo">Catecismo:</label>
		<select name="catecismo">
			<option value="" <?php if (!$_POST['catecismo'] || $_POST['catecismo']=="") echo("selected"); ?>>Todos</option>
	<?php

        //Get catechisms
        try
        {
            $result = $db->getCatechisms();
        } catch (Exception $e)
        {
            echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
            die();
        }

		if (isset($result) && count($result)>=1)
		{
			foreach($result as $row)
			{
				echo("<option value='" . $row['ano_catecismo'] . "'");
				if ($_SERVER["REQUEST_METHOD"] == "POST" && $_POST['catecismo']==$row['ano_catecismo']) 
					echo(" selected");
				echo(">");
				echo("" . $row['ano_catecismo'] . "º" . "</option>\n"); 
			}
		}

		$result = null;
		   				
	?>
		</select>
	</div>
   </div>
   
   
   
   <div class="form-group">
   	<div class="col-xs-3">
   		<label for="turma">Grupo:</label>
		<select name="turma">
			<option value="" <?php if (!$_POST['catecismo'] || $_POST['catecismo']=="") echo("selected"); ?>>Todas</option>
	<?php

        //Get distinct catechesis group letters
        try
        {
            $result = $db->getGroupLetters();
        } catch (Exception $e)
        {
            echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
            die();
        }

        if (isset($result) && count($result)>=1)
		{
			foreach($result as $row)
			{
				echo("<option value='" . Utils::sanitizeOutput($row['turma']) . "'");
				if ($_SERVER["REQUEST_METHOD"] == "POST" && $_POST['turma']==$row['turma']) 
					echo(" selected");
				echo(">");
				echo("" . Utils::sanitizeOutput($row['turma']) . "</option>\n");
			}
		}

		$result = null;
		   				
	?>
		</select>
	</div>
   </div>
  
  
  
  <!-- Filtros -->
  <div class="clearfix" style="margin-bottom: 20px"></div>
  
   <div class="col-xs-9">
  <a style="cursor: pointer;" data-toggle="collapse" data-target="#filtros">Aplicar filtros <span class="glyphicon glyphicon-chevron-down"></span></a>
  <div style="margin-bottom: 10px"></div>
  
  <div id="filtros" class="collapse <?php if (($_POST['filtro_bap']!='' && $_POST['filtro_bap']!=0) ||($_POST['filtro_com']!='' && $_POST['filtro_com']!=0) || ($_POST['excluir_1']=='on') || ($_POST['excluir_2']=='on') || ($_POST['excluir_3']=='on') || ($_POST['excluir_4']=='on') || ($_POST['excluir_5']=='on') || ($_POST['excluir_6']=='on') || ($_POST['excluir_7']=='on') || ($_POST['excluir_8']=='on') || ($_POST['excluir_9']=='on') || ($_POST['excluir_10']=='on')) echo('in'); ?>">  
  	<div class="form-group">
   	<div class="col-xs-6">
   		<label for="filtro_bap">Baptismo:</label>
		<select name="filtro_bap">
			<option value="0" <?php if (!$_POST['filtro_bap'] || $_POST['filtro_bap']=="" || $_POST['filtro_bap']==0) echo("selected"); ?>>Indiferente</option>
			<option value="1" <?php if ($_POST['filtro_bap']==1) echo("selected"); ?>>Fez</option>
			<option value="2" <?php if ($_POST['filtro_bap']==2) echo("selected"); ?>>Não fez</option>
		</select>
	</div>
	<div class="col-xs-6">
   		<label for="filtro_com">Primeira comunhão:</label>
		<select name="filtro_com">
			<option value="0" <?php if (!$_POST['filtro_com'] || $_POST['filtro_com']=="" || $_POST['filtro_com']==0) echo("selected"); ?>>Indiferente</option>
			<option value="1" <?php if ($_POST['filtro_com']==1) echo("selected"); ?>>Fez</option>
			<option value="2" <?php if ($_POST['filtro_com']==2) echo("selected"); ?>>Não fez</option>
		</select>
	</div>
	</div>
	
	<div class="clearfix" style="margin-bottom: 10px;"></div>
	
	<div class="form-group">
	<div class="col-xs-12">
   		<label for="excluir">Excluir catecismos:</label>
        <?php
        for($i = 1; $i <= intval(Configurator::getConfigurationValueOrDefault(Configurator::KEY_NUM_CATECHISMS)); $i++)
        {
            ?>
            <input name="excluir_<?= $i ?>" type="checkbox" <?php if($_POST['excluir_' . $i]=='on') echo('checked'); ?>><?= $i ?>º
            <?php
        }
        ?>
	</div>
	</div>
	
	<div class="clearfix" style="margin-bottom: 10px;"></div>
  	
  </div>
  </div>
  <!-- /Filtros -->
  

    <div class="col-xs-3">
    	<label for="botao"> <br></label>
    	<div>
    		<button type="submit" class="btn btn-primary glyphicon glyphicon-search no-print"> Pesquisar</button> 
    	</div>	
    </div>
    
    
    <?php
	if($_POST['ano_catequetico'] && $_POST['catecismo'] && $_POST['turma'])
	{
		$ano_catequetico = intval($_POST['ano_catequetico']);
		$catecismo = intval($_POST['catecismo']);
		$turma = Utils::sanitizeInput($_POST['turma']);
		
		if($ano_catequetico > 1000000 && $catecismo > 0 && $catecismo <= intval(Configurator::getConfigurationValueOrDefault(Configurator::KEY_NUM_CATECHISMS)) && $turma!="")
		{
			echo("<div class=\"col-xs-4\">\n");
			echo("<button type=\"button\" onclick=\"imprime_presencas()\" class=\"btn btn-default no-print\"><span class=\"fas fa-stamp\"></span> Ir para a área de impressão</button>\n");
			echo("</div>\n");
		}
		
	}
     ?>
    
    <div class="clearfix"></div>
	 
    </form>
  
  </div>
  
  
  <?php

	$ano_catequetico = NULL;
	$catecismo = NULL;
	$turma = NULL;
	$bap = SacramentFilter::IRRELEVANT;
	$com = SacramentFilter::IRRELEVANT;
	$exclusoes = false;
	$excludedCatechisms = array();

    if ($_SERVER["REQUEST_METHOD"] == "POST")
	{
		
		if($_POST['ano_catequetico'])
			$ano_catequetico = intval($_POST['ano_catequetico']);
		if($_POST['catecismo'])
			$catecismo = intval($_POST['catecismo']);
		if($_POST['turma'])
			$turma = Utils::sanitizeInput($_POST['turma']);
		if(isset($_POST['filtro_bap']))
			$bap = intval($_POST['filtro_bap']);
		if(isset($_POST['filtro_com']))
			$com = intval($_POST['filtro_com']);
        for($i = 1; $i <= intval(Configurator::getConfigurationValueOrDefault(Configurator::KEY_NUM_CATECHISMS)); $i++)
        {
            if($_POST["excluir_" . $i])
            {
                array_push($excludedCatechisms, $i);
                $exclusoes = true;
            }
        }
		  	
		 
		if($ano_catequetico && $ano_catequetico < 1000000)	//Tem de ser da forma '20152016', logo, com 8 digitos
		{
			echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> O ano catequético é inválido.</div>");
		}
		else if($catecismo && ($catecismo <= 0 || $catecismo > intval(Configurator::getConfigurationValueOrDefault(Configurator::KEY_NUM_CATECHISMS))))
		{
			echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> O catecismo é inválido.</div>");
			die();
		}
	  	
	  	if(($bap && $bap!=SacramentFilter::IRRELEVANT) || ($com && $com!=SacramentFilter::IRRELEVANT))
	  	{
	  		$mensagem = "A mostrar apenas catequizandos que";
	  		
	  		if($bap && $bap==SacramentFilter::HAS)
	  			$mensagem .= " são baptizados";
	  		else if($bap && $bap==SacramentFilter::HAS_NOT)
	  			$mensagem .= " não são baptizados";
	  		
	  		if($bap!=SacramentFilter::IRRELEVANT && $com!=SacramentFilter::IRRELEVANT)
	  			$mensagem .= " e que";
	  			
	  		if($com && $com==SacramentFilter::HAS)
	  			$mensagem .= " fizeram a primeira comunhão";
	  		else if($com && $com==SacramentFilter::HAS_NOT)
	  			$mensagem .= " não fizeram a primeira comunhão";
	  			
	  		$mensagem .= ".";
	  		
	  			
	  		echo("<div class=\"alert alert-warning\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Aviso!</strong> " . $mensagem . "</div>");
	  	
	  	 }
	  	 
	  	 if($exclusoes)
	  	 	echo("<div class=\"alert alert-warning\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Aviso!</strong> Os catequizandos pertencentes a alguns catecismos foram excluídos da lista de resultados, conforme os filtros que aplicou.</div>");



        //Perform search
        try
        {
            $result = $db->getCatechumensByCatechismWithFilters(Utils::currentCatecheticalYear(), $ano_catequetico, $catecismo, $turma,
                false, OrderCatechumensBy::NAME_BIRTHDATE, $bap, $com, $excludedCatechisms);
        } catch (Exception $e)
        {
            echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
            die();
        }

        $searchResults->setCatechumensList($result);
        $sacramentsOpen = (($bap && $bap!=SacramentFilter::IRRELEVANT) || ($com && $com!=SacramentFilter::IRRELEVANT));
        $searchResults->setSacramentsShown($sacramentsOpen);

        $searchResults->renderHTML();
	}
	
	
	//Libertar recursos
	$result = null;
?>
  
  

  <?php
	if($_POST['ano_catequetico'] && $_POST['catecismo'] && $_POST['turma'])
	{
		$ano_catequetico = intval($_POST['ano_catequetico']);
		$catecismo = intval($_POST['catecismo']);
		$turma = Utils::sanitizeInput($_POST['turma']);
		
		if($ano_catequetico > 1000000 && $catecismo > 0 && $catecismo <= intval(Configurator::getConfigurationValueOrDefault(Configurator::KEY_NUM_CATECHISMS)) && $turma!="")
		{
		?>
			
			<form id="form_imprime_presencas" action="folhasPresencas.php" method="post">
				<input type="hidden" name="ano_catequetico" value="<?php echo('' . $ano_catequetico . '');?>" >
				<input type="hidden" name="catecismo" value="<?php echo('' . $catecismo . '');?>" >
				<input type="hidden" name="turma" value="<?php echo('' . $turma . '');?>" >
			</form>
		
		
		<?php
		}
		
	}
     ?>


</div>

<?php
if($_POST['ano_catequetico'] && $_POST['catecismo'] && $_POST['turma'])
{
    $ano_catequetico = intval($_POST['ano_catequetico']);
    $catecismo = intval($_POST['catecismo']);
    $turma = Utils::sanitizeInput($_POST['turma']);

    if($ano_catequetico > 1000000 && $catecismo > 0 && $catecismo <= intval(Configurator::getConfigurationValueOrDefault(Configurator::KEY_NUM_CATECHISMS)) && $turma!="")
    {
        echo("<script>\n");
        echo("function imprime_presencas(){\n");
        echo("\tdocument.getElementById(\"form_imprime_presencas\").submit();}\n");
        echo("</script>\n");
    }
}
?>

<?php
$pageUI->renderJS(); // Render the widgets' JS code
?>

</body>
</html>
  
  
