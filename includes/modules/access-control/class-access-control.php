<?php
/**
 * Access Control Module
 *
 * Geographic-based access control for WordPress login and admin pages.
 * Evolved from Login/Admin Page Protector by Krafty Sprouts Media, LLC.
 *
 * @package    Securetor
 * @subpackage Modules/Access_Control
 * @since      2.0.0
 * @author     Krafty Sprouts Media, LLC
 * @license    GPL-2.0+
 */

namespace Securetor\Modules;

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Access Control class.
 *
 * Blocks unauthorized access to login and admin pages based on
 * geographic location with whitelist and bypass options.
 *
 * @since 2.0.0
 */
class Access_Control {

	/**
	 * Cache key prefix for transients.
	 *
	 * @since 2.0.0
	 * @var   string
	 */
	private $cache_key_prefix = 'securetor_ac_';

	/**
	 * Jetpack/WordPress.com IP ranges.
	 *
	 * @since 2.0.0
	 * @var   array
	 */
	private $jetpack_ips = array();

	/**
	 * Module settings.
	 *
	 * @since 2.0.0
	 * @var   array
	 */
	private $settings = array();

	/**
	 * Initialize the module.
	 *
	 * @since 2.0.0
	 */
	public function __construct() {
		// Jetpack/WordPress.com IP ranges (updated as of 2024)
		$this->jetpack_ips = array(
			'192.0.64.0/18',
			'198.181.116.0/20',
			'66.155.8.0/21',
			'66.155.9.0/24',
			'66.155.11.0/24',
			'76.74.248.0/21',
			'76.74.254.0/24',
			'195.234.108.0/22',
		);

		// Load settings
		$this->settings = get_option( 'securetor_access_control_settings', array() );

		// Initialize hooks
		$this->init_hooks();
	}

	/**
	 * Initialize WordPress hooks.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	private function init_hooks() {
		add_action( 'init', array( $this, 'check_emergency_bypass' ), 1 );
		add_action( 'init', array( $this, 'check_page_access' ), 2 );
		add_action( 'wp_login', array( $this, 'clear_user_cache' ), 10, 2 );
		add_action( 'securetor_daily_cleanup', array( $this, 'cleanup_old_logs' ) );
	}

	/**
	 * Emergency bypass for locked-out admins.
	 *
	 * Usage: Add ?emergency_bypass=YOUR_KEY to any admin URL
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function check_emergency_bypass() {
		if ( ! isset( $_GET['emergency_bypass'] ) ) {
			return;
		}

		$provided_key = sanitize_text_field( wp_unslash( $_GET['emergency_bypass'] ) );
		$stored_key = get_option( 'securetor_bypass_key' );

		if ( empty( $stored_key ) || ! hash_equals( $stored_key, $provided_key ) ) {
			return;
		}

		// Set a temporary session to bypass protection
		if ( ! session_id() ) {
			session_start();
		}
		$_SESSION['securetor_bypass'] = time() + 1800; // 30 minutes

		// Log the bypass usage
		$this->log_bypass_usage();

		// Redirect to remove the bypass key from URL
		$clean_url = remove_query_arg( 'emergency_bypass' );
		wp_safe_redirect( $clean_url );
		exit;
	}

	/**
	 * Check if emergency bypass is active.
	 *
	 * @since 2.0.0
	 * @return bool True if bypass is active.
	 */
	private function is_emergency_bypass_active() {
		if ( ! session_id() ) {
			session_start();
		}

		return isset( $_SESSION['securetor_bypass'] ) &&
			   $_SESSION['securetor_bypass'] > time();
	}

	/**
	 * Check page access and enforce restrictions.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function check_page_access() {
		// Skip if module is disabled
		if ( empty( $this->settings['enabled'] ) ) {
			return;
		}

		// Only check login/admin pages
		if ( ! $this->is_login_or_admin_page() ) {
			return;
		}

		$this->check_access();
	}

	/**
	 * Check if current page is login or admin page.
	 *
	 * @since 2.0.0
	 * @return bool True if login or admin page.
	 */
	private function is_login_or_admin_page() {
		global $pagenow;

		// Check for login page
		if ( 'wp-login.php' === $pagenow ) {
			return true;
		}

		// Check for admin pages
		if ( is_admin() && ! wp_doing_ajax() && ! wp_doing_cron() ) {
			return true;
		}

		return false;
	}

	/**
	 * Main access control logic.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	private function check_access() {
		// Check emergency bypass first
		if ( $this->is_emergency_bypass_active() ) {
			return;
		}

		$user_ip = $this->get_user_ip();

		// Check IP whitelist first
		if ( $this->is_ip_whitelisted( $user_ip ) ) {
			return;
		}

		$country_code = $this->get_country_code( $user_ip );

		// Allow traffic from allowed countries
		$allowed_countries = isset( $this->settings['allowed_countries'] ) ?
			$this->settings['allowed_countries'] : array( 'NG' );

		if ( in_array( $country_code, $allowed_countries, true ) ) {
			return;
		}

		// Allow Jetpack/WordPress.com IPs
		if ( $this->is_jetpack_ip( $user_ip ) ) {
			return;
		}

		// Allow logged-in users with proper capabilities (but only for admin, not login)
		if ( is_user_logged_in() && current_user_can( 'manage_options' ) && is_admin() ) {
			return;
		}

		// Allow WP-CLI access
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			return;
		}

		// Allow REST API authentication calls (for mobile apps, etc.)
		if ( $this->is_rest_api_auth() ) {
			return;
		}

		// Block access and log attempt
		$this->log_blocked_attempt( $user_ip, $country_code );
		$this->block_access();
	}

	/**
	 * Check if this is a REST API authentication request.
	 *
	 * @since 2.0.0
	 * @return bool True if REST API auth request.
	 */
	private function is_rest_api_auth() {
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ?
			sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

		return ( strpos( $request_uri, '/wp-json/' ) !== false &&
				(strpos( $request_uri, '/wp/v2/users/me' ) !== false ||
				 strpos( $request_uri, '/jwt-auth/' ) !== false) );
	}

	/**
	 * Check if IP is in whitelist.
	 *
	 * @since 2.0.0
	 *
	 * @param string $ip IP address to check.
	 * @return bool True if whitelisted.
	 */
	private function is_ip_whitelisted( $ip ) {
		$whitelist = get_option( 'securetor_ip_whitelist', array() );

		foreach ( $whitelist as $whitelisted_entry ) {
			$whitelisted_ip = $whitelisted_entry['ip'];

			// Support both single IPs and CIDR ranges
			if ( strpos( $whitelisted_ip, '/' ) !== false ) {
				if ( $this->ip_in_range( $ip, $whitelisted_ip ) ) {
					return true;
				}
			} else {
				if ( $ip === $whitelisted_ip ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Get user's real IP address.
	 *
	 * @since 2.0.0
	 * @return string IP address.
	 */
	private function get_user_ip() {
		$ip_keys = array(
			'HTTP_CLIENT_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_FORWARDED',
			'HTTP_X_CLUSTER_CLIENT_IP',
			'HTTP_FORWARDED_FOR',
			'HTTP_FORWARDED',
			'REMOTE_ADDR',
		);

		foreach ( $ip_keys as $key ) {
			if ( ! array_key_exists( $key, $_SERVER ) ) {
				continue;
			}

			foreach ( explode( ',', $_SERVER[ $key ] ) as $ip ) {
				$ip = trim( $ip );

				// Validate and sanitize IP
				if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) !== false ) {
					// Additional security: Check for suspicious patterns
					if ( ! $this->is_suspicious_ip( $ip ) ) {
						return $ip;
					}
				}
			}
		}

		return isset( $_SERVER['REMOTE_ADDR'] ) ?
			sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '127.0.0.1';
	}

	/**
	 * Check for suspicious IP patterns.
	 *
	 * @since 2.0.0
	 *
	 * @param string $ip IP address to check.
	 * @return bool True if suspicious.
	 */
	private function is_suspicious_ip( $ip ) {
		// Check for obviously fake IPs or common attack patterns
		$suspicious_patterns = array(
			'0.0.0.0',
			'255.255.255.255',
			'127.0.0.1',
			'::1',
		);

		return in_array( $ip, $suspicious_patterns, true );
	}

	/**
	 * Get country code from IP with enhanced caching.
	 *
	 * @since 2.0.0
	 *
	 * @param string $ip IP address.
	 * @return string Country code or 'UNKNOWN'.
	 */
	private function get_country_code( $ip ) {
		$cache_key = $this->cache_key_prefix . 'country_' . md5( $ip );
		$country_code = get_transient( $cache_key );

		if ( false !== $country_code ) {
			return $country_code;
		}

		// Try local IP ranges first
		$country_code = $this->get_country_from_local_ranges( $ip );

		// If not found locally, try external API as fallback
		if ( 'UNKNOWN' === $country_code ) {
			$country_code = $this->fetch_country_code_external( $ip );
		}

		// Cache with shorter duration for unknown IPs to retry sooner
		$cache_duration = ( 'UNKNOWN' === $country_code ) ? 300 : 3600;
		if ( isset( $this->settings['cache_duration'] ) ) {
			$cache_duration = ( 'UNKNOWN' === $country_code ) ? 300 : (int) $this->settings['cache_duration'];
		}

		set_transient( $cache_key, $country_code, $cache_duration );

		return $country_code;
	}

	/**
	 * Get country from local IP ranges.
	 *
	 * Enhanced Nigeria IP ranges with latest 2024 updates.
	 *
	 * @since 2.0.0
	 *
	 * @param string $ip IP address.
	 * @return string Country code or 'UNKNOWN'.
	 */
	private function get_country_from_local_ranges( $ip ) {
		// Nigeria IP ranges - EXPANDED with 2024 updates
		$nigeria_ranges = array(
			// MTN Nigeria - Updated ranges
			'41.58.0.0/16', '41.75.0.0/16', '105.112.0.0/12', '102.176.0.0/12',
			'102.90.0.0/16', // Added based on blocked mobile access

			// Airtel Nigeria - Updated ranges
			'41.76.0.0/16', '41.77.0.0/16', '41.78.0.0/16', '41.79.0.0/16',
			'102.67.0.0/16', '105.235.0.0/16',

			// Glo Nigeria - Updated ranges
			'154.113.0.0/16', '41.203.0.0/16', '41.184.0.0/16', '102.89.0.0/16',

			// 9mobile (Etisalat Nigeria) - Updated ranges
			'41.190.0.0/16', '196.6.0.0/16', '102.91.0.0/16',

			// Major ISP blocks - 2024 updates
			'196.1.0.0/16', '196.13.0.0/16', '196.27.0.0/16', '196.28.0.0/16',
			'196.29.0.0/16', '196.46.0.0/16', '196.49.0.0/16', '197.149.0.0/16',

			// Internet Exchange and backbone providers
			'196.200.0.0/13', '196.208.0.0/12', '197.210.0.0/16',

			// Government and educational institutions
			'129.205.0.0/16', '165.73.0.0/16', '165.88.0.0/16',

			// Additional verified Nigerian blocks (2024)
			'102.88.0.0/16', '102.90.0.0/16', '105.224.0.0/12',
			'197.242.0.0/16', '197.253.0.0/16', '197.255.0.0/16',
		);

		foreach ( $nigeria_ranges as $range ) {
			if ( $this->ip_in_range( $ip, $range ) ) {
				return 'NG';
			}
		}

		return 'UNKNOWN';
	}

	/**
	 * Fetch country code from external API.
	 *
	 * Uses multiple API endpoints for redundancy.
	 *
	 * @since 2.0.0
	 *
	 * @param string $ip IP address.
	 * @return string Country code or 'UNKNOWN'.
	 */
	private function fetch_country_code_external( $ip ) {
		if ( empty( $this->settings['use_external_api'] ) ) {
			return 'UNKNOWN';
		}

		if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) === false ) {
			return 'UNKNOWN';
		}

		// Try multiple API endpoints for redundancy
		$apis = array(
			"http://ipinfo.io/{$ip}/country",
			"http://ip-api.com/line/{$ip}?fields=countryCode",
		);

		foreach ( $apis as $url ) {
			$response = wp_remote_get(
				$url,
				array(
					'timeout'    => 2,
					'user-agent' => 'Securetor/' . SECURETOR_VERSION,
					'headers'    => array( 'Accept' => 'text/plain' ),
				)
			);

			if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
				continue;
			}

			$body = trim( wp_remote_retrieve_body( $response ) );

			// Handle different API response formats
			if ( strpos( $url, 'ip-api.com' ) !== false ) {
				$parts = explode( "\n", $body );
				$country_code = isset( $parts[0] ) ? trim( $parts[0] ) : '';
			} else {
				$country_code = $body;
			}

			if ( 2 === strlen( $country_code ) && ctype_alpha( $country_code ) ) {
				return strtoupper( $country_code );
			}
		}

		return 'UNKNOWN';
	}

	/**
	 * Log blocked attempt with enhanced details.
	 *
	 * @since 2.0.0
	 *
	 * @param string $ip IP address.
	 * @param string $country_code Country code.
	 * @return void
	 */
	private function log_blocked_attempt( $ip, $country_code ) {
		$log_entry = array(
			'ip_address'       => $ip,
			'country_code'     => $country_code,
			'user_agent'       => isset( $_SERVER['HTTP_USER_AGENT'] ) ? substr( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ), 0, 255 ) : '',
			'request_uri'      => isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '',
			'attempt_time'     => current_time( 'mysql' ),
			'server_vars'      => array(
				'HTTP_X_FORWARDED_FOR' => isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) : '',
				'HTTP_CLIENT_IP'       => isset( $_SERVER['HTTP_CLIENT_IP'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) ) : '',
				'REMOTE_ADDR'          => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '',
				'HTTP_X_REAL_IP'       => isset( $_SERVER['HTTP_X_REAL_IP'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_REAL_IP'] ) ) : '',
			),
			'is_login_attempt' => ( strpos( isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '', 'wp-login.php' ) !== false ),
			'referer'          => isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '',
		);

		$existing_logs = get_option( 'securetor_access_control_logs', array() );
		array_unshift( $existing_logs, $log_entry );

		// Keep only last 1000 entries
		$existing_logs = array_slice( $existing_logs, 0, 1000 );
		update_option( 'securetor_access_control_logs', $existing_logs );

		// Update statistics
		$stats = get_option( 'securetor_access_control_stats', array() );
		$stats['blocked_total'] = isset( $stats['blocked_total'] ) ? $stats['blocked_total'] + 1 : 1;
		$stats['last_blocked'] = current_time( 'mysql' );
		update_option( 'securetor_access_control_stats', $stats );
	}

	/**
	 * Log bypass usage for security monitoring.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	private function log_bypass_usage() {
		$bypass_log = get_option( 'securetor_bypass_log', array() );

		$log_entry = array(
			'ip_address'  => $this->get_user_ip(),
			'user_agent'  => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '',
			'bypass_time' => current_time( 'mysql' ),
			'request_uri' => isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '',
		);

		array_unshift( $bypass_log, $log_entry );
		$bypass_log = array_slice( $bypass_log, 0, 100 ); // Keep last 100 entries

		update_option( 'securetor_bypass_log', $bypass_log );
	}

	/**
	 * Display blocked access page.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	private function block_access() {
		status_header( 403 );

		$user_ip = esc_html( $this->get_user_ip() );
		$current_time = esc_html( current_time( 'Y-m-d H:i:s T' ) );

		$blocked_page = '<!DOCTYPE html>
<html>
<head>
	<title>' . esc_html__( 'Access Denied', 'securetor' ) . '</title>
	<meta name="robots" content="noindex,nofollow">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<style>
		body {
			font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
			text-align: center; margin: 0; padding: 20px; background: #f8f9fa;
			color: #495057; line-height: 1.6;
		}
		.container {
			max-width: 600px; margin: 50px auto; background: white;
			padding: 40px; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.1);
		}
		.error { color: #dc3545; font-size: 28px; margin-bottom: 20px; font-weight: 600; }
		p { margin-bottom: 15px; }
		.info { background: #e9ecef; padding: 15px; border-radius: 5px; margin: 20px 0; }
		.contact { background: #d1ecf1; border: 1px solid #bee5eb; padding: 15px; border-radius: 5px; }
		code { background: #f8f9fa; padding: 2px 5px; border-radius: 3px; font-family: monospace; }
	</style>
</head>
<body>
	<div class="container">
		<h1 class="error">🚫 ' . esc_html__( 'Access Denied', 'securetor' ) . '</h1>
		<p><strong>' . esc_html__( 'You do not have permission to access this page.', 'securetor' ) . '</strong></p>

		<div class="info">
			<p>' . esc_html__( 'This website restricts access to administrative areas based on geographic location for enhanced security.', 'securetor' ) . '</p>
			<p><strong>' . esc_html__( 'Your IP:', 'securetor' ) . '</strong> <code>' . $user_ip . '</code></p>
		</div>

		<p><strong>' . esc_html__( 'If you are the site administrator and need emergency access:', 'securetor' ) . '</strong></p>
		<ol style="text-align: left; max-width: 400px; margin: 20px auto;">
			<li>' . esc_html__( 'Contact your hosting provider', 'securetor' ) . '</li>
			<li>' . esc_html__( 'Use FTP/cPanel to temporarily deactivate the plugin', 'securetor' ) . '</li>
			<li>' . esc_html__( 'Add your IP to the whitelist via database', 'securetor' ) . '</li>
			<li>' . esc_html__( 'Use the emergency bypass feature if configured', 'securetor' ) . '</li>
		</ol>

		<div class="contact">
			<p><strong>' . esc_html__( 'Need help?', 'securetor' ) . '</strong> ' . esc_html__( 'Contact the site administrator with the following information:', 'securetor' ) . '</p>
			<p><strong>' . esc_html__( 'Time:', 'securetor' ) . '</strong> ' . $current_time . '<br>
			<strong>' . esc_html__( 'Your IP:', 'securetor' ) . '</strong> ' . $user_ip . '</p>
		</div>

		<p style="margin-top: 20px; font-size: 12px; color: #999;">' . esc_html__( 'Protected by Securetor', 'securetor' ) . '</p>
	</div>
</body>
</html>';

		echo $blocked_page; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	/**
	 * Check if IP is in range.
	 *
	 * @since 2.0.0
	 *
	 * @param string $ip IP address.
	 * @param string $range CIDR range.
	 * @return bool True if in range.
	 */
	private function ip_in_range( $ip, $range ) {
		if ( false === strpos( $range, '/' ) ) {
			$range .= '/32';
		}

		list( $range_ip, $netmask ) = explode( '/', $range, 2 );
		$range_decimal = ip2long( $range_ip );
		$ip_decimal = ip2long( $ip );

		if ( false === $range_decimal || false === $ip_decimal ) {
			return false;
		}

		$wildcard_decimal = pow( 2, ( 32 - $netmask ) ) - 1;
		$netmask_decimal = ~ $wildcard_decimal;

		return ( ( $ip_decimal & $netmask_decimal ) === ( $range_decimal & $netmask_decimal ) );
	}

	/**
	 * Check if IP is Jetpack IP.
	 *
	 * @since 2.0.0
	 *
	 * @param string $ip IP address.
	 * @return bool True if Jetpack IP.
	 */
	private function is_jetpack_ip( $ip ) {
		foreach ( $this->jetpack_ips as $range ) {
			if ( $this->ip_in_range( $ip, $range ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Clear user cache on login.
	 *
	 * @since 2.0.0
	 *
	 * @param string  $user_login Username.
	 * @param WP_User $user User object.
	 * @return void
	 */
	public function clear_user_cache( $user_login, $user ) {
		$user_ip = $this->get_user_ip();
		$cache_key = $this->cache_key_prefix . 'country_' . md5( $user_ip );
		delete_transient( $cache_key );
	}

	/**
	 * Cleanup old logs.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function cleanup_old_logs() {
		$cleanup_days = 30;
		$cutoff_timestamp = strtotime( "-{$cleanup_days} days" );

		$attempts = get_option( 'securetor_access_control_logs', array() );

		if ( empty( $attempts ) ) {
			return;
		}

		$cleaned_attempts = array_filter(
			$attempts,
			function( $attempt ) use ( $cutoff_timestamp ) {
				$attempt_timestamp = strtotime( $attempt['attempt_time'] );
				return $attempt_timestamp > $cutoff_timestamp;
			}
		);

		$cleaned_attempts = array_values( $cleaned_attempts );
		update_option( 'securetor_access_control_logs', $cleaned_attempts );
	}
}
