/**
 * Tests for the window.renevo-backed layout registry.
 */

import { registerLayout, getLayout } from './layoutRegistry';

function FakeLayout() {
	return null;
}

describe('layoutRegistry', () => {
	beforeEach(() => {
		window.renevo.layouts = {};
	});

	it('registers and retrieves a layout by key', () => {
		registerLayout('stacked', FakeLayout);
		expect(getLayout('stacked')).toBe(FakeLayout);
	});

	it('returns null for an unknown key', () => {
		expect(getLayout('unknown')).toBeNull();
	});

	it('overwrites an existing registration for the same key', () => {
		function OtherLayout() {
			return null;
		}
		registerLayout('stacked', FakeLayout);
		registerLayout('stacked', OtherLayout);
		expect(getLayout('stacked')).toBe(OtherLayout);
	});

	it('rejects a missing key without throwing', () => {
		registerLayout('', FakeLayout);
		expect(getLayout('')).toBeNull();
		expect(console).toHaveErrored();
	});

	it('rejects a non-function component without throwing', () => {
		registerLayout('broken', 'not-a-component');
		expect(getLayout('broken')).toBeNull();
		expect(console).toHaveErrored();
	});

	it('exposes the same functions on window.renevo', () => {
		expect(window.renevo.registerLayout).toBe(registerLayout);
		expect(window.renevo.getLayout).toBe(getLayout);
	});
});
