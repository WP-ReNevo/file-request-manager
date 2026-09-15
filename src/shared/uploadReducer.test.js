/**
 * Tests for the per-requested-file upload state machine.
 */

import {
	uploadReducer,
	initialUploadState,
	getUploadedEntries,
	getUploadedTokens,
	hasUploadedFile,
	countActiveEntries,
} from './uploadReducer';

const REQUESTED_FILES = [
	{ key: 'id-doc', max_files: 1 },
	{ key: 'proof-of-address', max_files: 3 },
];

function entry(overrides = {}) {
	return {
		id: 'entry-1',
		name: 'photo.jpg',
		size: 1024,
		status: 'waiting',
		progress: 0,
		token: null,
		error: null,
		file: undefined,
		...overrides,
	};
}

describe('initialUploadState', () => {
	it('creates one empty array per requested file key', () => {
		expect(initialUploadState(REQUESTED_FILES)).toEqual({
			'id-doc': [],
			'proof-of-address': [],
		});
	});

	it('handles an empty/undefined list', () => {
		expect(initialUploadState([])).toEqual({});
		expect(initialUploadState(undefined)).toEqual({});
	});
});

describe('uploadReducer', () => {
	it('ADD_ENTRIES appends entries to the given slot only', () => {
		const state = initialUploadState(REQUESTED_FILES);
		const next = uploadReducer(state, {
			type: 'ADD_ENTRIES',
			key: 'id-doc',
			entries: [entry()],
		});

		expect(next['id-doc']).toHaveLength(1);
		expect(next['proof-of-address']).toHaveLength(0);
	});

	it('walks an entry through waiting -> uploading -> uploaded', () => {
		let state = { 'id-doc': [entry()] };

		state = uploadReducer(state, {
			type: 'START_UPLOAD',
			key: 'id-doc',
			id: 'entry-1',
		});
		expect(state['id-doc'][0].status).toBe('uploading');

		state = uploadReducer(state, {
			type: 'PROGRESS',
			key: 'id-doc',
			id: 'entry-1',
			progress: 42,
		});
		expect(state['id-doc'][0].progress).toBe(42);

		state = uploadReducer(state, {
			type: 'SUCCESS',
			key: 'id-doc',
			id: 'entry-1',
			token: 'tok-123',
		});

		expect(state['id-doc'][0]).toMatchObject({
			status: 'uploaded',
			progress: 100,
			token: 'tok-123',
			error: null,
		});
	});

	it('FAILURE records the error and leaves other entries untouched', () => {
		let state = {
			'id-doc': [
				entry({ id: 'a', status: 'uploading' }),
				entry({ id: 'b', status: 'uploading' }),
			],
		};

		state = uploadReducer(state, {
			type: 'FAILURE',
			key: 'id-doc',
			id: 'a',
			error: 'This file is larger than the allowed maximum of 10 MB.',
		});

		expect(state['id-doc'][0]).toMatchObject({
			status: 'failed',
			error: 'This file is larger than the allowed maximum of 10 MB.',
		});
		expect(state['id-doc'][1].status).toBe('uploading');
	});

	it('RETRY-style re-run is just START_UPLOAD again after a failure', () => {
		let state = {
			'id-doc': [entry({ status: 'failed', error: 'nope' })],
		};

		state = uploadReducer(state, {
			type: 'START_UPLOAD',
			key: 'id-doc',
			id: 'entry-1',
		});

		expect(state['id-doc'][0]).toMatchObject({
			status: 'uploading',
			progress: 0,
			error: null,
		});
	});

	it('REMOVE marks the entry removed without deleting it', () => {
		let state = { 'id-doc': [entry({ status: 'uploaded' })] };

		state = uploadReducer(state, {
			type: 'REMOVE',
			key: 'id-doc',
			id: 'entry-1',
		});

		expect(state['id-doc']).toHaveLength(1);
		expect(state['id-doc'][0].status).toBe('removed');
	});

	it('RESET rebuilds the initial empty state', () => {
		const dirty = { 'id-doc': [entry()] };
		const next = uploadReducer(dirty, {
			type: 'RESET',
			requestedFiles: REQUESTED_FILES,
		});

		expect(next).toEqual(initialUploadState(REQUESTED_FILES));
	});

	it('ignores unknown action types', () => {
		const state = initialUploadState(REQUESTED_FILES);
		expect(uploadReducer(state, { type: 'NOPE' })).toBe(state);
	});
});

describe('selectors', () => {
	const state = {
		'id-doc': [entry({ id: 'a', status: 'uploaded', token: 't1' })],
		'proof-of-address': [
			entry({ id: 'b', status: 'uploaded', token: 't2' }),
			entry({ id: 'c', status: 'failed' }),
			entry({ id: 'd', status: 'removed' }),
		],
	};

	it('getUploadedEntries only returns uploaded entries across all slots', () => {
		expect(getUploadedEntries(state)).toHaveLength(2);
	});

	it('getUploadedTokens returns every uploaded token', () => {
		expect(getUploadedTokens(state)).toEqual(
			expect.arrayContaining(['t1', 't2'])
		);
	});

	it('hasUploadedFile is true only when a slot has an uploaded entry', () => {
		expect(hasUploadedFile(state, 'id-doc')).toBe(true);
		expect(hasUploadedFile(state, 'missing-key')).toBe(false);
	});

	it('countActiveEntries excludes removed entries', () => {
		expect(countActiveEntries(state, 'proof-of-address')).toBe(2);
	});
});
