<?php
/**
 * Plugin Name: Login/Admin Page Protector
 * Description: Blocks access to WordPress login and admin pages with exceptions for Jetpack/WordPress.com and Nigeria traffic. Includes IP tracking and caching.
 * Version: 1.0.0
 * Author: Krafty Sprouts Media, LLC
 * Author URI: http://kraftysporuts.com
 * License: GPL v2 or later
 * Text Domain: ksm-lapp
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('KSM_LAPP_VERSION', '1.0.0');
define('KSM_LAPP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('KSM_LAPP_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Main plugin class
 */
class KSM_LAPP_Login_Admin_Protector {
    
    private $cache_key_prefix = 'ksm_lapp_';
    private $cache_duration = 3600; // 1 hour
    private $jetpack_ips = array();
    private $log_option_name = 'ksm_lapp_blocked_attempts';
    
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
            '195.234.108.0/22'
        );
        
        add_action('init', array($this, 'init'));
        add_action('wp_login', array($this, 'clear_user_cache'), 10, 2);
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        if ($this->is_login_or_admin_page()) {
            $this->check_access();
        }
        
        // Add admin menu
        if (is_admin()) {
            add_action('admin_menu', array($this, 'add_admin_menu'));
            add_action('admin_init', array($this, 'admin_init'));
        }
    }
    
    /**
     * Check if current page is login or admin page
     */
    private function is_login_or_admin_page() {
        global $pagenow;
        
        // Check for login page
        if ($pagenow === 'wp-login.php') {
            return true;
        }
        
        // Check for admin pages
        if (is_admin() && !wp_doing_ajax() && !wp_doing_cron()) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Main access control logic
     */
    private function check_access() {
        $user_ip = $this->get_user_ip();
        $country_code = $this->get_country_code($user_ip);
        
        // Allow Nigeria traffic
        if ($country_code === 'NG') {
            return;
        }
        
        // Allow Jetpack/WordPress.com IPs
        if ($this->is_jetpack_ip($user_ip)) {
            return;
        }
        
        // Allow logged-in users with proper capabilities
        if (is_user_logged_in() && current_user_can('manage_options')) {
            return;
        }
        
        // Block access and log attempt
        $this->log_blocked_attempt($user_ip, $country_code);
        $this->block_access();
    }
    
    /**
     * Get user's real IP address
     */
    private function get_user_ip() {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 
                        'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 
                        'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
    
    /**
     * Get country code from IP with caching and fallback methods
     */
    private function get_country_code($ip) {
        $cache_key = $this->cache_key_prefix . 'country_' . md5($ip);
        $country_code = get_transient($cache_key);
        
        if ($country_code === false) {
            // Try local IP ranges first (for Nigeria)
            $country_code = $this->get_country_from_local_ranges($ip);
            
            // If not found locally, try external API as fallback
            if ($country_code === 'UNKNOWN') {
                $country_code = $this->fetch_country_code_external($ip);
            }
            
            set_transient($cache_key, $country_code, $this->cache_duration);
        }
        
        return $country_code;
    }
    
    /**
     * Get country code from local IP ranges (Nigeria focus)
     */
    private function get_country_from_local_ranges($ip) {
        // Nigeria IP ranges (major ISPs and blocks)
        $nigeria_ranges = array(
            '41.58.0.0/16',        // MTN Nigeria
            '41.75.0.0/16',        // Airtel Nigeria
            '41.76.0.0/16',        // Airtel Nigeria
            '41.77.0.0/16',        // Airtel Nigeria
            '41.78.0.0/16',        // Airtel Nigeria
            '41.79.0.0/16',        // Airtel Nigeria
            '41.184.0.0/16',       // Various Nigerian ISPs
            '41.190.0.0/16',       // Various Nigerian ISPs
            '41.203.0.0/16',       // Various Nigerian ISPs
            '41.210.0.0/16',       // Various Nigerian ISPs
            '41.211.0.0/16',       // Various Nigerian ISPs
            '41.212.0.0/16',       // Various Nigerian ISPs
            '41.213.0.0/16',       // Various Nigerian ISPs
            '41.214.0.0/16',       // Various Nigerian ISPs
            '41.215.0.0/16',       // Various Nigerian ISPs
            '41.216.0.0/16',       // Various Nigerian ISPs
            '41.217.0.0/16',       // Various Nigerian ISPs
            '41.218.0.0/16',       // Various Nigerian ISPs
            '41.219.0.0/16',       // Various Nigerian ISPs
            '41.220.0.0/16',       // Various Nigerian ISPs
            '41.221.0.0/16',       // Various Nigerian ISPs
            '41.222.0.0/16',       // Various Nigerian ISPs
            '41.223.0.0/16',       // Various Nigerian ISPs
            '105.112.0.0/12',      // Nigeria block
            '154.113.0.0/16',      // Nigeria block
            '196.1.0.0/16',        // Nigeria block
            '196.6.0.0/16',        // Nigeria block
            '196.13.0.0/16',       // Nigeria block
            '196.27.0.0/16',       // Nigeria block
            '196.28.0.0/16',       // Nigeria block
            '196.29.0.0/16',       // Nigeria block
            '196.46.0.0/16',       // Nigeria block
            '196.49.0.0/16',       // Nigeria block
            '196.200.0.0/16',      // Nigeria block
            '196.201.0.0/16',      // Nigeria block
            '196.202.0.0/16',      // Nigeria block
            '196.203.0.0/16',      // Nigeria block
            '196.204.0.0/16',      // Nigeria block
            '196.205.0.0/16',      // Nigeria block
            '196.206.0.0/16',      // Nigeria block
            '196.207.0.0/16',      // Nigeria block
            '196.208.0.0/16',      // Nigeria block
            '196.209.0.0/16',      // Nigeria block
            '196.210.0.0/16',      // Nigeria block
            '196.211.0.0/16',      // Nigeria block
            '196.212.0.0/16',      // Nigeria block
            '196.213.0.0/16',      // Nigeria block
            '196.214.0.0/16',      // Nigeria block
            '196.215.0.0/16',      // Nigeria block
            '196.216.0.0/16',      // Nigeria block
            '196.217.0.0/16',      // Nigeria block
            '196.218.0.0/16',      // Nigeria block
            '196.219.0.0/16',      // Nigeria block
            '196.220.0.0/16',      // Nigeria block
            '196.221.0.0/16',      // Nigeria block
            '196.222.0.0/16',      // Nigeria block
            '196.223.0.0/16',      // Nigeria block
        );
        
        foreach ($nigeria_ranges as $range) {
            if ($this->ip_in_range($ip, $range)) {
                return 'NG';
            }
        }
        
        return 'UNKNOWN';
    }
    
    /**
     * Fetch country code from external API (fallback only)
     */
    private function fetch_country_code_external($ip) {
        // Only use external API if specifically enabled
        if (!get_option('ksm_lapp_use_external_api', false)) {
            return 'UNKNOWN';
        }
        
        $url = "http://ipinfo.io/{$ip}/country";
        
        $response = wp_remote_get($url, array(
            'timeout' => 3, // Reduced timeout
            'user-agent' => 'KSM-LAPP-Plugin/' . KSM_LAPP_VERSION
        ));
        
        if (is_wp_error($response)) {
            return 'UNKNOWN';
        }
        
        $body = wp_remote_retrieve_body($response);
        return trim($body) ?: 'UNKNOWN';
    }
    
    /**
     * Check if IP belongs to Jetpack/WordPress.com
     */
    private function is_jetpack_ip($ip) {
        foreach ($this->jetpack_ips as $range) {
            if ($this->ip_in_range($ip, $range)) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Check if IP is in CIDR range
     */
    private function ip_in_range($ip, $range) {
        if (strpos($range, '/') === false) {
            $range .= '/32';
        }
        
        list($range_ip, $netmask) = explode('/', $range, 2);
        $range_decimal = ip2long($range_ip);
        $ip_decimal = ip2long($ip);
        $wildcard_decimal = pow(2, (32 - $netmask)) - 1;
        $netmask_decimal = ~ $wildcard_decimal;
        
        return (($ip_decimal & $netmask_decimal) == ($range_decimal & $netmask_decimal));
    }
    
    /**
     * Log blocked attempt using WordPress options
     */
    private function log_blocked_attempt($ip, $country_code) {
        $log_entry = array(
            'ip_address' => $ip,
            'country_code' => $country_code,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
            'attempt_time' => current_time('mysql')
        );
        
        // Get existing logs
        $existing_logs = get_option($this->log_option_name, array());
        
        // Add new entry to beginning of array
        array_unshift($existing_logs, $log_entry);
        
        // Keep only last 500 entries to prevent option from getting too large
        $existing_logs = array_slice($existing_logs, 0, 500);
        
        // Update option
        update_option($this->log_option_name, $existing_logs);
    }
    
    /**
     * Block access with 403 response
     */
    private function block_access() {
        status_header(403);
        
        $blocked_page = '<!DOCTYPE html>
<html>
<head>
    <title>Access Denied</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; margin-top: 50px; }
        .error { color: #d32f2f; }
    </style>
</head>
<body>
    <h1 class="error">Access Denied</h1>
    <p>You do not have permission to access this page.</p>
    <p>If you believe this is an error, please contact the site administrator.</p>
</body>
</html>';
        
        echo $blocked_page;
        exit;
    }
    
    /**
     * Clear user-specific cache on login
     */
    public function clear_user_cache($user_login, $user) {
        $user_ip = $this->get_user_ip();
        $cache_key = $this->cache_key_prefix . 'country_' . md5($user_ip);
        delete_transient($cache_key);
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        $this->set_default_options();
        
        // Schedule cleanup if not already scheduled
        if (!wp_next_scheduled('ksm_lapp_cleanup_old_logs')) {
            wp_schedule_event(time(), 'daily', 'ksm_lapp_cleanup_old_logs');
        }
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clean up transients
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '{$this->cache_key_prefix}%'");
        
        // Clear scheduled cleanup
        wp_clear_scheduled_hook('ksm_lapp_cleanup_old_logs');
    }

    
    /**
     * Set default options
     */
    private function set_default_options() {
        add_option('ksm_lapp_cache_duration', 3600);
        add_option('ksm_lapp_cleanup_days', 30);
        add_option('ksm_lapp_use_external_api', false); // Disabled by default
        add_option($this->log_option_name, array());
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_options_page(
            'Login/Admin Page Protector',
            'Login Protector',
            'manage_options',
            'ksm-lapp-settings',
            array($this, 'admin_page')
        );
    }
    
    /**
     * Admin page initialization
     */
    public function admin_init() {
        register_setting('ksm_lapp_settings', 'ksm_lapp_cache_duration');
        register_setting('ksm_lapp_settings', 'ksm_lapp_cleanup_days');
        register_setting('ksm_lapp_settings', 'ksm_lapp_use_external_api');
        
        add_action('ksm_lapp_cleanup_old_logs', array($this, 'cleanup_old_logs'));
    }
    
    /**
     * Admin page HTML
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Login/Admin Page Protector Settings</h1>
            
            <form method="post" action="options.php">
                <?php settings_fields('ksm_lapp_settings'); ?>
                <?php do_settings_sections('ksm_lapp_settings'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">Cache Duration (seconds)</th>
                        <td>
                            <input type="number" name="ksm_lapp_cache_duration" value="<?php echo esc_attr(get_option('ksm_lapp_cache_duration', 3600)); ?>" min="300" max="86400" />
                            <p class="description">How long to cache IP geolocation data (300-86400 seconds)</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Log Cleanup (days)</th>
                        <td>
                            <input type="number" name="ksm_lapp_cleanup_days" value="<?php echo esc_attr(get_option('ksm_lapp_cleanup_days', 30)); ?>" min="1" max="365" />
                            <p class="description">Delete blocked attempt logs older than this many days</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Use External API</th>
                        <td>
                            <label>
                                <input type="checkbox" name="ksm_lapp_use_external_api" value="1" <?php checked(get_option('ksm_lapp_use_external_api', false)); ?> />
                                Enable external IP geolocation API for non-Nigeria IPs
                            </label>
                            <p class="description">If disabled, only Nigeria IPs will be identified (recommended for privacy and performance)</p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
            
            <h2>Recent Blocked Attempts</h2>
            <?php $this->display_blocked_attempts(); ?>
        </div>
        <?php
    }
    
    /**
     * Display blocked attempts table
     */
    private function display_blocked_attempts() {
        $attempts = get_option($this->log_option_name, array());
        
        if (empty($attempts)) {
            echo '<p>No blocked attempts recorded yet.</p>';
            return;
        }
        
        // Show only last 50 attempts
        $recent_attempts = array_slice($attempts, 0, 50);
        
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>IP Address</th><th>Country</th><th>User Agent</th><th>Request URI</th><th>Time</th></tr></thead>';
        echo '<tbody>';
        
        foreach ($recent_attempts as $attempt) {
            echo '<tr>';
            echo '<td>' . esc_html($attempt['ip_address']) . '</td>';
            echo '<td>' . esc_html($attempt['country_code']) . '</td>';
            echo '<td>' . esc_html(substr($attempt['user_agent'], 0, 50)) . '...</td>';
            echo '<td>' . esc_html($attempt['request_uri']) . '</td>';
            echo '<td>' . esc_html($attempt['attempt_time']) . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
        
        $total_attempts = count($attempts);
        echo '<p><strong>Total logged attempts: ' . $total_attempts . '</strong> (showing last 50)</p>';
    }
    
    /**
     * Cleanup old logs
     */
    public function cleanup_old_logs() {
        $cleanup_days = get_option('ksm_lapp_cleanup_days', 30);
        $cutoff_timestamp = strtotime("-{$cleanup_days} days");
        
        $attempts = get_option($this->log_option_name, array());
        
        if (empty($attempts)) {
            return;
        }
        
        // Filter out old entries
        $cleaned_attempts = array_filter($attempts, function($attempt) use ($cutoff_timestamp) {
            $attempt_timestamp = strtotime($attempt['attempt_time']);
            return $attempt_timestamp > $cutoff_timestamp;
        });
        
        // Re-index array and update option
        $cleaned_attempts = array_values($cleaned_attempts);
        update_option($this->log_option_name, $cleaned_attempts);
    }
}

// Initialize the plugin
new KSM_LAPP_Login_Admin_Protector();
