/**
 * Custom webpack config.
 *
 * Extends @wordpress/scripts' default config (which auto-discovers every
 * `src/blocks/**\/block.json` and turns its `editorScript`/`viewScript` etc.
 * entries into build outputs, e.g. `build/blocks/file-request/index.js`) and
 * adds our own named entries for the non-block bundles the PHP side expects:
 * `editor-app`, `frontend`, `admin`, and `settings-app`.
 */

const path = require('path');
const defaultConfig = require('@wordpress/scripts/config/webpack.config');

const blockEntries =
	typeof defaultConfig.entry === 'function'
		? defaultConfig.entry()
		: defaultConfig.entry;

module.exports = {
	...defaultConfig,
	entry: {
		...blockEntries,
		'editor-app': path.resolve(__dirname, 'src/editor-app/index.js'),
		frontend: path.resolve(__dirname, 'src/frontend-app/index.js'),
		admin: path.resolve(__dirname, 'src/admin/index.js'),
		'settings-app': path.resolve(__dirname, 'src/settings-app/index.js'),
	},
	optimization: {
		...defaultConfig.optimization,
		splitChunks: {
			...defaultConfig.optimization.splitChunks,
			cacheGroups: {
				...defaultConfig.optimization.splitChunks.cacheGroups,
				// wp-scripts' default cache group names the extracted CSS
				// chunk "style-<entry>", e.g. "style-frontend.css". Assets.php
				// enqueues these bundles by their exact, unprefixed names
				// (build/frontend.css, build/editor-app.css, build/admin.css),
				// so the chunk name is overridden here to match the
				// requesting entry 1:1 instead.
				style: {
					...defaultConfig.optimization.splitChunks.cacheGroups.style,
					name: (moduleOrGroup, chunks) =>
						chunks.map((chunk) => chunk.name).join('-'),
				},
			},
		},
	},
};
