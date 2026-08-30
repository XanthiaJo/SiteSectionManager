<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SSM_Content {
	const DEFAULT_SECTION_MIGRATION_OPTION = 'ssm_default_home_assigned';

	public function create_section_home_page( $section_id, $section_title ) {
		$existing_page_id = $this->get_section_home_page_id( $section_id );
		if ( $existing_page_id ) {
			return $existing_page_id;
		}

		$page_id = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => __( 'Home', 'site-section-manager' ),
				'post_name'    => sanitize_title( $section_title . '-home' ),
				'post_content' => '',
			),
			true
		);

		if ( is_wp_error( $page_id ) ) {
			return $page_id;
		}

		update_post_meta( $page_id, '_ssm_section_id', (int) $section_id );
		update_post_meta( $page_id, '_ssm_is_section_home', 1 );
		update_post_meta( $section_id, '_ssm_home_page_id', (int) $page_id );

		return (int) $page_id;
	}

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

	public function migrate_empty_section_ids_to_home() {
		if ( get_option( self::DEFAULT_SECTION_MIGRATION_OPTION ) ) {
			return;
		}

		$this->assign_home_section_to_unset_posts();
		$this->assign_home_section_to_unset_terms( 'category' );
		$this->assign_home_section_to_unset_terms( 'post_tag' );

		update_option( self::DEFAULT_SECTION_MIGRATION_OPTION, 1, false );
	}

	public function get_section_home_page_id( $section_id ) {
		$home_page_id = (int) get_post_meta( $section_id, '_ssm_home_page_id', true );
		if ( $home_page_id > 0 && 'page' === get_post_type( $home_page_id ) ) {
			return $home_page_id;
		}

		$pages = get_posts(
			array(
				'post_type'              => 'page',
				'post_status'            => array( 'publish', 'draft', 'pending', 'private' ),
				'posts_per_page'         => 1,
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'meta_query'             => array(
					array(
						'key'   => '_ssm_section_id',
						'value' => (int) $section_id,
					),
					array(
						'key'   => '_ssm_is_section_home',
						'value' => 1,
					),
				),
				'suppress_filters'       => true,
			)
		);

		if ( empty( $pages ) ) {
			return 0;
		}

		$home_page_id = (int) $pages[0];
		update_post_meta( $section_id, '_ssm_home_page_id', $home_page_id );

		return $home_page_id;
	}

	public function get_section_navigation_items() {
		$items = array(
			array(
				'section_id'    => 0,
				'section_title' => __( 'Home', 'site-section-manager' ),
				'home_page_id'  => 0,
				'url'           => $this->get_default_home_url(),
			),
		);

		foreach ( $this->get_sections() as $section ) {
			$home_page_id = $this->get_section_home_page_id( $section->ID );
			if ( ! $home_page_id ) {
				continue;
			}

			$items[] = array(
				'section_id'    => (int) $section->ID,
				'section_title' => get_the_title( $section ),
				'home_page_id'  => $home_page_id,
				'url'           => get_permalink( $home_page_id ),
			);
		}

		return $items;
	}

	public function get_section_content_items( $post_type, $section_id, $is_home = false ) {
		$args = array(
			'post_type'              => $post_type,
			'post_status'            => array( 'publish', 'draft', 'pending', 'private', 'future' ),
			'posts_per_page'         => -1,
			'orderby'                => 'title',
			'order'                  => 'ASC',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'suppress_filters'       => true,
		);

		if ( $is_home ) {
			$args['meta_query'] = array(
				'relation' => 'OR',
				array(
					'key'     => '_ssm_section_id',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'   => '_ssm_section_id',
					'value' => 0,
				),
			);
		} else {
			$args['meta_query'] = array(
				array(
					'key'   => '_ssm_section_id',
					'value' => (int) $section_id,
				),
			);
		}

		return get_posts( $args );
	}

	private function get_default_home_url() {
		$front_page_id = (int) get_option( 'page_on_front' );
		if ( $front_page_id > 0 ) {
			$front_page_url = get_permalink( $front_page_id );
			if ( $front_page_url ) {
				return $front_page_url;
			}
		}

		return home_url( '/' );
	}

	private function assign_home_section_to_unset_posts() {
		$post_ids = get_posts(
			array(
				'post_type'              => array( 'post', 'page' ),
				'post_status'            => array( 'publish', 'draft', 'pending', 'private', 'future' ),
				'posts_per_page'         => -1,
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'meta_query'             => array(
					array(
						'key'     => '_ssm_section_id',
						'compare' => 'NOT EXISTS',
					),
				),
				'suppress_filters'       => true,
			)
		);

		foreach ( $post_ids as $post_id ) {
			update_post_meta( $post_id, '_ssm_section_id', 0 );
		}
	}

	private function assign_home_section_to_unset_terms( $taxonomy ) {
		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'fields'     => 'ids',
				'meta_query' => array(
					array(
						'key'     => 'ssm_section_id',
						'compare' => 'NOT EXISTS',
					),
				),
			)
		);

		if ( is_wp_error( $terms ) ) {
			return;
		}

		foreach ( $terms as $term_id ) {
			update_term_meta( $term_id, 'ssm_section_id', 0 );
		}
	}
}
