<?php

namespace catechesis\gui;

require_once(__DIR__ . '/../../../core/Configurator.php');
require_once(__DIR__ . '/../../../core/PdoDatabaseManager.php');
require_once(__DIR__ . '/../../../authentication/Authenticator.php');
require_once(__DIR__ . '/../../../core/Utils.php');
require_once(__DIR__ . '/../AboutDialog/AboutDialogWidget.php');
require_once(__DIR__ . '/../UpdateDialog/UpdateDialogWidget.php');

namespace catechesis\gui\MainNavbar;
abstract class MENU_OPTION
{
    const NONE = -1;
    const HOME = 0;
    const ENROLMENTS = 1;
    const CATECHUMENS = 2;
    const CATECHESIS = 3;
    const SACRAMENTS = 4;
    const ANALYSIS = 5;
    const SETTINGS = 6;
};



namespace catechesis\gui;
use catechesis\gui\MainNavbar\MENU_OPTION;
use catechesis\Configurator;
use catechesis\Authenticator;
use catechesis\PdoDatabaseManager;
use catechesis\Utils;
use catechesis\gui\ModalDialogWidget;

/**
 * Renders the main CatecheSis navbar.
 */
class MainNavbar extends Widget
{
    private /*int*/                 $menuOption = MENU_OPTION::NONE;        // The menu tab where the invoking page belongs. One of the constants in MainNavbar::MENU_OPTION
    private /*bool*/                $allowsSiblingEnrollment = False;       // Whether the option 'enroll a sibling' should be enabled in invoking page
    private /*AboutDialogWidget*/   $aboutDialog = null;                    // The dialog window about CatecheSis
    private /*UpdateDialogWidget*/  $updateDialog = null;                   // The update notification window


    public function __construct(string $id = null, int $menuOption, bool $allowsSiblingEnrollment=False)
    {
        parent::__construct($id);

        // Declare this widget's dependencies
        $this->addCSSDependency('css/bootstrap.min.css');
        $this->addCSSDependency('css/custom-navbar-colors.css'); //FIXME Remove this when migrating to Bootstrap 5
        $this->addCSSDependency('font-awesome/fontawesome-free-5.15.1-web/css/all.min.css');
        $this->addCSSDependency('gui/widgets/Navbar/MainNavbar.css');
        $this->addCSSDependency('css/animate.min.css');

        $this->addJSDependency('js/jquery.min.js');
        $this->addJSDependency('js/bootstrap.min.js');

        // Add the Modal dependencies
        $this->aboutDialog = new AboutDialogWidget("sobre");
        foreach($this->aboutDialog->getCSSDependencies() as $path)
            $this->addCSSDependency($path);
        foreach($this->aboutDialog->getJSDependencies() as $path)
            $this->addJSDependency($path);

        $this->updateDialog = new UpdateDialogWidget("updater");
        foreach($this->updateDialog->getCSSDependencies() as $path)
            $this->addCSSDependency($path);
        foreach($this->updateDialog->getJSDependencies() as $path)
            $this->addJSDependency($path);

        $this->menuOption = $menuOption;
        $this->allowsSiblingEnrollment = $allowsSiblingEnrollment;
    }


    /**
     * Render aditional CSS code, to define the favicon and other metadata for all pages using this navbar.
     * @return void
     */
    public function renderCSS()
    {
        ?>
        <link rel="shortcut icon" href="img/favicon.png" type="image/png">
        <link rel="icon" href="img/favicon.png" type="image/png">
        <?php
    }

    public function renderJS()
    {
        $this->aboutDialog->renderJS();
        $this->updateDialog->renderJS();
        ?>
        <script>
        $(function() {
            $('[data-toggle="popover"]').popover({
                html: true,
                content: function() {
                    return $('#popover-content').html();
                }
            });
        });
        </script>
        <?php
    }

    /**
     * @inheritDoc
     */
    public function renderHTML()
    {
        // Start a secure session if none is running
        Authenticator::startSecureSession();

        $db = new PdoDatabaseManager();

        //Get number of pending enrollments
        $pendingEnrollments = $db->getNumberOfPendingEnrollments();
        $pendingRenewals = $db->getNumberOfPendingRenewals();
        $totalPendingEnrollmentsAndRenewals = $pendingEnrollments + $pendingRenewals;

        $db = null;
        ?>
        <nav class="navbar navbar-default navbar-fixed-top">
            <div class="container-fluid">
                <div class="navbar-header">
                    <a class="navbar-brand" href="dashboard.php"><img src="img/CatecheSis_Logo_Navbar.svg" class="img-responsive" style="height:200%; margin-top: -7%;"></a>
                </div>
                <div>
                    <ul class="nav navbar-nav">
                        <!-- Dashboard -->
                        <li<?php if($this->menuOption==MENU_OPTION::HOME) echo(' class="active"'); ?>><a href="dashboard.php"><i class="fas fa-home"></i> </a></li>

                        <?php
                        if(Authenticator::isAdmin())
                        {
                            ?>
                            <!-- Enrollments -->
                            <li class="dropdown<?php if($this->menuOption==MENU_OPTION::ENROLMENTS) echo(', active'); ?>"><a class="dropdown-toggle" data-toggle="dropdown" href="#"><i class="fas fa-signature"></i> Inscrições <?php if($totalPendingEnrollmentsAndRenewals > 0) echo("<span class=\"badge\">" . $totalPendingEnrollmentsAndRenewals . "</span> ");?><span class="caret"></span></a>
                                <ul class="dropdown-menu">
                                    <li role="presentation" class="dropdown-header"><i class="fas fa-edit"></i> Inscrições e renovações</li>
                                    <li><a href="inscricao.php">Inscrever novo catequizando</a></li>
                                    <li class="<?php if(!$this->allowsSiblingEnrollment) echo('disabled'); ?>"><a href="<?php if($this->allowsSiblingEnrollment) echo('inscricao.php?modo=irmao'); ?>">Inscrever um irmão deste catequizando</a></li>
                                    <li><a href="renovacaoMatriculas.php">Renovar matrículas <?php if($pendingRenewals > 0) echo("<span class=\"badge\">" . $pendingRenewals . "</span>");?></a></li>
                                    <li class="divider"></li>
                                    <li role="presentation" class="dropdown-header"><i class="fas fa-globe-europe"></i> Inscrições online</li>
                                    <li><a href="processarInscricoesOnline.php">Processar pedidos de inscrição online <?php if($pendingEnrollments > 0) echo("<span class=\"badge\">" . $pendingEnrollments . "</span>");?></a></li>
                                </ul>
                            </li>
                        <?php
                        }
                        ?>

                        <!-- Catechumens -->
                        <li class="dropdown<?php if($this->menuOption==MENU_OPTION::CATECHUMENS) echo(', active'); ?>"><a class="dropdown-toggle" data-toggle="dropdown" href="#"><i class="fas fa-search"></i> Catequizandos<span class="caret"></span></a>
                            <ul class="dropdown-menu">
                                <li role="presentation" class="dropdown-header"><i class="fas fa-list"></i> Listar catequizandos</li>
                                <li><a href="meusCatequizandos.php">Os meus catequizandos </a></li>
                                <li><a href="listarEscuteiros.php">Listar escuteiros </a></li>
                                <li class="divider"></li>
                                <li role="presentation" class="dropdown-header"><i class="fas fa-search"></i> Procurar um catequizando</li>
                                <li><a href="pesquisarNome.php">Por nome/data de nascimento</a></li>
                                <li><a href="pesquisarAno.php">Por ano/catecismo </a></li>
                                <li><a href="pesquisarCatequista.php">Por catequista </a></li>
                            </ul>
                        </li>

                        <!-- Catechesis -->
                        <li class="dropdown<?php if($this->menuOption==MENU_OPTION::CATECHESIS) echo(', active'); ?>"><a class="dropdown-toggle" data-toggle="dropdown" href="#"><i class="fas fa-bible"></i> Catequese<span class="caret"></span></a>
                            <ul class="dropdown-menu">
                                <li role="presentation" class="dropdown-header"><i class="fas fa-user-graduate"></i> Tarefas do catequista</li>
                                <li><a href="gerarFolhasPresencas.php">Reprografia</a></li>
                                <li><a href="aproveitamento.php">Aproveitamento dos catequizandos</a></li>
                                <li class="divider"></li>
                                <?php
                                if(Authenticator::isAdmin())
                                {
                                    ?>
                                    <li role="presentation" class="dropdown-header"><i class="fas fa-users-cog"></i> Gestão da catequese</li>
                                    <li><a href="gerirGrupos.php">Gerir grupos de catequese</a></li>
                                    <li><a href="gerirUtilizadores.php">Gerir utilizadores e catequistas</a></li>
                                    <li class="divider"></li>
                                    <?php
                                } ?>
                                <li role="presentation" class="dropdown-header"><i class="far fa-newspaper"></i> Catequese virtual</li>
                                <li><a href="criarCatequeseVirtual.php"> Criar catequese virtual</a></li>
                            </ul>
                        </li>

                        <!-- Sacraments -->
                        <li class="dropdown<?php if($this->menuOption==MENU_OPTION::SACRAMENTS) echo(', active'); ?>"><a class="dropdown-toggle" data-toggle="dropdown" href="#"><i class="fas fa-dove"></i> Sacramentos<span class="caret"></span></a>
                            <ul class="dropdown-menu">
                                <li role="presentation" class="dropdown-header"><i class="fas fa-chart-line"></i> Apoio à decisão</li>
                                <li><a href="analisarBaptismos.php">Baptismos</a></li>
                                <li><a href="analisarComunhoes.php">Primeiras Comunhões</a></li>
                                <li><a href="analisarCrismas.php">Crismas</a></li>
                                <?php
                                if(Authenticator::isAdmin())
                                {
                                ?>
                                    <li class="divider"></li>
                                    <li role="presentation" class="dropdown-header"><i class="fas fa-list"></i> Listar sacramentos</li>
                                    <li><a href="listarBaptismos.php">Listar Baptismos </a></li>
                                    <li><a href="listarComunhoes.php">Listar Primeiras Comunhões </a></li>
                                    <li><a href="listarProfissoesFe.php">Listar Profissões de Fé </a></li>
                                    <li><a href="listarConfirmacoes.php">Listar Crismas </a></li>
                                <?php
                                }
                                ?>
                                <li class="divider"></li>
                                <li role="presentation" class="dropdown-header"><i class="fas fa-pen-fancy"></i> Registo</li>
                                <li><a href="registarSacramentos.php">Registar sacramentos</a></li>
                            </ul>
                        </li>

                        <!-- Analysis -->
                        <li class="dropdown<?php if($this->menuOption==MENU_OPTION::ANALYSIS) echo(', active'); ?>"><a class="dropdown-toggle" data-toggle="dropdown" href="#"><i class="fas fa-chart-line"></i> Análise<span class="caret"></span></a>
                            <ul class="dropdown-menu">
                                <li role="presentation" class="dropdown-header"><i class="fas fa-chart-pie"></i> Estatísticas</li>
                                <li><a href="estatisticaNumCatequizandos.php">Número de catequizandos por catequista</a></li>
                                <li><a href="estatisticaDesistencias.php">Desistências</a></li>
                                <li><a href="estatisticaPercursosCompletos.php">Percursos catequéticos completos</a></li>
                                <li><a href="estatisticaResidentes.php">Catequizandos residentes na paróquia</a></li>
                                <li class="divider"></li>
                                <li role="presentation" class="dropdown-header"><i class="fas fa-database"></i> Dados</li>
                                <li><a href="dadosInconsistentes.php">Dados inconsistentes</a></li>
                                <?php
                                if(Authenticator::isAdmin())
                                {
                                ?>
                                    <li><a href="logCatechesis.php">Registos de actividade do sistema</a></li>
                                <?php
                                }
                                ?>
                            </ul>
                        </li>
                    </ul>

                    <!-- Right area -->
                    <ul class="nav navbar-nav navbar-right">

                        <?php
                        if($_SESSION['IS_UPDATE_AVAILABLE'])
                        {
                        ?>
                        <!-- Updates available -->
                        <li class="dropdown"><a href="#" data-toggle="modal" data-target="#updater" href="#"><i class="fas fa-cloud-download-alt animated animate__animated animate__heartBeat"></i></a>
                        </li>
                        <?php
                        }
                        ?>

                        <!-- Settings -->
                        <li <?php if($this->menuOption==MENU_OPTION::SETTINGS) echo(' class="active"'); ?>><a href="configuracoes.php"><i class="fas fa-cog"></i></a></li>

                        <!-- Help -->
                        <li class="dropdown"><a class="dropdown-toggle" data-toggle="dropdown" href="#"><i class="fas fa-question-circle"></i></a>
                            <ul class="dropdown-menu">
                                <li role="presentation" class="dropdown-header"><i class="fas fa-question-circle"></i> Ajuda</li>
                                <li><a href="#" onclick='window.open("help/user_manual/manual_do_utilizador.html", "", "width=500, height=700, left=800, top=100, status=no, menubar=no, toolbar=no, location=no, scrollbars=1").focus();'>Manual do utilizador</a></li>
                                <li><a href="#" onclick='window.open("https://discord.gg/MZtmerHdUj", "", "").focus();'>Comunidade no <i class="fab fa-discord"></i> Discord</a></li>
                                <li class="divider"></li>
                                <li role="presentation" class="dropdown-header"><i class="fas fa-info-circle"></i> Info da versão</li>
                                <li><a href="#" data-toggle="modal" data-target="#sobre">Acerca do CatecheSis...</a></li>
                            </ul>
                        </li>

                        <!-- User account menu --->
                        <div class="navbar-header">
                            <li class="dropdown"><a class="dropdown-toggle" data-toggle="dropdown" href="#"><!--<img src="" class="img-circle">--><div data-initials="<?= Utils::userInitials(Authenticator::getUserFullName())?>"></div></a>
                                <ul class="dropdown-menu">
                                    <li role="presentation" class="dropdown-header"><i class="fas fa-user"></i> <?= strip_tags(Authenticator::getUserFullName()); ?></li>
                                    <li><a href="login.php?logout=true"><i class="fas fa-sign-out-alt"></i> Terminar sessão</a></li>
                                </ul>
                            </li>
                        </div>
                    </ul>

                </div>
            </div>
        </nav>

        <div style="margin-bottom: 100px"></div>
        <?php

        $this->aboutDialog->renderHTML();

        if($_SESSION['IS_UPDATE_AVAILABLE'])
        {
            $this->updateDialog->renderHTML();
        }
    }
}