/**
 * "Stacked" layout: one column, requested files top to bottom. The default,
 * and the base every other built-in layout borrows pieces from.
 */

import RequestHeader from '../RequestHeader';
import RequestedFileCard from '../RequestedFileCard';
import ContactFields from '../ContactFields';
import SubmitBar from '../SubmitBar';

export default function StackedLayout({
	mode,
	requestData,
	uploadState,
	onFilesSelected,
	onRetry,
	onRemove,
	missingRequired,
	attemptedSubmit,
	contactValues,
	onContactChange,
	honeypotValue,
	onHoneypotChange,
	submitting,
	submitError,
	onSubmit,
}) {
	const isLive = mode === 'live';
	const formDesign = requestData.form_design || {};
	const textLabels = requestData.text_labels || {};

	const requestedFilesBlock =
		(requestData.requested_files || []).length > 0 ? (
			<div className="frm-requested-files" key="requested-files">
				{(requestData.requested_files || []).map((requestedFile) => (
					<RequestedFileCard
						key={requestedFile.key}
						requestedFile={requestedFile}
						mode={mode}
						entries={(uploadState || {})[requestedFile.key] || []}
						onFilesSelected={onFilesSelected}
						onRetry={onRetry}
						onRemove={onRemove}
						showMissingWarning={
							isLive &&
							attemptedSubmit &&
							missingRequired.some(
								(file) => file.key === requestedFile.key
							)
						}
						uploadStyle={formDesign.upload_style}
						infoPosition={formDesign.file_info_position}
						textLabels={textLabels}
						requiredIndicator={formDesign.required_indicator}
						showOptionalBadge={formDesign.show_optional_badge}
					/>
				))}
			</div>
		) : null;

	const contactFieldsBlock = (
		<ContactFields
			key="contact-fields"
			contactFields={requestData.contact_fields}
			values={contactValues}
			onChange={onContactChange}
			honeypotValue={honeypotValue}
			onHoneypotChange={onHoneypotChange}
			disabled={!isLive || submitting}
			textLabels={textLabels}
		/>
	);

	const contactFieldsOnTop =
		(requestData.submission_settings || {}).contact_fields_position !==
		'bottom';

	const content = (
		<>
			<RequestHeader
				title={requestData.title}
				description={requestData.description}
			/>

			{contactFieldsOnTop ? contactFieldsBlock : null}
			{requestedFilesBlock}
			{contactFieldsOnTop ? null : contactFieldsBlock}

			{isLive && submitError ? (
				<p className="frm-frontend-app__error" role="alert">
					{submitError}
				</p>
			) : null}

			<SubmitBar
				submitting={submitting}
				disabled={!isLive}
				missingRequiredTitles={
					isLive && attemptedSubmit
						? missingRequired.map((file) => file.title)
						: []
				}
				submitText={textLabels.submit_button_text}
			/>
		</>
	);

	if (!isLive) {
		return content;
	}

	return (
		<form className="frm-frontend-app" onSubmit={onSubmit} noValidate>
			{content}
		</form>
	);
}
