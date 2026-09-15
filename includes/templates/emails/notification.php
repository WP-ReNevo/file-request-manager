<?php
/**
 * Minimal HTML email template.
 *
 * Included from NotificationService::render_template() with $subject, $body
 * and $button already in scope. Kept intentionally plain (no logo/colors) —
 * a branded email builder is a Pro feature, not a Lite one.
 *
 * @package FileRequestManager
 *
 * @var string                              $subject Resolved email subject.
 * @var string                              $body    Resolved plain-text email body.
 * @var array{url?: string, label?: string} $button  Optional call-to-action button.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title><?php echo esc_html( $subject ); ?></title>
</head>
<body style="margin:0;padding:0;background-color:#f3f4f6;font-family:-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;">
	<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f3f4f6;padding:24px 0;">
		<tr>
			<td align="center">
				<table role="presentation" width="480" cellpadding="0" cellspacing="0" style="background-color:#ffffff;border-radius:8px;overflow:hidden;">
					<tr>
						<td style="background-color:#1e293b;padding:20px 28px;">
							<table role="presentation" cellpadding="0" cellspacing="0">
								<tr>
									<?php $renevo_favicon_url = get_site_icon_url( 32 ); ?>
									<?php if ( $renevo_favicon_url ) : ?>
										<td style="padding-right:10px;">
											<img src="<?php echo esc_url( $renevo_favicon_url ); ?>" width="20" height="20" alt="" style="display:block;border-radius:4px;">
										</td>
									<?php endif; ?>
									<td>
										<span style="color:#ffffff;font-size:16px;font-weight:600;"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
									</td>
								</tr>
							</table>
						</td>
					</tr>
					<tr>
						<td style="padding:28px;color:#1f2937;font-size:14px;line-height:1.6;">
							<?php echo wp_kses_post( wpautop( esc_html( $body ) ) ); ?>
							<?php if ( ! empty( $button['url'] ) ) : ?>
								<table role="presentation" cellpadding="0" cellspacing="0" style="margin-top:20px;">
									<tr>
										<td style="background-color:#2563eb;border-radius:6px;">
											<a href="<?php echo esc_url( $button['url'] ); ?>" style="display:inline-block;padding:12px 24px;color:#ffffff;text-decoration:none;font-size:14px;font-weight:600;"><?php echo esc_html( $button['label'] ?? '' ); ?></a>
										</td>
									</tr>
								</table>
							<?php endif; ?>
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
</body>
</html>
