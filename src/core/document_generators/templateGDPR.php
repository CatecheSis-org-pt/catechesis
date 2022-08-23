<?php
require_once(__DIR__ . "/../Configurator.php");

use catechesis\Configurator;

// Get parish GDPR data
$parishName = Configurator::getConfigurationValueOrDefault(Configurator::KEY_PARISH_NAME);
$parishDiocese = Configurator::getConfigurationValueOrDefault(Configurator::KEY_PARISH_DIOCESE);
$dataProcessingName = Configurator::getConfigurationValueOrDefault(Configurator::KEY_GDPR_RESPONSIBLE_NAME);
$dataProcessingAddress = Configurator::getConfigurationValueOrDefault(Configurator::KEY_GDPR_RESPONSBILE_ADDRESS);
$dataProcessingEmail = Configurator::getConfigurationValueOrDefault(Configurator::KEY_GDPR_RESPONSIBLE_EMAIL);
$dpoName = Configurator::getConfigurationValueOrDefault(Configurator::KEY_GDPR_DPO_NAME);
$dpoAddress = Configurator::getConfigurationValueOrDefault(Configurator::KEY_GDPR_DPO_ADDRESS);
$dpoEmail = Configurator::getConfigurationValueOrDefault(Configurator::KEY_GDPR_DPO_EMAIL);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8" />
    <title>Declaração de consentimento</title>
    <meta name="author" content="User" />
    <style>
        * {font-family: Calibri; font-size: 11pt;}
        a.NoteRef {text-decoration: none;}
        hr {height: 1px; padding: 0; margin: 1em 0; border: 0; border-top: 1px solid #CCC;}
        table {border: 1px solid black; border-spacing: 0px; width : 100%;}
        td {border: 1px solid black;}
        .Normal {margin-top: 0; margin-bottom: 0;}
        .Ligação de Internet {color: #0000FF; text-decoration: underline ;}
        .Título {font-family: 'Liberation Sans'; font-size: 14pt;}
        .Body_Text {margin-top: 0pt; margin-bottom: 7pt;}
        .List {font-family: 'Lohit Devanagari';}
        .Caption {font-family: 'Lohit Devanagari'; font-size: 12pt; font-style: italic;}
        .Índice {font-family: 'Lohit Devanagari';}
    </style>
</head>
<body>
<p style="text-align: center; margin-top: 0; margin-bottom: 0;"><span style="font-size: 16pt; font-weight: bold;">Regulamento Geral da Proteção de Dados</span></p>
<p style="margin-bottom: 40pt"></p>
<p class="Body_Text" style="text-align: justify;">1. Tomei conhecimento de que os dados pessoais constantes do presente formulário, são tratados no estrito âmbito da missão da Igreja Católica, pela Catequese Paroquial da <?= $parishName ?>, Diocese de <?= $parishDiocese ?>, apenas para fins relativos ao percurso catequético do meu educando, o que inclui nomeadamente a sua disponibilização nos órgãos de informação da Fábrica da Igreja Paroquial da <?=$parishName?>, mais consentindo expressamente na visualização da sua fotografia pessoal, nos termos dos artigos 6º, nº 1, a), e) e f) e 9º, nº 2, d) do Regulamento (EU) 2016/679 do Parlamento Europeu e do Conselho, e nos demais termos desse Regulamento. </p>
<p class="Body_Text" style="text-align: justify;">2. Tomei conhecimento que o órgão responsável pelo tratamento dos dados é <?=$dataProcessingName?>, com domicílio em <?=$dataProcessingAddress?> com o e-mail <a href="mailto:<?=$dataProcessingEmail?>"><?=$dataProcessingEmail?></a>.</p>
<p class="Body_Text" style="text-align: justify;">3. O DPO – Encarregado da Proteção de Dados é <?=$dpoName?>, com domicílio profissional em <?=$dpoAddress?> e com o e-mail <a href="mailto:<?=$dpoEmail?>"><?=$dpoEmail?></a>.</p>
<p class="Body_Text" style="text-align: justify;">4. Tomei conhecimento que estes dados serão conservados enquanto a missão da Igreja, a finalidade para que foram recolhidos e tratados assim o exigir. </p>
<p class="Body_Text" style="text-align: justify;">5. Tomei conhecimento que enquanto titular dos dados tenho, nos termos do Regulamento (EU) 2016/679 do Parlamento Europeu e do Conselho, o direito de solicitar ao responsável pelo tratamento o acesso aos dados que me digam respeito, bem como a sua retificação ou o seu apagamento, a limitação do tratamento, o direito de me opor ao tratamento, o direito à portabilidade dos dados (ou seja, se o tratamento for realizado por meios automatizados, o direito de os receber num formato estruturado, de uso corrente e de leitura automática, e o direito de transmitir esses dados a outro responsável pelo seu tratamento) o direito de retirar o consentimento se esse for o fundamento do tratamento a qualquer altura e o direito de apresentar reclamação à autoridade de controlo, designadamente Comissão Nacional de Proteção de Dados, (www.cnpd.pt; geral@cnpd.pt), bem como ser notificado em caso de violação de dados pessoais que implique um elevado risco para os meus direitos e liberdades.</p>
<p class="Body_Text" style="text-align: justify; margin-top: 20pt;"> Assim, assino a presente declaração em como tomei conhecimento e aceito a política de proteção de dados que me foi exposta. </p>
</body>
</html>