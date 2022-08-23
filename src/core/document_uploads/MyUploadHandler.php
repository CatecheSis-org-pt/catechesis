<?php

//error_reporting(E_ALL | E_STRICT);

require_once(__DIR__ . "/UploadHandler.php");
require_once(__DIR__ . "/../config/catechesis_config.inc.php");
require_once(__DIR__ . "/../../authentication/utils/authentication_verify.php");
require_once(__DIR__ . '/../../authentication/Authenticator.php');
require_once(__DIR__ . "/../Utils.php");
require_once(__DIR__ . "/../UserData.php");
require_once(__DIR__ . "/../log_functions.php"); //Para poder escrever no log
require_once(__DIR__ . "/../PdoDatabaseManager.php");
require_once(__DIR__ . "/../domain/Sacraments.php");
require_once(__DIR__ . "/../../core/catechist_belongings.php");

use catechesis\PdoDatabaseManager;
use catechesis\Authenticator;
use catechesis\Utils;
use catechesis\UserData;
use core\domain\Sacraments;


class CustomUploadHandler extends UploadHandler 
{

    protected function initialize() 
    {
        parent::initialize();
    }


    protected function handle_form_data($file, $index) 
    {
    	$file->cid = intval(Utils::sanitizeInput($_REQUEST['cid']));
    	$file->sacramento = Utils::sanitizeInput($_REQUEST['sacramento']);
    }


    private function check_file_is_valid_pdf($filename)
    {
        // Verificar que o ficheiro e' mesmo um PDF, usando magic numbers
        $file_info = new finfo(FILEINFO_MIME); 
        $mime_type = $file_info->file($filename); 
        if (strpos($mime_type, 'application/pdf') !== false)
        {
            return true;
        }
        else
        {
            //echo("Os dados submetidos não correspondem a um ficheiro PDF válido.");
            return false;
        }
    }

    protected function validate($uploaded_file, $file, $error, $index)
    {
        //echo("RUNNING VALIDATION");

        if(!parent::validate($uploaded_file, $file, $error, $index))
            return false;
        else if( !$this->check_file_is_valid_pdf($uploaded_file) )
        {
            $file->error = "Os dados submetidos não correspondem a um ficheiro PDF válido.";
            return false;
        }

        return true;
    }

    protected function handle_file_upload($uploaded_file, $name, $size, $type, $error,
            $index = null, $content_range = null) 
    {
        $cid = intval(Utils::sanitizeInput($_REQUEST['cid']));
        $sacrament = Utils::sanitizeInput($_REQUEST['sacramento']);

        $sacramentType = Sacraments::sacramentFromString($sacrament);
        $sacramentExtName = Sacraments::toExternalString($sacramentType);

        if(is_null($sacramentType))
        {
            $file = new \stdClass();
            $file->error = "Sacramento não reconhecido.";
            return $file;
        }

        // Check permissions
        if(!Authenticator::isAdmin() && !catechumen_belongs_to_catechist($cid, Authenticator::getUsername()))
        {
            $file = new \stdClass();
            $file->error = "Não tem permissões para carregar documentos.";
            return $file;
        }

        $myFileName = $sacrament . '_' . $cid . '.pdf';

        $file = parent::handle_file_upload(
        	$uploaded_file, $myFileName /*$name*/, $size, $type, $error, $index, $content_range
        );


        /*if( !$this->check_file_is_valid_pdf($this->options['upload_dir'] . '/' . $file->name))
        {
            $file->error = "Os dados carregados não correspondem a um ficheiro PDF válido.";
            unlink($this->options['upload_dir'] . '/' . $file->name);   //Eliminar ficheiro
            return $file;
        }*/

        if (empty($file->error)) 
        {
            $db = new PdoDatabaseManager();

            try
            {
                if($db->setSacramentProofDocument($file->cid, $sacramentType, $file->name))
                    catechumenArchiveLog($cid, "Carregado comprovativo de " . $sacramentExtName . " do catequizando com id=" . $cid);
                else
                    $file->error = "Erro ao registar ficheiro na base de dados.";
            }
            catch(Exception $e)
            {
                $file->error = "Erro ao registar ficheiro na base de dados. " . $e->getMessage();
            }

            $db = null;
        }

        return $file;
    }


    //Override da funcao original para NAO permitir multiplos ficheiros com o mesmo nome
    protected function get_file_name($file_path, $name, $size, $type, $error,
            $index, $content_range) 
    {
        $name = $this->trim_file_name($file_path, $name, $size, $type, $error,
            $index, $content_range);

        return $name;
    }


    protected function set_additional_file_properties($file) 
    {
        //parent::set_additional_file_properties($file); --> Nao chamar isto, para nao permitir DELETE nem outras operacoes

        /*
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        	$sql = 'SELECT `id`, `type`, `title`, `description` FROM `'
        		.$this->options['db_table'].'` WHERE `name`=?';
        	$query = $this->db->prepare($sql);
 	        $query->bind_param('s', $file->name);
	        $query->execute();
	        $query->bind_result(
	        	$id,
	        	$type,
	        	$title,
	        	$description
	        );
	        while ($query->fetch()) {
	        	$file->id = $id;
        		$file->type = $type;
        		$file->title = $title;
        		$file->description = $description;
    		}
        }
        */
    }

    public function delete($print_response = true) {
        $response = parent::delete(false);

        /*
        foreach ($response as $name => $deleted) {
        	if ($deleted) {
	        	$sql = 'DELETE FROM `'
	        		.$this->options['db_table'].'` WHERE `name`=?';
	        	$query = $this->db->prepare($sql);
	 	        $query->bind_param('s', $name);
		        $query->execute();
        	}
        } 
        */
        return $this->generate_response($response, $print_response);
    }


    protected function get_download_url($file_name, $version = null, $direct = false)
    {
        //Override desta funcao e nao fazer nada, para nao responder ao cliente o caminho onde os ficheiros sao guardados
    }
}


?>