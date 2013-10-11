/**
 *
 * @package Fence Plus
 * @subpackage
 * @since
 */

jQuery(document).ready(function($) {
    $('#fencer-show-more-info').click(function() {
        $( "#fencer-more-info-box" ).toggle( 'blind', 500 );
    });
});