 <?php
  
require_once(__DIR__ . '/core/config/catechesis_config.inc.php');
require_once(__DIR__ . '/authentication/utils/authentication_verify.php');
require_once(__DIR__ . '/authentication/Authenticator.php');
require_once(__DIR__ . '/core/Configurator.php');
require_once(__DIR__ . '/core/log_functions.php');
 require_once(__DIR__ . "/gui/widgets/WidgetManager.php");
 require_once(__DIR__ . "/gui/widgets/Navbar/MainNavbar.php");
 require_once(__DIR__ . '/core/PdoDatabaseManager.php');
require_once(__DIR__ . '/core/Utils.php');

 use catechesis\DatabaseAccessMode;
 use catechesis\PdoDatabaseManager;
 use catechesis\Authenticator;
 use catechesis\Configurator;
 use catechesis\Utils;
 use catechesis\gui\WidgetManager;
 use catechesis\gui\MainNavbar;
 use catechesis\gui\MainNavbar\MENU_OPTION;



 // Create the widgets manager
 $pageUI = new WidgetManager();

 // Instantiate the widgets used in this page and register them in the manager
 $menu = new MainNavbar(null, MENU_OPTION::CATECHESIS);
 $pageUI->addWidget($menu);


//Funcao para redireccionar para a pagina de gestao de grupos, em caso de sucesso
function redireccionar($ano, $modo, $count)
{
    if($modo==1)
        header("Location: gerirGrupos.php?sel_ano_catequetico=" . $ano . "&msg=1");
    else
        header("Location: gerirGrupos.php?sel_ano_catequetico=" . $ano . "&msg=2&count=$count");
    $result=null;
    die();

}


if(!Authenticator::isAdmin())
{
    echo("<!DOCTYPE html><html><body><div><strong>Erro!</strong> Não tem permissões para aceder a este recurso.</div>");
    echo("</body></html>");
    die();

}

$db = new PdoDatabaseManager();


 //Erros
 $err1 = false;
 $err2 = false;
 $err3 = false;
 $err4 = false;
 $abortar = false;
 $abort1 = false;
 $abort2 = false;
 $abort3 = false;


//Verificar se existe algum ano catequetico na BD
$existe_ano_anterior = false;
try
{
    $catechetical_years = $db->getCatecheticalYears();

    if(isset($catechetical_years) && count($catechetical_years) > 0)
        $existe_ano_anterior = true;
    else
    {
        $err1 = true;

;
    }

}
catch(Exception $e)
{
    //echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
    $abort1 = true;
    die();
}




//Criar grupos de catequese
if ($_SERVER["REQUEST_METHOD"] == "POST")
{
    $ano_catequetico_original = $ano_catequetico_novo = Utils::sanitizeInput($_POST['ano_catequetico']);
    $modo = intval($_POST['criar_modo']);

    $matches = NULL;
    preg_match('/^([0-9]{4})\/([0-9]{4})$/', $ano_catequetico_novo, $matches);
    $ano_catequetico_novo = 10000 * intval($matches[1]) + intval($matches[2]);


    if($ano_catequetico_novo < 1000000)	//Tem de ser da forma '20152016', logo, com 8 digitos
    {
        //echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> O ano catequético é inválido. Criação de grupos cancelada.</div>");
        $err2 = true;

;
    }
    else if($modo==1)
    {
        try
        {
            $db->beginTransaction(DatabaseAccessMode::DEFAULT_EDIT);

            //Criar um grupo para cada catecismo
            for($i = 1; $i <= intval(Configurator::getConfigurationValueOrDefault(Configurator::KEY_NUM_CATECHISMS)); $i++)
            {
                if(!$db->createCatechismGroup($ano_catequetico_novo, $i, 'A'))
                {
                    $err3 = true;
                    $db->rollBack();

;
                }
            }

            $db->commit();

            writeLogEntry("Criados 10 grupos de catequese para o ano catequético " . intval($ano_catequetico_novo / 10000) . "/" . intval($ano_catequetico_novo % 10000) . ".");
            redireccionar($ano_catequetico_novo, 1, 0);

        }
        catch(Exception $e)
        {
            //echo $e->getMessage();
            //echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Falha ao criar grupos de catequese.</div>");
            $abort2 = true;
            die();
        }
    }
    else if($modo==2)
    {
        try
        {
            //Obter grupos do ultimo ano catequetico na BD
            $latest_year_catechims = $db->getCatechismsAndGroupsFromLatestYear();

            if(isset($latest_year_catechims))
            {
                $db->beginTransaction(DatabaseAccessMode::DEFAULT_EDIT);

                //Criar grupos equivalentes no novo ano catequetico
                $count = 0;
                foreach($latest_year_catechims as $row)
                {
                    $catecismo = intval($row['ano_catecismo']);
                    $turma = Utils::sanitizeOutput($row['turma']);

                    if($db->createCatechismGroup($ano_catequetico_novo, $catecismo, $turma))
                        $count++;
                    else
                    {
                        $db->rollBack();

;
                    }
                }

                $db->commit();

                writeLogEntry("Criados " . $count . " grupos de catequese para o ano catequético " . intval($ano_catequetico_novo / 10000) . "/" . intval($ano_catequetico_novo % 10000) . ", seguindo os mesmos grupos que no ano catequético de " . intval($row['ano_lectivo'] / 10000) . "/" . intval($row['ano_lectivo'] % 10000) . ".");
                redireccionar($ano_catequetico_novo, 2, $count);

            }
            else
            {
                //echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Falha ao criar grupos de catequese.</div>");
                $err3 = true;

;
             }
        }
        catch(Exception $e)
        {
            //echo $e->getMessage();
            //echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Falha ao criar grupos de catequese.</div>");
            $abort3 = true;
            die();
        }
    }
    else
    {
        //echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> O modo de criação automática de grupos é inválido. Criação de grupos cancelada.</div>");
        $err4 = true;

;
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <title>Gerir grupos de catequese</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php $pageUI->renderCSS(); // Render the widgets' CSS ?>
  <link rel="stylesheet" href="css/custom-navbar-colors.css">

    <?php
    $pageUI->renderJS(); // Render the widgets' JS code
    ?>
  <script src="js/rowlink.js"></script>

  
  <style>
  	@media print
	{    
	    .no-print, .no-print *
	    {
		display: none !important;
	    }
	    
	    /*@page {
		    size: 297mm 210mm;*/ /* landscape */
		    /* you can also specify margins here: */
		    /*margin: 35mm;*/
		    /*margin-right: 45mm;*/ /* for compatibility with both A4 and Letter */
		 /* }*/
		  
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


<div class="container" id="contentor">
  <h2> Gerir grupos de catequese</h2>
  
  <div class="row" style="margin-bottom:40px; "></div>

 
 <?php
 
 	//Mensagens de erro
 	
 	if($err1)
 	{
 		echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Falha ao detectar anos catequéticos anteriores na base de dados.</div>");
 	}
 	if($err2)
 	{
 		echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> O ano catequético é inválido. Criação de grupos cancelada.</div>");
 	}
 	if($err3)
 	{
 		echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Falha ao criar grupos de catequese.</div>");
 	}
 	if($err4)
 	{
 		echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> O modo de criação automática de grupos é inválido. Criação de grupos cancelada.</div>");
 	}
 	if($abort1)
 	{
 		echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Falha ao detectar anos catequéticos anteriores na base de dados.</div>");
 	}
 	if($abort2)
 	{
 		echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Falha ao criar grupos de catequese.</div>");
 	}
 	if($abort3)
 	{
 		echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Falha ao criar grupos de catequese.</div>");
 	}
 	
 	
 	if(!$abortar): 
 ?>
    
  
  
  <div class="no-print">
  
    
  <div class="row" style="margin-top:20px; "></div>
    
  <ul class="nav nav-tabs">
  <li role="presentation"><a href="gerirGrupos.php">Anos catequéticos existentes</a></li>
  <li role="presentation" class="active"><a href="novoAno.php">Novo ano catequético</a></li>
  </ul>
 
  </div>
  

	<div class="row" style="margin-bottom:60px; "></div>
 
 
 
  <form role="form" action="novoAno.php" method="post">
  
    <div class="form-group">
    <div class="col-xs-4">
      <label for="nome">Ano catequético:</label>
       <input type="text" class="form-control" id="ano_catequetico" name="ano_catequetico" placeholder="2015/2016" list="anos_catequeticos" required>
    </div>
   </div>
    
   <div class="clearfix"></div> 
   
   <div class="row" style="margin-bottom:20px; "></div>
   
   <div class="form-group">
    <div class="col-xs-8">
    	<label for="criar_modo">Criar automaticamente:</label>
    	<p>&nbsp;&nbsp;<input type="radio" name="criar_modo" value="1" <?php if(!$existe_ano_anterior) echo("checked");?>> <?= intval(Configurator::getConfigurationValueOrDefault(Configurator::KEY_NUM_CATECHISMS))?> grupos de catequese (1 grupo por catecismo)</p>
    	<?php if($existe_ano_anterior): ?>
	<p>&nbsp;&nbsp;<input type="radio" name="criar_modo" value="2" checked> mesmos catecismos e grupos que no ano anterior</p>
	<?php endif ?>
    </div>
    <div class="clearfix"></div>
    </div>
    
    
    <button type="submit" class="btn btn-primary"><span class="glyphicon glyphicon-plus"></span> Criar</button>
  
    </form>
    
    
  
   <div class="row" style="margin-top:20px; "></div>
	  
  



</div>



<datalist id="anos_catequeticos">
    <?php
    //Print from the last 10 years up to the following 5 years
    for($y = -10; $y < 5; $y++)
    {
        $year_start = date('Y') + $y;
        $year_end = date('Y') + $y + 1;
        echo("<option value='$year_start/$year_end'>");
    }
    ?>
</datalist>

<?php endif ?>
</body>
</html>