<?php
/**
 *
 * @package Fence Plus
 * @subpackage
 * @since
 */

class Fence_Plus_Fencer_Profile_Main {
	/**
	 * @var Fence_Plus_Fencer object
	 */
	private $fencer;

	/**
	 * @param $fencer Fence_Plus_Fencer object
	 */
	public function __construct( $fencer ) {
		$this->fencer = $fencer;

		add_filter( 'fence_plus_fencer_data_nav_tab', array( $this, 'default_tabs' ) );

		$this->render();
	}

	/**
	 * Returns the slug of the active tab
	 *
	 * @return string
	 */
	public function active_tab() {
		if ( isset( $_GET['tab'] ) ) {
			return $_GET['tab'];
		}
		else {
			return "overview";
		}
	}

	/**
	 * Default tabs to be added to the page
	 *
	 * @param $tabs
	 *
	 * @return array
	 */
	public function default_tabs( $tabs ) {
		$tabs['overview'] = array(
			'slug'       => 'overview',
			'title'      => __( 'Overview', Fence_Plus::SLUG ),
			'class_path' => FENCEPLUS_INCLUDES_VIEWS_PROFILE_PAGES_DIR . "overview.php",
			'class_name' => 'Fence_Plus_Profile_Overview'
		);

		$tabs['tournaments'] = array(
			'slug'       => 'tournaments',
			'title'      => __( 'Tournaments', Fence_Plus::SLUG ),
			'class_path' => FENCEPLUS_INCLUDES_VIEWS_PROFILE_PAGES_DIR . "tournaments.php",
			'class_name' => 'Fence_Plus_Profile_Tournaments'
		);

		$tabs['stats'] = array(
			'slug'       => 'stats',
			'title'      => __( 'Stats', Fence_Plus::SLUG ),
			'class_path' => FENCEPLUS_INCLUDES_VIEWS_PROFILE_PAGES_DIR . "stats.php",
			'class_name' => 'Fence_Plus_Profile_Stats'
		);

		return $tabs;
	}

	/**
	 * Renders the actual screen
	 */
	public function render() {
		?>

		<div id="profile_page" class="wrap">
			<h2>
				<?php echo $this->fencer->get_first_name() . " " . $this->fencer->get_last_name(); ?>

				<?php if ( current_user_can( 'edit_users' ) ) : ?>
					<a href="<?php echo esc_url( get_edit_user_link( $this->fencer->get_wp_id() ) ); ?>" class="edit-user add-new-h2"><?php esc_html_e( 'Edit Fencer', Fence_Plus::SLUG ); ?></a>
				<?php elseif ( $this->fencer->get_wp_id() == get_current_user_id() ) : ?>
					<a href="<?php echo esc_url( get_edit_profile_url( $this->fencer->get_wp_id() ) ); ?>" class="edit-user add-new-h2"><?php esc_html_e( 'Edit Profile', Fence_Plus::SLUG ); ?></a>
				<?php endif; ?>
			</h2>

			<h2 class="nav-tab-wrapper">
				<?php $active_tab = $this->active_tab();
				/**
				 * Controls fencer profile tabs.
				 *
				 * @since 0.1
				 *
				 * Array of associative arrays where the key is the slug and values are below.
				 *
				 * @param array {
				 *
				 * @type string $slug the tab's slug
				 * @type string $title the tab's title
				 * @type string $class_path absolute path to php file to render the tab
				 * @type string $class_name name of the class to instantiate
				 * }
				 */
				$tabs = apply_filters( 'fence_plus_fencer_data_nav_tab', array() ); ?>

				<?php foreach ( $tabs as $tab ) :
					$active = $tab['slug'] === $active_tab ? 'nav-tab-active' : ''; ?>
					<a class="nav-tab <?php echo $active; ?>" href="<?php echo add_query_arg( 'tab', $tab['slug'] ); ?>"><?php echo $tab['title']; ?></a>
				<?php endforeach; ?>
			</h2>
			<?php
			include ( $tabs[$active_tab]['class_path'] );
			$reflection = new ReflectionClass( $tabs[$active_tab]['class_name'] );
			$reflection->newInstanceArgs( array( $this->fencer ) );
			?>

		</div>

	<?php
	}
}