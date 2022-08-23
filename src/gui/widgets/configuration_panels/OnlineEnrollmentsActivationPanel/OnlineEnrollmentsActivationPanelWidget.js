$(function () {
    $('[data-toggle="popover"]').popover({ trigger: 'hover',
        html: true,
        delay: { 'show': 500, 'hide': 100 }
    });
})

$(function () {
    $('[data-toggle="tooltip"]').tooltip()
})


function onCheckShowPaymentInfo(widget_id)
{
    if(document.getElementById(widget_id + '_enable_payment').checked) //Outro
    {
        $("#" + widget_id + "_payment_info_collapsible").collapse('show');
    }
    else //Pai ou mae
    {
        $("#" + widget_id + "_payment_info_collapsible").collapse('hide');
    }
}