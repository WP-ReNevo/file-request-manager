/**
 * Title + description header, shared by the live frontend app, the editor
 * preview, and the block's static canvas preview.
 */

export default function RequestHeader({ title, description }) {
	return (
		<div className="frm-request-header">
			<h1 className="frm-request-header__title">{title}</h1>
			{description ? (
				<div
					className="frm-request-header__description"
					// Server-sanitized (wp_kses) HTML + inline CSS, no scripts.
					dangerouslySetInnerHTML={{ __html: description }}
				/>
			) : null}
		</div>
	);
}
