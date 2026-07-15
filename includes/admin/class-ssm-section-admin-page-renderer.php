<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SSM_Section_Admin_Page_Renderer {
	/**
	 * @var SSM_Section_Admin_Page_Data
	 */
	private $data;

	public function __construct( SSM_Section_Admin_Page_Data $data ) {
		$this->data = $data;
	}

	public function render_admin_page( $current_view, array $sections, $unsectioned ) {
		?>
		<div class="wrap ssm-section-page">
			<h1><?php esc_html_e( 'Site Sections', 'site-section-manager' ); ?></h1>
			<p class="ssm-section-hero"><?php esc_html_e( 'Create and manage section containers for your pages, posts, categories, and tags.', 'site-section-manager' ); ?></p>

			<?php $this->render_notices(); ?>

			<?php if ( $current_view ) : ?>
				<?php $this->render_section_dashboard( $current_view ); ?>
			<?php else : ?>
				<?php $this->render_unsectioned_overview( $unsectioned ); ?>
				<?php $this->render_create_section_form(); ?>
				<?php $this->render_section_index_table( $sections ); ?>
			<?php endif; ?>
		</div>
		<?php
	}

	public function render_notices() {
		if ( ! isset( $_GET['ssm_notice'] ) ) {
			return;
		}

		$notice  = sanitize_key( wp_unslash( $_GET['ssm_notice'] ) );
		$message = '';
		$class   = 'notice-info';

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

	public function render_create_section_form() {
		?>
		<div class="ssm-section-card">
			<div class="ssm-section-card__header">
				<h2><?php esc_html_e( 'Create Section', 'site-section-manager' ); ?></h2>
			</div>
			<div class="ssm-section-card__body">
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ssm-section-form-grid">
					<input type="hidden" name="action" value="ssm_create_section" />
					<?php wp_nonce_field( 'ssm_create_section' ); ?>
					<div class="ssm-section-field">
						<label for="ssm_section_title"><?php esc_html_e( 'Section title', 'site-section-manager' ); ?></label>
						<input type="text" class="regular-text" id="ssm_section_title" name="section_title" required />
					</div>
					<div class="ssm-section-field">
						<label for="ssm_section_content"><?php esc_html_e( 'Description', 'site-section-manager' ); ?></label>
						<textarea class="large-text" rows="6" id="ssm_section_content" name="section_content"></textarea>
					</div>
					<div class="ssm-section-actions">
						<?php submit_button( __( 'Create Section', 'site-section-manager' ), 'primary', 'submit', false ); ?>
					</div>
				</form>
			</div>
		</div>
		<?php
	}

	public function render_unsectioned_overview( $unsectioned ) {
		?>
		<div class="ssm-section-dashboard">
			<?php $this->render_dashboard_card( $unsectioned->post_title, (int) $unsectioned->posts_count + (int) $unsectioned->pages_count, $unsectioned->link ); ?>
		</div>
		<?php
	}

	public function render_section_index_table( array $sections ) {
		?>
		<h2><?php esc_html_e( 'Existing Sections', 'site-section-manager' ); ?></h2>
		<table class="widefat fixed striped ssm-section-table">
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
						<?php $delete_link = $this->data->get_delete_link( $section->ID ); ?>
						<tr>
							<td><strong><?php echo esc_html( get_the_title( $section ) ); ?></strong></td>
							<td><?php echo esc_html( get_the_date( '', $section ) ); ?></td>
							<td>
								<a href="<?php echo esc_url( $this->data->get_section_admin_url( $section->ID ) ); ?>"><?php esc_html_e( 'View', 'site-section-manager' ); ?></a>
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
		<?php
	}

	public function render_section_dashboard( $section ) {
		$section_id       = (int) $section->ID;
		$view_type        = isset( $section->view_type ) ? $section->view_type : 'section';
		$posts_count      = 'unsectioned' === $view_type ? $this->data->get_unsectioned_count( 'post' ) : $this->data->get_section_count( 'post', $section_id );
		$pages_count      = 'unsectioned' === $view_type ? $this->data->get_unsectioned_count( 'page' ) : $this->data->get_section_count( 'page', $section_id );
		$categories_count = 'unsectioned' === $view_type ? 0 : $this->data->get_section_term_count( 'category', $section_id );
		$tags_count       = 'unsectioned' === $view_type ? 0 : $this->data->get_section_term_count( 'post_tag', $section_id );
		$section_title    = 'unsectioned' === $view_type ? __( 'Unsectioned', 'site-section-manager' ) : get_the_title( $section );
		$section_slug_arg = 'unsectioned' === $view_type ? 0 : $section_id;
		?>
		<div class="ssm-section-card">
			<div class="ssm-section-card__header">
				<h2><?php echo esc_html( $section_title ); ?></h2>
			</div>
			<div class="ssm-section-card__body">
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ssm-section-form-grid">
					<?php if ( 'section' === $view_type ) : ?>
						<input type="hidden" name="action" value="ssm_update_section" />
						<input type="hidden" name="section_id" value="<?php echo esc_attr( $section_id ); ?>" />
						<?php wp_nonce_field( 'ssm_update_section_' . $section_id ); ?>
					<?php endif; ?>
					<div class="ssm-section-field">
						<label for="ssm_section_title_<?php echo esc_attr( $section_slug_arg ); ?>"><?php esc_html_e( 'Section title', 'site-section-manager' ); ?></label>
						<input type="text" class="regular-text" id="ssm_section_title_<?php echo esc_attr( $section_slug_arg ); ?>" name="section_title" value="<?php echo esc_attr( $section_title ); ?>" <?php disabled( 'unsectioned' === $view_type ); ?> />
					</div>
					<div class="ssm-section-field">
						<label for="ssm_section_content_<?php echo esc_attr( $section_slug_arg ); ?>"><?php esc_html_e( 'Description', 'site-section-manager' ); ?></label>
						<textarea class="large-text" rows="5" id="ssm_section_content_<?php echo esc_attr( $section_slug_arg ); ?>" name="section_content" <?php disabled( 'unsectioned' === $view_type ); ?>><?php echo esc_textarea( 'unsectioned' === $view_type ? __( 'Items that are not linked to a section.', 'site-section-manager' ) : $section->post_content ); ?></textarea>
					</div>
					<?php if ( 'section' === $view_type ) : ?>
						<div class="ssm-section-actions">
							<?php submit_button( __( 'Save Section', 'site-section-manager' ), 'primary', 'submit', false ); ?>
							<a class="button ssm-section-backlink" href="<?php echo esc_url( admin_url( 'admin.php?page=ssm-site-sections' ) ); ?>"><?php esc_html_e( 'Back to Sections', 'site-section-manager' ); ?></a>
						</div>
					<?php endif; ?>
				</form>
			</div>
		</div>

		<div class="ssm-section-dashboard">
				<?php $this->render_dashboard_card( __( 'Posts', 'site-section-manager' ), $posts_count, 'unsectioned' === $view_type ? admin_url( 'edit.php?post_type=post&ssm_section_id=0' ) : admin_url( 'edit.php?post_type=post&ssm_section_id=' . $section_id ) ); ?>
				<?php $this->render_dashboard_card( __( 'Pages', 'site-section-manager' ), $pages_count, 'unsectioned' === $view_type ? admin_url( 'edit.php?post_type=page&ssm_section_id=0' ) : admin_url( 'edit.php?post_type=page&ssm_section_id=' . $section_id ) ); ?>
				<?php $this->render_dashboard_card( __( 'Categories', 'site-section-manager' ), $categories_count, admin_url( 'edit-tags.php?taxonomy=category&ssm_section_id=' . $section_id ) ); ?>
				<?php $this->render_dashboard_card( __( 'Tags', 'site-section-manager' ), $tags_count, admin_url( 'edit-tags.php?taxonomy=post_tag&ssm_section_id=' . $section_id ) ); ?>
		</div>

		<?php if ( 'section' === $view_type ) : ?>
			<div class="ssm-section-actions">
				<a class="button button-primary" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=post&ssm_section_id=' . $section_id ) ); ?>"><?php esc_html_e( 'Add Post', 'site-section-manager' ); ?></a>
				<a class="button button-primary" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=page&ssm_section_id=' . $section_id ) ); ?>"><?php esc_html_e( 'Add Page', 'site-section-manager' ); ?></a>
				<a class="button button-primary" href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=category&ssm_section_id=' . $section_id ) ); ?>"><?php esc_html_e( 'Add Category', 'site-section-manager' ); ?></a>
				<a class="button button-primary" href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=post_tag&ssm_section_id=' . $section_id ) ); ?>"><?php esc_html_e( 'Add Tag', 'site-section-manager' ); ?></a>
				<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=ssm-site-sections' ) ); ?>"><?php esc_html_e( 'Back to Sections', 'site-section-manager' ); ?></a>
			</div>
		<?php else : ?>
			<div class="ssm-section-actions">
				<a class="button button-primary" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=post' ) ); ?>"><?php esc_html_e( 'Add Post', 'site-section-manager' ); ?></a>
				<a class="button button-primary" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=page' ) ); ?>"><?php esc_html_e( 'Add Page', 'site-section-manager' ); ?></a>
				<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=ssm-site-sections' ) ); ?>"><?php esc_html_e( 'Back to Sections', 'site-section-manager' ); ?></a>
			</div>
		<?php endif; ?>
		<?php
	}

	private function render_dashboard_card( $label, $count, $link ) {
		?>
		<div class="postbox ssm-section-metrics">
			<div class="inside">
				<p style="margin: 0 0 8px;"><strong><?php echo esc_html( $label ); ?></strong></p>
				<p style="font-size: 28px; line-height: 1; margin: 0 0 12px;"><?php echo esc_html( (string) $count ); ?></p>
				<a href="<?php echo esc_url( $link ); ?>"><?php esc_html_e( 'Open', 'site-section-manager' ); ?></a>
			</div>
		</div>
		<?php
	}
}
