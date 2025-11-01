<?php
/**
 * Plugin Name: Securetor
 * Plugin URI: https://kraftysprouts.com/securetor
 * Description: Comprehensive WordPress security suite with geographic access control and advanced anti-spam protection. Built on proven technology from Login/Admin Page Protector and three generations of anti-spam plugins.
 * Version: 2.0.1
 * Author: Krafty Sprouts Media, LLC
 * Author URI: https://kraftysprouts.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: securetor
 * Domain Path: /languages
 * Requires at least: 6.6
 * Requires PHP: 8.2
 *
 * @package    Securetor
 * @author     Krafty Sprouts Media, LLC
 * @since      2.0.0
 * @license    GPL-2.0+
 *
 * Credits:
 * - Access Control Module: Evolved from Login/Admin Page Protector (Krafty Sprouts Media, LLC)
 * - Anti-Spam Module: Merged from three generations of anti-spam plugins:
 *   * Anti-spam v5.5 by webvitaly (original)
 *   * Anti-spam Reloaded v6.5 by kudlav (community fork)
 *   * Fortify v1.0 by webvitaly (creator's return)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Current plugin version.
 *
 * @since 2.0.0
 */
define( 'SECURETOR_VERSION', '2.0.1' );

/**
 * Plugin directory path.
 *
 * @since 2.0.0
 */
define( 'SECURETOR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

/**
 * Plugin directory URL.
 *
 * @since 2.0.0
 */
define( 'SECURETOR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Plugin basename.
 *
 * @since 2.0.0
 */
define( 'SECURETOR_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Minimum WordPress version required.
 *
 * @since 2.0.0
 */
define( 'SECURETOR_MIN_WP_VERSION', '6.6' );

/**
 * Minimum PHP version required.
 *
 * @since 2.0.0
 */
define( 'SECURETOR_MIN_PHP_VERSION', '8.2' );

/**
 * Check WordPress and PHP version compatibility.
 *
 * @since 2.0.0
 * @return void
 */
function securetor_check_compatibility() {
	global $wp_version;

	// Check WordPress version
	if ( version_compare( $wp_version, SECURETOR_MIN_WP_VERSION, '<' ) ) {
		add_action( 'admin_notices', 'securetor_wp_version_notice' );
		return false;
	}

	// Check PHP version
	if ( version_compare( PHP_VERSION, SECURETOR_MIN_PHP_VERSION, '<' ) ) {
		add_action( 'admin_notices', 'securetor_php_version_notice' );
		return false;
	}

	return true;
}

/**
 * Display WordPress version compatibility notice.
 *
 * @since 2.0.0
 * @return void
 */
function securetor_wp_version_notice() {
	printf(
		'<div class="notice notice-error"><p><strong>%s:</strong> %s</p></div>',
		esc_html__( 'Securetor Error', 'securetor' ),
		sprintf(
			/* translators: 1: Current WordPress version, 2: Required WordPress version */
			esc_html__( 'Your WordPress version (%1$s) is too old. Securetor requires WordPress %2$s or higher. Please update WordPress to use Securetor.', 'securetor' ),
			esc_html( $GLOBALS['wp_version'] ),
			esc_html( SECURETOR_MIN_WP_VERSION )
		)
	);
}

/**
 * Display PHP version compatibility notice.
 *
 * @since 2.0.0
 * @return void
 */
function securetor_php_version_notice() {
	printf(
		'<div class="notice notice-error"><p><strong>%s:</strong> %s</p></div>',
		esc_html__( 'Securetor Error', 'securetor' ),
		sprintf(
			/* translators: 1: Current PHP version, 2: Required PHP version */
			esc_html__( 'Your PHP version (%1$s) is too old. Securetor requires PHP %2$s or higher. Please contact your hosting provider to upgrade PHP.', 'securetor' ),
			esc_html( PHP_VERSION ),
			esc_html( SECURETOR_MIN_PHP_VERSION )
		)
	);
}

// Check compatibility before proceeding
if ( ! securetor_check_compatibility() ) {
	return;
}

/**
 * Autoloader for Securetor classes.
 *
 * @since 2.0.0
 *
 * @param string $class_name The fully-qualified class name.
 * @return void
 */
function securetor_autoloader( $class_name ) {
	// Project namespace prefix
	$prefix = 'Securetor\\';

	// Base directory for the namespace prefix
	$base_dir = SECURETOR_PLUGIN_DIR . 'includes/';

	// Does the class use the namespace prefix?
	$len = strlen( $prefix );
	if ( strncmp( $prefix, $class_name, $len ) !== 0 ) {
		// No, move to the next registered autoloader
		return;
	}

	// Get the relative class name
	$relative_class = substr( $class_name, $len );

	// Replace namespace separators with directory separators
	// Replace underscores with hyphens and convert to lowercase
	$file = $base_dir . str_replace( '\\', '/', $relative_class );
	$file = strtolower( str_replace( '_', '-', $file ) );
	$file = 'class-' . $file . '.php';

	// If the file exists, require it
	if ( file_exists( $file ) ) {
		require_once $file;
	}
}

// Register autoloader
spl_autoload_register( 'securetor_autoloader' );

/**
 * Main Securetor class.
 *
 * @since 2.0.0
 */
final class Securetor {

	/**
	 * The single instance of the class.
	 *
	 * @since 2.0.0
	 * @var Securetor|null
	 */
	private static $instance = null;

	/**
	 * Module loader instance.
	 *
	 * @since 2.0.0
	 * @var Securetor\Core\Loader
	 */
	private $loader;

	/**
	 * Access Control module instance.
	 *
	 * @since 2.0.0
	 * @var Securetor\Modules\Access_Control
	 */
	private $access_control;

	/**
	 * Anti-Spam module instance.
	 *
	 * @since 2.0.0
	 * @var Securetor\Modules\Anti_Spam
	 */
	private $anti_spam;

	/**
	 * Main Securetor instance.
	 *
	 * Ensures only one instance of Securetor is loaded or can be loaded.
	 *
	 * @since 2.0.0
	 * @static
	 *
	 * @return Securetor Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Securetor constructor.
	 *
	 * @since 2.0.0
	 */
	private function __construct() {
		$this->define_constants();
		$this->load_dependencies();
		$this->set_locale();
		$this->init_hooks();
		$this->load_modules();
	}

	/**
	 * Define additional plugin constants.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	private function define_constants() {
		// Additional constants can be defined here as needed
	}

	/**
	 * Load required dependencies.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	private function load_dependencies() {
		// Core classes
		require_once SECURETOR_PLUGIN_DIR . 'includes/core/class-loader.php';
		require_once SECURETOR_PLUGIN_DIR . 'includes/core/class-i18n.php';
		require_once SECURETOR_PLUGIN_DIR . 'includes/core/class-activator.php';
		require_once SECURETOR_PLUGIN_DIR . 'includes/core/class-deactivator.php';

		// Admin
		if ( is_admin() ) {
			require_once SECURETOR_PLUGIN_DIR . 'includes/admin/class-admin.php';
			require_once SECURETOR_PLUGIN_DIR . 'includes/admin/class-settings.php';
		}

		// Modules
		require_once SECURETOR_PLUGIN_DIR . 'includes/modules/access-control/class-access-control.php';
		require_once SECURETOR_PLUGIN_DIR . 'includes/modules/anti-spam/class-anti-spam.php';

		// Initialize loader
		$this->loader = new Securetor\Core\Loader();
	}

	/**
	 * Set the plugin locale for internationalization.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	private function set_locale() {
		$plugin_i18n = new Securetor\Core\I18n();
		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
	}

	/**
	 * Register all hooks.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	private function init_hooks() {
		// Activation and deactivation hooks
		register_activation_hook( __FILE__, array( 'Securetor\Core\Activator', 'activate' ) );
		register_deactivation_hook( __FILE__, array( 'Securetor\Core\Deactivator', 'deactivate' ) );

		// Admin hooks
		if ( is_admin() ) {
			$admin = new Securetor\Admin\Admin();
			$this->loader->add_action( 'admin_menu', $admin, 'add_admin_menu' );
			$this->loader->add_action( 'admin_init', $admin, 'admin_init' );
			$this->loader->add_action( 'admin_enqueue_scripts', $admin, 'enqueue_admin_assets' );
			$this->loader->add_action( 'admin_notices', $admin, 'display_admin_notices' );
		}
	}

	/**
	 * Load and initialize plugin modules.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	private function load_modules() {
		// Get enabled modules
		$enabled_modules = get_option( 'securetor_enabled_modules', array(
			'access_control' => true,
			'anti_spam'      => true,
		) );

		// Load Access Control module
		if ( ! empty( $enabled_modules['access_control'] ) ) {
			$this->access_control = new Securetor\Modules\Access_Control();
		}

		// Load Anti-Spam module
		if ( ! empty( $enabled_modules['anti_spam'] ) ) {
			$this->anti_spam = new Securetor\Modules\Anti_Spam();
		}

		/**
		 * Fires after all modules have been loaded.
		 *
		 * @since 2.0.0
		 *
		 * @param Securetor $this Main Securetor instance.
		 */
		do_action( 'securetor_modules_loaded', $this );
	}

	/**
	 * Run the loader to execute all hooks.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * Get the loader instance.
	 *
	 * @since 2.0.0
	 * @return Securetor\Core\Loader
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Get Access Control module instance.
	 *
	 * @since 2.0.0
	 * @return Securetor\Modules\Access_Control|null
	 */
	public function get_access_control() {
		return $this->access_control;
	}

	/**
	 * Get Anti-Spam module instance.
	 *
	 * @since 2.0.0
	 * @return Securetor\Modules\Anti_Spam|null
	 */
	public function get_anti_spam() {
		return $this->anti_spam;
	}

	/**
	 * Prevent cloning of the instance.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	private function __clone() {
		_doing_it_wrong(
			__FUNCTION__,
			esc_html__( 'Cloning Securetor instance is forbidden.', 'securetor' ),
			esc_html( SECURETOR_VERSION )
		);
	}

	/**
	 * Prevent unserializing of the instance.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function __wakeup() {
		_doing_it_wrong(
			__FUNCTION__,
			esc_html__( 'Unserializing Securetor instance is forbidden.', 'securetor' ),
			esc_html( SECURETOR_VERSION )
		);
	}
}

/**
 * Returns the main instance of Securetor.
 *
 * @since 2.0.0
 * @return Securetor
 */
function securetor() {
	return Securetor::instance();
}

// Initialize Securetor
$GLOBALS['securetor'] = securetor();
$GLOBALS['securetor']->run();
