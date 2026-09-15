// Manual design controls, always visible — a template just pre-fills these
// values once; every value stays editable afterward. Stable
// @wordpress/components only — no __experimental* controls, since this
// plugin supports WP 6.4+.

import { __ } from '@wordpress/i18n';
import {
	Card,
	CardBody,
	CardHeader,
	RangeControl,
	RadioControl,
	SelectControl,
} from '@wordpress/components';
import ColorField from './ColorField';

const FONT_FAMILY_OPTIONS = [
	{ value: 'system', label: __('System UI', 'renevo-file-request-manager') },
	{ value: 'sans', label: __('Sans-serif', 'renevo-file-request-manager') },
	{ value: 'serif', label: __('Serif', 'renevo-file-request-manager') },
	{
		value: 'monospace',
		label: __('Monospace', 'renevo-file-request-manager'),
	},
];

export default function DesignOptionsPanel({ formDesign, dispatch }) {
	const setField = (key, value) =>
		dispatch({ type: 'SET_FORM_DESIGN_FIELD', key, value });

	return (
		<div className="frm-custom-design-panel">
			<Card>
				<CardHeader>
					<h3>{__('Colors', 'renevo-file-request-manager')}</h3>
				</CardHeader>
				<CardBody className="frm-custom-design-panel__colors">
					<ColorField
						id="frm-design-page-background-color"
						label={__(
							'Overall background color',
							'renevo-file-request-manager'
						)}
						value={formDesign.page_background_color || '#ffffff'}
						onChange={(value) =>
							setField('page_background_color', value)
						}
					/>
					<ColorField
						id="frm-design-background-color"
						label={__(
							'Background color',
							'renevo-file-request-manager'
						)}
						value={formDesign.background_color}
						onChange={(value) =>
							setField('background_color', value)
						}
					/>
					<ColorField
						id="frm-design-border-color"
						label={__(
							'Border color',
							'renevo-file-request-manager'
						)}
						value={formDesign.border_color}
						onChange={(value) => setField('border_color', value)}
					/>
					<ColorField
						id="frm-design-hover-color"
						label={__('Hover color', 'renevo-file-request-manager')}
						value={formDesign.hover_color}
						onChange={(value) => setField('hover_color', value)}
					/>
					<ColorField
						id="frm-design-required-badge-color"
						label={__(
							'"Required" badge color',
							'renevo-file-request-manager'
						)}
						value={formDesign.required_badge_color}
						onChange={(value) =>
							setField('required_badge_color', value)
						}
					/>
				</CardBody>
			</Card>

			<Card>
				<CardHeader>
					<h3>{__('Typography', 'renevo-file-request-manager')}</h3>
				</CardHeader>
				<CardBody>
					<SelectControl
						label={__('Font family', 'renevo-file-request-manager')}
						value={formDesign.font_family}
						options={FONT_FAMILY_OPTIONS}
						onChange={(value) => setField('font_family', value)}
					/>
					<RangeControl
						label={__(
							'Font size (px)',
							'renevo-file-request-manager'
						)}
						value={formDesign.font_size}
						onChange={(value) => setField('font_size', value)}
						min={12}
						max={24}
					/>
				</CardBody>
			</Card>

			<Card>
				<CardHeader>
					<h3>
						{__('Layout & spacing', 'renevo-file-request-manager')}
					</h3>
				</CardHeader>
				<CardBody>
					<RangeControl
						label={__(
							'Border radius (px)',
							'renevo-file-request-manager'
						)}
						value={formDesign.border_radius}
						onChange={(value) => setField('border_radius', value)}
						min={0}
						max={40}
					/>
					<RangeControl
						label={__(
							'Padding (px)',
							'renevo-file-request-manager'
						)}
						value={formDesign.padding}
						onChange={(value) => setField('padding', value)}
						min={0}
						max={64}
					/>
				</CardBody>
			</Card>

			<Card>
				<CardHeader>
					<h3>{__('Upload area', 'renevo-file-request-manager')}</h3>
				</CardHeader>
				<CardBody>
					<RadioControl
						label={__(
							'Upload widget style',
							'renevo-file-request-manager'
						)}
						selected={formDesign.upload_style}
						options={[
							{
								value: 'dropzone',
								label: __(
									'Drag & drop area',
									'renevo-file-request-manager'
								),
							},
							{
								value: 'button',
								label: __(
									'Simple upload button',
									'renevo-file-request-manager'
								),
							},
						]}
						onChange={(value) => setField('upload_style', value)}
					/>
				</CardBody>
			</Card>

			<Card>
				<CardHeader>
					<h3>
						{__('Submit button', 'renevo-file-request-manager')}
					</h3>
				</CardHeader>
				<CardBody>
					<ColorField
						id="frm-design-submit-button-color"
						label={__(
							'Background color',
							'renevo-file-request-manager'
						)}
						value={formDesign.submit_button_color}
						onChange={(value) =>
							setField('submit_button_color', value)
						}
					/>
					<ColorField
						id="frm-design-submit-button-hover-color"
						label={__('Hover color', 'renevo-file-request-manager')}
						value={formDesign.submit_button_hover_color}
						onChange={(value) =>
							setField('submit_button_hover_color', value)
						}
					/>
					<ColorField
						id="frm-design-submit-button-border-color"
						label={__(
							'Border color (optional)',
							'renevo-file-request-manager'
						)}
						value={formDesign.submit_button_border_color}
						onChange={(value) =>
							setField('submit_button_border_color', value)
						}
					/>
					<RadioControl
						label={__('Position', 'renevo-file-request-manager')}
						selected={formDesign.submit_button_position}
						options={[
							{
								value: 'left',
								label: __(
									'Left',
									'renevo-file-request-manager'
								),
							},
							{
								value: 'center',
								label: __(
									'Center',
									'renevo-file-request-manager'
								),
							},
							{
								value: 'right',
								label: __(
									'Right',
									'renevo-file-request-manager'
								),
							},
						]}
						onChange={(value) =>
							setField('submit_button_position', value)
						}
					/>
				</CardBody>
			</Card>

			<Card>
				<CardHeader>
					<h3>
						{__('Content layout', 'renevo-file-request-manager')}
					</h3>
				</CardHeader>
				<CardBody>
					<RadioControl
						label={__(
							'Description position',
							'renevo-file-request-manager'
						)}
						help={__(
							'The title always stays above the upload area — this only moves the optional description text.',
							'renevo-file-request-manager'
						)}
						selected={formDesign.file_info_position}
						options={[
							{
								value: 'above',
								label: __(
									'Above the upload area',
									'renevo-file-request-manager'
								),
							},
							{
								value: 'below',
								label: __(
									'Below the upload area',
									'renevo-file-request-manager'
								),
							},
						]}
						onChange={(value) =>
							setField('file_info_position', value)
						}
					/>
				</CardBody>
			</Card>
		</div>
	);
}
