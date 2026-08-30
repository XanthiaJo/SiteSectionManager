<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SSM_Frontend {
	/**
	 * @var SSM_Content
	 */
	private $content;

	/**
	 * @var SSM_Navigation
	 */
	private $navigation;

	/**
	 * @var bool
	 */
	private $has_rendered_global_header = false;

	public function __construct( SSM_Content $content, SSM_Navigation $navigation ) {
		$this->content    = $content;
		$this->navigation = $navigation;
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
		if ( is_admin() || $this->has_rendered_global_header ) {
			return;
		}

		$items = $this->content->get_section_navigation_items();
		if ( empty( $items ) ) {
			return;
		}

		$this->has_rendered_global_header = true;
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

	public function filter_bloginfo( $output, $show ) {
		if ( is_admin() || 'name' !== $show ) {
			return $output;
		}

		$section_title = $this->get_current_section_title();
		if ( '' === $section_title ) {
			return $output;
		}

		return $section_title;
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

	private function get_current_section_title() {
		$section_id = $this->get_current_section_id();
		if ( 0 === $section_id ) {
			return __( 'Home', 'site-section-manager' );
		}

		$section = get_post( $section_id );
		if ( ! $section || 'site_section' !== $section->post_type ) {
			return '';
		}

		return get_the_title( $section );
	}
}
