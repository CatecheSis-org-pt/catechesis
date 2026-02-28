<?php
require_once(CATECHESIS_ROOT_DIRECTORY . "authentication/Authenticator.php");
use catechesis\Authenticator;
?>

<div class="news-block-left" data-animation="animate__slideInRight">

    <h3>Registo de presenças ✅ </h3>

    <p>Finalmente, é possível registar as presenças dos catequizandos no CatecheSis!</p>
    <p>Aceda através do menu <a href="marcarPresencas.php" target="_blank"><i>Catequese > Marcar presenças</i></a> ou do atalho na página inicial.</p>
    <img class="img-responsive" src="help/onboarding/img/menu_presencas.png"/>

    <div style="margin-bottom: 40px;"></div>
    <p>A assiduidade de cada catequizando é mostrada no respetivo Arquivo, e também na página de <a href="aproveitamento.php" target="_blank">registo do aproveitamento</a>.</p>
    <img class="img-responsive" src="help/onboarding/img/presencas_bar.png"/>

    <div style="margin-bottom: 40px;"></div>
    <p>A folha de presenças em papel sai pré-preenchida com as presenças que marcou no CatecheSis até ao momento.</p>
    <img class="img-responsive" src="help/onboarding/img/folha_presencas.png"/>
</div>

<div style="margin-bottom: 40px;"></div>

<div class="news-block-right" data-animation="animate__slideInLeft">

    <h3>Modo "Quem é quem" 🤩</h3>

    <p>Veja os seus catequizandos como cartões animados em qualquer listagem ou resultado de pesquisa!</p>
    <video src="help/onboarding/img/quem_e_quem.m4v" style="width: 100%;" autoplay loop muted></video>

    <p>Memorize mais facilmente nomes e caras, ou registe as presenças e o aproveitamento dos catequizandos simplesmente levantando e baixando cartões! 😎</p>

    <p>Procure este ícone <img style="width: 100px; height: auto;" src="help/onboarding/img/icone_quem_e_quem.png"/> para ativar o modo.</p>

</div>

<div style="margin-bottom: 40px"></div>

<?php
    if(Authenticator::isAdmin())
    {
        ?>
        <div class="news-block-left" data-animation="animate__slideInRight">

            <h3>Catequese a horas 🕜</h3>

            <p>Agora é possível definir o dia da semana e o horário das sessões de cada grupo de catequese.</p>
            <p>Defina um dia de semana e horário geral, para todos os grupos, na página de <a href="configuracoes.php" target="_blank">Configurações</a> ...</p>
            <img class="img-responsive" src="help/onboarding/img/horario_catequese_geral.png"/>

            <div style="margin-bottom: 40px;"></div>
            <p>... e redefina dias ou horários para grupos específicos, na página de <a href="gerirGrupos.php" target="_blank">Gestão de grupos</a>.</p>
            <img class="img-responsive" src="help/onboarding/img/horario_catequese_especifico.png"/>

            <div style="margin-bottom: 40px;"></div>
            <p>Esta definição influencia o cálculo das datas nas folhas de presenças.</p>


        </div>
        <?php
    }
?>
