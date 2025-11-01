<?php
/**
 * Plugin Name: Login/Admin Page Protector
 * Description: Blocks access to WordPress login and admin pages with exceptions for Jetpack/WordPress.com and Nigeria traffic. Includes IP tracking and caching.
 * Version: 1.2.1
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
define('KSM_LAPP_VERSION', '1.2.1');
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
    private $whitelist_option_name = 'ksm_lapp_ip_whitelist';
    private $bypass_key_option_name = 'ksm_lapp_bypass_key';
    
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
        
        // Security: Use separate file for uninstall
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Add emergency bypass check
        add_action('init', array($this, 'check_emergency_bypass'), 1);
    }
    
    /**
     * Emergency bypass for locked-out admins
     * Usage: Add ?emergency_bypass=YOUR_KEY to any admin URL
     */
    public function check_emergency_bypass() {
        if (isset($_GET['emergency_bypass'])) {
            $provided_key = sanitize_text_field($_GET['emergency_bypass']);
            $stored_key = get_option($this->bypass_key_option_name);
            
            if (!empty($stored_key) && hash_equals($stored_key, $provided_key)) {
                // Set a temporary session to bypass protection
                if (!session_id()) {
                    session_start();
                }
                $_SESSION['ksm_lapp_bypass'] = time() + 1800; // 30 minutes
                
                // Log the bypass usage
                $this->log_bypass_usage();
                
                // Redirect to remove the bypass key from URL
                $clean_url = remove_query_arg('emergency_bypass');
                wp_redirect($clean_url);
                exit;
            }
        }
    }
    
    /**
     * Check if emergency bypass is active
     */
    private function is_emergency_bypass_active() {
        if (!session_id()) {
            session_start();
        }
        
        return isset($_SESSION['ksm_lapp_bypass']) && 
               $_SESSION['ksm_lapp_bypass'] > time();
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
        
        // Add AJAX handlers for whitelist management
        add_action('wp_ajax_ksm_lapp_add_ip', array($this, 'ajax_add_ip_to_whitelist'));
        add_action('wp_ajax_ksm_lapp_remove_ip', array($this, 'ajax_remove_ip_from_whitelist'));
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
     * Main access control logic - ENHANCED with more bypass options
     */
    private function check_access() {
        // Check emergency bypass first
        if ($this->is_emergency_bypass_active()) {
            return;
        }
        
        $user_ip = $this->get_user_ip();
        
        // Check IP whitelist first
        if ($this->is_ip_whitelisted($user_ip)) {
            return;
        }
        
        $country_code = $this->get_country_code($user_ip);
        
        // Allow Nigeria traffic
        if ($country_code === 'NG') {
            return;
        }
        
        // Allow Jetpack/WordPress.com IPs
        if ($this->is_jetpack_ip($user_ip)) {
            return;
        }
        
        // Allow logged-in users with proper capabilities (but only for admin, not login)
        if (is_user_logged_in() && current_user_can('manage_options') && is_admin()) {
            return;
        }
        
        // Allow WP-CLI access
        if (defined('WP_CLI') && WP_CLI) {
            return;
        }
        
        // Allow REST API authentication calls (for mobile apps, etc.)
        if ($this->is_rest_api_auth()) {
            return;
        }
        
        // Block access and log attempt
        $this->log_blocked_attempt($user_ip, $country_code);
        $this->block_access();
    }
    
    /**
     * Check if this is a REST API authentication request
     */
    private function is_rest_api_auth() {
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        return (strpos($request_uri, '/wp-json/') !== false && 
                (strpos($request_uri, '/wp/v2/users/me') !== false ||
                 strpos($request_uri, '/jwt-auth/') !== false));
    }
    
    /**
     * Check if IP is in whitelist
     */
    private function is_ip_whitelisted($ip) {
        $whitelist = get_option($this->whitelist_option_name, array());
        
        foreach ($whitelist as $whitelisted_entry) {
            $whitelisted_ip = $whitelisted_entry['ip'];
            
            // Support both single IPs and CIDR ranges
            if (strpos($whitelisted_ip, '/') !== false) {
                if ($this->ip_in_range($ip, $whitelisted_ip)) {
                    return true;
                }
            } else {
                if ($ip === $whitelisted_ip) {
                    return true;
                }
            }
        }
        
        return false;
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

                    // Validate and sanitize IP
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
                        // Additional security: Check for suspicious patterns
                        if (!$this->is_suspicious_ip($ip)) {
                            return $ip;
                        }
                    }
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
    
    /**
     * Check for suspicious IP patterns
     */
    private function is_suspicious_ip($ip) {
        // Check for obviously fake IPs or common attack patterns
        $suspicious_patterns = array(
            '0.0.0.0',
            '255.255.255.255',
            '127.0.0.1',
            '::1'
        );

        return in_array($ip, $suspicious_patterns);
    }

    /**
     * Get country code from IP with enhanced caching
     */
    private function get_country_code($ip) {
        $cache_key = $this->cache_key_prefix . 'country_' . md5($ip);
        $country_code = get_transient($cache_key);
        
        if ($country_code === false) {
            // Try local IP ranges first
            $country_code = $this->get_country_from_local_ranges($ip);
            
            // If not found locally, try external API as fallback
            if ($country_code === 'UNKNOWN') {
                $country_code = $this->fetch_country_code_external($ip);
            }
            
            // Cache with shorter duration for unknown IPs to retry sooner
            $cache_duration = ($country_code === 'UNKNOWN') ? 300 : $this->cache_duration;
            set_transient($cache_key, $country_code, $cache_duration);
        }
        
        return $country_code;
    }
    
    /**
     * Enhanced Nigeria IP ranges with latest updates
     */
    private function get_country_from_local_ranges($ip) {
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
        
        foreach ($nigeria_ranges as $range) {
            if ($this->ip_in_range($ip, $range)) {
                return 'NG';
            }
        }
        
        return 'UNKNOWN';
    }
    
    /**
     * Enhanced external API with multiple fallbacks
     */
    private function fetch_country_code_external($ip) {
        if (!get_option('ksm_lapp_use_external_api', false)) {
            return 'UNKNOWN';
        }
        
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return 'UNKNOWN';
        }
        
        // Try multiple API endpoints for redundancy
        $apis = array(
            "http://ipinfo.io/{$ip}/country",
            "http://ip-api.com/line/{$ip}?fields=countryCode",
        );
        
        foreach ($apis as $url) {
            $response = wp_remote_get($url, array(
                'timeout' => 2,
                'user-agent' => 'KSM-LAPP-Plugin/' . KSM_LAPP_VERSION,
                'headers' => array('Accept' => 'text/plain')
            ));
            
            if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                $body = trim(wp_remote_retrieve_body($response));
                
                // Handle different API response formats
                if (strpos($url, 'ip-api.com') !== false) {
                    $parts = explode("\n", $body);
                    $country_code = isset($parts[0]) ? trim($parts[0]) : '';
                } else {
                    $country_code = $body;
                }
                
                if (strlen($country_code) === 2 && ctype_alpha($country_code)) {
                    return strtoupper($country_code);
                }
            }
        }
        
        return 'UNKNOWN';
    }
    
    /**
     * Log blocked attempt with enhanced details
     */
    private function log_blocked_attempt($ip, $country_code) {
        $log_entry = array(
            'ip_address' => $ip,
            'country_code' => $country_code,
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
            'attempt_time' => current_time('mysql'),
            'server_vars' => array(
                'HTTP_X_FORWARDED_FOR' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '',
                'HTTP_CLIENT_IP' => $_SERVER['HTTP_CLIENT_IP'] ?? '',
                'REMOTE_ADDR' => $_SERVER['REMOTE_ADDR'] ?? '',
                'HTTP_X_REAL_IP' => $_SERVER['HTTP_X_REAL_IP'] ?? ''
            ),
            'is_login_attempt' => (strpos($_SERVER['REQUEST_URI'] ?? '', 'wp-login.php') !== false),
            'referer' => $_SERVER['HTTP_REFERER'] ?? ''
        );
        
        $existing_logs = get_option($this->log_option_name, array());
        array_unshift($existing_logs, $log_entry);
        
        // Keep only last 1000 entries
        $existing_logs = array_slice($existing_logs, 0, 1000);
        update_option($this->log_option_name, $existing_logs);
    }
    
    /**
     * Log bypass usage for security monitoring
     */
    private function log_bypass_usage() {
        $bypass_log = get_option('ksm_lapp_bypass_log', array());
        
        $log_entry = array(
            'ip_address' => $this->get_user_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'bypass_time' => current_time('mysql'),
            'request_uri' => $_SERVER['REQUEST_URI'] ?? ''
        );
        
        array_unshift($bypass_log, $log_entry);
        $bypass_log = array_slice($bypass_log, 0, 100); // Keep last 100 entries
        
        update_option('ksm_lapp_bypass_log', $bypass_log);
    }
    
    /**
     * Enhanced blocking page with more options
     */
    private function block_access() {
        status_header(403);
        
        $blocked_page = '<!DOCTYPE html>
<html>
<head>
    <title>Access Denied</title>
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
        <h1 class="error">🚫 Access Denied</h1>
        <p><strong>You do not have permission to access this page.</strong></p>
        
        <div class="info">
            <p>This website restricts access to administrative areas based on geographic location for enhanced security.</p>
            <p><strong>Your IP:</strong> <code>' . esc_html($this->get_user_ip()) . '</code></p>
        </div>
        
        <p><strong>If you are the site administrator and need emergency access:</strong></p>
        <ol style="text-align: left; max-width: 400px; margin: 20px auto;">
            <li>Contact your hosting provider</li>
            <li>Use FTP/cPanel to temporarily deactivate the plugin</li>
            <li>Add your IP to the whitelist via database</li>
            <li>Use the emergency bypass feature if configured</li>
        </ol>
        
        <div class="contact">
            <p><strong>Need help?</strong> Contact the site administrator with the following information:</p>
            <p><strong>Time:</strong> ' . current_time('Y-m-d H:i:s T') . '<br>
            <strong>Your IP:</strong> ' . esc_html($this->get_user_ip()) . '</p>
        </div>
    </div>
</body>
</html>';
        
        echo $blocked_page;
        exit;
    }
    
    /**
     * AJAX handler to add IP to whitelist
     */
    public function ajax_add_ip_to_whitelist() {
        if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['nonce'], 'ksm_lapp_whitelist')) {
            wp_die('Unauthorized');
        }
        
        $ip = sanitize_text_field($_POST['ip']);
        $description = sanitize_text_field($_POST['description']);
        
        if (!filter_var($ip, FILTER_VALIDATE_IP) && !$this->is_valid_cidr($ip)) {
            wp_send_json_error('Invalid IP address or CIDR range');
        }
        
        $whitelist = get_option($this->whitelist_option_name, array());
        $whitelist[] = array(
            'ip' => $ip,
            'description' => $description,
            'added_by' => wp_get_current_user()->user_login,
            'added_time' => current_time('mysql')
        );
        
        update_option($this->whitelist_option_name, $whitelist);
        wp_send_json_success('IP added to whitelist');
    }
    
    /**
     * Validate CIDR notation
     */
    private function is_valid_cidr($cidr) {
        if (strpos($cidr, '/') === false) {
            return false;
        }
        
        list($ip, $mask) = explode('/', $cidr);
        return filter_var($ip, FILTER_VALIDATE_IP) && is_numeric($mask) && $mask >= 0 && $mask <= 32;
    }
    
    // [Include all other existing methods: ip_in_range, is_jetpack_ip, clear_user_cache, activate, deactivate, etc.]
    // [For brevity, I'm not repeating all unchanged methods, but they should remain in the full implementation]
    
    /**
     * Enhanced admin page with whitelist management and security features
     */
    public function admin_page() {
        $current_ip = $this->get_user_ip();
        $current_country = $this->get_country_code($current_ip);
        $bypass_key = get_option($this->bypass_key_option_name);
        
        ?>
        <div class="wrap">
            <h1>Login/Admin Page Protector <small>v<?php echo KSM_LAPP_VERSION; ?></small></h1>
            
            <!-- Security Status Dashboard -->
            <div class="notice notice-info">
                <h3>🛡️ Security Status</h3>
                <p><strong>Your Current Status:</strong><br>
                IP Address: <code><?php echo esc_html($current_ip); ?></code><br>
                Detected Country: <code><?php echo esc_html($current_country); ?></code><br>
                Access Status: <?php echo ($current_country === 'NG' || $this->is_jetpack_ip($current_ip) || $this->is_ip_whitelisted($current_ip)) ? 
                    '<span style="color:green;">✅ ALLOWED</span>' : 
                    '<span style="color:red;">❌ WOULD BE BLOCKED</span>'; ?>
                </p>
                
                <?php if (empty($bypass_key)): ?>
                <div class="notice notice-warning inline">
                    <p><strong>⚠️ No Emergency Bypass Key Set!</strong> 
                    <a href="#emergency-bypass">Set one up now</a> to avoid being locked out.</p>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Emergency Bypass Section -->
            <div id="emergency-bypass" class="card" style="max-width: none; margin-top: 20px;">
                <h2 class="title">🚨 Emergency Bypass Configuration</h2>
                <div class="inside">
                    <?php if (empty($bypass_key)): ?>
                        <form method="post">
                            <?php wp_nonce_field('ksm_lapp_generate_bypass'); ?>
                            <p>Generate an emergency bypass key to regain access if you get locked out:</p>
                            <p>
                                <input type="submit" name="generate_bypass_key" class="button button-primary" 
                                       value="Generate Emergency Bypass Key" />
                            </p>
                        </form>
                    <?php else: ?>
                        <p><strong>Emergency Bypass URL:</strong></p>
                        <code style="background: #fffbf0; padding: 10px; display: block; margin: 10px 0; word-break: break-all;">
                            <?php echo home_url('/wp-admin/?emergency_bypass=' . esc_html($bypass_key)); ?>
                        </code>
                        <p class="description">
                            ⚠️ <strong>Keep this URL secure!</strong> Anyone with this URL can bypass the protection for 30 minutes.
                            Save it in a secure password manager.
                        </p>
                        <form method="post" style="margin-top: 15px;">
                            <?php wp_nonce_field('ksm_lapp_revoke_bypass'); ?>
                            <input type="submit" name="revoke_bypass_key" class="button" 
                                   value="Revoke Bypass Key" 
                                   onclick="return confirm('Are you sure? This will disable emergency access!');" />
                        </form>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- IP Whitelist Management -->
            <div class="card" style="max-width: none; margin-top: 20px;">
                <h2 class="title">📝 IP Whitelist Management</h2>
                <div class="inside">
                    <form id="add-ip-form">
                        <?php wp_nonce_field('ksm_lapp_whitelist', 'whitelist_nonce'); ?>
                        <table class="form-table">
                            <tr>
                                <th><label for="whitelist_ip">IP Address/CIDR Range</label></th>
                                <td>
                                    <input type="text" id="whitelist_ip" name="ip" placeholder="192.168.1.100 or 192.168.1.0/24" 
                                           style="width: 300px;" />
                                    <button type="button" onclick="document.getElementById('whitelist_ip').value='<?php echo esc_js($current_ip); ?>'" 
                                            class="button">Use My IP</button>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="whitelist_desc">Description</label></th>
                                <td>
                                    <input type="text" id="whitelist_desc" name="description" placeholder="Office IP, VPN, etc." 
                                           style="width: 300px;" />
                                </td>
                            </tr>
                        </table>
                        <p>
                            <input type="submit" class="button button-primary" value="Add to Whitelist" />
                        </p>
                    </form>
                    
                    <h3>Current Whitelist</h3>
                    <div id="whitelist-table">
                        <?php $this->display_whitelist_table(); ?>
                    </div>
                </div>
            </div>
            
            <!-- Settings Form -->
            <form method="post" action="options.php" style="margin-top: 20px;">
                <?php settings_fields('ksm_lapp_settings'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">Cache Duration (seconds)</th>
                        <td>
                            <input type="number" name="ksm_lapp_cache_duration" 
                                   value="<?php echo esc_attr(get_option('ksm_lapp_cache_duration', 3600)); ?>" 
                                   min="300" max="86400" />
                            <p class="description">How long to cache IP geolocation data (300-86400 seconds)</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Use External API</th>
                        <td>
                            <label>
                                <input type="checkbox" name="ksm_lapp_use_external_api" value="1" 
                                       <?php checked(get_option('ksm_lapp_use_external_api', false)); ?> />
                                Enable external IP geolocation API for non-Nigeria IPs
                            </label>
                            <p class="description">⚠️ This may impact privacy and performance. Only enable if needed.</p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
            
            <!-- Recent Blocked Attempts -->
            <h2>🚫 Recent Blocked Attempts</h2>
            <?php $this->display_blocked_attempts(); ?>
            
            <script>
            jQuery(document).ready(function($) {
                $('#add-ip-form').on('submit', function(e) {
                    e.preventDefault();
                    
                    $.post(ajaxurl, {
                        action: 'ksm_lapp_add_ip',
                        ip: $('#whitelist_ip').val(),
                        description: $('#whitelist_desc').val(),
                        nonce: $('#whitelist_nonce').val()
                    }, function(response) {
                        if (response.success) {
                            alert('IP added to whitelist!');
                            location.reload();
                        } else {
                            alert('Error: ' + response.data);
                        }
                    });
                });
            });
            </script>
        </div>
        <?php
        
        // Handle bypass key generation/revocation
        if (isset($_POST['generate_bypass_key']) && wp_verify_nonce($_POST['_wpnonce'], 'ksm_lapp_generate_bypass')) {
            $bypass_key = wp_generate_password(32, false);
            update_option($this->bypass_key_option_name, $bypass_key);
            echo '<div class="notice notice-success"><p>Emergency bypass key generated! Please save the URL shown above.</p></div>';
        }
        
        if (isset($_POST['revoke_bypass_key']) && wp_verify_nonce($_POST['_wpnonce'], 'ksm_lapp_revoke_bypass')) {
            delete_option($this->bypass_key_option_name);
            echo '<div class="notice notice-success"><p>Emergency bypass key revoked.</p></div>';
        }
    }
    
    /**
     * Display whitelist table
     */
    private function display_whitelist_table() {
        $whitelist = get_option($this->whitelist_option_name, array());
        
        if (empty($whitelist)) {
            echo '<p>No IPs in whitelist.</p>';
            return;
        }
        
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>IP/Range</th><th>Description</th><th>Added By</th><th>Added Date</th><th>Actions</th></tr></thead>';
        echo '<tbody>';
        
        foreach ($whitelist as $index => $entry) {
            echo '<tr>';
            echo '<td><code>' . esc_html($entry['ip']) . '</code></td>';
            echo '<td>' . esc_html($entry['description']) . '</td>';
            echo '<td>' . esc_html($entry['added_by'] ?? 'Unknown') . '</td>';
            echo '<td>' . esc_html($entry['added_time'] ?? 'Unknown') . '</td>';
            echo '<td><button class="button button-small" onclick="removeFromWhitelist(' . $index . ')">Remove</button></td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
    }
    
    /**
     * Enhanced blocked attempts display with filtering
     */
    private function display_blocked_attempts() {
        $attempts = get_option($this->log_option_name, array());
        
        if (empty($attempts)) {
            echo '<p>No blocked attempts recorded yet.</p>';
            return;
        }
        
        // Show statistics
        $total_attempts = count($attempts);
        $last_24h = array_filter($attempts, function($attempt) {
            return strtotime($attempt['attempt_time']) > (time() - 86400);
        });
        $unique_ips = array_unique(array_column($attempts, 'ip_address'));
        
        echo '<div class="notice notice-info inline">';
        echo '<p><strong>Statistics:</strong> ';
        echo 'Total: ' . $total_attempts . ' | ';
        echo 'Last 24h: ' . count($last_24h) . ' | ';
        echo 'Unique IPs: ' . count($unique_ips);
        echo '</p></div>';
        
        // Show recent attempts (last 50)
        $recent_attempts = array_slice($attempts, 0, 50);
        
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>IP Address</th><th>Country</th><th>Type</th><th>User Agent</th><th>Time</th><th>Actions</th></tr></thead>';
        echo '<tbody>';
        
        foreach ($recent_attempts as $attempt) {
            echo '<tr>';
            echo '<td><code>' . esc_html($attempt['ip_address']) . '</code></td>';
            echo '<td>' . esc_html($attempt['country_code']) . '</td>';
            
            $type = isset($attempt['is_login_attempt']) && $attempt['is_login_attempt'] ? 
                    '<span style="color: red;">Login</span>' : 
                    '<span style="color: orange;">Admin</span>';
            echo '<td>' . $type . '</td>';
            
            echo '<td>' . esc_html(substr($attempt['user_agent'], 0, 60)) . '...</td>';
            echo '<td>' . esc_html($attempt['attempt_time']) . '</td>';
            echo '<td>';
            echo '<button class="button button-small" onclick="addToWhitelist(\'' . 
                 esc_js($attempt['ip_address']) . '\', \'Blocked IP - added manually\')">Whitelist</button>';
            echo '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
        echo '<p class="description">Showing last 50 of ' . $total_attempts . ' total attempts.</p>';
    }
    
    /**
     * All other existing methods remain unchanged
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
    
    private function is_jetpack_ip($ip) {
        foreach ($this->jetpack_ips as $range) {
            if ($this->ip_in_range($ip, $range)) {
                return true;
            }
        }
        return false;
    }
    
    public function clear_user_cache($user_login, $user) {
        $user_ip = $this->get_user_ip();
        $cache_key = $this->cache_key_prefix . 'country_' . md5($user_ip);
        delete_transient($cache_key);
    }
    
    public function activate() {
        $this->set_default_options();
        
        if (!wp_next_scheduled('ksm_lapp_cleanup_old_logs')) {
            wp_schedule_event(time(), 'daily', 'ksm_lapp_cleanup_old_logs');
        }
    }
    
    public function deactivate() {
        global $wpdb;
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $this->cache_key_prefix . '%'));
        wp_clear_scheduled_hook('ksm_lapp_cleanup_old_logs');
    }
    
    private function set_default_options() {
        add_option('ksm_lapp_cache_duration', 3600);
        add_option('ksm_lapp_cleanup_days', 30);
        add_option('ksm_lapp_use_external_api', false);
        add_option($this->log_option_name, array());
        add_option($this->whitelist_option_name, array());
    }
    
    public function add_admin_menu() {
        add_options_page(
            'Login/Admin Page Protector',
            'Login Protector',
            'manage_options',
            'ksm-lapp-settings',
            array($this, 'admin_page')
        );
    }
    
    public function admin_init() {
        register_setting('ksm_lapp_settings', 'ksm_lapp_cache_duration');
        register_setting('ksm_lapp_settings', 'ksm_lapp_cleanup_days');
        register_setting('ksm_lapp_settings', 'ksm_lapp_use_external_api');
        
        add_action('ksm_lapp_cleanup_old_logs', array($this, 'cleanup_old_logs'));
    }
    
    public function cleanup_old_logs() {
        $cleanup_days = get_option('ksm_lapp_cleanup_days', 30);
        $cutoff_timestamp = strtotime("-{$cleanup_days} days");
        
        $attempts = get_option($this->log_option_name, array());
        
        if (empty($attempts)) {
            return;
        }
        
        $cleaned_attempts = array_filter($attempts, function($attempt) use ($cutoff_timestamp) {
            $attempt_timestamp = strtotime($attempt['attempt_time']);
            return $attempt_timestamp > $cutoff_timestamp;
        });
        
        $cleaned_attempts = array_values($cleaned_attempts);
        update_option($this->log_option_name, $cleaned_attempts);
    }
}

// Initialize the plugin
new KSM_LAPP_Login_Admin_Protector();
