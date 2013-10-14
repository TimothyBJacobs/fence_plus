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

		overview content here

	<?php
	}
}