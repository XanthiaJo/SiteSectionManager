<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SSM_Section_Admin_Page {
	/**
	 * @var SSM_Content
	 */
	private $content;

	/**
	 * @var SSM_Section_Admin_Page_Renderer
	 */
	private $renderer;

	/**
	 * @var SSM_Section_Admin_Page_Data
	 */
	private $data;

	/**
	 * @var array<string,int>
	 */
	private $section_menu_map = array();

	public function __construct( SSM_Content $content ) {
		$this->content  = $content;
		$this->data     = new SSM_Section_Admin_Page_Data();
		$this->renderer = new SSM_Section_Admin_Page_Renderer( $this->data );
	}

	public function register_admin_menu() {
		add_menu_page(
			__( 'Site Sections', 'site-section-manager' ),
			__( 'Site Sections', 'site-section-manager' ),
			'manage_options',
			'ssm-site-sections',
			array( $this, 'render_admin_page' ),
			'dashicons-screenoptions',
			25
		);

		add_submenu_page(
			'ssm-site-sections',
			__( 'Site Sections', 'site-section-manager' ),
			__( 'Sections', 'site-section-manager' ),
			'manage_options',
			'ssm-site-sections',
			array( $this, 'render_admin_page' )
		);

		foreach ( $this->content->get_sections() as $section ) {
			$slug = $this->get_section_menu_slug( $section->ID );
			$this->section_menu_map[ $slug ] = (int) $section->ID;

			add_submenu_page(
				'ssm-site-sections',
				get_the_title( $section ),
				get_the_title( $section ),
				'manage_options',
				$slug,
				array( $this, 'render_admin_page' )
			);
		}
	}

	public function enqueue_admin_assets() {
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		if ( 0 !== strpos( $page, 'ssm-site-section' ) && 'ssm-site-sections' !== $page ) {
			return;
		}

		wp_enqueue_style(
			'ssm-admin',
			SSM_URL . 'assets/css/ssm-admin.css',
			array(),
			SSM_VERSION
		);
	}

	public function render_admin_page() {
		$context = $this->get_admin_page_context();
		$this->renderer->render_admin_page( $context['current_view'], $context['sections'], $context['unsectioned'] );
	}

	public function handle_create_section() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do that.', 'site-section-manager' ) );
		}

		check_admin_referer( 'ssm_create_section' );

		$title   = isset( $_POST['section_title'] ) ? sanitize_text_field( wp_unslash( $_POST['section_title'] ) ) : '';
		$content = isset( $_POST['section_content'] ) ? wp_kses_post( wp_unslash( $_POST['section_content'] ) ) : '';

		if ( '' === $title ) {
			wp_safe_redirect( add_query_arg( 'ssm_notice', 'missing-title', admin_url( 'admin.php?page=ssm-site-sections' ) ) );
			exit;
		}

		$section_id = wp_insert_post(
			array(
				'post_type'    => 'site_section',
				'post_status'  => 'publish',
				'post_title'   => $title,
				'post_content' => $content,
			),
			true
		);

		if ( is_wp_error( $section_id ) ) {
			wp_safe_redirect( add_query_arg( 'ssm_notice', 'error', admin_url( 'admin.php?page=ssm-site-sections' ) ) );
			exit;
		}

		wp_safe_redirect( $this->get_section_admin_url( absint( $section_id ), array( 'ssm_notice' => 'created' ) ) );
		exit;
	}

	public function handle_delete_section() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do that.', 'site-section-manager' ) );
		}

		$section_id = isset( $_GET['section_id'] ) ? absint( $_GET['section_id'] ) : 0;
		if ( ! $section_id ) {
			wp_safe_redirect( admin_url( 'admin.php?page=ssm-site-sections' ) );
			exit;
		}

		check_admin_referer( 'ssm_delete_section_' . $section_id );
		wp_delete_post( $section_id, true );

		wp_safe_redirect( add_query_arg( 'ssm_notice', 'deleted', admin_url( 'admin.php?page=ssm-site-sections' ) ) );
		exit;
	}

	public function handle_update_section() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do that.', 'site-section-manager' ) );
		}

		$section_id = isset( $_POST['section_id'] ) ? absint( $_POST['section_id'] ) : 0;
		if ( ! $section_id ) {
			wp_safe_redirect( admin_url( 'admin.php?page=ssm-site-sections' ) );
			exit;
		}

		check_admin_referer( 'ssm_update_section_' . $section_id );

		$title   = isset( $_POST['section_title'] ) ? sanitize_text_field( wp_unslash( $_POST['section_title'] ) ) : '';
		$content = isset( $_POST['section_content'] ) ? wp_kses_post( wp_unslash( $_POST['section_content'] ) ) : '';

		wp_update_post(
			array(
				'ID'           => $section_id,
				'post_title'   => $title,
				'post_content' => $content,
			)
		);

		wp_safe_redirect( $this->get_section_admin_url( $section_id, array( 'ssm_notice' => 'updated' ) ) );
		exit;
	}

	private function get_admin_page_context() {
		return array(
			'current_view' => $this->get_current_view(),
			'sections'     => $this->content->get_sections(),
			'unsectioned'  => $this->data->get_unsectioned_summary(),
		);
	}

	private function get_current_view() {
		$has_section_id = isset( $_GET['section_id'] );
		$section_id     = $has_section_id ? absint( $_GET['section_id'] ) : 0;

		if ( $has_section_id && 0 === $section_id ) {
			$this->debug_log(
				'get_current_view resolved unsectioned view',
				array(
					'section_id' => 0,
					'page'       => isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '',
				)
			);
			return (object) array(
				'view_type'    => 'unsectioned',
				'ID'           => 0,
				'post_type'    => 'site_section',
				'post_title'   => __( 'Unsectioned', 'site-section-manager' ),
				'post_content' => '',
			);
		}

		if ( ! $section_id ) {
			$page_slug = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
			if ( isset( $this->section_menu_map[ $page_slug ] ) ) {
				$section_id = (int) $this->section_menu_map[ $page_slug ];
			}
		}

		if ( ! $section_id ) {
			$this->debug_log(
				'get_current_view resolved dashboard view',
				array(
					'page'       => isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '',
					'section_id' => 0,
				)
			);
			return null;
		}

		$section = get_post( $section_id );
		if ( ! $section || 'site_section' !== $section->post_type ) {
			$this->debug_log(
				'get_current_view rejected invalid section',
				array(
					'section_id' => $section_id,
					'found'      => (bool) $section,
					'post_type'  => $section ? $section->post_type : null,
				)
			);
			return null;
		}

		$section->view_type = 'section';
		$this->debug_log(
			'get_current_view resolved section view',
			array(
				'section_id' => $section_id,
				'title'      => get_the_title( $section ),
			)
		);
		return $section;
	}

	private function get_section_menu_slug( $section_id ) {
		return 'ssm-site-section-' . absint( $section_id );
	}

	private function get_section_admin_url( $section_id, array $args = array() ) {
		$slug = $this->get_section_menu_slug( $section_id );
		$url  = admin_url( 'admin.php?page=' . $slug );

		if ( ! empty( $args ) ) {
			$url = add_query_arg( $args, $url );
		}

		return $url;
	}

	private function debug_log( $message, array $context = array() ) {
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return;
		}

		$line = '[SSM] ' . $message;
		if ( ! empty( $context ) ) {
			$encoded = function_exists( 'wp_json_encode' ) ? wp_json_encode( $context ) : json_encode( $context );
			if ( false !== $encoded ) {
				$line .= ' ' . $encoded;
			}
		}

		error_log( $line );
	}
}
