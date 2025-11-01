<?php
/**
 * Uninstall Script for Securetor
 *
 * This file is called when the plugin is deleted via WordPress admin.
 * It removes all plugin data from the database.
 *
 * @package    Securetor
 * @since      2.0.0
 * @author     Krafty Sprouts Media, LLC
 * @license    GPL-2.0+
 */

// If uninstall not called from WordPress, then exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Remove all Securetor options from database.
 *
 * @since 2.0.0
 */
function securetor_uninstall_remove_options() {
	$plugin_options = array(
		// Core settings
		'securetor_version',
		'securetor_activated',
		'securetor_deactivated',
		'securetor_first_activation_done',
		'securetor_show_welcome',

		// Module settings
		'securetor_enabled_modules',
		'securetor_access_control_settings',
		'securetor_anti_spam_settings',

		// Access Control data
		'securetor_access_control_stats',
		'securetor_access_control_logs',
		'securetor_ip_whitelist',
		'securetor_bypass_key',
		'securetor_bypass_log',

		// Anti-Spam data
		'securetor_anti_spam_stats',
		'securetor_anti_spam_logs',
		'securetor_anti_spam_saved',
	);

	foreach ( $plugin_options as $option ) {
		delete_option( $option );
	}
}

/**
 * Remove all Securetor transients from database.
 *
 * @since 2.0.0
 */
function securetor_uninstall_remove_transients() {
	global $wpdb;

	// Remove all Securetor transients
	$wpdb->query(
		"DELETE FROM {$wpdb->options}
		WHERE option_name LIKE '_transient_securetor_%'
		OR option_name LIKE '_transient_timeout_securetor_%'"
	);

	// Also remove legacy LAPP transients if they exist
	$wpdb->query(
		"DELETE FROM {$wpdb->options}
		WHERE option_name LIKE '_transient_ksm_lapp_%'
		OR option_name LIKE '_transient_timeout_ksm_lapp_%'"
	);
}

/**
 * Remove all Securetor user meta.
 *
 * @since 2.0.0
 */
function securetor_uninstall_remove_user_meta() {
	global $wpdb;

	// Remove Securetor user meta
	$wpdb->query(
		"DELETE FROM {$wpdb->usermeta}
		WHERE meta_key LIKE 'securetor_%'"
	);

	// Also remove legacy anti-spam user meta
	$wpdb->query(
		"DELETE FROM {$wpdb->usermeta}
		WHERE meta_key LIKE 'antispam%'
		OR meta_key LIKE 'fortify%'"
	);
}

/**
 * Clear all Securetor scheduled events.
 *
 * @since 2.0.0
 */
function securetor_uninstall_clear_cron() {
	// Clear Securetor cron jobs
	wp_clear_scheduled_hook( 'securetor_daily_cleanup' );
	wp_clear_scheduled_hook( 'securetor_weekly_summary' );

	// Also clear legacy LAPP cron jobs
	wp_clear_scheduled_hook( 'ksm_lapp_cleanup_old_logs' );
}

/**
 * Log uninstall for debugging.
 *
 * @since 2.0.0
 */
function securetor_uninstall_log() {
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log(
			sprintf(
				'[Securetor] Plugin uninstalled at %s. All data removed.',
				current_time( 'mysql' )
			)
		);
	}
}

// Execute uninstall
securetor_uninstall_remove_options();
securetor_uninstall_remove_transients();
securetor_uninstall_remove_user_meta();
securetor_uninstall_clear_cron();
securetor_uninstall_log();
