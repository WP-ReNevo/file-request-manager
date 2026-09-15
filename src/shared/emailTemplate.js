/**
 * Builds the same HTML email markup as
 * `includes/templates/emails/notification.php`, for a live preview
 * (rendered in an iframe so it's isolated from wp-admin's own styles, the
 * way an inbox would actually show it). Keep this in sync with that file.
 */

function escapeHtml(value) {
	const div = document.createElement('div');
	div.textContent = String(value || '');
	return div.innerHTML;
}

/**
 * A rough client-side approximation of WordPress's wpautop(): blank lines
 * become paragraph breaks, single line breaks become <br>.
 *
 * @param {string} text Plain text (already HTML-escaped).
 * @return {string} HTML with <p>/<br> tags.
 */
function autop(text) {
	const paragraphs = String(text || '')
		.split(/\n\s*\n/)
		.map((block) => block.trim())
		.filter(Boolean);

	return paragraphs
		.map((block) => `<p>${block.replace(/\n/g, '<br>')}</p>`)
		.join('');
}

/**
 * Builds the full email HTML document for preview.
 *
 * @param {Object} args               Arguments.
 * @param {string} args.siteName      Site name shown in the header bar.
 * @param {string} args.faviconUrl    Optional site icon URL shown next to the site name.
 * @param {string} args.subject       Resolved subject (used as the <title>).
 * @param {string} args.body          Resolved plain-text body.
 * @param {string} [args.buttonUrl]   Optional call-to-action button URL.
 * @param {string} [args.buttonLabel] Optional call-to-action button label.
 * @return {string} Full HTML document.
 */
export function buildEmailHtml({
	siteName,
	faviconUrl,
	subject,
	body,
	buttonUrl,
	buttonLabel,
}) {
	const safeSiteName = escapeHtml(siteName);
	const safeSubject = escapeHtml(subject);
	const bodyHtml = autop(escapeHtml(body));
	const faviconHtml = faviconUrl
		? `<td style="padding-right:10px;"><img src="${escapeHtml(
				faviconUrl
			)}" width="20" height="20" alt="" style="display:block;border-radius:4px;"></td>`
		: '';
	const buttonHtml = buttonUrl
		? `<table role="presentation" cellpadding="0" cellspacing="0" style="margin-top:20px;"><tr><td style="background-color:#2563eb;border-radius:6px;"><a href="${escapeHtml(
				buttonUrl
			)}" style="display:inline-block;padding:12px 24px;color:#ffffff;text-decoration:none;font-size:14px;font-weight:600;">${escapeHtml(
				buttonLabel
			)}</a></td></tr></table>`
		: '';

	return `<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>${safeSubject}</title>
</head>
<body style="margin:0;padding:0;background-color:#ffffff;font-family:-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;">
	<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#ffffff;">
		<tr>
			<td align="center">
				<table role="presentation" width="480" cellpadding="0" cellspacing="0" style="background-color:#ffffff;">
					<tr>
						<td style="background-color:#1e293b;padding:20px 28px;">
							<table role="presentation" cellpadding="0" cellspacing="0">
								<tr>
									${faviconHtml}
									<td>
										<span style="color:#ffffff;font-size:16px;font-weight:600;">${safeSiteName}</span>
									</td>
								</tr>
							</table>
						</td>
					</tr>
					<tr>
						<td style="padding:28px;color:#1f2937;font-size:14px;line-height:1.6;">
							${bodyHtml}
							${buttonHtml}
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
</body>
</html>`;
}
