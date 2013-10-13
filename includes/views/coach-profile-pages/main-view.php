<?php
/**
 *
 * @package Fence Plus
 * @subpackage
 * @since
 */

class Fence_Plus_Coach_Profile_Main {
	/**
	 * @var Fence_Plus_Coach object
	 */
	private $coach;

	/**
	 * @param $coach Fence_Plus_Coach object
	 */
	public function __construct( Fence_Plus_Coach $coach ) {
		$this->coach = $coach;

		add_filter( 'fence_plus_coach_data_nav_tab', array( $this, 'default_tabs' ) );

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
			'class_path' => FENCEPLUS_INCLUDES_VIEWS_COACH_PROFILE_PAGES_DIR . "overview.php",
			'class_name' => 'Fence_Plus_Coach_Profile_Overview'
		);

		if ( current_user_can( 'edit_users' ) ) {
			$tabs['add-fencer'] = array(
				'slug'       => 'add-fencer',
				'title'      => __( 'Add Fencer', Fence_Plus::SLUG ),
				'class_path' => FENCEPLUS_INCLUDES_VIEWS_COACH_PROFILE_PAGES_DIR . 'add-fencer.php',
				'class_name' => 'Fence_Plus_Coach_Add_Fencer'
			);
		}

		return $tabs;
	}

	/**
	 * Renders the actual screen
	 */
	public function render() {
		?>

		<div id="profile_page" class="wrap">
			<h2>
				<?php echo $this->coach->display_name; ?>

				<?php if ( current_user_can( 'edit_users' ) ) : ?>
					<a href="<?php echo esc_url( get_edit_user_link( $this->coach->ID ) ); ?>" class="edit-user add-new-h2"><?php esc_html_e( 'Edit Coach', Fence_Plus::SLUG ); ?></a>
				<?php elseif ( $this->coach->ID == get_current_user_id() ) : ?>
					<a href="<?php echo esc_url( get_edit_profile_url( $this->coach->ID ) ); ?>" class="edit-user add-new-h2"><?php esc_html_e( 'Edit Profile', Fence_Plus::SLUG ); ?></a>
				<?php endif; ?>
			</h2>

			<h2 class="nav-tab-wrapper">
				<?php $active_tab = $this->active_tab();
				/**
				 * Controls coach profile tabs.
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
				$tabs = apply_filters( 'fence_plus_coach_data_nav_tab', array() ); ?>

				<?php foreach ( $tabs as $tab ) :
					$active = $tab['slug'] === $active_tab ? 'nav-tab-active' : ''; ?>
					<a class="nav-tab <?php echo $active; ?>" href="<?php echo add_query_arg( 'tab', $tab['slug'] ); ?>"><?php echo $tab['title']; ?></a>
				<?php endforeach; ?>
			</h2>
			<?php
			include ( $tabs[$active_tab]['class_path'] );
			$reflection = new ReflectionClass( $tabs[$active_tab]['class_name'] );
			$reflection->newInstanceArgs( array( $this->coach ) );
			?>

		</div>

	<?php
	}
}