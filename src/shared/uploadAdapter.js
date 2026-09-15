/**
 * Upload adapters: `createRestUploadAdapter()` (real REST calls),
 * `createMockUploadAdapter()` (timer-simulated, no network), or `null` (static preview).
 */

/**
 * Parses a WP REST JSON error body into `{ message, code }`.
 *
 * @param {Response} response Fetch response.
 * @return {Promise<{message: string, code: string}>} The parsed error.
 */
async function extractErrorBody(response) {
	try {
		const body = await response.json();
		if (body && body.message) {
			return { message: body.message, code: body.code };
		}
	} catch (e) {
		// Body wasn't JSON; fall through to the generic message below.
	}

	return {
		message:
			response.statusText || 'Something went wrong. Please try again.',
		code: '',
	};
}

/**
 * Real, network-backed upload adapter. On `renevo_expired` it refreshes the
 * nonce and retries once instead of dead-ending the whole form.
 *
 * @param {Object} args           Arguments.
 * @param {string} args.restUrl   Base REST URL (already namespaced).
 * @param {number} args.requestId The request's post ID.
 * @param {string} args.nonce     The per-request public nonce.
 * @return {Object} Adapter.
 */
export function createRestUploadAdapter({ restUrl, requestId, nonce }) {
	const base = restUrl.replace(/\/$/, '');
	let currentNonce = nonce;

	async function refreshNonce() {
		try {
			const response = await fetch(
				`${base}/public/requests/${requestId}`
			);
			if (!response.ok) {
				return false;
			}
			const data = await response.json();
			if (data && data.nonce) {
				currentNonce = data.nonce;
				return true;
			}
		} catch (e) {
			// Network error while refreshing — the caller surfaces the
			// original failure instead.
		}
		return false;
	}

	function uploadOnce(file, requestedFileKey, onProgress) {
		return new Promise((resolve, reject) => {
			const formData = new FormData();
			formData.append('file', file);
			formData.append('requested_file_key', requestedFileKey);
			formData.append('nonce', currentNonce);
			formData.append('website', '');

			const xhr = new XMLHttpRequest();
			xhr.open('POST', `${base}/public/requests/${requestId}/upload`);

			if (xhr.upload && typeof onProgress === 'function') {
				xhr.upload.addEventListener('progress', (event) => {
					if (event.lengthComputable) {
						onProgress(
							Math.round((event.loaded / event.total) * 100)
						);
					}
				});
			}

			xhr.addEventListener('load', () => {
				let body = {};
				try {
					body = JSON.parse(xhr.responseText);
				} catch (e) {
					// Ignore, handled by the status check below.
				}

				if (xhr.status >= 200 && xhr.status < 300) {
					resolve(body);
				} else {
					const error = new Error(
						(body && body.message) ||
							'The upload failed. Please try again.'
					);
					error.code = body && body.code;
					reject(error);
				}
			});

			xhr.addEventListener('error', () => {
				reject(
					new Error(
						'The upload failed. Please check your connection and try again.'
					)
				);
			});

			xhr.send(formData);
		});
	}

	async function submitOnce(payload) {
		const response = await fetch(
			`${base}/public/requests/${requestId}/submit`,
			{
				method: 'POST',
				headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify({ ...payload, nonce: currentNonce }),
			}
		);

		if (!response.ok) {
			const { message, code } = await extractErrorBody(response);
			const error = new Error(message);
			error.code = code;
			throw error;
		}

		return response.json();
	}

	return {
		/**
		 * Uploads a single file for one requested-file slot.
		 *
		 * @param {File}     file             The file to upload.
		 * @param {string}   requestedFileKey The requested file's stable key.
		 * @param {Function} [onProgress]     Optional `(percent:number) => void`.
		 * @return {Promise<{token:string,original_filename:string,size:number}>} Resolves with the stored upload's token and metadata.
		 */
		async uploadFile(file, requestedFileKey, onProgress) {
			try {
				return await uploadOnce(file, requestedFileKey, onProgress);
			} catch (error) {
				if (error.code === 'renevo_expired' && (await refreshNonce())) {
					return uploadOnce(file, requestedFileKey, onProgress);
				}
				throw error;
			}
		},

		/**
		 * Submits the final contact info + uploaded file tokens.
		 *
		 * @param {Object} payload {name, email, phone, company, message, website, tokens}.
		 * @return {Promise<Object>} The server's submission result.
		 */
		async submitRequest(payload) {
			try {
				return await submitOnce(payload);
			} catch (error) {
				if (error.code === 'renevo_expired' && (await refreshNonce())) {
					return submitOnce(payload);
				}
				throw error;
			}
		},
	};
}

let mockTokenCounter = 0;

/**
 * Creates a mock upload adapter that never touches the network (used by the Preview panel).
 *
 * @return {Object} Adapter.
 */
export function createMockUploadAdapter() {
	return {
		uploadFile(file, requestedFileKey, onProgress) {
			return new Promise((resolve) => {
				let progress = 0;

				const tick = () => {
					progress = Math.min(100, progress + 20);

					if (typeof onProgress === 'function') {
						onProgress(progress);
					}

					if (progress >= 100) {
						mockTokenCounter += 1;
						resolve({
							token: `preview-token-${mockTokenCounter}`,
							original_filename: file.name,
							size: file.size,
						});
						return;
					}

					setTimeout(tick, 120);
				};

				setTimeout(tick, 120);
			});
		},

		submitRequest() {
			return new Promise((resolve) => {
				setTimeout(() => {
					resolve({
						submission_code: 'PREVIEW',
						status: 'complete',
						missing_files: [],
						success_message:
							'This is a preview — nothing was submitted.',
						redirect_url: '',
					});
				}, 300);
			});
		},
	};
}
