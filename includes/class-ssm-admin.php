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

	public function __construct( SSM_Content $content ) {
		$this->section_page = new SSM_Section_Admin_Page( $content );
		$this->content_admin = new SSM_Content_Admin( $content );
	}

	public function register_section_admin_page() {
		$this->section_page->register_admin_menu();
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
}
