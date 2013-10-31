<?php
/**
 *
 * @package Fence Plus
 * @subpackage
 * @since
 */

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * Class Fence_Plus_Fencer_List_Table
 */
class Fence_Plus_Fencer_List_Table extends WP_List_Table {
	/**
	 *
	 */
	function __construct() {
		global $status, $page;

		//Set parent defaults
		parent::__construct( array(
				'singular' => __( 'fencer', Fence_Plus::SLUG ),
				'plural'   => __( 'fencers', Fence_Plus::SLUG ),
				'ajax'     => false
			)
		);
	}

	/**
	 * Populate data in the columns
	 *
	 * @param $item
	 * @param $column_name
	 *
	 * @return string
	 */
	function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'name':
			case 'primary_weapon' :
			case 'epee_rating' :
			case 'foil_rating':
			case 'saber_rating' :
				return $item[$column_name];
			default:
				return "";
		}
	}

	/**
	 * Render the checkbox for bulk actions
	 *
	 * @param $item
	 *
	 * @return string
	 */
	function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="%1$s[]" value="%2$s" />',
			/*$1%s*/
			$this->_args['singular'], //Let's simply repurpose the table's singular label ("movie")
			/*$2%s*/
			$item['ID'] //The value of the checkbox should be the record's id
		);
	}

	/**
	 * Render column titles
	 *
	 * @return array
	 */
	function get_columns() {
		$columns = array(
			'cb'             => '<input type="checkbox" />', //Render a checkbox instead of text
			'name'           => __( "Fencer's Name", Fence_Plus::SLUG ),
			'primary_weapon' => __( 'Primary Weapon', Fence_Plus::SLUG ),
			'epee_rating'    => __( 'Epee', Fence_Plus::SLUG ),
			'foil_rating'    => __( 'Foil', Fence_Plus::SLUG ),
			'saber_rating'   => __( 'Saber', Fence_Plus::SLUG )
		);

		return apply_filters( 'fence_plus_fencer_list_table_columns', $columns );
	}

	/**
	 * Determine what columns should be sortable
	 *
	 * @return array
	 */
	function get_sortable_columns() {
		$sortable_columns = array(
			'name'           => array( 'name', false ), //true means it's already sorted
			'primary_weapon' => array( 'primary_weapon', false ),
			'epee_rating'    => array( 'epee_rating', false ),
			'foil_rating'    => array( 'foil_rating', false ),
			'saber_rating'   => array( 'saber_rating', false )
		);

		return apply_filters( 'fence_plus_fencer_list_table_sortable_columns', $sortable_columns );
	}

	/**
	 * Add bulk actions to the bulk actions list
	 *
	 * @return mixed|void
	 */
	function get_bulk_actions() {
		if ( current_user_can( 'edit_users' ) ) {

			$coaches = Fence_Plus_Utility::get_all_coaches();

			$actions = array();

			foreach ( $coaches as $coach ) {
				$actions[$coach->ID] = $coach->display_name;
			}

		}
		else {
			$actions = array();
		}

		return apply_filters( 'fence_plus_fencer_list_table_bulk_actions', $actions );
	}

	/**
	 * Process bulk actions.
	 *
	 * Runs on every page load. Variables are passed as $_GET parameters
	 */
	function process_bulk_action() {
		if ( ! isset( $_GET['action'] ) )
			return;

		do_action( 'fence_plus_fencer_list_table_process_bulk_actions', $_GET['action'] );

		$coach_id = (int) urldecode( $_GET['action'] );

		try {
			$coach = new Fence_Plus_Coach( $coach_id );
		}
		catch ( InvalidArgumentException $e ) {
			return;
		}

		foreach ( $_GET['fencer'] as $fencer_id ) {
			try {
				$fencer = Fence_Plus_Fencer::wp_id_db_load( $fencer_id );
				$fencer->add_coach( $_GET['action'] );
				$fencer->save();

				$coach->add_fencer( $fencer_id );
			}
			catch ( InvalidArgumentException $e ) {
				continue;
			}
		}
		$coach->save();

		$num_fencers = count( $_GET['fencer'] );

		$message = sprintf( _n( '%1$d fencer assigned to %2$s.', '%1$d fencers assigned to %2$s.', $num_fencers, Fence_Plus::SLUG ), $num_fencers, $coach->display_name );
		Fence_Plus_Utility::add_admin_notification( $message, 'updated' );
	}

	/**
	 * Prepare the table for being rendered.
	 *
	 * Is responsible for all the logic.
	 */
	function prepare_items() {
		/**
		 * First, lets decide how many records per page to show
		 */
		$user = get_current_user_id();
		$screen = get_current_screen();
		$screen_option = $screen->get_option( 'per_page', 'option' );
		$per_page = get_user_meta( $user, $screen_option, true );

		if ( empty ( $per_page ) || $per_page < 1 ) {
			$per_page = $screen->get_option( 'per_page', 'default' );
		}

		/**
		 * REQUIRED. Now we need to define our column headers. This includes a complete
		 * array of columns to be displayed (slugs & titles), a list of columns
		 * to keep hidden, and a list of columns that are sortable. Each of these
		 * can be defined in another method (as we've done here) before being
		 * used to build the value for our _column_headers property.
		 */
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();

		/**
		 * REQUIRED. Finally, we build an array to be used by the class for column
		 * headers. The $this->_column_headers property takes an array which contains
		 * 3 other arrays. One for all columns, one for hidden columns, and one
		 * for sortable columns.
		 */
		$this->_column_headers = array( $columns, $hidden, $sortable );

		/**
		 * Optional. You can handle your bulk actions however you see fit. In this
		 * case, we'll handle them within our package just to keep things clean.
		 */
		$this->process_bulk_action();

		/**
		 * Instead of querying a database, we're going to fetch the example data
		 * property we created for use in this plugin. This makes this example
		 * package slightly different than one you might build on your own. In
		 * this example, we'll be using array manipulation to sort and paginate
		 * our data. In a real-world implementation, you will probably want to
		 * use sort and pagination data to build a custom query instead, as you'll
		 * be able to use your precisely-queried data immediately.
		 */
		$data = array();

		$search = isset( $_GET['s'] ) ? $_GET['s'] : "";
		$args = array(
			'role'   => 'fencer',
			'search' => $search,
			'fields' => 'all_with_meta'
		);

		try {
			$coach = new Fence_Plus_Coach( get_current_user_id() );
			$args['include'] = $coach->get_fencers();
		}
		catch ( InvalidArgumentException $e ) {
			// current user isn't a coach, so we can just show all fencers, since we already checked for permissions to be on this page
		}

		if ( '' !== $args['search'] )
			$args['search'] = '*' . $args['search'] . '*';

		$fencer_query = new WP_User_Query( $args );

		if ( $fencer_query->get_total() > 0 ) {

			$fencers = $fencer_query->get_results();

			foreach ( $fencers as $fencer_user ) {
				$fencer = Fence_Plus_Fencer::wp_id_db_load( $fencer_user->ID );

				$primary_weapons = $fencer->get_primary_weapon();

				$data[] = apply_filters( 'fence_plus_fencer_list_table_data', array(
						'name'           => '<a href="' . add_query_arg( array( 'fence_plus_fencer_data' => '1', 'fp_id' => $fencer->get_wp_id() ), get_edit_user_link( $fencer->get_wp_id() ) ) . '">' .
						  $fencer_user->display_name . "</a>",
						'primary_weapon' => empty( $primary_weapons ) ? "" : ibd_implode_with_word( $primary_weapons, 'and' ),
						'foil_rating'    => $fencer->get_foil_letter() . $fencer->get_foil_year(),
						'epee_rating'    => $fencer->get_epee_letter() . $fencer->get_epee_year(),
						'saber_rating'   => $fencer->get_saber_letter() . $fencer->get_saber_year(),
						'ID'             => $fencer->get_wp_id()
					),
					$fencer->get_wp_id()
				);
			}
		}

		/**
		 * This checks for sorting input and sorts the data in our array accordingly.
		 */
		function usort_reorder( $a, $b ) {
			$orderby = ( ! empty( $_REQUEST['orderby'] ) ) ? $_REQUEST['orderby'] : 'name'; //If no sort, default to title
			$order = ( ! empty( $_REQUEST['order'] ) ) ? $_REQUEST['order'] : 'asc'; //If no order, default to asc
			$result = strcmp( $a[$orderby], $b[$orderby] ); // Determine sort order

			if ( $orderby == 'foil_rating' || $orderby == 'epee_rating' || $orderby == 'saber_rating' )
				$result = strcmp( substr( $a[$orderby], 0, 1 ) . ( 3000 - (int) substr( $a[$orderby], 1 ) ),
				  substr( $b[$orderby], 0, 1 ) . ( 3000 - (int) substr( $b[$orderby], 1 ) )
				);

			return ( $order === 'asc' ) ? $result : - $result; // Send final sort direction to usort
		}

		usort( $data, 'usort_reorder' );

		/**
		 * REQUIRED for pagination. Let's figure out what page the user is currently
		 * looking at. We'll need this later, so you should always include it in
		 * your own package classes.
		 */
		$current_page = $this->get_pagenum();

		/**
		 * REQUIRED for pagination. Let's check how many items are in our data array.
		 * In real-world use, this would be the total number of items in your database,
		 * without filtering. We'll need this later, so you should always include it
		 * in your own package classes.
		 */
		$total_items = count( $data );

		/**
		 * The WP_List_Table class does not handle pagination for us, so we need
		 * to ensure that the data is trimmed to only the current page. We can use
		 * array_slice() to do that.
		 */
		$data = array_slice( $data, ( ( $current_page - 1 ) * $per_page ), $per_page );

		/**
		 * REQUIRED. Now we can add our *sorted* data to the items property, where
		 * it can be used by the rest of the class.
		 */
		$this->items = $data;

		/**
		 * REQUIRED. We also have to register our pagination options & calculations.
		 */
		$this->set_pagination_args( array(
				'total_items' => $total_items, //WE have to calculate the total number of items
				'per_page'    => $per_page, //WE have to determine how many items to show on a page
				'total_pages' => ceil( $total_items / $per_page ) //WE have to calculate the total number of pages
			)
		);
	}
}

function fence_plus_fencers_list_page() {
	//Create an instance of our package class...
	$fencer_list_table = new Fence_Plus_Fencer_List_Table();
	//Fetch, prepare, sort, and filter our data...
	$fencer_list_table->prepare_items();

	Fence_Plus_Utility::display_admin_notification();

	?>

	<style type="text/css">
		#epee_rating, #saber_rating, #foil_rating,
		.epee_rating, .saber_rating, .foil_rating {
			width:      70px;
			text-align: center;
		}
	</style>

	<div class="wrap">
        <h2><?php _e( "Fencers", Fence_Plus::SLUG ); ?>
	        <?php if ( current_user_can( 'create_users' ) ) : ?>
		        <a href="user-new.php" class="add-new-h2"><?php _e( "Add New" ); ?></a>
	        <?php endif; ?>
	        <?php if ( isset( $_GET['s'] ) && trim( $_GET['s'] ) != "" ) : ?>
		        <span class="subtitle"><?php printf( __( 'Search results for “%s”', Fence_Plus::SLUG ), $_GET['s'] ); ?></span>
	        <?php endif; ?>
		</h2>

        <form id="fencer-list-table-filter" method="get">
            <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>"/>
	        <?php $fencer_list_table->search_box( "Search", 'fence_plus_search' ); ?>
	        <?php $fencer_list_table->display() ?>
        </form>
    </div>

<?php
}