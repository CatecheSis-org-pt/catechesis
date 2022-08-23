/**
 * Checks whether the submitted sacrament date is valid.
 * Changes the UI to show an error and highlight the form field.
 * @param date_element_id
 * @param date_div_id
 * @param error_icon_id
 * @returns {boolean}
 */
function check_sacrament_date(date_element_id, date_div_id, error_icon_id)
{
    var date = document.getElementById(date_element_id).value;

    if(!data_valida(date) && date!=="" && date!==undefined)
    {
        $('#' + date_div_id).addClass('has-error');
        $('#' + date_div_id).addClass('has-feedback');
        $('#' + error_icon_id).show();
        return false;
    } else {
        $('#' + date_div_id).removeClass('has-error');
        $('#' + date_div_id).removeClass('has-feedback');
        $('#' + error_icon_id).hide();
        return true;
    }
}


/**
 * Setup the document upload drop zone.
 */

$(document).bind('dragover', function (e) {
    var dropZones = $('.dropzone'),
        timeout = window.dropZoneTimeout;
    if (timeout) {
        clearTimeout(timeout);
    } else {
        dropZones.addClass('in');
    }
    var hoveredDropZone = $(e.target).closest(dropZones);
    dropZones.not(hoveredDropZone).removeClass('hover');
    dropZones.not(hoveredDropZone).removeClass('in');
    hoveredDropZone.addClass('hover');
    hoveredDropZone.addClass('in');
    window.dropZoneTimeout = setTimeout(function () {
        window.dropZoneTimeout = null;
        dropZones.removeClass('in hover');
    }, 100);
});

$(document).bind('drop dragover', function (e) {
    e.preventDefault();
});