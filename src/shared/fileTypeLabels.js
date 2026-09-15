/**
 * Client-only presentation labels mirroring AllowedFileTypes.php, for display hints only.
 *
 * @type {Object<string, string>}
 */
export const FILE_TYPE_LABELS = {
	pdf: 'PDF',
	jpg: 'JPG',
	png: 'PNG',
	gif: 'GIF',
	webp: 'WEBP',
	doc: 'DOC',
	docx: 'DOCX',
	xls: 'XLS',
	xlsx: 'XLSX',
	zip: 'ZIP',
};

/**
 * File extensions per type key, mirroring AllowedFileTypes::all()'s
 * `extensions` list. Used to build a native `accept` attribute so the OS
 * file picker itself only offers matching files — client-side UX only, the
 * server independently re-validates every upload regardless.
 *
 * @type {Object<string, string[]>}
 */
export const FILE_TYPE_EXTENSIONS = {
	pdf: ['pdf'],
	jpg: ['jpg', 'jpeg'],
	png: ['png'],
	gif: ['gif'],
	webp: ['webp'],
	doc: ['doc'],
	docx: ['docx'],
	xls: ['xls'],
	xlsx: ['xlsx'],
	zip: ['zip'],
};

/**
 * Builds a native `accept` attribute value from a list of allowed type keys.
 *
 * @param {string[]} allowedTypes Type keys, e.g. [ 'pdf', 'jpg' ].
 * @return {string|undefined} A comma-separated `.ext` list, or `undefined`
 *                             when no restriction applies (any file type).
 */
export function buildAcceptAttribute(allowedTypes) {
	if (!Array.isArray(allowedTypes) || allowedTypes.length === 0) {
		return undefined;
	}

	const extensions = allowedTypes.flatMap(
		(key) => FILE_TYPE_EXTENSIONS[key] || [key]
	);

	return extensions.map((extension) => `.${extension}`).join(',');
}

/**
 * Builds a human-readable "Accepted: ..." string from a list of allowed type keys.
 *
 * @param {string[]} allowedTypes Type keys, e.g. [ 'pdf', 'jpg' ].
 * @return {string} Comma separated labels, or an empty string when none are set.
 */
export function formatAllowedTypes(allowedTypes) {
	if (!Array.isArray(allowedTypes) || allowedTypes.length === 0) {
		return '';
	}

	return allowedTypes
		.map((key) => FILE_TYPE_LABELS[key] || key.toUpperCase())
		.join(', ');
}
