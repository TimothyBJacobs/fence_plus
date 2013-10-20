/**
 *
 * @package Fence Plus
 * @subpackage
 * @since
 */
jQuery(document).ready(function ($) {
    $('.remove span').click(function () {
        var coach_id = $(this).attr('data-coach-id');

        var data = {
            action: 'fence_plus_remove_coach',
            coach_id: coach_id,
            fencer_id: fence_plus_ajax.fencer_id
        };

        $.post(fence_plus_ajax.ajax_url, data, function (response) {
            if (response == true) {
                $('#coach-' + coach_id).remove();
            }
        });
    });
});