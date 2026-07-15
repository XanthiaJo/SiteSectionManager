<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SSM_Section_Admin_Page_Data {
	public function get_unsectioned_summary() {
		return (object) array(
			'view_type'    => 'unsectioned',
			'ID'           => 0,
			'post_type'    => 'site_section',
			'post_title'   => __( 'Unsectioned', 'site-section-manager' ),
			'post_content' => __( 'Items that are not linked to a section.', 'site-section-manager' ),
			'posts_count'  => $this->get_unsectioned_count( 'post' ),
			'pages_count'  => $this->get_unsectioned_count( 'page' ),
			'link'         => admin_url( 'admin.php?page=ssm-site-sections&section_id=0' ),
		);
	}

	public function get_section_count( $post_type, $section_id ) {
		$query = new WP_Query(
			array(
				'post_type'              => $post_type,
				'post_status'            => array( 'publish', 'draft', 'pending', 'private' ),
				'posts_per_page'         => 1,
				'fields'                 => 'ids',
				'no_found_rows'          => false,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'meta_query'             => array(
					array(
						'key'   => '_ssm_section_id',
						'value' => (int) $section_id,
					),
				),
			)
		);

		return isset( $query->found_posts ) ? (int) $query->found_posts : 0;
	}

	public function get_section_term_count( $taxonomy, $section_id ) {
		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'fields'     => 'ids',
				'meta_query' => array(
					array(
						'key'   => 'ssm_section_id',
						'value' => (int) $section_id,
					),
				),
			)
		);

		if ( is_wp_error( $terms ) ) {
			return 0;
		}

		return count( $terms );
	}

	public function get_unsectioned_count( $post_type ) {
		$query = new WP_Query(
			array(
				'post_type'              => $post_type,
				'post_status'            => array( 'publish', 'draft', 'pending', 'private' ),
				'posts_per_page'         => 1,
				'fields'                 => 'ids',
				'no_found_rows'          => false,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'meta_query'             => array(
					'relation' => 'OR',
					array(
						'key'     => '_ssm_section_id',
						'compare' => 'NOT EXISTS',
					),
					array(
						'key'   => '_ssm_section_id',
						'value' => 0,
					),
				),
			)
		);

		return isset( $query->found_posts ) ? (int) $query->found_posts : 0;
	}

	public function get_delete_link( $section_id ) {
		return wp_nonce_url(
			add_query_arg(
				array(
					'action'     => 'ssm_delete_section',
					'section_id' => $section_id,
				),
				admin_url( 'admin-post.php' )
			),
			'ssm_delete_section_' . $section_id
		);
	}

	public function get_section_menu_slug( $section_id ) {
		return 'ssm-site-section-' . absint( $section_id );
	}

	public function get_section_admin_url( $section_id, array $args = array() ) {
		$slug = $this->get_section_menu_slug( $section_id );
		$url  = admin_url( 'admin.php?page=' . $slug );

		if ( ! empty( $args ) ) {
			$url = add_query_arg( $args, $url );
		}

		return $url;
	}

	private function get_unsectioned_count_internal( $post_type ) {
		return $this->get_unsectioned_count( $post_type );
	}
}
