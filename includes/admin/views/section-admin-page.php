<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap ssm-section-page">
	<h1><?php esc_html_e( 'Site Sections', 'site-section-manager' ); ?></h1>
	<p class="ssm-section-hero"><?php esc_html_e( 'Create and manage section containers for your pages, posts, categories, and tags.', 'site-section-manager' ); ?></p>

	<?php $this->render_notices(); ?>

	<?php if ( $current_view ) : ?>
		<?php $this->render_section_dashboard( $current_view ); ?>
	<?php else : ?>
		<div class="ssm-section-dashboard">
			<?php $this->render_dashboard_card( $unsectioned->post_title, (int) $unsectioned->posts_count + (int) $unsectioned->pages_count, $unsectioned->link ); ?>
		</div>

		<?php $this->render_create_section_form(); ?>
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
