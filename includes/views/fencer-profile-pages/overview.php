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
		$this->styles_and_scripts();
		$this->render();
	}

	public function styles_and_scripts() {
		wp_register_style( 'fence-plus-profile-overview', FENCEPLUS_INCLUDES_CSS_URL . 'profile-overview.css' );
		wp_enqueue_style( 'fence-plus-profile-overview' );
	}

	/**
	 * Render the profile page
	 *
	 * Credit to iThemes Exchange WP Plugin for design and CSS
	 */
	public function render() {
		?>

		<div id="fence-plus-fencer-overview" class="postbox">
        <div class="inside">

            <div class="fencer-overview spacing-wrapper">
                <div class="fencer-avatar left">
	                <?php echo get_avatar( $this->fencer->get_wp_id(), 80 ); ?>
                </div>

                <div class="fencer-data right">
                    <div class="fencer-primary-weapon">
                        <?php echo implode(", ", $this->fencer->get_primary_weapon()); ?>
                    </div>

                    <div class="fencer-rating">
                        <?php echo implode(", ", $this->fencer->get_primary_weapon_rating()); ?>
                    </div>

                    <div class="fencer-usfa-id">
                        <?php echo $this->fencer->get_usfa_id(); ?>
                    </div>
                </div>

                <div class="fencer-info">
                    <h2 class="fencer-display-name"><?php echo $this->fencer->get_first_name() . " " . $this->fencer->get_last_name(); ?></h2>

                    <div class="fencer-birthyear">
                        <?php echo sprintf(__("Born %d", Fence_Plus::SLUG), $this->fencer->get_birthyear()); ?>
                    </div>

                    <div class="fencer-performance">
                        <a href="<?php echo esc_url(add_query_arg( array( 'fence_plus_fencer_data' => 1, 'tab' => 'stats' ),
		                          get_edit_user_link( $this->fencer->get_wp_id() ) ) ); ?>"><?php _e("View Past Performance", Fence_Plus::SLUG); ?></a>
                    </div>
                </div>
            </div>
        </div>
    </div>


	<?php
	}
}