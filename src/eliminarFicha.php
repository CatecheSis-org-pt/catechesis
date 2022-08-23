<?php

require_once(__DIR__ . '/core/config/catechesis_config.inc.php');
require_once(__DIR__ . '/authentication/utils/authentication_verify.php');
require_once(__DIR__ . '/authentication/Authenticator.php');
require_once(__DIR__ . '/core/log_functions.php'); //Para poder escrever no log
require_once(__DIR__ . "/core/DatabaseManager.php");
require_once(__DIR__ . "/core/PdoDatabaseManager.php");
require_once(__DIR__ . "/gui/widgets/WidgetManager.php");
require_once(__DIR__ . '/gui/widgets/Navbar/MainNavbar.php');
require_once(__DIR__ . "/core/domain/Sacraments.php");
require_once(__DIR__ . '/core/Utils.php');
require_once(__DIR__ . '/core/UserData.php');

use catechesis\DatabaseAccessMode;
use catechesis\PdoDatabaseManager;
use catechesis\Utils;
use catechesis\UserData;
use catechesis\Authenticator;
use core\domain\Sacraments;
use catechesis\gui\WidgetManager;
use catechesis\gui\MainNavbar;
use catechesis\gui\MainNavbar\MENU_OPTION;


// Create the widgets manager
$pageUI = new WidgetManager();

// Instantiate the widgets used in this page and register them in the manager
$menu = new MainNavbar(null, MENU_OPTION::CATECHUMENS);
$pageUI->addWidget($menu);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <title>Detalhes do catequizando</title>
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
</head>
<body>

<?php
$menu->renderHTML();
?>

<div class="container" id="contentor">
  <h2> Detalhes do catequizando</h2>

  <?php

    $db = new PdoDatabaseManager();
    $cid = intval(Utils::sanitizeInput($_REQUEST['cid']));


    //Permitir apenas a administradores
    if(!Authenticator::isAdmin())
    {
        echo("<div class=\"alert alert-danger\"><strong>Erro!</strong> Não tem permissões para aceder a este recurso.</div>");
        echo("</div></body></html>");
        die();
    }

    //Funcao para criar link de retorno em caso de erro
    function abortar()
    {
        global $cid;
        global $db;

        if(isset($db))
            $db->rollBack();

        writeLogEntry("Eliminação do catequizando com cid=" . $cid . ", falhou.");

        echo("<p>Regressar à <a href=\"mostrarFicha.php?cid=" . $cid . "\">ficha do catequizando</a></p>");

        //Libertar recursos
        $db = null;
        die();
    }




	if($cid && $cid>0)
	{
		$result = NULL;
		$fid_pai = NULL;
		$fid_mae = NULL;
		$fid_ee = NULL;	
		$nome = NULL;	
		$foto = NULL;
		
		
		//Obter dados do catequizando
		try
        {
            $result = $db->getCatechumenById($cid);

            if(isset($result))
            {
                $nome = Utils::sanitizeOutput($result['nome']);
                $fid_pai = Utils::sanitizeOutput($result['pai']);
                $fid_mae = Utils::sanitizeOutput($result['mae']);
                $fid_ee = Utils::sanitizeOutput($result['enc_edu']);
                $foto = Utils::sanitizeOutput($result['foto']);
                $familyMembers = $db->getCatechumenAuthorizationList($cid);
            }
            else
            {
                echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Catequizando não encontrado!</div>");
                abortar();
            }
		}
        catch (Exception $e)
        {
            echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
            abortar();
        }



        $db->beginTransaction(DatabaseAccessMode::DEFAULT_DELETE);
		
		//Eliminar baptismo
		try
        {
            if($db->deleteSacramentRecord($cid, Sacraments::BAPTISM))
			{
			    writeLogEntry("Eliminado baptismo do catequizando " . $nome . ", com cid=" . $cid . ".");
			}
		}
        catch (Exception $e)
        {
            //Nao existe registo de baptismo
        }

		//Eliminar primeira comunhão
		try
        {
			if($db->deleteSacramentRecord($cid, Sacraments::FIRST_COMMUNION))
            {
                writeLogEntry("Eliminada primeira comunhão do catequizando " . $nome . ", com cid=" . $cid . ".");
			}
		}
        catch (Exception $e)
        {
            //Nao existe registo de primeira comunhao
        }

		
		//Eliminar profissao fe
		try
        {
            if($db->deleteSacramentRecord($cid, Sacraments::PROFESSION_OF_FAITH))
            {
                writeLogEntry("Eliminada profissão de fé do catequizando " . $nome . ", com cid=" . $cid . ".");
			}
		}
		catch (Exception $e)
        {
            //Nao existe registo de profissao de fe
        }
		
        //Eliminar crisma
		try
        {
            if($db->deleteSacramentRecord($cid, Sacraments::CHRISMATION))
            {
                writeLogEntry("Eliminado crisma do catequizando " . $nome . ", com cid=" . $cid . ".");
			}
		}
        catch (Exception $e)
        {
            //Nao existe registo de baptismo
        }

		
		//Eliminar escolaridade
		try
        {
			if($db->deleteCatechumenSchoolingRecord($cid))
			{
			    writeLogEntry("Eliminado percurso escolar do catequizando " . $nome . ", com cid=" . $cid . ".");
			}
			else
			{
				echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Falha ao tentar eliminar registos de escolaridade do catequizando.</div>");
				abortar();
            }
		}
        catch (Exception $e)
        {
            echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
            abortar();
        }
		
		
		
		//Eliminar inscricoes
		try
        {
            if($db->unenrollCatechumenFromAllGroups($cid, false))
            {
                writeLogEntry("Eliminadas inscrições do catequizando " . $nome . ", com cid=" . $cid . ", em grupos de catequese.");
			}
            else
            {
				echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Falha ao tentar eliminar registos de inscrições do catequizando em grupos de catequese.</div>");
				abortar();
            }
		}
        catch (Exception $e)
        {
            echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
            abortar();
        }



        //Eliminar familiares autorizados a vir buscar
        // NOTA: Os familiares que podem vir buscar o catequizando sao criados especificamente para a pagina de autorizacoes
        // e nao tem outro uso, portanto podemos elimina-los aqui.
        try
        {
            $errorsOnFamilyMemberDeletion = false;
            foreach($familyMembers as $familyMember)
            {
                try
                {
                    $db->deleteFamilyMember($familyMember['fid']);
                    $db->removeFamilyMemberFromCatechumenAuthorizationList($cid, $familyMember['fid']);
                }
                catch(Exception $e)
                {
                    // Keep going if the removal of some family member failed. Log this event afterwards.
                    $errorsOnFamilyMemberDeletion = true;
                    echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro! Falha ao eliminar um familiar do catequizando. Detalhes: </strong> " . $e->getMessage() . "</div>");
                }
            }
            //NOTE: A custom SQL query could be used here: "DELETE FROM familiar WHERE fid IN (SELECT fid FROM autorizacaoSaidaMenores WHERE cid = :cid);"

            if($errorsOnFamilyMemberDeletion)
                writeLogEntry("Erro! Alguns familiares do catequizando " . $nome . ", com cid=" . $cid . ", não puderam ser eliminados.");

            writeLogEntry("Eliminados familiares do catequizando " . $nome . ", com cid=" . $cid . ", que podiam vir buscá-lo.");
        }
        catch (Exception $e)
        {
            echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
            abortar();
        }
		
			

		//Eliminar catequizando
		try
        {
            if(!$db->deleteCatechumen($cid))
            {
                echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Falha ao eliminar ficha do catequizando.</div>");
                abortar();
            }
		}
        catch (Exception $e)
        {
            echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
            abortar();
        }
		
		

		
		
		//Verificar se algum dos encarregados de educacao pode ser removido (nao tem mais filhos)
		
		//Pai
		if(!is_null($fid_pai))
		{
			try
            {
                $db2 = new PdoDatabaseManager();    //Use another DB connection, because the previous one has a pending transaction
                $children = $db2->getFamilyMemberChildren($fid_pai);

                if(!isset($children) || empty($children))
                {
                    $db->deleteFamilyMember($fid_pai);
                    writeLogEntry("Eliminado pai do catequizando " . $nome . ", com fid=" . $fid_pai . ".");
                }

                $db2 = null;
			}
            catch (Exception $e)
            {
                echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
                abortar();
            }
		}
		
		
		//Mae
		if(!is_null($fid_mae))
		{
			try
            {
                $db2 = new PdoDatabaseManager();    //Use another DB connection, because the previous one has a pending transaction
                $children = $db2->getFamilyMemberChildren($fid_mae);

                if(!isset($children) || empty($children))
                {
                    $db->deleteFamilyMember($fid_mae);
                    writeLogEntry("Eliminada mãe do catequizando " . $nome . ", com fid=" . $fid_mae . ".");
                }

                $db2 = null;
            }
            catch (Exception $e)
            {
                echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
                abortar();
            }
		}
		
		
		//Encarregado educacao (outro)
		if(!is_null($fid_ee) && $fid_ee!=$fid_pai && $fid_ee!=$fid_mae)
		{
			try
            {
                $db2 = new PdoDatabaseManager();    //Use another DB connection, because the previous one has a pending transaction
                $children = $db2->getFamilyMemberChildren($fid_ee);

                if(!isset($children) || empty($children))
                {
                    $db->deleteFamilyMember($fid_ee);
                    writeLogEntry("Eliminado encarregado de educação do catequizando " . $nome . ", com fid=" . $fid_ee . ".");
                }

                $db2 = null;
			}
            catch (Exception $e)
            {
                echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
                abortar();
            }
		}
		

		//Eliminar foto
		if($foto)
		{
			unlink(UserData::getCatechumensPhotosFolder() . '/' . $foto);
			writeLogEntry("Eliminada foto do catequizando " . $nome . ", com nome de ficheiro=" . $foto . ".");
		}
		
		
		if($db->commit())
		{	
			writeLogEntry("Eliminado catequizando " . $nome . ", com cid=" . $cid . ".");
			echo("<div class=\"alert alert-success\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Sucesso!</strong> Catequizando $nome eliminado da base de dados.</div>");
		}
		else
		{
			echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Falha ao tentar eliminar ficha do catequizando.</div>");
			abortar();
		}
	}

	//Libertar recursos
    $db = null;
?>

	<p>Ir para <a href="meusCatequizandos.php">Os meus catequizandos</a></p>
	
</div>

<?php
$pageUI->renderJS(); // Render the widgets' JS code
?>

</body>
</html>