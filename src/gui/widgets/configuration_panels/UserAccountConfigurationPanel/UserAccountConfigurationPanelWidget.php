<?php

namespace catechesis\gui;

require_once(__DIR__ . '/../AbstractSettingsPanel/AbstractSettingsPanelWidget.php');
require_once(__DIR__ . '/../../../../authentication/Authenticator.php');
require_once(__DIR__ . '/../../../../core/PdoDatabaseManager.php');
require_once(__DIR__ . '/../../../../core/Utils.php');

use catechesis\Authenticator;
use catechesis\PdoDatabaseManager;
use catechesis\Utils;
use uLogin;


/**
 * Panel containing the account settings for the authenticated user.
 * Allows to change the name, e-mail, phone and password.
 */
class UserAccountConfigurationPanelWidget extends AbstractSettingsPanelWidget
{
    public static $ACTION_PARAMETER = "edit_user_account_details";
    private /*bool*/ $allowChangingOtherUsers = false;              //Allow POSTs that change user accounts other than the logged in user
    private /*string*/ $userAccount = null;                         //Username to edit with this widget


    public function __construct(string $id = null)
    {
        parent::__construct($id, false);

        parent::setTitle("Dados da conta");
        //parent::setURL($_SERVER['PHP_SELF']);
        parent::useHeaderButtons();

        $this->userAccount = Authenticator::getUsername();    // By default, this widget changes the currently logged in user
    }



    /**
     * Sets whether this widget accepts changing details of a user account other than the logged in user.
     * @param bool $allowChangingOtherUsers
     * @return UserAccountConfigurationPanelWidget
     */
    public function allowChangingOtherUsers(bool $allowChangingOtherUsers = true)
    {
        $this->allowChangingOtherUsers = (Authenticator::isAdmin()? $allowChangingOtherUsers : false);      //Only administrators can change other user accounts
        return $this;
    }


    /**
     * Sets the username of the account that will be edited with this widget.
     * @param string $username
     * @return UserAccountConfigurationPanelWidget
     */
    public function setUsername(string $username)
    {
        if($username != Authenticator::getUsername() && $this->allowChangingOtherUsers)
            $this->userAccount = $username;
        else
            $this->userAccount = Authenticator::getUsername();
        return $this;
    }


    protected function renderBody()
    {
        $db = new PdoDatabaseManager();

        //Obter dados do utilizador
        try
        {
            $ed_username = $this->userAccount;

            $userContacts = $db->getUserAccountDetails($ed_username);
            $userStatus = $db->getUserAccountStatus($ed_username);

            $ed_full_name = Utils::sanitizeOutput($userContacts['name']);
            $ed_tel = Utils::sanitizeOutput($userContacts['phone']);
            $ed_email = Utils::sanitizeOutput($userContacts['email']);
            $isAdmin = $userStatus["admin"];
            $isActiveCatechist = $userStatus["catechist_status"]==1;
            $isInactiveCatechist = $userStatus["catechist_status"]==0;

        }
        catch(\Exception $e)
        {
            echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
            die();
        }

        $db = null;
        ?>
            <div class="form-group">
                <div class="col-xs-6">
                  <label for="username">Username:</label>
                  <span><?= $ed_username ?></span>
                </div>
                <div class="col-xs-6">
                  <label for="attributes">Atributos:</label>
                  <span><?php if($isAdmin) echo('<span class="label label-primary">Administrador</span> ');  if($isActiveCatechist) echo('<span class="label label-success">Catequista ativo</span>'); else if($isInactiveCatechist) echo('<span class="label label-default">Catequista inactivo</span>'); ?></span>
                </div>
            </div>

            <div class="row clearfix" style="margin-bottom:20px; "></div>

            <!--nome-->
            <div class="form-group">
                <div class="col-xs-6">
                  <label for="nome">Nome:</label>
                  <input type="text" class="form-control" id="<?=$this->getID()?>_nome" name="nome" placeholder="Nome do catequista" style="cursor: auto;" value="<?= $ed_full_name ?>" required readonly>
                  <input type="hidden" class="form-control" id="<?=$this->getID()?>_nome_backup" value="<?= $ed_full_name ?>" readonly>
                </div>
            </div>

            <!--email-->
            <div class="form-group">
                <div class="col-xs-4">
                  <label for="nome">E-mail:</label>
                  <input type="email" class="form-control" id="<?=$this->getID()?>_email" name="email" placeholder="ex: endereco@someserver.com" style="cursor: auto;" value="<?= $ed_email ?>" readonly>
                  <input type="hidden" class="form-control" id="<?=$this->getID()?>_email_backup" value="<?= $ed_email ?>" readonly>
                </div>
            </div>

            <!--telefone-->
            <div class="col-xs-2">
              <label for="tel">Telefone:</label>
              <input type="tel" class="form-control" id="<?=$this->getID()?>_telefone" name="telefone" placeholder="Telefone" style="cursor: auto;" value="<?= $ed_tel ?>" readonly>
              <input type="hidden" class="form-control" id="<?=$this->getID()?>_telefone_backup" value="<?= $ed_tel ?>" readonly>
            </div>

            <div class="row" style="margin-bottom:20px; "></div>

            <div class="col-xs-12">
                <a style="cursor: pointer;" data-toggle="collapse" data-target="#<?=$this->getID()?>_nova-password" onclick="on_edit_<?= $this->getID() ?>()" >Modificar palavra-passe <span class="glyphicon glyphicon-chevron-down"></span></a>
                <div style="margin-bottom: 10px"></div>

                <div id="<?=$this->getID()?>_nova-password" class="collapse">
                    <!--password1-->
                    <div class="form-group">
                        <div class="col-xs-4">
                          <label for="nome">Nova palavra-passe:</label>
                          <input type="password" class="form-control" id="<?=$this->getID()?>_password1" name="password1" onchange="valida_password_<?=$this->getID()?>()" readonly>
                        </div>
                    </div>

                    <div class="col-xs-8">
                        <label for="erro">&nbsp;<br>&nbsp;</label>
                        <span class="text-danger" id="<?=$this->getID()?>_erro_p1" style="display: none;"><span class="glyphicon glyphicon-remove"></span> A palavra-passe é inválida. Deve conter letras e dígitos e não deve ser inferior a 10 caracteres.</span>
                    </div>

                    <div class="clearfix"></div>

                    <!--password2-->
                    <div class="form-group">
                        <div class="col-xs-4">
                          <label for="nome">Confirmar nova palavra-passe:</label>
                          <input type="password" class="form-control" id="<?=$this->getID()?>_password2" name="password2" onchange="testa_passwords_<?=$this->getID()?>()" readonly>
                        </div>
                    </div>

                    <div class="col-xs-8">
                        <label for="erro">&nbsp;<br>&nbsp;</label>
                        <span class="text-danger" id="<?=$this->getID()?>_erro_p2" style="display: none;"><span class="glyphicon glyphicon-remove"></span> As palavras-passe não coincidem.</span>
                    </div>

                    <div class="clearfix"></div>
                </div>

                <input type="hidden" name="action" value="<?= self::$ACTION_PARAMETER ?>">
            <?php
            if($this->allowChangingOtherUsers)
            {?>
                <input type="hidden" name="un" value="<?= $ed_username ?>">
            <?php
            }
            ?>
            </div>
    <?php
    }


    /**
     * @inherit
     */
    protected function onEdit()
    {?>
        document.getElementById("<?=$this->getID()?>_nome").readOnly = false;
        document.getElementById("<?=$this->getID()?>_telefone").readOnly = false;
        document.getElementById("<?=$this->getID()?>_email").readOnly = false;
        document.getElementById("<?=$this->getID()?>_password1").readOnly = false;
        document.getElementById("<?=$this->getID()?>_password2").readOnly = false;
       <?php
    }


    /**
     * @inherit
     */
    protected function onCancel()
    {
        ?>
        document.getElementById("<?=$this->getID()?>_nome").readOnly = true;
        document.getElementById("<?=$this->getID()?>_telefone").readOnly = true;
        document.getElementById("<?=$this->getID()?>_email").readOnly = true;
        document.getElementById("<?=$this->getID()?>_password1").readOnly = true;
        document.getElementById("<?=$this->getID()?>_password2").readOnly = true;
        document.getElementById("<?=$this->getID()?>_email").readOnly = true;
        document.getElementById("<?=$this->getID()?>_nome").value = document.getElementById("<?=$this->getID()?>_nome_backup").value;
        document.getElementById("<?=$this->getID()?>_telefone").value = document.getElementById("<?=$this->getID()?>_telefone_backup").value;
        document.getElementById("<?=$this->getID()?>_email").value = document.getElementById("<?=$this->getID()?>_email_backup").value;
        document.getElementById("<?=$this->getID()?>_password1").value = "";
        document.getElementById("<?=$this->getID()?>_password2").value = "";
        <?php
    }


    /**
     * @inherit
     */
    protected function onSubmit()
    {
        ?>
        if(!valida_password_<?=$this->getID()?>())
        {
            alert("A palavra-passe é inválida. Deve conter letras e dígitos e não deve ser inferior a 10 caracteres. Deixe esse campo em branco se não pretender modificar a palavra-passe.");
            return false;
        }
        else if(!testa_passwords_<?=$this->getID()?>())
        {
            alert("As palavras-passe não coincidem. Deixe esses campos em branco se não pretender modificar a palavra-passe.");
            return false;
        }
        else
            return true;
        <?php
    }


    /**
     * @inherit
     */
    public function renderJS()
    {
        parent::renderJS();
        ?>
        <script type="text/javascript">
            function valida_password_<?=$this->getID()?>()
            {
                var letterNumber = /^(?=.*[a-zA-Z])(?=.*[0-9])/;
                var p1 = document.getElementById('<?=$this->getID()?>_password1').value;

                if((p1==="" || p1===undefined) || (p1.length >= 10 && (p1.match(letterNumber))))
                {
                    document.getElementById('<?=$this->getID()?>_erro_p1').style.display = "none";
                    return true;
                }
                else
                {
                    document.getElementById('<?=$this->getID()?>_erro_p1').style.display = "inline";
                    return false;
                }
            }


            function testa_passwords_<?=$this->getID()?>()
            {
                var p1 = document.getElementById('<?=$this->getID()?>_password1').value;
                var p2 = document.getElementById('<?=$this->getID()?>_password2').value;

                if(p1!==p2)
                {
                    document.getElementById('<?=$this->getID()?>_erro_p2').style.display = "inline";
                    return false;
                }
                else
                {
                    document.getElementById('<?=$this->getID()?>_erro_p2').style.display = "none";
                    return true;
                }
            }
        </script>
        <?php
    }

    /**
     * @inheritDoc
     */
    public function handlePost()
    {
        $db = new PdoDatabaseManager();

        // Start a secure session if none is running
        Authenticator::startSecureSession();

        $ulogin = new uLogin('catechesis\Authenticator::appLogin', 'catechesis\Authenticator::appLoginFail');

        $action = Utils::sanitizeInput($_POST['action']);

        //Editar dados da conta
        if($action == self::$ACTION_PARAMETER)
        {
            if($this->allowChangingOtherUsers)
                $editar_username = Utils::sanitizeInput($_POST['un']);
            else
                $editar_username = Authenticator::getUsername();
            $editar_nome = Utils::sanitizeInput($_POST['nome']);
            $editar_tel = intval($_POST['telefone']);
            $editar_email = Utils::sanitizeInput($_POST['email']);
            $editar_password = Utils::sanitizeInput($_POST['password1']);

            try
            {
                if($db->updateUserAccountDetails($editar_username, $editar_nome, $editar_tel, $editar_email))
                {
                    writeLogEntry("Dados da conta do utilizador " . $editar_username . " actualizados.");
                    echo("<div class=\"alert alert-success\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Sucesso!</strong> Os dados da " . ($editar_username == Authenticator::getUsername()?"sua conta":("conta " . $editar_username)) . " foram actualizados.</div>");
                    $_SESSION['nome_utilizador'] = $editar_nome;
                }
                else
                {
                    echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Falha ao atualizar dados da conta.</div>");
                    die();
                }
            }
            catch(\Exception $e)
            {
                //echo $e->getMessage();
                echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
                die();
            }


            if($editar_password && $editar_password!="")
            {
                if(!($ulogin->SetPassword($ulogin->Uid($editar_username), $editar_password)))
                {
                    echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> Falha ao modificar palavra-passe da sua conta.</div>");
                }
                else
                {
                    writeLogEntry("Palavra-passe do utilizador " . $editar_username . " modificada.");
                    echo("<div class=\"alert alert-success\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Sucesso!</strong> Palavra-passe da sua conta actualizada. Terá de a utilizar na próxima vez que se autenticar.</div>");
                }
            }
        }

        $db = null;
    }
}