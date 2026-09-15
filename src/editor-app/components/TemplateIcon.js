// Per-template icon for the Templates picker — deliberately a different
// glyph set from LayoutIcon (structure-only), since a template is a whole
// look, not just a structure.

const ICONS = {
	classic: (
		<svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
			<rect
				x="3"
				y="4"
				width="18"
				height="16"
				rx="2"
				stroke="currentColor"
				strokeWidth="1.6"
			/>
			<line
				x1="3"
				y1="8.5"
				x2="21"
				y2="8.5"
				stroke="currentColor"
				strokeWidth="1.6"
			/>
			<circle cx="6" cy="6.25" r="0.75" fill="currentColor" />
			<circle cx="8.5" cy="6.25" r="0.75" fill="currentColor" />
		</svg>
	),
	modern: (
		<svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
			<path
				d="M12 2 L14.2 9.8 L22 12 L14.2 14.2 L12 22 L9.8 14.2 L2 12 L9.8 9.8 Z"
				fill="currentColor"
			/>
		</svg>
	),
	editorial: (
		<svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
			<path
				d="M9 5c-3 1-5 4-5 8v6h6v-6H6c0-2.5 1.2-4.3 3-5V5z"
				fill="currentColor"
			/>
			<path
				d="M20 5c-3 1-5 4-5 8v6h6v-6h-4c0-2.5 1.2-4.3 3-5V5z"
				fill="currentColor"
			/>
		</svg>
	),
	default: (
		<svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
			<circle cx="12" cy="12" r="8" fill="currentColor" />
		</svg>
	),
};

export default function TemplateIcon({ templateKey, accent }) {
	return (
		<span
			className="frm-layout-icon frm-layout-icon--swatch"
			style={{ background: accent }}
			aria-hidden="true"
		>
			{ICONS[templateKey] || ICONS.default}
		</span>
	);
}
