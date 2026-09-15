/**
 * "Your contact details" tab.
 *
 * Deliberately a fixed set of five fields (name/email/phone/company/message)
 * — this is not a generic form builder, so no "add field" control here.
 */

import { __ } from '@wordpress/i18n';
import {
	Card,
	CardBody,
	CheckboxControl,
	Flex,
	FlexItem,
	SelectControl,
} from '@wordpress/components';

const FIELD_LABELS = {
	name: __('Name', 'renevo-file-request-manager'),
	email: __('Email', 'renevo-file-request-manager'),
	phone: __('Phone', 'renevo-file-request-manager'),
	company: __('Company', 'renevo-file-request-manager'),
	message: __('Message', 'renevo-file-request-manager'),
};

export default function ContactFieldsSection({
	contactFields,
	submissionSettings,
	dispatch,
}) {
	return (
		<Card>
			<CardBody>
				<SelectControl
					label={__(
						'Position on the page',
						'renevo-file-request-manager'
					)}
					value={submissionSettings.contact_fields_position}
					options={[
						{
							value: 'top',
							label: __(
								'Above the requested files',
								'renevo-file-request-manager'
							),
						},
						{
							value: 'bottom',
							label: __(
								'Below the requested files',
								'renevo-file-request-manager'
							),
						},
					]}
					onChange={(value) =>
						dispatch({
							type: 'SET_SUBMISSION_SETTING',
							key: 'contact_fields_position',
							value,
						})
					}
					help={__(
						'Where "Your details" appears on the public page.',
						'renevo-file-request-manager'
					)}
				/>
				{Object.keys(FIELD_LABELS).map((key) => {
					const field = contactFields[key];

					return (
						<Flex
							key={key}
							align="center"
							className="frm-contact-field-row"
						>
							<FlexItem>
								<CheckboxControl
									label={FIELD_LABELS[key]}
									checked={field.enabled}
									onChange={(enabled) =>
										dispatch({
											type: 'SET_CONTACT_FIELD',
											key,
											changes: {
												enabled,
												required:
													enabled && field.required,
											},
										})
									}
								/>
							</FlexItem>
							{field.enabled ? (
								<FlexItem>
									<CheckboxControl
										label={__(
											'Required',
											'renevo-file-request-manager'
										)}
										checked={field.required}
										onChange={(required) =>
											dispatch({
												type: 'SET_CONTACT_FIELD',
												key,
												changes: { required },
											})
										}
									/>
								</FlexItem>
							) : null}
						</Flex>
					);
				})}
			</CardBody>
		</Card>
	);
}
