/**
 * Top-level state for the Create/Edit File Request screen.
 */

import { requestedFilesReducer } from './requestedFilesReducer';

/**
 * @param {Object} state  Current editor state.
 * @param {Object} action Action.
 * @return {Object} Next state.
 */
export function editorReducer(state, action) {
	switch (action.type) {
		// Fully replaces state, e.g. after loading a request from the
		// server or picking a template. Callers are expected to have
		// already normalized the incoming object via Request.fromServer()/
		// fromTemplate()/blank() — the reducer stays a dumb state container.
		case 'REPLACE':
			return { ...action.state };

		case 'SET_FIELD':
			return { ...state, [action.field]: action.value };

		case 'SET_CONTACT_FIELD':
			return {
				...state,
				contact_fields: {
					...state.contact_fields,
					[action.key]: {
						...state.contact_fields[action.key],
						...action.changes,
					},
				},
			};

		case 'SET_SUBMISSION_SETTING':
			return {
				...state,
				submission_settings: {
					...state.submission_settings,
					[action.key]: action.value,
				},
			};

		case 'SET_NOTIFICATION_SETTING':
			return {
				...state,
				notification_settings: {
					...state.notification_settings,
					[action.key]: action.value,
				},
			};

		// One-field merge, used by the Layout picker and the Custom panel's
		// individual controls.
		case 'SET_FORM_DESIGN_FIELD':
			return {
				...state,
				form_design: {
					...state.form_design,
					[action.key]: action.value,
				},
			};

		// Multi-field merge — a preset tile sets `preset` and its paired
		// `layout` together, so picking one visibly changes structure too.
		case 'SET_FORM_DESIGN_FIELDS':
			return {
				...state,
				form_design: {
					...state.form_design,
					...action.fields,
				},
			};

		case 'SET_TEXT_LABEL':
			return {
				...state,
				text_labels: {
					...state.text_labels,
					[action.key]: action.value,
				},
			};

		case 'REQUESTED_FILES':
			return {
				...state,
				requested_files: requestedFilesReducer(
					state.requested_files,
					action.payload
				),
			};

		default:
			return state;
	}
}
