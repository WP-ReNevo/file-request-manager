const fs = require('fs-extra');
const path = require('path');

const srcDir = path.join(__dirname, '..');
const distDir = path.join(__dirname, '../dist');

// Everything the plugin needs at runtime. No vendor/ — the plugin has no
// production Composer dependencies, and falls back to its own lightweight
// autoloader (includes/frm-autoloader.php) when vendor/autoload.php is
// absent, exactly for this case. The uncompiled JS/SCSS source that
// produces build/ is NOT shipped here — it's public at the readme's linked
// GitHub repo instead, so regular installs don't get a src/ folder they'll
// never use.
const dirsToCopy = ['includes', 'build', 'languages'];
const filesToCopy = ['renevo-file-request-manager.php', 'uninstall.php', 'readme.txt'];

fs.emptyDirSync(distDir);

dirsToCopy.forEach((dir) => {
	const from = path.join(srcDir, dir);
	if (fs.existsSync(from)) {
		fs.copySync(from, path.join(distDir, dir));
	}
});

filesToCopy.forEach((file) => {
	fs.copySync(path.join(srcDir, file), path.join(distDir, file));
});

console.log('Files copied to dist/');
