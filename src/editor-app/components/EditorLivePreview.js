/**
 * Persistent right-column preview shown alongside every editor tab: the
 * live public-page preview (reusing the same layout components the real
 * frontend uses, in non-interactive "static" mode) for every tab except
 * Notifications, which shows the email preview instead.
 */

import { __ } from '@wordpress/i18n';
import { getLayout } from '../../shared/layoutRegistry';
import StackedLayout from '../../shared/layouts/StackedLayout';
import { buildFormDesignVars } from '../../shared/formDesignStyle';
import EmailPreview from './EmailPreview';

const noop = () => {};

export default function EditorLivePreview({
	state,
	activeTab,
	siteName,
	faviconUrl,
}) {
	if (activeTab === 'notifications') {
		return (
			<EmailPreview
				notificationSettings={state.notification_settings}
				requestTitle={state.title}
				requestedFilesCount={state.requested_files.length}
				siteName={siteName}
				faviconUrl={faviconUrl}
			/>
		);
	}

	const formDesign = state.form_design || {};
	const Layout = getLayout(formDesign.layout) || StackedLayout;

	const previewRequestData = {
		...state,
		title:
			state.title ||
			__('Untitled request', 'renevo-file-request-manager'),
	};

	return (
		<div className="frm-live-preview">
			<div
				className="frm-frontend-root frm-live-preview__frame"
				data-frm-layout={formDesign.layout || 'stacked'}
				data-frm-file-wrap={formDesign.file_block_wrap || 'card'}
				data-frm-contact-wrap={formDesign.contact_fields_wrap || 'card'}
				style={buildFormDesignVars(formDesign)}
			>
				<Layout
					mode="static"
					requestData={previewRequestData}
					uploadState={{}}
					onFilesSelected={noop}
					onRetry={noop}
					onRemove={noop}
					missingRequired={[]}
					attemptedSubmit={false}
					contactValues={{}}
					onContactChange={noop}
					honeypotValue=""
					onHoneypotChange={noop}
					submitting={false}
					submitError=""
					onSubmit={noop}
				/>
			</div>
		</div>
	);
}
