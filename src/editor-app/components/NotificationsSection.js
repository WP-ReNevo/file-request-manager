/**
 * "Notifications" tab: admin notification + requester confirmation email.
 */

import { useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	Card,
	CardBody,
	CardHeader,
	TextControl,
	TextareaControl,
	ToggleControl,
	Button,
} from '@wordpress/components';

const PLACEHOLDER_TOKENS = [
	'{name}',
	'{email}',
	'{phone}',
	'{company}',
	'{message}',
	'{request_title}',
	'{submission_id}',
	'{submitted_at}',
	'{file_count}',
	'{request_url}',
	'{submission_url}',
];

// Inserts `token` at the textarea's current cursor position (replacing any
// selection), then restores focus/cursor after the controlled re-render.
function insertAtCursor(textareaEl, value, token, onChange) {
	const start = textareaEl ? textareaEl.selectionStart : value.length;
	const end = textareaEl ? textareaEl.selectionEnd : value.length;
	const next = value.slice(0, start) + token + value.slice(end);

	onChange(next);

	if (!textareaEl) {
		return;
	}

	requestAnimationFrame(() => {
		const cursor = start + token.length;
		textareaEl.focus();
		textareaEl.setSelectionRange(cursor, cursor);
	});
}

function PlaceholderChips({ textareaRef, value, onChange }) {
	const handleClick = (token) => {
		insertAtCursor(textareaRef.current, value, token, onChange);

		if (navigator.clipboard) {
			navigator.clipboard.writeText(token).catch(() => {});
		}
	};

	return (
		<div className="frm-placeholder-chips">
			<span className="frm-placeholder-chips__label">
				{__('Available placeholders:', 'renevo-file-request-manager')}
			</span>
			{PLACEHOLDER_TOKENS.map((token) => (
				<Button
					key={token}
					variant="secondary"
					size="small"
					className="frm-placeholder-chip"
					onClick={() => handleClick(token)}
					title={__(
						'Insert into the body above (also copied to clipboard)',
						'renevo-file-request-manager'
					)}
				>
					{token}
				</Button>
			))}
		</div>
	);
}

export default function NotificationsSection({
	notificationSettings,
	dispatch,
}) {
	const adminBodyRef = useRef(null);
	const requesterBodyRef = useRef(null);

	const setField = (key, value) =>
		dispatch({ type: 'SET_NOTIFICATION_SETTING', key, value });

	return (
		<div className="frm-notifications-section">
			<Card>
				<CardHeader>
					<h3>
						{__(
							'Admin notification',
							'renevo-file-request-manager'
						)}
					</h3>
				</CardHeader>
				<CardBody>
					<ToggleControl
						label={__(
							'Notify an admin on new submissions',
							'renevo-file-request-manager'
						)}
						checked={notificationSettings.admin_notify_enabled}
						onChange={(value) =>
							setField('admin_notify_enabled', value)
						}
					/>
					<TextControl
						label={__('Admin email', 'renevo-file-request-manager')}
						type="email"
						value={notificationSettings.admin_email}
						onChange={(value) => setField('admin_email', value)}
					/>
					<TextControl
						label={__('Subject', 'renevo-file-request-manager')}
						value={notificationSettings.admin_subject}
						onChange={(value) => setField('admin_subject', value)}
					/>
					<TextareaControl
						ref={adminBodyRef}
						label={__('Body', 'renevo-file-request-manager')}
						value={notificationSettings.admin_body}
						onChange={(value) => setField('admin_body', value)}
						rows={6}
					/>
					<PlaceholderChips
						textareaRef={adminBodyRef}
						value={notificationSettings.admin_body}
						onChange={(value) => setField('admin_body', value)}
					/>
				</CardBody>
			</Card>

			<Card>
				<CardHeader>
					<h3>
						{__(
							'Requester confirmation',
							'renevo-file-request-manager'
						)}
					</h3>
				</CardHeader>
				<CardBody>
					<ToggleControl
						label={__(
							'Send a confirmation email to the requester',
							'renevo-file-request-manager'
						)}
						checked={
							notificationSettings.requester_confirmation_enabled
						}
						onChange={(value) =>
							setField('requester_confirmation_enabled', value)
						}
					/>
					<div className="frm-notifications-section__row">
						<TextControl
							label={__(
								'From name',
								'renevo-file-request-manager'
							)}
							value={notificationSettings.requester_from_name}
							onChange={(value) =>
								setField('requester_from_name', value)
							}
						/>
						<TextControl
							label={__(
								'From email',
								'renevo-file-request-manager'
							)}
							type="email"
							value={notificationSettings.requester_from_email}
							onChange={(value) =>
								setField('requester_from_email', value)
							}
						/>
					</div>
					<TextControl
						label={__('Subject', 'renevo-file-request-manager')}
						value={notificationSettings.requester_subject}
						onChange={(value) =>
							setField('requester_subject', value)
						}
					/>
					<TextareaControl
						ref={requesterBodyRef}
						label={__('Body', 'renevo-file-request-manager')}
						value={notificationSettings.requester_body}
						onChange={(value) => setField('requester_body', value)}
						rows={6}
					/>
					<PlaceholderChips
						textareaRef={requesterBodyRef}
						value={notificationSettings.requester_body}
						onChange={(value) => setField('requester_body', value)}
					/>
				</CardBody>
			</Card>
		</div>
	);
}
