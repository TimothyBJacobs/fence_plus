<?php
/**
 *
 * @package Fence Plus
 * @subpackage
 * @since
 */
class Fence_Plus_Event {

	/**
	 * @var int event ID
	 */
	private $id;
	/**
	 * @var int tournament ID
	 */
	private $tournament_id;
	/**
	 * @var string tournament name
	 */
	private $tournament;
	/**
	 * @var string event name
	 */
	private $full_name;
	/**
	 * @var string weapon "Foil"|"Epee"|"Saber"
	 */
	private $weapon;
	/**
	 * @var string "Men"|"Women"
	 */
	private $gender;
	/**
	 * @var string tournament authority "USFA"|"FIE"
	 */
	private $authority;
	/**
	 * @var string event age limit
	 * @see https://sites.google.com/a/countersix.com/fred-rest-api/request-response-reference
	 */
	private $age_limit;
	/**
	 * @var string rating limit
	 * @see https://sites.google.com/a/countersix.com/fred-rest-api/request-response-reference
	 */
	private $rating_limit;
	/**
	 * @var string todo find out how different from rating prediction
	 */
	private $rating;
	/**
	 * @var string prediction of event's rating based on current registrations
	 */
	private $rating_prediction;
	/**
	 * @var int number of pre-registrations todo find out difference from prereg_count
	 */
	private $entries;
	/**
	 * @var int number of fencers pre-registered for this event
	 */
	private $prereg_count;
	/**
	 * @var bool is this a team event
	 */
	private $is_team;
	/**
	 * @var string event description
	 */
	private $description;
	/**
	 * @var string ISO 8601 close of registration date
	 */
	private $close_of_reg;
	/**
	 * @var string event fee
	 */
	private $fee;
	/**
	 * @var array
	 *  askFRED fencer ID => Fence_Plus_Fencer objectt5
	 */
	private $preregs = array();

}
