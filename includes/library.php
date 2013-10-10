<?php
/**
 *
 * @package Fence Plus
 * @subpackage includes
 * @since 0.1
 */

function ibd_implode_with_word( $array, $word ) {
	$last = array_slice( $array, - 1 );
	$first = join( ', ', array_slice( $array, 0, - 1 ) );
	$both = array_filter( array_merge( array( $first ), $last ) );

	return join( " $word ", $both );
}