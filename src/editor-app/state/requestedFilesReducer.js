/**
 * State management for the "Files you need" builder: add, edit, duplicate, delete.
 * Each item's `key` (see `../../shared/uuid.js`) is generated once and never changes.
 */

import { generateUuid } from '../../shared/uuid';

/**
 * Builds a blank requested file with a fresh, stable key.
 *
 * @param {Object} [overrides] Fields to override the defaults with.
 * @return {Object} A new requested file definition.
 */
export function createRequestedFile(overrides = {}) {
	return {
		key: generateUuid(),
		title: '',
		description: '',
		required: true,
		allowed_types: [],
		max_size_mb: 10,
		max_files: 1,
		...overrides,
	};
}

/**
 * @param {Array}  files  Current list of requested files.
 * @param {Object} action Action, see the individual `case`s for shape.
 * @return {Array} Next list.
 */
export function requestedFilesReducer(files, action) {
	switch (action.type) {
		case 'ADD':
			return [...files, createRequestedFile(action.file)];

		case 'UPDATE':
			return files.map((file) =>
				file.key === action.key
					? { ...file, ...action.changes, key: file.key }
					: file
			);

		case 'DELETE':
			return files.filter((file) => file.key !== action.key);

		case 'DUPLICATE': {
			const index = files.findIndex((file) => file.key === action.key);
			if (index === -1) {
				return files;
			}

			const copy = {
				...files[index],
				key: generateUuid(),
				title: `${files[index].title} (copy)`.trim(),
			};

			const next = [...files];
			next.splice(index + 1, 0, copy);
			return next;
		}

		case 'REORDER': {
			const { fromIndex, toIndex } = action;
			if (
				fromIndex < 0 ||
				fromIndex >= files.length ||
				toIndex < 0 ||
				toIndex >= files.length
			) {
				return files;
			}

			const next = [...files];
			const [moved] = next.splice(fromIndex, 1);
			next.splice(toIndex, 0, moved);
			return next;
		}

		case 'SET_ALL':
			return action.files || [];

		default:
			return files;
	}
}
