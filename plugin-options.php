<?php

/**
 * This function introduces the plugin options into the 'Appearance' menu and into a top-level
 * 'Fence Plus Theme' menu.
 */
function fence_plus_plugin_menu() {

	/*add_plugin_page(
		'Fence Plus', 					        // The title to be displayed in the browser window for this page.
		'Fence Plus',					        // The text to be displayed for this menu item
		'administrator',					    // Which type of users can see this menu item
		'fence_plus_plugin_options',			// The unique ID - that is, the slug - for this menu item
		'fence_plus_plugin_display'				// The name of the function to call when rendering this menu's page
	);*/

	add_menu_page(
		'Fence Plus', // The value used to populate the browser's title bar when the menu page is active
		'Fence Plus', // The text of the menu in the administrator's sidebar
		'administrator', // What roles are able to access the menu
		'fence_plus_plugin_menu', // The ID used to bind submenu items to this menu
		'fence_plus_plugin_display' // The callback function used to render this menu
	);

	/*add_submenu_page(
		'fence_plus_plugin_menu',				        // The ID of the top-level menu page to which this submenu item belongs
		__( 'Club Information', 'fence_plus' ),			// The value used to populate the browser's title bar when the menu page is active
		__( 'Club Information', 'fence_plus' ),			// The label of this submenu item displayed in the menu
		'administrator',					            // What roles are able to access this submenu item
		'fence_plus_plugin_club_options',	            // The ID used to represent this submenu item
		'fence_plus_plugin_display'				        // The callback function used to render the options for this submenu item
	); */

	add_submenu_page(
		'fence_plus_plugin_menu',
		__( 'Fencer Options', 'fence_plus' ),
		__( 'Fencer Options', 'fence_plus' ),
		'administrator',
		'fence_plus_plugin_fencer_options',
		create_function( null, 'fence_plus_plugin_display( "fencer_options" );' )
	);

	add_submenu_page(
		'fence_plus_plugin_menu',
		__( 'Club Information', 'fence_plus' ),
		__( 'Club Information', 'fence_plus' ),
		'administrator',
		'fence_plus_plugin_coach_options',
		create_function( null, 'fence_plus_plugin_display( "coach_options" );' )
	);

} // end fence_plus_plugin_menu
add_action( 'admin_menu', 'fence_plus_plugin_menu' );

/**
 * Renders a simple page to display for the plugin menu defined above.
 */
function fence_plus_plugin_display( $active_tab = '' ) {
	?>
	<!-- Create a header in the default WordPress 'wrap' container -->
	<div class="wrap">

    <div id="icon-plugins" class="icon32"></div>
    <h2><?php _e( 'Fence Plus Options', 'fence_plus' ); ?></h2>
		<?php settings_errors(); ?>

		<?php if ( isset( $_GET['tab'] ) ) {
			$active_tab = $_GET['tab'];
		}
		else if ( $active_tab == 'fencer_options' ) {
			$active_tab = 'fencer_options';
		}
		else if ( $active_tab == 'coach_options' ) {
			$active_tab = 'coach_options';
		}
		else {
			$active_tab = 'club_options';
		} // end if/else ?>

		<h2 class="nav-tab-wrapper">
        <!--<a href="?page=fence_plus_plugin_menu" class="nav-tab <?php /*echo $active_tab == 'club_options' ? 'nav-tab-active' : ''; */?>"><?php /*_e( 'Club Information', 'fence_plus' ); */?></a>-->
        <a href="?page=fence_plus_plugin_fencer_options" class="nav-tab <?php echo $active_tab == 'fencer_options' ? 'nav-tab-active' : ''; ?>"><?php _e( 'Fencer Options', 'fence_plus' ); ?></a>
        <a href="?page=fence_plus_plugin_coach_options" class="nav-tab <?php echo $active_tab == 'coach_options' ? 'nav-tab-active' : ''; ?>"><?php _e( 'Club Information', 'fence_plus' ); ?></a>
    </h2>

    <form method="post" action="options.php">
        <?php

	    if ( $active_tab == 'club_options' ) {

		    settings_fields( 'fence_plus_plugin_club_options' );
		    do_settings_sections( 'fence_plus_plugin_club_options' );

	    }
	    elseif ( $active_tab == 'fencer_options' ) {

		    settings_fields( 'fence_plus_plugin_fencer_options' );
		    do_settings_sections( 'fence_plus_plugin_fencer_options' );

	    }
	    else {

		    settings_fields( 'fence_plus_plugin_coach_options' );
		    do_settings_sections( 'fence_plus_plugin_coach_options' );

	    } // end if/else

	    submit_button();

	    ?>
    </form>

</div><!-- /.wrap -->
<?php
} // end fence_plus_plugin_display

/* ------------------------------------------------------------------------ *
 * Setting Registration
 * ------------------------------------------------------------------------ */

/**
 * Provides default values for the Fencer Options.
 */
function fence_plus_plugin_default_fencer_options() {

	$defaults = array(
		'clean_profile'       => '1',
		'import_fencers'      => '0',
		'usfa_id'             => '',
		'wipe_fencers'        => '0',
		'no_tournaments_text' => 'There weren\'t any tournaments found.',
		'tournament_distance' => 300
	);

	return apply_filters( 'fence_plus_plugin_default_fencer_options', $defaults );

} // end fence_plus_plugin_default_fencer_options

/**
 * Provides default values for the Club Information.
 */
/*function fence_plus_plugin_default_club_options() {
	
	$defaults = array(
		'clean_profile'		=>	'1'

	);
	
	return apply_filters( 'fence_plus_plugin_default_club_options', $defaults );
	
} // end fence_plus_plugin_default_club_options*/

/**
 * Provides default values for the Input Options.
 */
function fence_plus_plugin_default_input_options() {

	$defaults = array(
		'club_name'                => '',
		'club_address'             => '',
		'zip_code'                 => '',
		'custom_event'             => '0',
		'custom_event_title'       => '',
		'custom_event_description' => '',
		'custom_event_date'        => '',
		'custom_event_url'         => ''

	);

	return apply_filters( 'fence_plus_plugin_default_input_options', $defaults );

} // end fence_plus_plugin_default_input_options

/**
 * Initializes the plugin's display options page by registering the Sections,
 * Fields, and Settings.
 *
 * This function is registered with the 'admin_init' hook.
 */
/*function fence_plus_initialize_plugin_options() {

	// If the plugin options don't exist, create them.
	if( false == get_option( 'fence_plus_plugin_club_options' ) ) {	
		add_option( 'fence_plus_plugin_club_options', apply_filters( 'fence_plus_plugin_default_club_options', fence_plus_plugin_default_club_options() ) );
	} // end if

	// First, we register a section. This is necessary since all future options must belong to a 
	add_settings_section(
		'general_settings_section',			// ID used to identify this section and with which to register options
		__( 'Club Information', 'fence_plus' ),		// Title to be displayed on the administration page
		'fence_plus_general_options_callback',	// Callback used to render the description of the section
		'fence_plus_plugin_club_options'		// Page on which to add this section of options
	);
	
	// Next, we'll introduce the fields for toggling the visibility of content elements.
	add_settings_field(
		'club_address',						// ID used to identify the field throughout the plugin
		__( 'Street Address', 'fence_plus' ),							// The label to the left of the option interface element
		'fence_plus_club_address_callback',	// The name of the function responsible for rendering the option interface
		'fence_plus_plugin_club_options',	// The page on which this option will be displayed
		'general_settings_section',			// The name of the section to which this field belongs
		array(								// The array of arguments to pass to the callback. In this case, just a description.
			__( 'Leave out city and state', 'fence_plus' ),
		)
	);

    add_settings_field(
        'club_zip_code',
        __( 'Zip Code', 'fence_plus' ),
        'fence_plus_zip_code_callback',
        'fence_plus_plugin_club_options',
        'general_settings_section'
    );

    add_settings_field(
        'club_name',
        __( 'Club Name', 'fence_plus' ),
        'fence_plus_club_name_callback',
        'fence_plus_plugin_club_options',
        'general_settings_section'
    );
	
	// Finally, we register the fields with WordPress
	register_setting(
		'fence_plus_plugin_club_options',
		'fence_plus_plugin_club_options',
        'fence_plus_plugin_validate_club_information'
	);

} // end fence_plus_initialize_plugin_options
add_action( 'admin_init', 'fence_plus_initialize_plugin_options' );*/

/**
 * Initializes the plugin's social options by registering the Sections,
 * Fields, and Settings.
 *
 * This function is registered with the 'admin_init' hook.
 */
function fence_plus_plugin_intialize_fencer_options() {

	if ( false == get_option( 'fence_plus_plugin_fencer_options' ) ) {
		add_option( 'fence_plus_plugin_fencer_options', apply_filters( 'fence_plus_plugin_default_fencer_options', fence_plus_plugin_default_fencer_options() ) );
	} // end if

	add_settings_section(
		'fencer_options_section', // ID used to identify this section and with which to register options
		__( 'Fencer Options', 'fence_plus' ), // Title to be displayed on the administration page
		'fence_plus_fencer_options_callback', // Callback used to render the description of the section
		'fence_plus_plugin_fencer_options' // Page on which to add this section of options
	);

	add_settings_section(
		'fencer_options_import_section', // ID used to identify this section and with which to register options
		__( 'Import Fencers', 'fence_plus' ), // Title to be displayed on the administration page
		'fence_plus_fencer_options_import_callback', // Callback used to render the description of the section
		'fence_plus_plugin_fencer_options' // Page on which to add this section of options
	);

	add_settings_field(
		'clean_profile',
		'Cleanup Fencer Profiles',
		'fence_plus_clean_profile_callback',
		'fence_plus_plugin_fencer_options',
		'fencer_options_section'
	);

	add_settings_field(
		'tournament_distance',
		'Tournament Distance',
		'fence_plus_tournament_distance_callback',
		'fence_plus_plugin_fencer_options',
		'fencer_options_section'
	);

	add_settings_field(
		'no_tournaments_text',
		'No Tournaments Found Text',
		'fence_plus_no_tournaments_text_callback',
		'fence_plus_plugin_fencer_options',
		'fencer_options_section'
	);

	add_settings_field(
		'usfa_id',
		'USFA ID',
		'fence_plus_usfa_id_callback',
		'fence_plus_plugin_fencer_options',
		'fencer_options_import_section'
	);

	add_settings_field(
		'import_fencers',
		'Import Fencers?',
		'fence_plus_import_fencers_callback',
		'fence_plus_plugin_fencer_options',
		'fencer_options_import_section'
	);

	add_settings_field(
		'wipe_fencers',
		'Wipe all Fencers?',
		'fence_plus_wipe_fencers_callback',
		'fence_plus_plugin_fencer_options',
		'fencer_options_import_section'
	);

	register_setting(
		'fence_plus_plugin_fencer_options',
		'fence_plus_plugin_fencer_options',
		'fence_plus_plugin_validate_fencer_options'
	);

} // end fence_plus_plugin_intialize_fencer_options
add_action( 'admin_init', 'fence_plus_plugin_intialize_fencer_options' );

/**
 * Initializes the plugin's input example by registering the Sections,
 * Fields, and Settings. This particular group of options is used to demonstration
 * validation and sanitization.
 *
 * This function is registered with the 'admin_init' hook.
 */
function fence_plus_plugin_initialize_coach_options() {

	if ( false == get_option( 'fence_plus_plugin_coach_options' ) ) {
		add_option( 'fence_plus_plugin_coach_options', apply_filters( 'fence_plus_plugin_default_input_options', fence_plus_plugin_default_input_options() ) );
	} // end if

	add_settings_section(
		'coach_options_section',
		__( 'Club Information', 'fence_plus' ),
		'fence_plus_coach_options_callback',
		'fence_plus_plugin_coach_options'
	);

	add_settings_section(
		'coach_options_custom_event_section',
		__( 'Custom Event', 'fence_plus' ),
		'fence_plus_coach_options_custom_event_callback',
		'fence_plus_plugin_coach_options'
	);

	add_settings_field(
		'club_name',
		__( 'Club Name', 'fence_plus' ),
		'fence_plus_input_element_callback',
		'fence_plus_plugin_coach_options',
		'coach_options_section'
	);

	add_settings_field(
		'street_address',
		__( 'Street Address', 'fence_plus' ),
		'fence_plus_club_address_callback',
		'fence_plus_plugin_coach_options',
		'coach_options_section'
	);

	add_settings_field(
		'zip_code',
		__( 'Zip Code', 'fence_plus' ),
		'fence_plus_zip_code_callback',
		'fence_plus_plugin_coach_options',
		'coach_options_section'
	);

	add_settings_field(
		'custom_event',
		__( 'Custom Event', 'fence_plus' ),
		'fence_plus_custom_event_callback',
		'fence_plus_plugin_coach_options',
		'coach_options_custom_event_section'
	);

	add_settings_field(
		'custom_event_title',
		__( 'Custom Event Title', 'fence_plus' ),
		'fence_plus_custom_event_title_callback',
		'fence_plus_plugin_coach_options',
		'coach_options_custom_event_section'
	);

	add_settings_field(
		'custom_event_description',
		__( 'Custom Event Description', 'fence_plus' ),
		'fence_plus_custom_event_description_callback',
		'fence_plus_plugin_coach_options',
		'coach_options_custom_event_section'
	);

	add_settings_field(
		'custom_event_date',
		__( 'Custom Event Date', 'fence_plus' ),
		'fence_plus_custom_event_date_callback',
		'fence_plus_plugin_coach_options',
		'coach_options_custom_event_section'
	);

	add_settings_field(
		'custom_event_url',
		__( 'Custom Event URL', 'fence_plus' ),
		'fence_plus_custom_event_url_callback',
		'fence_plus_plugin_coach_options',
		'coach_options_custom_event_section'
	);
	/*
		add_settings_field(
			'Checkbox Element',
			__( 'Checkbox Element', 'fence_plus' ),
			'fence_plus_checkbox_element_callback',
			'fence_plus_plugin_coach_options',
			'coach_options_section'
		);

		add_settings_field(
			'Radio Button Elements',
			__( 'Radio Button Elements', 'fence_plus' ),
			'fence_plus_radio_element_callback',
			'fence_plus_plugin_coach_options',
			'coach_options_section'
		);

		add_settings_field(
			'Select Element',
			__( 'Select Element', 'fence_plus' ),
			'fence_plus_select_element_callback',
			'fence_plus_plugin_coach_options',
			'coach_options_section'
		);*/

	register_setting(
		'fence_plus_plugin_coach_options',
		'fence_plus_plugin_coach_options',
		'fence_plus_plugin_validate_coach_options'
	);

} // end fence_plus_plugin_initialize_coach_options
add_action( 'admin_init', 'fence_plus_plugin_initialize_coach_options' );

/* ------------------------------------------------------------------------ *
 * Section Callbacks
 * ------------------------------------------------------------------------ */

/**
 * This function provides a simple description for the General Options page.
 *
 * It's called from the 'fence_plus_initialize_plugin_options' function by being passed as a parameter
 * in the add_settings_section function.
 */
/*function fence_plus_general_options_callback() {
	echo '<p>' . __( 'We need some details about your club so we can function properly.', 'fence_plus' ) . '</p>';
} // end fence_plus_general_options_callback*/

/**
 * This function provides a simple description for the Fencer Options page.
 *
 * It's called from the 'fence_plus_plugin_intialize_fencer_options' function by being passed as a parameter
 * in the add_settings_section function.
 */
function fence_plus_fencer_options_callback() {
	echo '<p>' . __( 'Some options for your fencers.', 'fence_plus' ) . '</p>';
	if ( isset( $_GET['importing'] ) ) {
		echo "<div id='message' class='updated highlight'><p><b>We are currently importing all of the fencers, you can go ahead and leave this page and the process will continue uninterrupted.</b></p></div>";
	}
} // end fence_plus_general_options_callback

function fence_plus_fencer_options_import_callback() {
	$options = get_option( 'fence_plus_plugin_coach_options' );
	$club_name = $options['club_name'];
	echo '<p>' . __( 'Imports and creates a user for each fencer who has listed ' . $club_name . ' as their primary club on askFRED.</p>
    <p>Each fencer\'s username is their Firstname Lastname as listed on askFRED and their password is their USFA ID and can be changed when they login.</p>', 'fence_plus'
	) . '</p>';
} // end fence_plus_general_options_callback

/**
 * This function provides a simple description for the Coach Options page.
 *
 * It's called from the 'fence_plus_plugin_intialize_coach_options_options' function by being passed as a parameter
 * in the add_settings_section function.
 */
function fence_plus_coach_options_callback() {
	echo '<p>' . __( 'We need some details about your club so everything can work properly.', 'fence_plus' ) . '</p>';
} // end fence_plus_general_options_callback

function fence_plus_coach_options_custom_event_callback() {
	echo '<p>' . __( 'Options for displaying a custom event for all fencers.', 'fence_plus' ) . '</p>';
} // end fence_plus_general_options_callback

/* ------------------------------------------------------------------------ *
 * Field Callbacks
 * ------------------------------------------------------------------------ */

/**
 * This function renders the interface elements for toggling the visibility of the header element.
 *
 * It accepts an array or arguments and expects the first element in the array to be the description
 * to be displayed next to the checkbox.
 */
/*function fence_plus_toggle_header_callback($args) {

	// First, we read the options collection
	$options = get_option('fence_plus_plugin_club_options');

	// Next, we update the name attribute to access this element's ID in the context of the display options array
	// We also access the club_address element of the options collection in the call to the checked() helper function
	$html = '<input type="text" id="club_address" name="fence_plus_plugin_coach_options[club_address]" value="' . $options['club_address'] . '" />';

	// Here, we'll take the first argument of the array and add it to a label next to the checkbox
	$html .= '<label for="club_address">&nbsp;'  . $args[0] . '</label>';

	echo $html;

} // end fence_plus_toggle_header_callback

function fence_plus_toggle_content_callback($args) {

	$options = get_option('fence_plus_plugin_club_options');

	$html = '<input type="text" id="club_zip_code" name="fence_plus_plugin_coach_options[club_zip_code]" value="' . $options['club_zip_code'] . '" />';
	//$html .= '<label for="club_zip_code">&nbsp;'  . $args[0] . '</label>';

	echo $html;

} // end fence_plus_toggle_content_callback

function fence_plus_toggle_footer_callback($args) {

	$options = get_option('fence_plus_plugin_club_options');

	$html = '<input type="text" id="club_name" name="fence_plus_plugin_coach_options[club_name]" value="' . $options['club_name'] . '" />';
	//$html .= '<label for="club_name">&nbsp;'  . $args[0] . '</label>';

	echo $html;

} // end fence_plus_toggle_footer_callback

function fence_plus_twitter_callback() {

	// First, we read the social options collection
	$options = get_option( 'fence_plus_plugin_fencer_options' );

	// Next, we need to make sure the element is defined in the options. If not, we'll set an empty string.
	$url = '';
	if( isset( $options['twitter'] ) ) {
		$url = esc_url( $options['twitter'] );
	} // end if

	// Render the output
	echo '<input type="text" id="twitter" name="fence_plus_plugin_fencer_options[twitter]" value="' . $url . '" />';

} // end fence_plus_twitter_callback

function fence_plus_facebook_callback() {

	$options = get_option( 'fence_plus_plugin_fencer_options' );

	$url = '';
	if( isset( $options['facebook'] ) ) {
		$url = esc_url( $options['facebook'] );
	} // end if

	// Render the output
	echo '<input type="text" id="facebook" name="fence_plus_plugin_fencer_options[facebook]" value="' . $url . '" />';

} // end fence_plus_facebook_callback

function fence_plus_googleplus_callback() {

	$options = get_option( 'fence_plus_plugin_fencer_options' );

	$url = '';
	if( isset( $options['googleplus'] ) ) {
		$url = esc_url( $options['googleplus'] );
	} // end if

	// Render the output
	echo '<input type="text" id="googleplus" name="fence_plus_plugin_fencer_options[googleplus]" value="' . $url . '" />';

} // end fence_plus_googleplus_callback */

function fence_plus_input_element_callback() {

	$options = get_option( 'fence_plus_plugin_coach_options' );

	// Render the output
	echo '<input type="text" id="club_name" name="fence_plus_plugin_coach_options[club_name]" value="' . $options['club_name'] . '" />';
}

function fence_plus_zip_code_callback() {

	$options = get_option( 'fence_plus_plugin_coach_options' );

	// Render the output
	echo '<input type="text" id="zip_code" name="fence_plus_plugin_coach_options[zip_code]" placeholder="10039" value="' . $options['zip_code'] . '" />';
}

function fence_plus_club_address_callback() {

	$options = get_option( 'fence_plus_plugin_coach_options' );

	// Render the output
	echo '<input type="text" id="club_address" name="fence_plus_plugin_coach_options[club_address]" placeholder="154 Orange Pit Lane" value="' . $options['club_address'] . '" />';
	echo '<label for="club_address">&nbsp;Make sure this is the same address that you use for your tournaments at askFRED</label>';

} // end fence_plus_input_element_callback

function fence_plus_clean_profile_callback() {

	$options = get_option( 'fence_plus_plugin_fencer_options' );

	$html = '<input type="checkbox" id="clean_profile" name="fence_plus_plugin_fencer_options[clean_profile]" value="1"' . checked( 1, $options['clean_profile'], false ) . '/>';
	$html .= '&nbsp;';
	$html .= '<label for="clean_profile">Removes dashboard widgets and unnecessary settings for all fencers</label>';

	echo $html;

} // end fence_plus_checkbox_element_callback

function fence_plus_tournament_distance_callback() {

	$options = get_option( 'fence_plus_plugin_fencer_options' );

	// Render the output
	echo '<input type="number" id="tournament_distance" name="fence_plus_plugin_fencer_options[tournament_distance]" placeholder="300" value="' . $options['tournament_distance'] . '" />';
	echo '<label for="tournament_distance">&nbsp;Distance in miles from the club to look for tournaments, maximum: 999.</label>';

} // end fence_plus_tournament_distance_callback

function fence_plus_no_tournaments_text_callback() {

	$options = get_option( 'fence_plus_plugin_fencer_options' );

	// Render the output
	echo '<textarea id="no_tournaments_text" name="fence_plus_plugin_fencer_options[no_tournaments_text]" rows="5" cols="50">' . $options['no_tournaments_text'] . '</textarea>';

} // end fence_plus_no_tournaments_text_callback

function fence_plus_import_fencers_callback() {

	$options = get_option( 'fence_plus_plugin_fencer_options' );

	$html = '<input type="checkbox" id="import_fencers" name="fence_plus_plugin_fencer_options[import_fencers]" value="1"' . checked( 1, $options['import_fencers'], false ) . '/>';
	$html .= '&nbsp;';
	$html .= '<label for="import_fencers">You can check this box again to re-import your fencers, no duplicates will be created.</label>';

	echo $html;

} // end fence_plus_checkbox_element_callback

function fence_plus_wipe_fencers_callback() {

	$options = get_option( 'fence_plus_plugin_fencer_options' );

	$html = '<input type="checkbox" id="wipe_fencers" name="fence_plus_plugin_fencer_options[wipe_fencers]" value="1"' . checked( 1, $options['wipe_fencers'], false ) . '/>';
	$html .= '&nbsp;';
	$html .= '<label for="wipe_fencers">CAUTION: Checking this will wipe all fencers. </label>';

	echo $html;

} // end fence_plus_checkbox_element_callback

function fence_plus_usfa_id_callback() {

	$options = get_option( 'fence_plus_plugin_fencer_options' );

	$html = '<input type="text" id="usfa_id" name="fence_plus_plugin_fencer_options[usfa_id]" value="' . $options['usfa_id'] . '" />';
	$html .= '&nbsp;';
	$html .= '<label for="usfa_id">Enter a USFA ID of any fencer who has your club listed as their primary club.</label>';

	echo $html;

} // end fence_plus_checkbox_element_callback

/*********Club Information**********/
/*
function fence_plus_club_address_callback() {

    $options = get_option( 'fence_plus_plugin_club_information' );

    // Render the output
    echo '<input type="text" id="club_address" name="fence_plus_plugin_coach_options[club_address]" value="' . $options['club_address'] . '" />';

} // end fence_plus_club_address_callback

function fence_plus_zip_code_callback() {

    $options = get_option( 'fence_plus_plugin_club_information' );

    // Render the output
    echo '<input type="text" id="club_zip_code" name="fence_plus_plugin_coach_options[club_zip_code]" value="' . $options['club_zip_code'] . '" />';

} // end fence_plus_zip_code_callback

function fence_plus_club_name_callback() {

    $options = get_option( 'fence_plus_plugin_club_information' );

    // Render the output
    echo '<input type="text" id="club_name" name="fence_plus_plugin_coach_options[club_name]" value="' . $options['club_name'] . '" />';

} // end fence_plus_club_name_callback*/

/**************/

function fence_plus_custom_event_callback() {

	$options = get_option( 'fence_plus_plugin_coach_options' );

	$html = '<input type="checkbox" id="custom_event" name="fence_plus_plugin_coach_options[custom_event]" value="1"' . checked( 1, $options['custom_event'], false ) . '/>';
	$html .= '&nbsp;';
	$html .= '<label for="custom_event">Displays a custom event for all fencers</label>';

	echo $html;
}

function fence_plus_custom_event_title_callback() {

	$options = get_option( 'fence_plus_plugin_coach_options' );

	// Render the output
	echo '<input type="text" id="custom_event_title" name="fence_plus_plugin_coach_options[custom_event_title]" size="49" value="' . $options['custom_event_title'] . '" />';

} // end fence_plus_input_element_callback

function fence_plus_custom_event_description_callback() {

	$options = get_option( 'fence_plus_plugin_coach_options' );

	// Render the output
	echo '<textarea id="custom_event_description" name="fence_plus_plugin_coach_options[custom_event_description]" rows="5" cols="50">' . $options['custom_event_description'] . '</textarea>';

} // end fence_plus_textarea_element_callback

function fence_plus_custom_event_date_callback() {

	$options = get_option( 'fence_plus_plugin_coach_options' );

	// Render the output
	echo '<input type="date" id="custom_event_date" name="fence_plus_plugin_coach_options[custom_event_date]" placeholder="2013-05-27" value="' . $options['custom_event_date'] . '" />';
	echo '<label for="custom_event_date">&nbsp;Format: YYYY-MM-DD</label>';

} // end fence_plus_input_element_callback

function fence_plus_custom_event_url_callback() {

	$options = get_option( 'fence_plus_plugin_coach_options' );

	// Render the output
	echo '<input type="url" id="custom_event_url" name="fence_plus_plugin_coach_options[custom_event_url]" value="' . $options['custom_event_url'] . '" />';

} // end fence_plus_input_element_callback

/*function fence_plus_checkbox_element_callback() {

	$options = get_option( 'fence_plus_plugin_coach_options' );

	$html = '<input type="checkbox" id="checkbox_example" name="fence_plus_plugin_coach_options[checkbox_example]" value="1"' . checked( 1, $options['checkbox_example'], false ) . '/>';
	$html .= '&nbsp;';
	$html .= '<label for="checkbox_example">This is an example of a checkbox</label>';

	echo $html;

} // end fence_plus_checkbox_element_callback

function fence_plus_radio_element_callback() {

	$options = get_option( 'fence_plus_plugin_coach_options' );

	$html = '<input type="radio" id="radio_example_one" name="fence_plus_plugin_coach_options[radio_example]" value="1"' . checked( 1, $options['radio_example'], false ) . '/>';
	$html .= '&nbsp;';
	$html .= '<label for="radio_example_one">Option One</label>';
	$html .= '&nbsp;';
	$html .= '<input type="radio" id="radio_example_two" name="fence_plus_plugin_coach_options[radio_example]" value="2"' . checked( 2, $options['radio_example'], false ) . '/>';
	$html .= '&nbsp;';
	$html .= '<label for="radio_example_two">Option Two</label>';

	echo $html;

} // end fence_plus_radio_element_callback

function fence_plus_select_element_callback() {

	$options = get_option( 'fence_plus_plugin_coach_options' );

	$html = '<select id="time_options" name="fence_plus_plugin_coach_options[time_options]">';
		$html .= '<option value="default">' . __( 'Select a time option...', 'fence_plus' ) . '</option>';
		$html .= '<option value="never"' . selected( $options['time_options'], 'never', false) . '>' . __( 'Never', 'fence_plus' ) . '</option>';
		$html .= '<option value="sometimes"' . selected( $options['time_options'], 'sometimes', false) . '>' . __( 'Sometimes', 'fence_plus' ) . '</option>';
		$html .= '<option value="always"' . selected( $options['time_options'], 'always', false) . '>' . __( 'Always', 'fence_plus' ) . '</option>';	$html .= '</select>';

	echo $html;

} // end fence_plus_radio_element_callback*/

/* ------------------------------------------------------------------------ *
 * Setting Callbacks
 * ------------------------------------------------------------------------ */

/**
 * Sanitization callback for the social options. Since each of the social options are text inputs,
 * this function loops through the incoming option and strips all tags and slashes from the value
 * before serializing it.
 *
 * @params    $input    The unsanitized collection of options.
 *
 * @returns            The collection of sanitized values.
 */
/*function fence_plus_plugin_validate_club_information( $input ) {

    // Create our array for storing the validated options
    $output = array();

    // Loop through each of the incoming options
    foreach( $input as $key => $value ) {

        // Check to see if the current option has a value. If so, process it.
        if( isset( $input[$key] ) ) {

            // Strip all HTML and PHP tags and properly handle quoted strings
            $output[$key] = strip_tags( stripslashes( $input[ $key ] ) );

        } // end if

    } // end foreach

    // Return the array processing any additional functions filtered by this action
    return apply_filters( 'fence_plus_plugin_validate_club_information', $output, $input );

} // end fence_plus_plugin_validate_club_information*/

/*function fence_plus_plugin_sanitize_fencer_options( $input ) {
	
	// Define the array for the updated options
	$output = array();

	// Loop through each of the options sanitizing the data
	foreach( $input as $key => $val ) {
	
		if( isset ( $input[$key] ) ) {
			$output[$key] = esc_url_raw( strip_tags( stripslashes( $input[$key] ) ) );
		} // end if	
	
	} // end foreach
	
	// Return the new collection
	return apply_filters( 'fence_plus_plugin_sanitize_fencer_options', $output, $input );

} // end fence_plus_plugin_sanitize_fencer_options */

function fence_plus_plugin_validate_fencer_options( $input ) {

	// Create our array for storing the validated options
	$output = array();

	// Loop through each of the incoming options
	foreach ( $input as $key => $value ) {
		if ( isset( $input['import_fencers'] ) ) {
			if ( $input['import_fencers'] == 1 ) {
				delete_transient( 'AF_USFA_IDS_KEY' );
			}
		}

		// Check to see if the current option has a value. If so, process it.
		if ( isset( $input[$key] ) ) {

			// Strip all HTML and PHP tags and properly handle quoted strings
			$output[$key] = strip_tags( stripslashes( $input[$key] ) );

		} // end if

	} // end foreach

	// Return the array processing any additional functions filtered by this action
	return apply_filters( 'fence_plus_plugin_validate_fencer_options', $output, $input );

} // end fence_plus_plugin_validate_coach_options

function fence_plus_plugin_validate_coach_options( $input ) {

	delete_transient( 'AF_CLUB_TOURNAMENT_KEY' );
	// Create our array for storing the validated options
	$output = array();

	// Loop through each of the incoming options
	foreach ( $input as $key => $value ) {

		// Check to see if the current option has a value. If so, process it.
		if ( isset( $input[$key] ) ) {

			// Strip all HTML and PHP tags and properly handle quoted strings
			$output[$key] = strip_tags( stripslashes( $input[$key] ) );

		} // end if

	} // end foreach

	// Return the array processing any additional functions filtered by this action
	return apply_filters( 'fence_plus_plugin_validate_coach_options', $output, $input );

} // end fence_plus_plugin_validate_coach_options

?>