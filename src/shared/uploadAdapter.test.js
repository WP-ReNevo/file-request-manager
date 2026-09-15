/**
 * Tests for the upload adapters — most importantly, that the mock adapter
 * used by the editor app's Preview panel never touches the network.
 */

import { createMockUploadAdapter } from './uploadAdapter';

describe('createMockUploadAdapter', () => {
	let fetchSpy;
	let xhrSpy;

	beforeEach(() => {
		jest.useFakeTimers();
		fetchSpy = jest.fn();
		global.fetch = fetchSpy;
		xhrSpy = jest.fn();
		global.XMLHttpRequest = xhrSpy;
	});

	afterEach(() => {
		jest.useRealTimers();
		delete global.fetch;
		delete global.XMLHttpRequest;
	});

	it('uploadFile() never calls fetch or XMLHttpRequest', async () => {
		const adapter = createMockUploadAdapter();
		const file = { name: 'photo.jpg', size: 2048 };

		const promise = adapter.uploadFile(file, 'id-doc', jest.fn());
		await jest.runAllTimersAsync();
		const result = await promise;

		expect(fetchSpy).not.toHaveBeenCalled();
		expect(xhrSpy).not.toHaveBeenCalled();
		expect(result).toMatchObject({
			original_filename: 'photo.jpg',
			size: 2048,
		});
		expect(typeof result.token).toBe('string');
	});

	it('uploadFile() reports progress up to 100 before resolving', async () => {
		const adapter = createMockUploadAdapter();
		const onProgress = jest.fn();

		const promise = adapter.uploadFile(
			{ name: 'a.pdf', size: 10 },
			'id-doc',
			onProgress
		);
		await jest.runAllTimersAsync();
		await promise;

		expect(onProgress).toHaveBeenCalled();
		const lastCallValue =
			onProgress.mock.calls[onProgress.mock.calls.length - 1][0];
		expect(lastCallValue).toBe(100);
	});

	it('submitRequest() never calls fetch and resolves without creating a submission', async () => {
		const adapter = createMockUploadAdapter();

		const promise = adapter.submitRequest({
			name: 'Jane',
			email: 'jane@example.com',
			tokens: [],
		});
		await jest.runAllTimersAsync();
		const result = await promise;

		expect(fetchSpy).not.toHaveBeenCalled();
		expect(result).toMatchObject({
			submission_code: 'PREVIEW',
			status: 'complete',
			missing_files: [],
		});
	});
});
