/**
 *
 * @package Fence Plus
 * @subpackage
 * @since
 */

jQuery(document).ready(function($) {
    $('.fencer-show-more-info').click(function() {
        var usfa_id = $(this).attr('data-usfa-id');
        $( "#fencer-" + usfa_id + " .fencer-more-info-box" ).toggle( 'blind', 500 );
    });
});