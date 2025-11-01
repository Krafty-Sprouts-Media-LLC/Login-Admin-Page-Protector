/**
 * Securetor Anti-Spam JavaScript
 *
 * Handles comment form spam protection by:
 * - Hiding the year verification field
 * - Auto-filling current year
 * - Ensuring honeypot trap stays empty
 *
 * Credits: Merged from Anti-spam v5.5, Reloaded v6.5, and Fortify v1.0
 *
 * @package Securetor
 * @since   2.0.0
 */

(function() {
	'use strict';

	/**
	 * Initialize anti-spam protection.
	 */
	function securetorAntiSpamInit() {
		// Hide year field and trap
		const groups = document.querySelectorAll('.securetor-as-group');
		groups.forEach(function(el) {
			el.style.display = 'none';
		});

		// Get answer from hidden field
		const answerField = document.querySelector('.securetor-as-control-a');
		const answer = answerField ? answerField.value : '';

		// Set answer in question field
		const questionFields = document.querySelectorAll('.securetor-as-control-q');
		questionFields.forEach(function(el) {
			el.value = answer;
		});

		// Clear trap field (honeypot must stay empty)
		const trapFields = document.querySelectorAll('.securetor-as-control-e');
		trapFields.forEach(function(el) {
			el.value = '';
		});

		// Add dynamic year control (JavaScript-only verification)
		const dynamicControl = document.createElement('input');
		dynamicControl.type = 'hidden';
		dynamicControl.name = 'securetor_as_d';
		dynamicControl.value = new Date().getFullYear().toString();

		// Add to all comment forms
		const forms = document.querySelectorAll('form');
		forms.forEach(function(form) {
			// Check for common comment form IDs
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

	// Timeout fallback for theme compatibility (from Original v5.5)
	// Some themes load comment forms dynamically
	setTimeout(securetorAntiSpamInit, 1000);
})();
