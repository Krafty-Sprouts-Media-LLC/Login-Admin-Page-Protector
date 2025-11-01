<?php
/**
 * Securetor Settings
 *
 * Handles registration of all plugin settings using WordPress Settings API.
 *
 * @package    Securetor
 * @subpackage Admin
 * @since      2.0.0
 * @author     Krafty Sprouts Media, LLC
 * @license    GPL-2.0+
 */

namespace Securetor\Admin;

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings class.
 *
 * Registers and handles all plugin settings.
 *
 * @since 2.0.0
 */
class Settings {

	/**
	 * Register all settings.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function register_settings() {
		// Access Control settings
		register_setting(
			'securetor_access_control',
			'securetor_access_control_settings',
			array(
				'sanitize_callback' => array( $this, 'sanitize_access_control_settings' ),
			)
		);

		// Anti-Spam settings
		register_setting(
			'securetor_anti_spam',
			'securetor_anti_spam_settings',
			array(
				'sanitize_callback' => array( $this, 'sanitize_anti_spam_settings' ),
			)
		);

		// General settings
		register_setting(
			'securetor_general',
			'securetor_enabled_modules',
			array(
				'sanitize_callback' => array( $this, 'sanitize_enabled_modules' ),
			)
		);
	}

	/**
	 * Sanitize Access Control settings.
	 *
	 * @since 2.0.0
	 *
	 * @param array $input Settings input.
	 * @return array Sanitized settings.
	 */
	public function sanitize_access_control_settings( $input ) {
		$sanitized = array();

		// Enabled
		$sanitized['enabled'] = ! empty( $input['enabled'] );

		// Allowed countries
		if ( isset( $input['allowed_countries'] ) && is_array( $input['allowed_countries'] ) ) {
			$sanitized['allowed_countries'] = array_map( 'sanitize_text_field', $input['allowed_countries'] );
		} else {
			$sanitized['allowed_countries'] = array( 'NG' );
		}

		// Cache duration
		$sanitized['cache_duration'] = isset( $input['cache_duration'] ) ?
			absint( $input['cache_duration'] ) : 3600;

		// Use external API
		$sanitized['use_external_api'] = ! empty( $input['use_external_api'] );

		// Block login
		$sanitized['block_login'] = ! empty( $input['block_login'] );

		// Block admin
		$sanitized['block_admin'] = ! empty( $input['block_admin'] );

		return $sanitized;
	}

	/**
	 * Sanitize Anti-Spam settings.
	 *
	 * @since 2.0.0
	 *
	 * @param array $input Settings input.
	 * @return array Sanitized settings.
	 */
	public function sanitize_anti_spam_settings( $input ) {
		$sanitized = array();

		// Enabled
		$sanitized['enabled'] = ! empty( $input['enabled'] );

		// Save spam comments
		$sanitized['save_spam_comments'] = ! empty( $input['save_spam_comments'] );

		// Block trackbacks
		$sanitized['block_trackbacks'] = ! empty( $input['block_trackbacks'] );

		// Inline JS mode
		$sanitized['inline_js_mode'] = ! empty( $input['inline_js_mode'] );

		// Timeout fallback
		$sanitized['timeout_fallback'] = ! empty( $input['timeout_fallback'] );

		// Random initial value
		$sanitized['random_initial_value'] = ! empty( $input['random_initial_value'] );

		// Email notifications
		$sanitized['email_notifications'] = ! empty( $input['email_notifications'] );

		// Notification email
		$sanitized['notification_email'] = isset( $input['notification_email'] ) ?
			sanitize_email( $input['notification_email'] ) : get_option( 'admin_email' );

		// Custom error message
		$sanitized['custom_error_message'] = isset( $input['custom_error_message'] ) ?
			sanitize_textarea_field( $input['custom_error_message'] ) : '';

		// Show info notice
		$sanitized['show_info_notice'] = ! empty( $input['show_info_notice'] );

		return $sanitized;
	}

	/**
	 * Sanitize enabled modules.
	 *
	 * @since 2.0.0
	 *
	 * @param array $input Modules input.
	 * @return array Sanitized modules.
	 */
	public function sanitize_enabled_modules( $input ) {
		$sanitized = array();

		if ( isset( $input['access_control'] ) ) {
			$sanitized['access_control'] = ! empty( $input['access_control'] );
		}

		if ( isset( $input['anti_spam'] ) ) {
			$sanitized['anti_spam'] = ! empty( $input['anti_spam'] );
		}

		return $sanitized;
	}
}
