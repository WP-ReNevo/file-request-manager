/**
 * "Files you need" tab: the list of requested-file cards plus the
 * Add/Edit modal.
 */

import { useEffect, useState } from '@wordpress/element';
import { __, sprintf, _n } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import {
	Button,
	Card,
	CardBody,
	Flex,
	FlexItem,
	FlexBlock,
} from '@wordpress/components';
import { restPath } from '../../shared/restNamespace';
import RequestedFileModal from './RequestedFileModal';

export default function RequestedFilesBuilder({ requestedFiles, dispatch }) {
	const [fileTypes, setFileTypes] = useState({});
	const [modalKey, setModalKey] = useState(undefined); // undefined = closed, null = adding, string = editing.
	const [draggedIndex, setDraggedIndex] = useState(null);
	const [dragOverIndex, setDragOverIndex] = useState(null);

	useEffect(() => {
		apiFetch({ path: restPath('/file-types') })
			.then(setFileTypes)
			.catch(() => {});
	}, []);

	const reorder = (fromIndex, toIndex) => {
		if (fromIndex !== toIndex) {
			dispatch({
				type: 'REQUESTED_FILES',
				payload: { type: 'REORDER', fromIndex, toIndex },
			});
		}
		setDraggedIndex(null);
		setDragOverIndex(null);
	};

	const handleDragStart = (event, index) => {
		if (!event.target.closest('.frm-drag-handle')) {
			event.preventDefault();
			return;
		}
		setDraggedIndex(index);
		event.dataTransfer.effectAllowed = 'move';
	};

	const handleDragOver = (event, index) => {
		if (draggedIndex === null) {
			return;
		}
		event.preventDefault();
		event.dataTransfer.dropEffect = 'move';
		if (dragOverIndex !== index) {
			setDragOverIndex(index);
		}
	};

	const handleDrop = (event, index) => {
		event.preventDefault();
		if (draggedIndex !== null) {
			reorder(draggedIndex, index);
		}
	};

	const handleDragEnd = () => {
		setDraggedIndex(null);
		setDragOverIndex(null);
	};

	const moveByKeyboard = (index, direction) => {
		const toIndex = index + direction;
		if (toIndex >= 0 && toIndex < requestedFiles.length) {
			reorder(index, toIndex);
		}
	};

	const editingFile =
		modalKey && requestedFiles.find((file) => file.key === modalKey);

	const typeLabel = (key) => (fileTypes[key] ? fileTypes[key].label : key);

	const handleModalSave = (values) => {
		if (modalKey) {
			dispatch({
				type: 'REQUESTED_FILES',
				payload: { type: 'UPDATE', key: modalKey, changes: values },
			});
		} else {
			dispatch({
				type: 'REQUESTED_FILES',
				payload: { type: 'ADD', file: values },
			});
		}
		setModalKey(undefined);
	};

	return (
		<div className="frm-requested-files-builder">
			{requestedFiles.length === 0 ? (
				<p className="frm-requested-files-builder__empty">
					{__(
						"You haven't added any requested files yet.",
						'renevo-file-request-manager'
					)}
				</p>
			) : (
				<div className="frm-requested-files-builder__list">
					{requestedFiles.map((file, index) => (
						<div
							key={file.key}
							className={[
								'frm-requested-file-card-wrapper',
								draggedIndex === index
									? 'frm-requested-file-card--dragging'
									: '',
								dragOverIndex === index &&
								draggedIndex !== null &&
								draggedIndex !== index
									? 'frm-requested-file-card--drag-over'
									: '',
							]
								.filter(Boolean)
								.join(' ')}
							draggable="true"
							onDragStart={(event) =>
								handleDragStart(event, index)
							}
							onDragOver={(event) => handleDragOver(event, index)}
							onDrop={(event) => handleDrop(event, index)}
							onDragEnd={handleDragEnd}
						>
							<Card className="frm-requested-file-card">
								<CardBody>
									<Flex align="flex-start">
										<FlexItem>
											<span
												className="frm-drag-handle"
												role="button"
												tabIndex={0}
												aria-label={__(
													'Drag to reorder, or use the arrow keys.',
													'renevo-file-request-manager'
												)}
												onKeyDown={(event) => {
													if (
														event.key === 'ArrowUp'
													) {
														event.preventDefault();
														moveByKeyboard(
															index,
															-1
														);
													} else if (
														event.key ===
														'ArrowDown'
													) {
														event.preventDefault();
														moveByKeyboard(
															index,
															1
														);
													}
												}}
											>
												⠿
											</span>
										</FlexItem>
										<FlexBlock>
											<Flex justify="flex-start" gap={2}>
												<strong>
													{file.title ||
														__(
															'(untitled)',
															'renevo-file-request-manager'
														)}
												</strong>
												<span
													className={
														file.required
															? 'frm-badge frm-badge--published'
															: 'frm-badge frm-badge--draft'
													}
												>
													{file.required
														? __(
																'Required',
																'renevo-file-request-manager'
															)
														: __(
																'Optional',
																'renevo-file-request-manager'
															)}
												</span>
											</Flex>
											{file.description ? (
												<p>{file.description}</p>
											) : null}
											<p className="frm-requested-file-card__meta">
												{file.allowed_types
													.map(typeLabel)
													.join(', ') ||
													__(
														'Any file type',
														'renevo-file-request-manager'
													)}
												{' · '}
												{sprintf(
													/* translators: %d: maximum size in megabytes. */
													__(
														'Max %d MB',
														'renevo-file-request-manager'
													),
													file.max_size_mb
												)}
												{' · '}
												{sprintf(
													/* translators: %d: maximum number of files. */
													_n(
														'%d file',
														'%d files',
														file.max_files,
														'renevo-file-request-manager'
													),
													file.max_files
												)}
											</p>
										</FlexBlock>
										<FlexItem>
											<Flex gap={2}>
												<Button
													variant="secondary"
													onClick={() =>
														setModalKey(file.key)
													}
												>
													{__(
														'Edit',
														'renevo-file-request-manager'
													)}
												</Button>
												<Button
													variant="tertiary"
													onClick={() =>
														dispatch({
															type: 'REQUESTED_FILES',
															payload: {
																type: 'DUPLICATE',
																key: file.key,
															},
														})
													}
												>
													{__(
														'Duplicate',
														'renevo-file-request-manager'
													)}
												</Button>
												<Button
													variant="tertiary"
													isDestructive
													onClick={() =>
														dispatch({
															type: 'REQUESTED_FILES',
															payload: {
																type: 'DELETE',
																key: file.key,
															},
														})
													}
												>
													{__(
														'Delete',
														'renevo-file-request-manager'
													)}
												</Button>
											</Flex>
										</FlexItem>
									</Flex>
								</CardBody>
							</Card>
						</div>
					))}
				</div>
			)}

			<Button variant="primary" onClick={() => setModalKey(null)}>
				{__('+ Add requested file', 'renevo-file-request-manager')}
			</Button>

			{modalKey !== undefined ? (
				<RequestedFileModal
					initialValue={editingFile || null}
					fileTypes={fileTypes}
					onSave={handleModalSave}
					onClose={() => setModalKey(undefined)}
				/>
			) : null}
		</div>
	);
}
