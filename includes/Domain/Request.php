<?php
/**
 * Value object for a File Request.
 *
 * @package FileRequestManager
 */

namespace FileRequestManager\Domain;

use FileRequestManager\Services\FormDesignRegistry;
use FileRequestManager\Services\FormLayoutRegistry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Value object for a File Request (title, description, status, requested files, settings).
 */
class Request {

	const CONTACT_FIELD_KEYS = array( 'name', 'email', 'phone', 'company', 'message' );

	/**
	 * Post ID. Null for a request that has not been saved yet.
	 *
	 * @var int|null
	 */
	public $id;

	/**
	 * Request title.
	 *
	 * @var string
	 */
	public $title;

	/**
	 * Instructions shown to the requester. Restricted HTML (see kses_post).
	 *
	 * @var string
	 */
	public $description;

	/**
	 * WordPress post status: draft, publish, or trash.
	 *
	 * @var string
	 */
	public $status;

	/**
	 * @var RequestedFile[]
	 */
	public $requested_files = array();

	/**
	 * @var array
	 */
	public $contact_fields = array();

	/**
	 * @var array
	 */
	public $submission_settings = array();

	/**
	 * @var array
	 */
	public $notification_settings = array();

	/**
	 * @var array
	 */
	public $form_design = array();

	/**
	 * @var array
	 */
	public $text_labels = array();

	/**
	 * Schema version of the stored meta shape, for future upgrades.
	 *
	 * @var int
	 */
	public $schema_version = 1;

	/**
	 * @var string
	 */
	public $created_at = '';

	/**
	 * @var string
	 */
	public $updated_at = '';

	/**
	 * Default contact field configuration: name + email enabled and required.
	 *
	 * @return array
	 */
	public static function default_contact_fields() {
		return array(
			'name'    => array(
				'enabled'  => true,
				'required' => true,
			),
			'email'   => array(
				'enabled'  => true,
				'required' => true,
			),
			'phone'   => array(
				'enabled'  => false,
				'required' => false,
			),
			'company' => array(
				'enabled'  => false,
				'required' => false,
			),
			'message' => array(
				'enabled'  => false,
				'required' => false,
			),
		);
	}

	/**
	 * Default submission behaviour.
	 *
	 * @return array
	 */
	public static function default_submission_settings() {
		return array(
			'success_message'            => __( 'Thank you. Your documents have been received.', 'renevo-file-request-manager' ),
			'redirect_url'               => '',
			'allow_multiple_submissions' => true,
			'contact_fields_position'    => 'top',
		);
	}

	/**
	 * Default notification behaviour, using documented {placeholder} tokens.
	 *
	 * @return array
	 */
	public static function default_notification_settings() {
		return array(
			'admin_notify_enabled'           => true,
			'admin_email'                    => get_option( 'admin_email' ),
			'admin_subject'                  => __( 'New file request submission: {request_title}', 'renevo-file-request-manager' ),
			/* translators: email body with placeholders. Do not translate the {tokens}. */
			'admin_body'                     => __( "Hello,\n\nA new submission has been received.\n\nRequest: {request_title}\nSubmission: {submission_id}\nName: {name}\nEmail: {email}\nFiles received: {file_count}", 'renevo-file-request-manager' ),
			'requester_confirmation_enabled' => true,
			'requester_from_name'            => get_bloginfo( 'name' ),
			'requester_from_email'           => get_option( 'admin_email' ),
			'requester_subject'              => __( 'We received your documents', 'renevo-file-request-manager' ),
			/* translators: email body with placeholders. Do not translate the {tokens}. */
			'requester_body'                 => __( "Hello {name},\n\nWe received your submission for:\n\n{request_title}\n\nFiles received: {file_count}\n\nThank you.", 'renevo-file-request-manager' ),
		);
	}

	/**
	 * Default public-form appearance: the "Classic" design preset.
	 *
	 * @return array
	 */
	public static function default_form_design() {
		return array(
			'layout'                     => 'stacked',
			'preset'                     => 'classic',
			'background_color'           => '#ffffff',
			'page_background_color'      => '',
			'border_color'               => '#d5d7dc',
			'border_radius'              => 8,
			'padding'                    => 16,
			'upload_style'               => 'dropzone',
			'hover_color'                => '#1d4ed8',
			'font_size'                  => 16,
			'font_family'                => 'system',
			'submit_button_color'        => '#2563eb',
			'submit_button_hover_color'  => '#1d4ed8',
			'submit_button_border_color' => '',
			'submit_button_position'     => 'left',
			'file_info_position'         => 'above',
			'file_block_wrap'            => 'card',
			'contact_fields_wrap'        => 'card',
			'required_badge_color'       => '#dc2626',
			'required_indicator'         => 'badge',
			'show_optional_badge'        => true,
		);
	}

	/**
	 * Default UI text overrides: every value matches the hardcoded English
	 * string it replaces, so an unmodified request renders identically.
	 *
	 * @return array
	 */
	public static function default_text_labels() {
		return array(
			'contact_heading'       => __( 'Your details', 'renevo-file-request-manager' ),
			'name_label'            => __( 'Name', 'renevo-file-request-manager' ),
			'email_label'           => __( 'Email', 'renevo-file-request-manager' ),
			'phone_label'           => __( 'Phone', 'renevo-file-request-manager' ),
			'company_label'         => __( 'Company', 'renevo-file-request-manager' ),
			'message_label'         => __( 'Message', 'renevo-file-request-manager' ),
			'submit_button_text'    => __( 'Submit', 'renevo-file-request-manager' ),
			'required_label'        => __( 'Required', 'renevo-file-request-manager' ),
			'optional_label'        => __( 'Optional', 'renevo-file-request-manager' ),
			/* translators: %s is replaced with the list of accepted file types, e.g. "PDF, JPG". Keep the %s token. */
			'accepted_types_text'   => __( 'Accepted: %s', 'renevo-file-request-manager' ),
			/* translators: %d is replaced with the maximum size in megabytes. Keep the %d token. */
			'max_size_text'         => __( 'Maximum size: %d MB', 'renevo-file-request-manager' ),
			/* translators: %d is replaced with the maximum number of files (this exact string is used when that number is 1). Keep the %d token. */
			'max_files_text'        => __( 'Up to %d file', 'renevo-file-request-manager' ),
			/* translators: %d is replaced with the maximum number of files (this exact string is used when that number is greater than 1). Keep the %d token. */
			'max_files_text_plural' => __( 'Up to %d files', 'renevo-file-request-manager' ),
			'upload_area_text'      => __( 'Upload area', 'renevo-file-request-manager' ),
		);
	}

	/**
	 * Builds a Request from raw (REST input or already-stored) data, sanitizing every field.
	 * Keys missing from $data fall back to $base (if given) instead of a blank default.
	 *
	 * @param array      $data Raw associative array.
	 * @param int|null   $id   Existing post ID, if updating.
	 * @param self|null  $base Existing Request to fall back to for omitted fields.
	 * @return self
	 */
	public static function from_array( array $data, $id = null, self $base = null ) {
		$request     = new self();
		$request->id = $id ? absint( $id ) : null;

		$request->title = isset( $data['title'] )
			? sanitize_text_field( $data['title'] )
			: ( $base ? $base->title : '' );

		$request->description = isset( $data['description'] )
			? self::sanitize_description( $data['description'] )
			: ( $base ? $base->description : '' );

		if ( isset( $data['status'] ) ) {
			$status          = sanitize_key( $data['status'] );
			$request->status = in_array( $status, array( 'draft', 'publish' ), true ) ? $status : 'draft';
		} else {
			$request->status = $base ? $base->status : 'draft';
		}

		if ( isset( $data['requested_files'] ) && is_array( $data['requested_files'] ) ) {
			$request->requested_files = array_map( array( RequestedFile::class, 'from_array' ), $data['requested_files'] );
		} else {
			$request->requested_files = $base ? $base->requested_files : array();
		}

		$request->contact_fields = isset( $data['contact_fields'] ) && is_array( $data['contact_fields'] )
			? self::sanitize_contact_fields( $data['contact_fields'] )
			: ( $base ? $base->contact_fields : self::sanitize_contact_fields( array() ) );

		$request->submission_settings = isset( $data['submission_settings'] ) && is_array( $data['submission_settings'] )
			? self::sanitize_submission_settings( $data['submission_settings'] )
			: ( $base ? $base->submission_settings : self::sanitize_submission_settings( array() ) );

		$request->notification_settings = isset( $data['notification_settings'] ) && is_array( $data['notification_settings'] )
			? self::sanitize_notification_settings( $data['notification_settings'] )
			: ( $base ? $base->notification_settings : self::sanitize_notification_settings( array() ) );

		$request->form_design = isset( $data['form_design'] ) && is_array( $data['form_design'] )
			? self::sanitize_form_design( $data['form_design'] )
			: ( $base ? $base->form_design : self::sanitize_form_design( array() ) );

		$request->text_labels = isset( $data['text_labels'] ) && is_array( $data['text_labels'] )
			? self::sanitize_text_labels( $data['text_labels'] )
			: ( $base ? $base->text_labels : self::sanitize_text_labels( array() ) );

		$request->schema_version = 1;

		return $request;
	}

	/**
	 * Sanitizes the description: wp_kses_post()'s tag allowlist (no script,
	 * iframe, object, form) plus style/class on every tag, so inline CSS
	 * survives. wp_kses() runs style values through safecss_filter_attr().
	 *
	 * @param string $html Raw HTML.
	 * @return string
	 */
	private static function sanitize_description( $html ) {
		$allowed = wp_kses_allowed_html( 'post' );

		foreach ( $allowed as $tag => $attrs ) {
			$allowed[ $tag ]['style'] = true;
			$allowed[ $tag ]['class'] = true;
		}

		return wp_kses( $html, $allowed );
	}

	/**
	 * Sanitizes the contact-fields configuration against the fixed set of built-in fields.
	 *
	 * @param array $raw Raw contact field configuration.
	 * @return array
	 */
	private static function sanitize_contact_fields( array $raw ) {
		$defaults = self::default_contact_fields();
		$clean    = array();

		foreach ( self::CONTACT_FIELD_KEYS as $key ) {
			$field   = isset( $raw[ $key ] ) && is_array( $raw[ $key ] ) ? $raw[ $key ] : $defaults[ $key ];
			$enabled = ! empty( $field['enabled'] );

			$clean[ $key ] = array(
				'enabled'  => $enabled,
				'required' => $enabled && ! empty( $field['required'] ),
			);
		}

		return $clean;
	}

	/**
	 * Sanitizes submission behaviour settings.
	 *
	 * @param array $raw Raw submission settings.
	 * @return array
	 */
	private static function sanitize_submission_settings( array $raw ) {
		$defaults = self::default_submission_settings();

		$position = isset( $raw['contact_fields_position'] ) ? sanitize_key( $raw['contact_fields_position'] ) : $defaults['contact_fields_position'];

		return array(
			'success_message'            => isset( $raw['success_message'] ) ? sanitize_textarea_field( $raw['success_message'] ) : $defaults['success_message'],
			'redirect_url'               => isset( $raw['redirect_url'] ) && '' !== $raw['redirect_url'] ? esc_url_raw( $raw['redirect_url'] ) : '',
			'allow_multiple_submissions' => isset( $raw['allow_multiple_submissions'] ) ? (bool) $raw['allow_multiple_submissions'] : $defaults['allow_multiple_submissions'],
			'contact_fields_position'    => in_array( $position, array( 'top', 'bottom' ), true ) ? $position : 'top',
		);
	}

	/**
	 * Sanitizes notification settings.
	 *
	 * @param array $raw Raw notification settings.
	 * @return array
	 */
	private static function sanitize_notification_settings( array $raw ) {
		$defaults = self::default_notification_settings();

		return array(
			'admin_notify_enabled'           => isset( $raw['admin_notify_enabled'] ) ? (bool) $raw['admin_notify_enabled'] : $defaults['admin_notify_enabled'],
			'admin_email'                    => isset( $raw['admin_email'] ) && is_email( $raw['admin_email'] ) ? sanitize_email( $raw['admin_email'] ) : $defaults['admin_email'],
			'admin_subject'                  => isset( $raw['admin_subject'] ) ? sanitize_text_field( $raw['admin_subject'] ) : $defaults['admin_subject'],
			'admin_body'                     => isset( $raw['admin_body'] ) ? sanitize_textarea_field( $raw['admin_body'] ) : $defaults['admin_body'],
			'requester_confirmation_enabled' => isset( $raw['requester_confirmation_enabled'] ) ? (bool) $raw['requester_confirmation_enabled'] : $defaults['requester_confirmation_enabled'],
			'requester_from_name'            => isset( $raw['requester_from_name'] ) ? sanitize_text_field( $raw['requester_from_name'] ) : $defaults['requester_from_name'],
			'requester_from_email'           => isset( $raw['requester_from_email'] ) && is_email( $raw['requester_from_email'] ) ? sanitize_email( $raw['requester_from_email'] ) : $defaults['requester_from_email'],
			'requester_subject'              => isset( $raw['requester_subject'] ) ? sanitize_text_field( $raw['requester_subject'] ) : $defaults['requester_subject'],
			'requester_body'                 => isset( $raw['requester_body'] ) ? sanitize_textarea_field( $raw['requester_body'] ) : $defaults['requester_body'],
		);
	}

	/**
	 * Sanitizes the public form's appearance settings.
	 *
	 * @param array $raw Raw form design settings.
	 * @return array
	 */
	private static function sanitize_form_design( array $raw ) {
		$defaults = self::default_form_design();

		$layout       = isset( $raw['layout'] ) ? sanitize_key( $raw['layout'] ) : $defaults['layout'];
		$preset       = isset( $raw['preset'] ) ? sanitize_key( $raw['preset'] ) : $defaults['preset'];
		$upload       = isset( $raw['upload_style'] ) ? sanitize_key( $raw['upload_style'] ) : $defaults['upload_style'];
		$font         = isset( $raw['font_family'] ) ? sanitize_key( $raw['font_family'] ) : $defaults['font_family'];
		$position     = isset( $raw['submit_button_position'] ) ? sanitize_key( $raw['submit_button_position'] ) : $defaults['submit_button_position'];
		$info_pos     = isset( $raw['file_info_position'] ) ? sanitize_key( $raw['file_info_position'] ) : $defaults['file_info_position'];
		$file_wrap    = isset( $raw['file_block_wrap'] ) ? sanitize_key( $raw['file_block_wrap'] ) : $defaults['file_block_wrap'];
		$contact_wrap = isset( $raw['contact_fields_wrap'] ) ? sanitize_key( $raw['contact_fields_wrap'] ) : $defaults['contact_fields_wrap'];
		$required_ind = isset( $raw['required_indicator'] ) ? sanitize_key( $raw['required_indicator'] ) : $defaults['required_indicator'];

		return array(
			'layout'                     => FormLayoutRegistry::is_valid_key( $layout ) ? $layout : $defaults['layout'],
			'preset'                     => FormDesignRegistry::is_valid_key( $preset ) ? $preset : $defaults['preset'],
			'background_color'           => self::sanitize_design_color( $raw, 'background_color', $defaults ),
			'page_background_color'      => self::sanitize_design_color( $raw, 'page_background_color', $defaults ),
			'border_color'               => self::sanitize_design_color( $raw, 'border_color', $defaults ),
			'border_radius'              => isset( $raw['border_radius'] ) ? min( 40, max( 0, absint( $raw['border_radius'] ) ) ) : $defaults['border_radius'],
			'padding'                    => isset( $raw['padding'] ) ? min( 64, max( 0, absint( $raw['padding'] ) ) ) : $defaults['padding'],
			'upload_style'               => in_array( $upload, array( 'dropzone', 'button' ), true ) ? $upload : $defaults['upload_style'],
			'hover_color'                => self::sanitize_design_color( $raw, 'hover_color', $defaults ),
			'font_size'                  => isset( $raw['font_size'] ) ? min( 24, max( 12, absint( $raw['font_size'] ) ) ) : $defaults['font_size'],
			'font_family'                => in_array( $font, array( 'system', 'sans', 'serif', 'monospace' ), true ) ? $font : $defaults['font_family'],
			'submit_button_color'        => self::sanitize_design_color( $raw, 'submit_button_color', $defaults ),
			'submit_button_hover_color'  => self::sanitize_design_color( $raw, 'submit_button_hover_color', $defaults ),
			'submit_button_border_color' => self::sanitize_design_color( $raw, 'submit_button_border_color', $defaults ),
			'submit_button_position'     => in_array( $position, array( 'left', 'center', 'right' ), true ) ? $position : $defaults['submit_button_position'],
			'file_info_position'         => in_array( $info_pos, array( 'above', 'below' ), true ) ? $info_pos : $defaults['file_info_position'],
			'file_block_wrap'            => in_array( $file_wrap, array( 'card', 'flat' ), true ) ? $file_wrap : $defaults['file_block_wrap'],
			'contact_fields_wrap'        => in_array( $contact_wrap, array( 'card', 'flat' ), true ) ? $contact_wrap : $defaults['contact_fields_wrap'],
			'required_badge_color'       => self::sanitize_design_color( $raw, 'required_badge_color', $defaults ),
			'required_indicator'         => in_array( $required_ind, array( 'badge', 'asterisk' ), true ) ? $required_ind : $defaults['required_indicator'],
			'show_optional_badge'        => isset( $raw['show_optional_badge'] ) ? (bool) $raw['show_optional_badge'] : $defaults['show_optional_badge'],
		);
	}

	/**
	 * Sanitizes one hex color field of the form design settings, falling back to its default.
	 *
	 * @param array  $raw      Raw form design settings.
	 * @param string $key      Field name.
	 * @param array  $defaults Default form design settings.
	 * @return string
	 */
	private static function sanitize_design_color( array $raw, $key, array $defaults ) {
		if ( ! isset( $raw[ $key ] ) ) {
			return $defaults[ $key ];
		}

		$color = sanitize_hex_color( $raw[ $key ] );

		return $color ? $color : $defaults[ $key ];
	}

	/**
	 * Sanitizes UI text overrides. A blank/missing value falls back to the
	 * default string rather than rendering an empty label.
	 *
	 * @param array $raw Raw text label overrides.
	 * @return array
	 */
	private static function sanitize_text_labels( array $raw ) {
		$defaults = self::default_text_labels();
		$clean    = array();

		foreach ( $defaults as $key => $default_value ) {
			$value         = isset( $raw[ $key ] ) ? sanitize_text_field( $raw[ $key ] ) : '';
			$clean[ $key ] = '' !== $value ? $value : $default_value;
		}

		return $clean;
	}

	/**
	 * Finds a requested file definition by its stable key.
	 *
	 * @param string $key Requested file key.
	 * @return RequestedFile|null
	 */
	public function get_requested_file( $key ) {
		foreach ( $this->requested_files as $file ) {
			if ( $file->key === $key ) {
				return $file;
			}
		}

		return null;
	}

	/**
	 * Converts the object to a plain array for REST output or meta storage.
	 *
	 * @return array
	 */
	public function to_array() {
		return array(
			'id'                    => $this->id,
			'title'                 => $this->title,
			'description'           => $this->description,
			'status'                => $this->status,
			'requested_files'       => array_map(
				static function ( RequestedFile $file ) {
					return $file->to_array();
				},
				$this->requested_files
			),
			'contact_fields'        => $this->contact_fields,
			'submission_settings'   => $this->submission_settings,
			'notification_settings' => $this->notification_settings,
			'form_design'           => $this->form_design,
			'text_labels'           => $this->text_labels,
			'schema_version'        => $this->schema_version,
			'created_at'            => $this->created_at,
			'updated_at'            => $this->updated_at,
		);
	}

	/**
	 * Converts the object to the reduced, non-sensitive shape served to the public frontend.
	 *
	 * Excludes notification settings (admin email/subject/body) entirely.
	 *
	 * @return array
	 */
	public function to_public_array() {
		return array(
			'id'                  => $this->id,
			'title'               => $this->title,
			'description'         => $this->description,
			'status'              => $this->status,
			'requested_files'     => array_map(
				static function ( RequestedFile $file ) {
					return $file->to_array();
				},
				$this->requested_files
			),
			'contact_fields'      => $this->contact_fields,
			'submission_settings' => $this->submission_settings,
			'form_design'         => $this->form_design,
			'text_labels'         => $this->text_labels,
		);
	}
}
