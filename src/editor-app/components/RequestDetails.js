/**
 * "Request details" tab: title + description.
 */

import { __ } from '@wordpress/i18n';
import {
	BaseControl,
	TextControl,
	Card,
	CardBody,
} from '@wordpress/components';
import DescriptionEditor from './DescriptionEditor';

export default function RequestDetails({ title, description, dispatch }) {
	return (
		<Card>
			<CardBody>
				<TextControl
					label={__('Title', 'renevo-file-request-manager')}
					value={title}
					onChange={(value) =>
						dispatch({ type: 'SET_FIELD', field: 'title', value })
					}
					help={__(
						'Shown to visitors at the top of the request.',
						'renevo-file-request-manager'
					)}
				/>
				<BaseControl
					id="frm-description-editor"
					label={__('Description', 'renevo-file-request-manager')}
					help={__(
						'Shown under the title. Scripts and unsafe tags are stripped when saved.',
						'renevo-file-request-manager'
					)}
					__nextHasNoMarginBottom
				>
					<DescriptionEditor
						id="frm-description-editor"
						initialValue={description}
						onChange={(value) =>
							dispatch({
								type: 'SET_FIELD',
								field: 'description',
								value,
							})
						}
					/>
				</BaseControl>
			</CardBody>
		</Card>
	);
}
