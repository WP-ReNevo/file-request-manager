/**
 * Add/Edit modal for a single requested file.
 */

import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	Modal,
	TextControl,
	TextareaControl,
	ToggleControl,
	CheckboxControl,
	Button,
	Flex,
} from '@wordpress/components';

const BLANK = {
	title: '',
	description: '',
	required: true,
	allowed_types: [],
	max_size_mb: 10,
	max_files: 1,
};

export default function RequestedFileModal({
	initialValue,
	fileTypes,
	onSave,
	onClose,
}) {
	const [values, setValues] = useState({ ...BLANK, ...initialValue });

	const setField = (field, value) =>
		setValues((prev) => ({ ...prev, [field]: value }));

	const toggleType = (key, checked) => {
		setValues((prev) => ({
			...prev,
			allowed_types: checked
				? [...prev.allowed_types, key]
				: prev.allowed_types.filter((type) => type !== key),
		}));
	};

	const handleSave = () => {
		onSave({
			...values,
			max_size_mb: Math.max(1, Number(values.max_size_mb) || 1),
			max_files: Math.max(1, Number(values.max_files) || 1),
		});
	};

	return (
		<Modal
			title={
				initialValue
					? __('Edit requested file', 'renevo-file-request-manager')
					: __('Add requested file', 'renevo-file-request-manager')
			}
			onRequestClose={onClose}
			className="frm-requested-file-modal"
		>
			<TextControl
				label={__('Title', 'renevo-file-request-manager')}
				value={values.title}
				onChange={(value) => setField('title', value)}
			/>
			<TextareaControl
				label={__('Description', 'renevo-file-request-manager')}
				value={values.description}
				onChange={(value) => setField('description', value)}
				rows={3}
			/>
			<ToggleControl
				label={__('Required', 'renevo-file-request-manager')}
				checked={values.required}
				onChange={(value) => setField('required', value)}
			/>

			<fieldset className="frm-requested-file-modal__types">
				<legend>
					{__('Allowed file types', 'renevo-file-request-manager')}
				</legend>
				<div className="frm-requested-file-modal__types-grid">
					{Object.keys(fileTypes).map((key) => (
						<CheckboxControl
							key={key}
							label={fileTypes[key].label}
							checked={values.allowed_types.includes(key)}
							onChange={(checked) => toggleType(key, checked)}
						/>
					))}
				</div>
			</fieldset>

			<div className="frm-requested-file-modal__size-row">
				<TextControl
					type="number"
					min={1}
					label={__('Max size (MB)', 'renevo-file-request-manager')}
					value={values.max_size_mb}
					onChange={(value) => setField('max_size_mb', value)}
				/>
				<TextControl
					type="number"
					min={1}
					label={__(
						'Max number of files',
						'renevo-file-request-manager'
					)}
					value={values.max_files}
					onChange={(value) => setField('max_files', value)}
				/>
			</div>

			<Flex
				justify="flex-end"
				className="frm-requested-file-modal__actions"
			>
				<Button variant="tertiary" onClick={onClose}>
					{__('Cancel', 'renevo-file-request-manager')}
				</Button>
				<Button
					variant="primary"
					onClick={handleSave}
					disabled={!values.title}
				>
					{__('Save', 'renevo-file-request-manager')}
				</Button>
			</Flex>
		</Modal>
	);
}
