/**
 * Per-requested-file upload state machine.
 * State: { [requestedFileKey]: UploadEntry[] }, entry status is
 * waiting | uploading | uploaded | failed | removed.
 */

/**
 * Builds the empty initial state for a set of requested files.
 *
 * @param {Array<{key: string}>} requestedFiles Requested file definitions.
 * @return {Object} Initial state, one empty array per requested file key.
 */
export function initialUploadState(requestedFiles) {
	const state = {};

	(requestedFiles || []).forEach((requestedFile) => {
		state[requestedFile.key] = [];
	});

	return state;
}

/**
 * Gets every entry across every slot whose status is "uploaded".
 *
 * @param {Object} state Reducer state.
 * @return {Array} Uploaded entries.
 */
export function getUploadedEntries(state) {
	return Object.values(state)
		.flat()
		.filter((entry) => entry.status === 'uploaded');
}

/**
 * Gets every successfully uploaded token, in no particular order.
 *
 * @param {Object} state Reducer state.
 * @return {string[]} Tokens.
 */
export function getUploadedTokens(state) {
	return getUploadedEntries(state).map((entry) => entry.token);
}

/**
 * Whether a given requested file slot has at least one uploaded entry.
 *
 * @param {Object} state Reducer state.
 * @param {string} key   Requested file key.
 * @return {boolean} Whether at least one entry in that slot is uploaded.
 */
export function hasUploadedFile(state, key) {
	return (state[key] || []).some((entry) => entry.status === 'uploaded');
}

/**
 * Counts entries in a slot that are still "live" (not removed) — used to
 * enforce max_files client-side.
 *
 * @param {Object} state Reducer state.
 * @param {string} key   Requested file key.
 * @return {number} Count of non-removed entries.
 */
export function countActiveEntries(state, key) {
	return (state[key] || []).filter((entry) => entry.status !== 'removed')
		.length;
}

function updateEntry(state, key, id, changes) {
	const entries = state[key] || [];

	return {
		...state,
		[key]: entries.map((entry) =>
			entry.id === id ? { ...entry, ...changes } : entry
		),
	};
}

/**
 * The reducer.
 *
 * @param {Object} state  Current state.
 * @param {Object} action Action, see the individual `case`s for shape.
 * @return {Object} Next state.
 */
export function uploadReducer(state, action) {
	switch (action.type) {
		case 'ADD_ENTRIES': {
			const { key, entries } = action;
			return {
				...state,
				[key]: [...(state[key] || []), ...entries],
			};
		}

		case 'START_UPLOAD':
			return updateEntry(state, action.key, action.id, {
				status: 'uploading',
				progress: 0,
				error: null,
			});

		case 'PROGRESS':
			return updateEntry(state, action.key, action.id, {
				progress: action.progress,
			});

		case 'SUCCESS':
			return updateEntry(state, action.key, action.id, {
				status: 'uploaded',
				progress: 100,
				token: action.token,
				error: null,
			});

		case 'FAILURE':
			return updateEntry(state, action.key, action.id, {
				status: 'failed',
				error: action.error,
			});

		case 'REMOVE':
			return updateEntry(state, action.key, action.id, {
				status: 'removed',
				progress: 0,
			});

		case 'RESET':
			return initialUploadState(action.requestedFiles);

		default:
			return state;
	}
}
