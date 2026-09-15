<?php
/**
 * Shared plugin branding header, included at the top of every admin screen.
 *
 * @package FileRequestManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="frm-brand-header">
	<svg class="frm-brand-header__logo" width="28" height="28" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
		<rect width="32" height="32" rx="9" fill="#2563EB"/>
		<path d="M16 9V19" stroke="#fff" stroke-width="2.4" stroke-linecap="round"/>
		<path d="M11.5 13.5L16 9L20.5 13.5" stroke="#fff" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>
		<path d="M9 20.5V22.5C9 23.0523 9.44772 23.5 10 23.5H22C22.5523 23.5 23 23.0523 23 22.5V20.5" stroke="#fff" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>
	</svg>
	<span class="frm-brand-header__title"><?php esc_html_e( 'ReNevo', 'renevo-file-request-manager' ); ?></span>
</div>
