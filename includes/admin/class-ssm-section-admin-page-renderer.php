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
		$section_summaries = $this->data->get_section_summaries( $sections );
		?>
		<div class="wrap ssm-section-page">
			<h1><?php esc_html_e( 'Site Sections', 'site-section-manager' ); ?></h1>
			<p class="ssm-section-hero"><?php esc_html_e( 'Create and manage section containers for your pages, posts, categories, and tags.', 'site-section-manager' ); ?></p>

			<?php $this->render_notices(); ?>

			<?php if ( $current_view ) : ?>
				<?php $this->render_section_dashboard( $current_view ); ?>
			<?php else : ?>
				<?php do_action( 'ssm_render_section_global_panels', $current_view ); ?>
				<?php $this->render_section_index_grid( $unsectioned, $section_summaries ); ?>
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
			case 'created-home-error':
				$message = __( 'Site section created, but the Home page could not be created automatically.', 'site-section-manager' );
				$class   = 'notice-warning';
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
			case 'menu-updated':
				$message = __( 'Menu settings updated.', 'site-section-manager' );
				$class   = 'notice-success';
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
	}

	public function render_section_index_grid( $home_summary, array $section_summaries ) {
		?>
		<div class="ssm-section-grid">
			<?php $this->render_section_summary_card( $home_summary ); ?>
			<?php foreach ( $section_summaries as $summary ) : ?>
				<?php $this->render_section_summary_card( $summary ); ?>
			<?php endforeach; ?>
			<?php $this->render_create_section_grid_card(); ?>
		</div>
		<?php
	}

	public function render_section_dashboard( $section ) {
		$section_id       = (int) $section->ID;
		$view_type        = isset( $section->view_type ) ? $section->view_type : 'section';
		$section_title    = 'unsectioned' === $view_type ? __( 'Home', 'site-section-manager' ) : get_the_title( $section );
		$section_slug_arg = 'unsectioned' === $view_type ? 0 : $section_id;
		$is_home          = 'unsectioned' === $view_type;
		$summary          = $is_home ? $this->data->get_unsectioned_summary() : $this->data->get_section_summary( $section );
		$menu_link        = $summary->menu_link;
		$menu_id          = $summary->menu_id;
		$menu_items       = $this->data->get_menu_items( $menu_id );
		?>
		<div class="ssm-section-workspace">
			<div class="ssm-section-workspace__top">
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
								<input type="text" class="regular-text" id="ssm_section_title_<?php echo esc_attr( $section_slug_arg ); ?>" name="section_title" value="<?php echo esc_attr( $section_title ); ?>" <?php disabled( $is_home ); ?> />
							</div>
							<div class="ssm-section-field">
								<label for="ssm_section_content_<?php echo esc_attr( $section_slug_arg ); ?>"><?php esc_html_e( 'Description', 'site-section-manager' ); ?></label>
								<textarea class="large-text" rows="5" id="ssm_section_content_<?php echo esc_attr( $section_slug_arg ); ?>" name="section_content" <?php disabled( $is_home ); ?>><?php echo esc_textarea( $is_home ? __( 'Default content that belongs to the main Home section.', 'site-section-manager' ) : $section->post_content ); ?></textarea>
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
				<div class="ssm-section-card">
					<div class="ssm-section-card__header">
						<h2><?php esc_html_e( 'Menu', 'site-section-manager' ); ?></h2>
					</div>
					<div class="ssm-section-card__body">
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ssm-section-form-grid">
							<input type="hidden" name="action" value="ssm_save_section_menu" />
							<input type="hidden" name="section_id" value="<?php echo esc_attr( $section_id ); ?>" />
							<input type="hidden" name="is_home" value="<?php echo esc_attr( $is_home ? '1' : '0' ); ?>" />
							<?php wp_nonce_field( 'ssm_save_section_menu' ); ?>
							<div class="ssm-section-menu-editor">
								<?php foreach ( $menu_items as $index => $item ) : ?>
									<div class="ssm-section-menu-row">
										<input type="hidden" name="menu_item_id[<?php echo esc_attr( $index ); ?>]" value="<?php echo esc_attr( $item->ID ); ?>" />
										<input type="text" name="menu_item_label[<?php echo esc_attr( $index ); ?>]" value="<?php echo esc_attr( $item->title ); ?>" placeholder="<?php esc_attr_e( 'Label', 'site-section-manager' ); ?>" />
										<input type="url" name="menu_item_url[<?php echo esc_attr( $index ); ?>]" value="<?php echo esc_url( $item->url ); ?>" placeholder="<?php esc_attr_e( 'URL', 'site-section-manager' ); ?>" />
										<label class="ssm-section-menu-remove"><input type="checkbox" name="menu_item_delete[<?php echo esc_attr( $index ); ?>]" value="1" /> <?php esc_html_e( 'Remove', 'site-section-manager' ); ?></label>
									</div>
								<?php endforeach; ?>
								<?php $new_index = count( $menu_items ); ?>
								<div class="ssm-section-menu-row">
									<input type="hidden" name="menu_item_id[<?php echo esc_attr( $new_index ); ?>]" value="0" />
									<input type="text" name="menu_item_label[<?php echo esc_attr( $new_index ); ?>]" value="" placeholder="<?php esc_attr_e( 'New label', 'site-section-manager' ); ?>" />
									<input type="url" name="menu_item_url[<?php echo esc_attr( $new_index ); ?>]" value="" placeholder="<?php esc_attr_e( 'https://example.com/', 'site-section-manager' ); ?>" />
									<span class="description"><?php esc_html_e( 'Add one link', 'site-section-manager' ); ?></span>
								</div>
							</div>
							<p class="description"><?php esc_html_e( 'Use this panel for simple link edits. Open the full Menus screen for advanced item types.', 'site-section-manager' ); ?></p>
						<div class="ssm-section-actions">
								<?php submit_button( __( 'Save Menu', 'site-section-manager' ), 'secondary', 'submit', false ); ?>
							<a class="button button-primary" href="<?php echo esc_url( $menu_link ); ?>"><?php esc_html_e( 'Edit Menu', 'site-section-manager' ); ?></a>
						</div>
						</form>
					</div>
				</div>
			</div>
			<?php $this->render_section_content_table( __( 'Pages', 'site-section-manager' ), 'page', $section_id, $is_home ); ?>
			<?php $this->render_section_content_table( __( 'Posts', 'site-section-manager' ), 'post', $section_id, $is_home ); ?>
		</div>
		<?php
	}

	private function render_section_summary_card( $summary ) {
		?>
		<div class="ssm-section-summary-card">
			<div class="ssm-section-summary-card__header">
				<h2><?php echo esc_html( $summary->post_title ); ?></h2>
			</div>
			<div class="ssm-section-summary-card__body">
				<ul class="ssm-section-summary-card__stats">
					<li><strong><?php esc_html_e( 'Posts', 'site-section-manager' ); ?>:</strong> <?php echo esc_html( (string) $summary->posts_count ); ?></li>
					<li><strong><?php esc_html_e( 'Pages', 'site-section-manager' ); ?>:</strong> <?php echo esc_html( (string) $summary->pages_count ); ?></li>
					<li><strong><?php esc_html_e( 'Categories', 'site-section-manager' ); ?>:</strong> <?php echo esc_html( (string) $summary->categories_count ); ?></li>
					<li><strong><?php esc_html_e( 'Tags', 'site-section-manager' ); ?>:</strong> <?php echo esc_html( (string) $summary->tags_count ); ?></li>
				</ul>
				<div class="ssm-section-actions">
					<a class="button button-primary" href="<?php echo esc_url( $summary->link ); ?>"><?php esc_html_e( 'Open Section', 'site-section-manager' ); ?></a>
					<a class="button" href="<?php echo esc_url( $summary->menu_link ); ?>"><?php esc_html_e( 'Edit Menu', 'site-section-manager' ); ?></a>
					<?php if ( 'section' === $summary->view_type ) : ?>
						<a class="button button-link-delete" href="<?php echo esc_url( $summary->delete_link ); ?>" onclick="return confirm('<?php echo esc_js( __( 'Delete this section?', 'site-section-manager' ) ); ?>');"><?php esc_html_e( 'Delete', 'site-section-manager' ); ?></a>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<?php
	}

	private function render_section_content_table( $label, $post_type, $section_id, $is_home ) {
		$list_link = $is_home
			? admin_url( 'edit.php?post_type=' . $post_type . '&ssm_section_id=0' )
			: admin_url( 'edit.php?post_type=' . $post_type . '&ssm_section_id=' . $section_id );
		$create_link = $is_home
			? admin_url( 'post-new.php?post_type=' . $post_type )
			: admin_url( 'post-new.php?post_type=' . $post_type . '&ssm_section_id=' . $section_id );
		$table = new SSM_Section_Posts_List_Table( $post_type, $section_id, $is_home );
		$table->prepare_items();
		?>
		<div class="ssm-section-card">
			<div class="ssm-section-card__header ssm-section-card__header--table">
				<h2><?php echo esc_html( $label ); ?></h2>
				<div class="ssm-section-actions">
					<a class="button button-primary" href="<?php echo esc_url( $create_link ); ?>"><?php echo esc_html( 'page' === $post_type ? __( 'Add Page', 'site-section-manager' ) : __( 'Add Post', 'site-section-manager' ) ); ?></a>
					<a class="button" href="<?php echo esc_url( $list_link ); ?>"><?php esc_html_e( 'Open Full List', 'site-section-manager' ); ?></a>
				</div>
			</div>
			<div class="ssm-section-card__body">
				<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
					<input type="hidden" name="page" value="<?php echo esc_attr( $is_home ? 'ssm-site-sections' : 'ssm-site-section-' . $section_id ); ?>" />
					<input type="hidden" name="section_id" value="<?php echo esc_attr( $is_home ? 0 : $section_id ); ?>" />
					<input type="hidden" name="post_type" value="<?php echo esc_attr( $post_type ); ?>" />
					<input type="hidden" name="<?php echo esc_attr( 'ssm_' . $post_type . '_paged' ); ?>" value="<?php echo esc_attr( $table->get_pagenum() ); ?>" />
					<?php $table->display(); ?>
				</form>
			</div>
		</div>
		<?php
	}

	private function render_create_section_grid_card() {
		?>
		<div class="ssm-section-summary-card ssm-section-summary-card--create">
			<div class="ssm-section-summary-card__header">
				<h2><?php esc_html_e( 'Create Section', 'site-section-manager' ); ?></h2>
			</div>
			<div class="ssm-section-summary-card__body">
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ssm-section-form-grid">
					<input type="hidden" name="action" value="ssm_create_section" />
					<?php wp_nonce_field( 'ssm_create_section' ); ?>
					<div class="ssm-section-field">
						<label for="ssm_section_grid_title"><?php esc_html_e( 'Section title', 'site-section-manager' ); ?></label>
						<input type="text" class="regular-text" id="ssm_section_grid_title" name="section_title" required />
					</div>
					<div class="ssm-section-field">
						<label for="ssm_section_grid_content"><?php esc_html_e( 'Description', 'site-section-manager' ); ?></label>
						<textarea class="large-text" rows="6" id="ssm_section_grid_content" name="section_content"></textarea>
					</div>
					<div class="ssm-section-actions">
						<?php submit_button( __( 'Create Section', 'site-section-manager' ), 'primary', 'submit', false ); ?>
					</div>
				</form>
			</div>
		</div>
		<?php
	}
}
