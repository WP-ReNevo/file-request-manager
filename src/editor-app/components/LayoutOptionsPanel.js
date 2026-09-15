// Layout-structural settings that apply regardless of which template was
// last picked.

import { __ } from '@wordpress/i18n';
import {
	Card,
	CardBody,
	CardHeader,
	RadioControl,
	ToggleControl,
} from '@wordpress/components';

export default function LayoutOptionsPanel({ formDesign, dispatch }) {
	const setField = (key, value) =>
		dispatch({ type: 'SET_FORM_DESIGN_FIELD', key, value });

	return (
		<Card className="frm-layout-options-panel">
			<CardHeader>
				<h3>{__('Layout options', 'renevo-file-request-manager')}</h3>
			</CardHeader>
			<CardBody>
				<RadioControl
					label={__(
						'Requested-file blocks',
						'renevo-file-request-manager'
					)}
					selected={formDesign.file_block_wrap}
					options={[
						{
							value: 'card',
							label: __(
								'Bordered box (default)',
								'renevo-file-request-manager'
							),
						},
						{
							value: 'flat',
							label: __(
								'Flat, no border',
								'renevo-file-request-manager'
							),
						},
					]}
					onChange={(value) => setField('file_block_wrap', value)}
				/>

				<RadioControl
					label={__(
						'"Your details" block',
						'renevo-file-request-manager'
					)}
					selected={formDesign.contact_fields_wrap}
					options={[
						{
							value: 'card',
							label: __(
								'Bordered box (default)',
								'renevo-file-request-manager'
							),
						},
						{
							value: 'flat',
							label: __(
								'Flat, no border',
								'renevo-file-request-manager'
							),
						},
					]}
					onChange={(value) => setField('contact_fields_wrap', value)}
				/>

				<RadioControl
					label={__(
						'"Required" indicator',
						'renevo-file-request-manager'
					)}
					help={__(
						'A required file always shows one of these — it can never be hidden entirely.',
						'renevo-file-request-manager'
					)}
					selected={formDesign.required_indicator}
					options={[
						{
							value: 'badge',
							label: __(
								'Badge (default)',
								'renevo-file-request-manager'
							),
						},
						{
							value: 'asterisk',
							label: __(
								'Asterisk (*)',
								'renevo-file-request-manager'
							),
						},
					]}
					onChange={(value) => setField('required_indicator', value)}
				/>

				<ToggleControl
					label={__(
						'Show "Optional" badge',
						'renevo-file-request-manager'
					)}
					checked={formDesign.show_optional_badge}
					onChange={(value) => setField('show_optional_badge', value)}
				/>
			</CardBody>
		</Card>
	);
}
