/**
 * Click-to-copy for the `.frm-shortcode` chip on the plain PHP-rendered admin screens.
 */

import './style.scss';

function copyToClipboard(text) {
	if (navigator.clipboard && navigator.clipboard.writeText) {
		return navigator.clipboard.writeText(text);
	}

	// Fallback for browsers/contexts without the async Clipboard API.
	const textarea = document.createElement('textarea');
	textarea.value = text;
	textarea.setAttribute('readonly', '');
	textarea.style.position = 'absolute';
	textarea.style.left = '-9999px';
	document.body.appendChild(textarea);
	textarea.select();
	document.execCommand('copy');
	document.body.removeChild(textarea);
	return Promise.resolve();
}

function initShortcodeCopy() {
	document.querySelectorAll('.frm-shortcode').forEach((el) => {
		if (!el.dataset.frmOriginalText) {
			el.dataset.frmOriginalText = el.textContent;
		}

		el.addEventListener('click', () => {
			copyToClipboard(el.dataset.frmOriginalText).then(() => {
				el.classList.add('frm-shortcode--copied');
				el.textContent = 'Copied!';

				window.clearTimeout(el._frmCopyTimeout);
				el._frmCopyTimeout = window.setTimeout(() => {
					el.classList.remove('frm-shortcode--copied');
					el.textContent = el.dataset.frmOriginalText;
				}, 1500);
			});
		});
	});
}

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', initShortcodeCopy);
} else {
	initShortcodeCopy();
}
