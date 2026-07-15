<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SSM_Navigation_Admin {
	/**
	 * @var SSM_Navigation
	 */
	private $navigation;

	public function __construct( SSM_Navigation $navigation ) {
		$this->navigation = $navigation;
	}

	public function render_settings_card() {
		$locations         = $this->navigation->get_available_theme_locations();
		$selected_location = $this->navigation->get_selected_theme_location();
		?>
		<div class="ssm-section-card">
			<div class="ssm-section-card__header">
				<h2><?php esc_html_e( 'Global Menu', 'site-section-manager' ); ?></h2>
			</div>
			<div class="ssm-section-card__body">
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ssm-section-form-grid">
					<input type="hidden" name="action" value="ssm_save_navigation_settings" />
					<?php wp_nonce_field( 'ssm_save_navigation_settings' ); ?>
					<div class="ssm-section-field">
						<label for="ssm_theme_menu_location"><?php esc_html_e( 'Theme menu location', 'site-section-manager' ); ?></label>
						<select id="ssm_theme_menu_location" name="ssm_theme_menu_location">
							<option value=""><?php esc_html_e( 'Use plugin fallback header', 'site-section-manager' ); ?></option>
							<?php foreach ( $locations as $location => $label ) : ?>
								<option value="<?php echo esc_attr( $location ); ?>" <?php selected( $selected_location, $location ); ?>>
									<?php echo esc_html( $label . ' (' . $location . ')' ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<p class="description"><?php esc_html_e( 'When selected, this menu location will be populated automatically from your section titles. Leave it blank to keep using the plugin header bar.', 'site-section-manager' ); ?></p>
					</div>
					<div class="ssm-section-actions">
						<?php submit_button( __( 'Save Menu Settings', 'site-section-manager' ), 'secondary', 'submit', false ); ?>
						<a class="button" href="<?php echo esc_url( admin_url( 'nav-menus.php' ) ); ?>"><?php esc_html_e( 'Open Menus', 'site-section-manager' ); ?></a>
					</div>
				</form>
			</div>
		</div>
		<?php
	}

	public function handle_save_section_menu() {
		if ( ! current_user_can( 'edit_theme_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do that.', 'site-section-manager' ) );
		}

		check_admin_referer( 'ssm_save_section_menu' );

		$section_id = isset( $_POST['section_id'] ) ? (int) wp_unslash( $_POST['section_id'] ) : 0;
		$is_home    = ! empty( $_POST['is_home'] );
		$menu_id    = $is_home ? $this->navigation->get_home_menu_id() : $this->navigation->get_section_menu_id( $section_id );
		$item_ids   = isset( $_POST['menu_item_id'] ) ? (array) wp_unslash( $_POST['menu_item_id'] ) : array();
		$labels     = isset( $_POST['menu_item_label'] ) ? (array) wp_unslash( $_POST['menu_item_label'] ) : array();
		$urls       = isset( $_POST['menu_item_url'] ) ? (array) wp_unslash( $_POST['menu_item_url'] ) : array();
		$deletes    = isset( $_POST['menu_item_delete'] ) ? (array) wp_unslash( $_POST['menu_item_delete'] ) : array();
		$total      = max( count( $item_ids ), count( $labels ), count( $urls ) );

		for ( $index = 0; $index < $total; $index++ ) {
			$item_id = isset( $item_ids[ $index ] ) ? (int) $item_ids[ $index ] : 0;
			$label   = isset( $labels[ $index ] ) ? sanitize_text_field( $labels[ $index ] ) : '';
			$url     = isset( $urls[ $index ] ) ? esc_url_raw( $urls[ $index ] ) : '';
			$delete  = isset( $deletes[ $index ] ) && '1' === (string) $deletes[ $index ];

			if ( $item_id && $delete ) {
				wp_delete_post( $item_id, true );
				continue;
			}

			if ( '' === $label || '' === $url ) {
				continue;
			}

			wp_update_nav_menu_item(
				$menu_id,
				$item_id,
				array(
					'menu-item-title'  => $label,
					'menu-item-url'    => $url,
					'menu-item-status' => 'publish',
					'menu-item-type'   => 'custom',
				)
			);
		}

		$redirect_url = $is_home
			? admin_url( 'admin.php?page=ssm-site-sections&section_id=0&ssm_notice=menu-updated' )
			: admin_url( 'admin.php?page=ssm-site-section-' . $section_id . '&ssm_notice=menu-updated' );

		wp_safe_redirect( $redirect_url );
		exit;
	}

	public function handle_save_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do that.', 'site-section-manager' ) );
		}

		check_admin_referer( 'ssm_save_navigation_settings' );

		$location = isset( $_POST['ssm_theme_menu_location'] ) ? wp_unslash( $_POST['ssm_theme_menu_location'] ) : '';
		$this->navigation->update_selected_theme_location( $location );

		wp_safe_redirect( add_query_arg( 'ssm_notice', 'menu-updated', wp_get_referer() ? wp_get_referer() : admin_url( 'admin.php?page=ssm-site-sections' ) ) );
		exit;
	}
}
