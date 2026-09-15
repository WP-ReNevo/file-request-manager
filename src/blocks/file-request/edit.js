/**
 * Editor (canvas) side of the File Request block.
 */

import { __ } from '@wordpress/i18n';
import { useEffect, useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import {
	PanelBody,
	ComboboxControl,
	Placeholder,
	Button,
	Spinner,
	Notice,
} from '@wordpress/components';
import RequestHeader from '../../shared/RequestHeader';
import RequestedFileCard from '../../shared/RequestedFileCard';
import { restPath } from '../../shared/restNamespace';
import { buildFormDesignVars } from '../../shared/formDesignStyle';

export default function Edit({ attributes, setAttributes }) {
	const { requestId } = attributes;
	const blockProps = useBlockProps();
	const config = window.renevoBlockEditor || {};

	const [options, setOptions] = useState([]);
	const [loadingOptions, setLoadingOptions] = useState(true);
	const [request, setRequest] = useState(null);
	const [loadingRequest, setLoadingRequest] = useState(false);
	const [error, setError] = useState('');

	useEffect(() => {
		let cancelled = false;

		apiFetch({
			path: restPath('/requests?status=any&per_page=100'),
		})
			.then((items) => {
				if (cancelled) {
					return;
				}
				setOptions(
					items.map((item) => ({
						value: item.id,
						label:
							item.title ||
							__('(no title)', 'renevo-file-request-manager'),
					}))
				);
			})
			.catch(() => {
				if (!cancelled) {
					setError(
						__(
							'Could not load File Requests.',
							'renevo-file-request-manager'
						)
					);
				}
			})
			.finally(() => {
				if (!cancelled) {
					setLoadingOptions(false);
				}
			});

		return () => {
			cancelled = true;
		};
	}, []);

	useEffect(() => {
		if (!requestId) {
			setRequest(null);
			return;
		}

		let cancelled = false;
		setLoadingRequest(true);
		setError('');

		apiFetch({ path: restPath(`/requests/${requestId}`) })
			.then((item) => {
				if (!cancelled) {
					setRequest(item);
				}
			})
			.catch(() => {
				if (!cancelled) {
					setError(
						__(
							'Could not load this File Request.',
							'renevo-file-request-manager'
						)
					);
				}
			})
			.finally(() => {
				if (!cancelled) {
					setLoadingRequest(false);
				}
			});

		return () => {
			cancelled = true;
		};
	}, [requestId]);

	const editUrl = requestId
		? `${config.adminUrl}?page=renevo-editor&id=${requestId}`
		: '';

	const inspectorControls = (
		<InspectorControls>
			<PanelBody
				title={__('File Request', 'renevo-file-request-manager')}
			>
				<ComboboxControl
					label={__(
						'Select a File Request',
						'renevo-file-request-manager'
					)}
					value={requestId || undefined}
					options={options}
					isLoading={loadingOptions}
					onChange={(value) =>
						setAttributes({ requestId: value ? Number(value) : 0 })
					}
					allowReset
				/>
				{requestId ? (
					<p>
						<Button variant="secondary" href={editUrl}>
							{__(
								'Edit File Request',
								'renevo-file-request-manager'
							)}
						</Button>
					</p>
				) : null}
			</PanelBody>
		</InspectorControls>
	);

	if (!requestId) {
		return (
			<div {...blockProps}>
				{inspectorControls}
				<Placeholder
					icon="media-document"
					label={__('File Request', 'renevo-file-request-manager')}
					instructions={__(
						'Choose a File Request to embed on this page.',
						'renevo-file-request-manager'
					)}
				>
					<ComboboxControl
						label={__(
							'File Request',
							'renevo-file-request-manager'
						)}
						hideLabelFromVision
						value={undefined}
						options={options}
						isLoading={loadingOptions}
						onChange={(value) =>
							setAttributes({
								requestId: value ? Number(value) : 0,
							})
						}
					/>
				</Placeholder>
			</div>
		);
	}

	return (
		<div {...blockProps}>
			{inspectorControls}
			{error ? (
				<Notice status="error" isDismissible={false}>
					{error}
				</Notice>
			) : null}
			{loadingRequest || !request ? (
				<Placeholder
					icon="media-document"
					label={__('File Request', 'renevo-file-request-manager')}
				>
					<Spinner />
				</Placeholder>
			) : (
				<div
					className="frm-frontend-root frm-block-static-preview"
					style={buildFormDesignVars(request.form_design)}
				>
					<RequestHeader
						title={request.title}
						description={request.description}
					/>
					<div className="frm-requested-files">
						{(request.requested_files || []).map(
							(requestedFile) => (
								<RequestedFileCard
									key={requestedFile.key}
									requestedFile={requestedFile}
									mode="static"
									uploadStyle={
										(request.form_design || {}).upload_style
									}
									infoPosition={
										(request.form_design || {})
											.file_info_position
									}
									textLabels={request.text_labels || {}}
									requiredIndicator={
										(request.form_design || {})
											.required_indicator
									}
									showOptionalBadge={
										(request.form_design || {})
											.show_optional_badge
									}
								/>
							)
						)}
					</div>
					<Button variant="secondary" href={editUrl}>
						{__('Edit File Request', 'renevo-file-request-manager')}
					</Button>
				</div>
			)}
		</div>
	);
}
