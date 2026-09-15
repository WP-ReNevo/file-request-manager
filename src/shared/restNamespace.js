/**
 * Admin-context REST calls rely on WordPress core's own default `apiFetch`
 * root URL + nonce middleware (auto-registered on every wp-admin page), so
 * paths are prefixed with the full namespace here rather than configuring a
 * second, competing root URL middleware (which core's own registration
 * would silently win over).
 */
export const REST_NAMESPACE = '/renevo/v1';

/**
 * Builds a namespace-prefixed REST path for use with the default `apiFetch` instance.
 *
 * @param {string} path Path relative to the plugin's REST namespace, e.g. "/templates".
 * @return {string} Full path relative to the WP REST root, e.g. "/renevo/v1/templates".
 */
export function restPath(path) {
	return `${REST_NAMESPACE}${path}`;
}
