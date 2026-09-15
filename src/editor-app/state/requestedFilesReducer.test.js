/**
 * Tests for the "Files you need" builder's add/edit/duplicate/delete state
 * management, including key stability guarantees.
 */

import {
	requestedFilesReducer,
	createRequestedFile,
} from './requestedFilesReducer';

describe('createRequestedFile', () => {
	it('generates a key when none is provided', () => {
		const file = createRequestedFile();
		expect(typeof file.key).toBe('string');
		expect(file.key.length).toBeGreaterThan(0);
	});

	it('generates a different key on every call', () => {
		const a = createRequestedFile();
		const b = createRequestedFile();
		expect(a.key).not.toBe(b.key);
	});

	it('applies sensible defaults', () => {
		const file = createRequestedFile();
		expect(file).toMatchObject({
			title: '',
			required: true,
			allowed_types: [],
			max_size_mb: 10,
			max_files: 1,
		});
	});

	it('lets overrides win over defaults', () => {
		const file = createRequestedFile({ title: 'ID card', required: false });
		expect(file.title).toBe('ID card');
		expect(file.required).toBe(false);
	});
});

describe('requestedFilesReducer', () => {
	it('ADD appends a new file with a fresh key', () => {
		const files = requestedFilesReducer([], {
			type: 'ADD',
			file: { title: 'Identity document' },
		});

		expect(files).toHaveLength(1);
		expect(files[0].title).toBe('Identity document');
		expect(files[0].key).toBeTruthy();
	});

	it('UPDATE changes fields on the matching item only, and never its key', () => {
		const files = [
			{ key: 'a', title: 'One' },
			{ key: 'b', title: 'Two' },
		];

		const next = requestedFilesReducer(files, {
			type: 'UPDATE',
			key: 'a',
			changes: { title: 'Renamed', key: 'attempted-hijack' },
		});

		expect(next[0]).toEqual({ key: 'a', title: 'Renamed' });
		expect(next[1]).toEqual(files[1]);
	});

	it('DELETE removes only the matching item', () => {
		const files = [
			{ key: 'a', title: 'One' },
			{ key: 'b', title: 'Two' },
		];

		const next = requestedFilesReducer(files, {
			type: 'DELETE',
			key: 'a',
		});

		expect(next).toEqual([{ key: 'b', title: 'Two' }]);
	});

	it('DUPLICATE inserts a copy with a new key right after the original', () => {
		const files = [
			{ key: 'a', title: 'One' },
			{ key: 'b', title: 'Two' },
		];

		const next = requestedFilesReducer(files, {
			type: 'DUPLICATE',
			key: 'a',
		});

		expect(next).toHaveLength(3);
		expect(next[0].key).toBe('a');
		expect(next[1].title).toBe('One (copy)');
		expect(next[1].key).not.toBe('a');
		expect(next[2].key).toBe('b');
	});

	it('DUPLICATE is a no-op when the key does not exist', () => {
		const files = [{ key: 'a', title: 'One' }];
		const next = requestedFilesReducer(files, {
			type: 'DUPLICATE',
			key: 'missing',
		});
		expect(next).toBe(files);
	});

	it('REORDER moves an item from one index to another', () => {
		const files = [{ key: 'a' }, { key: 'b' }, { key: 'c' }];

		const next = requestedFilesReducer(files, {
			type: 'REORDER',
			fromIndex: 0,
			toIndex: 2,
		});

		expect(next.map((f) => f.key)).toEqual(['b', 'c', 'a']);
	});

	it('REORDER ignores out-of-range indexes', () => {
		const files = [{ key: 'a' }, { key: 'b' }];
		const next = requestedFilesReducer(files, {
			type: 'REORDER',
			fromIndex: 0,
			toIndex: 5,
		});
		expect(next).toBe(files);
	});

	it('preserves key stability across an update+duplicate+delete sequence', () => {
		let files = requestedFilesReducer([], {
			type: 'ADD',
			file: { title: 'Identity document' },
		});
		const originalKey = files[0].key;

		files = requestedFilesReducer(files, {
			type: 'UPDATE',
			key: originalKey,
			changes: { title: 'ID document (renamed)' },
		});
		expect(files[0].key).toBe(originalKey);

		files = requestedFilesReducer(files, {
			type: 'DUPLICATE',
			key: originalKey,
		});
		expect(files[0].key).toBe(originalKey);
		expect(files[1].key).not.toBe(originalKey);

		files = requestedFilesReducer(files, {
			type: 'DELETE',
			key: files[1].key,
		});
		expect(files).toHaveLength(1);
		expect(files[0].key).toBe(originalKey);
	});

	it('ignores unknown action types', () => {
		const files = [{ key: 'a' }];
		expect(requestedFilesReducer(files, { type: 'NOPE' })).toBe(files);
	});
});
