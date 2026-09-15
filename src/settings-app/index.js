/**
 * Entry point for the admin Settings React app.
 */

import { createRoot } from '@wordpress/element';
import App from './App';
import './style.scss';

const container = document.getElementById('frm-settings-app-root');

if (container) {
	createRoot(container).render(<App />);
}
