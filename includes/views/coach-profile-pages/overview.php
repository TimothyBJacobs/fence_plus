<?php
/**
 *
 * @package Fence Plus
 * @subpackage
 * @since
 */
class Fence_Plus_Coach_Profile_Overview {
	/**
	 * @var Fence_Plus_Coach
	 */
	private $coach;

	/**
	 * @param Fence_Plus_Coach $coach
	 */
	public function __construct( Fence_Plus_Coach $coach ) {
		$this->coach = $coach;
		$this->render();
	}

	/**
	 *
	 */
	public function render() {
		?>

		<?php foreach ( $this->coach->get_fencers() as $fencer_id ) : $fencer = Fence_Plus_Fencer::wp_id_db_load( $fencer_id ); ?>
			<?php $fencer->short_box(); ?>
		<?php endforeach; ?>

	<?php
	}
}