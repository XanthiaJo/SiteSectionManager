<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SSM_Content_Admin_Bulk_Edit {
	/**
	 * @var SSM_Content
	 */
	private $content;

	public function __construct( SSM_Content $content ) {
		$this->content = $content;
	}

	public function render_section_field( $column_name, $post_type ) {
		if ( 'ssm_section' !== $column_name || ! in_array( $post_type, array( 'post', 'page' ), true ) ) {
			return;
		}

		$sections = $this->content->get_sections();

		?>
		<fieldset class="inline-edit-col-right">
			<div class="inline-edit-col">
				<label class="alignleft">
					<span class="title"><?php esc_html_e( 'Site Section', 'site-section-manager' ); ?></span>
					<select name="ssm_bulk_edit_section_id">
						<option value="-1"><?php esc_html_e( 'No change', 'site-section-manager' ); ?></option>
						<option value="0"><?php esc_html_e( 'Home', 'site-section-manager' ); ?></option>
						<?php foreach ( $sections as $section ) : ?>
							<option value="<?php echo esc_attr( $section->ID ); ?>">
								<?php echo esc_html( get_the_title( $section ) ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</label>
			</div>
		</fieldset>
		<?php
	}

	public function render_quick_edit_field( $column_name, $post_type ) {
		if ( 'ssm_section' !== $column_name || ! in_array( $post_type, array( 'post', 'page' ), true ) ) {
			return;
		}

		$sections = $this->content->get_sections();

		?>
		<fieldset class="inline-edit-col-right">
			<div class="inline-edit-col">
				<label class="alignleft">
					<span class="title"><?php esc_html_e( 'Site Section', 'site-section-manager' ); ?></span>
					<select name="ssm_quick_edit_section_id" class="ssm-quick-edit-section">
						<option value="0"><?php esc_html_e( 'Home', 'site-section-manager' ); ?></option>
						<?php foreach ( $sections as $section ) : ?>
							<option value="<?php echo esc_attr( $section->ID ); ?>">
								<?php echo esc_html( get_the_title( $section ) ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</label>
			</div>
		</fieldset>
		<?php
	}

	public function render_term_quick_edit_field( $column_name, $screen, $taxonomy ) {
		if ( 'ssm_section' !== $column_name || 'edit-tags' !== $screen || ! in_array( $taxonomy, array( 'category', 'post_tag' ), true ) ) {
			return;
		}

		$sections = $this->content->get_sections();

		?>
		<fieldset>
			<div class="inline-edit-col">
				<label>
					<span class="title"><?php esc_html_e( 'Site Section', 'site-section-manager' ); ?></span>
					<select name="ssm_term_section_id" class="ssm-quick-edit-term-section">
						<option value="0"><?php esc_html_e( 'Home', 'site-section-manager' ); ?></option>
						<?php foreach ( $sections as $section ) : ?>
							<option value="<?php echo esc_attr( $section->ID ); ?>">
								<?php echo esc_html( get_the_title( $section ) ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</label>
				<?php wp_nonce_field( 'ssm_save_term_section', 'ssm_term_section_nonce' ); ?>
			</div>
		</fieldset>
		<?php
	}

	public function output_quick_edit_script() {
		global $current_screen;

		if ( ! isset( $current_screen->base ) || 'edit' !== $current_screen->base || ! in_array( $current_screen->post_type, array( 'post', 'page' ), true ) ) {
			return;
		}

		?>
		<script>
		jQuery(function($) {
			if (typeof inlineEditPost === 'undefined') {
				return;
			}

			var originalEdit = inlineEditPost.edit;
			inlineEditPost.edit = function(id) {
				originalEdit.apply(this, arguments);

				var postId = 0;
				if (typeof id === 'object') {
					postId = parseInt(this.getId(id), 10);
				} else {
					postId = parseInt(id, 10);
				}

				if (!postId) {
					return;
				}

				var $row = $('#post-' + postId);
				var sectionId = $row.find('.ssm-inline-section-value').data('section-id');
				if (typeof sectionId === 'undefined') {
					sectionId = 0;
				}

				$('#edit-' + postId).find('select[name="ssm_quick_edit_section_id"]').val(String(sectionId));
			};
		});
		</script>
		<?php
	}

	public function output_term_quick_edit_script() {
		global $current_screen;

		if ( ! isset( $current_screen->base ) || 'edit-tags' !== $current_screen->base || ! in_array( $current_screen->taxonomy, array( 'category', 'post_tag' ), true ) ) {
			return;
		}

		?>
		<script>
		jQuery(function($) {
			if (typeof inlineEditTax === 'undefined') {
				return;
			}

			var originalEdit = inlineEditTax.edit;
			inlineEditTax.edit = function(id) {
				originalEdit.apply(this, arguments);

				var termId = 0;
				if (typeof id === 'object') {
					termId = parseInt(this.getId(id), 10);
				} else {
					termId = parseInt(id, 10);
				}

				if (!termId) {
					return;
				}

				var $row = $('#tag-' + termId);
				var sectionId = $row.find('.ssm-inline-term-section-value').data('section-id');
				if (typeof sectionId === 'undefined') {
					sectionId = 0;
				}

				$('#edit-' + termId).find('select[name="ssm_term_section_id"]').val(String(sectionId));
			};
		});
		</script>
		<?php
	}

	public function save_sections( array $post_ids, array $shared_post_data ) {
		if ( ! isset( $shared_post_data['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $shared_post_data['_wpnonce'] ) ), 'bulk-posts' ) ) {
			return;
		}

		if ( ! isset( $shared_post_data['post_type'] ) || ! in_array( $shared_post_data['post_type'], array( 'post', 'page' ), true ) ) {
			return;
		}

		if ( ! isset( $shared_post_data['ssm_bulk_edit_section_id'] ) ) {
			return;
		}

		$section_id = sanitize_text_field( wp_unslash( $shared_post_data['ssm_bulk_edit_section_id'] ) );
		if ( '-1' === $section_id ) {
			return;
		}

		foreach ( $post_ids as $post_id ) {
			$post_id = absint( $post_id );
			if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
				continue;
			}

			update_post_meta( $post_id, '_ssm_section_id', absint( $section_id ) );
		}
	}

	public function save_quick_edit_section( $post_id, $post ) {
		if ( ! isset( $_POST['_inline_edit'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_inline_edit'] ) ), 'inlineeditnonce' ) ) {
			return;
		}

		if ( ! in_array( $post->post_type, array( 'post', 'page' ), true ) ) {
			return;
		}

		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) || ! isset( $_POST['ssm_quick_edit_section_id'] ) ) {
			return;
		}

		update_post_meta( $post_id, '_ssm_section_id', absint( wp_unslash( $_POST['ssm_quick_edit_section_id'] ) ) );
	}
}
