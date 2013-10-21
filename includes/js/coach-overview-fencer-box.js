/**
 *
 * @package Fence Plus
 * @subpackage
 * @since
 */
jQuery(document).ready(function ($) {
    $(document).on('click', '.old-coach .action span', function () {
        var coach_id = $(this).attr('data-coach-id');

        var data = {
            action: 'fence_plus_remove_coach',
            coach_id: coach_id,
            fencer_id: fence_plus_ajax.fencer_id
        };

        $.post(fence_plus_ajax.ajax_url, data, function (response) {
            if (response == true) {
                $('#coach-' + coach_id).toggle('blind', 1000, function () {
                    $(this).prependTo("#new-coaches").removeClass('old-coach').addClass('new-coach');
                    $("#no-new-coach-message").fadeOut();

                    if ($('.old-coach').length < 1) {
                        $('#no-coach-message').fadeIn();
                    }

                    $(this).toggle('blind', 1000);
                });
            }
        });
    });

    $(document).on('click', '.new-coach .action span', function () {
        var coach_id = $(this).attr('data-coach-id');

        var data = {
            action: 'fence_plus_add_coach_to_fencer',
            coach_id: coach_id,
            fencer_id: fence_plus_ajax.fencer_id
        };

        $.post(fence_plus_ajax.ajax_url, data, function (response) {
            if (response == true) {
                var coach = $('#coach-' + coach_id);
                coach.toggle('blind', 1000, function () {
                    $(this).prependTo("#old-coaches").removeClass('new-coach').addClass('old-coach');
                    $('#no-coach-message').fadeOut();
                    if ($('.new-coach').length < 1) {
                        $('#no-new-coach-message').fadeIn();
                    }

                    $(this).toggle('blind', 1000);
                });
            }
            else if (response == false) {
                $('#old-coaches').text('test');
            }
        });
    });
});