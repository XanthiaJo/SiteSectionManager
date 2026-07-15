<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SSM_Admin {
	/**
	 * @var SSM_Section_Admin_Page
	 */
	private $section_page;

	/**
	 * @var SSM_Content_Admin
	 */
	private $content_admin;

	/**
	 * @var SSM_Navigation_Admin
	 */
	private $navigation_admin;

	public function __construct( SSM_Content $content, SSM_Navigation $navigation ) {
		$this->section_page    = new SSM_Section_Admin_Page( $content, $navigation );
		$this->content_admin   = new SSM_Content_Admin( $content );
		$this->navigation_admin = new SSM_Navigation_Admin( $navigation );
	}

	public function register_section_admin_page() {
		$this->section_page->register_admin_menu();
	}

	public function register_section_admin_bar( $wp_admin_bar ) {
		$this->section_page->register_admin_bar( $wp_admin_bar );
	}

	public function render_navigation_settings( $current_view = null ) {
		$this->navigation_admin->render_settings_card();
	}

	public function handle_save_navigation_settings() {
		$this->navigation_admin->handle_save_settings();
	}

	public function handle_save_section_menu() {
		$this->navigation_admin->handle_save_section_menu();
	}

	public function enqueue_section_admin_assets() {
		$this->section_page->enqueue_admin_assets();
	}

	public function hide_native_content_menus() {
		$this->content_admin->hide_native_content_menus();
	}

	public function register_meta_boxes() {
		$this->content_admin->register_meta_boxes();
	}

	public function register_term_form_fields() {
		$this->content_admin->register_term_form_fields();
	}

	public function handle_create_section() {
		$this->section_page->handle_create_section();
	}

	public function handle_delete_section() {
		$this->section_page->handle_delete_section();
	}

	public function handle_update_section() {
		$this->section_page->handle_update_section();
	}

	public function save_post_section( $post_id, $post ) {
		$this->content_admin->save_post_section( $post_id, $post );
	}

	public function save_term_section( $term_id, $tt_id ) {
		$this->content_admin->save_term_section( $term_id, $tt_id );
	}

	public function render_term_section_add_field() {
		$this->content_admin->render_term_section_add_field();
	}

	public function render_term_section_edit_field( $term ) {
		$this->content_admin->render_term_section_edit_field( $term );
	}

	public function add_section_column( $columns ) {
		return $this->content_admin->add_section_column( $columns );
	}

	public function render_section_column( $column, $post_id ) {
		$this->content_admin->render_section_column( $column, $post_id );
	}

	public function render_bulk_edit_section_field( $column_name, $post_type ) {
		$this->content_admin->render_bulk_edit_section_field( $column_name, $post_type );
	}

	public function render_quick_edit_section_field( $column_name, $post_type, $taxonomy = '' ) {
		$this->content_admin->render_quick_edit_section_field( $column_name, $post_type, $taxonomy );
	}

	public function output_quick_edit_script() {
		$this->content_admin->output_quick_edit_script();
	}

	public function render_term_quick_edit_section_field( $column_name, $screen, $taxonomy ) {
		$this->content_admin->render_term_quick_edit_section_field( $column_name, $screen, $taxonomy );
	}

	public function output_term_quick_edit_script() {
		$this->content_admin->output_term_quick_edit_script();
	}

	public function save_bulk_edit_sections( $post_ids, $shared_post_data ) {
		$this->content_admin->save_bulk_edit_sections( $post_ids, $shared_post_data );
	}

	public function save_quick_edit_section( $post_id, $post ) {
		$this->content_admin->save_quick_edit_section( $post_id, $post );
	}

	public function register_term_bulk_actions( $actions ) {
		return $this->content_admin->register_term_bulk_actions( $actions );
	}

	public function handle_term_bulk_actions( $redirect_to, $action, $term_ids ) {
		return $this->content_admin->handle_term_bulk_actions( $redirect_to, $action, $term_ids );
	}

	public function render_admin_post_filters() {
		$this->content_admin->render_admin_post_filters();
	}

	public function render_admin_term_filters( $taxonomy = '' ) {
		$this->content_admin->render_admin_term_filters( $taxonomy );
	}

	public function filter_admin_post_queries( $query ) {
		$this->content_admin->filter_admin_post_queries( $query );
	}

	public function filter_admin_term_queries( $query ) {
		$this->content_admin->filter_admin_term_queries( $query );
	}

	public function filter_wp_count_posts( $counts, $post_type, $perm ) {
		return $this->content_admin->filter_wp_count_posts( $counts, $post_type, $perm );
	}
}
