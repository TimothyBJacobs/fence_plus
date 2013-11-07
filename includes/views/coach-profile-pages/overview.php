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
	 * @var array
	 */
	private $overview_stats = array();

	/**
	 * @param Fence_Plus_Coach $coach
	 */
	public function __construct( Fence_Plus_Coach $coach ) {
		$this->coach = $coach;

		wp_enqueue_style( 'fence-plus-profile' );

		$this->add_overview_stats();
		add_action( 'fence_plus_coach_overview_after', array( $this, 'top_fencers' ) );

		$this->render();
	}

	/**
	 * Add default stats to be rendered in overview box
	 */
	public function add_overview_stats() {
		$num_fencers = count( $fencer_ids = $this->coach->get_editable_users() );
		$this->overview_stats[] = array(
			'number' => $num_fencers,
			'title'  => _n( 'Fencer', 'Fencers', $num_fencers, Fence_Plus::SLUG )
		);

		$epee = array();
		$foil = array();
		$saber = array();

		foreach ( $fencer_ids as $fencer_id ) {
			try {
				$fencer = Fence_Plus_Fencer::wp_id_db_load( $fencer_id );
			}
			catch ( InvalidArgumentException $e ) {
				continue;
			}

			$weapon = $fencer->get_primary_weapon();

			if (empty($weapon))
				continue;

			switch ( $weapon[0] ) {
				case 'Epee':
					$epee[] = $fencer;
					break;
				case 'Foil':
					$foil[] = $fencer;
					break;
				case 'Saber':
					$saber[] = $fencer;
					break;
			}
		}

		$this->overview_stats[] = array(
			'number' => $num_fencers = count( $epee ),
			'title'  => _n( 'Epee', 'Epees', $num_fencers, Fence_Plus::SLUG )
		);
		$this->overview_stats[] = array(
			'number' => $num_fencers = count( $foil ),
			'title'  => _n( 'Foil', 'Foils', $num_fencers, Fence_Plus::SLUG )
		);
		$this->overview_stats[] = array(
			'number' => $num_fencers = count( $saber ),
			'title'  => _n( 'Saber', 'Sabers', $num_fencers, Fence_Plus::SLUG )
		);
	}

	/**
	 * Render the coaches top fencers
	 */
	public function top_fencers() {
		$fencer_ids = $this->coach->get_editable_users();
		$fencers = array();

		foreach ( $fencer_ids as $fencer_id ) {
			try {
				$fencers[] = Fence_Plus_Fencer::wp_id_db_load( $fencer_id );
			}
			catch ( InvalidArgumentException $e ) {
				continue;
			}
		}

		usort( $fencers, array( 'Fence_Plus_Utility', 'sort_fencers' ) );
		array_splice( $fencers, 3 );

		foreach ( $fencers as $fencer ) {
			if ( $fencer->get_primary_weapon() != array() )
				$fencer->summary_box();
		}
	}

	/**
	 * Render the page
	 */
	public function render() {
		do_action( 'fence_plus_coach_overview_before', $this->coach );

		/**
		 * Adds ability to add stats to Coach overview page
		 *
		 * @since 0.1
		 *
		 * @param array {
		 *
		 * @type int $number the number to be displayed
		 * @type string $title the stat title
		 * }
		 */
		$overview_stats = apply_filters( 'fence_plus_coach_overview_stats', $this->overview_stats, $this->coach );

		?>

		<div class="postbox fence-plus-overview-box">
			<div class="inside">
				<div class="spacing-wrapper">

					<?php foreach ( $overview_stats as $overview_stat ) : ?>
						<div class="stat">
							<div class="number"><?php echo $overview_stat['number']; ?></div><br/>
							<div class="text"><?php echo $overview_stat['title']; ?></div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</div>

		<h3><?php _e( "Top Fencers", Fence_Plus::SLUG ); ?></h3>

		<?php

		do_action( 'fence_plus_coach_overview_after', $this->coach );
	}
}