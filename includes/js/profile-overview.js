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
    });
});