// A labeled color swatch button that opens a ColorPicker — shared by
// DesignOptionsPanel and LayoutOptionsPanel.

import {
	BaseControl,
	Dropdown,
	Button,
	ColorIndicator,
	ColorPicker,
} from '@wordpress/components';

export default function ColorField({ id, label, value, onChange }) {
	return (
		<BaseControl id={id} label={label} __nextHasNoMarginBottom>
			<Dropdown
				className="frm-color-field"
				contentClassName="frm-color-field__popover"
				renderToggle={({ isOpen, onToggle }) => (
					<Button
						id={id}
						className="frm-color-field__toggle"
						variant="secondary"
						onClick={onToggle}
						aria-expanded={isOpen}
					>
						<ColorIndicator colorValue={value} />
						<span>{value}</span>
					</Button>
				)}
				renderContent={() => (
					<ColorPicker
						color={value}
						onChange={onChange}
						enableAlpha={false}
					/>
				)}
			/>
		</BaseControl>
	);
}
