<?php
/**
 *
 * @package Fence Plus
 * @subpackage
 * @since
 */

class Fence_Plus_User_Table {

	/**
	 * @var int holds WordPress Fencer user ID
	 */
	private $user_id;

	/**
	 *
	 */
	public function __construct() {
		add_filter( 'user_row_actions', array( $this, 'user_row_actions' ), 10, 2 );

		add_filter( 'manage_users_columns', array( $this, 'add_columns' ) );
		add_filter( 'manage_users_custom_column', array( $this, 'modify_rows' ), 10, 3 );

		add_action( 'admin_head', array( $this, 'add_css' ) );

		if ( isset( $_GET['user_id'] ) ) {
			$this->user_id = $_GET['user_id'];
		}
		else if ( isset( $_GET['fp_id'] ) ) {
			$this->user_id = $_GET['fp_id'];
		}
		else {
			$this->user_id = get_current_user_id();
		}
	}

	/**
	 * Add fencer data row action
	 *
	 * @param $actions
	 * @param $user
	 *
	 * @return mixed
	 */
	public function user_row_actions( $actions, $user ) {
		if ( current_user_can( 'edit_user', $user->ID ) ) {
			if ( isset( $actions['delete'] ) ) {
				$delete = $actions['delete']; // grab the delete action so we can move it to the end of the array
				unset( $actions['delete'] );
			}

			if ( Fence_Plus_Fencer::is_fencer( $user ) ) {

				$query_args = array(
					'fence_plus_fencer_data' => 1
				);

				$actions['fence_plus_fencer'] = "<a href='" . add_query_arg( $query_args, get_edit_user_link( $user->ID ) ) . "'>" . __( 'Fencer Data', Fence_Plus::SLUG ) . "</a>";
			}
			else if ( Fence_Plus_Coach::is_coach( $user ) ) {
				$query_args = array(
					'fence_plus_coach_data' => 1
				);

				$actions['fence_plus_coach'] = "<a href='" . add_query_arg( $query_args, get_edit_user_link( $user->ID ) ) . "'>" . __( 'Coach Data', Fence_Plus::SLUG ) . "</a>";
			}

			if ( isset( $delete ) )
				$actions['delete'] = $delete;
		}

		return $actions;
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
		}
		catch ( InvalidArgumentException $e ) {
			return "";
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
					width: 70px;
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