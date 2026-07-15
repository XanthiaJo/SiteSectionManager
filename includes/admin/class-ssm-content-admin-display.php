<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SSM_Content_Admin_Display {
	/**
	 * @var SSM_Content
	 */
	private $content;

	public function __construct( SSM_Content $content ) {
		$this->content = $content;
	}

	public function render_section_meta_box( $post ) {
		wp_nonce_field( 'ssm_save_section', 'ssm_section_nonce' );

		$current_section_id = (int) get_post_meta( $post->ID, '_ssm_section_id', true );
		if ( ! metadata_exists( 'post', $post->ID, '_ssm_section_id' ) && isset( $_GET['ssm_section_id'] ) && '' !== (string) wp_unslash( $_GET['ssm_section_id'] ) ) {
			$current_section_id = absint( $_GET['ssm_section_id'] );
		}
		$sections = $this->content->get_sections();

		echo '<p>' . esc_html__( 'Assign this item to a site section.', 'site-section-manager' ) . '</p>';
		echo '<select name="ssm_section_id" style="width:100%">';
		echo '<option value="0">' . esc_html__( 'Home', 'site-section-manager' ) . '</option>';

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

	public function render_term_section_add_field() {
		$sections            = $this->content->get_sections();
		$selected_section_id = ( isset( $_GET['ssm_section_id'] ) && '' !== (string) wp_unslash( $_GET['ssm_section_id'] ) ) ? absint( $_GET['ssm_section_id'] ) : 0;
		?>
		<?php wp_nonce_field( 'ssm_save_term_section', 'ssm_term_section_nonce' ); ?>
		<div class="form-field term-ssm-section-wrap">
			<label for="ssm_term_section_id"><?php esc_html_e( 'Site Section', 'site-section-manager' ); ?></label>
			<select name="ssm_term_section_id" id="ssm_term_section_id">
				<option value="0"<?php selected( $selected_section_id, 0 ); ?>><?php esc_html_e( 'Home', 'site-section-manager' ); ?></option>
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
					<option value="0"><?php esc_html_e( 'Home', 'site-section-manager' ); ?></option>
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
			echo esc_html__( 'Home', 'site-section-manager' );
			echo '<span class="hidden ssm-inline-section-value" data-section-id="0"></span>';
			return;
		}

		echo esc_html( get_the_title( $section_id ) );
		printf(
			'<span class="hidden ssm-inline-section-value" data-section-id="%d"></span>',
			(int) $section_id
		);
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
			return esc_html__( 'Home', 'site-section-manager' ) . '<span class="hidden ssm-inline-term-section-value" data-section-id="0"></span>';
		}

		return esc_html( get_the_title( $section_id ) ) . sprintf(
			'<span class="hidden ssm-inline-term-section-value" data-section-id="%d"></span>',
			(int) $section_id
		);
	}
}
