function onCheckUseCustomImage(widget_id)
{
    if(document.getElementById(widget_id + '_use_custom_image').checked) //Custom image
    {
        $("#" + widget_id + "_customize_front_page_collapsible").collapse('show');
    }
    else //Default image
    {
        $("#" + widget_id + "_customize_front_page_collapsible").collapse('hide');
    }
}