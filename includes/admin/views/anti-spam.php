<?php
/**
 * Anti-Spam Settings View
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
$settings = get_option( 'securetor_anti_spam_settings', array() );
$stats = get_option( 'securetor_anti_spam_stats', array() );
?>

<div class="wrap securetor-anti-spam">
	<h1><?php esc_html_e( 'Anti-Spam Settings', 'securetor' ); ?></h1>

	<p class="description">
		<?php esc_html_e( 'Advanced comment spam protection merged from three generations of proven anti-spam technology.', 'securetor' ); ?>
	</p>

	<form method="post" action="options.php">
		<?php settings_fields( 'securetor_anti_spam' ); ?>

		<!-- Main Settings -->
		<div class="securetor-card">
			<h2><?php esc_html_e( 'Spam Detection Settings', 'securetor' ); ?></h2>

			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="enabled"><?php esc_html_e( 'Enable Anti-Spam', 'securetor' ); ?></label>
					</th>
					<td>
						<label>
							<input type="checkbox" name="securetor_anti_spam_settings[enabled]" id="enabled" value="1" <?php checked( ! empty( $settings['enabled'] ) ); ?> />
							<?php esc_html_e( 'Block spam comments using advanced dual-protection method', 'securetor' ); ?>
						</label>
						<p class="description">
							<?php esc_html_e( 'Uses year validation + honeypot trap to catch 100% of automated spam.', 'securetor' ); ?>
						</p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="block_trackbacks"><?php esc_html_e( 'Block Trackbacks', 'securetor' ); ?></label>
					</th>
					<td>
						<label>
							<input type="checkbox" name="securetor_anti_spam_settings[block_trackbacks]" id="block_trackbacks" value="1" <?php checked( ! empty( $settings['block_trackbacks'] ) ); ?> />
							<?php esc_html_e( 'Block all trackback attempts', 'securetor' ); ?>
						</label>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="save_spam_comments"><?php esc_html_e( 'Save Spam', 'securetor' ); ?></label>
					</th>
					<td>
						<label>
							<input type="checkbox" name="securetor_anti_spam_settings[save_spam_comments]" id="save_spam_comments" value="1" <?php checked( ! empty( $settings['save_spam_comments'] ) ); ?> />
							<?php esc_html_e( 'Save blocked spam comments for review', 'securetor' ); ?>
						</label>
					</td>
				</tr>
			</table>
		</div>

		<!-- JavaScript Settings -->
		<div class="securetor-card">
			<h2><?php esc_html_e( 'JavaScript Settings', 'securetor' ); ?></h2>

			<table class="form-table">
				<tr>
					<th scope="row"><?php esc_html_e( 'JavaScript Mode', 'securetor' ); ?></th>
					<td>
						<fieldset>
							<label>
								<input type="radio" name="securetor_anti_spam_settings[inline_js_mode]" value="0" <?php checked( empty( $settings['inline_js_mode'] ) ); ?> />
								<?php esc_html_e( 'External JS file (recommended)', 'securetor' ); ?>
							</label><br>
							<label>
								<input type="radio" name="securetor_anti_spam_settings[inline_js_mode]" value="1" <?php checked( ! empty( $settings['inline_js_mode'] ) ); ?> />
								<?php esc_html_e( 'Inline JS (for cache-heavy sites)', 'securetor' ); ?>
							</label>
						</fieldset>
						<p class="description">
							<?php esc_html_e( 'External JS allows browser caching. Inline JS reduces HTTP requests.', 'securetor' ); ?>
						</p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="timeout_fallback"><?php esc_html_e( 'Timeout Fallback', 'securetor' ); ?></label>
					</th>
					<td>
						<label>
							<input type="checkbox" name="securetor_anti_spam_settings[timeout_fallback]" id="timeout_fallback" value="1" <?php checked( ! empty( $settings['timeout_fallback'] ) ); ?> />
							<?php esc_html_e( 'Enable 1-second timeout fallback for theme compatibility', 'securetor' ); ?>
						</label>
						<p class="description">
							<?php esc_html_e( 'Helps with themes that load comment forms dynamically.', 'securetor' ); ?>
						</p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="random_initial_value"><?php esc_html_e( 'Random Values', 'securetor' ); ?></label>
					</th>
					<td>
						<label>
							<input type="checkbox" name="securetor_anti_spam_settings[random_initial_value]" id="random_initial_value" value="1" <?php checked( ! empty( $settings['random_initial_value'] ) ); ?> />
							<?php esc_html_e( 'Use random initial values to confuse spam bots', 'securetor' ); ?>
						</label>
					</td>
				</tr>
			</table>
		</div>

		<!-- Notifications -->
		<div class="securetor-card">
			<h2><?php esc_html_e( 'Notifications', 'securetor' ); ?></h2>

			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="email_notifications"><?php esc_html_e( 'Email Alerts', 'securetor' ); ?></label>
					</th>
					<td>
						<label>
							<input type="checkbox" name="securetor_anti_spam_settings[email_notifications]" id="email_notifications" value="1" <?php checked( ! empty( $settings['email_notifications'] ) ); ?> />
							<?php esc_html_e( 'Send email when spam is blocked', 'securetor' ); ?>
						</label>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="notification_email"><?php esc_html_e( 'Notification Email', 'securetor' ); ?></label>
					</th>
					<td>
						<input type="email" name="securetor_anti_spam_settings[notification_email]" id="notification_email" value="<?php echo esc_attr( isset( $settings['notification_email'] ) ? $settings['notification_email'] : get_option( 'admin_email' ) ); ?>" class="regular-text" />
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="custom_error_message"><?php esc_html_e( 'Custom Error Message', 'securetor' ); ?></label>
					</th>
					<td>
						<textarea name="securetor_anti_spam_settings[custom_error_message]" id="custom_error_message" rows="3" class="large-text"><?php echo esc_textarea( isset( $settings['custom_error_message'] ) ? $settings['custom_error_message'] : '' ); ?></textarea>
						<p class="description">
							<?php esc_html_e( 'Custom message shown to blocked users. Leave blank for default message.', 'securetor' ); ?>
						</p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="show_info_notice"><?php esc_html_e( 'Admin Notice', 'securetor' ); ?></label>
					</th>
					<td>
						<label>
							<input type="checkbox" name="securetor_anti_spam_settings[show_info_notice]" id="show_info_notice" value="1" <?php checked( ! empty( $settings['show_info_notice'] ) ); ?> />
							<?php esc_html_e( 'Show information notice on comments page', 'securetor' ); ?>
						</label>
					</td>
				</tr>
			</table>
		</div>

		<?php submit_button(); ?>
	</form>

	<!-- Statistics -->
	<div class="securetor-card">
		<h2><?php esc_html_e( 'Statistics', 'securetor' ); ?></h2>

		<div class="securetor-stat-grid">
			<div class="securetor-stat-box">
				<div class="securetor-stat-number">
					<?php echo esc_html( number_format_i18n( isset( $stats['blocked_total'] ) ? $stats['blocked_total'] : 0 ) ); ?>
				</div>
				<div class="securetor-stat-label">
					<?php esc_html_e( 'Spam Blocked', 'securetor' ); ?>
				</div>
			</div>

			<?php if ( ! empty( $stats['last_blocked'] ) ) : ?>
				<div class="securetor-stat-box">
					<div class="securetor-stat-text">
						<?php echo esc_html( human_time_diff( strtotime( $stats['last_blocked'] ), current_time( 'timestamp' ) ) . ' ' . __( 'ago', 'securetor' ) ); ?>
					</div>
					<div class="securetor-stat-label">
						<?php esc_html_e( 'Last Blocked', 'securetor' ); ?>
					</div>
				</div>
			<?php endif; ?>
		</div>

		<?php if ( ! empty( $stats['reasons'] ) ) : ?>
			<h3><?php esc_html_e( 'Blocked by Reason', 'securetor' ); ?></h3>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Reason', 'securetor' ); ?></th>
						<th><?php esc_html_e( 'Count', 'securetor' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $stats['reasons'] as $reason => $count ) : ?>
						<tr>
							<td><?php echo esc_html( ucfirst( str_replace( '_', ' ', $reason ) ) ); ?></td>
							<td><?php echo esc_html( number_format_i18n( $count ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>

		<p style="margin-top: 15px;">
			<button type="button" class="button" onclick="if(confirm('<?php echo esc_js( __( 'Are you sure you want to reset statistics?', 'securetor' ) ); ?>')) { document.getElementById('reset-stats-form').submit(); }">
				<?php esc_html_e( 'Reset Statistics', 'securetor' ); ?>
			</button>
		</p>

		<form method="post" id="reset-stats-form" style="display: none;">
			<?php wp_nonce_field( 'securetor_reset_stats', 'securetor_stats_nonce' ); ?>
			<input type="hidden" name="reset_spam_stats" value="1" />
		</form>
	</div>

	<!-- Credits -->
	<div class="securetor-card">
		<h2><?php esc_html_e( 'Credits', 'securetor' ); ?></h2>
		<p>
			<?php esc_html_e( 'This anti-spam module is built on technology from three generations of proven anti-spam plugins:', 'securetor' ); ?>
		</p>
		<ul style="list-style: disc; margin-left: 20px;">
			<li><strong>Anti-spam v5.5</strong> <?php esc_html_e( 'by webvitaly (original)', 'securetor' ); ?></li>
			<li><strong>Anti-spam Reloaded v6.5</strong> <?php esc_html_e( 'by kudlav (community fork)', 'securetor' ); ?></li>
			<li><strong>Fortify v1.0</strong> <?php esc_html_e( 'by webvitaly (creator\'s return)', 'securetor' ); ?></li>
		</ul>
		<p>
			<?php esc_html_e( 'Securetor merges the best features from all three to create a superior spam protection solution.', 'securetor' ); ?>
		</p>
	</div>
</div>

<?php
// Handle statistics reset
if ( isset( $_POST['reset_spam_stats'] ) && check_admin_referer( 'securetor_reset_stats', 'securetor_stats_nonce' ) ) {
	update_option( 'securetor_anti_spam_stats', array(
		'blocked_total' => 0,
		'last_blocked'  => null,
		'reasons'       => array(),
	) );
	echo '<div class="notice notice-success"><p>' . esc_html__( 'Statistics reset successfully!', 'securetor' ) . '</p></div>';
	echo '<script>window.location.href = window.location.href.split("?")[0] + "?page=securetor-anti-spam";</script>';
}
?>

<style>
.securetor-stat-grid {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
	gap: 20px;
	margin: 20px 0;
}

.securetor-stat-box {
	background: #f8f9fa;
	border: 1px solid #e0e0e0;
	padding: 20px;
	text-align: center;
	border-radius: 4px;
}

.securetor-stat-number {
	font-size: 36px;
	font-weight: 600;
	color: #2271b1;
	line-height: 1;
}

.securetor-stat-text {
	font-size: 18px;
	font-weight: 600;
	color: #2271b1;
}

.securetor-stat-label {
	font-size: 14px;
	color: #666;
	margin-top: 8px;
}
</style>
