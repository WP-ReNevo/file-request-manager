/**
 * The submit button + inline "missing required files" warning (never blocks submit).
 */

import { __, sprintf } from '@wordpress/i18n';

export default function SubmitBar({
	submitting,
	missingRequiredTitles = [],
	disabled = false,
	submitText,
}) {
	return (
		<div className="frm-submit-bar">
			{missingRequiredTitles.length > 0 ? (
				<p className="frm-submit-bar__warning" role="alert">
					{sprintf(
						/* translators: %s: comma separated list of missing required file titles. */
						__(
							'Please upload the following required file(s): %s',
							'renevo-file-request-manager'
						),
						missingRequiredTitles.join(', ')
					)}
				</p>
			) : null}
			{/*
			 * type="submit" so it participates in the enclosing <form>'s
			 * native validation (required fields) and onSubmit handler —
			 * it deliberately has no click handler of its own so a
			 * submission is never triggered twice.
			 */}
			<button
				type="submit"
				className="frm-submit-bar__button"
				disabled={disabled || submitting}
			>
				{submitting
					? __('Submitting…', 'renevo-file-request-manager')
					: submitText || __('Submit', 'renevo-file-request-manager')}
			</button>
		</div>
	);
}
