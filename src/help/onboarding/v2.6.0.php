<?php
require_once(__DIR__ . "/../../authentication/Authenticator.php");
use catechesis\Authenticator;
?>

<div class="news-block-left" data-animation="animate__slideInRight">

    <h3>Campos telefone/telemóvel reorganizados 📞 </h3>

    <p>Agora, nas fichas dos catequizandos, os campos de telefone e telemóvel aparecem junto ao pai/mãe, para tornar mais explícito de quem é cada número de telefone.</p>

    <img class="img-responsive" src="help/onboarding/img/campos_telefone_atualizados.jpg"/>

    <div style="margin-bottom: 40px;"></div>
    <p>Os contactos do encarregado de educação ainda aparecem no painel de Informações de contacto, junto à morada e e-mail, para um acesso mais rápido.</p>
    <p>Os antigos "telemóvel" e "telefone" gerais foram utilizados para preencher os contactos da pessoa designada como encarregado de educação.<br>
        Nesta fase de transição, precisamos da sua ajuda para confirmar se os contactos dos seus catequizandos estão registados no campo correto (pai ou mãe).</p>

</div>

<div style="margin-bottom: 40px;"></div>

<div class="news-block-right" data-animation="animate__slideInLeft">

    <h3>Melhorias no tratamento do campo NIF 📝</h3>

    <p>Ao editar fichas de catequizandos, o campo NIF pode agora ser preenchido com o valor `000 000 000`, quando não souber o NIF.<br>
        Utilize o botão azul junto ao campo NIF <img style="width: 100px; height: auto;" src="help/onboarding/img/botao_NIF.png"/> para preencher automaticamente com este valor.</p>

    <p>Se a sua paróquia passou a utilizar recentemente o campo NIF nos formulários de inscrição do CatecgeSis, nesta fase de transição, muitas fichas antigas ainda não têm o NIF preenchido.
        O relatório de dados inconsistentes irá informá-lo acerca de catequizandos com o NIF em falta.</p>

    <p><i>Nota: O campo NIF pode não estar disponível na sua paróquia, se um administrador não o tiver ativado.</i></p>


</div>

<div style="margin-bottom: 40px"></div>

<?php
    if(Authenticator::isAdmin())
    {
        ?>
        <?php
    }
?>
