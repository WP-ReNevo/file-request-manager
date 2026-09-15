/**
 * Sticky top bar: Save Draft / Publish / Preview + status feedback.
 */

import { __ } from '@wordpress/i18n';
import { Button, Notice } from '@wordpress/components';

export default function PublishBar({
	title,
	status,
	saving,
	saveError,
	saveNotice,
	onSaveDraft,
	onPublish,
	onPreview,
	backUrl,
}) {
	return (
		<div className="frm-publish-bar">
			<div className="frm-publish-bar__row">
				<div className="frm-publish-bar__title">
					<a href={backUrl}>
						{__(
							'← Back to requests',
							'renevo-file-request-manager'
						)}
					</a>
					<h1>
						{title ||
							__(
								'(untitled request)',
								'renevo-file-request-manager'
							)}
						<span
							className={
								status === 'publish'
									? 'frm-badge frm-badge--published'
									: 'frm-badge frm-badge--draft'
							}
						>
							{status === 'publish'
								? __('Published', 'renevo-file-request-manager')
								: __('Draft', 'renevo-file-request-manager')}
						</span>
					</h1>
				</div>
				<div className="frm-publish-bar__actions">
					<Button variant="tertiary" onClick={onPreview}>
						{__('Preview', 'renevo-file-request-manager')}
					</Button>
					<Button
						variant="secondary"
						onClick={onSaveDraft}
						isBusy={saving === 'draft'}
						disabled={!!saving}
					>
						{__('Save Draft', 'renevo-file-request-manager')}
					</Button>
					<Button
						variant="primary"
						onClick={onPublish}
						isBusy={saving === 'publish'}
						disabled={!!saving}
					>
						{__('Publish', 'renevo-file-request-manager')}
					</Button>
				</div>
			</div>

			{saveError ? (
				<Notice status="error" isDismissible={false}>
					{saveError}
				</Notice>
			) : null}
			{saveNotice ? (
				<Notice status="success" isDismissible={false}>
					{saveNotice}
				</Notice>
			) : null}
		</div>
	);
}
