<?php
/**
 * General Settings View
 *
 * @package    Securetor
 * @subpackage Admin/Views
 * @since      2.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get current settings
$enabled_modules = get_option( 'securetor_enabled_modules', array() );
?>

<div class="wrap securetor-settings">
	<h1><?php esc_html_e( 'Securetor Settings', 'securetor' ); ?></h1>

	<form method="post" action="options.php">
		<?php settings_fields( 'securetor_general' ); ?>

		<!-- Module Management -->
		<div class="securetor-card">
			<h2><?php esc_html_e( 'Module Management', 'securetor' ); ?></h2>
			<p><?php esc_html_e( 'Enable or disable security modules as needed.', 'securetor' ); ?></p>

			<table class="form-table">
				<tr>
					<th scope="row"><?php esc_html_e( 'Access Control Module', 'securetor' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="securetor_enabled_modules[access_control]" value="1" <?php checked( ! empty( $enabled_modules['access_control'] ) ); ?> />
							<?php esc_html_e( 'Enable geographic-based access control for login and admin pages', 'securetor' ); ?>
						</label>
						<p class="description">
							<?php esc_html_e( 'Blocks unauthorized access based on IP geolocation with whitelist support.', 'securetor' ); ?>
						</p>
					</td>
				</tr>

				<tr>
					<th scope="row"><?php esc_html_e( 'Anti-Spam Module', 'securetor' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="securetor_enabled_modules[anti_spam]" value="1" <?php checked( ! empty( $enabled_modules['anti_spam'] ) ); ?> />
							<?php esc_html_e( 'Enable advanced comment spam protection', 'securetor' ); ?>
						</label>
						<p class="description">
							<?php esc_html_e( 'Blocks automated spam using year validation and honeypot traps.', 'securetor' ); ?>
						</p>
					</td>
				</tr>
			</table>

			<?php submit_button( __( 'Save Module Settings', 'securetor' ) ); ?>
		</div>
	</form>

	<!-- System Information -->
	<div class="securetor-card">
		<h2><?php esc_html_e( 'System Information', 'securetor' ); ?></h2>

		<table class="widefat">
			<tbody>
				<tr>
					<td style="width: 200px;"><strong><?php esc_html_e( 'Plugin Version:', 'securetor' ); ?></strong></td>
					<td><?php echo esc_html( SECURETOR_VERSION ); ?></td>
				</tr>
				<tr>
					<td><strong><?php esc_html_e( 'WordPress Version:', 'securetor' ); ?></strong></td>
					<td><?php echo esc_html( get_bloginfo( 'version' ) ); ?></td>
				</tr>
				<tr>
					<td><strong><?php esc_html_e( 'PHP Version:', 'securetor' ); ?></strong></td>
					<td><?php echo esc_html( PHP_VERSION ); ?></td>
				</tr>
				<tr>
					<td><strong><?php esc_html_e( 'Server Software:', 'securetor' ); ?></strong></td>
					<td><?php echo esc_html( isset( $_SERVER['SERVER_SOFTWARE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ) : __( 'Unknown', 'securetor' ) ); ?></td>
				</tr>
				<tr>
					<td><strong><?php esc_html_e( 'Active Modules:', 'securetor' ); ?></strong></td>
					<td>
						<?php
						$active_modules = array();
						if ( ! empty( $enabled_modules['access_control'] ) ) {
							$active_modules[] = __( 'Access Control', 'securetor' );
						}
						if ( ! empty( $enabled_modules['anti_spam'] ) ) {
							$active_modules[] = __( 'Anti-Spam', 'securetor' );
						}
						echo ! empty( $active_modules ) ? esc_html( implode( ', ', $active_modules ) ) : esc_html__( 'None', 'securetor' );
						?>
					</td>
				</tr>
			</tbody>
		</table>
	</div>

	<!-- Plugin Information -->
	<div class="securetor-card">
		<h2><?php esc_html_e( 'About Securetor', 'securetor' ); ?></h2>

		<p>
			<strong><?php esc_html_e( 'Securetor', 'securetor' ); ?></strong> -
			<?php esc_html_e( 'Comprehensive WordPress security suite combining geographic access control and advanced anti-spam protection.', 'securetor' ); ?>
		</p>

		<p>
			<strong><?php esc_html_e( 'Developed by:', 'securetor' ); ?></strong>
			<a href="https://kraftysprouts.com" target="_blank">Krafty Sprouts Media, LLC</a>
		</p>

		<h3><?php esc_html_e( 'Module Origins', 'securetor' ); ?></h3>

		<p><strong><?php esc_html_e( 'Access Control Module:', 'securetor' ); ?></strong></p>
		<ul style="list-style: disc; margin-left: 20px;">
			<li><?php esc_html_e( 'Evolved from Login/Admin Page Protector by Krafty Sprouts Media, LLC', 'securetor' ); ?></li>
			<li><?php esc_html_e( 'Geographic-based security with Nigeria and multi-country support', 'securetor' ); ?></li>
		</ul>

		<p><strong><?php esc_html_e( 'Anti-Spam Module:', 'securetor' ); ?></strong></p>
		<ul style="list-style: disc; margin-left: 20px;">
			<li><strong>Anti-spam v5.5</strong> <?php esc_html_e( 'by webvitaly (original plugin)', 'securetor' ); ?></li>
			<li><strong>Anti-spam Reloaded v6.5</strong> <?php esc_html_e( 'by kudlav (community fork)', 'securetor' ); ?></li>
			<li><strong>Fortify v1.0</strong> <?php esc_html_e( 'by webvitaly (creator\'s return)', 'securetor' ); ?></li>
		</ul>

		<p style="margin-top: 20px;">
			<a href="https://kraftysprouts.com/securetor/docs" class="button" target="_blank">
				<span class="dashicons dashicons-book" style="vertical-align: middle;"></span>
				<?php esc_html_e( 'Documentation', 'securetor' ); ?>
			</a>
			<a href="https://kraftysprouts.com/support" class="button" target="_blank">
				<span class="dashicons dashicons-sos" style="vertical-align: middle;"></span>
				<?php esc_html_e( 'Support', 'securetor' ); ?>
			</a>
		</p>
	</div>

	<!-- License Information -->
	<div class="securetor-card">
		<h2><?php esc_html_e( 'License', 'securetor' ); ?></h2>
		<p>
			<?php
			printf(
				/* translators: %s: GPL license URL */
				__( 'Securetor is licensed under the <a href="%s" target="_blank">GNU General Public License v2.0 or later</a>.', 'securetor' ),
				esc_url( 'https://www.gnu.org/licenses/gpl-2.0.html' )
			);
			?>
		</p>
		<p>
			<?php esc_html_e( 'This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation.', 'securetor' ); ?>
		</p>
	</div>
</div>
