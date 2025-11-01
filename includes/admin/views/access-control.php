<?php
/**
 * Access Control Settings View
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
$settings = get_option( 'securetor_access_control_settings', array() );
$stats = get_option( 'securetor_access_control_stats', array() );
$logs = get_option( 'securetor_access_control_logs', array() );
$whitelist = get_option( 'securetor_ip_whitelist', array() );
$bypass_key = get_option( 'securetor_bypass_key' );

// Get current user IP for quick add
$current_ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
?>

<div class="wrap securetor-access-control">
	<h1><?php esc_html_e( 'Access Control Settings', 'securetor' ); ?></h1>

	<form method="post" action="options.php">
		<?php settings_fields( 'securetor_access_control' ); ?>

		<!-- Main Settings -->
		<div class="securetor-card">
			<h2><?php esc_html_e( 'Access Control Settings', 'securetor' ); ?></h2>

			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="enabled"><?php esc_html_e( 'Enable Access Control', 'securetor' ); ?></label>
					</th>
					<td>
						<label>
							<input type="checkbox" name="securetor_access_control_settings[enabled]" id="enabled" value="1" <?php checked( ! empty( $settings['enabled'] ) ); ?> />
							<?php esc_html_e( 'Block unauthorized access to login and admin pages', 'securetor' ); ?>
						</label>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="allowed_countries"><?php esc_html_e( 'Allowed Countries', 'securetor' ); ?></label>
					</th>
					<td>
						<select name="securetor_access_control_settings[allowed_countries][]" id="allowed_countries" multiple size="5" style="width: 300px;">
							<?php
							$countries = array(
								'NG' => __( 'Nigeria', 'securetor' ),
								'US' => __( 'United States', 'securetor' ),
								'GB' => __( 'United Kingdom', 'securetor' ),
								'CA' => __( 'Canada', 'securetor' ),
								'KE' => __( 'Kenya', 'securetor' ),
								'GH' => __( 'Ghana', 'securetor' ),
								'ZA' => __( 'South Africa', 'securetor' ),
								'EG' => __( 'Egypt', 'securetor' ),
							);
							$allowed = isset( $settings['allowed_countries'] ) ? $settings['allowed_countries'] : array( 'NG' );
							foreach ( $countries as $code => $name ) {
								printf(
									'<option value="%s" %s>%s</option>',
									esc_attr( $code ),
									selected( in_array( $code, $allowed, true ), true, false ),
									esc_html( $name )
								);
							}
							?>
						</select>
						<p class="description"><?php esc_html_e( 'Hold Ctrl (Cmd on Mac) to select multiple countries.', 'securetor' ); ?></p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="cache_duration"><?php esc_html_e( 'Cache Duration', 'securetor' ); ?></label>
					</th>
					<td>
						<input type="number" name="securetor_access_control_settings[cache_duration]" id="cache_duration" value="<?php echo esc_attr( isset( $settings['cache_duration'] ) ? $settings['cache_duration'] : 3600 ); ?>" min="300" max="86400" style="width: 100px;" />
						<?php esc_html_e( 'seconds', 'securetor' ); ?>
						<p class="description"><?php esc_html_e( 'How long to cache IP geolocation data (300-86400 seconds).', 'securetor' ); ?></p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="use_external_api"><?php esc_html_e( 'External API', 'securetor' ); ?></label>
					</th>
					<td>
						<label>
							<input type="checkbox" name="securetor_access_control_settings[use_external_api]" id="use_external_api" value="1" <?php checked( ! empty( $settings['use_external_api'] ) ); ?> />
							<?php esc_html_e( 'Use external geolocation API as fallback', 'securetor' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Only enable if local IP ranges are insufficient. May impact privacy.', 'securetor' ); ?></p>
					</td>
				</tr>
			</table>

			<?php submit_button(); ?>
		</div>
	</form>

	<!-- Emergency Bypass -->
	<div class="securetor-card">
		<h2><?php esc_html_e( 'Emergency Bypass', 'securetor' ); ?></h2>
		<?php if ( empty( $bypass_key ) ) : ?>
			<p><?php esc_html_e( 'Generate an emergency bypass key to regain access if you get locked out.', 'securetor' ); ?></p>
			<form method="post">
				<?php wp_nonce_field( 'securetor_generate_bypass', 'securetor_bypass_nonce' ); ?>
				<p>
					<button type="submit" name="generate_bypass_key" class="button button-primary">
						<?php esc_html_e( 'Generate Emergency Bypass Key', 'securetor' ); ?>
					</button>
				</p>
			</form>
		<?php else : ?>
			<div class="notice notice-warning inline">
				<p>
					<strong><?php esc_html_e( 'Emergency Bypass URL:', 'securetor' ); ?></strong><br>
					<code style="background: #fffbf0; padding: 10px; display: block; margin: 10px 0; word-break: break-all; font-size: 12px;">
						<?php echo esc_html( home_url( '/wp-admin/?emergency_bypass=' . $bypass_key ) ); ?>
					</code>
				</p>
				<p class="description">
					<span class="dashicons dashicons-warning" style="color: #f0b849;"></span>
					<?php esc_html_e( 'Keep this URL secure! Anyone with this URL can bypass protection for 30 minutes. Save it in a password manager.', 'securetor' ); ?>
				</p>
			</div>
			<form method="post">
				<?php wp_nonce_field( 'securetor_revoke_bypass', 'securetor_bypass_nonce' ); ?>
				<p>
					<button type="submit" name="revoke_bypass_key" class="button" onclick="return confirm('<?php echo esc_js( __( 'Are you sure? This will disable emergency access!', 'securetor' ) ); ?>');">
						<?php esc_html_e( 'Revoke Bypass Key', 'securetor' ); ?>
					</button>
				</p>
			</form>
		<?php endif; ?>
	</div>

	<!-- IP Whitelist -->
	<div class="securetor-card">
		<h2><?php esc_html_e( 'IP Whitelist', 'securetor' ); ?></h2>

		<form method="post" class="securetor-add-ip-form">
			<?php wp_nonce_field( 'securetor_add_ip', 'securetor_ip_nonce' ); ?>
			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="whitelist_ip"><?php esc_html_e( 'IP Address/Range', 'securetor' ); ?></label>
					</th>
					<td>
						<input type="text" name="ip_address" id="whitelist_ip" value="" placeholder="192.168.1.100 or 192.168.1.0/24" style="width: 300px;" />
						<button type="button" class="button" onclick="document.getElementById('whitelist_ip').value='<?php echo esc_js( $current_ip ); ?>';">
							<?php esc_html_e( 'Use My IP', 'securetor' ); ?>
						</button>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="whitelist_description"><?php esc_html_e( 'Description', 'securetor' ); ?></label>
					</th>
					<td>
						<input type="text" name="description" id="whitelist_description" value="" placeholder="<?php esc_attr_e( 'Office IP, VPN, etc.', 'securetor' ); ?>" style="width: 300px;" />
					</td>
				</tr>
			</table>
			<p>
				<button type="submit" name="add_to_whitelist" class="button button-primary">
					<?php esc_html_e( 'Add to Whitelist', 'securetor' ); ?>
				</button>
			</p>
		</form>

		<h3><?php esc_html_e( 'Current Whitelist', 'securetor' ); ?></h3>
		<?php if ( ! empty( $whitelist ) ) : ?>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'IP/Range', 'securetor' ); ?></th>
						<th><?php esc_html_e( 'Description', 'securetor' ); ?></th>
						<th><?php esc_html_e( 'Added', 'securetor' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'securetor' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $whitelist as $index => $entry ) : ?>
						<tr>
							<td><code><?php echo esc_html( $entry['ip'] ); ?></code></td>
							<td><?php echo esc_html( isset( $entry['description'] ) ? $entry['description'] : '' ); ?></td>
							<td><?php echo esc_html( isset( $entry['added_time'] ) ? $entry['added_time'] : __( 'Unknown', 'securetor' ) ); ?></td>
							<td>
								<form method="post" style="display: inline;">
									<?php wp_nonce_field( 'securetor_remove_ip', 'securetor_ip_nonce' ); ?>
									<input type="hidden" name="ip_index" value="<?php echo esc_attr( $index ); ?>" />
									<button type="submit" name="remove_from_whitelist" class="button button-small" onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to remove this IP?', 'securetor' ) ); ?>');">
										<?php esc_html_e( 'Remove', 'securetor' ); ?>
									</button>
								</form>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php else : ?>
			<p><?php esc_html_e( 'No IPs in whitelist.', 'securetor' ); ?></p>
		<?php endif; ?>
	</div>

	<!-- Statistics -->
	<div class="securetor-card">
		<h2><?php esc_html_e( 'Statistics', 'securetor' ); ?></h2>
		<table class="widefat">
			<tr>
				<td><strong><?php esc_html_e( 'Total Blocked:', 'securetor' ); ?></strong></td>
				<td><?php echo esc_html( number_format_i18n( isset( $stats['blocked_total'] ) ? $stats['blocked_total'] : 0 ) ); ?></td>
			</tr>
			<tr>
				<td><strong><?php esc_html_e( 'Last Blocked:', 'securetor' ); ?></strong></td>
				<td><?php echo ! empty( $stats['last_blocked'] ) ? esc_html( $stats['last_blocked'] ) : esc_html__( 'Never', 'securetor' ); ?></td>
			</tr>
		</table>
	</div>

	<!-- Recent Blocked Attempts -->
	<?php if ( ! empty( $logs ) ) : ?>
		<div class="securetor-card">
			<h2><?php esc_html_e( 'Recent Blocked Attempts', 'securetor' ); ?></h2>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'IP Address', 'securetor' ); ?></th>
						<th><?php esc_html_e( 'Country', 'securetor' ); ?></th>
						<th><?php esc_html_e( 'Time', 'securetor' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'securetor' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					$recent_logs = array_slice( $logs, 0, 50 );
					foreach ( $recent_logs as $log ) :
						?>
						<tr>
							<td><code><?php echo esc_html( isset( $log['ip_address'] ) ? $log['ip_address'] : '' ); ?></code></td>
							<td><?php echo esc_html( isset( $log['country_code'] ) ? $log['country_code'] : 'Unknown' ); ?></td>
							<td><?php echo esc_html( isset( $log['attempt_time'] ) ? $log['attempt_time'] : '' ); ?></td>
							<td>
								<button type="button" class="button button-small" onclick="document.getElementById('whitelist_ip').value='<?php echo esc_js( $log['ip_address'] ); ?>'; window.scrollTo(0,0);">
									<?php esc_html_e( 'Whitelist', 'securetor' ); ?>
								</button>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<p class="description">
				<?php
				printf(
					/* translators: %d: total log count */
					esc_html__( 'Showing last 50 of %d attempts.', 'securetor' ),
					(int) count( $logs )
				);
				?>
			</p>
		</div>
	<?php endif; ?>
</div>

<?php
// Handle form submissions
if ( isset( $_POST['generate_bypass_key'] ) && check_admin_referer( 'securetor_generate_bypass', 'securetor_bypass_nonce' ) ) {
	$new_key = wp_generate_password( 32, false );
	update_option( 'securetor_bypass_key', $new_key );
	echo '<div class="notice notice-success"><p>' . esc_html__( 'Emergency bypass key generated successfully!', 'securetor' ) . '</p></div>';
	echo '<script>window.location.reload();</script>';
}

if ( isset( $_POST['revoke_bypass_key'] ) && check_admin_referer( 'securetor_revoke_bypass', 'securetor_bypass_nonce' ) ) {
	delete_option( 'securetor_bypass_key' );
	echo '<div class="notice notice-success"><p>' . esc_html__( 'Emergency bypass key revoked.', 'securetor' ) . '</p></div>';
	echo '<script>window.location.reload();</script>';
}

if ( isset( $_POST['add_to_whitelist'] ) && check_admin_referer( 'securetor_add_ip', 'securetor_ip_nonce' ) ) {
	$ip = isset( $_POST['ip_address'] ) ? sanitize_text_field( wp_unslash( $_POST['ip_address'] ) ) : '';
	$description = isset( $_POST['description'] ) ? sanitize_text_field( wp_unslash( $_POST['description'] ) ) : '';

	if ( ! empty( $ip ) ) {
		$whitelist = get_option( 'securetor_ip_whitelist', array() );
		$whitelist[] = array(
			'ip'          => $ip,
			'description' => $description,
			'added_by'    => wp_get_current_user()->user_login,
			'added_time'  => current_time( 'mysql' ),
		);
		update_option( 'securetor_ip_whitelist', $whitelist );
		echo '<div class="notice notice-success"><p>' . esc_html__( 'IP added to whitelist successfully!', 'securetor' ) . '</p></div>';
		echo '<script>window.location.reload();</script>';
	}
}

if ( isset( $_POST['remove_from_whitelist'] ) && check_admin_referer( 'securetor_remove_ip', 'securetor_ip_nonce' ) ) {
	$index = isset( $_POST['ip_index'] ) ? absint( $_POST['ip_index'] ) : -1;
	if ( $index >= 0 ) {
		$whitelist = get_option( 'securetor_ip_whitelist', array() );
		if ( isset( $whitelist[ $index ] ) ) {
			unset( $whitelist[ $index ] );
			$whitelist = array_values( $whitelist );
			update_option( 'securetor_ip_whitelist', $whitelist );
			echo '<div class="notice notice-success"><p>' . esc_html__( 'IP removed from whitelist successfully!', 'securetor' ) . '</p></div>';
			echo '<script>window.location.reload();</script>';
		}
	}
}
?>
