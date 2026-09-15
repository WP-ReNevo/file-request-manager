// Client-side registry of public-form layout components, backed by
// `window.renevo` — the interop boundary a Pro add-on's own, separately-built
// script uses to register a new layout without importing our internal
// modules (mirrors PHP `register_block_type()` + JS `registerBlockType()`:
// FormLayoutRegistry.php owns label/description for the picker + server-side
// validation, this owns rendering only).

const w = typeof window !== 'undefined' ? window : {};
w.renevo = w.renevo || {};
w.renevo.layouts = w.renevo.layouts || {};

/**
 * @param {string}   key       Layout key, matching a FormLayoutRegistry.php entry.
 * @param {Function} Component React component, see the layout prop contract in src/shared/layouts/index.js.
 */
export function registerLayout(key, Component) {
	if (!key || typeof Component !== 'function') {
		// eslint-disable-next-line no-console
		console.error(`renevo.registerLayout: invalid layout "${key}"`);
		return;
	}
	w.renevo.layouts[key] = Component;
}

/**
 * @param {string} key Layout key.
 * @return {Function|null} The registered component, or null if unknown.
 */
export function getLayout(key) {
	return w.renevo.layouts[key] || null;
}

w.renevo.registerLayout = registerLayout;
w.renevo.getLayout = getLayout;
