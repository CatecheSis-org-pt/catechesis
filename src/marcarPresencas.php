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



// Create the widgets manager
$pageUI = new WidgetManager();

// Instantiate the widgets used in this page and register them in the manager
$menu = new MainNavbar(null, MENU_OPTION::CATECHESIS);
$pageUI->addWidget($menu);

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
        if(date('D') == 'Sat')
            $data_sessao = date('d-m-Y', strtotime('today'));
        else
        {
            $defaultWeekDay = WeekDay::toString(Configurator::getConfigurationValueOrDefault(Configurator::KEY_CATECHESIS_WEEK_DAY));
            $data_sessao = date('d-m-Y', strtotime('next ' . $defaultWeekDay));
        }
    }
    // Prevent future dates server-side as well
    $today_pt = date('d-m-Y');
    if (strtotime($data_sessao) > strtotime($today_pt)) {
        $data_sessao = $today_pt;
    }

    $ano_lectivo = Utils::computeCatecheticalYear(date("d-m-Y", strtotime($data_sessao)));
    $data_sql = date("Y-m-d", strtotime($data_sessao));

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
                }

                // Get all catechumens in this group to mark those not in $presencas_marcadas as absent
                $allCatechumens = $db->getCatechumensByCatechismWithFilters($ano_lectivo, $ano_lectivo, $catecismo, $turma);
                
                foreach($allCatechumens as $cat) {
                    $cid = intval($cat['cid']);
                    $isPresent = in_array($cid, $presencas_marcadas) ? 1 : 0;
                    $db->setCatechumenAttendance($data_sql, $catecismo, $turma, $ano_lectivo, $cid, $isPresent, Authenticator::getUsername());
                }

                echo("<div class=\"alert alert-success\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Sucesso!</strong> Presenças atualizadas com sucesso.</div>");
            } catch (Exception $e) {
                echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
            }
        }
    }

    // Load catechumens and their attendance for the selected session
    try {
        $catechumens = $db->getCatechumensByCatechismWithFilters($ano_lectivo, $ano_lectivo, $catecismo, $turma);
        
        // Fetch current attendance for this session
        $attendees = $db->getLessonAttendees($data_sql, $catecismo, $turma, $ano_lectivo);
        $presentCids = array();
        if($attendees) {
            foreach($attendees as $a) {
                $presentCids[] = intval($a['cid']);
            }
        }
    } catch (Exception $e) {
        echo("<div class=\"alert alert-danger\"><a href=\"#\" class=\"close\" data-dismiss=\"alert\">&times;</a><strong>Erro!</strong> " . $e->getMessage() . "</div>");
        $catechumens = array();
    }

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
            </div>
        </div>
    </form>

    <div class="clearfix" style="margin-bottom: 40px"></div>

</div>

<?php $pageUI->renderJS(); ?>
<script src="js/rowlink.js"></script>
<script src="js/bootstrap-datepicker-1.9.0-dist/js/bootstrap-datepicker.min.js"></script>
<script src="js/bootstrap-datepicker-1.9.0-dist/locales/bootstrap-datepicker.pt.min.js"></script>
<script src="js/bootstrap-switch.js"></script>

<script>
    $(document).ready(function() {
        $('#data_sessao_div').datepicker({
            format: "dd-mm-yyyy",
            language: "pt",
            autoclose: true,
            todayHighlight: true,
            endDate: "0d"
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
    });
</script>

</body>
</html>
