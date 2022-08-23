<?php

require_once(__DIR__ . '/core/config/catechesis_config.inc.php');
require_once(__DIR__ . '/authentication/utils/authentication_verify.php');
require_once(__DIR__ . '/authentication/Authenticator.php');
require_once(__DIR__ . '/core/log_functions.php'); //Para poder escrever no log
require_once(__DIR__ . '/core/Utils.php');
require_once(__DIR__ . "/core/PdoDatabaseManager.php");
require_once(__DIR__ . '/gui/widgets/WidgetManager.php');
require_once(__DIR__ . '/gui/widgets/Navbar/MainNavbar.php');
require_once(__DIR__ . '/gui/widgets/ModalDialog/ModalDialogWidget.php');
require_once(__DIR__ . "/gui/widgets/configuration_panels/UserAccountConfigurationPanel/UserAccountConfigurationPanelWidget.php");


use catechesis\PdoDatabaseManager;
use catechesis\Authenticator;
use catechesis\Utils;
use catechesis\gui\WidgetManager;
use catechesis\gui\MainNavbar;
use catechesis\gui\MainNavbar\MENU_OPTION;
use catechesis\gui\ModalDialogWidget;
use catechesis\gui\UserAccountConfigurationPanelWidget;
use catechesis\gui\Button;
use catechesis\gui\ButtonType;


$db = new PdoDatabaseManager();
$ulogin = new uLogin();


// Create the widgets manager
$pageUI = new WidgetManager();

// Instantiate the widgets used in this page and register them in the manager
$menu = new MainNavbar(null, MENU_OPTION::CATECHESIS);
$pageUI->addWidget($menu);
$confirmAdminPriviledgesDialog = new ModalDialogWidget("confirmarAdmin");
$pageUI->addWidget($confirmAdminPriviledgesDialog);
$confirmAdminRevokeDialog = new ModalDialogWidget("dispensarAdmin");
$pageUI->addWidget($confirmAdminRevokeDialog);
$userAccountSettingsPanel = (new UserAccountConfigurationPanelWidget())->allowChangingOtherUsers(true);
$pageUI->addWidget($userAccountSettingsPanel);


?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <title>Gerir utilizadores e catequistas</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php $pageUI->renderCSS(); // Render the widgets' CSS ?>
  <link rel="stylesheet" href="css/custom-navbar-colors.css">
  <link rel="stylesheet" href="css/btn-group-hover.css">

  
  
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
	
  <h2> Gerir utilizadores e catequistas</h2>
  
  <div class="row" style="margin-bottom:40px; "></div>
  
  <div class="no-print">      
  	<div class="row" style="margin-top:20px; "></div>  	  
  	<ul class="nav nav-tabs">
  	<li role="presentation" class="active"><a href="">Utilizadores existentes</a></li>
  	<li role="presentation"><a href="criarUtilizador.php">Novo utilizador</a></li>
  	</ul> 
  </div>

<?php

	//Criar conta
	if(isset($_POST['op']) && $_POST['op']=="criar")
	{

		$un = Utils::sanitizeInput($_POST['un']);
		$nome = Utils::sanitizeInput($_POST['nome']);
	
		$tel = NULL;
		if(isset($_POST['telefone']) && $_POST['telefone']!="")
			$tel = intval($_POST['telefone']);
	
		$email = NULL;
		if(isset($_POST['email']) && $_POST['email']!="")
			$email = Utils::sanitizeInput($_POST['email']);
	
		$password_user = Utils::sanitizeInput($_POST['password1']);
		$catequista = Utils::sanitizeInput($_POST['catequista']);
		$administrador = intval($_POST['admin']);

        $isAdmin = ($administrador==1);
        $isCatechist = ($catequista=="activo" || $catequista=="inactivo");
        $isCatechistActive = ($catequista=="activo");
	
	
		if(!$password_user || $password_user=="")
		{
			echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> As password é inválida. Falha ao criar conta de utilizador.</div>");
		}
		else
		{
			try
			{
			    if($db->createUserAccount($un, $nome, $password_user, $isAdmin, $isCatechist, $isCatechistActive, $tel, $email))
                {

                    if($isCatechist)
                    {
                        writeLogEntry("Criada conta do utilizador e catequista " . $un . ".");
                        echo("<div class=\"alert alert-success\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Sucesso!</strong> Conta do utilizador " . $un . " criada.</div>");
                    }
                    else
                    {
                        writeLogEntry("Criada conta do utilizador " . $un . ".");
                        echo("<div class=\"alert alert-success\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Sucesso!</strong> Conta do utilizador " . $un . " criada.</div>");
                    }
                }
			    else
                {
                    echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Falha ao criar conta de utilizador.</div>");
                }
			}
            catch (Exception $e)
            {
                echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
            }
		}
	}
	
	
	
	
	
	//Aplicar accoes
	$activar_username = NULL;
	$bloquear_username = NULL;
	$cat_username = NULL;
	
	
	//Activar conta
	if(isset($_POST['op']) && $_POST['op']=="activar")
	{
		$activar_username = Utils::sanitizeInput($_POST['un']);

		try
        {
		    if($db->activateUserAccount($activar_username))
            {
                writeLogEntry("Ativada conta do utilizador " . $activar_username . ".");
                echo("<div class=\"alert alert-success\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Sucesso!</strong> Conta do utilizador " . $activar_username . " ativada.</div>");
			}
		    else
            {
                echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Falha ao ativar conta do utilizador " . $activar_username . ".</div>");
            }
			     	    
		}
        catch (Exception $e)
        {
            echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
            die();
        }
	}
	
	
	
	//Bloquear conta
	if(isset($_POST['op']) && ($_POST['op']=="bloquear" || $_POST['op']=="dis_cat"))
	{
	
		$bloquear_username = Utils::sanitizeInput($_POST['un']);

		if($bloquear_username != Authenticator::getUsername()) //O utilizador nao se bloqueia a ele proprio
		{
            try
            {
                if ($db->blockUserAccount($bloquear_username))
                {
                    writeLogEntry("Bloqueada conta do utilizador " . $bloquear_username . ".");
                    echo("<div class=\"alert alert-success\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Sucesso!</strong> Conta do utilizador " . $bloquear_username . " bloqueada.</div>");
                }
                else
                {
                    echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Falha ao bloquear conta do utilizador " . $bloquear_username . ".</div>");

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

	
	
	//Tornar administrador
	if(isset($_POST['op']) && $_POST['op']=="set_admin")
	{
		$admin_username = Utils::sanitizeInput($_POST['un']);

		try
        {
			if($db->giveAdminRights($admin_username))
			{
			   writeLogEntry("Utilizador " . $admin_username . " promovido a administrador.");
			   echo("<div class=\"alert alert-success\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Sucesso!</strong> Utilizador " . $admin_username . " promovido a administrador.</div>");
			}
			else
            {
                echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Falha ao promover o utilizador " . $admin_username . " a administrador.</div>");

;
            }
			     	    
		}
        catch (Exception $e)
        {
            echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
            die();
        }
	}


    //Dispensar administrador
    if(isset($_POST['op']) && $_POST['op']=="unset_admin")
    {
        $admin_username = Utils::sanitizeInput($_POST['un']);

        if($admin_username == Authenticator::getUsername())
        {
            echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Não pode dispensar-se a si próprio da função de administrador. Por favor peça a outro administrador para o fazer.</div>");
        }
        else
        {
            try
            {
                if($db->revokeAdminRights($admin_username))
                {
                    writeLogEntry("Utilizador " . $admin_username . " dispensado da função de administrador.");
                    echo("<div class=\"alert alert-success\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Sucesso!</strong> Utilizador " . $admin_username . " dispensado da função de administrador.</div>");
                }
                else
                {
                    echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Falha ao dispensar o utilizador " . $admin_username . " da função de administrador.</div>");

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
	
	
	
	//Activar catequista
	if(isset($_POST['op']) && $_POST['op']=="make_cat")
	{
		$cat_username = Utils::sanitizeInput($_POST['un']);

		try
        {
            if($db->setAsActiveCatechist($cat_username))
			{
			    if(Authenticator::getUsername() == $cat_username)
                    $_SESSION['catequista'] = 1;    // To see immediate changes in the current session

			    writeLogEntry("Utilizador " . $cat_username . " definido como catequista ativo.");
                echo("<div class=\"alert alert-success\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Sucesso!</strong> Utilizador " . $cat_username . " definido como catequista ativo.</div>");
			}
            else
            {
                echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Falha ao definir o utilizador " . $cat_username . " como catequista ativo.</div>");

;
            }
		}
        catch (Exception $e)
        {
            echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
            die();
        }
	}
	
	
	
	
	//Desactivar catequista
	if(isset($_POST['op']) && $_POST['op']=="dis_cat")
	{
		$cat_username = Utils::sanitizeInput($_POST['un']);

		try
        {
			if($db->setAsInactiveCatechist($cat_username))
			{
                if(Authenticator::getUsername() == $cat_username)
                    $_SESSION['catequista'] = 0;    // To see immediate changes in the current session

				writeLogEntry("Catequista " . $cat_username . " definido como inativo.");
				echo("<div class=\"alert alert-success\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Sucesso!</strong> Catequista " . $cat_username . " definido como inativo.</div>");
			}
			else
			{				
				echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Falha ao definir o utilizador " . $cat_username . " como catequista inativo.</div>");

;
			}
		}
        catch (Exception $e)
        {
            echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
            die();
        }
	}
	
	

	//Editar detalhes da conta
    $userAccountSettingsPanel->handlePost(); //Handle POSTs with account details change
	
	?>
	
	<!--<div class="row" style="margin-bottom:60px; "></div>-->

	<!-- Cabecalho com Num Resultados --> 
	<div class="well well-lg" style="position:relative; z-index:2;"> 
		<h2 style="margin-top: 0px;"><small>Estatísticas</small></h2>
	    <h4><small><span id="numero_catequistas">--</span> catequistas ativos</small></h4>	  
	    <h4><small><span id="numero_utilizadores">--</span> utilizadores ativos</small></h4>
	    <h4><small><span id="numero_contas">--</span> contas registadas</small></h4>	 	 
	</div>
	<div class="row" style="margin-top:20px; "></div>
	
	<div class="col-xs-12">
    	<table class="table table-hover">
    	  <thead>
    		<tr>
    			<th>Nome</th>
    			<th>Username</th>
    			<th>E-mail</th>
    			<th>Telefone</th>  
    			<th>Catequista</th> 
    			<th>Conta</th>		
    			<th></th>
    		</tr>
    	  </thead>
    	  <tbody>

    <?php

	//Obter utilizadores
    $result = null;
	try
    {
		$result = $db->getAllUsers();
	}
    catch (Exception $e)
    {
        echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
        die();
    }
	
	
	if(isset($result) && count($result)>=1)
	{
		$num_catequistas = 0;
		$num_utilizadores = 0;
		$num_contas = 0;
		
		foreach($result as $row)
		{
			echo("\t<tr class='" . (($row['mostrar']<=0)?"active":"") . "'>\n");
			
			echo("\t\t<td>" . Utils::sanitizeOutput($row['nome']) . "</td>\n");
			echo("\t\t<td>" . Utils::sanitizeOutput($row['username']) . "</td>\n");
			
			if($row['email'] && $row['email']!="")
				echo("\t\t<td>" . Utils::sanitizeOutput($row['email']) . "</td>\n");
			else
				echo("\t\t<td> - </td>\n");
			
			if($row['tel'] && $row['tel']!="")	
				echo("\t\t<td>" . Utils::sanitizeOutput($row['tel']) . "</td>\n");
			else
				echo("\t\t<td> - </td>\n");

			if(isset($row['cat_estado']) && $row['cat_estado']==1)
			{
				echo("\t\t<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span class=\"label label-success\">Ativo</span></td>\n");
				$num_catequistas = $num_catequistas + 1;
			}
			else if(isset($row['cat_estado']) && $row['cat_estado']==0)
				echo("\t\t<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span class=\"label label-default\">Inativo</span></td>\n");
			else //Nao e catequista (NULL)
				echo("\t\t<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span class=\"text-danger glyphicon glyphicon-remove\"></span></td>\n");

			echo("\t\t<td>");
			if($row['u_estado']==1)
			{
				echo("<span class=\"label label-success\">Ativa</span>");
				$num_utilizadores = $num_utilizadores + 1;
			}
			else
				echo("<span class=\"label label-danger\">Bloqueada</span>");
				
			if($row['admin']==1)
				echo("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span class=\"label label-primary\">Administrador</span>");

			$num_contas = $num_contas + 1;
			
			?>
			
			<td>
    				<div class="btn-group btn-group-hover">
    				<button type="button" class="btn btn-xs btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false"><span class="glyphicon glyphicon-wrench"></span> Ações <span class="caret"></span></button>
                      <ul class="dropdown-menu" role="menu">
                      <li role="presentation" class="dropdown-header"><span class="glyphicon glyphicon-user"></span> Conta</li>
                         <?php if($row['username'] != Authenticator::getUsername())
                             { ?>
                        <li><a onclick="activar_conta('<?php echo('' . Utils::sanitizeOutput($row['username']));?>')" style="cursor: pointer;">Ativar</a></li>
                        <li><a onclick="bloquear_conta('<?php echo('' . Utils::sanitizeOutput($row['username']));?>')" style="cursor: pointer;">Bloquear</a></li>
                            <?php if($row['admin']==1){ ?>
                            <li><a href="#" data-toggle="modal" data-target="#dispensarAdmin" onclick="prepara_admin('<?php echo('' . Utils::sanitizeOutput($row['username']));?>', 'unset_admin')">Dispensar administrador</a></li>
                            <?php } else { ?>
                            <li><a href="#" data-toggle="modal" data-target="#confirmarAdmin" onclick="prepara_admin('<?php echo('' . Utils::sanitizeOutput($row['username']));?>', 'set_admin')">Tornar administrador</a></li>
                            <?php } ?>
                        <?php } ?>
                        <li><a href="gerirUtilizadores.php?op=editar&amp;un=<?php echo('' . Utils::sanitizeOutput($row['username']));?>">Modificar dados da conta</a></li>
                        <li class="divider"></li>
                      <li role="presentation" class="dropdown-header"><span class="glyphicon glyphicon-briefcase"></span> Catequista</li>
                        <li><a onclick="activar_cat('<?php echo('' . Utils::sanitizeOutput($row['username']));?>')" style="cursor: pointer;">Ativar</a></li>
                        <li><a onclick="desactivar_cat('<?php echo('' . Utils::sanitizeOutput($row['username']));?>')" style="cursor: pointer;">Desativar</a></li>
                      </ul>
				 </div>
    			</td>
    			<?php
    			
    			echo("\t</tr>\n");
		}
	}
?>
    	</tbody>
    </table>
   </div>

 <div class="row" style="margin-bottom:60px; "></div>

<?php
	if(isset($_REQUEST['op']) && $_REQUEST['op']=="editar")
	{ 
		$ed_username = Utils::sanitizeInput($_REQUEST['un']);

        $userAccountSettingsPanel->setUsername($ed_username);
        $userAccountSettingsPanel->renderHTML();


		//Fazer scroll automaticamente ate a caixa para editar os dados do utilizador
        ?>
  		<script> $('html, body').animate({ scrollTop: $('#<?= $userAccountSettingsPanel->getID() ?>').offset().top }, 1000); </script>
        <?php
	} //--if(isset($_REQUEST['op']) && $_REQUEST['op']=="editar")
?>
  




<?php

// Dialog to give administrator priviledges

$confirmAdminPriviledgesDialog->setTitle("Tornar administrador");
$confirmAdminPriviledgesDialog->setBodyContents(<<<HTML_CODE
        <p>Se promover este utilizador a administrador este poderá consultar e modificar fichas de todos os catequizandos, gerir grupos de catequese, utilizadores e catequistas, e poderá inclusivamente promover e despromover outros utilizadores a administradores.</p>
        <p>Tem a certeza de que pretende promover este utilizador a administrador?</p>
HTML_CODE
);
$confirmAdminPriviledgesDialog->addButton(new Button("Não", ButtonType::SECONDARY))
                                ->addButton(new Button("Sim", ButtonType::DANGER, "submete_admin()"));
$confirmAdminPriviledgesDialog->renderHTML();



// Dialog to give administrator priviledges

$confirmAdminRevokeDialog->setTitle("Dispensar administrador");
$confirmAdminRevokeDialog->setBodyContents(<<<HTML_CODE
        <p>Se dispensar este administrador este deixará de poder consultar e modificar fichas de todos os catequizandos, gerir grupos de catequese, utilizadores e catequistas, mas continuará a ter acesso às fichas dos respetivos catequizandos.</p>
        <p>Tem a certeza de que pretende dispensar este administrador?</p>
HTML_CODE
);
$confirmAdminRevokeDialog->addButton(new Button("Não", ButtonType::SECONDARY))
                            ->addButton(new Button("Sim", ButtonType::DANGER, "submete_admin()"));
$confirmAdminRevokeDialog->renderHTML();

?>

<!-- Form para activar conta-->
<form id="form_activar_conta" action="gerirUtilizadores.php" method="post">
	<input type="hidden" name="op" value="activar">
	<input type="hidden" name="un" id="activar_un">
</form>

<!-- Form para bloquear conta-->
<form id="form_bloquear_conta" action="gerirUtilizadores.php" method="post">
	<input type="hidden" name="op" value="bloquear">
	<input type="hidden" name="un" id="bloquear_un">
</form>


<!-- Form para activar catequista-->
<form id="form_activar_cat" action="gerirUtilizadores.php" method="post">
	<input type="hidden" name="op" value="make_cat">
	<input type="hidden" name="un" id="act_cat_un">
</form>

<!-- Form para desactivar catequista-->
<form id="form_desactivar_cat" action="gerirUtilizadores.php" method="post">
	<input type="hidden" name="op" value="dis_cat">
	<input type="hidden" name="un" id="dis_cat_un">
</form>


<!-- Form para tornar administrador-->
<form id="form_admin" action="gerirUtilizadores.php" method="post">
	<input type="hidden" name="op" id="admin_op" value="set_admin">
	<input type="hidden" name="un" id="admin_un">
</form>



<?php
$pageUI->renderJS(); // Render the widgets' JS code
?>

<script>
function activar_conta(username)
{
	document.getElementById('activar_un').value = username;
	document.getElementById('form_activar_conta').submit();
}

function bloquear_conta(username)
{
	document.getElementById('bloquear_un').value = username;
	document.getElementById('form_bloquear_conta').submit();
}


function activar_cat(username)
{
	document.getElementById('act_cat_un').value = username;
	document.getElementById('form_activar_cat').submit();
}


function desactivar_cat(username)
{
	document.getElementById('dis_cat_un').value = username;
	document.getElementById('form_desactivar_cat').submit();
}

function prepara_admin(username, op)
{
	document.getElementById('admin_un').value = username;
    document.getElementById('admin_op').value = op;
}

function submete_admin()
{
	document.getElementById('form_admin').submit();	
}

</script>

<script src="js/btn-group-hover.js"></script>

<script>
	document.getElementById('numero_catequistas').innerHTML = "<?php echo($num_catequistas); ?>";
	document.getElementById('numero_utilizadores').innerHTML = "<?php echo($num_utilizadores); ?>";
	document.getElementById('numero_contas').innerHTML = "<?php echo($num_contas); ?>";
</script>

</body>
</html>