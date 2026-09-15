# ReNevo File Request Manager — Developer Guide

This is a reference for anyone working on the plugin itself, or building a
Pro add-on / custom integration on top of it. It is not end-user
documentation — see `readme.txt` for that.

## Getting set up

```
composer install
npm install && npm run build
```

- `composer install` pulls PHP dependencies. `require-dev` includes
  PHPCS + WordPress Coding Standards (`composer run phpcs` / `phpcbf`) and
  the PHPUnit test stack (`phpunit/phpunit`, `yoast/phpunit-polyfills`,
  `wp-phpunit/wp-phpunit`).
- `npm install && npm run build` builds the React/Gutenberg frontend
  (`src/` → `build/`) via the webpack config in `webpack.config.js`. `build/`
  is what actually gets enqueued at runtime (see `includes/Admin/Assets.php`
  and `includes/Blocks/FileRequestBlock.php`); the plugin does not read from
  `src/` directly.

### Running the PHP test suite

```
vendor/bin/phpunit --configuration=phpunit.xml.dist
```

Tests live in `tests/php/` and extend `WP_UnitTestCase` from
`wp-phpunit/wp-phpunit`. This requires a real MySQL database — the suite
does not run against SQLite or a mock. Configure it via environment
variables (`WP_TESTS_DB_NAME`, `WP_TESTS_DB_USER`, `WP_TESTS_DB_PASSWORD`,
`WP_TESTS_DB_HOST`, `WP_TESTS_ABSPATH`) or by editing the defaults in
`tests/php/wp-tests-config.php`. `WP_TESTS_ABSPATH` must point at a real
WordPress core checkout (wp-settings.php, wp-admin/, wp-includes/) —
`wp-phpunit/wp-phpunit` ships only the *test* library, not WordPress core
itself.

## Building a release zip

```
npm run plugin-zip
```

This runs `clean` (empties `dist/`) → `build` (webpack, `src/` → `build/`)
→ `make-pot` (`build-scripts/make-pot.js`, regenerates
`languages/renevo-file-request-manager.pot`) → `copy` (`build-scripts/copy.js`, stages only the
runtime files — `includes/`, `build/`, `languages/`,
`renevo-file-request-manager.php`, `uninstall.php`, `readme.txt` — into `dist/`) →
`bundle` (`build-scripts/bundle.js`, zips `dist/` into `renevo-file-request-manager-{version}.zip`
at the plugin root, reading the version straight from the plugin header).
The result is exactly what an
end user would get from the WordPress.org zip download: no `src/`, `tests/`,
`docs/`, `node_modules/`, `vendor/`, or dev config files. There is no
`vendor/` in the zip either — the plugin has no production Composer
dependencies, and falls back to its own PSR-4 autoloader
(`includes/renevo-autoloader.php`) when `vendor/autoload.php` isn't present.

`.wordpress-org/` holds the WP.org *directory listing* assets (icon,
banner, screenshots) — these are never part of the installable zip. On
WP.org they're uploaded to the plugin's SVN `assets/` directory (a sibling
of `trunk/` and `tags/`, not inside either), e.g.:

```
svn cp .wordpress-org/icon-256x256.png     svn-checkout/assets/icon-256x256.png
svn cp .wordpress-org/banner-1544x500.png  svn-checkout/assets/banner-1544x500.png
svn commit -m "Add directory assets"
```

## Architecture at a glance

`renevo-file-request-manager.php` defines the plugin constants (`RENEVO_VERSION`,
`RENEVO_DB_VERSION`, `RENEVO_PATH`, `RENEVO_URL`, `RENEVO_FILE`, `RENEVO_BASENAME`),
registers the activation/deactivation hooks, and boots `Plugin::instance()`
on `plugins_loaded`. `Plugin` (`includes/Plugin.php`) is a small, eager
service container: it constructs every service once in `build_services()`
and exposes them via `Plugin::instance()->get( SomeClass::class )`, then
wires WordPress hooks in `boot()`. There is no lazy resolution and no
autowiring — if you add a new service, register it in `build_services()`
and add it to the constructor of whatever consumes it.

### Data model

Two different storage mechanisms, one per concept:

**File Requests** are a `file_request` custom post type (see
`includes/Core/PostType.php`), never exposed through the native post list or
post editor (`show_ui`/`show_in_menu` are both `false` — the CPT is a
storage detail, not a product surface). The post's title/content/status map
directly to a request's title/description/status; everything else lives in
postmeta as JSON-serializable arrays, read/written entirely by
`RequestRepository`:

| Meta key                     | Contents                                    |
|-------------------------------|----------------------------------------------|
| `_renevo_requested_files`        | Array of requested-file definitions          |
| `_renevo_contact_fields`         | Which contact fields are enabled/required     |
| `_renevo_submission_settings`    | Success message, redirect URL, etc.           |
| `_renevo_notification_settings`  | Admin/requester email settings                |
| `_renevo_schema_version`         | For future meta-shape migrations              |

Native `draft` / `publish` / `trash` post statuses are reused as-is for the
request lifecycle rather than inventing a parallel status field.

**Submissions** and their files live in two dedicated custom tables, created
by `Migrations::install()` (via `dbDelta()`) and gated behind the
`renevo_db_version` option so upgrades and fresh installs run the same code
path:

- `{$wpdb->prefix}renevo_submissions` — one row per submission (contact info,
  status, submission code).
- `{$wpdb->prefix}renevo_submission_files` — one row per uploaded file,
  foreign-keyed (by convention, not a DB constraint) to a submission.

All access to these tables goes through `SubmissionRepository`; nothing else
in the plugin issues raw SQL against them.

Uploaded files themselves are never stored in the Media Library. They live
under `wp-content/uploads/renevo-file-request-manager/` (see
`FileStorageService`), in a directory created with an `index.php` and
`.htaccess` for defense-in-depth, with random, non-guessable stored
filenames unrelated to the original filename. The only path to a file is
the authenticated admin download route below — there is no public URL.

### Request lifecycle (upload flow)

1. `GET /public/requests/{id}` returns the reduced public shape of a
   published request (`Request::to_public_array()` — no notification
   settings) plus a nonce scoped to that request.
2. `POST /public/requests/{id}/upload` validates and stores one file at a
   time (`UploadValidator` + `FileStorageService::store_temp_upload()`),
   returning an opaque token backed by a transient.
3. `POST /public/requests/{id}/submit` resolves the submitted tokens,
   moves each file into its permanent submission directory, creates the
   `Submission` + `SubmissionFile` rows, determines
   incomplete/complete status via
   `UploadValidator::validate_required_coverage()`, and fires the
   notification emails.

Every write route on `PublicController` runs through a private `guard()`:
a per-request nonce (`PublicController::nonce_action( $id )`), an empty
honeypot field, and a per-IP rate limit — none of which substitutes for the
server-side validation in `UploadValidator`, which runs unconditionally
regardless of what the client already checked.

## REST API

Namespace: `renevo/v1` (`RequestsController::NAMESPACE_V1`).

Admin routes (`Capabilities::current_user_can_manage()`, default
`manage_options`, gates every one of them):

| Method | Route                                   | Purpose                          |
|--------|------------------------------------------|-----------------------------------|
| GET    | `/requests`                              | List/search/paginate requests     |
| POST   | `/requests`                              | Create a request                  |
| GET    | `/requests/{id}`                         | Get one request                   |
| PUT    | `/requests/{id}`                         | Update a request                  |
| DELETE | `/requests/{id}`                         | Trash a request                   |
| POST   | `/requests/{id}/duplicate`               | Duplicate a request as a draft    |
| GET    | `/templates`                             | List starter templates            |
| GET    | `/file-types`                            | List the allowed-type registry    |
| GET    | `/submissions`                           | List/search/paginate submissions  |
| GET    | `/submissions/{id}`                      | Get one submission                |
| PUT    | `/submissions/{id}/status`               | Change a submission's status      |
| GET    | `/submissions/{id}/files/{file_id}/download` | Stream a submitted file (never a normal JSON response) |
| GET    | `/settings`                              | Get plugin settings               |
| PUT    | `/settings`                              | Update plugin settings            |

Public routes (`permission_callback` is `__return_true`; writes are instead
gated by `PublicController::guard()` as described above):

| Method | Route                                    | Purpose                       |
|--------|-------------------------------------------|--------------------------------|
| GET    | `/public/requests/{id}`                   | Get the public shape + nonce   |
| POST   | `/public/requests/{id}/upload`            | Upload one file, get a token   |
| POST   | `/public/requests/{id}/submit`            | Finalize a submission          |

## Hooks reference

This is the complete, current list — grep `includes/` for `apply_filters`
and `do_action` to verify it before relying on it, since it can change
between releases.

| Hook | Type | Fired from | Purpose |
|------|------|-----------|---------|
| `renevo_manage_capability` | filter | `Core\Capabilities::capability()` | Remap the capability required to manage requests/submissions (default `manage_options`) to something more granular. |
| `renevo_requested_file_types` | filter | `Services\AllowedFileTypes::all()` | Add/remove entries in the selectable file-type registry (extensions + MIME types). Extending it does **not** bypass `AllowedFileTypes::hard_denied_extensions()`, which is checked independently and always wins. |
| `renevo_request_templates` | filter | `Services\TemplateRegistry::all()` | Add, remove, or replace the starter templates offered when creating a request. |
| `renevo_email_placeholders` | filter | `Services\PlaceholderResolver::resolve()` | Add new `{token}` values to the context available to email subjects/bodies (and any other template resolved through this class). |
| `renevo_notification_context` | filter | `Services\NotificationService` (`build_context()`) | Add data to the placeholder context specifically used when building the admin/requester notification emails. |
| `renevo_frontend_render_data` | filter | `Frontend\RequestRenderer::render()` | Modify the JSON payload (public request data, nonce, REST URL) embedded in the frontend mount point before it reaches the browser. |
| `renevo_post_type_args` | filter | `Core\PostType::register()` | Modify the `register_post_type()` arguments for the internal `file_request` CPT. |
| `renevo_loaded` | action | `Plugin::boot()`, end | Fires once every hook is registered. The safe point for a Pro add-on to start referencing plugin services/hooks. |
| `renevo_services_ready` | action | `Plugin::build_services()`, end | Fires once every core service is constructed, before hooks are registered. Use `Plugin::instance()->get( SomeClass::class )` from here (or later) to access a service. |

There is deliberately no dedicated "register a Pro feature" hook beyond
these — see below.

## Extending Lite (Pro add-on guidance)

A future Pro add-on is expected to be a *separate* plugin that hooks into
Lite exclusively through the filters/actions above and the public class API
(`Plugin::instance()->get( ... )`), never by editing any file inside this
plugin. Concretely:

- Add new file-type choices via `renevo_requested_file_types` rather than
  editing `AllowedFileTypes`.
- Add new starter templates via `renevo_request_templates` rather than editing
  `TemplateRegistry`.
- Add new email placeholders (e.g. an expiring-link URL, a CAPTCHA result)
  via `renevo_email_placeholders` / `renevo_notification_context`.
- Gate advanced capability schemes via `renevo_manage_capability`.
- Hook `renevo_loaded` to register additional REST routes, admin pages, or
  cron jobs once Lite has finished booting.

Nothing in Lite currently reads a "Pro is active" flag or exposes an
extension-point registry beyond the hooks above — if Pro needs to change
Lite's behavior in a way none of these hooks cover, that's a signal Lite
needs a new, generically useful hook added (not a Pro-specific conditional).

## Privacy

`Privacy\Privacy` (registered via `Plugin::boot()`) integrates with
WordPress's built-in Privacy tools:

- **Exporter** (`export( $email )`): looks up every submission for an email
  address via `SubmissionRepository::find_by_email()` and returns its
  contact fields, submission code, timestamp, and the list of uploaded
  filenames (not file contents).
- **Eraser** (`erase( $email )`): deletes the matching submissions outright
  — both the database rows and the files on disk
  (`FileStorageService::delete_submission_files()`) — rather than
  anonymizing them in place. For this plugin the uploaded files themselves
  are the personal data, so partial anonymization wouldn't be meaningful.

Retention: `SubmissionRepository::purge_expired_submissions()` runs daily
(`renevo_daily_maintenance` cron) and deletes submissions older than
`renevo_settings['retention_days']`. A value of `0` means "keep forever" and
makes this a no-op.

Uninstall: deactivating the plugin does not delete anything. Deleting it
from the Plugins screen (`uninstall.php`) also keeps everything by default
— requests, submissions, uploaded files, and options are only removed if
the site owner has explicitly enabled "Delete all data on uninstall" in
Settings.

## Testing notes

`tests/php/bootstrap.php` loads the plugin the same way WordPress would (on
`muplugins_loaded`, before `plugins_loaded`), so `Plugin::boot()` — and
therefore `Migrations::maybe_upgrade()` — runs once per test process before
any test executes. One consequence worth knowing if you add tests: the
`wp-phpunit` harness rewrites every `CREATE TABLE` / `DROP TABLE` query into
its `TEMPORARY` equivalent for the duration of each test (via a `query`
filter added in `WP_UnitTestCase::set_up()`), so schema created inside a
test is scoped to that connection and never touches the real tables. The
plugin's two custom tables are created for real once, at boot, before that
filter exists — a test that needs to exercise real `DROP TABLE` behavior
(see `tests/php/test-uninstall.php`) has to temporarily remove that filter
first, or it will silently no-op instead of dropping anything.
