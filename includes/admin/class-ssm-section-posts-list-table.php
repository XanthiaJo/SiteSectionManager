<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table', false ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

if ( ! class_exists( 'WP_Posts_List_Table', false ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-posts-list-table.php';
}

final class SSM_Section_Posts_List_Table extends WP_Posts_List_Table {
	/**
	 * @var int
	 */
	private $section_id;

	/**
	 * @var bool
	 */
	private $is_home;

	/**
	 * @var string
	 */
	private $page_var;

	/**
	 * @var array<string,int>
	 */
	private $status_counts = array();

	public function __construct( $post_type, $section_id, $is_home = false ) {
		$screen = convert_to_screen( 'edit-' . $post_type );
		$screen->post_type = $post_type;

		parent::__construct(
			array(
				'screen' => $screen,
			)
		);

		$this->section_id = (int) $section_id;
		$this->is_home    = (bool) $is_home;
		$this->page_var   = 'ssm_' . $post_type . '_paged';
	}

	public function get_pagenum() {
		$pagenum = isset( $_REQUEST[ $this->page_var ] ) ? absint( wp_unslash( $_REQUEST[ $this->page_var ] ) ) : 0;

		if ( isset( $this->_pagination_args['total_pages'] ) && $pagenum > $this->_pagination_args['total_pages'] ) {
			$pagenum = $this->_pagination_args['total_pages'];
		}

		return max( 1, $pagenum );
	}

	public function prepare_items() {
		global $per_page;

		$post_type = $this->screen->post_type;
		$per_page  = $this->get_items_per_page( 'edit_' . $post_type . '_per_page' );
		$args      = $this->get_query_args();
		$args['posts_per_page'] = $per_page;
		$args['paged']          = $this->get_pagenum();
		$args['fields']         = 'all';

		$query = new WP_Query( $args );

		$this->items = $query->posts;
		$this->set_hierarchical_display(
			is_post_type_hierarchical( $post_type )
			&& 'menu_order title' === ( $query->query['orderby'] ?? '' )
		);

		$this->set_pagination_args(
			array(
				'total_items' => (int) $query->found_posts,
				'per_page'    => (int) $per_page,
			)
		);
	}

	public function has_items() {
		return ! empty( $this->items );
	}

	public function display_rows_or_placeholder() {
		if ( $this->has_items() ) {
			$this->display_rows( $this->items );
			return;
		}

		echo '<tr class="no-items"><td class="colspanchange" colspan="' . esc_attr( $this->get_column_count() ) . '">';
		$this->no_items();
		echo '</td></tr>';
	}

	public function get_views() {
		$counts     = $this->get_status_counts();
		$views      = array();
		$base_args  = $this->get_base_link_args();
		$current    = isset( $_REQUEST['post_status'] ) ? sanitize_key( wp_unslash( $_REQUEST['post_status'] ) ) : '';
		$all_total  = array_sum( $counts );
		$all_label  = sprintf( _nx( 'All <span class="count">(%s)</span>', 'All <span class="count">(%s)</span>', $all_total, 'posts' ), number_format_i18n( $all_total ) );

		$views['all'] = array(
			'url'     => esc_url( add_query_arg( $base_args, admin_url( 'admin.php' ) ) ),
			'label'   => $all_label,
			'current' => '' === $current || ! isset( $counts[ $current ] ),
		);

		foreach ( array( 'publish' => __( 'Published' ), 'draft' => __( 'Draft' ), 'pending' => __( 'Pending' ), 'private' => __( 'Private' ), 'future' => __( 'Scheduled' ) ) as $status => $label ) {
			if ( empty( $counts[ $status ] ) ) {
				continue;
			}

			$views[ $status ] = array(
				'url'     => esc_url( add_query_arg( array_merge( $base_args, array( 'post_status' => $status ) ), admin_url( 'admin.php' ) ) ),
				'label'   => sprintf( _nx( '%s <span class="count">(%s)</span>', '%s <span class="count">(%s)</span>', $counts[ $status ], 'posts' ), $label, number_format_i18n( $counts[ $status ] ) ),
				'current' => $current === $status,
			);
		}

		return $this->get_views_links( $views );
	}

	protected function pagination( $which ) {
		if ( empty( $this->_pagination_args['total_items'] ) ) {
			return;
		}

		$total_items    = $this->_pagination_args['total_items'];
		$total_pages    = $this->_pagination_args['total_pages'];
		$current        = $this->get_pagenum();
		$current_url    = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
		$current_url    = remove_query_arg( wp_removable_query_args(), $current_url );
		$current_url    = remove_query_arg( array( 'paged', $this->page_var ), $current_url );
		$page_links     = array();
		$disable_first  = 1 === $current;
		$disable_prev   = 1 === $current;
		$disable_last   = $total_pages === $current;
		$disable_next   = $total_pages === $current;
		$format         = $this->page_var;

		if ( 'top' === $which && $total_pages > 1 ) {
			$this->screen->render_screen_reader_content( 'heading_pagination' );
		}

		$output = '<span class="displaying-num">' . sprintf( _n( '%s item', '%s items', $total_items ), number_format_i18n( $total_items ) ) . '</span>';

		$total_pages_before = '<span class="paging-input">';
		$total_pages_after  = '</span></span>';

		if ( $disable_first ) {
			$page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&laquo;</span>';
		} else {
			$page_links[] = sprintf(
				"<a class='first-page button' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
				esc_url( remove_query_arg( $format, $current_url ) ),
				__( 'First page' ),
				'&laquo;'
			);
		}

		if ( $disable_prev ) {
			$page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&lsaquo;</span>';
		} else {
			$page_links[] = sprintf(
				"<a class='prev-page button' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
				esc_url( add_query_arg( $format, max( 1, $current - 1 ), $current_url ) ),
				__( 'Previous page' ),
				'&lsaquo;'
			);
		}

		$html_current_page  = $current;
		$total_pages_before .= sprintf(
			'<span class="paging-input"><label for="current-page-selector-%1$s" class="screen-reader-text">%2$s</label><input class="current-page" id="current-page-selector-%1$s" type="text" name="%3$s" value="%4$s" size="1" aria-describedby="table-paging-%1$s" /></span>',
			esc_attr( $this->screen->id ),
			__( 'Current Page' ),
			esc_attr( $format ),
			esc_attr( $html_current_page )
		);

		$page_links[] = $total_pages_before . number_format_i18n( $total_pages ) . $total_pages_after;

		if ( $disable_next ) {
			$page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&rsaquo;</span>';
		} else {
			$page_links[] = sprintf(
				"<a class='next-page button' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
				esc_url( add_query_arg( $format, min( $total_pages, $current + 1 ), $current_url ) ),
				__( 'Next page' ),
				'&rsaquo;'
			);
		}

		if ( $disable_last ) {
			$page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&raquo;</span>';
		} else {
			$page_links[] = sprintf(
				"<a class='last-page button' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
				esc_url( add_query_arg( $format, $total_pages, $current_url ) ),
				__( 'Last page' ),
				'&raquo;'
			);
		}

		$pagination_links_class = 'pagination-links';

		if ( ! empty( $this->_pagination_args['infinite_scroll'] ) ) {
			$pagination_links_class .= ' hide-if-js';
		}

		$output .= '<span class="' . esc_attr( $pagination_links_class ) . '">' . implode( "\n", $page_links ) . '</span>';

		$class = 'tablenav-pages';
		if ( 'bottom' === $which ) {
			$class .= ' one-page';
		}
		?>
		<div class="<?php echo esc_attr( $class ); ?>">
			<?php echo $output; ?>
		</div>
		<?php
	}

	private function get_query_args() {
		$args = $this->get_base_query_args();

		$args['posts_per_page'] = 1;
		$args['fields']         = 'ids';
		$args['no_found_rows']   = false;

		$orderby = isset( $_REQUEST['orderby'] ) ? sanitize_key( wp_unslash( $_REQUEST['orderby'] ) ) : '';
		$order   = isset( $_REQUEST['order'] ) && 'asc' === strtolower( (string) wp_unslash( $_REQUEST['order'] ) ) ? 'ASC' : 'DESC';

		if ( '' !== $orderby ) {
			$args['orderby'] = $orderby;
			$args['order']   = $order;
		}

		if ( ! empty( $_REQUEST['s'] ) ) {
			$args['s'] = sanitize_text_field( wp_unslash( $_REQUEST['s'] ) );
		}

		if ( ! empty( $_REQUEST['m'] ) ) {
			$args['m'] = preg_replace( '/[^0-9]/', '', (string) wp_unslash( $_REQUEST['m'] ) );
		}

		if ( 'post' === $this->screen->post_type ) {
			if ( isset( $_REQUEST['cat'] ) ) {
				$args['cat'] = absint( wp_unslash( $_REQUEST['cat'] ) );
			}

			if ( isset( $_REQUEST['author'] ) ) {
				$args['author'] = absint( wp_unslash( $_REQUEST['author'] ) );
			}
		}

		$status = isset( $_REQUEST['post_status'] ) ? sanitize_key( wp_unslash( $_REQUEST['post_status'] ) ) : '';
		if ( '' !== $status ) {
			$args['post_status'] = $status;
		}

		return $args;
	}

	private function get_base_query_args() {
		return array(
			'post_type'              => $this->screen->post_type,
			'post_status'            => array( 'publish', 'draft', 'pending', 'private', 'future' ),
			'orderby'                => is_post_type_hierarchical( $this->screen->post_type ) ? 'menu_order title' : 'date',
			'order'                  => is_post_type_hierarchical( $this->screen->post_type ) ? 'ASC' : 'DESC',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'ignore_sticky_posts'    => true,
			'meta_query'             => $this->get_section_meta_query(),
		);
	}

	private function get_base_link_args() {
		return array(
			'page'       => $this->is_home ? 'ssm-site-sections' : 'ssm-site-section-' . $this->section_id,
			'section_id' => $this->is_home ? 0 : $this->section_id,
			'post_type'  => $this->screen->post_type,
		);
	}

	private function get_section_meta_query() {
		if ( $this->is_home ) {
			return array(
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
		}

		return array(
			array(
				'key'   => '_ssm_section_id',
				'value' => $this->section_id,
			),
		);
	}

	private function get_status_counts() {
		if ( ! empty( $this->status_counts ) ) {
			return $this->status_counts;
		}

		$query = new WP_Query(
			array(
				'post_type'              => $this->screen->post_type,
				'post_status'            => array( 'publish', 'draft', 'pending', 'private', 'future' ),
				'posts_per_page'         => -1,
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'meta_query'             => $this->get_section_meta_query(),
			)
		);

		foreach ( (array) $query->posts as $post_id ) {
			$status = get_post_status( $post_id );
			if ( ! $status ) {
				continue;
			}

			if ( ! isset( $this->status_counts[ $status ] ) ) {
				$this->status_counts[ $status ] = 0;
			}

			$this->status_counts[ $status ]++;
		}

		return $this->status_counts;
	}
}
