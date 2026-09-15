/**
 * Renders one requested-file slot: live (interactive upload) or static (read-only) mode.
 */

import { useState, useEffect } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { formatAllowedTypes, buildAcceptAttribute } from './fileTypeLabels';
import UploadDropzone from './UploadDropzone';

function TrashIcon() {
	return (
		<svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
			<path
				d="M4 7h16M9 7V5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2m-9 0 1 13a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1l1-13"
				stroke="currentColor"
				strokeWidth="1.6"
				strokeLinecap="round"
				strokeLinejoin="round"
			/>
		</svg>
	);
}

// Local, client-only image preview — the raw File object never leaves the
// browser for this; it's just an object URL for the thumbnail.
function useImagePreview(file) {
	const [url, setUrl] = useState(null);

	useEffect(() => {
		if (!file || !file.type || !file.type.startsWith('image/')) {
			setUrl(null);
			return undefined;
		}

		const objectUrl = URL.createObjectURL(file);
		setUrl(objectUrl);

		return () => URL.revokeObjectURL(objectUrl);
	}, [file]);

	return url;
}

// Admin-authored templates (not sprintf-extracted strings), so plain token replace.
function fillTemplate(template, value) {
	return template.replace('%s', value).replace('%d', value);
}

function formatSize(bytes) {
	if (!bytes && bytes !== 0) {
		return '';
	}
	if (bytes < 1024) {
		return `${bytes} B`;
	}
	if (bytes < 1024 * 1024) {
		return `${Math.round(bytes / 1024)} KB`;
	}
	return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

function statusLabel(status) {
	switch (status) {
		case 'waiting':
			return __('Waiting…', 'renevo-file-request-manager');
		case 'uploading':
			return __('Uploading…', 'renevo-file-request-manager');
		case 'uploaded':
			return __('Uploaded', 'renevo-file-request-manager');
		case 'failed':
			return __('Failed', 'renevo-file-request-manager');
		default:
			return '';
	}
}

function UploadEntryRow({ entry, onRetry, onRemove }) {
	const previewUrl = useImagePreview(entry.file);

	if (entry.status === 'removed') {
		return null;
	}

	return (
		<li className={`frm-file-entry frm-file-entry--${entry.status}`}>
			{previewUrl ? (
				<img
					className="frm-file-entry__thumb"
					src={previewUrl}
					alt=""
				/>
			) : (
				<span className="frm-file-entry__icon" aria-hidden="true">
					{entry.status === 'uploaded' && '✓'}
					{entry.status === 'failed' && '✗'}
					{(entry.status === 'waiting' ||
						entry.status === 'uploading') &&
						'…'}
				</span>
			)}
			<span className="frm-file-entry__body">
				<span className="frm-file-entry__name">{entry.name}</span>
				<span className="frm-file-entry__meta">
					{entry.status === 'uploading' ? (
						<span className="frm-file-entry__progress">
							<span
								className="frm-file-entry__progress-bar"
								style={{ width: `${entry.progress}%` }}
							/>
						</span>
					) : (
						<span className="frm-file-entry__status">
							{statusLabel(entry.status)}
							{entry.status === 'uploaded' &&
								` · ${formatSize(entry.size)}`}
						</span>
					)}
					{entry.status === 'failed' && entry.error ? (
						<span className="frm-file-entry__error">
							{entry.error}
						</span>
					) : null}
				</span>
				{entry.status === 'failed' ? (
					<span className="frm-file-entry__actions">
						<button
							type="button"
							className="frm-file-entry__retry"
							onClick={() => onRetry(entry.id)}
						>
							{__('Try again', 'renevo-file-request-manager')}
						</button>
					</span>
				) : null}
			</span>
			{entry.status !== 'uploading' ? (
				<button
					type="button"
					className="frm-file-entry__remove"
					onClick={() => onRemove(entry.id)}
					aria-label={sprintf(
						/* translators: %s: file name. */
						__('Remove %s', 'renevo-file-request-manager'),
						entry.name
					)}
				>
					<TrashIcon />
				</button>
			) : null}
		</li>
	);
}

export default function RequestedFileCard({
	requestedFile,
	mode = 'static',
	entries = [],
	onFilesSelected,
	onRetry,
	onRemove,
	showMissingWarning = false,
	uploadStyle = 'dropzone',
	infoPosition = 'above',
	textLabels = {},
	requiredIndicator = 'badge',
	showOptionalBadge = true,
}) {
	const isLive = mode === 'live';
	const visibleEntries = entries.filter(
		(entry) => entry.status !== 'removed'
	);
	const activeCount = visibleEntries.length;
	const roomLeft = requestedFile.max_files - activeCount;
	const typesHint = formatAllowedTypes(requestedFile.allowed_types);

	const requiredLabel =
		textLabels.required_label ||
		__('Required', 'renevo-file-request-manager');
	const optionalLabel =
		textLabels.optional_label ||
		__('Optional', 'renevo-file-request-manager');
	const acceptedTypesTemplate =
		textLabels.accepted_types_text ||
		/* translators: %s: comma separated list of file types, e.g. "PDF, JPG". */
		__('Accepted: %s', 'renevo-file-request-manager');
	const maxSizeTemplate =
		textLabels.max_size_text ||
		/* translators: %d: maximum file size in megabytes. */
		__('Maximum size: %d MB', 'renevo-file-request-manager');
	const maxFilesTemplate =
		requestedFile.max_files > 1
			? textLabels.max_files_text_plural ||
				/* translators: %d: maximum number of files. */
				__('Up to %d files', 'renevo-file-request-manager')
			: textLabels.max_files_text ||
				/* translators: %d: maximum number of files. */
				__('Up to %d file', 'renevo-file-request-manager');
	const uploadAreaText =
		textLabels.upload_area_text ||
		__('Upload area', 'renevo-file-request-manager');

	// A required file always shows something (badge or asterisk) — never
	// nothing — since "required" must stay visibly obvious.
	let requiredMark = null;
	if (requestedFile.required && requiredIndicator === 'asterisk') {
		requiredMark = (
			<span
				className="frm-requested-file__required-mark"
				aria-label={requiredLabel}
			>
				{' *'}
			</span>
		);
	} else if (requestedFile.required) {
		requiredMark = (
			<span className="frm-requested-file__required">
				{requiredLabel}
			</span>
		);
	} else if (showOptionalBadge) {
		requiredMark = (
			<span className="frm-requested-file__optional">
				{optionalLabel}
			</span>
		);
	}

	// Title (+ required/optional mark) and the accepted-types/size hints
	// always sit at the top, right under it — only the free-text description
	// below is repositionable via `infoPosition`.
	const titleBlock = (
		<div className="frm-requested-file__header" key="header">
			<h3 className="frm-requested-file__title">
				{requestedFile.title}
				{requiredMark}
			</h3>
			<p className="frm-requested-file__hints">
				{typesHint && fillTemplate(acceptedTypesTemplate, typesHint)}
				{typesHint && ' · '}
				{fillTemplate(maxSizeTemplate, requestedFile.max_size_mb)}
				{requestedFile.max_files > 1 &&
					' · ' +
						fillTemplate(maxFilesTemplate, requestedFile.max_files)}
			</p>
		</div>
	);

	const descriptionBlock = requestedFile.description ? (
		<p className="frm-requested-file__description" key="description">
			{requestedFile.description}
		</p>
	) : null;

	function renderUploadBlock() {
		if (isLive) {
			return (
				<div key="upload">
					{roomLeft > 0 ? (
						<UploadDropzone
							variant={uploadStyle}
							multiple={requestedFile.max_files > 1}
							accept={buildAcceptAttribute(
								requestedFile.allowed_types
							)}
							onFilesSelected={(files) =>
								onFilesSelected(requestedFile, files)
							}
							label={sprintf(
								/* translators: %s: requested file title. */
								__('Upload %s', 'renevo-file-request-manager'),
								requestedFile.title
							)}
						/>
					) : null}

					{visibleEntries.length > 0 ? (
						<ul className="frm-file-entry-list">
							{visibleEntries.map((entry) => (
								<UploadEntryRow
									key={entry.id}
									entry={entry}
									onRetry={(id) =>
										onRetry(requestedFile.key, id)
									}
									onRemove={(id) =>
										onRemove(requestedFile.key, id)
									}
								/>
							))}
						</ul>
					) : null}

					{showMissingWarning ? (
						<p className="frm-requested-file__warning" role="alert">
							{__(
								'This file is required but hasn’t been uploaded yet.',
								'renevo-file-request-manager'
							)}
						</p>
					) : null}
				</div>
			);
		}

		if (uploadStyle === 'button') {
			return (
				<input
					key="upload"
					type="file"
					className="frm-dropzone--button"
					disabled
					aria-label={__(
						'Choose a file',
						'renevo-file-request-manager'
					)}
				/>
			);
		}

		return (
			<div
				key="upload"
				className="frm-requested-file__static-placeholder"
			>
				{uploadAreaText}
			</div>
		);
	}

	const uploadBlock = renderUploadBlock();

	const middleBlocks =
		infoPosition === 'below'
			? [uploadBlock, descriptionBlock]
			: [descriptionBlock, uploadBlock];

	return (
		<div
			className="frm-requested-file"
			id={`frm-field-${requestedFile.key}`}
		>
			{titleBlock}
			{middleBlocks}
		</div>
	);
}
