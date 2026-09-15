=== ReNevo File Request Manager ===
Contributors: renevo
Tags: file upload, file request, document upload, client documents, gutenberg
Requires at least: 6.4
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Request files from clients, customers, and visitors with simple, customizable file request forms.

== Description ==

Need a client to send you a few documents? Instead of chasing emails back and forth, create a File Request: tell the visitor exactly what you need — "Identity document," "Proof of address," "Signed contract" — and let them upload it straight from a page on your own site.

ReNevo is built around one idea: **a requested file, not a generic form field**. Every request is a named checklist of the specific files you need, and every submission tells you at a glance whether everything you asked for actually arrived.

= What you can do =

* **Create a File Request.** Give it a title and, optionally, instructions for the person uploading.
* **List exactly what you need.** Add as many requested files as you like — e.g. "Passport," "Utility bill," "W-9 form" — each with its own label, description, required/optional flag, allowed file types, maximum file size, and maximum number of files.
* **Collect basic contact info.** Turn name, email, phone, company, and a message field on or off individually, and decide which are required.
* **Publish it your way.** Drop in a `[file_request id="123"]` shortcode or add the File Request block in the block editor — both render the same upload form.
* **Receive everything in one place.** Uploaded files and contact details arrive together as a submission, viewable from the plugin's own Submissions screen — nothing lands in your Media Library, and nothing is emailed as an attachment.
* **Know what's missing.** If a required file wasn't uploaded, the submission is still saved (so nothing the visitor already sent is lost) and is clearly marked "Incomplete," with the missing item(s) listed.
* **Get notified.** Send yourself an email when a submission comes in, and optionally send the visitor a confirmation — both with a customizable subject and body.
* **Start from a template.** Eight ready-made templates (company registration, client onboarding, tax documents, job application, and more) give you a starting point you can edit freely.

= What this is not =

ReNevo is not a general-purpose form builder, and it doesn't try to be one in this free version. There's no drag-and-drop field designer and no arbitrary field types — every "field" you define is a file you're requesting. If you need a contact form, a survey, or a payment form, a different plugin will serve you better.

= Privacy and storage, plainly =

Uploaded files are stored in a protected folder inside your own `wp-content/uploads/` directory, outside the Media Library, with no predictable or public URL. Only a signed-in administrator (using the plugin's own authenticated download link) can retrieve a file. ReNevo doesn't call out to any external service, doesn't track or phone home, and doesn't share submission data with anyone. Everything happens on your own server.

= Source code =

The `build/` folder in this plugin contains compiled/minified JavaScript and CSS. The uncompiled source and the build tooling used to produce it are maintained publicly at https://github.com/WP-ReNevo/file-request-manager.

== Installation ==

1. Upload the `file-request-manager` folder to `/wp-content/plugins/`, or install it from Plugins → Add New in your WordPress admin.
2. Activate the plugin through the "Plugins" screen.
3. Go to **File Requests → Requests** and create your first request, or start from a template.
4. Publish the request, then either:
   * copy the `[file_request id="123"]` shortcode it gives you into any page or post, or
   * add the **File Request** block to a page in the block editor and choose your request.
5. Submissions appear under **File Requests → Submissions** as they come in.

== Frequently Asked Questions ==

= Is this a generic contact form builder? =

No. Every request is built around a list of specific files you need, plus a small, fixed set of contact fields (name, email, phone, company, message) that you can turn on or off. There are no custom field types and no drag-and-drop form designer.

= Can I ask for more than one specific file? =

Yes. A single request can list as many requested files as you need, each with its own title, description, required/optional setting, allowed file types, and size limit.

= Can a visitor upload more than one file for the same item? =

Yes, if you allow it. Each requested file has its own "maximum number of files" setting — set it above 1 to let the visitor upload several files (e.g. several receipts) against the same item.

= Can I control the maximum file size and which file types are allowed? =

Yes, per requested file. You choose from a fixed list of common types (PDF, JPG, PNG, GIF, WEBP, DOC, DOCX, XLS, XLSX, ZIP) and set a maximum size in MB. Certain file types — like PHP, executables, and other script types — can never be uploaded, regardless of what's configured, as a security safeguard.

= Can I add instructions for each requested file? =

Yes. Every requested file has its own optional description, shown to the visitor under its title, in addition to the request's own overall description.

= Can I create more than one File Request? =

Yes, you can create as many as you like, each published independently with its own shortcode/block.

= Does it work with the block editor (Gutenberg)? =

Yes. There's a dedicated File Request block — add it to any page, choose your request from a dropdown, and it renders the live upload form.

= Does it work with the shortcode, too? =

Yes. Every request also gets a `[file_request id="123"]` shortcode you can place in any post, page, or widget area that supports shortcodes.

= Where are the uploaded files stored? Are they in my Media Library? =

No, they're never added to the Media Library. Files are stored in a protected subdirectory of your own `wp-content/uploads/` folder, which is not publicly browsable and has no predictable URL. The only way to retrieve a file is an authenticated download link available to signed-in administrators from the Submissions screen.

= Does this plugin send my clients' files to an external service? =

No. ReNevo doesn't integrate with any third-party storage, doesn't send analytics or telemetry anywhere, and doesn't make any outbound calls to external services. Everything is stored on your own server.

= Can I customize the message shown after someone submits? =

Yes, each request has its own editable "success message," and you can optionally redirect the visitor to a URL of your choice afterward instead.

= Will I get an email when someone submits? =

Yes, if you leave admin notifications enabled (the default). You can customize the recipient address, subject, and body, using placeholders like `{name}`, `{request_title}`, and `{file_count}`. You can also send the person who submitted an optional confirmation email.

= What happens if someone doesn't upload a required file? =

The submission is still recorded — nothing the visitor already sent is thrown away — but it's marked "Incomplete" instead of "Complete," and the admin submission view shows exactly which required item is still missing. The visitor is shown a message about what's missing as well, so they can go back and finish.

= Does it work on phones and tablets? =

Yes, the upload form is responsive and works on mobile browsers.

= Does the visitor need to log in or create an account? =

No. Anyone with the link can open the request and submit files; no WordPress account or login is required.

= Does deleting the plugin delete my clients' data? =

Not by default. Uninstalling the plugin (not just deactivating it) leaves all your requests, submissions, and uploaded files in place unless you've explicitly turned on "Delete all data on uninstall" in Settings.

== Screenshots ==

1. The Dashboard, showing a summary of requests and recent submissions.
2. The Requests list, with status and quick actions.
3. Creating a File Request: adding requested files, contact fields, and settings.
4. Starting a new request from a built-in template.
5. The public upload form as seen by a visitor (shortcode/block output).
6. The Submissions list, with status at a glance.
7. A single submission's detail view, including uploaded files and contact info.
8. The Settings screen.

== Changelog ==

= 1.0.0 =
* Initial release.
* Create, edit, duplicate, and publish File Requests with a list of specific requested files (title, description, required flag, allowed types, max size, max files).
* Configurable contact fields: name, email, phone, company, message.
* Publish via the `[file_request]` shortcode or the File Request Gutenberg block.
* Eight built-in starting templates.
* Submissions list and detail view, with status (Incomplete / Complete / Reviewed / Archived) and per-file authenticated downloads.
* Admin email notifications and optional requester confirmation emails, with customizable, placeholder-driven subject/body.
* Configurable success message and optional redirect after submission.
* Files stored outside the Media Library in a protected uploads subdirectory.
* Built-in privacy exporter and eraser (WordPress Privacy tools), configurable submission retention, and an opt-in "delete all data on uninstall" setting.

== Upgrade Notice ==

= 1.0.0 =
Initial release.
