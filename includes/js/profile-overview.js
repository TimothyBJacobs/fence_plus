/**
 *
 * @package Fence Plus
 * @subpackage
 * @since
 */

jQuery(document).ready(function ($) {
    $(document).on('click', ".fencer-show-more-info", function () {
        var usfa_id = $(this).attr('data-usfa-id');
        $("#fencer-" + usfa_id + " .fencer-more-info-box").toggle('blind', 500);

        return false;
    });

    $(document).on('click', ".fencer-primary-weapon .saved-weapon, .fencer-primary-weapon .genericon", function () {
        var usfa_id = $(this).closest('.fencer-primary-weapon').attr('data-usfa-id');
        var parent_object_selector = "#fencer-" + usfa_id;

        var existing_weapon = $(parent_object_selector + " .fencer-primary-weapon .saved-weapon");
        var weapon_selector = $(parent_object_selector + " .fencer-primary-weapon .new-weapon");


        if (existing_weapon.css('display') == 'none') {
            existing_weapon.show();
            weapon_selector.hide();
        } else {
            existing_weapon.hide();
            weapon_selector.show();
        }
    });

    $(document).on('change', '.new-weapon', function () {
        $(this).prev().text($(this).val()).show();
        $(this).hide();

        var data = {
            action: 'fence_plus_change_primary_weapon',
            value: $(this).val(),
            user_id: $(this).closest('.fence-plus-fencer-overview').attr('data-wp-id')
        };

        $.post(fence_plus_ajax.ajax_url, data, function (response) {
            console.log(response);
        });

    });
});