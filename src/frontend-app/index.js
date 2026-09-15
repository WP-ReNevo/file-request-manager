/**
 * Entry point for the public frontend bundle. Mounts one App instance per `.frm-frontend-root`.
 */

import { createRoot } from '@wordpress/element';
import App from './App';
import { buildFormDesignVars } from '../shared/formDesignStyle';
import '../shared/layouts';
import './style.scss';

function mount(container) {
	const raw = container.getAttribute('data-frm-request');

	if (!raw) {
		return;
	}

	let requestData;
	try {
		requestData = JSON.parse(raw);
	} catch (error) {
		return;
	}

	// This container is about to become the React-rendered root itself, so
	// clear the attribute that fed it to avoid re-parsing on a future
	// hot-reload and drop the now-redundant JSON blob from the DOM.
	container.removeAttribute('data-frm-request');

	// The container is server-rendered markup React only mounts children
	// into — it never owns the container's own attributes — so the chosen
	// design's CSS variable overrides are applied directly here rather than
	// through a React `style` prop.
	const formDesign = requestData.form_design || {};
	container.setAttribute('data-frm-layout', formDesign.layout || 'stacked');
	container.setAttribute(
		'data-frm-file-wrap',
		formDesign.file_block_wrap || 'card'
	);
	container.setAttribute(
		'data-frm-contact-wrap',
		formDesign.contact_fields_wrap || 'card'
	);

	Object.entries(buildFormDesignVars(formDesign)).forEach(
		([property, value]) => container.style.setProperty(property, value)
	);

	createRoot(container).render(<App requestData={requestData} />);
}

function init() {
	document
		.querySelectorAll('.frm-frontend-root')
		.forEach((container) => mount(container));
}

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', init);
} else {
	init();
}
