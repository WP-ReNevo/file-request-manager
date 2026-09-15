/**
 * React hook wrapping `uploadReducer` with the side-effecting parts
 * (talking to an upload adapter, client-side size/count checks) that don't
 * belong in a pure reducer.
 */

import { useReducer, useCallback } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { generateUuid } from './uuid';
import {
	uploadReducer,
	initialUploadState,
	countActiveEntries,
	getUploadedTokens,
} from './uploadReducer';

/**
 * @param {Array}       requestedFiles Requested file definitions.
 * @param {Object|null} adapter        An upload adapter (real or mock), or null
 *                                     for a fully static/no-interaction context.
 * @return {Object} { state, addFiles, retry, remove, uploadedTokens }
 */
export function useUploadManager(requestedFiles, adapter) {
	const [state, dispatch] = useReducer(
		uploadReducer,
		requestedFiles,
		initialUploadState
	);

	const runUpload = useCallback(
		(key, entry) => {
			if (!adapter) {
				return;
			}

			dispatch({ type: 'START_UPLOAD', key, id: entry.id });

			adapter
				.uploadFile(entry.file, key, (progress) => {
					dispatch({ type: 'PROGRESS', key, id: entry.id, progress });
				})
				.then((result) => {
					dispatch({
						type: 'SUCCESS',
						key,
						id: entry.id,
						token: result.token,
					});
				})
				.catch((error) => {
					dispatch({
						type: 'FAILURE',
						key,
						id: entry.id,
						error:
							error && error.message
								? error.message
								: __(
										'The upload failed. Please try again.',
										'renevo-file-request-manager'
									),
					});
				});
		},
		[adapter]
	);

	const addFiles = useCallback(
		(requestedFile, files) => {
			const key = requestedFile.key;
			const already = countActiveEntries(state, key);
			const room = Math.max(0, requestedFile.max_files - already);
			const incoming = Array.from(files).slice(0, room);
			const maxBytes = requestedFile.max_size_mb * 1024 * 1024;

			const entries = incoming.map((file) => {
				const tooLarge = file.size > maxBytes;

				return {
					id: generateUuid(),
					name: file.name,
					size: file.size,
					status: tooLarge ? 'failed' : 'waiting',
					progress: 0,
					token: null,
					error: tooLarge
						? sprintf(
								/* translators: %d: maximum file size in megabytes. */
								__(
									'This file is larger than the allowed maximum of %d MB.',
									'renevo-file-request-manager'
								),
								requestedFile.max_size_mb
							)
						: null,
					file,
				};
			});

			dispatch({ type: 'ADD_ENTRIES', key, entries });

			entries
				.filter((entry) => entry.status === 'waiting')
				.forEach((entry) => runUpload(key, entry));
		},
		[state, runUpload]
	);

	const retry = useCallback(
		(key, id) => {
			const entry = (state[key] || []).find((item) => item.id === id);

			if (entry && entry.file) {
				runUpload(key, entry);
			}
		},
		[state, runUpload]
	);

	const remove = useCallback((key, id) => {
		dispatch({ type: 'REMOVE', key, id });
	}, []);

	return {
		state,
		addFiles,
		retry,
		remove,
		uploadedTokens: getUploadedTokens(state),
	};
}
