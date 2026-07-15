<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SSM_Frontend {
	/**
	 * @var SSM_Content
	 */
	private $content;

	public function __construct( SSM_Content $content ) {
		$this->content = $content;
	}

	public function enqueue_assets() {
		if ( is_admin() ) {
			return;
		}

		wp_enqueue_style(
			'ssm-frontend',
			SSM_URL . 'assets/css/ssm-frontend.css',
			array(),
			SSM_VERSION
		);
	}

	public function render_global_header() {
		if ( is_admin() ) {
			return;
		}

		$items = $this->content->get_section_navigation_items();
		if ( empty( $items ) ) {
			return;
		}

		$current_section_id = $this->get_current_section_id();
		?>
		<div class="ssm-global-header">
			<div class="ssm-global-header__inner">
				<nav class="ssm-global-header__nav" aria-label="<?php esc_attr_e( 'Site sections', 'site-section-manager' ); ?>">
					<?php foreach ( $items as $item ) : ?>
						<a
							class="ssm-global-header__link<?php echo (int) $current_section_id === (int) $item['section_id'] ? ' is-current' : ''; ?>"
							href="<?php echo esc_url( $item['url'] ); ?>"
						>
							<?php echo esc_html( $item['section_title'] ); ?>
						</a>
					<?php endforeach; ?>
				</nav>
			</div>
		</div>
		<?php
	}

	private function get_current_section_id() {
		if ( is_singular( 'site_section' ) ) {
			return (int) get_the_ID();
		}

		$post_id = get_queried_object_id();
		if ( ! $post_id ) {
			return 0;
		}

		return (int) get_post_meta( $post_id, '_ssm_section_id', true );
	}
}
