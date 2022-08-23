$(function () {
    $('[data-toggle="popover"]').popover({ trigger: 'hover',
        html: true,
        delay: { 'show': 500, 'hide': 100 }
    });
})

$(function () {
    $('[data-toggle="tooltip"]').tooltip()
})



var catechumen_attributes_visible = {};
function set_attributes_visibility(widget_id, value)
{
    catechumen_attributes_visible[widget_id] = value;
}

function show_hide_catechumen_attributes(widget_id)
{
    if(catechumen_attributes_visible[widget_id])
    {
        catechumen_attributes_visible[widget_id] = false;
        $("#" + widget_id + "_botao_atributos").html("<span class=\"glyphicon glyphicon-eye-open\"></span> Mostrar atributos");
        $("." + widget_id + "_col_atributos").animate
        (
            {
                'max-width':'50px',
                'opacity':0.0
            },
            "400"
        );
    }
    else
    {
        catechumen_attributes_visible[widget_id] = true;
        $("#" + widget_id + "_botao_atributos").html("<span class=\"glyphicon glyphicon-eye-close\"></span> Ocultar atributos");
        $("." + widget_id + "_col_atributos").animate
        (
            {
                'max-width':'100px',
                'opacity':1.0
            },
            "400"
        );
    }
}



var catechumen_sacraments_visible = {};
function set_sacraments_visibility(widget_id, value)
{
    catechumen_sacraments_visible[widget_id] = value;
}

function show_hide_catechumen_sacraments(widget_id)
{
    if(catechumen_sacraments_visible[widget_id])
    {
        catechumen_sacraments_visible[widget_id] = false;
        $("#" + widget_id + "_botao_sacramentos").html("<span class=\"glyphicon glyphicon-eye-open\"></span> Mostrar sacramentos");
        $("." + widget_id + "_col_sacramentos").animate
        (
            {
                'max-width':'0px',
                'opacity':0.0
            },
            "400"
        );
        $("#" + widget_id + "_legenda_sacramentos").animate
        (
            {
                'opacity':0.0
            },
            "400"
        );
        $("#" + widget_id + "_legenda_sacramentos_p").animate
        (
            {
                'opacity':0.0
            },
            "400"
        );
    }
    else
    {
        catechumen_sacraments_visible[widget_id] = true;
        $("#" + widget_id + "_botao_sacramentos").html("<span class=\"glyphicon glyphicon-eye-close\"></span> Ocultar sacramentos");
        $("." + widget_id + "_col_sacramentos").animate
        (
            {
                'max-width':'300px',
                'opacity':1.0
            },
            "400"
        );
        $("#" + widget_id + "_legenda_sacramentos").animate
        (
            {
                'opacity':1.0
            },
            "400"
        );
        $("#" + widget_id + "_legenda_sacramentos_p").animate
        (
            {
                'opacity':1.0
            },
            "400"
        ); //Para impressora
    }
}


/**
 * Redraws a DataTable given the ID of the corresponding HTML table,
 * and a reference to the actual DataTable object instantiated in Javascript.
 * This method is to be called whenever the window size changes, to fix
 * the columns widths.
 * @param table_id
 * @param dataTables_object
 */
function redraw_datatable(table_id, dataTables_object)
{
    $(table_id).css({ width: $(table_id).parent().width() });
    dataTables_object.columns.adjust().draw();
}

function download_results(widget_id, tipo_ficheiro)
{
    document.getElementById(widget_id + "_transferir_tipo").value = tipo_ficheiro;
    document.getElementById(widget_id + "_transferir_form").submit();
}