<?php
/**
 * Securetor Admin
 *
 * Main admin interface controller.
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
 * Admin class.
 *
 * Handles all admin-related functionality.
 *
 * @since 2.0.0
 */
class Admin {

	/**
	 * Initialize the class.
	 *
	 * @since 2.0.0
	 */
	public function __construct() {
		// Constructor can be used for future initialization
	}

	/**
	 * Add admin menu pages.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function add_admin_menu() {
		// Main menu page
		add_menu_page(
			esc_html__( 'Securetor', 'securetor' ),
			esc_html__( 'Securetor', 'securetor' ),
			'manage_options',
			'securetor',
			array( $this, 'render_dashboard_page' ),
			'dashicons-shield',
			80
		);

		// Dashboard submenu
		add_submenu_page(
			'securetor',
			esc_html__( 'Dashboard', 'securetor' ),
			esc_html__( 'Dashboard', 'securetor' ),
			'manage_options',
			'securetor',
			array( $this, 'render_dashboard_page' )
		);

		// Access Control submenu
		add_submenu_page(
			'securetor',
			esc_html__( 'Access Control', 'securetor' ),
			esc_html__( 'Access Control', 'securetor' ),
			'manage_options',
			'securetor-access-control',
			array( $this, 'render_access_control_page' )
		);

		// Anti-Spam submenu
		add_submenu_page(
			'securetor',
			esc_html__( 'Anti-Spam', 'securetor' ),
			esc_html__( 'Anti-Spam', 'securetor' ),
			'manage_options',
			'securetor-anti-spam',
			array( $this, 'render_anti_spam_page' )
		);

		// Settings submenu
		add_submenu_page(
			'securetor',
			esc_html__( 'Settings', 'securetor' ),
			esc_html__( 'Settings', 'securetor' ),
			'manage_options',
			'securetor-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register admin initialization hooks.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function admin_init() {
		// Register settings
		$settings = new Settings();
		$settings->register_settings();
	}

	/**
	 * Enqueue admin assets (CSS/JS).
	 *
	 * @since 2.0.0
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_admin_assets( $hook ) {
		// Only load on Securetor pages
		if ( strpos( $hook, 'securetor' ) === false ) {
			return;
		}

		// Enqueue admin CSS
		wp_enqueue_style(
			'securetor-admin',
			SECURETOR_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			SECURETOR_VERSION
		);

		// Enqueue admin JS
		wp_enqueue_script(
			'securetor-admin',
			SECURETOR_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			SECURETOR_VERSION,
			true
		);

		// Localize script with data
		wp_localize_script(
			'securetor-admin',
			'securetorAdmin',
			array(
				'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
				'nonce'       => wp_create_nonce( 'securetor_admin_nonce' ),
				'strings'     => array(
					'confirm_reset' => esc_html__( 'Are you sure you want to reset statistics? This action cannot be undone.', 'securetor' ),
					'confirm_delete' => esc_html__( 'Are you sure you want to delete this entry?', 'securetor' ),
				),
			)
		);
	}

	/**
	 * Display admin notices.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function display_admin_notices() {
		// Welcome notice
		if ( get_option( 'securetor_show_welcome' ) ) {
			$this->render_welcome_notice();
		}

		// Module-specific notices
		$this->render_module_notices();
	}

	/**
	 * Render welcome notice.
	 *
	 * @since 2.0.0
	 * @access private
	 * @return void
	 */
	private function render_welcome_notice() {
		?>
		<div class="notice notice-success is-dismissible securetor-welcome-notice">
			<h2><?php esc_html_e( 'Welcome to Securetor!', 'securetor' ); ?></h2>
			<p>
				<?php
				esc_html_e( 'Thank you for installing Securetor - your comprehensive WordPress security solution.', 'securetor' );
				?>
			</p>
			<p>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=securetor' ) ); ?>" class="button button-primary">
					<?php esc_html_e( 'Get Started', 'securetor' ); ?>
				</a>
				<a href="<?php echo esc_url( 'https://kraftysprouts.com/securetor/docs' ); ?>" class="button" target="_blank">
					<?php esc_html_e( 'Documentation', 'securetor' ); ?>
				</a>
			</p>
		</div>
		<script>
		jQuery(document).ready(function($) {
			$('.securetor-welcome-notice').on('click', '.notice-dismiss', function() {
				$.post(ajaxurl, {
					action: 'securetor_dismiss_welcome',
					nonce: '<?php echo esc_js( wp_create_nonce( 'securetor_dismiss_welcome' ) ); ?>'
				});
			});
		});
		</script>
		<?php

		// Remove the notice after displaying once
		delete_option( 'securetor_show_welcome' );
	}

	/**
	 * Render module-specific notices.
	 *
	 * @since 2.0.0
	 * @access private
	 * @return void
	 */
	private function render_module_notices() {
		$current_screen = get_current_screen();

		// Access Control warnings
		if ( $current_screen && strpos( $current_screen->id, 'securetor-access-control' ) !== false ) {
			$this->render_access_control_notices();
		}

		// Anti-Spam warnings
		if ( $current_screen && strpos( $current_screen->id, 'securetor-anti-spam' ) !== false ) {
			$this->render_anti_spam_notices();
		}
	}

	/**
	 * Render Access Control specific notices.
	 *
	 * @since 2.0.0
	 * @access private
	 * @return void
	 */
	private function render_access_control_notices() {
		$settings = get_option( 'securetor_access_control_settings', array() );
		$bypass_key = get_option( 'securetor_bypass_key' );

		// Warn if no bypass key is set
		if ( empty( $bypass_key ) ) {
			?>
			<div class="notice notice-warning">
				<p>
					<strong><?php esc_html_e( 'Warning:', 'securetor' ); ?></strong>
					<?php esc_html_e( 'No emergency bypass key has been configured. If you get locked out, you will need FTP access to regain control.', 'securetor' ); ?>
					<a href="#emergency-bypass"><?php esc_html_e( 'Set up now', 'securetor' ); ?></a>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * Render Anti-Spam specific notices.
	 *
	 * @since 2.0.0
	 * @access private
	 * @return void
	 */
	private function render_anti_spam_notices() {
		// Anti-spam notices can be added here
	}

	/**
	 * Render dashboard page.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function render_dashboard_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'securetor' ) );
		}

		require_once SECURETOR_PLUGIN_DIR . 'includes/admin/views/dashboard.php';
	}

	/**
	 * Render Access Control page.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function render_access_control_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'securetor' ) );
		}

		require_once SECURETOR_PLUGIN_DIR . 'includes/admin/views/access-control.php';
	}

	/**
	 * Render Anti-Spam page.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function render_anti_spam_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'securetor' ) );
		}

		require_once SECURETOR_PLUGIN_DIR . 'includes/admin/views/anti-spam.php';
	}

	/**
	 * Render Settings page.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'securetor' ) );
		}

		require_once SECURETOR_PLUGIN_DIR . 'includes/admin/views/settings.php';
	}
}
