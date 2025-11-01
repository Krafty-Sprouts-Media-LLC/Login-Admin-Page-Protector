<?php
/**
 * Admin Dashboard View
 *
 * @package    Securetor
 * @subpackage Admin/Views
 * @since      2.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get module settings
$enabled_modules = get_option( 'securetor_enabled_modules', array() );
$access_control_enabled = ! empty( $enabled_modules['access_control'] );
$anti_spam_enabled = ! empty( $enabled_modules['anti_spam'] );

// Get statistics
$access_stats = get_option( 'securetor_access_control_stats', array() );
$spam_stats = get_option( 'securetor_anti_spam_stats', array() );
?>

<div class="wrap securetor-dashboard">
	<h1>
		<span class="dashicons dashicons-shield" style="font-size: 32px; width: 32px; height: 32px;"></span>
		<?php esc_html_e( 'Securetor Dashboard', 'securetor' ); ?>
	</h1>

	<p class="securetor-tagline">
		<?php esc_html_e( 'Comprehensive WordPress security suite by Krafty Sprouts Media, LLC', 'securetor' ); ?>
	</p>

	<!-- Security Status Overview -->
	<div class="securetor-cards">
		<div class="securetor-card securetor-card-primary">
			<h2><?php esc_html_e( 'Security Status', 'securetor' ); ?></h2>
			<div class="securetor-status-items">
				<div class="securetor-status-item">
					<span class="dashicons dashicons-<?php echo $access_control_enabled ? 'yes-alt' : 'dismiss'; ?>" style="color: <?php echo $access_control_enabled ? '#46b450' : '#dc3232'; ?>;"></span>
					<strong><?php esc_html_e( 'Access Control:', 'securetor' ); ?></strong>
					<?php echo $access_control_enabled ? esc_html__( 'Active', 'securetor' ) : esc_html__( 'Disabled', 'securetor' ); ?>
				</div>
				<div class="securetor-status-item">
					<span class="dashicons dashicons-<?php echo $anti_spam_enabled ? 'yes-alt' : 'dismiss'; ?>" style="color: <?php echo $anti_spam_enabled ? '#46b450' : '#dc3232'; ?>;"></span>
					<strong><?php esc_html_e( 'Anti-Spam:', 'securetor' ); ?></strong>
					<?php echo $anti_spam_enabled ? esc_html__( 'Active', 'securetor' ) : esc_html__( 'Disabled', 'securetor' ); ?>
				</div>
			</div>
		</div>

		<!-- Access Control Statistics -->
		<div class="securetor-card">
			<h2><?php esc_html_e( 'Access Control', 'securetor' ); ?></h2>
			<?php if ( $access_control_enabled ) : ?>
				<div class="securetor-stat-large">
					<?php echo esc_html( number_format_i18n( isset( $access_stats['blocked_total'] ) ? $access_stats['blocked_total'] : 0 ) ); ?>
				</div>
				<p class="securetor-stat-label"><?php esc_html_e( 'Blocked Access Attempts', 'securetor' ); ?></p>
				<?php if ( ! empty( $access_stats['last_blocked'] ) ) : ?>
					<p class="securetor-stat-detail">
						<?php
						printf(
							/* translators: %s: time ago */
							esc_html__( 'Last blocked: %s', 'securetor' ),
							esc_html( human_time_diff( strtotime( $access_stats['last_blocked'] ), current_time( 'timestamp' ) ) . ' ' . __( 'ago', 'securetor' ) )
						);
						?>
					</p>
				<?php endif; ?>
				<p>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=securetor-access-control' ) ); ?>" class="button">
						<?php esc_html_e( 'Manage Access Control', 'securetor' ); ?>
					</a>
				</p>
			<?php else : ?>
				<p><?php esc_html_e( 'Access Control module is currently disabled.', 'securetor' ); ?></p>
				<p>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=securetor-settings' ) ); ?>" class="button button-primary">
						<?php esc_html_e( 'Enable Access Control', 'securetor' ); ?>
					</a>
				</p>
			<?php endif; ?>
		</div>

		<!-- Anti-Spam Statistics -->
		<div class="securetor-card">
			<h2><?php esc_html_e( 'Anti-Spam', 'securetor' ); ?></h2>
			<?php if ( $anti_spam_enabled ) : ?>
				<div class="securetor-stat-large">
					<?php echo esc_html( number_format_i18n( isset( $spam_stats['blocked_total'] ) ? $spam_stats['blocked_total'] : 0 ) ); ?>
				</div>
				<p class="securetor-stat-label"><?php esc_html_e( 'Spam Comments Blocked', 'securetor' ); ?></p>
				<?php if ( ! empty( $spam_stats['last_blocked'] ) ) : ?>
					<p class="securetor-stat-detail">
						<?php
						printf(
							/* translators: %s: time ago */
							esc_html__( 'Last blocked: %s', 'securetor' ),
							esc_html( human_time_diff( strtotime( $spam_stats['last_blocked'] ), current_time( 'timestamp' ) ) . ' ' . __( 'ago', 'securetor' ) )
						);
						?>
					</p>
				<?php endif; ?>
				<p>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=securetor-anti-spam' ) ); ?>" class="button">
						<?php esc_html_e( 'Manage Anti-Spam', 'securetor' ); ?>
					</a>
				</p>
			<?php else : ?>
				<p><?php esc_html_e( 'Anti-Spam module is currently disabled.', 'securetor' ); ?></p>
				<p>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=securetor-settings' ) ); ?>" class="button button-primary">
						<?php esc_html_e( 'Enable Anti-Spam', 'securetor' ); ?>
					</a>
				</p>
			<?php endif; ?>
		</div>
	</div>

	<!-- Quick Actions -->
	<div class="securetor-card">
		<h2><?php esc_html_e( 'Quick Actions', 'securetor' ); ?></h2>
		<div class="securetor-quick-actions">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=securetor-access-control' ) ); ?>" class="button">
				<span class="dashicons dashicons-lock"></span>
				<?php esc_html_e( 'Configure Access Control', 'securetor' ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=securetor-anti-spam' ) ); ?>" class="button">
				<span class="dashicons dashicons-shield"></span>
				<?php esc_html_e( 'Configure Anti-Spam', 'securetor' ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=securetor-settings' ) ); ?>" class="button">
				<span class="dashicons dashicons-admin-generic"></span>
				<?php esc_html_e( 'General Settings', 'securetor' ); ?>
			</a>
			<a href="<?php echo esc_url( 'https://kraftysprouts.com/securetor/docs' ); ?>" class="button" target="_blank">
				<span class="dashicons dashicons-book"></span>
				<?php esc_html_e( 'Documentation', 'securetor' ); ?>
			</a>
		</div>
	</div>

	<!-- System Information -->
	<div class="securetor-card">
		<h2><?php esc_html_e( 'System Information', 'securetor' ); ?></h2>
		<table class="widefat securetor-system-info">
			<tbody>
				<tr>
					<td><strong><?php esc_html_e( 'Plugin Version:', 'securetor' ); ?></strong></td>
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
					<td><strong><?php esc_html_e( 'Active Modules:', 'securetor' ); ?></strong></td>
					<td>
						<?php
						$active_count = 0;
						if ( $access_control_enabled ) {
							$active_count++;
						}
						if ( $anti_spam_enabled ) {
							$active_count++;
						}
						printf(
							/* translators: 1: active modules count, 2: total modules */
							esc_html__( '%1$d of %2$d', 'securetor' ),
							(int) $active_count,
							2
						);
						?>
					</td>
				</tr>
			</tbody>
		</table>
	</div>

	<!-- Footer -->
	<div class="securetor-footer">
		<p>
			<?php
			printf(
				/* translators: %s: Krafty Sprouts Media URL */
				__( 'Securetor is developed by <a href="%s" target="_blank">Krafty Sprouts Media, LLC</a>', 'securetor' ),
				esc_url( 'https://kraftysprouts.com' )
			);
			?>
		</p>
	</div>
</div>

<style>
.securetor-dashboard {
	max-width: 1200px;
}

.securetor-tagline {
	font-size: 14px;
	color: #666;
	margin: 0 0 20px 0;
}

.securetor-cards {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
	gap: 20px;
	margin-bottom: 20px;
}

.securetor-card {
	background: #fff;
	border: 1px solid #ccd0d4;
	padding: 20px;
	box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.securetor-card-primary {
	grid-column: 1 / -1;
}

.securetor-card h2 {
	margin-top: 0;
	padding-bottom: 10px;
	border-bottom: 1px solid #eee;
}

.securetor-status-items {
	display: flex;
	gap: 30px;
	margin-top: 15px;
}

.securetor-status-item {
	display: flex;
	align-items: center;
	gap: 8px;
}

.securetor-stat-large {
	font-size: 48px;
	font-weight: 600;
	color: #2271b1;
	line-height: 1;
	margin: 10px 0;
}

.securetor-stat-label {
	font-size: 14px;
	color: #666;
	margin: 5px 0;
}

.securetor-stat-detail {
	font-size: 12px;
	color: #999;
	margin: 5px 0 15px 0;
}

.securetor-quick-actions {
	display: flex;
	flex-wrap: wrap;
	gap: 10px;
}

.securetor-quick-actions .button {
	display: inline-flex;
	align-items: center;
	gap: 5px;
}

.securetor-system-info td {
	padding: 10px;
}

.securetor-footer {
	text-align: center;
	padding: 20px 0;
	color: #666;
	font-size: 13px;
}
</style>
