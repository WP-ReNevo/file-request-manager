/**
 * Client-side {token} placeholder replacement, mirroring
 * `includes/Services/PlaceholderResolver.php` for live previews. Unresolved
 * tokens become an empty string, same as the server.
 */

/**
 * Replaces every `{token}` in a template with values from a context object.
 *
 * @param {string} template Raw template text.
 * @param {Object} context  Placeholder name => value (without braces).
 * @return {string} Resolved text.
 */
export function resolvePlaceholders(template, context) {
	return String(template || '').replace(
		/\{([a-zA-Z0-9_]+)\}/g,
		(match, key) =>
			Object.prototype.hasOwnProperty.call(context, key)
				? String(context[key])
				: ''
	);
}
