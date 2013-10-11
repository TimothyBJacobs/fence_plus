<?php
/**
 *
 * @package Fence Plus
 * @subpackage Fencer Profile Pages
 * @since 0.1
 */

class Fence_Plus_Profile_Overview {
	/**
	 * @var Fence_Plus_Fencer
	 */
	public $fencer;

	/**
	 * @param Fence_Plus_Fencer $fencer
	 */
	public function __construct( Fence_Plus_Fencer $fencer ) {
		$this->fencer = $fencer;
		$this->render();
	}

	/**
	 * Render the profile page
	 */
	public function render() {
		$primary_weapon_field = "";

		if ( array() == $this->fencer->get_primary_weapon() ) {
			$primary_weapon_field .= "<select id='fence_plus_primary_weapon' name='fence_plus_primary_weapon'>";
			$primary_weapon_field .= "<option></option>";
			$primary_weapon_field .= "<option value='Epee'>Epee</option>";
			$primary_weapon_field .= "<option value='Foil'>Foil</option>";
			$primary_weapon_field .= "<option value='Saber'>Saber</option>";
			$primary_weapon_field .= "</select>";
		}
		else {
			$primary_weapon_field = ibd_implode_with_word( $this->fencer->get_primary_weapon(), 'and' );
		}

		$fields = "<tr><th>" . __( 'First Name', Fence_Plus::SLUG ) . "</th><td>" . $this->fencer->get_first_name() . "</td></tr>";
		$fields .= "<tr><th>" . __( 'Last Name', Fence_Plus::SLUG ) . "</th><td>" . $this->fencer->get_last_name() . "</td></tr>";
		$fields .= "<tr><th>" . __( 'Year of Birth', Fence_Plus::SLUG ) . "</th><td>" . $this->fencer->get_birthyear() . "</td></tr>";
		$fields .= "<tr><th>" . __( 'Gender', Fence_Plus::SLUG ) . "</th><td>" . $this->fencer->get_gender() . "</td></tr>";
		$fields .= "<tr><th>" . __( 'Primary Weapon', Fence_Plus::SLUG ) . "</th><td>" . $primary_weapon_field . "</td></tr>";
		$fields .= "<tr><th>" . __( 'Epee Rating', Fence_Plus::SLUG ) . "</th><td>" . $this->fencer->get_epee_letter() . $this->fencer->get_epee_year() . "</td></tr>";
		$fields .= "<tr><th>" . __( 'Foil Rating', Fence_Plus::SLUG ) . "</th><td>" . $this->fencer->get_foil_letter() . $this->fencer->get_foil_year() . "</td></tr>";
		$fields .= "<tr><th>" . __( 'Saber Rating', Fence_Plus::SLUG ) . "</th><td>" . $this->fencer->get_saber_letter() . $this->fencer->get_saber_year() . "</td></tr>";
		$fields .= "<tr><th>" . __( 'USFA ID', Fence_Plus::SLUG ) . "</th><td>" . $this->fencer->get_usfa_id() . "</td></tr>";

		echo "<table>$fields</table>";
	}
}