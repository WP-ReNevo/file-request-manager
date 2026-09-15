/**
 * The main editor: tabbed sections + the publish bar + the Preview panel.
 */

import { Fragment, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { Request } from '../state/Request';
import { restPath } from '../../shared/restNamespace';
import RequestDetails from './RequestDetails';
import RequestedFilesBuilder from './RequestedFilesBuilder';
import ContactFieldsSection from './ContactFieldsSection';
import SubmissionSettingsSection from './SubmissionSettingsSection';
import NotificationsSection from './NotificationsSection';
import FormDesignSection from './FormDesignSection';
import TextLabelsSection from './TextLabelsSection';
import PublishBar from './PublishBar';
import PreviewPanel from './PreviewPanel';
import EditorLivePreview from './EditorLivePreview';

const TABS = [
	{
		name: 'details',
		title: __('Request details', 'renevo-file-request-manager'),
	},
	{
		name: 'files',
		title: __('Files you need', 'renevo-file-request-manager'),
	},
	{
		name: 'contact',
		title: __('Your contact details', 'renevo-file-request-manager'),
	},
	{
		name: 'submission',
		title: __('Submission settings', 'renevo-file-request-manager'),
	},
	{ name: 'design', title: __('Form Design', 'renevo-file-request-manager') },
	{
		name: 'text',
		title: __('Text & Labels', 'renevo-file-request-manager'),
	},
	{
		name: 'notifications',
		title: __('Notifications', 'renevo-file-request-manager'),
	},
];

export default function EditorScreen({
	state,
	dispatch,
	requestsUrl,
	siteName,
	faviconUrl,
	formDesignPresets,
	formLayouts,
}) {
	const [saving, setSaving] = useState(null);
	const [saveError, setSaveError] = useState('');
	const [saveNotice, setSaveNotice] = useState('');
	const [previewOpen, setPreviewOpen] = useState(false);
	const [activeTab, setActiveTab] = useState('details');

	const handleSave = async (targetStatus) => {
		setSaving(targetStatus);
		setSaveError('');
		setSaveNotice('');

		const payload = { ...state, status: targetStatus };

		try {
			const path = restPath(
				state.id ? `/requests/${state.id}` : '/requests'
			);
			const method = state.id ? 'PUT' : 'POST';
			const response = await apiFetch({ path, method, data: payload });

			dispatch({ type: 'REPLACE', state: Request.fromServer(response) });

			if (response.id && window.history && window.history.replaceState) {
				const url = new URL(window.location.href);
				url.searchParams.set('id', response.id);
				window.history.replaceState({}, '', url.toString());
			}

			setSaveNotice(
				targetStatus === 'publish'
					? __('Published.', 'renevo-file-request-manager')
					: __('Draft saved.', 'renevo-file-request-manager')
			);
		} catch (error) {
			setSaveError(
				(error && error.message) ||
					__(
						'Could not save. Please try again.',
						'renevo-file-request-manager'
					)
			);
		} finally {
			setSaving(null);
		}
	};

	function renderActiveTab() {
		switch (activeTab) {
			case 'files':
				return (
					<RequestedFilesBuilder
						requestedFiles={state.requested_files}
						dispatch={dispatch}
					/>
				);
			case 'contact':
				return (
					<ContactFieldsSection
						contactFields={state.contact_fields}
						submissionSettings={state.submission_settings}
						dispatch={dispatch}
					/>
				);
			case 'submission':
				return (
					<SubmissionSettingsSection
						submissionSettings={state.submission_settings}
						dispatch={dispatch}
					/>
				);
			case 'design':
				return (
					<FormDesignSection
						formDesign={state.form_design}
						presets={formDesignPresets}
						layouts={formLayouts}
						dispatch={dispatch}
					/>
				);
			case 'text':
				return (
					<TextLabelsSection
						textLabels={state.text_labels}
						dispatch={dispatch}
					/>
				);
			case 'notifications':
				return (
					<NotificationsSection
						notificationSettings={state.notification_settings}
						dispatch={dispatch}
					/>
				);
			case 'details':
			default:
				return (
					<RequestDetails
						title={state.title}
						description={state.description}
						dispatch={dispatch}
					/>
				);
		}
	}

	return (
		<Fragment>
			<PublishBar
				title={state.title}
				status={state.status}
				saving={saving}
				saveError={saveError}
				saveNotice={saveNotice}
				onSaveDraft={() => handleSave('draft')}
				onPublish={() => handleSave('publish')}
				onPreview={() => setPreviewOpen(true)}
				backUrl={requestsUrl}
			/>

			<div className="frm-editor-screen">
				{/* Full width, above both the tab content and the live preview
				    columns, so it has room for every tab without needing to
				    horizontally scroll. */}
				<div className="frm-editor-screen__tabbar" role="tablist">
					{TABS.map((tab) => (
						<button
							key={tab.name}
							type="button"
							role="tab"
							aria-selected={activeTab === tab.name}
							className={
								activeTab === tab.name
									? 'frm-editor-screen__tab frm-editor-screen__tab--active'
									: 'frm-editor-screen__tab'
							}
							onClick={() => setActiveTab(tab.name)}
						>
							{tab.title}
						</button>
					))}
				</div>

				<div className="frm-editor-screen__body">
					<div className="frm-editor-screen__tab-content">
						{renderActiveTab()}
					</div>

					<EditorLivePreview
						state={state}
						activeTab={activeTab}
						siteName={siteName}
						faviconUrl={faviconUrl}
					/>
				</div>

				{previewOpen ? (
					<PreviewPanel
						state={state}
						onClose={() => setPreviewOpen(false)}
					/>
				) : null}
			</div>
		</Fragment>
	);
}
