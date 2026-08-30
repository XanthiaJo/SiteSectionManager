<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SSM_Section_Admin_Page_Data {
	/**
	 * @var SSM_Content
	 */
	private $content;

	/**
	 * @var SSM_Navigation
	 */
	private $navigation;

	public function __construct( SSM_Content $content, SSM_Navigation $navigation ) {
		$this->content    = $content;
		$this->navigation = $navigation;
	}

	public function get_unsectioned_summary() {
		$menu_id = $this->navigation->get_home_menu_id();

		return (object) array(
			'view_type'    => 'unsectioned',
			'ID'           => 0,
			'post_type'    => 'site_section',
			'post_title'   => __( 'Home', 'site-section-manager' ),
			'post_content' => __( 'Default content that belongs to the main Home section.', 'site-section-manager' ),
			'posts_count'  => $this->get_unsectioned_count( 'post' ),
			'pages_count'  => $this->get_unsectioned_count( 'page' ),
			'categories_count' => $this->get_unsectioned_term_count( 'category' ),
			'tags_count'   => $this->get_unsectioned_term_count( 'post_tag' ),
			'link'         => admin_url( 'admin.php?page=ssm-site-sections&section_id=0' ),
			'menu_id'      => $menu_id,
			'menu_link'    => $this->navigation->get_menu_edit_url( $menu_id ),
		);
	}

	public function get_section_summaries( array $sections ) {
		$summaries = array();

		foreach ( $sections as $section ) {
			$summaries[] = $this->get_section_summary( $section );
		}

		return $summaries;
	}

	public function get_section_summary( $section ) {
		$section_id = (int) $section->ID;
		$menu_id    = $this->navigation->get_section_menu_id( $section_id, get_the_title( $section ) );

		return (object) array(
			'view_type'         => 'section',
			'ID'                => $section_id,
			'post_title'        => get_the_title( $section ),
			'post_content'      => $section->post_content,
			'posts_count'       => $this->get_section_count( 'post', $section_id ),
			'pages_count'       => $this->get_section_count( 'page', $section_id ),
			'categories_count'  => $this->get_section_term_count( 'category', $section_id ),
			'tags_count'        => $this->get_section_term_count( 'post_tag', $section_id ),
			'link'              => $this->get_section_admin_url( $section_id ),
			'delete_link'       => $this->get_delete_link( $section_id ),
			'menu_id'           => $menu_id,
			'menu_link'         => $this->navigation->get_menu_edit_url( $menu_id ),
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

	public function get_unsectioned_term_count( $taxonomy ) {
		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'fields'     => 'ids',
				'meta_query' => array(
					'relation' => 'OR',
					array(
						'key'     => 'ssm_section_id',
						'compare' => 'NOT EXISTS',
					),
					array(
						'key'   => 'ssm_section_id',
						'value' => 0,
					),
				),
			)
		);

		if ( is_wp_error( $terms ) ) {
			return 0;
		}

		return count( $terms );
	}

	public function get_section_content_items( $post_type, $section_id, $is_home = false ) {
		return $this->content->get_section_content_items( $post_type, $section_id, $is_home );
	}

	public function get_menu_items( $menu_id ) {
		return $this->navigation->get_menu_items( $menu_id );
	}

	public function is_section_menu_auto( $section_id, $is_home = false ) {
		return $this->navigation->is_section_menu_auto( $section_id, $is_home );
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
}
