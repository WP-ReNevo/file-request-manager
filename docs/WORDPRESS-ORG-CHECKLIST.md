# WordPress.org Plugin Directory Submission Checklist

Honest status against the actual repository as of this writing. This is a
pre-submission checklist, not a marketing document — anything not actually
done is marked as such.

## readme.txt

- [x] `readme.txt` present at plugin root, standard WP.org format
      (`=== Name ===` header block, short description, Description,
      Installation, FAQ, Screenshots, Changelog, Upgrade Notice).
- [x] Header fields present: Contributors, Tags, Requires at least, Tested
      up to, Requires PHP, Stable tag, License, License URI.
- [x] `Stable tag` (1.0.0) matches the `Version` header in
      `renevo-file-request-manager.php`.
- [ ] `Contributors` currently lists a placeholder WordPress.org username
      (`filerequestmanager`) — **must be replaced** with the real
      WordPress.org.org account username(s) that will own this plugin
      before submission, or the readme parser / directory listing will be
      wrong.
- [ ] Tags, short description, and screenshots have not been validated
      against an actual WordPress.org preview (the `readme.txt` validator
      at https://wordpress.org/plugins/developers/readme-validator/)
      because that's an external network check outside this environment —
      run it once before submitting.

## Licensing

- [x] GPL-compatible license declared consistently: `License: GPL v2 or
      later` in the main plugin file header, `License: GPLv2 or later` in
      `readme.txt`, and `"license": "GPL-2.0-or-later"` in `composer.json`.
- [ ] No standalone `license.txt`/`LICENSE` file at the plugin root. Not
      strictly required (the header declarations above are sufficient for
      the directory), but worth adding the standard GPLv2 license text file
      for clarity before submission.

## Plugin header / identity

- [x] Text domain (`renevo-file-request-manager`) matches the plugin slug and is used
      consistently across every `__()`/`_e()`/`_n()` call in `includes/`
      and `src/` (renamed from `file-request-manager` when the plugin was
      renamed to "ReNevo File Request Manager" for the WP.org review;
      `phpcs.xml.dist`'s `text_domain` property updated to match).
- [x] Text domain also matches in `src/blocks/file-request/block.json`
      (`"textdomain": "renevo-file-request-manager"`). The block's own `name` ("namespace") was
      also renamed to `renevo/file-request` — this is a test site with no
      real published content yet, so there was no "existing block markup"
      risk to weigh against full consistency. On a site with real published
      posts using the block, renaming this would need a content migration
      first (WordPress stops recognizing `<!-- wp:old-name/block -->` once
      the registered name changes).
- [x] `Domain Path: /languages` declared in the plugin header, and
      `languages/renevo-file-request-manager.pot` is generated automatically as part of
      `npm run plugin-zip` (see `build-scripts/make-pot.js`); WP.org also
      builds translations from source strings itself via GlotPress.
- [x] No `Plugin URI` in the main file header (no dedicated plugin page
      exists) — WP.org rejects dead/placeholder URLs, and an absent header
      is safer than one pointing at a URL that 404s. `Author URI` points to
      `https://profiles.wordpress.org/renevo/`, which is guaranteed to
      resolve since it's the submitting account's own profile.

## Code quality / security

- [x] PHPCS configured (`phpcs.xml.dist`, WordPress Coding Standards +
      PHPCompatibilityWP for PHP 7.4+) and, per the brief this test suite
      was written against, passes cleanly.
- [x] Sanitization on input: every REST write path runs input through
      `Domain\Request::from_array()` / `Domain\RequestedFile::from_array()`,
      which sanitize every field (`sanitize_text_field`, `wp_kses_post`,
      `sanitize_key`, `absint`, `is_email`, etc.) rather than trusting raw
      REST params.
- [x] Escaping on output: spot-checked admin/frontend rendering
      (`Frontend\RequestRenderer`, admin page templates) uses
      `esc_html`/`esc_attr`/`esc_url`/`wp_json_encode` appropriately.
- [x] Nonces / CSRF protection: admin REST routes rely on core's REST
      cookie + nonce authentication; public write routes
      (`Rest\PublicController`) are additionally protected by a
      per-request, per-ID nonce (`PublicController::nonce_action()`), a
      honeypot field, and a per-IP rate limit, all verified server-side
      unconditionally.
- [x] Capability checks: every admin REST route requires
      `Capabilities::current_user_can_manage()` (default `manage_options`,
      filterable) — verified directly and via full REST dispatch in
      `tests/php/test-rest-permissions.php`.
- [x] File upload validation is server-side and authoritative
      (`Services\UploadValidator`): extension **and** real
      content-sniffed MIME type must both be in the allowed set, a
      hard-denied extension list (php, phtml, exe, js, svg, htaccess, …)
      always wins regardless of admin configuration, and size is enforced
      per requested file. Covered by `tests/php/test-upload-validator.php`.
- [x] No `eval()`, no dynamic `include`/`require` of user-controlled paths,
      no direct `$_GET`/`$_POST`/`$_FILES` superglobal use without
      sanitization (spot-checked; REST args and `WP_REST_Request` accessors
      are used throughout instead of raw superglobals).
- [x] No external HTTP calls / third-party service integration anywhere in
      `includes/` — confirmed by inspection, nothing to disclose. `readme.txt`
      states this explicitly in the FAQ.
- [x] No tracking, telemetry, or "call home" behavior of any kind.

## Uninstall behavior

- [x] `uninstall.php` present, guarded by `WP_UNINSTALL_PLUGIN`, and
      defaults to **keeping** all data (posts, postmeta, custom tables,
      uploaded files, options) unless the site owner explicitly opted in via
      Settings → "Delete all data on uninstall".
      Verified in `tests/php/test-uninstall.php` (both the opt-in deletion
      path and the default keep-everything path).
- [x] Deactivation (`Activator::deactivate()`) only unschedules the daily
      maintenance cron — it does not touch any stored data, matching the
      "deactivation is reversible, uninstall is a deliberate choice"
      expectation.

## Assets required for the directory listing

- [x] `icon-256x256.png` and `icon-128x128.png` — generated, in
      `.wordpress-org/`.
- [x] `banner-1544x500.png` and `banner-772x250.png` — generated, in
      `.wordpress-org/`.
- [ ] **Screenshots — none exist as actual image files.** `readme.txt`
      lists 8 numbered screenshot captions (`== Screenshots ==`) describing
      what each should show, but the corresponding `screenshot-1.png`
      through `screenshot-8.png` files still need to be captured from a
      real running install (these have to show actual UI, so they can't be
      generated ahead of time) and added to `.wordpress-org/`.
- On WP.org, everything in `.wordpress-org/` is uploaded to the plugin's
  SVN `assets/` directory (separate from `trunk/`, i.e. never bundled into
  the installable zip) — see `docs/DEVELOPER.md` for the exact SVN layout.
- Screenshots are now the only remaining blocker to an actual submission —
  everything else on this list is either done or a small text fix.

## Build artifacts

- [x] `build/` (webpack output consumed by `Admin\Assets` and
      `Blocks\FileRequestBlock`) exists and contains compiled JS/CSS as of
      this writing — produced by a separate, concurrent effort on the JS
      side of this plugin, not by this checklist's author. Re-verify it's
      up to date (`npm run build`) right before packaging the submission
      zip, since JS work may have continued after this note was written.
- [x] `.gitignore` exists (`node_modules/`, `vendor/`, `build/`, `dist/`,
      `*.zip`, `build-scripts/.cache/` excluded from version control — they're
      build artifacts/caches, not source). `build/` is still explicitly
      **included in the distributed submission zip** via `build-scripts/copy.js`
      — the zip WP.org receives must contain working, built JS/CSS since the
      directory does not run your build step. `src/`, `package.json`, and
      `webpack.config.js` are deliberately excluded from the zip (via
      `.distignore` and `copy.js`'s allowlist) — regular installs don't need
      a copy of the JS source they'll never build — and instead the source is
      kept public at https://github.com/WP-ReNevo/file-request-manager,
      linked from readme.txt's "Source code" section.
- [x] No minified/obfuscated PHP anywhere in `includes/`.
- [x] The only non-human-authored code that ships is the `build/` JS/CSS
      output, which is standard and expected for a block-editor plugin —
      not a directory-guideline violation on its own, as long as the
      corresponding `src/` is public (it is, at the GitHub repo linked from
      readme.txt) so reviewers and users can see what produced it.

## Not evaluated by this checklist

- Actual visual/manual testing of the plugin in a real WordPress admin
  (activation, request creation, public submission end-to-end) — this
  checklist only reflects static review of the code, tests, and repository
  contents, not a live smoke test.
- The WP.org plugin review team's own automated and manual checks, which
  can surface issues beyond what's listed here.
