/**
 * Registers the `renevo/file-request` block. Dynamic block; save() returns null.
 */

import { registerBlockType } from '@wordpress/blocks';
import apiFetch from '@wordpress/api-fetch';
import metadata from './block.json';
import Edit from './edit';
import './editor.scss';

const config = window.renevoBlockEditor || {};

if (config.restUrl) {
	apiFetch.use(apiFetch.createRootURLMiddleware(config.restUrl));
}
if (config.nonce) {
	apiFetch.use(apiFetch.createNonceMiddleware(config.nonce));
}

registerBlockType(metadata.name, {
	...metadata,
	edit: Edit,
	save: () => null,
});
