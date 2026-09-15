/**
 * Renders only the enabled contact fields (name/email/phone/company/message)
 * plus the always-present honeypot field. This is deliberately not a
 * generic field builder — the set of possible fields is fixed.
 */

import { __ } from '@wordpress/i18n';

const FIELD_CONFIG = {
	name: {
		type: 'text',
		defaultLabel: __('Name', 'renevo-file-request-manager'),
	},
	email: {
		type: 'email',
		defaultLabel: __('Email', 'renevo-file-request-manager'),
	},
	phone: {
		type: 'tel',
		defaultLabel: __('Phone', 'renevo-file-request-manager'),
	},
	company: {
		type: 'text',
		defaultLabel: __('Company', 'renevo-file-request-manager'),
	},
	message: {
		type: 'textarea',
		defaultLabel: __('Message', 'renevo-file-request-manager'),
	},
};

export default function ContactFields({
	contactFields,
	values,
	onChange,
	honeypotValue,
	onHoneypotChange,
	disabled = false,
	textLabels = {},
}) {
	const enabledKeys = Object.keys(FIELD_CONFIG).filter(
		(key) =>
			contactFields && contactFields[key] && contactFields[key].enabled
	);

	if (enabledKeys.length === 0) {
		return null;
	}

	return (
		<fieldset className="frm-contact-fields">
			<legend className="frm-contact-fields__legend">
				{textLabels.contact_heading ||
					__('Your details', 'renevo-file-request-manager')}
			</legend>

			{enabledKeys.map((key) => {
				const config = FIELD_CONFIG[key];
				const required = !!contactFields[key].required;
				const fieldId = `frm-contact-${key}`;
				const label = textLabels[`${key}_label`] || config.defaultLabel;

				return (
					<div className="frm-field" key={key}>
						<label htmlFor={fieldId} className="frm-field__label">
							{label}
							{required ? (
								<span
									className="frm-field__required-mark"
									aria-hidden="true"
								>
									{' *'}
								</span>
							) : null}
						</label>
						{config.type === 'textarea' ? (
							<textarea
								id={fieldId}
								name={key}
								className="frm-field__input"
								required={required}
								disabled={disabled}
								value={values[key] || ''}
								onChange={(event) =>
									onChange(key, event.target.value)
								}
								rows={4}
							/>
						) : (
							<input
								id={fieldId}
								name={key}
								type={config.type}
								className="frm-field__input"
								required={required}
								disabled={disabled}
								value={values[key] || ''}
								onChange={(event) =>
									onChange(key, event.target.value)
								}
							/>
						)}
					</div>
				);
			})}

			{/*
			 * Honeypot: visually hidden via CSS clipping (never
			 * display:none/type=hidden, which bots specifically skip), never
			 * pre-filled, and never shown to real users.
			 */}
			<div className="frm-honeypot" aria-hidden="true">
				<label htmlFor="frm-website">
					{__(
						'Leave this field empty',
						'renevo-file-request-manager'
					)}
				</label>
				<input
					id="frm-website"
					type="text"
					name="website"
					tabIndex={-1}
					autoComplete="off"
					value={honeypotValue || ''}
					onChange={(event) => onHoneypotChange(event.target.value)}
				/>
			</div>
		</fieldset>
	);
}
