<?php

namespace catechesis\gui;

use Dompdf\Exception;

require_once(__DIR__ . '/../Widget.php');
require_once(__DIR__ . '/../ModalDialog/ModalDialogWidget.php');
require_once(__DIR__ . "/../../common/Animation.php");
require_once(__DIR__ . "/../../common/Button.php");

class AboutDialogWidget extends ModalDialogWidget
{
    public function __construct(string $id = null)
    {
        parent::__construct($id);

        $this->setSize(ModalDialogWidget::SIZE_MEDIUM);
        $this->setTitle("Acerca do CatecheSis...");
        $this->addButton(new Button("Fechar", ButtonType::PRIMARY));
    }


    /**
     * Setting the body of this modal dialog is unsupported.
     * @param string $contents
     * @return $this
     */
    public function setBodyContents(string $contents)
    {
        throw new Exception("AboutDialogWidget: The body of a AboutDialogWidget cannot be set.");
    }

    /**
     * Renders the body of the about dialog.
     * @return void
     */
    protected function renderBodyContents()
    {
        ?>
        <div style="overflow: hidden;">
            <div class="container col-xs-12">
                <div class="col-xs-4">
                    <img src="img/CatecheSis_Logo_About.svg" class="img-responsive">
                </div>
                <div class="col-xs-8">
                    <div class="col-xs-12">
                        <h3> CatecheSis </h3>
                    </div>
                    <div class="col-xs-12">
                        <span>Versão 2.1.0</span>
                    </div>
                    <div class="col-xs-12">
                        <span>Última revisão em: 01-05-2023</span>
                    </div>
                </div>
                <div class="clearfix"></div>
            </div>
            <div class="clearfix"></div>

            <div style="margin-bottom: 40px;"></div>

            <p>O CatecheSis é um sistema livre, <i>open-source</i>, para gestão de grupos de catequese concebido por catequistas e para catequistas para atender às necessidades da sua paróquia.</p>
            <p>Desenvolvido pelos <a style="cursor: pointer;" onclick='window.open("licenses/CatecheSis/authors.php", "Termos e condições", "width=500, height=700, left=800, top=100, status=no, menubar=no, toolbar=no, location=no, scrollbars=1").focus();'>autores e contribuidores do CatecheSis</a>.</p>
            <p>Saiba mais em <a href="https://catechesis.org.pt" target="_blank" rel="noopener noreferrer">catechesis.org.pt</a>.</p>

            <div style="margin-bottom: 20px;"></div>

            <a style="cursor: pointer;" data-toggle="collapse" data-target="#license"> <i class="fas fa-chevron-down"></i> Licenças </a>

            <div style="margin-bottom: 10px"></div>

            <div id="license" class="collapse">
                <div class="container col-xs-12">
                    <p>O CatecheSis está sujeito à licença <a style="cursor: pointer;" onclick='window.open("licenses/CatecheSis/LICENSE", "Licença", "width=500, height=700, left=800, top=100, status=no, menubar=no, toolbar=no, location=no, scrollbars=1").focus();'>licença AGPL-3.0</a>.</p>
                    <p><pre><?php include(__DIR__ . '/../../../licenses/CatecheSis/LICENSE_SHORT_PT'); ?></pre></p>
                    <span>O CatecheSis também utiliza software escrito por terceiros.<br></span>
                    <span>As respectivas licenças podem ser consultadas <a style="cursor: pointer;" onclick='window.open("licenses/index.php", "Software de terceiros", "width=500, height=700, left=800, top=100, status=no, menubar=no, toolbar=no, location=no, scrollbars=1").focus();'>aqui</a>.</span>
                </div>
                <div class="row clearfix" style="margin-top:10px; "></div>
            </div>

            <div style="margin-bottom: 20px;"></div>

            <a style="cursor: pointer;" data-toggle="collapse" data-target="#terms_and_condions"> <i class="fas fa-chevron-down"></i> Termos e condições </a>

            <div style="margin-bottom: 10px"></div>

            <div id="terms_and_condions" class="collapse">
                <div class="container col-xs-12">
                    <p>A utilização do CatecheSis está sujeita aos <a style="cursor: pointer;" onclick='window.open("licenses/CatecheSis/termos_e_condicoes.php", "Termos e condições", "width=500, height=700, left=800, top=100, status=no, menubar=no, toolbar=no, location=no, scrollbars=1").focus();'>termos e condições</a>.</p>
                    <p><b>Ao utilizar o CatecheSis reconhece que leu e aceitou os termos e condições.</b></p>
                </div>
                <div class="row clearfix" style="margin-top:10px; "></div>
            </div>
        </div>
        <?php
    }
}