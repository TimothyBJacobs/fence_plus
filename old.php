<?php

/************ Add User Meta Info **************/
function tj_fence_plus_display_fencer_field() {
	if ( current_user_can( 'view_tournaments' ) || current_user_can( 'delete_users' ) ) { // Get the user ID of Fencers, Coaches, or Admins
		$user = wp_get_current_user();
		$user_id = $user->ID;

		if ( current_user_can( 'delete_users' ) && isset( $_GET['user_id'] ) ) { // Get the ID if an admin is viewing another profile from the URL
			$user_id = $_GET['user_id'];
		}

		$usfaID = absint( get_user_meta( $user_id, 'tj_fence_plus_usfaID', true ) ); // Sanitize the USFAID

		if ( current_user_can( 'view_tournaments' ) == false ) {
			?> <!--If current user is not a fencer add a divider on the profile page-->

			<tr xmlns="http://www.w3.org/1999/html" xmlns="http://www.w3.org/1999/html">
            <th><b>Fencer Information</b></th>
        </tr>   <?php } ?>
		<tr>
        <th scope="row">USFA ID</th>
        <td>
            <form>
                <?php echo "<input id='tj_fence_plus_usfaID' name='tj_fence_plus_usfaID' type='text' value='$usfaID' />";?>
            </form>
        </td>
    </tr>
		<?php
		tj_fence_plus_personal_info_table_backend_profile(); // Display the data fetched from the API
	}
}

// Trigger this function on 'personal_options' action
add_action( 'personal_options', 'tj_fence_plus_display_fencer_field' );

// Monitor form submits and update user's setting if applicable
function tj_fence_plus_update_fencer_field() {
	$user = wp_get_current_user(); // Gets current user ID
	$user_id = $user->ID;

	if ( current_user_can( 'delete_users' ) && isset( $_POST['user_id'] ) ) { // If admin and viewing another profile page, get the USER ID from the URL
		$user_id = $_POST['user_id'];
	}

	$usfaID = absint( $_POST['tj_fence_plus_usfaID'] ); // Sanitize USFA ID
	$usfaID = substr( $usfaID, 0, 9 ); // Return only the first 9 characters, the length of a USFA ID

	update_user_meta( $user_id, 'tj_fence_plus_usfaID', $usfaID ); // Update user meta
	delete_transient( $user_id . 'AF_USFA_KEY' ); // Delete the transient when updated so correct values are displayed immiediately
}

add_action( 'personal_options_update', 'tj_fence_plus_update_fencer_field' );
add_action( 'edit_user_profile_update', 'tj_fence_plus_update_fencer_field' );

/****************Retrieve Tournament Information for Widget*****************/
function tj_fence_plus_ask_tournaments( $items, $all ) {

	$coach_options = get_option( 'fence_plus_plugin_coach_options' ); // Retrieve options from options page
	$fence_options = get_option( 'fence_plus_plugin_fencer_options' );
	$af_zip = $coach_options['zip_code']; // Get the Club Zip Code and addresss
	$af_address = urlencode( $coach_options['club_address'] );
	$lat = urlencode( substr( '40.815827', 0, 9 ) );
	$long = urlencode( substr( '-73.960088', 0, 9 ) );
	$radius = urlencode( substr( absint( $fence_options['tournament_distance'] ), 0, 3 ) );

	$af_date = urlencode( current_time( 'mysql' ) ); // Get the current time
	$af_api_key = urlencode( 'a8a854b2e3c3eac74bfda01f625182b8' ); // Encode API Key

	$af_per_page = 100; // How many results to show per page
	$page = 1; // Initialize $page as 1 to start browsing on the first page of results

	if ( $all == true ) {
		$af_get_url = "https://api.askfred.net/v1/tournament/?_api_key=" . $af_api_key . "&lat=" . $lat . "&long=" . $long . "&radius_mi=" . $radius . "&prereg_close_gte=" . $af_date . "&is_cancelled=0&_per_page=" . $af_per_page . "&_page=";
		if ( false !== $cache = get_transient( 'AF_ALL_TOURNAMENT_KEY' ) ) { // Check if their is cached data
			return $cache;
		}
	}
	else {
		$af_get_url = "https://api.askfred.net/v1/tournament/?_api_key=" . $af_api_key . "&address_contains=" . $af_address . "&zip=" . $af_zip . "&prereg_close_gte=" . $af_date . "&is_cancelled=0&_per_page=" . $af_per_page . "&_page=";

		if ( false !== $cache = get_transient( 'AF_CLUB_TOURNAMENT_KEY' ) ) { // Check if their is cached data
			return $cache;
		}
	}

	$af_get_request = $af_get_url . $page; // Append $page to allow function to automatically update pages

	$af_get_response = file_get_contents( $af_get_request ); // Get the content of the API request
	$af_decoded = json_decode( $af_get_response, true ); // decode the JSON
	$af_count = 1 + floor( $af_decoded['total_matched'] / $af_per_page ); // Get the total number of results and divide it by number per page, floor it and round up to get how many pages to cycle through
	$af_club_tournaments = array(); // Initialize tournaments array

	if ( $af_decoded['total_matched'] == 0 ) { // If there are no results found return an error
		return array(
			0 => array( 'error' => true )
		);
	}

	else {

		for ( $i = 1; $i <= $af_count; $i ++ ) { // For each page

			if ( $i > 1 ) { // If you are over the first page
				$af_get_request = $af_get_url . $page; // Get data on second page
				$af_get_response = file_get_contents( $af_get_request );
				$af_decoded = json_decode( $af_get_response, true );
			}

			$af_decoded_tournaments = ( $af_decoded['tournaments'] );
			$af_decoded_tournaments_length = count( $af_decoded_tournaments );

			if ( $items > $af_decoded_tournaments_length ) { // If there are more items than there are tournaments,
				$items = $af_decoded_tournaments_length; // Set the number of items equal to the number of tournaments
			}

			for ( $p = 0; $p < $items; $p ++ ) { // For each tournament listing

				$tournament_array = $af_decoded_tournaments[$p];

				if ( ! empty( $tournament_array['name'] ) && ! empty( $tournament_array['id'] ) && ! empty( $tournament_array['start_date'] ) && ! empty( $tournament_array['events'] ) ) { // If the required field are not empty
					$tournament_array_decoded = array( // Return an array with the following values.
						'name'        => $tournament_array['name'],
						'id'          => $tournament_array['id'],
						'start_date'  => $tournament_array['start_date'],
						'description' => $tournament_array['comments'],
						'url'         => "http://askfred.net/Events/moreInfo.php?tournament_id=" . $tournament_array['id'], // Get the permalink of the tournament URL
						'error'       => false,
						'events'      => $tournament_array['events']
					);
					array_push( $af_club_tournaments, $tournament_array_decoded ); // Add that result to the tournaments array
				}
			}
			$page ++; // Increment the page variable to allow next search to be on a new page
		}
		$cache = 86400; // Set cache time to 1 day
		if ( $all == false ) {
			set_transient( 'AF_CLUB_TOURNAMENT_KEY', $af_club_tournaments, $cache ); // Store data in a transient
		}
		else {
			set_transient( 'AF_ALL_TOURNAMENT_KEY', $af_club_tournaments, $cache ); // Store data in a transient
		}

		return $af_club_tournaments;
	}
}

/***************Up Coming Tournaments Widget**************/
// use widgets_init action hook to execute custom function
add_action( 'widgets_init', 'tj_fence_plus_register_widgets' );

define( 'AF_CLUB_TOURNAMENT_KEY', 'af_club_tournament_key' );

//register our widget
function tj_fence_plus_register_widgets() {
	register_widget( 'tj_fence_plus_widget_upcoming_tournaments' ); // Register upcoming tournament widget
}

//boj_widget_upcoming_tournaments class
class tj_fence_plus_widget_upcoming_tournaments extends WP_Widget {

//process the new widget
	function tj_fence_plus_widget_upcoming_tournaments() {
		$widget_ops = array(
			'classname'   => 'tj_fence_plus_widget_class', // establish widget class
			'description' => 'Let your fencers know about any upcoming tournaments at your club' // widget description
		);
		$this->WP_Widget( 'tj_fence_plus_widget_upcoming_tournaments', 'Upcoming Tournaments', $widget_ops );
	}

//build the widget settings form with defaults
	function form( $instance ) {
		$defaults = array( 'title' => 'Upcoming Tournaments @ NAFA', 'items' => '3', 'customize' => 1, 'description' => 1 );
		$instance = wp_parse_args( (array) $instance, $defaults );
		$title = $instance['title'];
		$items = $instance['items'];
		$description = $instance['description'];
		$customize = $instance['customize'];
		?>

		<p>Title: <input class="widefat" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>"/></p>
		<p>Tournaments to Display:
        <select name="<?php echo $this->get_field_name( 'items' ); ?>">
            <option value="1" <?php selected( $items, 1 ); ?>>1</option>
            <option value="2" <?php selected( $items, 2 ); ?>>2</option>
            <option value="3" <?php selected( $items, 3 ); ?>>3</option>
            <option value="4" <?php selected( $items, 4 ); ?>>4</option>
            <option value="5" <?php selected( $items, 5 ); ?>>5</option>
        </select>
    </p>
		<p>Show Description?:  <input name="<?php echo $this->get_field_name( 'description' ); ?>" type="checkbox" <?php checked( $description, 'on' ); ?> /></p>
		<p>Customize based on eligibility for tournament:  <input name="<?php echo $this->get_field_name( 'customize' ); ?>" type="checkbox" <?php checked( $customize, 'on' ); ?> /></p>
	<?php
	}

//save the widget settings
	function update( $new_instance, $old_instance ) { // sanitize the inputs and save
		$instance = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['items'] = strip_tags( $new_instance['items'] );
		$instance['description'] = strip_tags( $new_instance['description'] );
		$instance['customize'] = strip_tags( $new_instance['customize'] );

		return $instance;
	}

//display the widget
	function widget( $args, $instance ) {
		extract( $args );

		echo $before_widget;
		$options = get_option( 'fence_plus_plugin_fencer_options' );
		$title = apply_filters( 'widget_title', $instance['title'] );
		$items = empty( $instance['items'] ) ? 3 : $instance['items'];
		$description = empty( $instance['description'] ) ? 0 : 1;
		$none = $options['no_tournaments_text'];
		$customize = empty( $instance['customize'] ) ? 0 : 1;

		$items = intval( $items ); // validate that items is a number

		$any_tournament = false;

		$custom_event_show = false;
		$options = get_option( 'fence_plus_plugin_coach_options' );

		if ( isset( $options['custom_event'] ) ) {
			if ( $options['custom_event'] == 1 ) {
				$custom_event_show = true;
				$custom_event_title = $options['custom_event_title'];
				$custom_event_description = $options['custom_event_description'];
				$custom_event_url = $options['custom_event_url'];
				$custom_event_date = date_format( date_create( $options['custom_event_date'] ), "l, F d, Y" );
				$any_tournament = true;
				if ( time() > strtotime( $options['custom_event_date'] ) ) {
					$custom_event_show = false;
				}
			}
		}

		$results = tj_fence_plus_ask_tournaments( $items, false ); // Get the amount of tournaments requested from the widget
		$results_length = count( $results );

		if ( $items > $results_length ) {
			$items = $results_length;
		}

		/*******Check for Customized Tournaments*******/
		if ( $customize == 1 && is_user_logged_in() && tj_fence_plus_get_info( 'usfa_id' ) != null ) { // If the user is logged in, has a valid USFAID, and the widget wants customization
			$birthyear = tj_fence_plus_get_info( 'year' ); // Get birth year
			$fencer_age_limit = tj_fence_plus_get_age( $birthyear ); // Get the age bracket

			if ( ! empty( $title ) ) {
				echo $before_title . $title . $after_title;
			}
			; // If the widget title is not empty display it

			if ( $results[0]['error'] == true && $custom_event_show == false ) { // Check if there was an error
				echo '<p>' . $none . '</p>'; // If yes display the $none text
			}

			else {
				echo '<ul>';
				if ( $custom_event_show == true ) {
					?>
					<li>
                    <b style="font-size: 1.1em;"><a href="<?php echo $custom_event_url; ?>" target="_blank"><?php echo $custom_event_title; ?></a></b>
                    <ul>
                        <li><p style="margin-bottom: 5px !important;"><b>Date: </b><span><?php echo $custom_event_date; ?></span></p></li>
	                    <?php if ( $description == 1 ) { ?>
		                    <li><p style="margin-bottom: 5px !important;"><b>Description: </b><?php echo wp_trim_words( $custom_event_description, $num_words = 35 ); ?></p></li> <?php }?>
                    </ul>
                </li>

				<?php
				}

				for ( $m = 0; $m <= $items; $m ++ ) { // For each tournament

					$events_length = count( $results[$m]['events'] ); // Get how many events there are in each tournament

					for ( $j = 0; $j < $events_length; $j ++ ) { // For each event
						$event_age_limit = $results[$m]['events'][$j]['age_limit']; // Get the event age limit
						$event_rating_limit = strtolower( $results[$m]['events'][$j]['rating_limit'] ); // Get the event rating limit
						$fencer_rating_limit = tj_fence_plus_get_info( strtolower( $results[$m]['events'][$j]['weapon'] ) ); // Get the fencer rating limit by geting the weapon of the event and passing that to the get info funciton

						if ( substr( strtolower( $results[$m]['events'][$j]['gender'] ), - 1 ) == 'd' ) { // If the last letter of event gender is "d"
							$event_gender = 'mixed'; // Return mixed gender event
						}
						else {
							$event_gender = substr( strtolower( $results[$m]['events'][$j]['gender'] ), 0, 1 ); // If not, event gender is equal to the lower case first letter
						}

						$fencer_gender = strtolower( tj_fence_plus_get_info( 'gender' ) ); // Get the fencer gender

						if ( tj_fence_plus_check_age_limit( $fencer_age_limit, $event_age_limit, $fencer_rating_limit, $event_rating_limit, $fencer_gender, $event_gender ) == true ) { // Check if the fencer can qualify for the event
							$has_tournament = true; // if there is a tournament
							$any_tournament = true; // we check if there are any tournaments to display the $none text only once
							break;
						}
						else {
							$has_tournament = false; // If there are no tournaments set it to false, we don't set any tournament to false
						}

					}

					if ( $has_tournament == true ) {
						?> <!--If there is a tournament with an event the fencer can go to display it-->
						<li>
                        <b style="font-size: 1.1em;"><a href="<?php echo $results[$m]['url']; ?>" target="_blank"><?php echo $results[$m]['name']; ?></a></b>
                        <ul>
                            <li><p style="margin-bottom: 5px !important;"><b>Date: </b><span><?php echo date_format( date_create( $results[$m]['start_date'] ), "l, F d, Y" ); ?></span></p></li>
	                        <?php if ( $description == 1 ) { ?>
		                        <li><p style="margin-bottom: 5px !important;"><b>Description: </b><?php echo wp_trim_words( $results[$m]['description'], $num_words = 35 ); ?></p></li> <?php }?>
                        </ul>
                    </li>
					<?php
					}

				}

				if ( $any_tournament == false ) { // if there are no tournaments display the $none text.
					echo '<li><p>' . $none . '</p></li>';
				}

				?>

				</ul>
			<?php
			}
			echo $after_widget;
		}
		/*******End Customized Tournaments********/
		else { // If customized tournaments is not checked just display all tournaments
			if ( ! empty( $title ) ) {
				echo $before_title . $title . $after_title;
			}
			;

			if ( $results[0]['error'] == true && $custom_event_show == false ) {
				echo '<p>' . $none . '</p>';
			}
			else {
				if ( $custom_event_show == true ) {
					?>
					<ul>
					<li>
                        <b style="font-size: 1.1em;"><a href="<?php echo $custom_event_url; ?>" target="_blank"><?php echo $custom_event_title; ?></a></b>
                        <ul>
                            <li><p style="margin-bottom: 5px !important;"><b>Date: </b><span><?php echo $custom_event_date; ?></span></p></li>
	                        <?php if ( $description == 1 ) { ?>
		                        <li><p style="margin-bottom: 5px !important;"><b>Description: </b><?php echo wp_trim_words( $custom_event_description, $num_words = 35 ); ?></p></li> <?php }?>
                        </ul>
                    </li>
				<?php
				}
				for ( $m = 0; $m <= $items; $m ++ ) {
					?>
					<li>
                        <b style="font-size: 1.1em;"><a href="<?php echo $results[$m]['url']; ?>" target="_blank"><?php echo $results[$m]['name']; ?></a></b>
                        <ul>
                            <li><p style="margin-bottom: 5px !important;"><b>Date: </b><span><?php echo date_format( date_create( $results[$m]['start_date'] ), "l, F d, Y" ); ?></span></p></li>
	                        <?php if ( $description == 1 ) { ?>
		                        <li><p style="margin-bottom: 5px !important;"><b>Description: </b><?php echo wp_trim_words( $results[$m]['description'], $num_words = 35 ); ?></p></li> <?php }?>
                        </ul>
                    </li>
				<?php } ?>
				</ul>
			<?php
			}
			echo $after_widget;
		}
	}
}

/********Upcoming Tournaments Dashboard Widget**************/

add_action( 'wp_dashboard_setup', 'tj_fence_plus_club_dashboard_widget' );

function tj_fence_plus_club_dashboard_widget() {
	$options = get_option( 'fence_plus_plugin_coach_options' );
	if ( ! empty( $options['club_name'] ) ) {
		$club_name = $options['club_name'];
	}
	else {
		$club_name = "your club";
	}

//create a custom dashboard widget with a title that has the club name
	wp_add_dashboard_widget( 'tj_fence_plus_club_tournaments', 'Upcoming Tournaments at ' . $club_name, 'tj_fence_plus_club_dashboard_widget_display', 'tj_fence_plus_club_dashboard_widget_setup' );
}

function tj_fence_plus_club_dashboard_widget_setup() {
	$user = get_current_user();
	$user_id = $user->ID;

//check if option is set before saving
	if ( isset( $_POST['number_tournaments'] ) ) { // How many tounaments to display
//retrieve the option value from the form
		$number = absint( $_POST['number_tournaments'] );

//save the value as an option
//update_option( 'tj_fence_plus_number_tournaments', $number );
		update_user_meta( $user_id, 'tj_fence_plus_number_tournaments', $number );
	}

	if ( isset( $_POST['show_description'] ) ) { // Whether or not to show description
//retrieve the option value from the form
		$show_description = strip_tags( stripslashes( $_POST['show_description'] ) );

//save the value as an option
//update_option( 'tj_fence_plus_show_description', $show_description );
		update_user_meta( $user_id, 'tj_fence_plus_show_description', $show_description );
	}

//load the saved feed if it exists
	$number_tournaments = get_user_meta( $user_id, 'tj_fence_plus_number_tournaments', true );
	$show_description = get_user_meta( $user_id, 'tj_fence_plus_show_description', true );
	?>

	<label for="number_tournaments">
    How many tournaments to display: <input type="text" name="number_tournaments" id="number_tournaments" value="<?php echo $number_tournaments; ?>" size="1"/>
</label><br/>
	<label for="show_description">
    Show Description?:  <input name="show_description" id="show_description" type="checkbox" <?php checked( $show_description, 'on' ); ?> />
</label><br/>

<?php
}

function tj_fence_plus_club_dashboard_widget_display() {
	$user = get_current_user();
	$user_id = $user->ID;

//load our widget options
	$number = get_user_meta( $user_id, 'tj_fence_plus_number_tournaments', true );
	$show = get_user_meta( $user_id, 'tj_fence_plus_show_description', true );
	$options = get_option( 'fence_plus_plugin_fencer_options' );
	$none = $options['no_tournaments_text'];

//if option is empty set a default
	$items = ( $number ) ? $number : '3';
	$show_description = empty( $show ) ? 0 : 1;

	$birthyear = tj_fence_plus_get_info( 'year' );
	$fencer_age_limit = tj_fence_plus_get_age( $birthyear );

	$items = intval( $items );

	$results = tj_fence_plus_ask_tournaments( $items, false );
	$results_length = count( $results );

	$any_tournament = false;

	$custom_event_show = false;
	$options = get_option( 'fence_plus_plugin_coach_options' );

	if ( isset( $options['custom_event'] ) ) {
		if ( $options['custom_event'] == 1 ) {
			$custom_event_show = true;
			$custom_event_title = $options['custom_event_title'];
			$custom_event_description = $options['custom_event_description'];
			$custom_event_url = $options['custom_event_url'];
			$custom_event_date = date_format( date_create( $options['custom_event_date'] ), "l, F d, Y" );
			$any_tournament = true;
			if ( time() > strtotime( $options['custom_event_date'] ) ) {
				$custom_event_show = false;
			}
		}
	}

	if ( $items > $results_length ) {
		$items = $results_length;
	}

	if ( $results[0]['error'] == true ) {
		echo '<p>' . $none . '</p>';
	}

	else {

		if ( tj_fence_plus_get_info( 'usfa_id' ) != null ) {

			echo '<ul>';
			if ( $custom_event_show == true ) {
				?>
				<li>
                <a class="rsswidget" href="<?php if ( isset( $custom_event_url ) ) {
	                echo $custom_event_url;
                }
                else {
	                echo "#";
                } ?>" target="_blank">
                    <?php if ( isset( $custom_event_title ) ) {
		                echo $custom_event_title;
	                }
	                else {
		                echo "Club Wide Event";
	                }; ?></a><span style="color: #999; font-size: 12px; margin-left: 3px;">
                        <?php if ( isset( $custom_event_date ) ) {
							echo $custom_event_date;
						} ?></span>

					<?php if ( $show_description == 1 ) { ?>
						<div class="rssSummary"><?php if ( isset( $custom_event_description ) ) {
							echo $custom_event_description;
						} ?></div><?php }?>

            </li>

			<?php
			}
			for ( $m = 0; $m < $items; $m ++ ) {

				$events_length = count( $results[$m]['events'] );

				for ( $j = 0; $j < $events_length; $j ++ ) {
					$event_age_limit = strtolower( $results[$m]['events'][$j]['age_limit'] );
					$event_rating_limit = strtolower( $results[$m]['events'][$j]['rating_limit'] );
					$fencer_rating_limit = tj_fence_plus_get_info( strtolower( $results[$m]['events'][$j]['weapon'] ) );

					if ( substr( strtolower( $results[$m]['events'][$j]['gender'] ), - 1 ) == 'd' ) {
						$event_gender = 'mixed';
					}
					else {
						$event_gender = substr( strtolower( $results[$m]['events'][$j]['gender'] ), 0, 1 );
					}

					$fencer_gender = strtolower( tj_fence_plus_get_info( 'gender' ) );

					/* echo "Event Age Limit: ".$event_age_limit."<br />";
					 echo "Fencer Age Limit: ".$fencer_age_limit."<br />";
					 echo "Event Rating Limit: ".$event_rating_limit."<br />";
					 echo "Fencer Rating Limit: ".$fencer_rating_limit."<br />";
					 echo "Event Gender Limit: ".$event_gender."<br />";
					 echo "Fencer Gender Limit: ".$fencer_gender."<br />";
					 echo "________________________<br />";*/

					if ( tj_fence_plus_check_age_limit( $fencer_age_limit, $event_age_limit, $fencer_rating_limit, $event_rating_limit, $fencer_gender, $event_gender ) == true ) {
						$has_tournament = true;
						$any_tournament = true;
						break;
					}
					else {
						$has_tournament = false;
					}

				}

				if ( $has_tournament == true ) {
					?>
					<li>
                    <a class="rsswidget" href="<?php echo $results[$m]['url']; ?>" target="_blank"><?php echo $results[$m]['name']; ?></a><span style="color: #999; font-size: 12px; margin-left: 3px;"><?php echo date_format( date_create( $results[$m]['start_date'] ), "l, F d, Y" ); ?></span>

						<?php if ( $show_description == 1 ) { ?>
							<div class="rssSummary"><?php echo $results[$m]['description']; ?></div><?php }?>

                </li>
				<?php
				}
			}

			if ( $any_tournament == false ) {
				echo '<li><p>' . $none . '</p></li>';
			}

			?>

			</ul>
		<?php
		}

		else {

			if ( $results[0]['error'] == true ) {
				echo '<p>' . $none . '</p>';
			}
			else {
				?>
				<ul>
                <?php for ( $m = 0; $m <= $items; $m ++ ) { ?>
						<li>
                    <a class="rsswidget" href="<?php echo $results[$m]['url']; ?>" target="_blank"><?php echo $results[$m]['name']; ?></a><span style="color: #999; font-size: 12px; margin-left: 3px;"><?php echo date_format( date_create( $results[$m]['start_date'] ), "l, F d, Y" ); ?></span>

							<?php if ( $show_description == 1 ) { ?>
								<div class="rssSummary"><?php echo $results[$m]['description']; ?></div><?php }?>

                </li>
					<?php } ?>
            </ul>
			<?php
			}
		}
	}
}

/*************Club Tournaments Shortcode****************/
add_shortcode( 'club_tournaments', 'tj_fence_plus_club_tournaments_shortcode' );
function tj_fence_plus_club_tournaments_shortcode( $attr ) {
	if ( isset( $attr['customize'] ) ) {
		if ( $attr['customize'] == "yes" ) {
			$customize = true;
		}
	}
	else {
		$customize = false;
	}

	if ( isset( $attr['tournaments'] ) ) {
		$items = $attr['tournaments'];
	}
	else {
		$items = 3;
	}

	$birthyear = tj_fence_plus_get_info( 'year' );
	$fencer_age_limit = tj_fence_plus_get_age( $birthyear );
	$any_tournament = false;
	$custom_event_show = false;
	$coach_options = get_option( 'fence_plus_plugin_coach_options' );
	$options = get_option( 'fence_plus_plugin_fencer_options' );
	$none_text = $options['no_tournaments_text'];

	if ( isset( $coach_options['custom_event'] ) ) {
		if ( $coach_options['custom_event'] == 1 ) {
			$custom_event_show = true;
			$custom_event_title = $coach_options['custom_event_title'];
			$custom_event_description = $coach_options['custom_event_description'];
			$custom_event_url = $coach_options['custom_event_url'];
			$custom_event_date = date_format( date_create( $coach_options['custom_event_date'] ), "l, F d, Y" );
			$any_tournament = true;
			if ( time() > strtotime( $coach_options['custom_event_date'] ) ) {
				$custom_event_show = false;
			}
		}
	}

	if ( $custom_event_show == true ) {
		$items --;
	}

	$results = tj_fence_plus_ask_tournaments( $items, false ); // Get the amount of tournaments requested from the widget
	$results_length = count( $results );

	if ( $items > $results_length ) {
		$items = $results_length;
	}

	if ( $results[0]['error'] == true && $custom_event_show == false ) { // Check if there was an error
		echo '<p>' . $none_text . '</p>'; // If yes display the $none text
	}

	else {
		if ( $customize == true && is_user_logged_in() && tj_fence_plus_get_info( 'usfa_id' ) != null ) {
			?>

			<table>
            <thead>
            <tr>
                <th></th>
	            <?php if ( $custom_event_show == true ) { ?>
		            <th><?php echo $custom_event_title; ?></th>
	            <?php
	            }

	            for ( $m = 0; $m < $items; $m ++ ) {

		            $events_length = count( $results[$m]['events'] );

		            for ( $j = 0; $j < $events_length; $j ++ ) {
			            $event_age_limit = strtolower( $results[$m]['events'][$j]['age_limit'] );
			            $event_rating_limit = strtolower( $results[$m]['events'][$j]['rating_limit'] );
			            $fencer_rating_limit = tj_fence_plus_get_info( strtolower( $results[$m]['events'][$j]['weapon'] ) );

			            if ( substr( strtolower( $results[$m]['events'][$j]['gender'] ), - 1 ) == 'd' ) {
				            $event_gender = 'mixed';
			            }
			            else {
				            $event_gender = substr( strtolower( $results[$m]['events'][$j]['gender'] ), 0, 1 );
			            }

			            $fencer_gender = strtolower( tj_fence_plus_get_info( 'gender' ) );

			            if ( tj_fence_plus_check_age_limit( $fencer_age_limit, $event_age_limit, $fencer_rating_limit, $event_rating_limit, $fencer_gender, $event_gender ) == true ) {
				            $has_tournament = true;
				            $any_tournament = true;
				            break;
			            }
			            else {
				            $has_tournament = false;
			            }
		            }

		            if ( $any_tournament == false ) {
			            echo '<p>' . $none_text . '</p>';
			            break;
		            }

		            if ( $has_tournament == true ) {
			            ?>
			            <th><?php echo $results[$m]['name']; ?></th>
		            <?php
		            }
		            else {
			            break;
		            }
	            } ?>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>Date</td>
	            <?php if ( $custom_event_show == true ) { ?>
		            <td><?php echo $custom_event_date; ?></td>
	            <?php
	            }
	            for ( $m = 0; $m < $items; $m ++ ) {

		            $events_length = count( $results[$m]['events'] );

		            for ( $j = 0; $j < $events_length; $j ++ ) {
			            $event_age_limit = strtolower( $results[$m]['events'][$j]['age_limit'] );
			            $event_rating_limit = strtolower( $results[$m]['events'][$j]['rating_limit'] );
			            $fencer_rating_limit = tj_fence_plus_get_info( strtolower( $results[$m]['events'][$j]['weapon'] ) );

			            if ( substr( strtolower( $results[$m]['events'][$j]['gender'] ), - 1 ) == 'd' ) {
				            $event_gender = 'mixed';
			            }
			            else {
				            $event_gender = substr( strtolower( $results[$m]['events'][$j]['gender'] ), 0, 1 );
			            }

			            $fencer_gender = strtolower( tj_fence_plus_get_info( 'gender' ) );

			            if ( tj_fence_plus_check_age_limit( $fencer_age_limit, $event_age_limit, $fencer_rating_limit, $event_rating_limit, $fencer_gender, $event_gender ) == true ) {
				            $has_tournament = true;
				            $any_tournament = true;
				            break;
			            }
			            else {
				            $has_tournament = false;
			            }

		            }

		            if ( $has_tournament == true ) {
			            ?>
			            <td><?php echo date_format( date_create( $results[$m]['start_date'] ), "l, F d, Y" ); ?></td>
		            <?php
		            }
	            } ?>
            </tr>
            <tr>
                <td>Description</td>
	            <?php if ( $custom_event_show == true ) { ?>
		            <td><?php echo wp_trim_words( $custom_event_description, $num_words = 40 ); ?>
			            <br/>–&nbsp;<a href="<?php echo $custom_event_url; ?>" target="_blank">Learn more...</a></td>
	            <?php
	            }
	            for ( $m = 0; $m < $items; $m ++ ) {

		            $events_length = count( $results[$m]['events'] );

		            for ( $j = 0; $j < $events_length; $j ++ ) {
			            $event_age_limit = strtolower( $results[$m]['events'][$j]['age_limit'] );
			            $event_rating_limit = strtolower( $results[$m]['events'][$j]['rating_limit'] );
			            $fencer_rating_limit = tj_fence_plus_get_info( strtolower( $results[$m]['events'][$j]['weapon'] ) );

			            if ( substr( strtolower( $results[$m]['events'][$j]['gender'] ), - 1 ) == 'd' ) {
				            $event_gender = 'mixed';
			            }
			            else {
				            $event_gender = substr( strtolower( $results[$m]['events'][$j]['gender'] ), 0, 1 );
			            }

			            $fencer_gender = strtolower( tj_fence_plus_get_info( 'gender' ) );

			            if ( tj_fence_plus_check_age_limit( $fencer_age_limit, $event_age_limit, $fencer_rating_limit, $event_rating_limit, $fencer_gender, $event_gender ) == true ) {
				            $has_tournament = true;
				            $any_tournament = true;
				            break;
			            }
			            else {
				            $has_tournament = false;
			            }

		            }

		            if ( $has_tournament == true ) {
			            ?>
			            <td><?php echo wp_trim_words( $results[$m]['description'], 40 ); ?>
				            <br/>–&nbsp;<a href="<?php echo $results[$m]['url']; ?>" target="_blank">Learn more...</a></td>
		            <?php
		            }
	            } ?>
            </tr>
            </tbody>
        </table>

		<?php

		}
		else {
			?>

			<table>
            <thead>
            <tr>
                <th></th>
	            <?php if ( $custom_event_show == true ) { ?>
		            <th><?php echo $custom_event_title; ?></th>
	            <?php
	            }

	            for ( $m = 0; $m < $items; $m ++ ) {
		            ?>
		            <th><?php echo $results[$m]['name']; ?></th>
	            <?php } ?>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>Date</td>
	            <?php if ( $custom_event_show == true ) { ?>
		            <td><?php echo $custom_event_date; ?></td>
	            <?php
	            }
	            for ( $m = 0; $m < $items; $m ++ ) {
		            ?>
		            <td><?php echo date_format( date_create( $results[$m]['start_date'] ), "l, F d, Y" ); ?></td>
	            <?php } ?>
            </tr>
            <tr>
                <td>Description</td>
	            <?php if ( $custom_event_show == true ) { ?>
		            <td><?php echo wp_trim_words( $custom_event_description, $num_words = 40 ); ?>
			            <br/>–&nbsp;<a href="<?php echo $custom_event_url; ?>" target="_blank">Learn more...</a></td>
	            <?php
	            }
	            for ( $m = 0; $m < $items; $m ++ ) {
		            ?>
		            <td><?php echo wp_trim_words( $results[$m]['description'], 40 ); ?>
			            <br/>–&nbsp;<a href="<?php echo $results[$m]['url']; ?>" target="_blank">Learn more...</a></td>
	            <?php } ?>
            </tr>
            </tbody>
        </table>

		<?php
		}
	}
}

/**************Upcoming All Tournaments Dashboard Widget*****/
define( 'AF_ALL_TOURNAMENT_KEY', 'af_all_tournament_key' );
add_action( 'wp_dashboard_setup', 'tj_fence_plus_tournaments_dashboard_widget' );

function tj_fence_plus_tournaments_dashboard_widget() {

//create a custom dashboard widget with a title
	wp_add_dashboard_widget( 'tj_fence_plus_all_tournaments', 'Find a tournament on askFRED ', 'tj_fence_plus_tournaments_widget_display', 'tj_fence_plus_tournaments_dashboard_widget_setup' );
}

function tj_fence_plus_tournaments_dashboard_widget_setup() {

	$user = wp_get_current_user();
	$user_id = $user->ID;

//check if option is set before saving
	if ( isset( $_POST['all_number_tournaments'] ) ) { // How many tounaments to display

//retrieve the option value from the form
		$all_tournaments_number = absint( $_POST['all_number_tournaments'] );

//save the value as an option
		update_user_meta( $user_id, 'all_tournaments_number', $all_tournaments_number );
	}

	if ( isset( $_POST['all_show_description'] ) ) { // Whether or not to show description

//retrieve the option value from the form
		$all_tournaments_show = strip_tags( stripslashes( $_POST['all_show_description'] ) );

//save the value as an option
		update_user_meta( $user_id, 'all_tournaments_show', $all_tournaments_show );
	}

	$all_number_tournaments = get_user_meta( $user_id, 'all_tournaments_number', true );
	$all_show_description = get_user_meta( $user_id, 'all_tournaments_show', true );

	?>

	<label for="all_number_tournaments">
    How many tournaments to display: <input type="text" name="all_number_tournaments" id="all_number_tournaments" value="<?php echo $all_number_tournaments; ?>" size="1"/>
</label><br/>
	<label for="all_show_description">
    Show Description?:  <input name="all_show_description" id="all_show_description" type="checkbox" <?php checked( $all_show_description, 1 ); ?> />
</label><br/>


<?php
}

function tj_fence_plus_tournaments_widget_display() {
	$user = wp_get_current_user();
	$user_id = $user->ID;

//load our widget options$all_number = get_user_meta($user_id, 'all_tournaments_number', true);
	$all_show = get_user_meta( $user_id, 'all_tournaments_show', true );
	$all_number = get_user_meta( $user_id, 'all_tournaments_number', true );

//if option is empty set a default
	$all_items = ( $all_number ) ? $all_number : '3';
	$all_show_description = empty( $all_show ) ? 0 : 1;

	$options = get_option( 'fence_plus_plugin_fencer_options' );
	$all_none = $options['no_tournaments_text'];

	$birthyear = tj_fence_plus_get_info( 'year' );
	$fencer_age_limit = strtolower( tj_fence_plus_get_age( $birthyear ) );

	$all_items = intval( $all_items );
	$all_results = tj_fence_plus_ask_tournaments( 99, true );

	$all_results_length = count( $all_results );

	$any_tournament = false;

	if ( $all_items > $all_results_length ) {
		$all_items = $all_results_length;
	}

	if ( $all_results[0]['error'] == true ) {
		echo '<p>' . $all_none . '</p>';
	}

	else {

		if ( tj_fence_plus_get_info( 'usfa_id' ) != null ) {

			echo '<ul>';

			for ( $m = 0; $m <= $all_items; ) {

				$events_length = count( $all_results[$m]['events'] );

				for ( $j = 0; $j < $events_length; $j ++ ) {
					$event_age_limit = strtolower( $all_results[$m]['events'][$j]['age_limit'] );
					$event_rating_limit = strtolower( $all_results[$m]['events'][$j]['rating_limit'] );
					$fencer_rating_limit = tj_fence_plus_get_info( strtolower( $all_results[$m]['events'][$j]['weapon'] ) );

					if ( substr( strtolower( $all_results[$m]['events'][$j]['gender'] ), - 1 ) == 'd' ) {
						$event_gender = 'mixed';
					}
					else {
						$event_gender = substr( strtolower( $all_results[$m]['events'][$j]['gender'] ), 0, 1 );
					}

					$fencer_gender = strtolower( tj_fence_plus_get_info( 'gender' ) );

					/*echo "Event Age Limit: ".$event_age_limit."<br />";
					echo "Fencer Age Limit: ".$fencer_age_limit."<br />";
					echo "Event Rating Limit: ".$event_rating_limit."<br />";
					echo "Fencer Rating Limit: ".$fencer_rating_limit."<br />";
					echo "Event Gender Limit: ".$event_gender."<br />";
					echo "Fencer Gender Limit: ".$fencer_gender."<br />";
					echo $m;
					echo "<br />________________________<br />";*/

					if ( tj_fence_plus_check_age_limit( $fencer_age_limit, $event_age_limit, $fencer_rating_limit, $event_rating_limit, $fencer_gender, $event_gender ) == true ) {
						$has_tournament = true;
						$any_tournament = true;
						break;
					}
					else {
						$has_tournament = false;
					}

				}

				if ( $has_tournament == true ) {
					?>
					<li class="">
                    <a class="rsswidget" href="<?php echo $all_results[$m]['url']; ?>" target="_blank"><?php echo $all_results[$m]['name']; ?></a>&nbsp;<span style="color: #999; font-size: 12px; margin-left: 3px;"><?php echo date_format( date_create( $all_results[$m]['start_date'] ), "l, F d, Y" ); ?></span>

						<?php if ( $all_show_description == 1 ) { ?>
							<div class="rssSummary"><?php echo $all_results[$m]['description']; ?></div><?php }?>

                </li>
					<?php
					$m ++;
				}
				/*else {
					$m--;
				}*/
			}

			if ( $any_tournament == false ) {
				echo '<li><p>' . $all_none . '</p></li>';
			}

			?>

			</ul>
		<?php
		}

		else {

			if ( $all_results[0]['error'] == true ) {
				echo '<p>' . $all_none . '</p>';
			}
			else {
				?>
				<ul>
                <?php for ( $m = 0; $m < $all_items; $m ++ ) { ?>
						<li>
                    <a class="rsswidget" href="<?php echo $all_results[$m]['url']; ?>" target="_blank"><?php echo $all_results[$m]['name']; ?></a><span style="color: #999; font-size: 12px; margin-left: 3px;"><?php echo date_format( date_create( $all_results[$m]['start_date'] ), "l, F d, Y" ); ?></span>

							<?php if ( $all_show_description == 1 ) { ?>
								<div class="rssSummary"><?php echo $all_results[$m]['description']; ?></div><?php }?>

                </li>
					<?php } ?>
            </ul>
			<?php
			}
		}
	}
}

/*************All Tournaments Shortcode****************/
add_shortcode( 'all_tournaments', 'tj_fence_plus_all_tournaments_shortcode' );
function tj_fence_plus_all_tournaments_shortcode( $attr ) {
	if ( isset( $attr['customize'] ) ) {
		if ( $attr['customize'] == "yes" ) {
			$customize = true;
		}
	}
	else {
		$customize = false;
	}

	if ( isset( $attr['tournaments'] ) ) {
		$items = $attr['tournaments'];
	}
	else {
		$items = 10;
	}

	$birthyear = tj_fence_plus_get_info( 'year' );
	$fencer_age_limit = tj_fence_plus_get_age( $birthyear );
	$any_tournament = false;

	$options = get_option( 'fence_plus_plugin_fencer_options' );
	$none_text = $options['no_tournaments_text'];

	$results = tj_fence_plus_ask_tournaments( 100, true ); // Get the amount of tournaments requested from the widget
	$results_length = count( $results );

	if ( $items > $results_length ) {
		$items = $results_length;
	}

	if ( $results[0]['error'] == true ) { // Check if there was an error
		echo '<p>' . $none_text . '</p>'; // If yes display the $none text
	}

	else {
		if ( $customize == true && is_user_logged_in() && tj_fence_plus_get_info( 'usfa_id' ) != null ) {
			?>
			<table>
            <thead>
            <tr>
                <th class="fence-plus-table">Name</th>
                <th class="fence-plus-table">Date</th>
                <th>Description</th>
            </tr>
            </thead>
            <tbody>
                <?php for ( $m = 0; $m <= $items; $m ++ ) {

	                $events_length = count( $results[$m]['events'] );

	                for ( $j = 0; $j < $events_length; $j ++ ) {
		                $event_age_limit = strtolower( $results[$m]['events'][$j]['age_limit'] );
		                $event_rating_limit = strtolower( $results[$m]['events'][$j]['rating_limit'] );
		                $fencer_rating_limit = tj_fence_plus_get_info( strtolower( $results[$m]['events'][$j]['weapon'] ) );

		                if ( substr( strtolower( $results[$m]['events'][$j]['gender'] ), - 1 ) == 'd' ) {
			                $event_gender = 'mixed';
		                }
		                else {
			                $event_gender = substr( strtolower( $results[$m]['events'][$j]['gender'] ), 0, 1 );
		                }

		                $fencer_gender = strtolower( tj_fence_plus_get_info( 'gender' ) );

		                if ( tj_fence_plus_check_age_limit( $fencer_age_limit, $event_age_limit, $fencer_rating_limit, $event_rating_limit, $fencer_gender, $event_gender ) == true ) {
			                $has_tournament = true;
			                $any_tournament = true;
			                break;
		                }
		                else {
			                $has_tournament = false;
		                }
	                }
	                if ( $has_tournament == true ) {
		                ?>
		                <tr>
                    <td><b><?php echo $results[$m]["name"];?></b></td>
                    <td><?php echo date_format( date_create( $results[$m]['start_date'] ), "l, F d, Y" ); ?></td>
                    <td><?php if ( ! empty( $results[$m]['description'] ) ) {
		                    echo $results[$m]['description'];
		                    echo '<br />–&nbsp;<a href="' . $results[$m]['url'] . '" target="_blank">Learn more...</a>';
	                    }
	                    else {
		                    echo '<a href="' . $results[$m]['url'] . '" target="_blank">Learn more</a> about this tournament on askFRED.';
	                    }
	                    ?>
                    </td>
                </tr>
		                <?php
		                if ( $has_tournament == false ) {
			                $m --;
		                }
	                }
                } ?>
            </tbody>
        </table>
		<?php
		}
		else {
			?>
			<table>
            <thead>
            <tr>
                <th class="fence-plus-table">Name</th>
                <th class="fence-plus-table">Date</th>
                <th>Description</th>
            </tr>
            </thead>
            <tbody>
                <?php
                for ( $m = 0; $m <= $items; $m ++ ) {
	                ?>
	                <tr>
                    <td class=""><b><?php echo $results[$m]["name"];?></b></td>
                    <td><?php echo date_format( date_create( $results[$m]['start_date'] ), "l, F d, Y" ); ?></td>
                    <td><?php if ( ! empty( $results[$m]['description'] ) ) {
		                    echo $results[$m]['description'];
		                    echo '<br />–&nbsp;<a href="' . $results[$m]['url'] . '" target="_blank">Learn more...</a>';
	                    }
	                    else {
		                    echo '<a href="' . $results[$m]['url'] . '" target="_blank">Learn more</a> about this tournament on askFRED.';
	                    }
	                    ?>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
		<?php
		}
	}
}

/***************RETRIEVE FENCER INFORMATION*****************/
function tj_fence_plus_define() {
	if ( current_user_can( 'delete_users' ) && isset( $_GET['user_id'] ) ) {
		$user_id = $_GET['user_id'];
		define( $user_id . 'AF_USFA_KEY', 'af_usfa_key' );
	}

	else {
		$user = wp_get_current_user();
		$user_id = $user->ID;
		define( $user_id . 'AF_USFA_KEY', 'af_usfa_key' );
	}
}

add_action( 'init', 'tj_fence_plus_define' );

// askFRED API key

// Poll askFRED API
// Return array of (usfa_id, year, gender, foil, epee, saber), or false on error
function tj_fence_plus_ask_askFRED( $user_id = null, $af_id = null ) {
// Define the USFA ID
	$user = wp_get_current_user();
	$user_id = $user->ID;

	if ( current_user_can( 'delete_users' ) && isset( $_GET['user_id'] ) ) {
		$user_id = $_GET['user_id'];
	}

	$af_fencer_usfa_id = get_user_meta( $user_id, 'tj_fence_plus_usfaID', true );

	if ( $af_fencer_usfa_id == null ) {
		return $error_code = 3;
	}

	$af_get_request = "https://api.askfred.net/v1/fencer/?_api_key=a8a854b2e3c3eac74bfda01f625182b8&usfa_id=" . $af_fencer_usfa_id;
	$af_get_response = wp_remote_get( $af_get_request ); // Get askFRED response
	$af_get_response_body = wp_remote_retrieve_body( $af_get_response ); // Get JSON Object from the response

// Make sure the request was successful or return false
	if ( empty( $af_get_response ) ) {
		return false;
	}
	else {
		$af_decoded = json_decode( $af_get_response_body, true ); // Return array of get request

		if ( $af_decoded['total_matched'] == 0 && $af_decoded['fencers'] !== null ) {
			return $error_code = 1;
		}

		if ( $af_decoded['fencers'] == null ) {
			return $error_code = 2;
		}

		$af_decoded_length = count( $af_decoded['fencers'] );

		for ( $i = 0; $i < $af_decoded_length; $i ++ ) {
			$af_decoded = $af_decoded['fencers'][$i];

			if ( isset( $af_decoded['usfa_id'] ) ) {
				$usfa_id = $af_decoded['usfa_id'];
			}

			else {
				$usfa_id = null;
			}

			if ( isset( $af_decoded['first_name'] ) ) {
				$first = $af_decoded['first_name'];
			}

			else {
				$first = 'First';
			}

			if ( isset( $af_decoded['last_name'] ) ) {
				$last = $af_decoded['last_name'];
			}

			else {
				$last = 'Last';
			}

			if ( isset( $af_decoded['birthyear'] ) ) {
				$year = $af_decoded['birthyear'];
			}

			else {
				$year = 1990;
			}

			if ( isset( $af_decoded['gender'] ) ) {
				$gender = $af_decoded['gender'];
			}

			else {
				$gender = 'Unknown';
			}

			if ( isset( $af_decoded['usfa_ratings']['foil']['letter'] ) ) {
				$foil = $af_decoded['usfa_ratings']['foil']['letter'];
			}

			else {
				$foil = 'U';
			}

			if ( isset( $af_decoded['usfa_ratings']['epee']['letter'] ) ) {
				$epee = $af_decoded['usfa_ratings']['epee']['letter'];
			}

			else {
				$epee = 'U';
			}

			if ( isset( $af_decoded['usfa_ratings']['saber']['letter'] ) ) {
				$saber = $af_decoded['usfa_ratings']['saber']['letter'];
			}

			else {
				$saber = 'U';
			}

			if ( isset( $af_decoded['id'] ) ) {
				$id = $af_decoded['id'];
			}

			else {
				$id = 0;
			}

			return array(
				'usfa_id'    => $usfa_id,
				'first_name' => $first,
				'last_name'  => $last,
				'year'       => $year,
				'gender'     => $gender,
				'foil'       => $foil,
				'epee'       => $epee,
				'saber'      => $saber,
				'id'         => $id,
				'error_code' => null
			);
		}
	}
}

function tj_fence_plus_ask_askFRED_ids( $af_id ) {
	$ids = count( $af_id );
	$cycles = floor( $ids / 100 ) + 1;
	$af_decoded = array(); // Initialize the array

	if ( $ids > 100 ) {
		for ( $cycle = 0; $cycle < $cycles; $cycle ++ ) {
			$af_get_request = "https://api.askfred.net/v1/fencer/?_api_key=a8a854b2e3c3eac74bfda01f625182b8&_per_page=100&fencer_ids=";
			$ids_start = $cycle * 100;
			$af_id_max = array_slice( $af_id, $ids_start, 100, true );
			foreach ( $af_id_max as $id ) {
				$af_get_request = $af_get_request . $id . ",";
			}
			$af_get_request = substr( $af_get_request, 0, - 1 );
			$af_get_response = wp_remote_get( $af_get_request ); // Get askFRED response
			$af_get_response_body = wp_remote_retrieve_body( $af_get_response ); // Get JSON Object from the response
			$af_decoded_results = json_decode( $af_get_response_body, true );
			$af_decoded = array_merge( $af_decoded, $af_decoded_results['fencers'] );
		}
	}
	else {
		$af_get_request = "https://api.askfred.net/v1/fencer/?_api_key=a8a854b2e3c3eac74bfda01f625182b8&_per_page=100&fencer_ids=";
		foreach ( $af_id as $id ) {
			$af_get_request = $af_get_request . $id . ",";
		}
		$af_get_request = substr( $af_get_request, 0, - 1 ); // Remove last ","
		$af_get_response = wp_remote_get( $af_get_request );
		$af_get_response_body = wp_remote_retrieve_body( $af_get_response ); // Get JSON Object from the response
		$af_decoded = json_decode( $af_get_response_body, true );
		$af_decoded = $af_decoded['fencers'];
	}

	if ( $af_decoded == null ) {
		return $error_code = 2;
	}

	$af_decoded_length = count( $af_decoded );

	for ( $i = 0; $i < $af_decoded_length; $i ++ ) {

		if ( isset( $af_decoded[$i]['usfa_id'] ) ) {
			$usfa_id = $af_decoded[$i]['usfa_id'];
		}

		else {
			$usfa_id = null;
		}

		if ( isset( $af_decoded[$i]['first_name'] ) ) {
			$first = $af_decoded[$i]['first_name'];
		}

		else {
			$first = 'First';
		}

		if ( isset( $af_decoded[$i]['last_name'] ) ) {
			$last = $af_decoded[$i]['last_name'];
		}

		else {
			$last = 'Last';
		}

		if ( isset( $af_decoded[$i]['birthyear'] ) ) {
			$year = $af_decoded[$i]['birthyear'];
		}

		else {
			$year = 1990;
		}

		if ( isset( $af_decoded[$i]['gender'] ) ) {
			$gender = $af_decoded[$i]['gender'];
		}

		else {
			$gender = 'Unknown';
		}

		if ( isset( $af_decoded[$i]['usfa_ratings']['foil']['letter'] ) ) {
			$foil = $af_decoded[$i]['usfa_ratings']['foil']['letter'];
		}

		else {
			$foil = 'U';
		}

		if ( isset( $af_decoded[$i]['usfa_ratings']['epee']['letter'] ) ) {
			$epee = $af_decoded[$i]['usfa_ratings']['epee']['letter'];
		}

		else {
			$epee = 'U';
		}

		if ( isset( $af_decoded[$i]['usfa_ratings']['saber']['letter'] ) ) {
			$saber = $af_decoded[$i]['usfa_ratings']['saber']['letter'];
		}

		else {
			$saber = 'U';
		}

		if ( isset( $af_decoded[$i]['id'] ) ) {
			$id = $af_decoded[$i]['id'];
		}

		else {
			$id = $af_id[$i];
		}

		$return[$id] = array(
			'usfa_id'    => $usfa_id,
			'first_name' => $first,
			'last_name'  => $last,
			'year'       => $year,
			'gender'     => $gender,
			'foil'       => $foil,
			'epee'       => $epee,
			'saber'      => $saber,
			'id'         => $id,
			'error_code' => null
		);
	}

	return $return;
}

// Return array of personal fencer information, either from cache or fresh from API based on askFRED fencer_ids
function tj_fence_plus_get_info_ids( $af_ids, $af_id = null ) {
// first look for a cached result
	if ( false !== $cache = get_transient( 'AF_USFA_IDS_KEY' ) ) {
		if ( $af_id != null ) {
			return $cache[$af_id];
		}
		else {
			return $cache;
		}
	}

	$fresh = tj_fence_plus_ask_askFRED_ids( $af_ids );
	$cache = 86400; // Set cache time to 1 day

	if ( $fresh == 2 ) {
		$fresh = array(
			'usfa_id'    => 100093532,
			'first_name' => 'George',
			'last_name'  => 'Washington',
			'year'       => 1776,
			'gender'     => 'M',
			'foil'       => 'A',
			'epee'       => 'A',
			'saber'      => 'A',
			'id'         => 0,
			'error_code' => 2
		);
		$cache = 0;
	}

	set_transient( 'AF_USFA_IDS_KEY', $fresh, $cache );
	if ( $af_id != null ) {
		return $fresh[$af_id];
	}
	else {
		return $fresh;
	}
}

function tj_fence_plus_refresh_ids() {
	$af_ids = array();
	$args = array(
		'role' => 'fencer'
	);

	$fencers = get_users( $args );

	foreach ( $fencers as $fencer ) {
		$user_id = $fencer->ID;
		$af_id = get_user_meta( $user_id, 'tj_fence_plus_id', true );
		$af_ids[] = $af_id;
	}
	foreach ( $fencers as $fencer ) {
		$user_id = $fencer->ID;
		$af_id = get_user_meta( $user_id, 'tj_fence_plus_id', true );
		$fresh = tj_fence_plus_get_info_ids( $af_ids, $af_id );
		if ( ! isset( $fresh['error_code'] ) ) {
			$cache = 86400;
			set_transient( $user_id . 'AF_USFA_KEY', $fresh, $cache );
		}
	}
}

add_action( 'tj_fence_plus_cron_refresh_hook', 'tj_fence_plus_refresh_ids' );

function tj_fence_plus_refresh_ids_cron() {
	if ( ! wp_next_scheduled( 'tj_fence_plus_cron_refresh_hook' ) ) {
		wp_schedule_event( time(), 'daily', 'tj_fence_plus_cron_refresh_hook' );
	}
}

function tbj_fence_plus_importing_fencers() {
	if ( isset( $_GET['updating'] ) ) {
		if ( $_GET['updating'] == true ) {
			echo '<div class="updated highlight">
               <p>Your fencers are currently importing, in the meantime feel free to leave this page, the import will continue uninterrupted.</p>
            </div>';
		}
	}
}

add_action( 'admin_notices', 'tbj_fence_plus_importing_fencers' );

// Return array of personal fencer information, either from cache or fresh from API
function tj_fence_plus_get_info( $info, $user_id = null ) {

	if ( current_user_can( 'delete_users' ) && isset( $_GET['user_id'] ) ) {
		$user_id = $_GET['user_id'];
	}

	if ( $user_id == null ) {
		$user = wp_get_current_user();
		$user_id = $user->ID;
	}

// first look for a cached result
	if ( false !== $cache = get_transient( $user_id . 'AF_USFA_KEY' ) ) {
		return $cache[$info];
	}

// no cache? Then get fresh value
	if ( $user_id != null ) {
		$fresh = tj_fence_plus_ask_askFRED( $user_id );
	}
	else {
		$fresh = tj_fence_plus_ask_askFRED();
	}

// Default life span  is 1 day (86400 seconds)
	$cache = 86400;

// If askFRED query unsuccessful, store dummy values for 5 minutes
	if ( $fresh === 1 ) {
		$fresh = array(
			'usfa_id'    => 100093532,
			'first_name' => 'George',
			'last_name'  => 'Washington',
			'year'       => 1776,
			'gender'     => 'M',
			'foil'       => 'A',
			'epee'       => 'A',
			'saber'      => 'A',
			'id'         => 0,
			'error_code' => 1
		);
		$cache = 0;
	}

	if ( $fresh === false ) {
		$fresh = array(
			'usfa_id'    => 100093532,
			'first_name' => 'George',
			'last_name'  => 'Washington',
			'year'       => 1776,
			'gender'     => 'M',
			'foil'       => 'A',
			'epee'       => 'A',
			'saber'      => 'A',
			'id'         => 0,
			'error_code' => 0
		);
		$cache = 0;
	}

// Store transient
	set_transient( $user_id . 'AF_USFA_KEY', $fresh, $cache );

// Return fresh asked info
	return $fresh[$info];
}

// Echo table of fencer personal information for backend user profile
function tj_fence_plus_personal_info_table_backend_profile() {
	$usfa_id = tj_fence_plus_get_info( 'usfa_id' );
	$year = tj_fence_plus_get_info( 'year' );
	$age_bracket = ucfirst( tj_fence_plus_get_age( $year ) );
	$gender = tj_fence_plus_get_info( 'gender' );
	$first_name = tj_fence_plus_get_info( 'first_name' );
	$last_name = tj_fence_plus_get_info( 'last_name' );
	$foil = tj_fence_plus_get_info( 'foil' );
	$epee = tj_fence_plus_get_info( 'epee' );
	$saber = tj_fence_plus_get_info( 'saber' );
	$af_id = tj_fence_plus_get_info( 'id' );

	if ( tj_fence_plus_get_info( 'error_code' ) != null ) {
		$error_code = tj_fence_plus_get_info( 'error_code' );
		$error = tj_fence_plus_error( $error_code );
		echo "<div id='message' class='error'><p><b>$error</b></p></div>";
	}

	else {

		echo "
        <tr><td colspan='2'>See an error? You can fix it on your <a href='http://askfred.net/Myfred/' target='_blank'>askFRED profile</a> and then just click update profile, and your changes will automagically show up.</td></tr>
        <!--<tr><td>USFA ID</td><td><input type='text' id='tj_fence_plus_get_info()' disabled='disabled' value='$usfa_id'</input></td></tr>-->
        <tr><td>First Name</td><td><input type='text' id='tj_fence_plus_get_info()' disabled='disabled' value='$first_name'</input></td></tr>
        <tr><td>Last Name</td><td><input type='text' id='tj_fence_plus_get_info()' disabled='disabled' value='$last_name'</input></td></tr>
        <tr><td>Year of Birth</td><td><input type='text' id='tj_fence_plus_get_info()' disabled='disabled' value='$year'</input></td></tr>
        <tr><td>Age Bracket</td><td><input type='text' id='tj_fence_plus_get_info()' disabled='disabled' value='$age_bracket'</input></td></tr>
        <tr><td>Gender</td><td><input type='text' id='tj_fence_plus_get_info()' disabled='disabled' value='$gender'</input></td></tr>
        <!--<tr><td>askFRED ID</td><td><input type='text' id='tj_fence_plus_get_info()' disabled='disabled' value='$af_id'</input></td></tr>-->
        <tr><td>Foil Rating</td><td><input type='text' id='tj_fence_plus_get_info()' disabled='disabled' value='$foil'</input></td></tr>
        <tr><td>Epee Rating</td><td><input type='text' id='tj_fence_plus_get_info()' disabled='disabled' value='$epee'</input></td></tr>
        <tr><td>Saber Rating</td><td><input type='text' id='tj_fence_plus_get_info()' disabled='disabled' value='$saber'</input></td></tr>dd
        ";
	}

}

// Register Actions
add_action( 'tj_fence_plus_personal_info_table_backend_profile', 'tj_fence_plus_personal_info_table_backend_profile' );

/***********Check if Fencer Age Limit is within event limit**********/

function tj_fence_plus_check_age_limit( $fencer_age_limit, $event_age_limit, $fencer_rating_limit, $event_rating_limit, $fencer_gender, $event_gender ) {
	if ( $fencer_gender == "u" ) {
		$fencer_gender = true;
	}

	if ( $fencer_gender == "f" ) {
		$fencer_gender = "w";
	}

	if ( $fencer_gender == $event_gender ) {
		$pass_gender = true;
	}

	else if ( $event_gender == 'mixed' ) {
		$pass_gender = true;
	}

	else {
		$pass_gender = false;

		return $pass_gender;
	}

	/*********Check Age**********/

	if ( $event_age_limit == 'none' || $event_age_limit == "open" ) {
		$pass_age = true;
	}

	else if ( $fencer_age_limit == $event_age_limit ) {
		$pass_age = true;
	}

	else if ( $fencer_age_limit[0] == "v" && ( $event_age_limit == 'veteran' || $event_age_limit[0] == 'v' ) ) {
		$pass_age = true;
	}

	else if ( $event_age_limit == 'junior' && $fencer_age_limit == 'cadet' ) {
		$pass_age = true;
	}

	else if ( ( $event_age_limit == 'veteran' || $event_age_limit == 'vetcombined' ) && ( $fencer_age_limit[0] == 'v' ) ) {
		$pass_age = true;
	}

	else if ( ( $event_age_limit == 'senior' ) && ( $fencer_age_limit == 'cadet' || $fencer_age_limit == 'junior' || $fencer_age_limit[0] == 'v' ) ) {
		$pass_age = true;
	}

	else {
		$pass_age = false;

		return $pass_age;
	}

	/*******Check Rating*********/

	if ( $event_rating_limit == 'open' || $event_rating_limit == 'unrated' ) {
		$pass_rating = true;
	}

	else if ( $fencer_rating_limit == 'U' && ( $event_rating_limit == 'eunder' ) || $event_rating_limit == 'div3' || $event_rating_limit == 'div2' || $event_rating_limit == 'div1a' ) {
		$pass_rating = true;
	}

	else if ( $fencer_rating_limit == 'E' && ( $event_rating_limit == 'eunder' ) || $event_rating_limit == 'div3' || $event_rating_limit == 'div2' || $event_rating_limit == 'div1a' ) {
		$pass_rating = true;
	}

	else if ( $fencer_rating_limit == 'D' && ( $event_rating_limit == 'dabove' ) || $event_rating_limit == 'div3' || $event_rating_limit == 'div2' || $event_rating_limit == 'div1a' ) {
		$pass_rating = true;
	}

	else if ( $fencer_rating_limit == 'C' && ( $event_rating_limit == 'dabove' ) || $event_rating_limit == 'div1' || $event_rating_limit == 'div2' || $event_rating_limit == 'div1a' ) {
		$pass_rating = true;
	}

	else if ( $fencer_rating_limit == 'B' && ( $event_rating_limit == 'dabove' ) || $event_rating_limit == 'div1' || $event_rating_limit == 'babove' || $event_rating_limit == 'div1a' ) {
		$pass_rating = true;
	}

	else if ( $fencer_rating_limit == 'A' && ( $event_rating_limit == 'dabove' ) || $event_rating_limit == 'div1' || $event_rating_limit == 'babove' || $event_rating_limit == 'aonly' || $event_rating_limit == 'div1a' ) {
		$pass_rating = true;
	}

	else {
		$pass_rating = false;
	}

	return $pass_rating;

}

/***********Get Age Bracket*************/

function tj_fence_plus_get_age( $birthyear ) {
	if ( $birthyear <= 2005 && $birthyear >= 2003 ) {
		return "Y10";
	}

	else if ( $birthyear <= 2003 && $birthyear >= 2000 ) {
		return "Y12";
	}

	else if ( $birthyear <= 2001 && $birthyear >= 1998 ) {
		return "Y14";
	}

	else if ( $birthyear <= 1999 && $birthyear >= 1996 ) {
		return "cadet";
	}

	else if ( $birthyear <= 1999 && $birthyear >= 1993 ) {
		return "junior";
	}

	else if ( $birthyear <= 1992 && $birthyear >= 1973 ) {
		return "senior";
	}

	else if ( $birthyear <= 1972 && $birthyear >= 1963 ) {
		return "vet40";
	}

	else if ( $birthyear <= 1963 && $birthyear >= 1953 ) {
		return "vet50";
	}

	else if ( $birthyear <= 1953 && $birthyear >= 1943 ) {
		return "vet60";
	}

	else if ( $birthyear <= 1943 ) {
		return "vet70";
	}
}

/*********Add all fencers in a club******/

function tj_fence_plus_page_load() {
	tj_fence_plus_refresh_ids();
	$options = get_option( 'fence_plus_plugin_fencer_options' );
	$usfa_id = $options['usfa_id'];
	$clean_profile = $options['clean_profile'];
	$no_tournaments_text = $options['no_tournaments_text'];
	$radius = $options['tournament_distance'];

	if ( isset( $options['import_fencers'] ) ) {
		$checked = $options['import_fencers'];

		if ( $checked == 1 && isset( $usfa_id ) && $usfa_id != null ) {
			$fencer_options = array(
				'clean_profile'       => $clean_profile,
				'no_tournaments_text' => $no_tournaments_text,
				'tournament_distance' => $radius,
				'import_fencers'      => 0,
				'usfa_id'             => $usfa_id,
				'wipe_fencers'        => 0
			);
			update_option( 'fence_plus_plugin_fencer_options', $fencer_options );
			tj_fence_plus_search_club();
		}
	}

	if ( isset( $options['wipe_fencers'] ) ) {
		$checked = $options['wipe_fencers'];

		if ( $checked == 1 ) {
			$fencer_options = array(
				'clean_profile'       => $clean_profile,
				'no_tournaments_text' => $no_tournaments_text,
				'tournament_distance' => $radius,
				'import_fencers'      => 0,
				'usfa_id'             => $usfa_id,
				'wipe_fencers'        => 0
			);
			update_option( 'fence_plus_plugin_fencer_options', $fencer_options );
			tj_fence_plus_delete_fencers();
		}
	}
}

add_action( 'admin_init', 'tj_fence_plus_page_load' );

function tj_fence_plus_search_club() {
	$redirect_to = get_option( 'siteurl' ) . "/wp-admin/admin.php?page=fence_plus_plugin_fencer_options&updating=true"; // Redirect to page with updating key
	wp_redirect( $redirect_to );
	$af_club_id = tj_fence_plus_get_club_id();
	$af_per_page = 100;
	$page = 1;
	$af_get_url = "https://api.askfred.net/v1/fencer/?_api_key=a8a854b2e3c3eac74bfda01f625182b8&club_id=" . $af_club_id . "&_per_page=" . $af_per_page . "&_page=";
	$af_get_request = $af_get_url . $page;
	$af_get_response = wp_remote_get( $af_get_request );
	$af_get_response = wp_remote_retrieve_body( $af_get_response );
	$af_decoded = json_decode( $af_get_response, true );
	$af_count = floor( $af_decoded['total_matched'] / $af_per_page ) + 1;
	$count = 0;

	for ( $i = 1; $i < $af_count; $i ++ ) {

		if ( $i != 1 ) {
			$af_get_request = $af_get_url . $page;
			$af_get_response = wp_remote_get( $af_get_request );
			$af_get_response = wp_remote_retrieve_body( $af_get_response );
			$af_decoded = json_decode( $af_get_response, true );
		}

		$af_decoded_fencers = ( $af_decoded['fencers'] );
		$af_decoded_fencers_length = count( $af_decoded_fencers );

		for ( $q = 0; $q < $af_decoded_fencers_length; $q ++ ) {
			$fencer_array = $af_decoded_fencers[$q];

			if ( ! empty( $fencer_array['usfa_id'] ) && ! empty( $fencer_array['usfa_ratings'] ) ) {
				$count = $count + 1;
			}
		}

		for ( $p = 0; $p <= $af_decoded_fencers_length; $p ++ ) {

			$fencer_array = $af_decoded_fencers[$p];

			if ( ! empty( $fencer_array['usfa_id'] ) && ! empty( $fencer_array['usfa_ratings'] ) ) {
				$fencer_array_decoded = array(
					'first_name' => $fencer_array['first_name'],
					'last_name'  => $fencer_array['last_name'],
					'usfa_id'    => $fencer_array['usfa_id'],
					'id'         => $fencer_array['id']
				);

				$username = esc_attr( $fencer_array_decoded['first_name'] . " " . $fencer_array_decoded['last_name'] );
				$password = $fencer_array_decoded['usfa_id'];

				while ( username_exists( $username ) != null ) {
					break 2;
				}

				wp_insert_user( array(
						'user_login' => $username,
						'user_pass'  => $password,
						'role'       => 'fencer'

					)
				);

				$user = get_user_by( 'login', $username );
				$user_id = $user->ID;
				update_user_meta( $user_id, 'tj_fence_plus_usfaID', $password );
				update_user_meta( $user_id, 'tj_fence_plus_id', $fencer_array_decoded['id'] );
				update_user_meta( $user_id, 'all_tournaments_number', 5 );
				update_user_meta( $user_id, 'all_tournaments_description', 1 );
				update_user_meta( $user_id, 'tj_fence_plus_show_description', 1 );
				update_user_meta( $user_id, 'tj_fence_plus_number_tournaments', 3 );
			}
		}

		$page ++;
	}
	exit;
}

function tj_fence_plus_update_af_id( $af_id ) {
	$users = get_users();
	foreach ( $users as $user ) {
		$user_id = $user->ID;
		$unknown_id = get_user_meta( $user_id, 'tj_fence_plus_id' );
		if ( $unknown_id == $af_id ) {
			break;
		}
	}
	$usfa_id = get_user_meta( $user_id, 'tj_fence_plus_usfaID' );
	$af_get_url = "https://api.askfred.net/v1/fencer/?_api_key=a8a854b2e3c3eac74bfda01f625182b8&usfa_id=" . $usfa_id;
	$response = wp_remote_get( $af_get_url );
	$response_body = wp_remote_retrieve_body( $response );
	$decoded = json_decode( $response_body );
	$new_af_id = $decoded['fencers']['0']['id'];

	return $new_af_id;
}

function tj_fence_plus_delete_fencers() {
	$args = array(
		'role' => 'fencer'
	);

	$fencers = get_users( $args );

	foreach ( $fencers as $fencer ) {
		$user_id = $fencer->ID;
		wp_delete_user( $user_id );
		delete_transient( $user_id . 'AF_USFA_KEY' );
	}

}

/******Get Club ID**********/
function tj_fence_plus_get_club_id() {
	$options = get_option( 'fence_plus_plugin_fencer_options' );
	$af_fencer_usfa_id = $options['usfa_id'];
	$af_per_page = 100;
	$page = 1;
	$af_get_url = "https://api.askfred.net/v1/fencer/?_api_key=a8a854b2e3c3eac74bfda01f625182b8&usfa_id=" . $af_fencer_usfa_id . "&_per_page=" . $af_per_page . "&_page=";
	$af_get_request = $af_get_url . $page;
	$af_get_response = file_get_contents( $af_get_request );
	$af_decoded = json_decode( $af_get_response, true );
	$af_club_id = $af_decoded['fencers']['0']['clubs']['0']['id'];

//add_option('AF_CLUB_ID', $af_club_id);
	return $af_club_id;
}

/************ Add Fencer and Coach custom roles ****************/

register_activation_hook( __FILE__, 'tj_fence_plus_activation' );

function tj_fence_plus_activation() {

	/*********Add User Roles*************/
	add_role( 'fencer', 'Fencer', array(
			'read'              => true,
			'edit_posts'        => false,
			'manage_posts'      => false,
			'publish_posts'     => false,
			'edit_others_posts' => false,
			'delete_posts'      => false,
		)
	);

	add_role( 'coach', 'Coach', array(
			'read'              => true,
			'edit_posts'        => false,
			'manage_posts'      => false,
			'publish_posts'     => false,
			'edit_others_posts' => false,
			'delete_posts'      => false,
		)
	);

	$fencer_role = get_role( 'fencer' );
	$fencer_role->add_cap( 'view_tournaments' );
	$fencer_role->add_cap( 'edit_dashboard' );

	$coach_role = get_role( 'coach' );
	$coach_role->add_cap( 'list_users' );
	$coach_role->add_cap( 'view_tournaments' );
	$coach_role->add_cap( 'edit_dashboard' );
}

/***********Add Columns to User Profile Lists********/

function tj_fence_plus_modify_user_page( $column ) {
	$column['foil_rating'] = 'Foil';
	$column['epee_rating'] = 'Epee';
	$column['saber_rating'] = 'Saber';
	$column['usfa_id'] = 'USFA ID';

	return $column;
}

add_filter( 'manage_users_columns', 'tj_fence_plus_modify_user_page' );

function tj_fence_plus_modify_user_page_row( $val, $column_name, $user_id ) {
	$usfa_id = tj_fence_plus_get_info( 'usfa_id', $user_id );

	$foil = tj_fence_plus_get_info( 'foil', $user_id );
	$epee = tj_fence_plus_get_info( 'epee', $user_id );
	$saber = tj_fence_plus_get_info( 'saber', $user_id );

	switch ( $column_name ) {
		case 'foil_rating' :
			if ( isset( $foil ) ) {
				return $foil;
			}
			break;

		case 'epee_rating' :
			if ( isset( $epee ) ) {
				return $epee;
			}
			break;

		case 'saber_rating' :
			if ( isset( $saber ) ) {
				return $saber;
			}
			break;

		case 'usfa_id' :
			return $usfa_id;
			break;

		default:
	}

// return $return;
}

add_filter( 'manage_users_custom_column', 'tj_fence_plus_modify_user_page_row', 10, 3 );

function tj_fence_plus_admin_style() {
	echo '<style type="text/css">';
	echo '.users .column-username { width:31% !important; }';
	echo '.users .column-name { width:15% !important; display: none;  }';
	echo '.users .column-email { width:15% !important; display: none !important;  }';
	echo '.users .column-role { width:10% !important;  }';
	echo '.users .column-posts { width:7% !important;  }';
	echo '.users .column-foil_rating { width:8% !important; text-align: center; }';
	echo '.users .column-epee_rating { width:8% !important; text-align: center; }';
	echo '.users .column-saber_rating { width:8% !important; text-align: center; }';
	echo '.users th.column-foil_rating { width:8% !important; text-align: center !important; }';
	echo '.users th.column-epee_rating { width:8% !important; text-align: center !important; }';
	echo '.users th.column-saber_rating { width:8% !important; text-align: center !important; }';
	echo '.users .column-usfa_id { text-align: center; width: 10%;}';
	echo '.users th.column-usfa_id { text-align: center; }';
	echo '.star-0 {list-style: square inside url("http://localhost:8080/wordpress/wp-content/plugins/askfred/img/stars/0-stars.png");}';
	echo '.star-0-5 {list-style: square inside url("http://localhost:8080/wordpress/wp-content/plugins/askfred/img/stars/0.5-stars.png");}';
	echo '.star-1 {list-style: square inside url("http://localhost:8080/wordpress/wp-content/plugins/askfred/img/stars/1-stars.png");}';
	echo '.star-1-5 {list-style: square inside url("http://localhost:8080/wordpress/wp-content/plugins/askfred/img/stars/1.5-stars.png");}';
	echo '.star-2 {list-style: square inside url("http://localhost:8080/wordpress/wp-content/plugins/askfred/img/stars/2-stars.png");}';
	echo '.star-2-5 {list-style: square inside url("http://localhost:8080/wordpress/wp-content/plugins/askfred/img/stars/2.5-stars.png");}';
	echo '.star-3 {list-style: square inside url("http://localhost:8080/wordpress/wp-content/plugins/askfred/img/stars/3-stars.png");}';
	echo '</style>';
}

add_action( 'admin_head', 'tj_fence_plus_admin_style' );

function tj_fence_plus_style() {
	echo '<style type="text/css">';
	echo '.fence-plus-table {min-width: 15%;}';
	echo '</style>';
}

add_action( 'wp_head', 'tj_fence_plus_style' );

/***********Clean Up User Profile*******************/

function af_clean_up_profile() {
	$options = get_option( 'fence_plus_plugin_fencer_options' );
	if ( isset( $options['clean_profile'] ) ) {
		$clean_profile = $options['clean_profile'];
	}
	else {
		$clean_profile = 0;
	}

	if ( current_user_can( 'view_tournaments' ) && $clean_profile == 1 ) {

		remove_meta_box( 'dashboard_primary', 'dashboard', 'core' ); // Remove Default Dashboard Widget Boxes
		remove_meta_box( 'dashboard_secondary', 'dashboard', 'core' );

// Auto enable hide admin bar in frontend
		function yoast_hide_admin_bar_settings() {
			?>
			<style type="text/css">
            .show-admin-bar {
	            display: none;
	            }
        </style>
		<?php
		}

		function yoast_disable_admin_bar() {
			if ( 2 == get_current_user_id() ) {
				add_filter( 'show_admin_bar', '__return_false' );
				add_action( 'admin_print_scripts-profile.php', 'yoast_hide_admin_bar_settings' );
			}
		}

		add_action( 'init', 'yoast_disable_admin_bar', 9 );

// Remove admin skin color chooser
		function admin_del_options() {
			global $_wp_admin_css_colors;
			$_wp_admin_css_colors = 0;
		}

		add_action( 'admin_head', 'admin_del_options' );

//Remove Social Media Fields
		function tj_fence_plus_remove_user_fields( $user_contactmethods ) {
			$user_contactmethods = array();

			return $user_contactmethods;
		}

		add_filter( 'user_contactmethods', 'tj_fence_plus_remove_user_fields', 10, 1 );

// Remove BIO
		if ( ! function_exists( 'remove_plain_bio' ) ) {
			function remove_plain_bio( $buffer ) {
				$titles = array( '#<h3>About Yourself</h3>#', '#<h3>About the user</h3>#' );
				$buffer = preg_replace( $titles, '<h3>Password</h3>', $buffer, 1 );
				$biotable = '#<h3>Password</h3>.+?<table.+?/tr>#s';
				$buffer = preg_replace( $biotable, '<h3>Password</h3> <table class="form-table">', $buffer, 1 );

				return $buffer;
			}

			function profile_admin_buffer_start() {
				ob_start( "remove_plain_bio" );
			}

			function profile_admin_buffer_end() {
				ob_end_flush();
			}
		}
		add_action( 'admin_head', 'profile_admin_buffer_start' );
		add_action( 'admin_footer', 'profile_admin_buffer_end' );
	}
}

add_action( 'admin_init', 'af_clean_up_profile' );

/*
Plugin Name: Redirect user to front page
Plugin URI: http://www.theblog.ca/wplogin-front-page
Description: When a user logs in via wp-login directly, redirect them to the front page.
Author: Peter
Version: 1.0
Author URI: http://www.theblog.ca
*/

function redirect_to_front_page() {
	global $redirect_to;
	if ( ! isset( $_GET['redirect_to'] ) ) {
		$redirect_to = get_option( 'siteurl' ) . "/wp-admin/index.php"; // Redirect to dashboard on login
	}
}

add_action( 'login_form', 'redirect_to_front_page' );
