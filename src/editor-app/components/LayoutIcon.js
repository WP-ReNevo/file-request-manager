// Small representative icon for a layout key, used by the Layout picker.
// See TemplateIcon for the (visually distinct) Templates picker icon set.

const ICONS = {
	stacked: (
		<svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
			<rect
				x="3"
				y="4"
				width="18"
				height="4.5"
				rx="1"
				fill="currentColor"
			/>
			<rect
				x="3"
				y="10.5"
				width="18"
				height="4.5"
				rx="1"
				fill="currentColor"
			/>
			<rect
				x="3"
				y="17"
				width="18"
				height="4"
				rx="1"
				fill="currentColor"
			/>
		</svg>
	),
	grid: (
		<svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
			<rect x="3" y="3" width="8" height="8" rx="1" fill="currentColor" />
			<rect
				x="13"
				y="3"
				width="8"
				height="8"
				rx="1"
				fill="currentColor"
			/>
			<rect
				x="3"
				y="13"
				width="8"
				height="8"
				rx="1"
				fill="currentColor"
			/>
			<rect
				x="13"
				y="13"
				width="8"
				height="8"
				rx="1"
				fill="currentColor"
			/>
		</svg>
	),
	editorial: (
		<svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
			<rect
				x="3"
				y="3"
				width="18"
				height="4"
				rx="1"
				fill="currentColor"
			/>
			<circle cx="5" cy="13.5" r="1.5" fill="currentColor" />
			<rect
				x="9"
				y="12.25"
				width="12"
				height="2.5"
				rx="1"
				fill="currentColor"
			/>
			<circle cx="5" cy="19.5" r="1.5" fill="currentColor" />
			<rect
				x="9"
				y="18.25"
				width="12"
				height="2.5"
				rx="1"
				fill="currentColor"
			/>
		</svg>
	),
};

export default function LayoutIcon({ layout }) {
	return (
		<span className="frm-layout-icon" aria-hidden="true">
			{ICONS[layout] || ICONS.stacked}
		</span>
	);
}
