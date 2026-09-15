/**
 * A drag-and-drop + click-to-browse file dropzone, keyboard-operable.
 *
 * The "button" variant is deliberately a bare, unstyled native
 * `<input type="file">` — the browser's own file-picker button, not a
 * custom-colored control — since it exists specifically as the plain,
 * chrome-free alternative to the dropzone.
 */

import { useRef, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

export default function UploadDropzone({
	multiple,
	accept,
	onFilesSelected,
	disabled,
	label,
	variant = 'dropzone',
}) {
	const inputRef = useRef();
	const [isDragging, setIsDragging] = useState(false);

	const handleChange = (event) => {
		if (event.target.files && event.target.files.length) {
			onFilesSelected(event.target.files);
		}
		// Allow re-selecting the same file after a removal/retry.
		event.target.value = '';
	};

	if (variant === 'button') {
		return (
			<input
				type="file"
				className="frm-dropzone--button"
				multiple={!!multiple}
				accept={accept}
				disabled={disabled}
				onChange={handleChange}
				aria-label={
					label || __('Choose a file', 'renevo-file-request-manager')
				}
			/>
		);
	}

	const openPicker = () => {
		if (!disabled && inputRef.current) {
			inputRef.current.click();
		}
	};

	const handleKeyDown = (event) => {
		if (event.key === 'Enter' || event.key === ' ') {
			event.preventDefault();
			openPicker();
		}
	};

	const handleDrop = (event) => {
		event.preventDefault();
		setIsDragging(false);

		if (disabled) {
			return;
		}

		if (event.dataTransfer && event.dataTransfer.files.length) {
			onFilesSelected(event.dataTransfer.files);
		}
	};

	const classes = [
		'frm-dropzone',
		isDragging ? 'frm-dropzone--dragging' : '',
		disabled ? 'frm-dropzone--disabled' : '',
	]
		.filter(Boolean)
		.join(' ');

	return (
		<div
			className={classes}
			role="button"
			tabIndex={disabled ? -1 : 0}
			aria-disabled={disabled}
			aria-label={
				label ||
				__(
					'Click to browse or drag and drop a file here',
					'renevo-file-request-manager'
				)
			}
			onClick={openPicker}
			onKeyDown={handleKeyDown}
			onDragOver={(event) => {
				event.preventDefault();
				if (!disabled) {
					setIsDragging(true);
				}
			}}
			onDragLeave={() => setIsDragging(false)}
			onDrop={handleDrop}
		>
			<input
				ref={inputRef}
				type="file"
				className="frm-dropzone__input"
				multiple={!!multiple}
				accept={accept}
				disabled={disabled}
				onChange={handleChange}
				tabIndex={-1}
				aria-hidden="true"
			/>
			<span className="frm-dropzone__icon" aria-hidden="true">
				&#8593;
			</span>
			<span className="frm-dropzone__text">
				{__(
					'Drag and drop a file here, or',
					'renevo-file-request-manager'
				)}{' '}
				<span className="frm-dropzone__link">
					{__('browse', 'renevo-file-request-manager')}
				</span>
			</span>
		</div>
	);
}
