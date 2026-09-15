/**
 * Entry point for the admin "Create/Edit File Request" React app.
 */

import { createRoot } from '@wordpress/element';
import App from './App';
import '../shared/layouts';
import './style.scss';

// Root URL + nonce middleware are already provided by WordPress core for
// every wp-admin page (registered on the shared `wp-api-fetch` script);
// registering our own here would conflict with it rather than replace it,
// so REST calls throughout this app use namespace-prefixed paths instead
// (see `shared/restNamespace.js`).
const config = window.renevoEditorApp || {};

const container = document.getElementById('frm-editor-app-root');

if (container) {
	createRoot(container).render(<App config={config} />);
}
