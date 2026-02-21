<?php

require_once(__DIR__ . '/core/config/catechesis_config.inc.php');
require_once(__DIR__ . '/authentication/utils/authentication_verify.php');
require_once(__DIR__ . '/authentication/Authenticator.php');
require_once(__DIR__ . '/core/Configurator.php');
require_once(__DIR__ . '/core/Utils.php');
require_once(__DIR__ . '/core/UserData.php');
require_once(__DIR__ . '/core/DataValidationUtils.php');
require_once(__DIR__ . '/core/catechist_belongings.php');
require_once(__DIR__ . '/core/domain/WeekDay.php');
require_once(__DIR__ . "/core/PdoDatabaseManager.php");
require_once(__DIR__ . '/gui/widgets/WidgetManager.php');
require_once(__DIR__ . '/gui/widgets/Navbar/MainNavbar.php');
require_once(__DIR__ . '/gui/widgets/ModalDialog/ModalDialogWidget.php');
require_once(__DIR__ . '/gui/common/Button.php');
require_once(__DIR__ . '/core/log_functions.php');

use catechesis\DataValidationUtils;
use catechesis\Authenticator;
use catechesis\Configurator;
use catechesis\PdoDatabaseManager;
use catechesis\UserData;
use catechesis\Utils;
use core\domain\WeekDay;
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

$confirmDeleteDialog = new ModalDialogWidget("confirmarEliminarSessao");
$pageUI->addWidget($confirmDeleteDialog);

?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <title>Marcar Presenças</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php $pageUI->renderCSS(); ?>
  <link rel="stylesheet" href="css/custom-navbar-colors.css">
  <link rel="stylesheet" href="css/bootstrap-datepicker-1.9.0-dist/bootstrap-datepicker3.min.css">
  <link rel="stylesheet" href="font-awesome/fontawesome-free-5.15.1-web/css/all.min.css">
  <link rel="stylesheet" href="css/bootoast-1.0.1/bootoast.min.css">
  <link rel="stylesheet" href="css/bootstrap-switch.css">

  <style>
  	@media print
	{    
	    .no-print, .no-print *
	    {
		display: none !important;
	    }

        @page {
            size: portrait;
        }

        body {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
	    
	    a[href]:after {
		    content: none;
		  }
	}
	
	@media screen
	{
		.only-print, .only-print *
		{
			display: none !important;
		}
	}

    .rowlink {
        cursor: pointer;
    }

    .highlighted-session-date {
        background-color: #dff0d8 !important;
        border-radius: 4px;
        color: #3c763d !important;
        font-weight: bold;
    }
    .highlighted-session-date:hover {
        background-color: #d0e9c6 !important;
    }

    .highlighted-weekday {
        background-color: #fcf8e3 !important;
        border-radius: 4px;
        color: #8a6d3b !important;
    }
    .highlighted-weekday:hover {
        background-color: #faf2cc !important;
    }
  </style>
</head>
<body>

<?php
$menu->renderHTML();
?>

<div class="container" id="contentor">

    <?php

    $db = new PdoDatabaseManager();

    // Get input parameters
    $data_sessao = NULL;
    $catecismo = NULL;
    $turma = NULL;

    if(isset($_POST['catecismo']))
    {
        $catecismo = intval($_POST['catecismo']);
    }
    if(isset($_POST['turma']))
    {
        $turma = Utils::sanitizeInput($_POST['turma']);
    }
    if(isset($_POST['data_sessao']))
    {
        $data_sessao = $_POST['data_sessao'];
    }

    $catechistGroups = $db->getCatechistGroups(Authenticator::getUsername(), Utils::currentCatecheticalYear());

    // Set defaults if not provided
    if(!isset($catecismo) || !($catecismo >= 1 && $catecismo <= intval(Configurator::getConfigurationValueOrDefault(Configurator::KEY_NUM_CATECHISMS))))
    {
        if(isset($catechistGroups) && count($catechistGroups) >= 1)
            $catecismo = $catechistGroups[0]["ano_catecismo"];
        else
            $catecismo = 1;
    }
    if(!isset($turma))
    {
        if(isset($catechistGroups) && count($catechistGroups) >= 1)
            $turma = $catechistGroups[0]["turma"];
        else
            $turma = 'A';
    }
    if(!$data_sessao || !DataValidationUtils::validateDate($data_sessao))
    {
        $currentCatecheticalYear = Utils::currentCatecheticalYear();
        $effectiveWeekDay = Utils::getEffectiveWeekDay($currentCatecheticalYear, $catecismo, $turma);
        $todayWeekDay = intval(date('w')); // 0 (Sunday) to 6 (Saturday)

        if($todayWeekDay == $effectiveWeekDay)
            $data_sessao = date('d-m-Y');
        else
        {
            // Find the last date that was coincident with the week day in which that group usually has lessons
            $daysToSubtract = ($todayWeekDay - $effectiveWeekDay + 7) % 7;
            if ($daysToSubtract == 0) $daysToSubtract = 7;
            
            $data_sessao = date('d-m-Y', strtotime("-$daysToSubtract days"));
        }
    }
    // Prevent future dates server-side as well
    $today_pt = date('d-m-Y');
    if (strtotime($data_sessao) > strtotime($today_pt)) {
        $data_sessao = $today_pt;
    }

    $ano_lectivo = Utils::computeCatecheticalYear(date("d-m-Y", strtotime($data_sessao)));
    $data_sql = date("Y-m-d", strtotime($data_sessao));

    // Check permissions
    if (!Authenticator::isAdmin() && !group_belongs_to_catechist($ano_lectivo, $catecismo, $turma, Authenticator::getUsername())) {
        echo("<div class=\"alert alert-danger\"><strong>Erro!</strong> Não tem permissões para aceder ao grupo selecionado.</div>");
        echo("</div></body></html>");
        die();
    }

    // Load catechumens and their attendance for the selected session
    try {
        $catechumens = $db->getCatechumensByCatechismWithFilters($ano_lectivo, $ano_lectivo, $catecismo, $turma);
        
        // Fetch current attendance for this session (needed for comparison during save and for the table display)
        $attendees = $db->getLessonAttendees($data_sql, $catecismo, $turma, $ano_lectivo);

        // Handle saving attendance
        if(isset($_POST['op']) && $_POST['op'] == "guardar")
        {
            $presencas_marcadas = isset($_POST['presenca']) ? $_POST['presenca'] : array(); // List of cids that are present
            
            // Block future dates on submit (server-side validation)
            if (strtotime($data_sql) > strtotime(date('Y-m-d'))) {
                echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> A data selecionada é no futuro. Só é possível marcar presenças para hoje ou datas passadas.</div>");
            } else {
                try {
                    // Ensure session exists (create only on save)
                    $sessions = $db->getCatechesisSessions($ano_lectivo, $catecismo, $turma);
                    $sessionExists = false;
                    foreach($sessions as $s) {
                        if($s['data'] == $data_sql) {
                            $sessionExists = true;
                            break;
                        }
                    }
                    if(!$sessionExists) {
                        $db->createCatechesisSession($data_sql, $catecismo, $turma, $ano_lectivo);
                        writeLogEntry("Sessão de catequese criada para o " . $catecismo . "º" . $turma . " em " . $data_sessao . ".");
                    }

                    // Get all catechumens in this group to mark those not in $presencas_marcadas as absent
                    $allCatechumens = $db->getCatechumensByCatechismWithFilters($ano_lectivo, $ano_lectivo, $catecismo, $turma);
                    
                    $changedCatechumens = 0;
                    foreach($allCatechumens as $cat)
                    {
                        $cid = intval($cat['cid']);
                        $isPresent = in_array($cid, $presencas_marcadas) ? 1 : 0;

                        $db->setCatechumenAttendance($data_sql, $catecismo, $turma, $ano_lectivo, $cid, $isPresent, Authenticator::getUsername());

                        $log_string = "Catequizando " . Utils::sanitizeOutput($cat['nome']) . " (cid=" . $cid . ") marcado como " . ($isPresent ? "Presente" : "Ausente") . " na sessão de " . $data_sessao . ".";
                        catechumenArchiveLog($cid, $log_string);

                        $changedCatechumens++;
                    }

                    if($changedCatechumens > 0) {
                        writeLogEntry("Alteradas presenças de " . $changedCatechumens . " catequizandos do " . $catecismo . "º" . $turma . " na sessão de " . $data_sessao . ".");
                    }

                    // Refresh attendees list after update
                    $attendees = $db->getLessonAttendees($data_sql, $catecismo, $turma, $ano_lectivo);

                    echo("<div class=\"alert alert-success\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Sucesso!</strong> Presenças atualizadas com sucesso.</div>");
                } catch (Exception $e) {
                    echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
                }
            }
        }

        // Handle deleting session
        if(isset($_POST['op']) && $_POST['op'] == "eliminar")
        {
            try {
                $db->deleteCatechesisSession($data_sql, $catecismo, $turma, $ano_lectivo);
                writeLogEntry("Sessão de catequese de " . $data_sessao . " eliminada para o " . $catecismo . "º" . $turma . ".");
                echo("<div class=\"alert alert-success\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Sucesso!</strong> Sessão de catequese eliminada com sucesso.</div>");
            } catch (Exception $e) {
                echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
            }
        }
        
        $presentCids = array();
        if($attendees) {
            foreach($attendees as $a) {
                $presentCids[] = intval($a['cid']);
            }
        }

        // Fetch all sessions to highlight in calendar
        $sessions = $db->getCatechesisSessions($ano_lectivo, $catecismo, $turma);
        $sessionDates = array();
        $currentSessionExists = false;
        if($sessions) {
            foreach($sessions as $s) {
                $sessionDates[] = $s['data'];
                if($s['data'] == $data_sql) {
                    $currentSessionExists = true;
                }
            }
        }
    } catch (Exception $e) {
        echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
        $catechumens = array();
    }

    $effectiveWeekDay = Utils::getEffectiveWeekDay($ano_lectivo, $catecismo, $turma);

    ?>

    <h2 class="no-print"> Marcar Presenças</h2>

    <div class="well well-lg" style="position:relative; z-index:2;">
        <form role="form" action="marcarPresencas.php" method="post" id="form_filtros">
            <div class="row">
                <!--catecismo-->
                <div class="form-group col-xs-4 col-sm-2">
                    <label for="catecismo">Catecismo:</label>
                    <select id="catecismo" name="catecismo" class="form-control" onchange="this.form.submit()">
                        <?php
                        $catechisms = $db->getCatechisms(Utils::currentCatecheticalYear());
                        if (isset($catechisms)) {
                            foreach($catechisms as $row) {
                                echo("<option value='" . intval($row['ano_catecismo']) . "'");
                                if ($catecismo == $row['ano_catecismo']) echo(" selected");
                                echo(">" . intval($row['ano_catecismo']) . "º</option>\n");
                            }
                        }
                        ?>
                    </select>
                </div>
                <!--turma-->
                <div class="form-group col-xs-4 col-sm-2">
                    <label for="turma">Grupo:</label>
                    <select id="turma" name="turma" class="form-control" onchange="this.form.submit()">
                        <?php
                        $groups = $db->getCatechismGroups($ano_lectivo, $catecismo);
                        if (isset($groups)) {
                            foreach($groups as $row) {
                                echo("<option value='" . Utils::sanitizeOutput($row['turma']) . "'");
                                if ($turma == $row['turma']) echo(" selected");
                                echo(">" . Utils::sanitizeOutput($row['turma']) . "</option>\n");
                            }
                        }
                        ?>
                    </select>
                </div>
                <!--data-->
                <div class="form-group col-xs-8 col-sm-3">
                    <label for="data_sessao">Data da sessão:</label>
                    <div class="input-group date" id="data_sessao_div" data-date="" data-date-format="dd-mm-yyyy">
                        <input class="form-control" id="data_sessao" name="data_sessao" type="text" onchange="this.form.submit();" 
                               placeholder="dd-mm-aaaa" value="<?php echo($data_sessao); ?>">
                        <span class="input-group-addon" style="cursor: pointer;"><i class="glyphicon glyphicon-calendar"></i></span>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <form role="form" action="marcarPresencas.php" method="post" id="form_presencas">
        <input type="hidden" name="op" value="guardar">
        <input type="hidden" name="catecismo" value="<?= $catecismo ?>">
        <input type="hidden" name="turma" value="<?= $turma ?>">
        <input type="hidden" name="data_sessao" value="<?= $data_sessao ?>">

        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <?php if (count($catechumens) >= 1): ?>
                    <tr class="no-print">
                        <th>
                            <input type="checkbox" id="checkbox-geral">
                            <span style="margin-left: 10px; vertical-align: middle;">Todos</span>
                        </th>
                        <th></th>
                        <th></th>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <th>Presença</th>
                        <th>Nome</th>
                        <th>Data nascimento</th>
                    </tr>
                </thead>
                <tbody class="rowlink">
                    <?php
                    if (count($catechumens) >= 1) {
                        foreach($catechumens as $row) {
                            $cid = intval($row['cid']);
                            $isPresent = in_array($cid, $presentCids);
                            $rowClass = $isPresent ? "success" : "danger";
                            ?>
                            <tr class="<?= $rowClass ?>">
                                <td>
                                    <input type="checkbox" class="attendance-switch" name="presenca[]" value="<?= $cid ?>" <?= $isPresent ? "checked" : "" ?>>
                                </td>
                                <td>
                                    <a href="mostrarFicha.php?cid=<?= $cid ?>" target="_blank"></a>
                                    <?= Utils::sanitizeOutput($row['nome']) ?>
                                </td>
                                <td><?= date("d-m-Y", strtotime($row['data_nasc'])) ?></td>
                            </tr>
                            <?php
                        }
                    } else {
                        echo("<tr><td colspan='3' class='text-center'>Sem catequizandos para este grupo</td></tr>");
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <div class="no-print">
            <div class="btn-group" role="group">
                <button type="submit" class="btn btn-primary"><i class="glyphicon glyphicon-floppy-disk"></i> Guardar</button>
                <?php if ($currentSessionExists): ?>
                    <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#confirmarEliminarSessao">
                        <i class="glyphicon glyphicon-trash"></i> Eliminar sessão de catequese
                    </button>
                <?php endif; ?>
            </div>
        </div>
        <input type="hidden" name="op" id="op_eliminar" value="guardar">
    </form>

    <div class="clearfix" style="margin-bottom: 40px"></div>

</div>

<?php
// Dialog to confirm delete catechesis session
$confirmDeleteDialog->setTitle("Confirmar eliminação");
$confirmDeleteDialog->setBodyContents(<<<HTML_CODE
    <p>Tem a certeza que deseja eliminar esta sessão e todas as presenças nela registadas?</p>
HTML_CODE
);
$confirmDeleteDialog->addButton(new Button("Não", ButtonType::SECONDARY))
                    ->addButton(new Button("Sim", ButtonType::DANGER, "eliminarSessao()"));
$confirmDeleteDialog->renderHTML();
?>

<?php $pageUI->renderJS(); ?>
<script src="js/rowlink.js"></script>
<script src="js/bootstrap-datepicker-1.9.0-dist/js/bootstrap-datepicker.min.js"></script>
<script src="js/bootstrap-datepicker-1.9.0-dist/locales/bootstrap-datepicker.pt.min.js"></script>
<script src="js/bootstrap-switch.js"></script>

<script>
    var sessionDates = <?= json_encode($sessionDates) ?>;
    var effectiveWeekDay = <?= $effectiveWeekDay ?>;

    $(document).ready(function() {
        $('#data_sessao_div').datepicker({
            format: "dd-mm-yyyy",
            language: "pt",
            autoclose: true,
            todayHighlight: true,
            endDate: "0d",
            beforeShowDay: function(date) {
                var dateString = date.getFullYear() + '-' + 
                                ('0' + (date.getMonth() + 1)).slice(-2) + '-' + 
                                ('0' + date.getDate()).slice(-2);
                
                // Registered days take precedence
                if (sessionDates.indexOf(dateString) !== -1) {
                    return {
                        classes: 'highlighted-session-date',
                        tooltip: 'Sessão existente'
                    };
                }

                // Week days where the group usually has classes
                if (date.getDay() === effectiveWeekDay) {
                    return {
                        classes: 'highlighted-weekday',
                        tooltip: 'Dia habitual de catequese'
                    };
                }
            }
        });
        $('#data_sessao_div .input-group-addon').on('click', function() {
            $('#data_sessao_div').datepicker('show');
        });

        $(".attendance-switch").bootstrapSwitch({
            size: 'mini',
            onText: 'Presente',
            offText: 'Falta',
            onColor: 'success',
            offColor: 'danger'
        });

        $('.attendance-switch').on('switchChange.bootstrapSwitch', function(event, state) {
            var row = $(this).closest('tr');
            if (state) {
                row.removeClass('danger').addClass('success');
            } else {
                row.removeClass('success').addClass('danger');
            }
        });

        $("#checkbox-geral").bootstrapSwitch({
            size: 'mini',
            onText: 'Presente',
            offText: 'Falta',
            onColor: 'success',
            offColor: 'danger'
        });

        $('#checkbox-geral').on('switchChange.bootstrapSwitch', function(event, state) {
            $('.attendance-switch').bootstrapSwitch('state', state);
        });
    });

    function eliminarSessao() {
        document.getElementById('op_eliminar').value = 'eliminar';
        document.getElementById('form_presencas').submit();
    }
</script>

</body>
</html>
