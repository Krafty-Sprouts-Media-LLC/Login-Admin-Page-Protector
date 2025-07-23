<?php
/**
 * Plugin Name: Login/Admin Page Protector
 * Description: Blocks access to WordPress login and admin pages with exceptions for Jetpack/WordPress.com and Nigeria traffic. Includes IP tracking and caching.
 * Version: 1.1.0
 * Author: Krafty Sprouts Media, LLC
 * Author URI: http://kraftysprouts.com
 * License: GPL v2 or later
 * Text Domain: ksm-lapp
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('KSM_LAPP_VERSION', '1.1.0');
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
        register_uninstall_hook(__FILE__, array('KSM_LAPP_Login_Admin_Protector', 'uninstall'));
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
     * Get user's real IP address - IMPROVED to handle mobile/proxy scenarios
     */
    private function get_user_ip() {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 
                        'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 
                        'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    
                    // Only validate IP format - allow private ranges for mobile/proxy scenarios
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
                        // Log the IP source for debugging (only if WP_DEBUG is enabled)
                        if (defined('WP_DEBUG') && WP_DEBUG) {
                            error_log("KSM_LAPP: Using IP {$ip} from {$key}");
                        }
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
     * Get country code from local IP ranges (Nigeria focus) - ENHANCED ranges
     */
    private function get_country_from_local_ranges($ip) {
        // Nigeria IP ranges - EXPANDED and organized by provider
        $nigeria_ranges = array(
            // MTN Nigeria
            '41.58.0.0/16', '41.75.0.0/16', '105.112.0.0/12',
            
            // Airtel Nigeria  
            '41.76.0.0/16', '41.77.0.0/16', '41.78.0.0/16', '41.79.0.0/16',
            
            // Glo Nigeria
            '154.113.0.0/16', '41.203.0.0/16', '41.184.0.0/16',
            
            // 9mobile (Etisalat Nigeria)
            '41.190.0.0/16', '196.6.0.0/16',
            
            // Major ISP blocks
            '196.1.0.0/16', '196.13.0.0/16', '196.27.0.0/16', '196.28.0.0/16',
            '196.29.0.0/16', '196.46.0.0/16', '196.49.0.0/16',
            
            // Internet Exchange and backbone providers
            '196.200.0.0/13', // 196.200.0.0 to 196.207.255.255
            '196.208.0.0/12', // 196.208.0.0 to 196.223.255.255
            
            // NITEL and other providers
            '41.210.0.0/15', // 41.210.0.0 to 41.211.255.255
            '41.212.0.0/14', // 41.212.0.0 to 41.215.255.255
            '41.216.0.0/13', // 41.216.0.0 to 41.223.255.255
            
            // Additional Nigerian blocks
            '165.73.0.0/16', '165.88.0.0/16', '165.255.0.0/16',
            '197.149.0.0/16', '197.210.0.0/16', '197.242.0.0/16',
            '197.253.0.0/16', '197.255.0.0/16',
            
            // Mobile carrier additional ranges
            '169.239.0.0/16', // Additional MTN
            '41.139.0.0/16',  // Additional Airtel
            '105.235.0.0/16', // Mobile carriers
            '102.89.0.0/16',  // Internet providers
            '102.91.0.0/16',  // Internet providers
        );
        
        foreach ($nigeria_ranges as $range) {
            if ($this->ip_in_range($ip, $range)) {
                return 'NG';
            }
        }
        
        return 'UNKNOWN';
    }
    
    /**
     * Fetch country code from external API (fallback only) - IMPROVED error handling
     */
    private function fetch_country_code_external($ip) {
        // Only use external API if specifically enabled
        if (!get_option('ksm_lapp_use_external_api', false)) {
            return 'UNKNOWN';
        }
        
        // Skip private/local IPs for external API calls
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return 'UNKNOWN';
        }
        
        $url = "http://ipinfo.io/{$ip}/country";
        
        $response = wp_remote_get($url, array(
            'timeout' => 3,
            'user-agent' => 'KSM-LAPP-Plugin/' . KSM_LAPP_VERSION,
            'headers' => array('Accept' => 'text/plain')
        ));
        
        if (is_wp_error($response)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('KSM_LAPP: External API error: ' . $response->get_error_message());
            }
            return 'UNKNOWN';
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('KSM_LAPP: External API returned code: ' . $response_code);
            }
            return 'UNKNOWN';
        }
        
        $body = wp_remote_retrieve_body($response);
        $country_code = trim($body);
        
        // Validate country code format (should be 2 letters)
        if (strlen($country_code) === 2 && ctype_alpha($country_code)) {
            return strtoupper($country_code);
        }
        
        return 'UNKNOWN';
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
        
        if ($range_decimal === false || $ip_decimal === false) {
            return false;
        }
        
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
            'attempt_time' => current_time('mysql'),
            'server_vars' => array(
                'HTTP_X_FORWARDED_FOR' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '',
                'HTTP_CLIENT_IP' => $_SERVER['HTTP_CLIENT_IP'] ?? '',
                'REMOTE_ADDR' => $_SERVER['REMOTE_ADDR'] ?? ''
            )
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
    <meta name="robots" content="noindex,nofollow">
    <style>
        body { font-family: Arial, sans-serif; text-align: center; margin-top: 50px; background: #f1f1f1; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 40px; border-radius: 5px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .error { color: #d32f2f; font-size: 24px; margin-bottom: 20px; }
        p { color: #666; line-height: 1.6; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="error">Access Denied</h1>
        <p>You do not have permission to access this page.</p>
        <p>This site restricts access to login and admin areas based on geographic location for security purposes.</p>
        <p>If you believe this is an error, please contact the site administrator.</p>
    </div>
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
     * Plugin deactivation - IMPROVED cleanup
     */
    public function deactivate() {
        // Clean up transients
        global $wpdb;
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $this->cache_key_prefix . '%'));
        
        // Clear scheduled cleanup
        wp_clear_scheduled_hook('ksm_lapp_cleanup_old_logs');
    }
    
    /**
     * Plugin uninstall - COMPLETE cleanup
     */
    public static function uninstall() {
        // Remove all plugin options
        delete_option('ksm_lapp_cache_duration');
        delete_option('ksm_lapp_cleanup_days');
        delete_option('ksm_lapp_use_external_api');
        delete_option('ksm_lapp_blocked_attempts');
        
        // Clean up any remaining transients
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'ksm_lapp_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_ksm_lapp_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_ksm_lapp_%'");
        
        // Clear any scheduled events
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
     * Admin page HTML - ENHANCED with debugging info
     */
    public function admin_page() {
        // Get current user's IP for testing
        $current_ip = $this->get_user_ip();
        $current_country = $this->get_country_code($current_ip);
        ?>
        <div class="wrap">
            <h1>Login/Admin Page Protector Settings <small>v<?php echo KSM_LAPP_VERSION; ?></small></h1>
            
            <!-- Current Status -->
            <div class="notice notice-info">
                <p><strong>Your Current Status:</strong><br>
                IP Address: <code><?php echo esc_html($current_ip); ?></code><br>
                Detected Country: <code><?php echo esc_html($current_country); ?></code><br>
                Access Status: <?php echo ($current_country === 'NG' || $this->is_jetpack_ip($current_ip)) ? 
                    '<span style="color:green;">✓ ALLOWED</span>' : 
                    '<span style="color:red;">✗ WOULD BE BLOCKED</span>'; ?>
                </p>
            </div>
            
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
            
            <!-- Clear Cache Button -->
            <form method="post" style="margin-top: 20px;">
                <p>
                    <input type="submit" name="clear_cache" class="button" value="Clear IP Cache" />
                    <span class="description">Clear all cached IP geolocation data</span>
                </p>
            </form>
            
            <?php
            // Handle cache clearing
            if (isset($_POST['clear_cache'])) {
                global $wpdb;
                $deleted = $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", '_transient_' . $this->cache_key_prefix . 'country_%'));
                $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", '_transient_timeout_' . $this->cache_key_prefix . 'country_%'));
                echo '<div class="notice notice-success"><p>Cache cleared! Removed ' . intval($deleted) . ' cached entries.</p></div>';
            }
            ?>
            
            <h2>Recent Blocked Attempts</h2>
            <?php $this->display_blocked_attempts(); ?>
        </div>
        <?php
    }
    
    /**
     * Display blocked attempts table - ENHANCED with more details
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
        echo '<thead><tr><th>IP Address</th><th>Country</th><th>User Agent</th><th>Request URI</th><th>Proxy Info</th><th>Time</th></tr></thead>';
        echo '<tbody>';
        
        foreach ($recent_attempts as $attempt) {
            echo '<tr>';
            echo '<td>' . esc_html($attempt['ip_address']) . '</td>';
            echo '<td>' . esc_html($attempt['country_code']) . '</td>';
            echo '<td>' . esc_html(substr($attempt['user_agent'], 0, 50)) . '...</td>';
            echo '<td>' . esc_html($attempt['request_uri']) . '</td>';
            
            // Show proxy information if available
            $proxy_info = '';
            if (isset($attempt['server_vars'])) {
                $vars = $attempt['server_vars'];
                if (!empty($vars['HTTP_X_FORWARDED_FOR'])) {
                    $proxy_info .= 'X-Forwarded: ' . esc_html($vars['HTTP_X_FORWARDED_FOR']) . '<br>';
                }
                if (!empty($vars['HTTP_CLIENT_IP'])) {
                    $proxy_info .= 'Client-IP: ' . esc_html($vars['HTTP_CLIENT_IP']) . '<br>';
                }
                $proxy_info .= 'Remote: ' . esc_html($vars['REMOTE_ADDR']);
            }
            echo '<td>' . $proxy_info . '</td>';
            
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
