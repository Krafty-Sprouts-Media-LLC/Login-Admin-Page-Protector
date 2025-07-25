<?php
/**
 * Uninstall script for Login/Admin Page Protector
 * 
 * This file is called when the plugin is deleted via WordPress admin.
 * It removes all plugin data from the database.
 */

// If uninstall not called from WordPress, then exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Remove all plugin options
$plugin_options = array(
    'ksm_lapp_cache_duration',
    'ksm_lapp_cleanup_days', 
    'ksm_lapp_use_external_api',
    'ksm_lapp_blocked_attempts',
    'ksm_lapp_ip_whitelist',
    'ksm_lapp_bypass_key',
    'ksm_lapp_bypass_log'
);

foreach ($plugin_options as $option) {
    delete_option($option);
}

// Clean up any remaining transients
global $wpdb;

// Remove all plugin-related transients
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'ksm_lapp_%'");
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_ksm_lapp_%'");
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_ksm_lapp_%'");

// Clear any scheduled events
wp_clear_scheduled_hook('ksm_lapp_cleanup_old_logs');

// Optional: Log the uninstall for debugging (only if WP_DEBUG is enabled)
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('KSM_LAPP: Plugin uninstalled and all data removed.');
}
