<?php
/**
 * Anti-Spam Module
 *
 * Advanced comment spam protection merged from three generations of anti-spam plugins.
 *
 * Credits:
 * - Anti-spam v5.5 by webvitaly (original)
 * - Anti-spam Reloaded v6.5 by kudlav (community fork)
 * - Fortify v1.0 by webvitaly (creator's return)
 *
 * @package    Securetor
 * @subpackage Modules/Anti_Spam
 * @since      2.0.0
 * @author     Krafty Sprouts Media, LLC
 * @license    GPL-2.0+
 */

namespace Securetor\Modules;

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Anti-Spam class.
 *
 * Blocks automated spam comments using dual protection:
 * year validation + honeypot trap.
 *
 * @since 2.0.0
 */
class Anti_Spam {

	/**
	 * Module settings.
	 *
	 * @since 2.0.0
	 * @var   array
	 */
	private $settings = array();

	/**
	 * Initialize the module.
	 *
	 * @since 2.0.0
	 */
	public function __construct() {
		// Load settings
		$this->settings = get_option( 'securetor_anti_spam_settings', array() );

		// Initialize hooks
		$this->init_hooks();
	}

	/**
	 * Initialize WordPress hooks.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	private function init_hooks() {
		// Skip if disabled
		if ( empty( $this->settings['enabled'] ) ) {
			return;
		}

		// Frontend hooks
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'comment_form', array( $this, 'add_form_fields' ) );
		add_filter( 'preprocess_comment', array( $this, 'validate_comment' ), 1 );

		// Admin hooks
		if ( is_admin() ) {
			add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		}
	}

	/**
	 * Enqueue scripts (conditional loading).
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function enqueue_scripts() {
		global $withcomments;

		// Only load on pages with comments
		if ( ! ( is_singular() || $withcomments ) || ! comments_open() ) {
			return;
		}

		// Check if inline mode is enabled
		if ( ! empty( $this->settings['inline_js_mode'] ) ) {
			// Inline JS will be added in add_form_fields()
			return;
		}

		// External JS file with versioning
		wp_enqueue_script(
			'securetor-anti-spam',
			SECURETOR_PLUGIN_URL . 'includes/modules/anti-spam/assets/js/anti-spam.min.js',
			array(),
			SECURETOR_VERSION,
			true
		);
	}

	/**
	 * Add anti-spam fields to comment form.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function add_form_fields() {
		// Skip for logged-in users
		if ( is_user_logged_in() ) {
			return;
		}

		$current_year = gmdate( 'Y' );

		// Random initial value to confuse bots (from Reloaded)
		$random_value = ! empty( $this->settings['random_initial_value'] ) ?
			wp_rand( 0, 99 ) : '';

		$nonce = wp_create_nonce( 'securetor_anti_spam_nonce' );

		// HTML output with proper escaping
		printf(
			'<!-- Securetor Anti-Spam v%s -->',
			esc_html( SECURETOR_VERSION )
		);

		// Year field (hidden by JS)
		printf(
			'<p class="securetor-as-group securetor-as-year" style="clear: both;">
				<label>%s<span class="required">*</span>
					<input type="text" name="securetor_as_q" class="securetor-as-control-q" value="%s" autocomplete="off" />
				</label>
				<input type="hidden" name="securetor_as_a" class="securetor-as-control-a" value="%s" />
				<input type="hidden" name="securetor_as_nonce" value="%s" />
			</p>',
			sprintf(
				/* translators: %s: invisible text */
				esc_html__( 'Current ye%s@r', 'securetor' ),
				'<span style="display:none;">ignore me</span>'
			),
			esc_attr( $random_value ),
			esc_attr( $current_year ),
			esc_attr( $nonce )
		);

		// Honeypot field (hidden with CSS)
		printf(
			'<p class="securetor-as-group securetor-as-trap" style="display: none;">
				<label>%s</label>
				<input type="text" name="securetor_as_e" class="securetor-as-control-e" value="" autocomplete="off" />
			</p>',
			esc_html__( 'Leave this field empty', 'securetor' )
		);

		// Inline JS if enabled (from Fortify)
		if ( ! empty( $this->settings['inline_js_mode'] ) ) {
			$this->output_inline_js();
		}
	}

	/**
	 * Output inline JavaScript.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	private function output_inline_js() {
		$timeout = ! empty( $this->settings['timeout_fallback'] );
		?>
		<script>
		(function() {
			'use strict';

			function securetorAntiSpamInit() {
				// Hide year field
				const groups = document.querySelectorAll('.securetor-as-group');
				groups.forEach(el => el.style.display = 'none');

				// Get answer from hidden field
				const answerField = document.querySelector('.securetor-as-control-a');
				const answer = answerField ? answerField.value : '';

				// Set answer in question field
				const questionFields = document.querySelectorAll('.securetor-as-control-q');
				questionFields.forEach(el => el.value = answer);

				// Clear trap field
				const trapFields = document.querySelectorAll('.securetor-as-control-e');
				trapFields.forEach(el => el.value = '');

				// Add dynamic control
				const dynamicControl = document.createElement('input');
				dynamicControl.type = 'hidden';
				dynamicControl.name = 'securetor_as_d';
				dynamicControl.value = new Date().getFullYear().toString();

				// Add to all comment forms
				const forms = document.querySelectorAll('form');
				forms.forEach(form => {
					if (['comments', 'respond', 'commentform'].includes(form.id)) {
						if (!form.classList.contains('securetor-as-processed')) {
							form.appendChild(dynamicControl.cloneNode(true));
							form.classList.add('securetor-as-processed');
						}
					}
				});
			}

			// Execute on DOM ready
			if (document.readyState === 'loading') {
				document.addEventListener('DOMContentLoaded', securetorAntiSpamInit);
			} else {
				securetorAntiSpamInit();
			}

			<?php if ( $timeout ) : ?>
			// Timeout fallback for theme compatibility (from Original)
			setTimeout(securetorAntiSpamInit, 1000);
			<?php endif; ?>
		})();
		</script>
		<?php
	}

	/**
	 * Validate comment for spam.
	 *
	 * @since 2.0.0
	 *
	 * @param array $commentdata Comment data.
	 * @return array Comment data if valid.
	 */
	public function validate_comment( $commentdata ) {
		// Skip for logged-in users
		if ( is_user_logged_in() ) {
			return $commentdata;
		}

		// Skip for pingbacks
		if ( isset( $commentdata['comment_type'] ) && 'pingback' === $commentdata['comment_type'] ) {
			return $commentdata;
		}

		// Block trackbacks if enabled
		if ( isset( $commentdata['comment_type'] ) && 'trackback' === $commentdata['comment_type'] ) {
			if ( ! empty( $this->settings['block_trackbacks'] ) ) {
				$this->handle_spam_detection( $commentdata, 'trackback' );
			}
			return $commentdata;
		}

		// Verify nonce
		if ( ! isset( $_POST['securetor_as_nonce'] ) ||
			 ! wp_verify_nonce( sanitize_key( $_POST['securetor_as_nonce'] ), 'securetor_anti_spam_nonce' ) ) {
			$this->handle_spam_detection( $commentdata, 'nonce_failure' );
		}

		// Validate year field
		$year_answer = isset( $_POST['securetor_as_q'] ) ?
			sanitize_text_field( wp_unslash( $_POST['securetor_as_q'] ) ) : '';
		$current_year = gmdate( 'Y' );

		if ( $year_answer !== $current_year ) {
			// Check JavaScript fallback field
			$js_year = isset( $_POST['securetor_as_d'] ) ?
				sanitize_text_field( wp_unslash( $_POST['securetor_as_d'] ) ) : '';
			if ( $js_year !== $current_year ) {
				$this->handle_spam_detection( $commentdata, 'year_mismatch' );
			}
		}

		// Check honeypot trap
		$trap_value = isset( $_POST['securetor_as_e'] ) ?
			sanitize_text_field( wp_unslash( $_POST['securetor_as_e'] ) ) : '';
		if ( ! empty( $trap_value ) ) {
			$this->handle_spam_detection( $commentdata, 'honeypot_filled' );
		}

		return $commentdata;
	}

	/**
	 * Handle spam detection.
	 *
	 * @since 2.0.0
	 *
	 * @param array  $commentdata Comment data.
	 * @param string $reason Reason for blocking.
	 * @return void
	 */
	private function handle_spam_detection( $commentdata, $reason ) {
		// Increment statistics
		$this->increment_stats( $reason );

		// Save spam comment if enabled
		if ( ! empty( $this->settings['save_spam_comments'] ) ) {
			$this->store_spam_comment( $commentdata, $reason );
		}

		// Send notification if enabled
		if ( ! empty( $this->settings['email_notifications'] ) ) {
			$this->send_spam_notification( $commentdata, $reason );
		}

		// Custom error message or default
		$error_message = ! empty( $this->settings['custom_error_message'] ) ?
			$this->settings['custom_error_message'] :
			__( 'Your comment has been identified as spam.', 'securetor' );

		wp_die(
			esc_html( $error_message ),
			esc_html__( 'Spam Detected', 'securetor' ),
			array( 'response' => 403 )
		);
	}

	/**
	 * Increment statistics.
	 *
	 * @since 2.0.0
	 *
	 * @param string $reason Reason for blocking.
	 * @return void
	 */
	private function increment_stats( $reason ) {
		$stats = get_option( 'securetor_anti_spam_stats', array() );

		// Total counter
		$stats['blocked_total'] = isset( $stats['blocked_total'] ) ?
			$stats['blocked_total'] + 1 : 1;

		// Reason breakdown
		if ( ! isset( $stats['reasons'] ) ) {
			$stats['reasons'] = array();
		}

		if ( ! isset( $stats['reasons'][ $reason ] ) ) {
			$stats['reasons'][ $reason ] = 0;
		}

		$stats['reasons'][ $reason ]++;

		// Last blocked
		$stats['last_blocked'] = current_time( 'mysql' );

		update_option( 'securetor_anti_spam_stats', $stats );
	}

	/**
	 * Store spam comment for review.
	 *
	 * @since 2.0.0
	 *
	 * @param array  $commentdata Comment data.
	 * @param string $reason Reason for blocking.
	 * @return void
	 */
	private function store_spam_comment( $commentdata, $reason ) {
		$spam_log = get_option( 'securetor_anti_spam_saved', array() );

		$spam_log[] = array(
			'author'  => isset( $commentdata['comment_author'] ) ? $commentdata['comment_author'] : '',
			'email'   => isset( $commentdata['comment_author_email'] ) ? $commentdata['comment_author_email'] : '',
			'content' => isset( $commentdata['comment_content'] ) ? substr( $commentdata['comment_content'], 0, 500 ) : '',
			'reason'  => $reason,
			'time'    => current_time( 'mysql' ),
			'ip'      => isset( $commentdata['comment_author_IP'] ) ? $commentdata['comment_author_IP'] : '',
		);

		// Keep last 100
		$spam_log = array_slice( $spam_log, -100 );

		update_option( 'securetor_anti_spam_saved', $spam_log );
	}

	/**
	 * Send spam notification email.
	 *
	 * @since 2.0.0
	 *
	 * @param array  $commentdata Comment data.
	 * @param string $reason Reason for blocking.
	 * @return void
	 */
	private function send_spam_notification( $commentdata, $reason ) {
		$to = ! empty( $this->settings['notification_email'] ) ?
			$this->settings['notification_email'] : get_option( 'admin_email' );

		$subject = sprintf(
			/* translators: %s: site name */
			__( '[%s] Spam Comment Blocked', 'securetor' ),
			get_bloginfo( 'name' )
		);

		$message = sprintf(
			/* translators: 1: reason, 2: author, 3: email, 4: IP, 5: content, 6: time */
			__( "A spam comment was blocked on your site.\n\nReason: %1\$s\nAuthor: %2\$s\nEmail: %3\$s\nIP: %4\$s\nContent: %5\$s\n\nTime: %6\$s", 'securetor' ),
			$reason,
			isset( $commentdata['comment_author'] ) ? $commentdata['comment_author'] : __( 'Unknown', 'securetor' ),
			isset( $commentdata['comment_author_email'] ) ? $commentdata['comment_author_email'] : __( 'Unknown', 'securetor' ),
			isset( $commentdata['comment_author_IP'] ) ? $commentdata['comment_author_IP'] : __( 'Unknown', 'securetor' ),
			isset( $commentdata['comment_content'] ) ? wp_trim_words( $commentdata['comment_content'], 50 ) : '',
			current_time( 'mysql' )
		);

		wp_mail( $to, $subject, $message );
	}

	/**
	 * Display admin notices.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function admin_notices() {
		$screen = get_current_screen();

		if ( ! $screen || 'edit-comments' !== $screen->id ) {
			return;
		}

		if ( empty( $this->settings['show_info_notice'] ) ) {
			return;
		}

		$stats = get_option( 'securetor_anti_spam_stats', array() );
		$blocked = isset( $stats['blocked_total'] ) ? $stats['blocked_total'] : 0;

		if ( $blocked > 0 ) {
			printf(
				'<div class="notice notice-info is-dismissible"><p><strong>%s:</strong> %s</p></div>',
				esc_html__( 'Securetor Anti-Spam', 'securetor' ),
				sprintf(
					/* translators: %s: number of blocked spam */
					esc_html( _n( '%s spam comment blocked', '%s spam comments blocked', $blocked, 'securetor' ) ),
					'<strong>' . esc_html( number_format_i18n( $blocked ) ) . '</strong>'
				)
			);
		}
	}
}
