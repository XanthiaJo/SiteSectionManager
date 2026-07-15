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
		$this->content = new SSM_Content();
		$this->admin   = new SSM_Admin( $this->content );

		add_action( 'init', array( $this, 'register_content_types' ) );
		add_action( 'init', array( $this->content, 'register_term_meta' ) );
		add_action( 'admin_enqueue_scripts', array( $this->admin, 'enqueue_section_admin_assets' ) );
		add_action( 'add_meta_boxes', array( $this->admin, 'register_meta_boxes' ) );
		add_action( 'admin_init', array( $this->admin, 'register_term_form_fields' ) );
		add_action( 'admin_menu', array( $this->admin, 'register_section_admin_page' ) );
		add_action( 'admin_post_ssm_create_section', array( $this->admin, 'handle_create_section' ) );
		add_action( 'admin_post_ssm_delete_section', array( $this->admin, 'handle_delete_section' ) );
		add_action( 'admin_post_ssm_update_section', array( $this->admin, 'handle_update_section' ) );
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
		add_action( 'restrict_manage_posts', array( $this->admin, 'render_admin_post_filters' ) );
		add_action( 'restrict_manage_terms', array( $this->admin, 'render_admin_term_filters' ) );
		add_action( 'pre_get_posts', array( $this->admin, 'filter_admin_post_queries' ) );
		add_action( 'pre_get_terms', array( $this->admin, 'filter_admin_term_queries' ) );
	}

	public function register_content_types() {
		$this->content->register_site_section_cpt();
		$this->content->register_section_taxonomies();
	}
}
