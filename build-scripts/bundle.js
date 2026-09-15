const fs = require('fs');
const path = require('path');
const archiver = require('archiver');

const pluginSlug = 'renevo-file-request-manager';

function getVersionFromPluginHeader() {
	const header = fs.readFileSync(
		path.join(__dirname, '../renevo-file-request-manager.php'),
		'utf8'
	);
	const match = header.match(/Version:\s*([0-9.]+)/);
	return match ? match[1] : require('../package.json').version;
}

const pluginVersion = getVersionFromPluginHeader();

const output = fs.createWriteStream(
	path.join(__dirname, `../${pluginSlug}-${pluginVersion}.zip`)
);
const archive = archiver('zip', { zlib: { level: 9 } });

output.on('close', function () {
	console.log(archive.pointer() + ' total bytes');
	console.log(`Built ${pluginSlug}-${pluginVersion}.zip`);
});
archive.on('error', function (err) {
	throw err;
});

archive.pipe(output);

archive.directory('dist/includes/', `${pluginSlug}/includes`);
archive.directory('dist/build/', `${pluginSlug}/build`);
archive.directory('dist/languages/', `${pluginSlug}/languages`);
archive.file('dist/renevo-file-request-manager.php', {
	name: `${pluginSlug}/renevo-file-request-manager.php`,
});
archive.file('dist/uninstall.php', { name: `${pluginSlug}/uninstall.php` });
archive.file('dist/readme.txt', { name: `${pluginSlug}/readme.txt` });

archive.finalize();
