<?php
/**
 * Securetor Internationalization
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
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
 * I18n class.
 *
 * Handles plugin internationalization and localization.
 *
 * @since 2.0.0
 */
class I18n {

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain(
			'securetor',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);
	}
}
