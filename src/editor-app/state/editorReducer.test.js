// Tests for the top-level editor reducer's SET_FORM_DESIGN_FIELD/
// SET_FORM_DESIGN_FIELDS (Form Design tab) and SET_TEXT_LABEL (Text &
// Labels tab) actions.

import { editorReducer } from './editorReducer';

function baseState() {
	return {
		title: 'Untitled',
		form_design: {
			preset: 'classic',
			background_color: '#ffffff',
			border_radius: 8,
		},
		text_labels: {
			contact_heading: 'Your details',
			submit_button_text: 'Submit',
		},
	};
}

describe('editorReducer', () => {
	it('SET_FORM_DESIGN_FIELD merges a single key without touching siblings', () => {
		const next = editorReducer(baseState(), {
			type: 'SET_FORM_DESIGN_FIELD',
			key: 'preset',
			value: 'custom',
		});

		expect(next.form_design).toEqual({
			preset: 'custom',
			background_color: '#ffffff',
			border_radius: 8,
		});
	});

	it('leaves other top-level state fields untouched', () => {
		const next = editorReducer(baseState(), {
			type: 'SET_FORM_DESIGN_FIELD',
			key: 'padding',
			value: 24,
		});

		expect(next.title).toBe('Untitled');
	});

	it('SET_FORM_DESIGN_FIELDS merges multiple keys at once, e.g. a template tile setting preset + layout', () => {
		const next = editorReducer(baseState(), {
			type: 'SET_FORM_DESIGN_FIELDS',
			fields: { preset: 'modern', layout: 'editorial' },
		});

		expect(next.form_design).toEqual({
			preset: 'modern',
			layout: 'editorial',
			background_color: '#ffffff',
			border_radius: 8,
		});
	});

	it('SET_TEXT_LABEL merges a single key without touching siblings', () => {
		const next = editorReducer(baseState(), {
			type: 'SET_TEXT_LABEL',
			key: 'submit_button_text',
			value: 'Trimite',
		});

		expect(next.text_labels).toEqual({
			contact_heading: 'Your details',
			submit_button_text: 'Trimite',
		});
	});

	it('SET_TEXT_LABEL does not mutate the previous state object', () => {
		const state = baseState();
		editorReducer(state, {
			type: 'SET_TEXT_LABEL',
			key: 'contact_heading',
			value: 'Datele tale',
		});

		expect(state.text_labels.contact_heading).toBe('Your details');
	});

	it('is a no-op for an unknown action type', () => {
		const state = baseState();
		const next = editorReducer(state, { type: 'NOT_A_REAL_ACTION' });

		expect(next).toBe(state);
	});
});
