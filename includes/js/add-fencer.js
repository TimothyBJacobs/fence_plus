/**
 *
 * @package Fence Plus
 * @subpackage
 * @since
 */

jQuery(document).ready(function ($) {
    $('#fence-plus-select-fencer').select2({
        placeholder: fence_plus_ajax.select_placeholder,
        allowClear: true
    });

    $('#fence-plus-add-fencer').submit(function () {
        $('#fence-plus-submit').attr('disabled', 'disabled');
        $('#fence-plus-ajax-loading').show();

        var fencer_id = $('#fence-plus-select-fencer').val();

        var data = {
            action: 'fence_plus_add_fencer_to_coach',
            coach_id: fence_plus_ajax.coach_id,
            fencer_id: fencer_id
        };

        $.post(fence_plus_ajax.ajax_url, data, function (response) {
            if ($.isNumeric(response)) {
                console.log(response);
                $( '#fencer-' + response ).flash( '#FFD700', 2000 );
            } else {
                $('#fence-plus-ajax-results').append(response);
            }

            reset_page();
        });

        return false;
    });

    jQuery.fn.flash = function( color, duration )
    {
        var current = this.css( 'background-color' );
        this.animate( { 'background-color': color }, 0 );
        this.animate( { 'background-color': current }, duration );
    };

    function reset_page() {
        $('#fence-plus-submit').removeAttr('disabled');
        $('#fence-plus-ajax-loading').hide();
        $('#fence-plus-select-fencer').val('');
    }
});