/**
 * Securetor Admin JavaScript
 *
 * @package Securetor
 * @since   2.0.0
 */

(function($) {
	'use strict';

	/**
	 * Securetor Admin object.
	 */
	var SecuretorAdmin = {

		/**
		 * Initialize.
		 */
		init: function() {
			this.handleDismissals();
			this.handleConfirmations();
			this.handleIPWhitelist();
		},

		/**
		 * Handle notice dismissals.
		 */
		handleDismissals: function() {
			$(document).on('click', '.securetor-welcome-notice .notice-dismiss', function() {
				$.post(
					ajaxurl,
					{
						action: 'securetor_dismiss_welcome',
						nonce: securetorAdmin.nonce
					}
				);
			});
		},

		/**
		 * Handle confirmation dialogs.
		 */
		handleConfirmations: function() {
			// Reset statistics
			$(document).on('click', '[name="reset_stats"]', function(e) {
				if (!confirm(securetorAdmin.strings.confirm_reset)) {
					e.preventDefault();
					return false;
				}
			});

			// Delete/Remove actions
			$(document).on('click', '.securetor-delete, .securetor-remove', function(e) {
				if (!confirm(securetorAdmin.strings.confirm_delete)) {
					e.preventDefault();
					return false;
				}
			});
		},

		/**
		 * Handle IP whitelist actions.
		 */
		handleIPWhitelist: function() {
			var self = this;

			// Add IP to whitelist
			$('#securetor-add-ip-form').on('submit', function(e) {
				e.preventDefault();

				var $form = $(this);
				var $button = $form.find('[type="submit"]');
				var originalText = $button.val();

				// Disable button and show loading
				$button.prop('disabled', true).val('Adding...');

				$.post(
					ajaxurl,
					{
						action: 'securetor_add_ip',
						ip: $form.find('[name="ip_address"]').val(),
						description: $form.find('[name="description"]').val(),
						nonce: securetorAdmin.nonce
					},
					function(response) {
						if (response.success) {
							// Show success message
							self.showNotice('IP added successfully!', 'success');

							// Clear form
							$form[0].reset();

							// Reload whitelist table
							location.reload();
						} else {
							self.showNotice(response.data || 'Error adding IP', 'error');
						}
					}
				).always(function() {
					$button.prop('disabled', false).val(originalText);
				});
			});
		},

		/**
		 * Show admin notice.
		 *
		 * @param {string} message Notice message.
		 * @param {string} type    Notice type (success, error, warning, info).
		 */
		showNotice: function(message, type) {
			type = type || 'info';

			var $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');

			$('.wrap h1').after($notice);

			// Auto-dismiss after 5 seconds
			setTimeout(function() {
				$notice.fadeOut(function() {
					$(this).remove();
				});
			}, 5000);
		}
	};

	// Initialize on document ready
	$(document).ready(function() {
		SecuretorAdmin.init();
	});

})(jQuery);
