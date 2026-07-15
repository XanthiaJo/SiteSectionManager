<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SSM_Plugin {
	/**
	 * @var SSM_Plugin|null
	 */
	private static $instance = null;

	/**
	 * @var SSM_Content
	 */
	private $content;

	/**
	 * @var SSM_Admin
	 */
	private $admin;

	/**
	 * @var SSM_Frontend
	 */
	private $frontend;

	/**
	 * @var SSM_Navigation
	 */
	private $navigation;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public static function activate() {
		self::instance()->register_content_types();
		flush_rewrite_rules();
	}

	public static function deactivate() {
		flush_rewrite_rules();
	}

	private function __construct() {
		$this->content    = new SSM_Content();
		$this->navigation = new SSM_Navigation( $this->content );
		$this->admin      = new SSM_Admin( $this->content, $this->navigation );
		$this->frontend   = new SSM_Frontend( $this->content, $this->navigation );

		add_action( 'init', array( $this, 'register_content_types' ) );
		add_action( 'init', array( $this->content, 'register_term_meta' ) );
		add_action( 'init', array( $this->content, 'migrate_empty_section_ids_to_home' ), 20 );
		add_action( 'after_setup_theme', array( $this->navigation, 'register_menu_location' ) );
		add_action( 'admin_enqueue_scripts', array( $this->admin, 'enqueue_section_admin_assets' ) );
		add_action( 'admin_bar_menu', array( $this->admin, 'register_section_admin_bar' ), 90 );
		add_action( 'wp_enqueue_scripts', array( $this->frontend, 'enqueue_assets' ) );
		add_action( 'wp_body_open', array( $this->frontend, 'render_global_header' ) );
		add_action( 'wp_footer', array( $this->frontend, 'render_global_header' ), 1 );
		add_filter( 'wp_nav_menu_args', array( $this->navigation, 'filter_nav_menu_args' ) );
		add_filter( 'wp_nav_menu_items', array( $this->navigation, 'filter_nav_menu_items' ), 10, 2 );
		add_action( 'add_meta_boxes', array( $this->admin, 'register_meta_boxes' ) );
		add_action( 'admin_init', array( $this->admin, 'register_term_form_fields' ) );
		add_action( 'admin_menu', array( $this->admin, 'register_section_admin_page' ) );
		add_action( 'admin_post_ssm_create_section', array( $this->admin, 'handle_create_section' ) );
		add_action( 'admin_post_ssm_delete_section', array( $this->admin, 'handle_delete_section' ) );
		add_action( 'admin_post_ssm_update_section', array( $this->admin, 'handle_update_section' ) );
		add_action( 'admin_post_ssm_save_navigation_settings', array( $this->admin, 'handle_save_navigation_settings' ) );
		add_action( 'admin_post_ssm_save_section_menu', array( $this->admin, 'handle_save_section_menu' ) );
		add_action( 'ssm_render_section_global_panels', array( $this->admin, 'render_navigation_settings' ) );
		add_action( 'save_post', array( $this->admin, 'save_post_section' ), 10, 2 );
		add_action( 'created_category', array( $this->admin, 'save_term_section' ), 10, 2 );
		add_action( 'edited_category', array( $this->admin, 'save_term_section' ), 10, 2 );
		add_action( 'created_post_tag', array( $this->admin, 'save_term_section' ), 10, 2 );
		add_action( 'edited_post_tag', array( $this->admin, 'save_term_section' ), 10, 2 );
		add_action( 'admin_menu', array( $this->admin, 'hide_native_content_menus' ), 100 );
		add_filter( 'manage_page_posts_columns', array( $this->admin, 'add_section_column' ) );
		add_action( 'manage_page_posts_custom_column', array( $this->admin, 'render_section_column' ), 10, 2 );
		add_filter( 'manage_post_posts_columns', array( $this->admin, 'add_section_column' ) );
		add_action( 'manage_post_posts_custom_column', array( $this->admin, 'render_section_column' ), 10, 2 );
		add_action( 'bulk_edit_custom_box', array( $this->admin, 'render_bulk_edit_section_field' ), 10, 2 );
		add_action( 'quick_edit_custom_box', array( $this->admin, 'render_quick_edit_section_field' ), 10, 3 );
		add_action( 'quick_edit_custom_box', array( $this->admin, 'render_term_quick_edit_section_field' ), 10, 3 );
		add_action( 'admin_footer-edit.php', array( $this->admin, 'output_quick_edit_script' ) );
		add_action( 'admin_footer-edit-tags.php', array( $this->admin, 'output_term_quick_edit_script' ) );
		add_action( 'bulk_edit_posts', array( $this->admin, 'save_bulk_edit_sections' ), 10, 2 );
		add_action( 'save_post', array( $this->admin, 'save_quick_edit_section' ), 20, 2 );
		add_filter( 'bulk_actions-edit-category', array( $this->admin, 'register_term_bulk_actions' ) );
		add_filter( 'bulk_actions-edit-post_tag', array( $this->admin, 'register_term_bulk_actions' ) );
		add_filter( 'handle_bulk_actions-edit-category', array( $this->admin, 'handle_term_bulk_actions' ), 10, 3 );
		add_filter( 'handle_bulk_actions-edit-post_tag', array( $this->admin, 'handle_term_bulk_actions' ), 10, 3 );
		add_action( 'restrict_manage_posts', array( $this->admin, 'render_admin_post_filters' ) );
		add_action( 'restrict_manage_terms', array( $this->admin, 'render_admin_term_filters' ) );
		add_action( 'pre_get_posts', array( $this->admin, 'filter_admin_post_queries' ) );
		add_action( 'pre_get_terms', array( $this->admin, 'filter_admin_term_queries' ) );
		add_filter( 'wp_count_posts', array( $this->admin, 'filter_wp_count_posts' ), 10, 3 );
	}

	public function register_content_types() {
		$this->content->register_site_section_cpt();
		$this->content->register_section_taxonomies();
	}
}
