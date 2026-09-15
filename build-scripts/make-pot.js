/**
 * Regenerates languages/renevo-file-request-manager.pot from every __()/_e()/etc. call in
 * includes/ and src/ (JS included), via WP-CLI's `wp i18n make-pot`. Uses a
 * global `wp` if one is on PATH; otherwise downloads a cached wp-cli.phar
 * (once) and runs it through the local PHP CLI, so this works on machines
 * that only have wp-cli available inside a tool like Local's own site
 * shell, not on the regular system PATH.
 */

const { spawnSync } = require('child_process');
const fs = require('fs');
const https = require('https');
const path = require('path');

const rootDir = path.join(__dirname, '..');
const languagesDir = path.join(rootDir, 'languages');
const potPath = path.join(languagesDir, 'renevo-file-request-manager.pot');
const cacheDir = path.join(__dirname, '.cache');
const pharPath = path.join(cacheDir, 'wp-cli.phar');
const pharUrl =
	'https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar';

// wp-cli's --exclude replaces its own defaults rather than adding to them,
// so everything that isn't plugin source has to be listed explicitly.
const excludes = [
	'.git',
	'node_modules',
	'vendor',
	'build',
	'dist',
	'tests',
	'build-scripts',
	'languages',
].join(',');

function hasGlobalWpCli() {
	const result = spawnSync('wp', ['--version'], { stdio: 'ignore' });
	return !result.error && result.status === 0;
}

function downloadFile(url, destination) {
	return new Promise((resolve, reject) => {
		const request = https.get(url, (response) => {
			const { statusCode, headers } = response;

			if (statusCode >= 300 && statusCode < 400 && headers.location) {
				response.resume();
				downloadFile(headers.location, destination).then(
					resolve,
					reject
				);
				return;
			}

			if (statusCode !== 200) {
				response.resume();
				reject(
					new Error(
						`Could not download wp-cli.phar (HTTP ${statusCode})`
					)
				);
				return;
			}

			const file = fs.createWriteStream(destination);
			response.pipe(file);
			file.on('finish', () => file.close(resolve));
			file.on('error', reject);
		});
		request.on('error', reject);
	});
}

async function ensureWpCliPhar() {
	if (fs.existsSync(pharPath)) {
		return;
	}
	fs.mkdirSync(cacheDir, { recursive: true });
	console.log(
		'Downloading wp-cli.phar (one-time, cached in build-scripts/.cache)...'
	);
	try {
		await downloadFile(pharUrl, pharPath);
	} catch (error) {
		fs.rmSync(pharPath, { force: true });
		throw error;
	}
}

async function run() {
	fs.mkdirSync(languagesDir, { recursive: true });

	let command;
	let baseArgs;

	if (hasGlobalWpCli()) {
		command = 'wp';
		baseArgs = [];
	} else {
		await ensureWpCliPhar();
		command = 'php';
		baseArgs = [pharPath];
	}

	const result = spawnSync(
		command,
		[
			...baseArgs,
			'i18n',
			'make-pot',
			rootDir,
			potPath,
			'--domain=renevo-file-request-manager',
			`--exclude=${excludes}`,
		],
		{ stdio: 'inherit' }
	);

	if (result.error || result.status !== 0) {
		console.error('Failed to generate languages/renevo-file-request-manager.pot');
		process.exit(result.status || 1);
	}

	console.log('Generated languages/renevo-file-request-manager.pot');
}

run().catch((error) => {
	console.error(error.message);
	process.exit(1);
});
