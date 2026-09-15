// "Text & Labels" tab: overrides for every fixed UI string on the public form.

import { __ } from '@wordpress/i18n';
import { Card, CardHeader, CardBody, TextControl } from '@wordpress/components';

export default function TextLabelsSection({ textLabels, dispatch }) {
	const setField = (key, value) =>
		dispatch({ type: 'SET_TEXT_LABEL', key, value });

	return (
		<div className="frm-text-labels-section">
			<Card>
				<CardHeader>
					<h3>{__('Contact form', 'renevo-file-request-manager')}</h3>
				</CardHeader>
				<CardBody>
					<TextControl
						label={__(
							'"Your details" heading',
							'renevo-file-request-manager'
						)}
						value={textLabels.contact_heading}
						onChange={(value) => setField('contact_heading', value)}
					/>
					<TextControl
						label={__(
							'Name field label',
							'renevo-file-request-manager'
						)}
						value={textLabels.name_label}
						onChange={(value) => setField('name_label', value)}
					/>
					<TextControl
						label={__(
							'Email field label',
							'renevo-file-request-manager'
						)}
						value={textLabels.email_label}
						onChange={(value) => setField('email_label', value)}
					/>
					<TextControl
						label={__(
							'Phone field label',
							'renevo-file-request-manager'
						)}
						value={textLabels.phone_label}
						onChange={(value) => setField('phone_label', value)}
					/>
					<TextControl
						label={__(
							'Company field label',
							'renevo-file-request-manager'
						)}
						value={textLabels.company_label}
						onChange={(value) => setField('company_label', value)}
					/>
					<TextControl
						label={__(
							'Message field label',
							'renevo-file-request-manager'
						)}
						value={textLabels.message_label}
						onChange={(value) => setField('message_label', value)}
					/>
					<TextControl
						label={__(
							'Submit button text',
							'renevo-file-request-manager'
						)}
						value={textLabels.submit_button_text}
						onChange={(value) =>
							setField('submit_button_text', value)
						}
					/>
				</CardBody>
			</Card>

			<Card>
				<CardHeader>
					<h3>
						{__(
							'Requested-file cards',
							'renevo-file-request-manager'
						)}
					</h3>
				</CardHeader>
				<CardBody>
					<TextControl
						label={__(
							'"Required" badge text',
							'renevo-file-request-manager'
						)}
						value={textLabels.required_label}
						onChange={(value) => setField('required_label', value)}
					/>
					<TextControl
						label={__(
							'"Optional" badge text',
							'renevo-file-request-manager'
						)}
						value={textLabels.optional_label}
						onChange={(value) => setField('optional_label', value)}
					/>
					<TextControl
						label={__(
							'Accepted file types text',
							'renevo-file-request-manager'
						)}
						value={textLabels.accepted_types_text}
						onChange={(value) =>
							setField('accepted_types_text', value)
						}
						help={
							/* translators: %s explanation. */ __(
								'%s is replaced with the list of accepted file types.',
								'renevo-file-request-manager'
							)
						}
					/>
					<TextControl
						label={__(
							'Maximum size text',
							'renevo-file-request-manager'
						)}
						value={textLabels.max_size_text}
						onChange={(value) => setField('max_size_text', value)}
						help={
							/* translators: %d explanation. */ __(
								'%d is replaced with the maximum size in MB.',
								'renevo-file-request-manager'
							)
						}
					/>
					<TextControl
						label={__(
							'File count text (one file)',
							'renevo-file-request-manager'
						)}
						value={textLabels.max_files_text}
						onChange={(value) => setField('max_files_text', value)}
						help={
							/* translators: %d explanation. */ __(
								'Used when the maximum is exactly 1 file. %d is replaced with the number.',
								'renevo-file-request-manager'
							)
						}
					/>
					<TextControl
						label={__(
							'File count text (multiple files)',
							'renevo-file-request-manager'
						)}
						value={textLabels.max_files_text_plural}
						onChange={(value) =>
							setField('max_files_text_plural', value)
						}
						help={
							/* translators: %d explanation. */ __(
								'Used when the maximum is more than 1 file. %d is replaced with the number.',
								'renevo-file-request-manager'
							)
						}
					/>
					<TextControl
						label={__(
							'Upload area placeholder text',
							'renevo-file-request-manager'
						)}
						value={textLabels.upload_area_text}
						onChange={(value) =>
							setField('upload_area_text', value)
						}
						help={__(
							'Shown only in non-interactive previews (this tab, the block editor).',
							'renevo-file-request-manager'
						)}
					/>
				</CardBody>
			</Card>
		</div>
	);
}
