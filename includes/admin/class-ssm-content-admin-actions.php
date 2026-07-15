<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SSM_Content_Admin_Actions {
	/**
	 * @var SSM_Content
	 */
	private $content;

	public function __construct( SSM_Content $content ) {
		$this->content = $content;
	}

	public function render_admin_post_filters() {
		global $typenow;
		global $pagenow;

		if ( 'edit.php' !== $pagenow || ! in_array( $typenow, array( 'post', 'page' ), true ) ) {
			$this->debug_log( 'render_admin_post_filters skipped', array( 'pagenow' => $pagenow, 'typenow' => $typenow ) );
			return;
		}

		$selected = isset( $_GET['ssm_section_id'] ) && '' !== (string) wp_unslash( $_GET['ssm_section_id'] ) ? absint( $_GET['ssm_section_id'] ) : '';
		$sections = $this->content->get_sections();

		$this->debug_log(
			'render_admin_post_filters output',
			array(
				'post_type' => $typenow,
				'selected'  => $selected,
				'sections'  => count( $sections ),
			)
		);

		echo '<select name="ssm_section_id">';
		echo '<option value="">' . esc_html__( 'All Site Sections', 'site-section-manager' ) . '</option>';
		echo '<option value="0"' . selected( $selected, '0', false ) . '>' . esc_html__( 'Home', 'site-section-manager' ) . '</option>';

		foreach ( $sections as $section ) {
			printf(
				'<option value="%d"%s>%s</option>',
				(int) $section->ID,
				selected( $selected, $section->ID, false ),
				esc_html( get_the_title( $section ) )
			);
		}

		echo '</select>';
	}

	public function render_admin_term_filters( $taxonomy = '' ) {
		if ( ! in_array( $taxonomy, array( 'category', 'post_tag' ), true ) ) {
			return;
		}

		$selected = isset( $_GET['ssm_section_id'] ) && '' !== (string) wp_unslash( $_GET['ssm_section_id'] ) ? absint( $_GET['ssm_section_id'] ) : '';
		$sections = $this->content->get_sections();

		echo '<select name="ssm_section_id">';
		echo '<option value="">' . esc_html__( 'All Site Sections', 'site-section-manager' ) . '</option>';
		echo '<option value="0"' . selected( $selected, '0', false ) . '>' . esc_html__( 'Home', 'site-section-manager' ) . '</option>';

		foreach ( $sections as $section ) {
			printf(
				'<option value="%d"%s>%s</option>',
				(int) $section->ID,
				selected( $selected, $section->ID, false ),
				esc_html( get_the_title( $section ) )
			);
		}

		echo '</select>';
	}

	public function save_post_section( $post_id, $post ) {
		if ( ! isset( $_POST['ssm_section_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ssm_section_nonce'] ) ), 'ssm_save_section' ) ) {
			return;
		}

		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$section_id = isset( $_POST['ssm_section_id'] ) ? absint( wp_unslash( $_POST['ssm_section_id'] ) ) : 0;
		update_post_meta( $post_id, '_ssm_section_id', $section_id );
	}

	public function save_term_section( $term_id, $tt_id ) {
		if ( ! isset( $_POST['ssm_term_section_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ssm_term_section_nonce'] ) ), 'ssm_save_term_section' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_categories' ) ) {
			return;
		}

		if ( ! isset( $_POST['ssm_term_section_id'] ) ) {
			return;
		}

		update_term_meta( $term_id, 'ssm_section_id', absint( wp_unslash( $_POST['ssm_term_section_id'] ) ) );
	}

	public function filter_admin_post_queries( $query ) {
		global $pagenow;

		if ( ! is_admin() || 'edit.php' !== $pagenow || ! is_a( $query, 'WP_Query' ) || ! $query->is_main_query() ) {
			$this->debug_log(
				'filter_admin_post_queries skipped',
				array(
					'is_admin'      => is_admin(),
					'pagenow'       => $pagenow,
					'is_wp_query'   => is_a( $query, 'WP_Query' ),
					'is_main_query' => is_a( $query, 'WP_Query' ) ? $query->is_main_query() : false,
				)
			);
			return;
		}

		$post_type = $query->get( 'post_type' );
		if ( ! in_array( $post_type, array( 'post', 'page' ), true ) ) {
			$this->debug_log( 'filter_admin_post_queries ignored unsupported post type', array( 'post_type' => $post_type ) );
			return;
		}

		if ( ! isset( $_GET['ssm_section_id'] ) || '' === (string) wp_unslash( $_GET['ssm_section_id'] ) ) {
			$this->debug_log( 'filter_admin_post_queries no section selected', array( 'post_type' => $post_type ) );
			return;
		}

		$section_id  = absint( $_GET['ssm_section_id'] );
		$section_ids = $this->get_section_post_ids( $post_type, $section_id );
		if ( empty( $section_ids ) ) {
			$section_ids = array( 0 );
		}

		$this->debug_log(
			'filter_admin_post_queries applying section filter',
			array(
				'post_type'  => $post_type,
				'section_id' => $section_id,
				'post_count' => count( $section_ids ),
			)
		);

		$query->set( 'post__in', $section_ids );
		$query->set( 'orderby', 'post__in' );
	}

	public function filter_admin_term_queries( $query ) {
		if ( ! is_admin() || ! is_a( $query, 'WP_Term_Query' ) ) {
			$this->debug_log(
				'filter_admin_term_queries skipped',
				array(
					'is_admin'     => is_admin(),
					'is_term_query' => is_a( $query, 'WP_Term_Query' ),
				)
			);
			return;
		}

		$taxonomy = isset( $query->query_vars['taxonomy'] ) ? $query->query_vars['taxonomy'] : '';
		if ( is_array( $taxonomy ) ) {
			$taxonomy = reset( $taxonomy );
		}

		if ( ! in_array( $taxonomy, array( 'category', 'post_tag' ), true ) ) {
			$this->debug_log( 'filter_admin_term_queries ignored unsupported taxonomy', array( 'taxonomy' => $taxonomy ) );
			return;
		}

		if ( ! isset( $_GET['ssm_section_id'] ) || '' === (string) wp_unslash( $_GET['ssm_section_id'] ) ) {
			$this->debug_log( 'filter_admin_term_queries no section selected', array( 'taxonomy' => $taxonomy ) );
			return;
		}

		$section_id = absint( $_GET['ssm_section_id'] );
		$this->debug_log(
			'filter_admin_term_queries applying section filter',
			array(
				'taxonomy'   => $taxonomy,
				'section_id' => $section_id,
			)
		);

		if ( 0 === $section_id ) {
			$query->query_vars['meta_query'] = array(
				'relation' => 'OR',
				array(
					'key'     => 'ssm_section_id',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'   => 'ssm_section_id',
					'value' => 0,
				),
			);
			return;
		}

		$query->query_vars['meta_query'] = array(
			array(
				'key'   => 'ssm_section_id',
				'value' => $section_id,
			),
		);
	}

	public function register_term_bulk_actions( $actions ) {
		$actions['ssm_set_section_0'] = __( 'Change Site Section to Home', 'site-section-manager' );

		foreach ( $this->content->get_sections() as $section ) {
			$actions[ 'ssm_set_section_' . (int) $section->ID ] = sprintf(
				/* translators: %s: Section title. */
				__( 'Change Site Section to %s', 'site-section-manager' ),
				get_the_title( $section )
			);
		}

		return $actions;
	}

	public function handle_term_bulk_actions( $redirect_to, $action, $term_ids ) {
		if ( 0 !== strpos( (string) $action, 'ssm_set_section_' ) ) {
			return $redirect_to;
		}

		$section_id = absint( substr( (string) $action, strlen( 'ssm_set_section_' ) ) );
		$updated    = 0;

		foreach ( (array) $term_ids as $term_id ) {
			$term_id = absint( $term_id );
			if ( ! $term_id || ! current_user_can( 'edit_term', $term_id ) ) {
				continue;
			}

			update_term_meta( $term_id, 'ssm_section_id', $section_id );
			$updated++;
		}

		return add_query_arg(
			array(
				'ssm_term_bulk_updated' => $updated,
				'ssm_term_bulk_section' => $section_id,
			),
			$redirect_to
		);
	}

	public function filter_wp_count_posts( $counts, $post_type, $perm ) {
		if ( ! in_array( $post_type, array( 'post', 'page' ), true ) || ! isset( $_GET['ssm_section_id'] ) || '' === (string) wp_unslash( $_GET['ssm_section_id'] ) ) {
			return $counts;
		}

		$section_id = absint( $_GET['ssm_section_id'] );
		$section_counts = $this->get_section_post_status_counts( $post_type, $section_id );
		$filtered_counts = $this->normalize_count_object( $counts, $section_counts );

		$this->debug_log(
			'filter_wp_count_posts updated counts',
			array(
				'post_type'      => $post_type,
				'section_id'     => $section_id,
				'counts'         => $section_counts,
				'original_counts'=> $counts,
			)
		);

		return $filtered_counts;
	}

	private function get_section_post_ids( $post_type, $section_id ) {
		if ( 0 === (int) $section_id ) {
			return $this->get_unsectioned_post_ids( $post_type );
		}

		$post_ids = get_posts(
			array(
				'post_type'              => $post_type,
				'post_status'            => array( 'publish', 'draft', 'pending', 'private' ),
				'posts_per_page'         => -1,
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'meta_query'             => array(
					array(
						'key'   => '_ssm_section_id',
						'value' => (int) $section_id,
					),
				),
				'suppress_filters'       => true,
			)
		);

		if ( is_wp_error( $post_ids ) ) {
			return array();
		}

		return array_map( 'absint', $post_ids );
	}

	private function get_unsectioned_post_ids( $post_type ) {
		$post_ids = get_posts(
			array(
				'post_type'              => $post_type,
				'post_status'            => array( 'publish', 'draft', 'pending', 'private' ),
				'posts_per_page'         => -1,
				'fields'                 => 'ids',
				'no_found_rows'          => true,
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
				'suppress_filters'       => true,
			)
		);

		if ( is_wp_error( $post_ids ) ) {
			return array();
		}

		return array_map( 'absint', $post_ids );
	}

	private function get_section_post_status_counts( $post_type, $section_id ) {
		$post_ids = 0 === (int) $section_id
			? $this->get_unsectioned_post_ids( $post_type )
			: $this->get_section_post_ids( $post_type, $section_id );

		$counts = array(
			'all'     => 0,
			'publish' => 0,
			'draft'   => 0,
			'pending' => 0,
			'private' => 0,
			'future'  => 0,
			'trash'   => 0,
		);

		foreach ( $post_ids as $post_id ) {
			$status = get_post_status( $post_id );
			if ( ! isset( $counts[ $status ] ) ) {
				$counts[ $status ] = 0;
			}

			$counts[ $status ]++;
			$counts['all']++;
		}

		return $counts;
	}

	private function normalize_count_object( $original_counts, array $section_counts ) {
		$normalized = is_object( $original_counts ) ? clone $original_counts : (object) array();

		foreach ( array( 'publish', 'future', 'draft', 'pending', 'private', 'trash', 'auto-draft', 'inherit', 'all' ) as $status ) {
			$normalized->{$status} = isset( $section_counts[ $status ] ) ? (int) $section_counts[ $status ] : 0;
		}

		if ( isset( $normalized->all ) ) {
			$normalized->all = (int) $section_counts['all'];
		}

		return $normalized;
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
