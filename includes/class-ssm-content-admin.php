<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SSM_Content_Admin {
	/**
	 * @var SSM_Content_Admin_Display
	 */
	private $display;

	/**
	 * @var SSM_Content_Admin_Actions
	 */
	private $actions;

	public function __construct( SSM_Content $content ) {
		$this->display = new SSM_Content_Admin_Display( $content );
		$this->actions = new SSM_Content_Admin_Actions( $content );
	}

	public function register_hooks() {
		add_action( 'add_meta_boxes', array( $this, 'register_meta_boxes' ) );
		add_action( 'admin_init', array( $this, 'register_term_form_fields' ) );
		add_action( 'save_post', array( $this, 'save_post_section' ), 10, 2 );
		add_action( 'created_category', array( $this, 'save_term_section' ), 10, 2 );
		add_action( 'edited_category', array( $this, 'save_term_section' ), 10, 2 );
		add_action( 'created_post_tag', array( $this, 'save_term_section' ), 10, 2 );
		add_action( 'edited_post_tag', array( $this, 'save_term_section' ), 10, 2 );
		add_filter( 'manage_page_posts_columns', array( $this, 'add_section_column' ) );
		add_action( 'manage_page_posts_custom_column', array( $this, 'render_section_column' ), 10, 2 );
		add_filter( 'manage_post_posts_columns', array( $this, 'add_section_column' ) );
		add_action( 'manage_post_posts_custom_column', array( $this, 'render_section_column' ), 10, 2 );
		add_action( 'restrict_manage_posts', array( $this, 'render_admin_post_filters' ) );
		add_action( 'restrict_manage_terms', array( $this, 'render_admin_term_filters' ) );
		add_action( 'pre_get_posts', array( $this, 'filter_admin_post_queries' ) );
		add_action( 'pre_get_terms', array( $this, 'filter_admin_term_queries' ) );
		add_filter( 'views_edit-post', array( $this, 'filter_post_views' ) );
		add_filter( 'views_edit-page', array( $this, 'filter_page_views' ) );
	}

	public function hide_native_content_menus() {
		remove_menu_page( 'edit.php' );
		remove_menu_page( 'edit.php?post_type=page' );
	}

	public function register_meta_boxes() {
		foreach ( array( 'page', 'post' ) as $post_type ) {
			add_meta_box(
				'ssm_section_selector',
				__( 'Site Section', 'site-section-manager' ),
				array( $this->display, 'render_section_meta_box' ),
				$post_type,
				'side',
				'default'
			);
		}
	}

	public function register_term_form_fields() {
		add_action( 'category_add_form_fields', array( $this->display, 'render_term_section_add_field' ) );
		add_action( 'category_edit_form_fields', array( $this->display, 'render_term_section_edit_field' ), 10, 2 );
		add_action( 'post_tag_add_form_fields', array( $this->display, 'render_term_section_add_field' ) );
		add_action( 'post_tag_edit_form_fields', array( $this->display, 'render_term_section_edit_field' ), 10, 2 );
		add_filter( 'manage_edit-category_columns', array( $this->display, 'add_term_section_column' ) );
		add_filter( 'manage_category_custom_column', array( $this->display, 'render_term_section_column' ), 10, 3 );
		add_filter( 'manage_edit-post_tag_columns', array( $this->display, 'add_term_section_column' ) );
		add_filter( 'manage_post_tag_custom_column', array( $this->display, 'render_term_section_column' ), 10, 3 );
	}

	public function render_admin_post_filters() {
		$this->actions->render_admin_post_filters();
	}

	public function render_admin_term_filters( $taxonomy = '' ) {
		$this->actions->render_admin_term_filters( $taxonomy );
	}

	public function save_post_section( $post_id, $post ) {
		$this->actions->save_post_section( $post_id, $post );
	}

	public function save_term_section( $term_id, $tt_id ) {
		$this->actions->save_term_section( $term_id, $tt_id );
	}

	public function add_section_column( $columns ) {
		return $this->display->add_section_column( $columns );
	}

	public function render_section_column( $column, $post_id ) {
		$this->display->render_section_column( $column, $post_id );
	}

	public function filter_admin_post_queries( $query ) {
		$this->actions->filter_admin_post_queries( $query );
	}

	public function filter_admin_term_queries( $query ) {
		$this->actions->filter_admin_term_queries( $query );
	}

	public function filter_post_views( $views ) {
		return $this->actions->filter_post_views( $views, 'post' );
	}

	public function filter_page_views( $views ) {
		return $this->actions->filter_post_views( $views, 'page' );
	}
}
