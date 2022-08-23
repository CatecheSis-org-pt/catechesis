<?php

require_once(__DIR__ . '/core/config/catechesis_config.inc.php');
require_once(__DIR__ . '/authentication/utils/authentication_verify.php');
require_once(__DIR__ . '/authentication/Authenticator.php');
require_once(__DIR__ . '/core/Configurator.php');
require_once(__DIR__ . '/core/log_functions.php'); //Para poder escrever no log
require_once(__DIR__ . '/core/Utils.php');
require_once(__DIR__ . "/core/PdoDatabaseManager.php");
require_once(__DIR__ . '/gui/widgets/WidgetManager.php');
require_once(__DIR__ . '/gui/widgets/Navbar/MainNavbar.php');
require_once(__DIR__ . '/gui/widgets/ModalDialog/ModalDialogWidget.php');

use catechesis\PdoDatabaseManager;
use catechesis\Authenticator;
use catechesis\Configurator;
use catechesis\Utils;
use catechesis\gui\WidgetManager;
use catechesis\gui\MainNavbar;
use catechesis\gui\MainNavbar\MENU_OPTION;
use catechesis\gui\ModalDialogWidget;
use catechesis\gui\Button;
use catechesis\gui\ButtonType;



// Create the widgets manager
$pageUI = new WidgetManager();

// Instantiate the widgets used in this page and register them in the manager
$menu = new MainNavbar(null, MENU_OPTION::CATECHESIS);
$pageUI->addWidget($menu);
$deleteGroupDialog = new ModalDialogWidget("confirmarEliminarGrupo");
$pageUI->addWidget($deleteGroupDialog);
$catechistAvailabilityDialog = new ModalDialogWidget("confirmarCatequista");
$pageUI->addWidget($catechistAvailabilityDialog);

?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <title>Gerir grupos de catequese</title>
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

	<?php

	if(!Authenticator::isAdmin())
	{
		echo("<div class=\"alert alert-danger\"><strong>Erro!</strong> Não tem permissões para aceder a este recurso.</div>");
		echo("</div></body></html>");
		die();
	}

	?>
	
  <h2> Gerir grupos de catequese</h2>
  
   
  <div class="row" style="margin-bottom:40px; "></div>
  
  
  
<?php
	
	$result = NULL;
	$ano_catequetico_sel = NULL;

	$db = new PdoDatabaseManager();

	//Acabou de ser criado um novo ano catequetico?
	if($_REQUEST['msg'] && $_REQUEST['msg']==1)
	{
        $ano_catequetico_sel = intval($_REQUEST['sel_ano_catequetico']);
		echo("<div class=\"alert alert-success\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Sucesso!</strong> Criados 10 novos grupos de catequese, para o ano catequético " . Utils::formatCatecheticalYear($ano_catequetico_sel) . " </div>");
	}
	else if($_REQUEST['msg'] && $_REQUEST['msg']==2)
	{
		$count = intval($_REQUEST['count']);
        $ano_catequetico_sel = intval($_REQUEST['sel_ano_catequetico']);
		echo("<div class=\"alert alert-success\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Sucesso!</strong> Criados $count novos grupos de catequese, para o ano catequético " . Utils::formatCatecheticalYear($ano_catequetico_sel) . " </div>");
	}
	

	
	//Adicionar grupo de catequese
	if($_REQUEST['op']=="adicionar")
	{
		$ad_ano_catequetico = intval($_REQUEST['sel_ano_catequetico']);
		$ad_catecismo = intval($_REQUEST['catecismo']);
		$ad_turma = Utils::sanitizeInput($_REQUEST['turma']);
		
		if($ad_ano_catequetico < 1000000)	//Tem de ser da forma '20152016', logo, com 8 digitos
		{
			echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> O ano catequético é inválido. Criação de grupo cancelada.</div>");
		}
		else if($ad_catecismo <= 0 || $ad_catecismo > intval(Configurator::getConfigurationValueOrDefault(Configurator::KEY_NUM_CATECHISMS)))
		{
			echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> O catecismo é inválido. Criação de grupo cancelada.</div>");

;
		}
		else
        {
            try
            {
                if ($db->createCatechismGroup($ad_ano_catequetico, $ad_catecismo, $ad_turma))
                {
                    writeLogEntry("Criado novo grupo de catequese " . Utils::formatCatecheticalYear($ad_ano_catequetico) . " " . $ad_catecismo . "º" . $ad_turma . ".");
                    echo("<div class=\"alert alert-success\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Sucesso!</strong> Novo grupo de catequese criado. </div>");
                }
                else
                {
                    echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Falha ao criar grupo de catequese.</div>");

;
                }
            }
            catch (Exception $e)
            {
                echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
                die();
            }
        }
	}
	
	
	
	
	//Eliminar grupo de catequese
	if($_REQUEST['op']=="elg")
	{		
		$el_ano_catequetico = intval($_REQUEST['el_ano_catequetico']);
		$el_catecismo = intval($_REQUEST['el_catecismo']);
		$el_turma = Utils::sanitizeInput($_REQUEST['el_turma']);
	
		if($el_ano_catequetico < 1000000)	//Tem de ser da forma '20152016', logo, com 8 digitos
		{
			echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> O ano catequético é inválido. Eliminação de grupo cancelada.</div>");
		}
		else if($el_catecismo <= 0 || $el_catecismo > intval(Configurator::getConfigurationValueOrDefault(Configurator::KEY_NUM_CATECHISMS)))
		{
			echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> O catecismo é inválido. Eliminação de grupo cancelada.</div>");

;
		}
		else
		{
			try
            {
                //Verificar se ha catequizandos inscritos neste grupo
                $result = $db->getCatechumensByCatechismWithFilters($el_ano_catequetico, $el_ano_catequetico, $el_catecismo, $el_turma);

                if(count($result) >= 1)
                {
                    echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Não foi possível eliminar o grupo porque este grupo de catequese tem catequizandos inscritos. <br>Para eliminar o grupo de catequese remova primeiro as inscrições dos catequizandos, através da secção percurso catequético dos seus arquivos.</div>");

;
                }
                else
                {
                    //Eliminar grupo
                    if($db->deleteCatechismGroup($el_ano_catequetico, $el_catecismo, $el_turma))
                    {
                        echo("<div class=\"alert alert-success\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Sucesso!</strong> Grupo de catequese eliminado. </div>");
                        writeLogEntry("Eliminado grupo de catequese " . Utils::formatCatecheticalYear($el_ano_catequetico) . " " . $el_catecismo . "º" . $el_turma . ".");
                    }
                    else
                    {
                        echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Falha ao eliminar o grupo de catequese.</div>");

;
                    }
                }
            }
            catch (Exception $e)
            {
                echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");

;
            }
		}
	}
	
	
	
	
	
	//Adicionar catequista
	if($_POST['cat_accao'] && $_POST['cat_accao']=="adicionar")
	{

		$cat_ano_catequetico = intval($_POST['sel_ano_catequetico']);
		$cat_catecismo = intval($_POST['cat_catecismo']);
		$cat_turma = Utils::sanitizeInput($_POST['cat_turma']);
		$catequista = Utils::sanitizeInput($_POST['catequista']);
	

		if($cat_ano_catequetico < 1000000)	//Tem de ser da forma '20152016', logo, com 8 digitos
		{
			echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> O ano catequético é inválido. Adição de catequista cancelada.</div>");
		}
		else if($cat_catecismo <= 0 || $cat_catecismo > intval(Configurator::getConfigurationValueOrDefault(Configurator::KEY_NUM_CATECHISMS)))
		{
			echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> O catecismo é inválido. Adição de catequista cancelada.</div>");

;
		}
		else
		{
			try
            {
				if($db->addCatechistToGroup($catequista, $cat_ano_catequetico, $cat_catecismo, $cat_turma))
				{
				    writeLogEntry("Catequista " . $catequista . " atribuído ao grupo de catequese " . Utils::formatCatecheticalYear($cat_ano_catequetico) . " " . $cat_catecismo . "º" . $cat_turma . ".");
				    echo("<div class=\"alert alert-success\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Sucesso!</strong> Catequista atribuído ao grupo de catequese. </div>");
				}
				else
                {
                    echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Falha ao atribuir catequista ao grupo de catequese.</div>");

;
                }
			}
            catch (Exception $e)
            {
                echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");

;
            }
		}
	}
	
	
	
	
	
	//Remover catequista
	if($_POST['cat_accao'] && $_POST['cat_accao']=="remover")
	{
		$cat_ano_catequetico = intval($_POST['sel_ano_catequetico']);
		$cat_catecismo = intval($_POST['cat_catecismo']);
		$cat_turma = Utils::sanitizeInput($_POST['cat_turma']);
		$catequista = Utils::sanitizeInput($_POST['cat_rem']);
	
		if($cat_ano_catequetico < 1000000)	//Tem de ser da forma '20152016', logo, com 8 digitos
		{
			echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> O ano catequético é inválido. Remoção de catequista cancelada.</div>");
		}
		else if($cat_catecismo <= 0 || $cat_catecismo > intval(Configurator::getConfigurationValueOrDefault(Configurator::KEY_NUM_CATECHISMS)))
		{
			echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> O catecismo é inválido. Remoção de catequista cancelada.</div>");

;
		}
		else
		{
			try
            {
				if($db->removeCatechistFromGroup($catequista, $cat_ano_catequetico, $cat_catecismo, $cat_turma))
				{
				    writeLogEntry("Catequista " . $catequista . " removido do grupo de catequese " . Utils::formatCatecheticalYear($cat_ano_catequetico) . " " . $cat_catecismo . "º" . $cat_turma . ".");
				    echo("<div class=\"alert alert-success\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Sucesso!</strong> Catequista removido do grupo de catequese. </div>");
				}
				else
                {
                    echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Falha ao remover catequista do grupo de catequese.</div>");

;
                }
			}
            catch (Exception $e)
            {
                echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");

;
            }
		}
	}

?>
  
  <div class="no-print">
  
    
  <div class="row" style="margin-top:20px; "></div>
    
  <ul class="nav nav-tabs">
  <li role="presentation" class="active"><a href="gerirGrupos.php">Anos catequéticos existentes</a></li>
  <li role="presentation"><a href="novoAno.php">Novo ano catequético</a></li>
  </ul>
 
  </div>
  

	<div class="row" style="margin-bottom:60px; "></div>
 
  
  <form role="form" action="gerirGrupos.php" method="post" id="form_ano">
    <div class="form-group">
    <div class="col-xs-6">
      <label for="nome">Ano catequético:</label>
       <select name="sel_ano_catequetico" onchange="this.form.submit()">
       <?php
       		

        //Obter anos catequeticos
        $result = null;
        try
        {
           $result = $db->getCatecheticalYears();
        }
        catch(Exception $e)
        {
           echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
           die();
        }

        $count = 0;
        foreach($result as $row)
        {
            echo("<option value='" . $row['ano_lectivo'] . "'");
            if(($_REQUEST['sel_ano_catequetico']==$row['ano_lectivo']) || ($count==0 && !$_REQUEST['sel_ano_catequetico']))
            {
                echo(" selected");
                $ano_catequetico_sel = $row['ano_lectivo'];
            }
            echo(">");
            echo("" . Utils::formatCatecheticalYear($row['ano_lectivo']) . "</option>\n");

            $count++;
        }
    	?>
    	</select>
    </div>
   </div>
   </form>
    
   
    
  
    
    
    <div class="clearfix"></div> 
  
   <div class="row" style="margin-top:20px; "></div>
	  
  


<form role="form" action="gerirGrupos.php?op=adicionar" method="post" id="form_adicionar_grupo">

  <div class="col-xs-12">
    	<table class="table table-hover">
    	  <thead>
    		<tr>
    			<th>Catecismo</th>
    			<th>Grupo</th>
    			<th>Catequistas</th>
    			<th></th>
    		</tr>
    	  </thead>
    	  <tbody>
    	  <?php
    	  	
    	  	$result = NULL;
    	  	
       		
       		if($ano_catequetico_sel)
       		{
	       		//Obter catecismos e turmas
                try
                {
                    $result = $db->getCatechismsAndGroups($ano_catequetico_sel);
                }
                catch(Exception $e)
                {
                    echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
                    die();
                }

                foreach($result as $row)
                {
                    $catecismo = intval($row['ano_catecismo']);
                    $turma = Utils::sanitizeOutput($row['turma']);

                    echo("<tr>\n");
                    echo("\t<td>" . $catecismo . "º</td>\n");
                    echo("\t<td>" . $turma . "</td>\n");

                    //Obter nomes dos catequistas
                    $result2 = NULL;
                    try
                    {
                        $result2 = $db->getGroupCatechists($ano_catequetico_sel, $catecismo, $turma);
                    }
                    catch(Exception $e)
                    {
                        echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
                        die();
                    }

                    if (isset($result2) && count($result2)>=1)
                    {
                        echo("\t<td>");
                        $count=0;
                        foreach($result2 as $row2)
                        {
                            if($count!=0)
                                echo(", ");
                            echo("" . Utils::firstAndLastName(Utils::sanitizeOutput($row2['nome'])) . "");
                            $count++;
                        }
                        echo("</td>\n");
                    }
                    else
                    {
                        echo("\t<td><i>Por definir</i></td>\n");
                    }


                    echo("\t<td><div class=\"btn-group-xs pull-right btn-group-hover\" role=\"group\" aria-label=\"...\">");
                    echo("<button type=\"button\" class=\"btn btn-default\" data-toggle=\"modal\" data-target=\"#confirmarEliminarGrupo\" onclick=\"preparar_eliminar_grupo(" . $ano_catequetico_sel . ", " . $row['ano_catecismo'] . ", '" . Utils::sanitizeOutput($row['turma']) . "')\"><span class=\"glyphicon glyphicon-trash text-danger\"></span><span class='text-danger'> Eliminar</span></button>");
                    echo("<button type=\"button\" class=\"btn btn-default\" onclick=\"editar_grupo(" . $ano_catequetico_sel . ", " . $row['ano_catecismo'] . ", '" . Utils::sanitizeOutput($row['turma']) . "')\"><span class=\"glyphicon glyphicon-th-list\"></span><span> Definir catequistas</span></button>
            </div>");

                    echo("</tr>\n");
                }
    	  	}
    	  
    	  ?>
    	
    		<tr class="active no-print">
    			<td>
                    <div class="input-group input-group-sm">
                        <select class="" name="catecismo" required>
                            <option disabled selected></option>
                            <?php
                            for($i = 1; $i <= intval(Configurator::getConfigurationValueOrDefault(Configurator::KEY_NUM_CATECHISMS)); $i++)
                            {
                                ?>
                                <option value="<?= $i ?>"><?= $i ?>º</option>
                                <?php
                            }
                            ?>
                        </select>
                    </div>
                </td>
    			<td>
                    <div class="input-group input-group-sm">
                        <select class="" name="turma" required>
                            <option disabled selected></option>
                                <option value="A">A</option>
                                <option value="B">B</option>
                                <option value="C">C</option>
                                <option value="D">D</option>
                                <option value="E">E</option>
                                <option value="F">F</option>
                        </select>
                    </div>
                </td>
    			<td><span class=""><i>A definir depois</i></span></td>
    			<td><input type="hidden" name="sel_ano_catequetico" value="<?php echo('' . $ano_catequetico_sel . ''); ?>">
    				<button type="submit" class="btn btn-default pull-right"><span class="glyphicon glyphicon-plus text-success"> Adicionar</span></button></td>
    		</tr>
    	</tbody>
    </table>
  </div>
  
</form>


<div class="row" style="margin-bottom:60px; "></div>




<?php
	if($_REQUEST['op']=="editar" || ($_POST['cat_accao'] && ($_POST['cat_accao']=="adicionar" || $_POST['cat_accao']=="remover")))
	{ 
		if(($_POST['cat_accao'] && ($_POST['cat_accao']=="adicionar" || $_POST['cat_accao']=="remover")))
		{
			$ed_ano_catequetico = intval($_POST['sel_ano_catequetico']);
			$ed_catecismo = intval($_REQUEST['cat_catecismo']);
			$ed_turma = Utils::sanitizeInput($_REQUEST['cat_turma']);
		
		}
		else
		{
			$ed_ano_catequetico = intval($_REQUEST['ed_ano_catequetico']);
			$ed_catecismo = intval($_REQUEST['ed_catecismo']);
			$ed_turma = Utils::sanitizeInput($_REQUEST['ed_turma']);
		}
	
		if($ed_ano_catequetico < 1000000)	//Tem de ser da forma '20152016', logo, com 8 digitos
		{
			echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> O ano catequético é inválido. Impossível definir catequistas.</div>");
		}
		else if($ed_catecismo<=0 || $ed_catecismo > intval(Configurator::getConfigurationValueOrDefault(Configurator::KEY_NUM_CATECHISMS)))
		{
			echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> O catecismo é inválido. Impossível definir catequistas.</div>");
		}
		else
		{	
	?>


<div class="panel panel-default collapse in" id="painel_editar">
	    <div class="panel-heading">Definir catequistas
	    	<div class="btn-group-xs pull-right" role="group" aria-label="...">
	    	<button type="button" id="cancelar" class="btn btn-default glyphicon glyphicon-remove" data-toggle="collapse" data-target="#painel_editar"> Fechar</button></div></div>
	    <div class="panel-body">    
	    
	    <form role="form" action="gerirGrupos.php" onsubmit="return verificar_disponibilidade();" method="post" id="form_adicionar_remover_cat">
	    
		    <div class="form-group">
		    <div class="col-xs-3">
		 	 <span>Catecismo: <span id="catecismo_editar"><?php echo("" . $ed_catecismo . ""); ?>º</span></span>
		     </div>

   		   
		    <div class="col-xs-3">
		 	 <span>Grupo: <span id="turma_editar"><?php echo("" . $ed_turma . ""); ?></span></span>
		     </div>
   		   </div>
	    
	    		<div class="clearfix"></div>
	    		<div class="row" style="margin-bottom:40px; "></div>
	    		
	    		
	    		<table class="table table-hover">
		    	  <thead>
		    		<tr>
		    			<th>Catequistas</th>
		    			<th></th>
		    		</tr>
		    	  </thead>
		    	  <tbody>
		    	  <?php

		    	  	
		    	//Obter nomes dos catequistas do grupo selecionado
				$result = NULL;
                try
                {
                    $result = $db->getGroupCatechists($ed_ano_catequetico, $ed_catecismo, $ed_turma);
                }
                catch(Exception $e)
                {
                    echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
                    die();
                }

				if (isset($result) && count($result)>=1)
				{
					foreach($result as $row)
					{
						echo("<tr>\n");
						echo("\t<td>" . Utils::sanitizeOutput($row['nome']) . "</td>\n");
						echo("\t<td><div class=\"btn-group-xs pull-right btn-group-hover\" role=\"group\" aria-label=\"...\"><button type=\"button\" class=\"btn btn-default\" onclick=\"remover_catequista('" . Utils::sanitizeOutput($row['username']) . "')\" ><span class=\"glyphicon glyphicon-trash text-danger\"></span><span class='text-danger'> Remover</span></button></div></td>\n");
						echo("</tr>\n");
					}
				}

		    	  ?>
		    		<tr class="active no-print">
		    			<td><div class="input-group input-group-sm"><select class="" id="catequista" name="catequista" required>
		    									  <option disabled selected></option>
		    	<?php

                //Construir dropdown com os nomes de todos os catequistas ativos
				$result = NULL;
                try
                {
                    $result = $db->getActiveCatechists();
                }
                catch(Exception $e)
                {
                    echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
                    die();
                }

				if ($result && count($result) > 0)
				{
					foreach($result as $row)
					{
						echo("<option value=\"" . Utils::sanitizeOutput($row['username']) . "\">" . Utils::sanitizeOutput($row['nome']) . "</option>\n");
					}
				}
		    	?>
											</select></div></td>
		    			<td><button type="submit"  class="btn btn-default pull-right">
		    				<span class="glyphicon glyphicon-plus text-success"> Adicionar</span></button></td>
		    		</tr>
		    	</tbody>
		    </table>
		    
		    
		    
		<input type="hidden" name="sel_ano_catequetico" id="cat_ano_catequetico" value='<?php echo("" . $ed_ano_catequetico . ""); ?>'>
		<input type="hidden" name="cat_catecismo" id="cat_catecismo" value='<?php echo("" . $ed_catecismo . ""); ?>'>
		<input type="hidden" name="cat_turma" id="cat_turma" value='<?php echo("" . $ed_turma . ""); ?>'>
		<input type="hidden" name="cat_accao" id="cat_accao">
		<input type="hidden" name="cat_rem" id="cat_rem">
	    
	    </form>
	    </div>
</div>
  
  <?php
  		}
  	}
  ?>
  
</div>




<?php

// Dialog to confirm delete catechesis group

$deleteGroupDialog->setTitle("Confirmar eliminação");
$deleteGroupDialog->setBodyContents(<<<HTML_CODE
                <p>Tem a certeza de que pretende eliminar este grupo de catequese?</p>
HTML_CODE
);
$deleteGroupDialog->addButton(new Button("Não", ButtonType::SECONDARY))
                    ->addButton(new Button("Sim", ButtonType::DANGER, "eliminar_grupo()"));
$deleteGroupDialog->renderHTML();



// Dialog to confirm catechist availability

$catechistAvailabilityDialog->setTitle("Confirmar disponibilidade do catequista");
$catechistAvailabilityDialog->setBodyContents(<<<HTML_CODE
        <p>O catequista que seleccionou já se encontra atribuído a um grupo de catequese neste ano catequético.</p>
        <p>Ainda assim deseja atribuí-lo também a este grupo de catequese?</p>
HTML_CODE
);
$catechistAvailabilityDialog->addButton(new Button("Não", ButtonType::SECONDARY))
                            ->addButton(new Button("Sim", ButtonType::WARNING, "adicionar_catequista()"));
$catechistAvailabilityDialog->renderHTML();
?>


<!-- form oculto para eliminar grupo -->
<form role="form" action="gerirGrupos.php?op=elg" method="post" id="form_eliminar_grupo">
	<input type="hidden" name="el_ano_catequetico" id="el_ano_catequetico">
	<input type="hidden" name="el_catecismo" id="el_catecismo">
	<input type="hidden" name="el_turma" id="el_turma">
	<input type="hidden" name="sel_ano_catequetico" value="<?php echo('' . $ano_catequetico_sel . ''); ?>">
</form>


<!-- form oculto para editar grupo -->
<form role="form" action="gerirGrupos.php?op=editar" method="post" id="form_editar_grupo">
	<input type="hidden" name="ed_ano_catequetico" id="ed_ano_catequetico">
	<input type="hidden" name="ed_catecismo" id="ed_catecismo">
	<input type="hidden" name="ed_turma" id="ed_turma">
	<input type="hidden" name="sel_ano_catequetico" value="<?php echo('' . $ano_catequetico_sel . ''); ?>">
</form>




<?php
$pageUI->renderJS(); // Render the widgets' JS code
?>
<script src="js/rowlink.js"></script>

<script>

function adicionar_catequista()
{
	document.getElementById("cat_accao").value = "adicionar";
	document.getElementById("form_adicionar_remover_cat").submit();
}


function remover_catequista(nome)
{
	document.getElementById("cat_accao").value = "remover";
	document.getElementById("cat_rem").value = nome;
	document.getElementById("form_adicionar_remover_cat").submit();
}


function verificar_disponibilidade()
{
	<?php

		try
        {
            if(isset($ed_ano_catequetico))
            {
                $result = $db->getAllAssignedCatechists($ed_ano_catequetico);

                if (isset($result))
                {
                    //Criar array com nomes de catequistas ja atribuidos e script para verificar atribuicoes repetidas
                    echo("var ocupados = [");

                    foreach ($result as $catequista_existente)
                        echo("\t\t'" . $catequista_existente['username'] . "',\n");

                    echo("\t\t];\n");

                    echo("\n\tvar cat_escolhido = document.getElementById('catequista').value;\n");
                    echo("\tvar found = $.inArray(cat_escolhido, ocupados) > -1;\n");
                    echo("\tif(found)\n\t{ $('#confirmarCatequista').modal('show'); return false;\n\t} else {\n\t adicionar_catequista();\n\t}\n");
                }
                else
                {
                    echo("adicionar_catequista();");
                }
            }
            else
            {
                echo("adicionar_catequista();");
            }
		}
        catch(Exception $e)
        {
            echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
            //echo("adicionar_catequista();");
        }

	?>
}



function editar_grupo(ano, catecismo, turma)
{
	document.getElementById("ed_ano_catequetico").value = ano;
	document.getElementById("ed_catecismo").value = catecismo;
	document.getElementById("ed_turma").value = turma;
	
	document.getElementById("form_editar_grupo").submit();

}



function editar_catequistas(ano, catecismo, turma)
{
	document.getElementById("cat_ano_catequetico").value = ano;
	document.getElementById("cat_catecismo").value = catecismo;
	document.getElementById("cat_turma").value = turma;
	
	document.getElementById("form_editar_catequistas").submit();

}



function preparar_eliminar_grupo(ano, catecismo, turma)
{
	document.getElementById("el_ano_catequetico").value = ano;
	document.getElementById("el_catecismo").value = catecismo;
	document.getElementById("el_turma").value = turma;
}

function eliminar_grupo()
{
	document.getElementById("form_eliminar_grupo").submit();	

}

</script>




<script>
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
</script>

<?php
if($_REQUEST['op']=="editar" || ($_POST['cat_accao'] && ($_POST['cat_accao']=="adicionar" || $_POST['cat_accao']=="remover")))
{
    //Fazer scroll automaticamente ate a caixa para editar os catequistas
    echo("<script> $('html, body').animate({ scrollTop: $('#painel_editar').offset().top }, 1000); </script>");
}
?>

</body>
</html>