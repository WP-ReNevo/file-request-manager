/**
 * Live-but-mocked preview of the public frontend, using the mock upload
 * adapter — the one surface where an admin can actually interact with the
 * real layout, nothing is ever sent to the server.
 */

import { useMemo, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Modal, Button, ButtonGroup } from '@wordpress/components';
import { getLayout } from '../../shared/layoutRegistry';
import StackedLayout from '../../shared/layouts/StackedLayout';
import { buildFormDesignVars } from '../../shared/formDesignStyle';
import { useUploadManager } from '../../shared/useUploadManager';
import { hasUploadedFile } from '../../shared/uploadReducer';
import { createMockUploadAdapter } from '../../shared/uploadAdapter';

export default function PreviewPanel({ state, onClose }) {
	const adapter = useMemo(() => createMockUploadAdapter(), []);
	const {
		state: uploadState,
		addFiles,
		retry,
		remove,
		uploadedTokens,
	} = useUploadManager(state.requested_files, adapter);
	const [width, setWidth] = useState('desktop');
	const [contactValues, setContactValues] = useState({});
	const [attemptedSubmit, setAttemptedSubmit] = useState(false);
	const [submitting, setSubmitting] = useState(false);
	const [result, setResult] = useState(null);

	const formDesign = state.form_design || {};
	const Layout = getLayout(formDesign.layout) || StackedLayout;

	const missingRequired = (state.requested_files || []).filter(
		(file) => file.required && !hasUploadedFile(uploadState, file.key)
	);

	const handleSubmit = async (event) => {
		event.preventDefault();
		setAttemptedSubmit(true);
		setSubmitting(true);

		const response = await adapter.submitRequest({
			website: '',
			...contactValues,
			tokens: uploadedTokens,
		});

		setSubmitting(false);
		setResult(response);
	};

	return (
		<Modal
			title={__('Preview', 'renevo-file-request-manager')}
			onRequestClose={onClose}
			isFullScreen
			className="frm-preview-panel"
		>
			<div className="frm-preview-panel__toolbar">
				<p className="frm-preview-panel__banner">
					{__(
						'Preview — submissions are disabled. Nothing here is sent to the server.',
						'renevo-file-request-manager'
					)}
				</p>
				<ButtonGroup>
					<Button
						variant={width === 'desktop' ? 'primary' : 'secondary'}
						onClick={() => setWidth('desktop')}
					>
						{__('Desktop', 'renevo-file-request-manager')}
					</Button>
					<Button
						variant={width === 'mobile' ? 'primary' : 'secondary'}
						onClick={() => setWidth('mobile')}
					>
						{__('Mobile', 'renevo-file-request-manager')}
					</Button>
				</ButtonGroup>
			</div>

			<div
				className={
					width === 'mobile'
						? 'frm-preview-panel__viewport frm-preview-panel__viewport--mobile'
						: 'frm-preview-panel__viewport'
				}
			>
				<div
					className="frm-frontend-root"
					data-frm-layout={formDesign.layout || 'stacked'}
					data-frm-file-wrap={formDesign.file_block_wrap || 'card'}
					data-frm-contact-wrap={
						formDesign.contact_fields_wrap || 'card'
					}
					style={buildFormDesignVars(formDesign)}
				>
					{result ? (
						<div className="frm-success-screen">
							<h1>
								{__('Thank you', 'renevo-file-request-manager')}
							</h1>
							<p>{result.success_message}</p>
						</div>
					) : (
						<Layout
							mode="live"
							requestData={state}
							uploadState={uploadState}
							onFilesSelected={(file, files) =>
								addFiles(file, files)
							}
							onRetry={retry}
							onRemove={remove}
							missingRequired={missingRequired}
							attemptedSubmit={attemptedSubmit}
							contactValues={contactValues}
							onContactChange={(key, value) =>
								setContactValues((prev) => ({
									...prev,
									[key]: value,
								}))
							}
							honeypotValue=""
							onHoneypotChange={() => {}}
							submitting={submitting}
							submitError=""
							onSubmit={handleSubmit}
						/>
					)}
				</div>
			</div>
		</Modal>
	);
}
