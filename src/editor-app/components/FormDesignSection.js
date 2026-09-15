/**
 * "Form Design" tab: pick a template (sets structure + colors in one shot),
 * then fine-tune structure (Layout) or any individual value below — every
 * field stays editable regardless of which template was last picked.
 */

import { __ } from '@wordpress/i18n';
import LayoutPicker from './LayoutPicker';
import LayoutOptionsPanel from './LayoutOptionsPanel';
import DesignPresetPicker from './DesignPresetPicker';
import DesignOptionsPanel from './DesignOptionsPanel';

export default function FormDesignSection({
	formDesign,
	presets,
	layouts,
	dispatch,
}) {
	const setField = (key, value) =>
		dispatch({ type: 'SET_FORM_DESIGN_FIELD', key, value });

	const selectPreset = (key) => {
		const preset = (presets || {})[key];
		const fields = preset
			? { ...preset.defaults, preset: key, layout: preset.layout }
			: { preset: key };

		dispatch({ type: 'SET_FORM_DESIGN_FIELDS', fields });
	};

	return (
		<div className="frm-form-design-section">
			<h2 className="frm-form-design-section__heading">
				{__('Templates', 'renevo-file-request-manager')}
			</h2>
			<p className="frm-form-design-section__hint">
				{__(
					'Each template pairs a distinct look with a distinct structure — pick one, then fine-tune anything below.',
					'renevo-file-request-manager'
				)}
			</p>

			<DesignPresetPicker
				presets={presets}
				activePreset={formDesign.preset}
				onSelect={selectPreset}
			/>

			<h2 className="frm-form-design-section__heading">
				{__('Layout', 'renevo-file-request-manager')}
			</h2>

			<LayoutPicker
				layouts={layouts}
				activeLayout={formDesign.layout}
				onSelect={(key) => setField('layout', key)}
			/>

			<LayoutOptionsPanel formDesign={formDesign} dispatch={dispatch} />

			<h2 className="frm-form-design-section__heading">
				{__('Design options', 'renevo-file-request-manager')}
			</h2>

			<DesignOptionsPanel formDesign={formDesign} dispatch={dispatch} />
		</div>
	);
}
