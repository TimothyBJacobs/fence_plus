/**
 *
 * @package Fence Plus
 * @subpackage Includes
 * @since 0.1
 */

jQuery(document).ready(function ($) {
    $('#import-fencers').click(function () {
        $(this).attr('disabled', 'disabled');
        $('#ajax-loading').show();

        var responseData = $('#response-data');

        responseData.text('Importing fencers. Feel free to click away, the import will not be interrupted');
        var wipe = false;

        if ($('#wipe-fencers').is(":checked")) {
            wipe = 'delete';
        }

        var usfa_id = $('#usfa-club-id').val();

        if (usfa_id.length < 1) {
            responseData.text("No USFA ID Provided.");
            reset_page();
            return false;
        }

        var data = {
            action: 'fence_plus_import_fencers',
            usfa_id: usfa_id,
            wipe: wipe,
            csv: $('#csv-import').val()
        };

        $.post(fence_plus_ajax.ajax_url, data, function (response) {
            $('#response-data').text(response);
            reset_page();
        });

        return false;
    });

    function reset_page() {
        $('#ajax-loading').hide();
        $('#import-fencers').removeAttr('disabled');
        $('#usfa-club-id').val('');
        $('#wipe-fencers').removeAttr('checked');
        $('#csv-import').val('');
    }

    var _custom_media = true,
            _orig_send_attachment = wp.media.editor.send.attachment;

    $('#csv-import_button').click(function (e) {
        var send_attachment_bkp = wp.media.editor.send.attachment;
        var button = $(this);
        var id = button.attr('id').replace('_button', '');
        _custom_media = true;
        wp.media.editor.send.attachment = function (props, attachment) {
            if (_custom_media) {
                $("#" + id).val(attachment.url);
                return false;
            } else {
                return _orig_send_attachment.apply(this, [props, attachment]);
            }
        };

        wp.media.editor.open(button);
        return false;
    });

    $('.add_media').on('click', function () {
        _custom_media = false;
    });

});