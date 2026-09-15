/**
 * Tests for buildFormDesignVars, the helper that turns a `form_design`
 * settings object into `.frm-frontend-root` CSS custom property overrides.
 */

import { buildFormDesignVars } from './formDesignStyle';

function sampleFormDesign(overrides = {}) {
	return {
		preset: 'classic',
		background_color: '#ffffff',
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
		required_badge_color: '#dc2626',
		...overrides,
	};
}

describe('buildFormDesignVars', () => {
	it('returns an empty object when given no form design', () => {
		expect(buildFormDesignVars(undefined)).toEqual({});
		expect(buildFormDesignVars(null)).toEqual({});
	});

	it('maps every field to its CSS custom property', () => {
		const vars = buildFormDesignVars(sampleFormDesign());

		expect(vars).toEqual({
			'--frm-color-surface': '#ffffff',
			'--frm-color-border': '#d5d7dc',
			'--frm-radius': '8px',
			'--frm-space': '16px',
			'--frm-color-primary': '#2563eb',
			'--frm-color-primary-hover': '#1d4ed8',
			'--frm-font-size': '16px',
			'--frm-font-family': expect.stringContaining('sans-serif'),
			'--frm-submit-bg': '#2563eb',
			'--frm-submit-hover-bg': '#1d4ed8',
			'--frm-submit-border-color': 'transparent',
			'--frm-submit-align': 'flex-start',
			'--frm-page-bg': 'transparent',
			'--frm-required-color': '#dc2626',
		});
	});

	it('uses page_background_color for --frm-page-bg when set', () => {
		expect(
			buildFormDesignVars(
				sampleFormDesign({ page_background_color: '#111827' })
			)['--frm-page-bg']
		).toBe('#111827');
	});

	it('uses submit_button_border_color for --frm-submit-border-color when set', () => {
		expect(
			buildFormDesignVars(
				sampleFormDesign({ submit_button_border_color: '#111111' })
			)['--frm-submit-border-color']
		).toBe('#111111');
	});

	it('falls back to hover_color for --frm-submit-hover-bg when unset', () => {
		expect(
			buildFormDesignVars(
				sampleFormDesign({ submit_button_hover_color: '' })
			)['--frm-submit-hover-bg']
		).toBe('#1d4ed8');
	});

	it('maps submit_button_position to a flexbox alignment value', () => {
		expect(
			buildFormDesignVars(
				sampleFormDesign({ submit_button_position: 'center' })
			)['--frm-submit-align']
		).toBe('center');

		expect(
			buildFormDesignVars(
				sampleFormDesign({ submit_button_position: 'right' })
			)['--frm-submit-align']
		).toBe('flex-end');
	});

	it('falls back to the system font stack for an unknown font_family', () => {
		const systemStack =
			buildFormDesignVars(sampleFormDesign())['--frm-font-family'];

		expect(
			buildFormDesignVars(
				sampleFormDesign({ font_family: 'not-a-real-font' })
			)['--frm-font-family']
		).toBe(systemStack);
	});
});
