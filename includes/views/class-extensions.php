<?php
/**
 *
 * @package Fence Plus
 * @subpackage
 * @since
 */

class Fence_Plus_Extensions {
	/**
	 * @var array extensions
	 */
	private $extensions = array();

	/**
	 * Initiate everything
	 */
	public function init() {
		$this->get_extensions();

		wp_enqueue_style( 'fence-plus-extensions' );

		$this->render();
	}

	/**
	 * Get extensions from external API
	 */
	private function get_extensions() {
		if ( false === $data = get_transient( 'fence-plus-extensions' ) ) {
			$data = $this->call_api();

			if ( ! isset( $data['products'] ) ) {
				set_transient( 'fence-plus-extensions', null, 1800 );
				return;
			}

			$data = $data['products'];

			set_transient( 'fence-plus-extensions', $data, 86400 );
		}

		foreach ( $data as $product ) {
			$product = $product['info'];

			$this->extensions[] = array(
				'name'        => $product['title'],
				'description' => $product['content'],
				'image'       => $product['thumbnail'],
				'info_url'    => $product['link']
			);
		}
	}

	/**
	 * Call API
	 *
	 * @return array|mixed
	 */
	private function call_api() {
		$data = wp_remote_get( 'http://fencepluswp.com/edd-api/products' );
		$data = wp_remote_retrieve_body( $data );

		return json_decode( $data, true );
	}

	/**
	 * Render the page
	 */
	public function render() {
		?>

		<div class="wrap">
			<h2><?php _e( "Fence Plus Extensions", Fence_Plus::SLUG ); ?></h2>

			<?php if ($this->extensions === null) : ?>

				<div class="error"><p><strong><?php _e("Fence Plus", Fence_Plus::SLUG); ?></strong> <?php _e("API Server is currently down, please try again later.", Fence_Plus::SLUG) ?></p></div>

		    <?php else : ?>

				<div class="extensions-grid">
					<ul>
						<?php foreach ( $this->extensions as $extension ) : ?>
							<li>
								<a href="<?php echo $extension['info_url']; ?>">
									<div class="preview-image">
										<img width="320" height="200" src="<?php echo $extension['image']; ?>" alt="<?php echo $extension['name']; ?>">
									</div>
									<h4><?php echo $extension['name']; ?></h4>
									<p><?php echo $extension['description']; ?></p>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>

			<?php endif; ?>
		</div>

	<?php
	}
}