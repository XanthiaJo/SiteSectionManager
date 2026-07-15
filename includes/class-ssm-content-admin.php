<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SSM_Content_Admin {
	/**
	 * @var SSM_Content
	 */
	private $content;

	public function __construct( SSM_Content $content ) {
		$this->content = $content;
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
				array( $this, 'render_section_meta_box' ),
				$post_type,
				'side',
				'default'
			);
		}
	}

	public function register_term_form_fields() {
		add_action( 'category_add_form_fields', array( $this, 'render_term_section_add_field' ) );
		add_action( 'category_edit_form_fields', array( $this, 'render_term_section_edit_field' ), 10, 2 );
		add_action( 'post_tag_add_form_fields', array( $this, 'render_term_section_add_field' ) );
		add_action( 'post_tag_edit_form_fields', array( $this, 'render_term_section_edit_field' ), 10, 2 );
		add_filter( 'manage_edit-category_columns', array( $this, 'add_term_section_column' ) );
		add_filter( 'manage_category_custom_column', array( $this, 'render_term_section_column' ), 10, 3 );
		add_filter( 'manage_edit-post_tag_columns', array( $this, 'add_term_section_column' ) );
		add_filter( 'manage_post_tag_custom_column', array( $this, 'render_term_section_column' ), 10, 3 );
	}

	public function render_section_meta_box( $post ) {
		wp_nonce_field( 'ssm_save_section', 'ssm_section_nonce' );

		$current_section_id = (int) get_post_meta( $post->ID, '_ssm_section_id', true );
		if ( ! $current_section_id && isset( $_GET['ssm_section_id'] ) ) {
			$current_section_id = absint( $_GET['ssm_section_id'] );
		}
		$sections = $this->content->get_sections();

		echo '<p>' . esc_html__( 'Assign this item to a site section.', 'site-section-manager' ) . '</p>';
		echo '<select name="ssm_section_id" style="width:100%">';
		echo '<option value="0">' . esc_html__( 'No section', 'site-section-manager' ) . '</option>';

		foreach ( $sections as $section ) {
			printf(
				'<option value="%d"%s>%s</option>',
				(int) $section->ID,
				selected( $current_section_id, $section->ID, false ),
				esc_html( get_the_title( $section ) )
			);
		}

		echo '</select>';
		echo '<p class="description">' . esc_html__( 'This is the primary organizing key for section-aware content.', 'site-section-manager' ) . '</p>';
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

		if ( $section_id > 0 ) {
			update_post_meta( $post_id, '_ssm_section_id', $section_id );
			return;
		}

		delete_post_meta( $post_id, '_ssm_section_id' );
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

		$section_id = absint( wp_unslash( $_POST['ssm_term_section_id'] ) );
		update_term_meta( $term_id, 'ssm_section_id', $section_id );
	}

	public function render_term_section_add_field() {
		$sections            = $this->content->get_sections();
		$selected_section_id = isset( $_GET['ssm_section_id'] ) ? absint( $_GET['ssm_section_id'] ) : 0;
		?>
		<?php wp_nonce_field( 'ssm_save_term_section', 'ssm_term_section_nonce' ); ?>
		<div class="form-field term-ssm-section-wrap">
			<label for="ssm_term_section_id"><?php esc_html_e( 'Site Section', 'site-section-manager' ); ?></label>
			<select name="ssm_term_section_id" id="ssm_term_section_id">
				<option value="0"<?php selected( $selected_section_id, 0 ); ?>><?php esc_html_e( 'No section', 'site-section-manager' ); ?></option>
				<?php foreach ( $sections as $section ) : ?>
					<option value="<?php echo esc_attr( $section->ID ); ?>" <?php selected( $selected_section_id, $section->ID ); ?>>
						<?php echo esc_html( get_the_title( $section ) ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<p class="description"><?php esc_html_e( 'Limit this category or tag to a site section.', 'site-section-manager' ); ?></p>
		</div>
		<?php
	}

	public function render_term_section_edit_field( $term ) {
		$sections      = $this->content->get_sections();
		$current_value = (int) get_term_meta( $term->term_id, 'ssm_section_id', true );
		?>
		<?php wp_nonce_field( 'ssm_save_term_section', 'ssm_term_section_nonce' ); ?>
		<tr class="form-field term-ssm-section-wrap">
			<th scope="row">
				<label for="ssm_term_section_id"><?php esc_html_e( 'Site Section', 'site-section-manager' ); ?></label>
			</th>
			<td>
				<select name="ssm_term_section_id" id="ssm_term_section_id">
					<option value="0"><?php esc_html_e( 'No section', 'site-section-manager' ); ?></option>
					<?php foreach ( $sections as $section ) : ?>
						<option value="<?php echo esc_attr( $section->ID ); ?>" <?php selected( $current_value, $section->ID ); ?>>
							<?php echo esc_html( get_the_title( $section ) ); ?>
						</option>
					<?php endforeach; ?>
				</select>
				<p class="description"><?php esc_html_e( 'Limit this category or tag to a site section.', 'site-section-manager' ); ?></p>
			</td>
		</tr>
		<?php
	}

	public function add_section_column( $columns ) {
		$columns['ssm_section'] = __( 'Site Section', 'site-section-manager' );
		return $columns;
	}

	public function render_section_column( $column, $post_id ) {
		if ( 'ssm_section' !== $column ) {
			return;
		}

		$section_id = (int) get_post_meta( $post_id, '_ssm_section_id', true );
		if ( ! $section_id ) {
			echo '&mdash;';
			return;
		}

		echo esc_html( get_the_title( $section_id ) );
	}

	public function render_admin_post_filters() {
		global $typenow;
		global $pagenow;

		if ( 'edit.php' !== $pagenow || ! in_array( $typenow, array( 'post', 'page' ), true ) ) {
			return;
		}

		$selected = isset( $_GET['ssm_section_id'] ) ? absint( $_GET['ssm_section_id'] ) : 0;
		$sections = $this->content->get_sections();

		echo '<select name="ssm_section_id">';
		echo '<option value="0">' . esc_html__( 'All Site Sections', 'site-section-manager' ) . '</option>';

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

		$selected = isset( $_GET['ssm_section_id'] ) ? absint( $_GET['ssm_section_id'] ) : 0;
		$sections = $this->content->get_sections();

		echo '<select name="ssm_section_id">';
		echo '<option value="0">' . esc_html__( 'All Site Sections', 'site-section-manager' ) . '</option>';

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

	public function filter_admin_post_queries( $query ) {
		global $pagenow;

		if ( ! is_admin() || 'edit.php' !== $pagenow || ! is_a( $query, 'WP_Query' ) || ! $query->is_main_query() ) {
			return;
		}

		$post_type = $query->get( 'post_type' );
		if ( ! in_array( $post_type, array( 'post', 'page' ), true ) ) {
			return;
		}

		if ( empty( $_GET['ssm_section_id'] ) ) {
			return;
		}

		$section_ids = $this->get_section_post_ids( $post_type, absint( $_GET['ssm_section_id'] ) );
		if ( empty( $section_ids ) ) {
			$section_ids = array( 0 );
		}

		$query->set( 'post__in', $section_ids );
		$query->set( 'orderby', 'post__in' );
	}

	public function filter_admin_term_queries( $query ) {
		if ( ! is_admin() || ! is_a( $query, 'WP_Term_Query' ) ) {
			return;
		}

		$taxonomy = isset( $query->query_vars['taxonomy'] ) ? $query->query_vars['taxonomy'] : '';
		if ( ! in_array( $taxonomy, array( 'category', 'post_tag' ), true ) ) {
			return;
		}

		if ( empty( $_GET['ssm_section_id'] ) ) {
			return;
		}

		$query->set(
			'meta_query',
			array(
				array(
					'key'   => 'ssm_section_id',
					'value' => absint( $_GET['ssm_section_id'] ),
				),
			)
		);
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

	public function add_term_section_column( $columns ) {
		$columns['ssm_section'] = __( 'Site Section', 'site-section-manager' );
		return $columns;
	}

	public function render_term_section_column( $output, $column_name, $term_id ) {
		if ( 'ssm_section' !== $column_name ) {
			return $output;
		}

		$section_id = (int) get_term_meta( $term_id, 'ssm_section_id', true );
		if ( ! $section_id ) {
			return '&mdash;';
		}

		return esc_html( get_the_title( $section_id ) );
	}
}
