/**
 * The public-facing "upload your files" app. One instance is mounted per
 * `.frm-frontend-root` element found on the page. Owns all state/handlers;
 * JSX composition is delegated to whichever layout is selected — see
 * src/shared/layoutRegistry.js.
 */

import { useState, useCallback, useMemo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { getLayout } from '../shared/layoutRegistry';
import StackedLayout from '../shared/layouts/StackedLayout';
import { useUploadManager } from '../shared/useUploadManager';
import { hasUploadedFile } from '../shared/uploadReducer';
import { createRestUploadAdapter } from '../shared/uploadAdapter';

export default function App({ requestData }) {
	const adapter = useMemo(
		() =>
			createRestUploadAdapter({
				restUrl: requestData.restUrl,
				requestId: requestData.id,
				nonce: requestData.nonce,
			}),
		[requestData.restUrl, requestData.id, requestData.nonce]
	);

	const { state, addFiles, retry, remove, uploadedTokens } = useUploadManager(
		requestData.requested_files,
		adapter
	);

	const [contactValues, setContactValues] = useState({});
	const [honeypot, setHoneypot] = useState('');
	const [attemptedSubmit, setAttemptedSubmit] = useState(false);
	const [submitting, setSubmitting] = useState(false);
	const [submitError, setSubmitError] = useState('');
	const [result, setResult] = useState(null);
	const [announcement, setAnnouncement] = useState('');

	const handleFilesSelected = useCallback(
		(requestedFile, files) => {
			addFiles(requestedFile, files);
			setAnnouncement(
				__('File added, uploading…', 'renevo-file-request-manager')
			);
		},
		[addFiles]
	);

	const missingRequired = (requestData.requested_files || []).filter(
		(file) => file.required && !hasUploadedFile(state, file.key)
	);

	const handleSubmit = async (event) => {
		event.preventDefault();
		setAttemptedSubmit(true);
		setSubmitError('');

		if (submitting) {
			return;
		}

		setSubmitting(true);

		try {
			const response = await adapter.submitRequest({
				website: honeypot,
				name: contactValues.name || '',
				email: contactValues.email || '',
				phone: contactValues.phone || '',
				company: contactValues.company || '',
				message: contactValues.message || '',
				tokens: uploadedTokens,
			});

			if (response.redirect_url) {
				window.location.href = response.redirect_url;
				return;
			}

			setResult(response);
			setAnnouncement(
				__('Submission received.', 'renevo-file-request-manager')
			);
		} catch (error) {
			const message =
				error && error.message
					? error.message
					: __(
							'Something went wrong. Please try again.',
							'renevo-file-request-manager'
						);
			setSubmitError(message);
			setAnnouncement(message);
		} finally {
			setSubmitting(false);
		}
	};

	if (result) {
		return (
			<div className="frm-success-screen">
				<h1>{__('Thank you', 'renevo-file-request-manager')}</h1>
				<p>{result.success_message}</p>
				{result.missing_files && result.missing_files.length > 0 ? (
					<p className="frm-success-screen__missing" role="alert">
						{__(
							'Please upload the following required file(s):',
							'renevo-file-request-manager'
						)}{' '}
						{result.missing_files.join(', ')}
					</p>
				) : null}
			</div>
		);
	}

	const formDesign = requestData.form_design || {};
	const Layout =
		getLayout(formDesign.layout) || getLayout('stacked') || StackedLayout;

	return (
		<>
			<Layout
				mode="live"
				requestData={requestData}
				uploadState={state}
				onFilesSelected={handleFilesSelected}
				onRetry={retry}
				onRemove={remove}
				missingRequired={missingRequired}
				attemptedSubmit={attemptedSubmit}
				contactValues={contactValues}
				onContactChange={(key, value) =>
					setContactValues((prev) => ({ ...prev, [key]: value }))
				}
				honeypotValue={honeypot}
				onHoneypotChange={setHoneypot}
				submitting={submitting}
				submitError={submitError}
				onSubmit={handleSubmit}
			/>
			<div className="frm-visually-hidden" aria-live="polite">
				{announcement}
			</div>
		</>
	);
}
