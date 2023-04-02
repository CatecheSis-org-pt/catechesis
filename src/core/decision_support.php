<?php
// ///////////////////////////////////////////////////////////////////////////////////////////
//                                                                                          //
//                          = Sistema de apoio a decisao =                                  //                                                                                       //
//                    CatecheSis - Sistema de gestao de catequese        					//
// ///////////////////////////////////////////////////////////////////////////////////////////

// Funcoes genericas e renderizadores para produzir relatorios de apoio a decisao sobre
// catequizandos, a respeito de qualquer sacramento.

require_once(__DIR__ . '/PdoDatabaseManager.php');
require_once(__DIR__ . '/DataValidationUtils.php');
require_once(__DIR__ . '/Utils.php');
require_once(__DIR__ . '/Configurator.php');

use catechesis\Configurator;
use catechesis\PdoDatabaseManager;
use catechesis\DataValidationUtils;
use catechesis\Utils;


// ===========================================================================================
// =                                                                                         =
// =                          Funcoes de analise de candidatos                               =
// =                                                                                         =
// ===========================================================================================
// Recebem um objecto 'candidato' contendo um objeto PDO 'catequizando' e um array 'relatorio'.
// Recebem um nivel de gravidade do problema:
//   0 - ignorar
//   1 - informacao
//   2 - aviso
//   3 - erro fatal

    abstract class RELATORIO
	{
		const IGNORAR = 0;
		const INFO = 1;
		const AVISO = 2;
		const FATAL = 3;
	}


	// Reporta um problema de acordo com o nivel de gravidade indicado
	function reportar(&$candidato, $nivel, $problema)
	{
		switch($nivel)
		{
			case 0:			// Ignorar
				break;

			case 1:			// Informacao
				array_push($candidato['relatorio']['info'], $problema);
				break;

			case 2:			// Aviso
				array_push($candidato['relatorio']['avisos'], $problema);
				break;

			case 3:			// Erro fatal
				array_push($candidato['relatorio']['fatais'], $problema);
				break;
		}
	}





	//Retorna true se o cantequizando ja tem o baptismo
	function hasBaptismo(&$candidato, $nivel)
	{
		$catequizando = $candidato['catequizando'];
		if(isset($catequizando['paroquia_batismo']))
		{
			reportar($candidato, $nivel, "O catequizando já é baptizado.");
			return true;
		}
		else
		{
			reportar($candidato, $nivel, "O catequizando ainda não foi baptizado.");
			return false;
		}
	}

	//Retorna true se o cantequizando ja fez a primeira comunhao
	function hasPrimeiraComunhao(&$candidato, $nivel)
	{
		$catequizando = $candidato['catequizando'];
		if(isset($catequizando['paroquia_comunhao']))
		{
			reportar($candidato, $nivel, "O catequizando já fez a Primeira Comunhão.");
			return true;
		}
		else
		{
			reportar($candidato, $nivel, "O catequizando ainda não fez a Primeira Comunhão.");
			return false;
		}
	}

	//Retorna true se o cantequizando ja fez o crisma
	function hasCrisma(&$candidato, $nivel)
	{
		$catequizando = $candidato['catequizando'];
		if(isset($catequizando['paroquia_crisma']))
		{
			reportar($candidato, $nivel, "O catequizando já recebeu o Crisma.");
			return true;
		}
		else
			return false;
	}


	// Verifica se a data do batismo e' valida
	function dataBaptismoValida(&$candidato, $nivel)
	{
		$catequizando = $candidato['catequizando'];
		if(!isset($catequizando['data_baptismo']))
		{
			reportar($candidato, $nivel, "A data de baptismo não foi preenchida.");
			return false;
		}
		else if(!DataValidationUtils::validateDate(date("d-m-Y", strtotime($catequizando['data_baptismo']))))
		{
			reportar($candidato, $nivel, "A data de baptismo é inválida.");
			return false;
		}
		else if($catequizando['data_baptismo'] < $catequizando['data_nasc'])
		{
			reportar($candidato, $nivel, "A data de baptismo é anterior à data de nascimento.");
			return false;
		}
		

		return true;
	}


	// Verifica se a data da comunhao e' valida
	function dataComunhaoValida(&$candidato, $nivel)
	{
		$catequizando = $candidato['catequizando'];
		if(!isset($catequizando['data_comunhao']))
		{
			reportar($candidato, $nivel, "A data da Primeira Comunhão não foi preenchida.");
			return false;
		}
		else if(!DataValidationUtils::validateDate(date("d-m-Y", strtotime($catequizando['data_comunhao']))))
		{
			reportar($candidato, $nivel, "A data da Primeira Comunhão é inválida.");
			return false;
		}
		else if($catequizando['data_comunhao'] < $catequizando['data_nasc'])
		{
			reportar($candidato, $nivel, "A data da Primeira Comunhão é anterior à data de nascimento.");
			return false;
		}

		return true;
	}

	// Verifica se a data da comunhao e' valida
	function dataCrismaValida(&$candidato, $nivel)
	{
		$catequizando = $candidato['catequizando'];
		if(!isset($catequizando['data_crisma']))
		{
			reportar($candidato, $nivel, "A data do Crisma não foi preenchida.");
			return false;
		}
		else if(!DataValidationUtils::validateDate(date("d-m-Y", strtotime($catequizando['data_crisma']))))
		{
			reportar($candidato, $nivel, "A data do Crisma é inválida.");
			return false;
		}
		else if($catequizando['data_crisma'] < $catequizando['data_nasc'])
		{
			reportar($candidato, $nivel, "A data do Crisma é anterior à data de nascimento.");
			return false;
		}

		return true;
	}

	//Verifica se a paroquia de batismo esta preenchida
	function paroquiaBaptismoValida(&$candidato, $nivel)
	{
		$catequizando = $candidato['catequizando'];
		if(!isset($catequizando['paroquia_batismo']) || $catequizando['paroquia_batismo']=="" || strpos(strtolower($catequizando['paroquia_batismo']), "n.d.")!==false)
		{
			reportar($candidato, $nivel,  "A paróquia de baptismo não foi preenchida.");
			return false;
		}
		return true;
	}

	//Verifica se a paroquia da comunhao esta preenchida
	function paroquiaComunhaoValida(&$candidato, $nivel)
	{
		$catequizando = $candidato['catequizando'];
		if(!isset($catequizando['paroquia_comunhao']) || $catequizando['paroquia_comunhao']=="" || strpos(strtolower($catequizando['paroquia_comunhao']), "n.d.")!==false)
		{
			reportar($candidato, $nivel,  "A paróquia da Primeira Comunhão não foi preenchida.");
			return false;
		}
		return true;
	}

	//Verifica se a paroquia da comunhao esta preenchida
	function paroquiaCrismaValida(&$candidato, $nivel)
	{
		$catequizando = $candidato['catequizando'];
		if(!isset($catequizando['paroquia_crisma']) || $catequizando['paroquia_crisma']=="" || strpos(strtolower($catequizando['paroquia_crisma']), "n.d.")!==false)
		{
			reportar($candidato, $nivel,  "A paróquia do Crisma não foi preenchida.");
			return false;
		}
		return true;
	}

	//Retorna true se o catequizando tem o comprovativo de baptismo
	function hasComprovativoBaptismo(&$candidato, $nivel)
	{
		$catequizando = $candidato['catequizando'];
		if(!isset($catequizando['comprovativo_batismo']) || $catequizando['comprovativo_batismo']=="")
		{
			reportar($candidato, $nivel, "Comprovativo do baptismo em falta.");
			return false;
		}

		return true;
	}


	//Retorna true se o candidato tem um numero suficiente de inscricoes na catequese para receber o sacramento
	function hasNumMinimoInscricoes(&$candidato, $nivel)
	{
		$catequizando = $candidato['catequizando'];
		if(!isset($catequizando['inscricoes']) || intval($catequizando['inscricoes']) < 2)
		{
			reportar($candidato, $nivel, "Inscrito há menos de 2 anos na catequese.");
			return false;
		}
		return true;
	}


	// Retorna true se o catecismo do catequizando e' o 3º ou superior, para fazer a Primeira Comunhao
	function hasCatecismoMinimoComunhao(&$candidato, $nivel)
	{
		$catequizando = $candidato['catequizando'];
		if(!isset($catequizando['ano_catecismo']) || intval($catequizando['ano_catecismo']) < 3)
		{
			reportar($candidato, $nivel, "Ainda não alcançou o 3º catecismo.");
			return false;
		}
		return true;
	}


	// Retorna true se o catecismo do catequizando e' o 10º, para fazer o Crisma
	function hasCatecismoMinimoCrisma(&$candidato, $nivel)
	{
		$catequizando = $candidato['catequizando'];
		if(!isset($catequizando['ano_catecismo']) || intval($catequizando['ano_catecismo']) < 10)
		{
			reportar($candidato, $nivel, "Ainda não alcançou o 10º catecismo.");
			return false;
		}
		return true;
	}



	// Retorna true se existe um endereco de email na ficha
	function hasEmail(&$candidato, $nivel)
	{
		$catequizando = $candidato['catequizando'];
		if(!isset($catequizando['email']) || $catequizando['email'] == "")
		{
			reportar($candidato, $nivel, "Sem endereço de e-mail.");
			return false;
		}
		else
			return true;
	}

	// Retorna true se o endereco de email e' valido
	function emailValido(&$candidato, $nivel)
	{
		$catequizando = $candidato['catequizando'];
		if(!isset($catequizando['email']) || $catequizando['email'] == "")
		{
			reportar($candidato, $nivel, "Sem endereço de e-mail.");
			return false;
		}
		else if(!DataValidationUtils::validateEmail($catequizando['email']))
		{
			reportar($candidato, $nivel, "O endereço de e-mail inválido.");
			return false;
		}
		else
			return true;
	}


	// Retorna true se a ficha tem pelo menos um contacto telefonico (telefone ou telemovel)
	function hasContactoTelefonico(&$candidato, $nivel)
	{
		$catequizando = $candidato['catequizando'];
		if((!isset($catequizando['telefone']) || $catequizando['telefone'] == "") && (!isset($catequizando['telemovel']) || $catequizando['telemovel'] == ""))
		{
			reportar($candidato, $nivel, "Sem contacto telefónico.");
			return false;
		}
		else
			return true;
	}


	// Retorna true se os contactos telefonicos (telefone e/ou telemovel) que existirem forem todos validos
	function contactosTelefonicosValidos(&$candidato, $nivel)
	{
		$res = true;
		$catequizando = $candidato['catequizando'];

		if(isset($catequizando['telefone']) && $catequizando['telefone'] != "")
		{
			if(!DataValidationUtils::validatePhoneNumber($catequizando['telefone'], Configurator::getConfigurationValueOrDefault(Configurator::KEY_LOCALIZATION_CODE), true))
			{
				reportar($candidato, $nivel, "Número de telefone inválido.");
				$res = false;
			}
		}

		if(isset($catequizando['telemovel']) && $catequizando['telemovel'] != "")
		{
			if(!DataValidationUtils::validatePhoneNumber($catequizando['telemovel'], Configurator::getConfigurationValueOrDefault(Configurator::KEY_LOCALIZATION_CODE) , true))
			{
				reportar($candidato, $nivel, "Número de telemóvel inválido.");
				$res = false;
			}
		}

		return $res;
	}


	// Verifica se a data de nascimento e' valida
	function dataNascValida(&$candidato, $nivel)
	{
		$catequizando = $candidato['catequizando'];
		if(!isset($catequizando['data_nasc']))
		{
			reportar($candidato, $nivel, "A data de nascimento não foi preenchida.");
			return false;
		}
		else if(!DataValidationUtils::validateDate(date("d-m-Y", strtotime($catequizando['data_nasc']))))
		{
			reportar($candidato, $nivel, "A data de nascimento é inválida.");
			return false;
		}

		return true;
	}


	//Retorna true se o candidato tem pelo menos uma inscricao na catequese (para detectar catequizandos perdidos na base de dados)
	function hasInscricoes(&$candidato, $nivel)
	{
		$catequizando = $candidato['catequizando'];
		if(!isset($catequizando['inscricoes']) || intval($catequizando['inscricoes']) <= 0)
		{
			reportar($candidato, $nivel, "O catequizando nunca esteve inscrito em nenhum grupo de catequese.");
			return false;
		}
		return true;
	}


    /**
     * Runs the decision support analysis to produce a report of catechumens
     * files with inconsistent data.
     * @param int $ano_catequetico
     * @param int $catecismo
     * @param string $turma
     * @return array
     */
    function runDecisionSupportAnalysis(string $username, bool $isAdmin, int $ano_catequetico=null, int $catecismo=null, string $turma=null)
    {
        $db = new PdoDatabaseManager();

        $problemas = array();        // Lista de catequizandos com problemas
        $sem_problemas = array();    // Lista de catequizandos sem problemas
        $relatorio = false;          // Indica se existe uma lista de resultados para renderizar

        // Query database
        $result = null;
        try
        {
            $result = $db->getDataDumpForInconsistencyAnalysis($username, $isAdmin, Utils::currentCatecheticalYear(),
                                                                $ano_catequetico, $catecismo, $turma);
        }
        catch (Exception $e)
        {
            echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
            die();
        }

        if (!empty($result))
        {
            foreach ($result as $row)
            {
                $candidato['catequizando'] = $row;
                $candidato['relatorio'] = array();
                $candidato['relatorio']['info'] = array();
                $candidato['relatorio']['avisos'] = array();
                $candidato['relatorio']['fatais'] = array();
                $problema = false;


                if (hasBaptismo($candidato, RELATORIO::IGNORAR))
                {
                    $problema |= !dataBaptismoValida($candidato, RELATORIO::AVISO);
                    $problema |= !paroquiaBaptismoValida($candidato, RELATORIO::AVISO);
                    if (!hasPrimeiraComunhao($candidato, RELATORIO::IGNORAR))
                        $problema |= !hasComprovativoBaptismo($candidato, RELATORIO::INFO);
                }

                if (hasPrimeiraComunhao($candidato, RELATORIO::IGNORAR))
                {
                    $problema |= !dataComunhaoValida($candidato, RELATORIO::AVISO);
                    $problema |= !paroquiaComunhaoValida($candidato, RELATORIO::AVISO);
                }

                if (hasCrisma($candidato, RELATORIO::IGNORAR))
                {
                    $problema |= !dataCrismaValida($candidato, RELATORIO::AVISO);
                    $problema |= !paroquiaCrismaValida($candidato, RELATORIO::AVISO);

                }

                $problema |= !dataNascValida($candidato, RELATORIO::AVISO);

                if (hasEmail($candidato, RELATORIO::INFO))
                    $problema |= !emailValido($candidato, RELATORIO::AVISO);
                else
                    $problema |= true;


                if (hasContactoTelefonico($candidato, RELATORIO::FATAL))
                    $problema |= !contactosTelefonicosValidos($candidato, RELATORIO::FATAL);
                else
                    $problema |= true;

                $problema |= !hasInscricoes($candidato, RELATORIO::FATAL);


                if ($problema)
                    array_push($problemas, $candidato);
                else
                    array_push($sem_problemas, $candidato);
            }

            //Ordenar os resultados por gravidade dos problemas detetados
            usort($problemas, "sort_catechumens_by_severity");

            $relatorio = true;
        }
        else
        {
            $relatorio = false;
        }

        $db = null;
        return array($problemas, $sem_problemas, $relatorio);
    }


// ===========================================================================================
// =                                                                                         =
// =                          Funcoes auxiliares para gerar o relatorio                      =
// =                                                                                         =
// ===========================================================================================




	// Associa um numero ao nivel de gravidade dos problemas encontrados
	function severity_level($candidato)
	{
		if(sizeof($candidato['relatorio']['fatais']) > 0)
			return 3;
		else if(sizeof($candidato['relatorio']['avisos']) > 0)
			return 2;
		else if(sizeof($candidato['relatorio']['info']) > 0)
			return 1;
		else
			return 0;
	}



	// Ordena os resultados do relatorio por ordem de gravidade dos erros, depois por catecismo/turma, e por fim por nome
	function sort_catechumens_by_severity($a, $b)
	{
		$diff_gravidade = severity_level($b) - severity_level($a);
		if($diff_gravidade != 0)
			return $diff_gravidade;
		else if($a['catequizando']['ano_catecismo'] != $b['catequizando']['ano_catecismo'])
			return $a['catequizando']['ano_catecismo'] - $b['catequizando']['ano_catecismo'];
		else if($a['catequizando']['turma'] != $b['catequizando']['turma'])
			return strcmp($a['catequizando']['turma'], $b['catequizando']['turma']);
		else
			return strcmp($a['catequizando']['nome'], $b['catequizando']['nome']);
	}

?>