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
}
