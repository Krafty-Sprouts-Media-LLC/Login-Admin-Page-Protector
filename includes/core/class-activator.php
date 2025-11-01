<?php
/**
 * Securetor Activator
 *
 * Fired during plugin activation.
 *
 * @package    Securetor
 * @subpackage Core
 * @since      2.0.0
 * @author     Krafty Sprouts Media, LLC
 * @license    GPL-2.0+
 */

namespace Securetor\Core;

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Activator class.
 *
 * Handles plugin activation tasks.
 *
 * @since 2.0.0
 */
class Activator {

	/**
	 * Activation hook callback.
	 *
	 * Sets up default options and schedules events.
	 *
	 * @since 2.0.0
	 * @static
	 * @return void
	 */
	public static function activate() {
		// Set default options
		self::set_default_options();

		// Schedule cleanup events
		self::schedule_events();

		// Set activation timestamp
		add_option( 'securetor_activated', current_time( 'timestamp' ) );

		// Set first activation flag for welcome screen
		if ( ! get_option( 'securetor_first_activation_done' ) ) {
			add_option( 'securetor_show_welcome', true );
			add_option( 'securetor_first_activation_done', true );
		}

		// Flush rewrite rules
		flush_rewrite_rules();

		/**
		 * Fires after Securetor is activated.
		 *
		 * @since 2.0.0
		 */
		do_action( 'securetor_activated' );

		// Log activation if WP_DEBUG is enabled
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( sprintf(
				'[Securetor] Plugin activated at %s',
				current_time( 'mysql' )
			) );
		}
	}

	/**
	 * Set default plugin options.
	 *
	 * @since 2.0.0
	 * @static
	 * @access private
	 * @return void
	 */
	private static function set_default_options() {
		// Core settings
		add_option( 'securetor_version', SECURETOR_VERSION );

		// Enabled modules
		add_option( 'securetor_enabled_modules', array(
			'access_control' => true,
			'anti_spam'      => false, // Disabled by default until configured
		) );

		// Access Control module defaults
		add_option( 'securetor_access_control_settings', array(
			'enabled'              => true,
			'allowed_countries'    => array( 'NG' ), // Nigeria by default
			'cache_duration'       => 3600,
			'use_external_api'     => false,
			'block_login'          => true,
			'block_admin'          => true,
		) );

		// Anti-Spam module defaults
		add_option( 'securetor_anti_spam_settings', array(
			'enabled'                => false,
			'save_spam_comments'     => false,
			'block_trackbacks'       => true,
			'inline_js_mode'         => false,
			'timeout_fallback'       => true,
			'random_initial_value'   => true,
			'email_notifications'    => false,
			'notification_email'     => get_option( 'admin_email' ),
			'custom_error_message'   => '',
			'show_info_notice'       => true,
		) );

		// Initialize statistics
		add_option( 'securetor_access_control_stats', array(
			'blocked_total'    => 0,
			'last_blocked'     => null,
			'unique_ips'       => array(),
		) );

		add_option( 'securetor_anti_spam_stats', array(
			'blocked_total'    => 0,
			'last_blocked'     => null,
			'reasons'          => array(),
		) );

		// Initialize logs (empty arrays)
		add_option( 'securetor_access_control_logs', array() );
		add_option( 'securetor_anti_spam_logs', array() );

		// IP whitelist
		add_option( 'securetor_ip_whitelist', array() );

		// Bypass key (empty initially)
		add_option( 'securetor_bypass_key', '' );
	}

	/**
	 * Schedule recurring events.
	 *
	 * @since 2.0.0
	 * @static
	 * @access private
	 * @return void
	 */
	private static function schedule_events() {
		// Schedule daily cleanup of old logs
		if ( ! wp_next_scheduled( 'securetor_daily_cleanup' ) ) {
			wp_schedule_event( time(), 'daily', 'securetor_daily_cleanup' );
		}

		// Schedule weekly statistics summary (optional)
		if ( ! wp_next_scheduled( 'securetor_weekly_summary' ) ) {
			wp_schedule_event( time(), 'weekly', 'securetor_weekly_summary' );
		}
	}
}
