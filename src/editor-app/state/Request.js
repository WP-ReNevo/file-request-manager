/**
 * Client-side defaults and shape helpers for the Request object.
 */

function defaultContactFields() {
	return {
		name: { enabled: true, required: true },
		email: { enabled: true, required: true },
		phone: { enabled: false, required: false },
		company: { enabled: false, required: false },
		message: { enabled: false, required: false },
	};
}

function defaultSubmissionSettings() {
	return {
		success_message: 'Thank you. Your documents have been received.',
		redirect_url: '',
		allow_multiple_submissions: true,
	};
}

function defaultFormDesign() {
	return {
		layout: 'stacked',
		preset: 'classic',
		background_color: '#ffffff',
		page_background_color: '',
		border_color: '#d5d7dc',
		border_radius: 8,
		padding: 16,
		upload_style: 'dropzone',
		hover_color: '#1d4ed8',
		font_size: 16,
		font_family: 'system',
		submit_button_color: '#2563eb',
		submit_button_hover_color: '#1d4ed8',
		submit_button_border_color: '',
		submit_button_position: 'left',
		file_info_position: 'above',
		file_block_wrap: 'card',
		contact_fields_wrap: 'card',
		required_badge_color: '#dc2626',
		required_indicator: 'badge',
		show_optional_badge: true,
	};
}

function defaultTextLabels() {
	return {
		contact_heading: 'Your details',
		name_label: 'Name',
		email_label: 'Email',
		phone_label: 'Phone',
		company_label: 'Company',
		message_label: 'Message',
		submit_button_text: 'Submit',
		required_label: 'Required',
		optional_label: 'Optional',
		accepted_types_text: 'Accepted: %s',
		max_size_text: 'Maximum size: %d MB',
		max_files_text: 'Up to %d file',
		max_files_text_plural: 'Up to %d files',
		upload_area_text: 'Upload area',
	};
}

function defaultNotificationSettings(adminEmail, siteName) {
	return {
		admin_notify_enabled: true,
		admin_email: adminEmail || '',
		admin_subject: 'New file request submission: {request_title}',
		admin_body:
			'Hello,\n\nA new submission has been received.\n\nRequest: {request_title}\nSubmission: {submission_id}\nName: {name}\nEmail: {email}\nFiles received: {file_count}',
		requester_confirmation_enabled: true,
		requester_from_name: siteName || '',
		requester_from_email: adminEmail || '',
		requester_subject: 'We received your documents',
		requester_body:
			'Hello {name},\n\nWe received your submission for:\n\n{request_title}\n\nFiles received: {file_count}\n\nThank you.',
	};
}

export const Request = {
	/**
	 * A completely blank request, as if "Start from scratch" was chosen.
	 *
	 * @param {string} [adminEmail] The site's admin email, used as the default notification address.
	 * @param {string} [siteName]   The site's name, used as the default requester-confirmation "from" name.
	 * @return {Object} A blank request in editor-state shape.
	 */
	blank(adminEmail, siteName) {
		return {
			id: null,
			title: '',
			description: '',
			status: 'draft',
			requested_files: [],
			contact_fields: defaultContactFields(),
			submission_settings: defaultSubmissionSettings(),
			notification_settings: defaultNotificationSettings(
				adminEmail,
				siteName
			),
			form_design: defaultFormDesign(),
			text_labels: defaultTextLabels(),
			schema_version: 1,
			created_at: '',
			updated_at: '',
		};
	},

	/**
	 * Builds editor state from a template returned by GET /templates.
	 *
	 * @param {Object} template     Template definition.
	 * @param {string} [adminEmail] The site's admin email, used as the default notification address.
	 * @param {string} [siteName]   The site's name, used as the default requester-confirmation "from" name.
	 * @return {Object} A request in editor-state shape, pre-filled from the template.
	 */
	fromTemplate(template, adminEmail, siteName) {
		return {
			...this.blank(adminEmail, siteName),
			title: template.title || '',
			description: template.description || '',
			requested_files: (template.requested_files || []).map((file) => ({
				...file,
			})),
		};
	},

	/**
	 * Normalizes a Request object from the REST API into editor state.
	 *
	 * @param {Object} request Raw request object from the server.
	 * @return {Object} A request in editor-state shape.
	 */
	fromServer(request) {
		const blank = this.blank();

		return {
			id: request.id ?? blank.id,
			title: request.title ?? blank.title,
			description: request.description ?? blank.description,
			status: request.status ?? blank.status,
			requested_files: request.requested_files ?? blank.requested_files,
			contact_fields: request.contact_fields ?? blank.contact_fields,
			submission_settings:
				request.submission_settings ?? blank.submission_settings,
			notification_settings:
				request.notification_settings ?? blank.notification_settings,
			form_design: request.form_design ?? blank.form_design,
			text_labels: request.text_labels ?? blank.text_labels,
			schema_version: request.schema_version ?? 1,
			created_at: request.created_at ?? '',
			updated_at: request.updated_at ?? '',
		};
	},
};
