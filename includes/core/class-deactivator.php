<?php
/**
 * Securetor Deactivator
 *
 * Fired during plugin deactivation.
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
 * Deactivator class.
 *
 * Handles plugin deactivation tasks.
 *
 * @since 2.0.0
 */
class Deactivator {

	/**
	 * Deactivation hook callback.
	 *
	 * Cleans up scheduled events and transients.
	 * Does NOT delete user data or settings (see uninstall.php for that).
	 *
	 * @since 2.0.0
	 * @static
	 * @return void
	 */
	public static function deactivate() {
		// Clear all transients (cache)
		self::clear_transients();

		// Unschedule all events
		self::unschedule_events();

		// Flush rewrite rules
		flush_rewrite_rules();

		// Set deactivation timestamp
		update_option( 'securetor_deactivated', current_time( 'timestamp' ) );

		/**
		 * Fires after Securetor is deactivated.
		 *
		 * @since 2.0.0
		 */
		do_action( 'securetor_deactivated' );

		// Log deactivation if WP_DEBUG is enabled
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( sprintf(
				'[Securetor] Plugin deactivated at %s',
				current_time( 'mysql' )
			) );
		}
	}

	/**
	 * Clear all plugin transients.
	 *
	 * @since 2.0.0
	 * @static
	 * @access private
	 * @return void
	 */
	private static function clear_transients() {
		global $wpdb;

		// Clear transients matching securetor_* pattern
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options}
				WHERE option_name LIKE %s
				OR option_name LIKE %s",
				$wpdb->esc_like( '_transient_securetor_' ) . '%',
				$wpdb->esc_like( '_transient_timeout_securetor_' ) . '%'
			)
		);

		// Also clear legacy LAPP transients if they exist
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options}
				WHERE option_name LIKE %s
				OR option_name LIKE %s",
				$wpdb->esc_like( '_transient_ksm_lapp_' ) . '%',
				$wpdb->esc_like( '_transient_timeout_ksm_lapp_' ) . '%'
			)
		);
	}

	/**
	 * Unschedule all plugin events.
	 *
	 * @since 2.0.0
	 * @static
	 * @access private
	 * @return void
	 */
	private static function unschedule_events() {
		// Unschedule cleanup event
		$timestamp = wp_next_scheduled( 'securetor_daily_cleanup' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'securetor_daily_cleanup' );
		}

		// Unschedule weekly summary
		$timestamp = wp_next_scheduled( 'securetor_weekly_summary' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'securetor_weekly_summary' );
		}

		// Clear all scheduled hooks (backup method)
		wp_clear_scheduled_hook( 'securetor_daily_cleanup' );
		wp_clear_scheduled_hook( 'securetor_weekly_summary' );

		// Also clear legacy LAPP events if they exist
		wp_clear_scheduled_hook( 'ksm_lapp_cleanup_old_logs' );
	}
}
