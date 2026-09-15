/**
 * Generates a client-side unique identifier.
 *
 * @return {string} A unique identifier.
 */
export function generateUuid() {
	if (
		typeof window !== 'undefined' &&
		window.crypto &&
		typeof window.crypto.randomUUID === 'function'
	) {
		return window.crypto.randomUUID();
	}

	return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(
		/[xy]/g,
		function (char) {
			const random = Math.floor(Math.random() * 16);
			// Per RFC4122 §4.4, the "y" positions must be one of 8/9/a/b
			// (variant bits 10xx) — expressed here without bitwise ops.
			const value = char === 'x' ? random : 8 + (random % 4);
			return value.toString(16);
		}
	);
}
