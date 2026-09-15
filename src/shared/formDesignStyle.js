// Turns a `form_design` object into `.frm-frontend-root` CSS custom properties.
// Used by every render surface so they all agree pixel-for-pixel.

const FONT_STACKS = {
	system: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif',
	sans: '"Helvetica Neue", Helvetica, Arial, sans-serif',
	serif: 'Georgia, "Times New Roman", Times, serif',
	monospace:
		'ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono", monospace',
};

const ALIGN_MAP = {
	left: 'flex-start',
	center: 'center',
	right: 'flex-end',
};

/**
 * @param {Object} formDesign A `form_design` settings object (see Request.php).
 * @return {Object} A plain object of `--frm-*` CSS custom properties, usable
 *                   directly as a React `style` prop or via
 *                   `element.style.setProperty(key, value)`.
 */
export function buildFormDesignVars(formDesign) {
	if (!formDesign) {
		return {};
	}

	return {
		'--frm-color-surface': formDesign.background_color,
		'--frm-color-border': formDesign.border_color,
		'--frm-radius': `${formDesign.border_radius}px`,
		'--frm-space': `${formDesign.padding}px`,
		// The submit button color doubles as the form's overall accent
		// (dropzone icon/link, progress bar, focus rings) so a preset reads
		// as one cohesive color story, not just a differently-colored button.
		'--frm-color-primary': formDesign.submit_button_color,
		'--frm-color-primary-hover': formDesign.hover_color,
		'--frm-font-size': `${formDesign.font_size}px`,
		'--frm-font-family':
			FONT_STACKS[formDesign.font_family] || FONT_STACKS.system,
		'--frm-submit-bg': formDesign.submit_button_color,
		'--frm-submit-hover-bg':
			formDesign.submit_button_hover_color || formDesign.hover_color,
		'--frm-submit-border-color':
			formDesign.submit_button_border_color || 'transparent',
		'--frm-submit-align':
			ALIGN_MAP[formDesign.submit_button_position] || 'flex-start',
		'--frm-page-bg': formDesign.page_background_color || 'transparent',
		'--frm-required-color': formDesign.required_badge_color,
	};
}
