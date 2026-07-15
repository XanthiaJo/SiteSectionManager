<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SSM_Content {
	public function register_site_section_cpt() {
		register_post_type(
			'site_section',
			array(
				'labels' => array(
					'name'               => __( 'Site Sections', 'site-section-manager' ),
					'singular_name'      => __( 'Site Section', 'site-section-manager' ),
					'add_new'            => __( 'Create Site Section', 'site-section-manager' ),
					'add_new_item'       => __( 'Create Site Section', 'site-section-manager' ),
					'edit_item'          => __( 'Edit Site Section', 'site-section-manager' ),
					'new_item'           => __( 'New Site Section', 'site-section-manager' ),
					'view_item'          => __( 'View Site Section', 'site-section-manager' ),
					'search_items'       => __( 'Search Site Sections', 'site-section-manager' ),
					'not_found'          => __( 'No site sections found.', 'site-section-manager' ),
					'not_found_in_trash' => __( 'No site sections found in trash.', 'site-section-manager' ),
					'all_items'          => __( 'All Site Sections', 'site-section-manager' ),
					'menu_name'          => __( 'Site Sections', 'site-section-manager' ),
				),
				'public'              => false,
				'show_ui'             => false,
				'show_in_menu'        => false,
				'show_in_rest'        => true,
				'supports'            => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
				'capability_type'     => 'post',
				'map_meta_cap'        => true,
				'menu_icon'           => 'dashicons-networking',
			)
		);
	}

	public function register_section_taxonomies() {
		register_taxonomy_for_object_type( 'category', 'page' );
		register_taxonomy_for_object_type( 'post_tag', 'page' );
	}

	public function register_term_meta() {
		if ( ! function_exists( 'register_term_meta' ) ) {
			return;
		}

		register_term_meta(
			'category',
			'ssm_section_id',
			array(
				'type'         => 'integer',
				'single'       => true,
				'show_in_rest' => true,
				'auth_callback' => '__return_true',
			)
		);

		register_term_meta(
			'post_tag',
			'ssm_section_id',
			array(
				'type'         => 'integer',
				'single'       => true,
				'show_in_rest' => true,
				'auth_callback' => '__return_true',
			)
		);
	}

	public function get_sections() {
		return get_posts(
			array(
				'post_type'        => 'site_section',
				'post_status'      => array( 'publish', 'draft', 'pending', 'private' ),
				'posts_per_page'   => -1,
				'orderby'          => 'title',
				'order'            => 'ASC',
				'suppress_filters' => true,
			)
		);
	}
}
