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

		wp_register_script( 'fence-plus-profile-overview', FENCEPLUS_INCLUDES_JS_URL . 'profile-overview.js', array( 'jquery', 'jquery-effects-blind' ) );
		wp_enqueue_script( 'fence-plus-profile-overview' );
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
	                <?php echo get_avatar( $this->fencer->get_wp_id(), 160 ); ?>
                </div>

                <div class="fencer-data right">
                    <div class="fencer-primary-weapon">
                        <?php $primary_weapon = $this->fencer->get_primary_weapon(); echo empty( $primary_weapon ) ? __( "N/A", Fence_Plus::SLUG ) : implode( ", ", $primary_weapon ); ?>
                    </div>

                    <div class="fencer-rating">
                        <?php $primary_weapon_rating = $this->fencer->get_primary_weapon_rating(); echo empty( $primary_weapon ) ? "<br>" : implode( ", ", $primary_weapon_rating ); ?>
                    </div>

                    <div class="fencer-usfa-id">
                        <?php echo $this->fencer->get_usfa_id(); ?>
                    </div>
                </div>

                <div class="fencer-info">
                    <h2 class="fencer-display-name"><?php echo $this->fencer->get_first_name() . " " . $this->fencer->get_last_name(); ?></h2>

                    <div class="fencer-birthyear">
                        <?php echo sprintf( __( "Born %d", Fence_Plus::SLUG ), $this->fencer->get_birthyear() ); ?>
                    </div>

                    <div class="fencer-performance">
                        <a id="fencer-show-more-info" href="#"><?php _e( "View More Information", Fence_Plus::SLUG ); ?></a>
                    </div>
                </div>
            </div>
	        <div class="fencer-more-info-box spacing-wrapper" id="fencer-more-info-box">
		        <div class="fencer-more-info-container">
			        <div class="row-headings left">
				        <p><?php _e( 'Epee', Fence_Plus::SLUG ); ?></p>
				        <p><?php _e( 'Foil', Fence_Plus::SLUG ); ?></p>
				        <p><?php _e( 'Saber', Fence_Plus::SLUG ); ?></p>
				        <p><?php _e( 'Gender', Fence_Plus::SLUG ); ?></p>
				        <?php do_action( 'fence_plus_profile_overview_more_info_row_heading', $this->fencer ); ?>
			        </div>
			        <div class="row-values left">
				        <p><?php echo $this->fencer->get_epee_letter() . $this->fencer->get_epee_year(); ?></p>
				        <p><?php echo $this->fencer->get_foil_letter() . $this->fencer->get_foil_year(); ?></p>
				        <p><?php echo $this->fencer->get_saber_letter() . $this->fencer->get_saber_year(); ?></p>
				        <p><?php echo $this->fencer->get_gender_full(); ?></p>
				        <?php do_action( 'fence_plus_profile_overview_more_info_row_value', $this->fencer ); ?>
			        </div>

			        <?php do_action( 'fence_plus_profile_overview_more_info_after', $this->fencer ); ?>
		        </div>
	        </div>
        </div>
    </div>


	<?php
	}
}