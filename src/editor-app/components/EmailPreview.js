/**
 * Live preview of the admin notification / requester confirmation email,
 * rendered from the same template as the real email (see
 * `shared/emailTemplate.js`) with realistic sample data standing in for
 * placeholders — not a live send, nothing ever leaves the browser.
 */

import { useMemo, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { TabPanel } from '@wordpress/components';
import { resolvePlaceholders } from '../../shared/placeholders';
import { buildEmailHtml } from '../../shared/emailTemplate';

function buildSampleContext() {
	const origin =
		typeof window !== 'undefined' && window.location
			? window.location.origin
			: 'https://example.com';

	return {
		name: 'Jane Doe',
		email: 'jane@example.com',
		phone: '+1 555 0100',
		company: 'Acme Inc.',
		message: 'Let me know if you need anything else.',
		submission_id: 'REQ-1042',
		submitted_at: new Date().toLocaleString(),
		request_url: `${origin}/documents-needed/`,
		submission_url: `${origin}/wp-admin/admin.php?page=renevo-submissions&view=submission&id=1042`,
	};
}

const SAMPLE_CONTEXT = buildSampleContext();

const TABS = [
	{
		name: 'admin',
		title: __('Admin notification', 'renevo-file-request-manager'),
	},
	{
		name: 'requester',
		title: __('Requester confirmation', 'renevo-file-request-manager'),
	},
];

export default function EmailPreview({
	notificationSettings,
	requestTitle,
	requestedFilesCount,
	siteName,
	faviconUrl,
}) {
	const [tab, setTab] = useState('admin');

	const context = useMemo(
		() => ({
			...SAMPLE_CONTEXT,
			request_title:
				requestTitle ||
				__('Untitled request', 'renevo-file-request-manager'),
			file_count: requestedFilesCount || 0,
		}),
		[requestTitle, requestedFilesCount]
	);

	const isAdmin = tab === 'admin';
	const subjectTemplate = isAdmin
		? notificationSettings.admin_subject
		: notificationSettings.requester_subject;
	const bodyTemplate = isAdmin
		? notificationSettings.admin_body
		: notificationSettings.requester_body;
	const enabled = isAdmin
		? notificationSettings.admin_notify_enabled
		: notificationSettings.requester_confirmation_enabled;

	const html = useMemo(
		() =>
			buildEmailHtml({
				siteName,
				faviconUrl,
				subject: resolvePlaceholders(subjectTemplate, context),
				body: resolvePlaceholders(bodyTemplate, context),
				buttonUrl: isAdmin ? context.submission_url : '',
				buttonLabel: __(
					'View submission',
					'renevo-file-request-manager'
				),
			}),
		[siteName, faviconUrl, subjectTemplate, bodyTemplate, context, isAdmin]
	);

	return (
		<div className="frm-live-preview">
			<TabPanel
				className="frm-live-preview__email-tabs"
				tabs={TABS}
				onSelect={setTab}
			>
				{() => null}
			</TabPanel>

			{!enabled ? (
				<p className="frm-live-preview__notice">
					{__(
						'This email is currently turned off — this is a preview of what it would look like if enabled.',
						'renevo-file-request-manager'
					)}
				</p>
			) : null}

			<iframe
				className="frm-live-preview__email-frame"
				title={__('Email preview', 'renevo-file-request-manager')}
				srcDoc={html}
			/>
		</div>
	);
}
