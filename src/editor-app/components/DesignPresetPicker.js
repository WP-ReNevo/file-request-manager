/**
 * The template picker: every template from GET /form-design-presets in a
 * grid. Picking one copies its `defaults` (colors, spacing, wraps, ...) and
 * its `layout` into form_design in one shot — see FormDesignSection.js.
 * There is no separate "Custom" mode: every value stays editable afterward.
 */

import { Card, CardBody, Spinner } from '@wordpress/components';
import TemplateIcon from './TemplateIcon';

export default function DesignPresetPicker({
	presets,
	activePreset,
	onSelect,
}) {
	const keys = Object.keys(presets || {});

	if (keys.length === 0) {
		return <Spinner />;
	}

	return (
		<div className="frm-design-presets">
			{keys.map((key) => {
				const preset = presets[key];
				const isActive = activePreset === key;
				const accent =
					(preset.defaults && preset.defaults.submit_button_color) ||
					'#2563eb';

				return (
					<Card
						key={key}
						className={
							isActive
								? 'frm-design-preset-card frm-design-preset-card--active'
								: 'frm-design-preset-card'
						}
					>
						<CardBody>
							<button
								type="button"
								className="frm-design-preset-card__button"
								aria-pressed={isActive}
								onClick={() => onSelect(key)}
							>
								<TemplateIcon
									templateKey={key}
									accent={accent}
								/>
								<span className="frm-design-preset-card__text">
									<span className="frm-design-preset-card__label">
										{preset.label}
									</span>
									<span className="frm-design-preset-card__description">
										{preset.description}
									</span>
								</span>
							</button>
						</CardBody>
					</Card>
				);
			})}
		</div>
	);
}
