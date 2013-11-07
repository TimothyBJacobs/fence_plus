<?php
/**
 * 
 * @package Fence Plus
 * @subpackage
 * @since
 */

class Fence_Plus_Extensions
{
	private $extensions = array();
	// todo API for checking available extensions

	public function init() {
		$this->extensions[] = array(
			'name'          => 'Auto Assign Fencers',
			'description'   => "Automatically assign fencers to coaches, based on a fencer's age, weapon, or rating.",
			'image'         => 'http://lorempixel.com/g/320/200/city/', // placeholder
			'info_url'      => 'https://www.ironbounddesigns.com/plugins/fence-plus-auto-assign-fencers'
		);

		wp_enqueue_style('fence-plus-extensions');

		$this->render();
	}

	public function render() {
		?>

		<div class="wrap">
			<h2><?php _e("Fence Plus Extensions", Fence_Plus::SLUG); ?></h2>

			<div class="extensions-grid">
				<ul>
					<?php foreach ($this->extensions as $extension) : ?>
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

		</div>

	<?php
	}
}