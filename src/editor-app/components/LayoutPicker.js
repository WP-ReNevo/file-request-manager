/**
 * Layout picker: every layout from GET /form-layouts in a tile grid. Unlike
 * templates there's no color story here — layout is a fixed choice among the
 * registered structures (built-in, or Pro-registered via
 * window.renevo.registerLayout()).
 */

import { Card, CardBody, Spinner } from '@wordpress/components';
import LayoutIcon from './LayoutIcon';

export default function LayoutPicker({ layouts, activeLayout, onSelect }) {
	const keys = Object.keys(layouts || {});

	if (keys.length === 0) {
		return <Spinner />;
	}

	return (
		<div className="frm-layout-picker">
			{keys.map((key) => {
				const layout = layouts[key];
				const isActive = activeLayout === key;

				return (
					<Card
						key={key}
						className={
							isActive
								? 'frm-layout-picker-card frm-layout-picker-card--active'
								: 'frm-layout-picker-card'
						}
					>
						<CardBody>
							<button
								type="button"
								className="frm-layout-picker-card__button"
								aria-pressed={isActive}
								onClick={() => onSelect(key)}
							>
								<LayoutIcon layout={key} />
								<span className="frm-layout-picker-card__label">
									{layout.label}
								</span>
								<span className="frm-layout-picker-card__description">
									{layout.description}
								</span>
							</button>
						</CardBody>
					</Card>
				);
			})}
		</div>
	);
}
