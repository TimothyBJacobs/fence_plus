<?php
/**
 *
 * @package Fence Plus
 * @subpackage
 * @since
 */

class Fence_Plus_User_Table {

	/**
	 *
	 */
	public function __construct() {
		add_filter( 'manage_users_columns', array( $this, 'add_columns' ) );
		add_filter( 'manage_users_custom_column', array( $this, 'modify_rows' ), 10, 3 );

		add_action( 'admin_head', array( $this, 'add_css' ) );
	}

	/**
	 * @param $column
	 *
	 * @return mixed
	 */
	public function add_columns( $column ) {
		$column['epee_rating'] = 'Epee';
		$column['foil_rating'] = 'Foil';
		$column['saber_rating'] = 'Saber';
		$column['usfa_id'] = 'USFA ID';
		unset( $column['name'] );

		return $column;
	}

	/**
	 * @param $val
	 * @param $column_name
	 * @param $user_id
	 *
	 * @return string
	 */
	public function modify_rows( $val, $column_name, $user_id ) {
		require_once( FENCEPLUS_INCLUDES_CLASSES_DIR . 'class-fencer.php' );
		try {
			$fencer = Fence_Plus_Fencer::wp_id_db_load( $user_id );
		} catch ( InvalidArgumentException $e ){
			return;
		}

		$output = "";

		switch ( $column_name ) {
			case 'foil_rating' :
				if ( "U" == $letter = $fencer->get_foil_letter() )
					$output = "U";
				else
					$output = $letter . $fencer->get_foil_year();
				break;

			case 'epee_rating' :
				if ( "U" == $letter = $fencer->get_epee_letter() )
					$output = "U";
				else
					$output = $letter . $fencer->get_epee_year();
				break;

			case 'saber_rating' :
				if ( "U" == $letter = $fencer->get_saber_letter() )
					$output = "U";
				else
					$output = $letter . $fencer->get_saber_year();
				break;

			case 'usfa_id' :
				$output = $fencer->get_usfa_id();
				break;
		}

		return $output;
	}

	/**
	 *
	 */
	public function add_css() {
		echo <<<EOT
			<style type="text/css">
				#epee_rating, #saber_rating, #foil_rating,
				.epee_rating, .saber_rating, .foil_rating  {
					width: 45px;
					text-align: center;
				}
				#usfa_id, .usfa_id {
					width: 75px;
					text-align: center;
				}
			</style>
EOT;

	}
}