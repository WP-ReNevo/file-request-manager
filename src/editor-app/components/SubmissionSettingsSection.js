/**
 * "Submission settings" tab.
 */

import { __ } from '@wordpress/i18n';
import {
	Card,
	CardBody,
	TextControl,
	TextareaControl,
	ToggleControl,
} from '@wordpress/components';

export default function SubmissionSettingsSection({
	submissionSettings,
	dispatch,
}) {
	const setField = (key, value) =>
		dispatch({ type: 'SET_SUBMISSION_SETTING', key, value });

	return (
		<Card>
			<CardBody>
				<TextareaControl
					label={__('Success message', 'renevo-file-request-manager')}
					value={submissionSettings.success_message}
					onChange={(value) => setField('success_message', value)}
					help={__(
						'Shown after a successful submission (unless a redirect URL is set below).',
						'renevo-file-request-manager'
					)}
					rows={3}
				/>
				<TextControl
					label={__(
						'Redirect URL (optional)',
						'renevo-file-request-manager'
					)}
					value={submissionSettings.redirect_url}
					onChange={(value) => setField('redirect_url', value)}
					help={__(
						'If set, the visitor is redirected here instead of seeing the success message.',
						'renevo-file-request-manager'
					)}
					type="url"
				/>
				<ToggleControl
					label={__(
						'Allow multiple submissions',
						'renevo-file-request-manager'
					)}
					checked={submissionSettings.allow_multiple_submissions}
					onChange={(value) =>
						setField('allow_multiple_submissions', value)
					}
					help={__(
						'When off, a second submission from the same email address is rejected.',
						'renevo-file-request-manager'
					)}
				/>
			</CardBody>
		</Card>
	);
}
