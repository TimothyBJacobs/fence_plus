<?php
/**
 *
 * @package Fence Plus
 * @subpackage
 * @since
 */

require_once "includes/classes/class-fencer.php";

$ratings = array(
	'foil'  => array(
		'letter' => "B",
		'weapon' => "Foil"
	),
	'epee'  => array(
		'letter' => "A",
		'weapon' => "Epee"
	),
	'saber' => array(
		'letter' => "B",
		'weapon' => "Saber"
	),
);

var_dump( Fence_Plus_Fencer::calculate_primary_wepaon( $ratings ) );