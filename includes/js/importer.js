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


    // Uploading files
    var file_frame;

    jQuery('#csv-import_button').live('click', function (event) {

        event.preventDefault();

        // If the media frame already exists, reopen it.
        if (file_frame) {
            file_frame.open();
            return;
        }

        // Create the media frame.
        file_frame = wp.media.frames.file_frame = wp.media({
            title: "Select a CSV of USFA IDs and Emails",
            button: {
                text: jQuery(this).data('uploader_button_text')
            },
            multiple: false, // Set to true to allow multiple files to be selected
            library: {
                type: "text/csv"
            }
        });

        // When an image is selected, run a callback.
        file_frame.on('select', function () {
            // We set multiple to false so only get one image from the uploader
            attachment = file_frame.state().get('selection').first().toJSON();

            // Do something with attachment.id and/or attachment.url here
            jQuery("#csv-import").val(attachment.url);
        });

        // Finally, open the modal
        file_frame.open();
    });

});