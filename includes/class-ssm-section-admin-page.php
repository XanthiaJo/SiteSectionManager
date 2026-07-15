<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SSM_Section_Admin_Page {
	/**
	 * @var SSM_Content
	 */
	private $content;

	/**
	 * @var array<string,int>
	 */
	private $section_menu_map = array();

	public function __construct( SSM_Content $content ) {
		$this->content = $content;
	}

	public function register_admin_menu() {
		add_menu_page(
			__( 'Site Sections', 'site-section-manager' ),
			__( 'Site Sections', 'site-section-manager' ),
			'manage_options',
			'ssm-site-sections',
			array( $this, 'render_admin_page' ),
			'dashicons-screenoptions',
			25
		);

		add_submenu_page(
			'ssm-site-sections',
			__( 'Site Sections', 'site-section-manager' ),
			__( 'Sections', 'site-section-manager' ),
			'manage_options',
			'ssm-site-sections',
			array( $this, 'render_admin_page' )
		);

		$sections = $this->content->get_sections();
		foreach ( $sections as $section ) {
			$slug = $this->get_section_menu_slug( $section->ID );
			$this->section_menu_map[ $slug ] = (int) $section->ID;

			add_submenu_page(
				'ssm-site-sections',
				get_the_title( $section ),
				get_the_title( $section ),
				'manage_options',
				$slug,
				array( $this, 'render_admin_page' )
			);
		}
	}

	public function render_admin_page() {
		$current_section = $this->get_current_section();
		$sections        = $this->content->get_sections();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Site Sections', 'site-section-manager' ); ?></h1>
			<p><?php esc_html_e( 'Create and manage section containers for your pages, posts, categories, and tags.', 'site-section-manager' ); ?></p>

			<?php $this->render_notices(); ?>

			<?php if ( $current_section ) : ?>
				<?php $this->render_section_dashboard( $current_section ); ?>
			<?php else : ?>
				<?php $this->render_create_section_form(); ?>
				<h2><?php esc_html_e( 'Existing Sections', 'site-section-manager' ); ?></h2>
				<table class="widefat fixed striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Title', 'site-section-manager' ); ?></th>
							<th><?php esc_html_e( 'Created', 'site-section-manager' ); ?></th>
							<th><?php esc_html_e( 'Actions', 'site-section-manager' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( ! empty( $sections ) ) : ?>
							<?php foreach ( $sections as $section ) : ?>
								<?php $delete_link = $this->get_delete_link( $section->ID ); ?>
								<tr>
									<td><strong><?php echo esc_html( get_the_title( $section ) ); ?></strong></td>
									<td><?php echo esc_html( get_the_date( '', $section ) ); ?></td>
									<td>
										<a href="<?php echo esc_url( $this->get_section_admin_url( $section->ID ) ); ?>"><?php esc_html_e( 'View', 'site-section-manager' ); ?></a>
										|
										<a href="<?php echo esc_url( $delete_link ); ?>" onclick="return confirm('<?php echo esc_js( __( 'Delete this section?', 'site-section-manager' ) ); ?>');"><?php esc_html_e( 'Delete', 'site-section-manager' ); ?></a>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php else : ?>
							<tr>
								<td colspan="3"><?php esc_html_e( 'No site sections yet.', 'site-section-manager' ); ?></td>
							</tr>
						<?php endif; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}

	public function handle_create_section() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do that.', 'site-section-manager' ) );
		}

		check_admin_referer( 'ssm_create_section' );

		$title   = isset( $_POST['section_title'] ) ? sanitize_text_field( wp_unslash( $_POST['section_title'] ) ) : '';
		$content = isset( $_POST['section_content'] ) ? wp_kses_post( wp_unslash( $_POST['section_content'] ) ) : '';

		if ( '' === $title ) {
			wp_safe_redirect( add_query_arg( 'ssm_notice', 'missing-title', admin_url( 'admin.php?page=ssm-site-sections' ) ) );
			exit;
		}

		$section_id = wp_insert_post(
			array(
				'post_type'    => 'site_section',
				'post_status'  => 'publish',
				'post_title'   => $title,
				'post_content' => $content,
			),
			true
		);

		if ( is_wp_error( $section_id ) ) {
			wp_safe_redirect( add_query_arg( 'ssm_notice', 'error', admin_url( 'admin.php?page=ssm-site-sections' ) ) );
			exit;
		}

		wp_safe_redirect( $this->get_section_admin_url( absint( $section_id ), array( 'ssm_notice' => 'created' ) ) );
		exit;
	}

	public function handle_delete_section() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do that.', 'site-section-manager' ) );
		}

		$section_id = isset( $_GET['section_id'] ) ? absint( $_GET['section_id'] ) : 0;
		if ( ! $section_id ) {
			wp_safe_redirect( admin_url( 'admin.php?page=ssm-site-sections' ) );
			exit;
		}

		check_admin_referer( 'ssm_delete_section_' . $section_id );
		wp_delete_post( $section_id, true );

		wp_safe_redirect( add_query_arg( 'ssm_notice', 'deleted', admin_url( 'admin.php?page=ssm-site-sections' ) ) );
		exit;
	}

	public function handle_update_section() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do that.', 'site-section-manager' ) );
		}

		$section_id = isset( $_POST['section_id'] ) ? absint( $_POST['section_id'] ) : 0;
		if ( ! $section_id ) {
			wp_safe_redirect( admin_url( 'admin.php?page=ssm-site-sections' ) );
			exit;
		}

		check_admin_referer( 'ssm_update_section_' . $section_id );

		$title   = isset( $_POST['section_title'] ) ? sanitize_text_field( wp_unslash( $_POST['section_title'] ) ) : '';
		$content = isset( $_POST['section_content'] ) ? wp_kses_post( wp_unslash( $_POST['section_content'] ) ) : '';

		wp_update_post(
			array(
				'ID'           => $section_id,
				'post_title'   => $title,
				'post_content' => $content,
			)
		);

		wp_safe_redirect( $this->get_section_admin_url( $section_id, array( 'ssm_notice' => 'updated' ) ) );
		exit;
	}

	private function get_current_section() {
		$section_id = isset( $_GET['section_id'] ) ? absint( $_GET['section_id'] ) : 0;
		if ( ! $section_id ) {
			$page_slug = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
			if ( isset( $this->section_menu_map[ $page_slug ] ) ) {
				$section_id = (int) $this->section_menu_map[ $page_slug ];
			}
		}

		return $section_id ? get_post( $section_id ) : null;
	}

	private function render_notices() {
		if ( ! isset( $_GET['ssm_notice'] ) ) {
			return;
		}

		$notice = sanitize_key( wp_unslash( $_GET['ssm_notice'] ) );
		$message = '';
		$class    = 'notice-info';

		switch ( $notice ) {
			case 'created':
				$message = __( 'Site section created.', 'site-section-manager' );
				$class   = 'notice-success';
				break;
			case 'updated':
				$message = __( 'Site section updated.', 'site-section-manager' );
				$class   = 'notice-success';
				break;
			case 'deleted':
				$message = __( 'Site section deleted.', 'site-section-manager' );
				$class   = 'notice-success';
				break;
			case 'missing-title':
				$message = __( 'Please enter a section title.', 'site-section-manager' );
				$class   = 'notice-error';
				break;
			case 'error':
				$message = __( 'Could not create the section.', 'site-section-manager' );
				$class   = 'notice-error';
				break;
		}

		if ( '' === $message ) {
			return;
		}
		?>
		<div class="notice <?php echo esc_attr( $class ); ?> is-dismissible">
			<p><?php echo esc_html( $message ); ?></p>
		</div>
		<?php
	}

	private function render_create_section_form() {
		?>
		<div class="postbox" style="max-width: 900px; margin-bottom: 24px;">
			<h2 class="hndle"><span><?php esc_html_e( 'Create Section', 'site-section-manager' ); ?></span></h2>
			<div class="inside">
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="ssm_create_section" />
					<?php wp_nonce_field( 'ssm_create_section' ); ?>
					<p>
						<label for="ssm_section_title"><strong><?php esc_html_e( 'Section title', 'site-section-manager' ); ?></strong></label>
						<input type="text" class="regular-text" id="ssm_section_title" name="section_title" required />
					</p>
					<p>
						<label for="ssm_section_content"><strong><?php esc_html_e( 'Description', 'site-section-manager' ); ?></strong></label>
						<textarea class="large-text" rows="6" id="ssm_section_content" name="section_content"></textarea>
					</p>
					<?php submit_button( __( 'Create Section', 'site-section-manager' ) ); ?>
				</form>
			</div>
		</div>
		<?php
	}

	private function render_section_dashboard( $section ) {
		$section_id        = (int) $section->ID;
		$posts_count       = $this->get_section_count( 'post', $section_id );
		$pages_count       = $this->get_section_count( 'page', $section_id );
		$categories_count  = $this->get_section_term_count( 'category', $section_id );
		$tags_count        = $this->get_section_term_count( 'post_tag', $section_id );
		?>
		<div class="postbox" style="margin-bottom: 20px;">
			<div class="inside">
				<h2 style="margin-top: 0;"><?php echo esc_html( get_the_title( $section ) ); ?></h2>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="ssm_update_section" />
					<input type="hidden" name="section_id" value="<?php echo esc_attr( $section_id ); ?>" />
					<?php wp_nonce_field( 'ssm_update_section_' . $section_id ); ?>
					<p>
						<label for="ssm_section_title_<?php echo esc_attr( $section_id ); ?>"><strong><?php esc_html_e( 'Section title', 'site-section-manager' ); ?></strong></label>
						<input type="text" class="regular-text" id="ssm_section_title_<?php echo esc_attr( $section_id ); ?>" name="section_title" value="<?php echo esc_attr( get_the_title( $section ) ); ?>" />
					</p>
					<p>
						<label for="ssm_section_content_<?php echo esc_attr( $section_id ); ?>"><strong><?php esc_html_e( 'Description', 'site-section-manager' ); ?></strong></label>
						<textarea class="large-text" rows="5" id="ssm_section_content_<?php echo esc_attr( $section_id ); ?>" name="section_content"><?php echo esc_textarea( $section->post_content ); ?></textarea>
					</p>
					<?php submit_button( __( 'Save Section', 'site-section-manager' ) ); ?>
				</form>
			</div>
		</div>

		<div class="postbox-container" style="max-width: 1200px;">
			<div style="display:grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 16px; margin-bottom: 24px;">
				<?php $this->render_dashboard_card( __( 'Posts', 'site-section-manager' ), $posts_count, admin_url( 'edit.php?post_type=post&ssm_section_id=' . $section_id ) ); ?>
				<?php $this->render_dashboard_card( __( 'Pages', 'site-section-manager' ), $pages_count, admin_url( 'edit.php?post_type=page&ssm_section_id=' . $section_id ) ); ?>
				<?php $this->render_dashboard_card( __( 'Categories', 'site-section-manager' ), $categories_count, admin_url( 'edit-tags.php?taxonomy=category&ssm_section_id=' . $section_id ) ); ?>
				<?php $this->render_dashboard_card( __( 'Tags', 'site-section-manager' ), $tags_count, admin_url( 'edit-tags.php?taxonomy=post_tag&ssm_section_id=' . $section_id ) ); ?>
			</div>

			<p>
				<a class="button button-primary" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=post&ssm_section_id=' . $section_id ) ); ?>"><?php esc_html_e( 'Add Post', 'site-section-manager' ); ?></a>
				<a class="button button-primary" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=page&ssm_section_id=' . $section_id ) ); ?>"><?php esc_html_e( 'Add Page', 'site-section-manager' ); ?></a>
				<a class="button button-primary" href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=category&ssm_section_id=' . $section_id ) ); ?>"><?php esc_html_e( 'Add Category', 'site-section-manager' ); ?></a>
				<a class="button button-primary" href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=post_tag&ssm_section_id=' . $section_id ) ); ?>"><?php esc_html_e( 'Add Tag', 'site-section-manager' ); ?></a>
				<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=ssm-site-sections' ) ); ?>"><?php esc_html_e( 'Back to Sections', 'site-section-manager' ); ?></a>
			</p>
		</div>
		<?php
	}

	private function render_dashboard_card( $label, $count, $link ) {
		?>
		<div class="postbox" style="margin: 0;">
			<div class="inside">
				<p style="margin: 0 0 8px;"><strong><?php echo esc_html( $label ); ?></strong></p>
				<p style="font-size: 28px; line-height: 1; margin: 0 0 12px;"><?php echo esc_html( (string) $count ); ?></p>
				<a href="<?php echo esc_url( $link ); ?>"><?php esc_html_e( 'Open', 'site-section-manager' ); ?></a>
			</div>
		</div>
		<?php
	}

	private function get_section_count( $post_type, $section_id ) {
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

	private function get_section_term_count( $taxonomy, $section_id ) {
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

	private function get_delete_link( $section_id ) {
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

	private function get_section_menu_slug( $section_id ) {
		return 'ssm-site-section-' . absint( $section_id );
	}

	private function get_section_admin_url( $section_id, array $args = array() ) {
		$slug = $this->get_section_menu_slug( $section_id );
		$url  = admin_url( 'admin.php?page=' . $slug );

		if ( ! empty( $args ) ) {
			$url = add_query_arg( $args, $url );
		}

		return $url;
	}
}
