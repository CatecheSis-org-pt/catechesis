<?php

require_once(__DIR__ . '/core/config/catechesis_config.inc.php');
require_once(__DIR__ . '/authentication/utils/authentication_verify.php');
require_once(__DIR__ . '/authentication/Authenticator.php');
require_once(__DIR__ . '/core/Utils.php');
require_once(__DIR__ . "/core/PdoDatabaseManager.php");
require_once(__DIR__ . "/gui/widgets/WidgetManager.php");
require_once(__DIR__ . '/gui/widgets/Navbar/MainNavbar.php');

use catechesis\Authenticator;
use catechesis\PdoDatabaseManager;
use catechesis\gui\WidgetManager;
use catechesis\gui\MainNavbar;
use catechesis\gui\MainNavbar\MENU_OPTION;


// Create the widgets manager
$pageUI = new WidgetManager();

// Instantiate the widgets used in this page and register them in the manager
$menu = new MainNavbar(null, MENU_OPTION::CATECHESIS);
$pageUI->addWidget($menu);

?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <title>Gerir utilizadores e catequistas</title>
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
	
	
  </style>
</head>
<body>

<?php
$menu->renderHTML();
?>

<div class="container" id="contentor">
  <h2> Gerir utilizadores e catequistas</h2>
  
  <?php

	if(!Authenticator::isAdmin())
	{
		echo("<div class=\"alert alert-danger\"><strong>Erro!</strong> Não tem permissões para aceder a este recurso.</div>");
		echo("</div></body></html>");
		die();
	
	}
	

   ?>
  
  <div class="row" style="margin-bottom:40px; "></div>
  
  <div class="no-print">
  
    
  <div class="row" style="margin-top:20px; "></div>
    
  <ul class="nav nav-tabs">
  <li role="presentation"><a href="gerirUtilizadores.php">Utilizadores existentes</a></li>
  <li role="presentation" class="active"><a href="">Novo utilizador</a></li>
  </ul>
 
  </div>	
    		
    	
 
 
 
 
 <div class="row" style="margin-bottom:20px; "></div>




	
<div class="panel panel-default collapse in" id="painel_editar">
	    <div class="panel-heading"></div>
	    <div class="panel-body">    
	    
	    
	    
	    <div class="row" style="margin-bottom:20px; "></div>
	    
	    
	    <form role="form" action="gerirUtilizadores.php" method="post" onsubmit="return valida_form();" id="form_criar_utilizador">
	    
		    <!--nome-->
		    <div class="form-group">
		    <div class="col-xs-6">
		      <label for="nome">Nome:</label>
		      <input type="text" class="form-control" id="nome" name="nome" placeholder="Nome completo" required>
		    </div>
		    </div>
		    
		    <!--email-->
		    <div class="form-group">
		    <div class="col-xs-4">
		      <label for="email">E-mail:</label>
		      <input type="email" class="form-control" id="email" name="email" placeholder="E-mail do catequista">
		    </div>
		    </div>
		    
		    <!--telefone-->
		     <div class="form-group">
		    <div class="col-xs-2">
		      <label for="tel">Telefone:</label>
		      <input type="tel" class="form-control" id="telefone" name="telefone" placeholder="Telefone">
		    </div>
		    </div>
		    
		    
		    <div class="row" style="margin-bottom:20px; "></div>
		    
		     <!--username-->
		    <div class="form-group">
		    <div class="col-xs-4">
		      <label for="un">Nome de utilizador:</label>
		      <input type="text" class="form-control" id="un" name="un" placeholder="Nome a usar no sistema" onchange="valida_username()" required>
		    </div>
		    </div>
		    
		    
		    <div class="col-xs-8">
		    	<label for="erro">&nbsp;<br>&nbsp;</label>
		    	<span class="text-danger" id="erro_un" style="display: none;"><span class="glyphicon glyphicon-remove"></span> O nome de utilizador é inválido. Deve conter apenas letras e/ou dígitos, sem espaços.</span>
		    	<span class="text-danger" id="erro_un2" style="display: none;"><span class="glyphicon glyphicon-remove"></span> O nome de utilizador já existe.</span>
		    </div>
		    
		    <div class="clearfix"></div>
		    
		    <!--password1-->
		    <div class="form-group">
		    <div class="col-xs-4">
		      <label for="pw1">Palavra-passe:</label>
		      <input type="password" class="form-control" id="password1" name="password1" onchange="valida_password()" required>
		    </div>
		    </div>
		    
		    <div class="col-xs-8">
		    	<label for="erro">&nbsp;<br>&nbsp;</label>
		    	<span class="text-danger" id="erro_p1" style="display: none;"><span class="glyphicon glyphicon-remove"></span> A palavra-passe é inválida. Deve conter letras e dígitos e não deve ser inferior a 10 caracteres.</span>
		    </div>
		    
		    <div class="clearfix"></div>
		    
		    <!--password2-->
		    <div class="form-group">
		    <div class="col-xs-4">
		      <label for="pw2">Confirmar palavra-passe:</label>
		      <input type="password" class="form-control" id="password2" name="password2" onchange="testa_passwords()" required>
		    </div>
		    </div>
		    
		    <div class="col-xs-8">
		    	<label for="erro">&nbsp;<br>&nbsp;</label>
		    	<span class="text-danger" id="erro_p2" style="display: none;"><span class="glyphicon glyphicon-remove"></span> As palavras-passe não coincidem.</span>
		    </div>
		    
		    <div class="clearfix"></div>
		    
		    
		    
		    <div class="row" style="margin-bottom:20px; "></div>
		    
		    <div class="form-group" id="catequista">
		    <div class="col-xs-8">
		    	<label for="catequista">Catequista?:</label>
		    	<label class="radio-inline"><input type="radio" name="catequista" value="nao" checked>Não</label>
			<label class="radio-inline" data-toggle="tooltip" data-placement="top" title="O utilizador vai dar catequese neste ano catequetico."><input type="radio" name="catequista" value="activo" >Sim, ativo</label>
			<label class="radio-inline" data-toggle="tooltip" data-placement="top" title="O utilizador já deu catequese, mas não está no activo neste ano catequético."><input type="radio" name="catequista" value="inactivo" >Sim, inativo</label>
		    </div>
		    <div class="clearfix"></div>
		    </div>
		    
		    <div class="form-group" id="admin">
		    <div class="col-xs-8">
		    	<label for="catequista">Tipo de conta:</label>
		    	<label class="radio-inline" data-toggle="tooltip" data-placement="top" title="O utilizador pode apenas consultar as fichas dos seus catequizandos, se for catequista. Não pode modificar fichas nem gerir utilizadores ou grupos de catequese."><input type="radio" name="admin" value="0" checked>Utilizador apenas</label>
			<label class="radio-inline" data-toggle="tooltip" data-placement="top" title="Se promover este utilizador a administrador este poderá consultar e modificar fichas de todos os catequizandos, gerir grupos de catequese, utilizadores e catequistas, e poderá inclusivamente promover outros utilizadores a administradores. Esta acção é irreversível."><input type="radio" name="admin" value="1">Administrador</label>
		    </div>
		    <div class="clearfix"></div>
		    </div>
		    
		    
		    <input type="hidden" name="op" value="criar">
 		    
		    <div class="col-xs-8">
	    		<button type="submit" class="btn btn-primary"><span class="glyphicon glyphicon-plus"></span> Criar</button>
	    	   </div>
	    	   <div class="clearfix"></div>
	    	   
	    </form>
	    </div>
</div>
</div>

<?php
$pageUI->renderJS(); // Render the widgets' JS code
?>

<?php

	$db = new PdoDatabaseManager();
	
	
	//Obter utilizadores
    $result = NULL;

    try
    {
        $result = $db->getAllUsers();
    }
    catch(Exception $e)
    {
        echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
        die();
    }

	
	if(isset($result) && count($result)>=1)
	{
		//Criar array com nomes de utilizadores e script para verificar usernames repetidos
       echo("<script>\n");
       echo("function verifica_username_repetido(){\n");
       echo("\tvar usernames = [ \n");
		
		foreach($result as $row)
		{
			echo("\t\t'" . strtoupper($row['username']) . "',\n");								// *** Usernames sao case insensitive. ***
		}
		
       echo("\t\t];\n");

       echo("\n\tvar nome_escrito = document.getElementById('un').value;\n");
       echo("\tvar found = $.inArray(nome_escrito.toUpperCase(), usernames) > -1;\n");		// *** Usernames sao case insensitive. ***
       echo("\tif(found)\n\t{ $('#username_repetido').show(); \n\treturn false;\n\t} else {\n\t $('#username_repetido').hide(); \n\treturn true;\n\t}\n");

       echo("}\n</script>\n");
		
	}
	else
	{
		echo("function verifica_username_repetido(){ return true; }\n");
	}
	
	
	//Libertar recursos
	$result = null;
?>


<script>

function valida_username()  
{  
	var letterNumber = /^[0-9a-zA-Z_.]+$/; 
	var un = document.getElementById('un').value;
	 
	 
	 if(un!=="" && !verifica_username_repetido())
	 {
	 	document.getElementById('erro_un').style = "display: none;";
	 	document.getElementById('erro_un2').style = "";
		return false;   
	 }
	else if((un.match(letterNumber)))   
	{  
		document.getElementById('erro_un').style = "display: none;";
		document.getElementById('erro_un2').style = "display: none;";
		return true;  
	}  
	else  
	{   
		document.getElementById('erro_un').style = "";
		document.getElementById('erro_un2').style = "display: none;";
		return false;   
	}  
}  



function valida_password()
{

	var letterNumber = /^(?=.*[a-zA-Z])(?=.*[0-9])/;
	var p1 = document.getElementById('password1').value;
	
	if(p1.length >= 10 && (p1.match(letterNumber)))
	{
		document.getElementById('erro_p1').style = "display: none;";
		return true;
	}
	else
	{
		document.getElementById('erro_p1').style = "";
		return false;
	}
		
	
}


function testa_passwords()
{
	var p1 = document.getElementById('password1').value;
	var p2 = document.getElementById('password2').value;
	
	if(p1!=p2)
	{
		document.getElementById('erro_p2').style = "";
		return false;
	}
	else
	{
		document.getElementById('erro_p2').style = "display: none;";
		return true;
	}
	
}


function valida_form()
{

	if(!valida_username())
	{
		alert("O nome de utilizador já existe ou é inválido. Deve conter apenas letras e/ou dígitos, sem espaços.");
		return false;
	}
	if(!valida_password())
	{
		alert("A palavra-passe é inválida. Deve conter letras e dígitos e não deve ser inferior a 10 caracteres.");
		return false;
	}
	if(!testa_passwords())
	{
		alert("As palavras-passe não coincidem.");
		return false;
	}
	
	
	return true;
}
</script>

<script>
$(function () {
  $('[data-toggle="tooltip"]').tooltip()
})
</script>

</body>
</html>