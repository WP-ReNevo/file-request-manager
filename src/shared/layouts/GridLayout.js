/**
 * "Grid" layout: requested files arranged as tiles. Nothing here is ever
 * hidden, so static preview renders the real structure too (read-only).
 */

import RequestHeader from '../RequestHeader';
import RequestedFileCard from '../RequestedFileCard';
import ContactFields from '../ContactFields';
import SubmitBar from '../SubmitBar';

export default function GridLayout({
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
	const contactFieldsOnTop =
		(requestData.submission_settings || {}).contact_fields_position !==
		'bottom';

	const requestedFilesBlock = (
		<div
			className="frm-requested-files frm-requested-files--grid"
			key="requested-files"
		>
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
	);

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
		return <div className="frm-frontend-app frm-grid">{content}</div>;
	}

	return (
		<form
			className="frm-frontend-app frm-grid"
			onSubmit={onSubmit}
			noValidate
		>
			{content}
		</form>
	);
}
