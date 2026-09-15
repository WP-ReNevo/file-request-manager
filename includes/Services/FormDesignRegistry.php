<?php
/**
 * Registry of public-form design templates.
 *
 * @package FileRequestManager
 */

namespace FileRequestManager\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registry of built-in templates. Each template names a `layout` (structure,
 * see FormLayoutRegistry) and a set of `defaults` — plain `form_design`
 * field values (colors, spacing, etc., see Request::default_form_design()).
 * Picking a template copies those values into the editable fields once; it
 * is a starting point, not an ongoing mode, so every value stays editable
 * afterward with no separate "Custom" state.
 *
 * Filterable via `renevo_form_design_presets` so a Pro add-on (or a site's own
 * `functions.php`) can register additional templates, with no code changes here.
 */
class FormDesignRegistry {

	/**
	 * Gets every registered template, keyed by template key.
	 *
	 * @return array<string, array{label: string, description: string, layout: string, defaults: array}>
	 */
	public static function all() {
		$templates = array(
			'classic'   => self::classic(),
			'modern'    => self::modern(),
			'editorial' => self::editorial(),
		);

		/**
		 * Filters the registry of form design templates.
		 *
		 * @param array $templates Templates keyed by template key.
		 */
		return apply_filters( 'renevo_form_design_presets', $templates );
	}

	/**
	 * Whether a template key exists in the registry.
	 *
	 * @param string $key Template key.
	 * @return bool
	 */
	public static function is_valid_key( $key ) {
		return array_key_exists( $key, self::all() );
	}

	/**
	 * "Classic": white, a subtle border, blue accent, one column.
	 *
	 * @return array
	 */
	private static function classic() {
		return array(
			'label'       => __( 'Classic', 'renevo-file-request-manager' ),
			'description' => __( 'Clean and familiar, with a subtle border.', 'renevo-file-request-manager' ),
			'layout'      => 'stacked',
			'defaults'    => array(
				'background_color'           => '#ffffff',
				'page_background_color'      => '',
				'border_color'               => '#d5d7dc',
				'border_radius'              => 8,
				'padding'                    => 16,
				'hover_color'                => '#1d4ed8',
				'font_family'                => 'system',
				'font_size'                  => 16,
				'submit_button_color'        => '#2563eb',
				'submit_button_hover_color'  => '#1d4ed8',
				'submit_button_border_color' => '',
				'required_badge_color'       => '#dc2626',
				'file_block_wrap'            => 'card',
				'contact_fields_wrap'        => 'card',
			),
		);
	}

	/**
	 * "Modern": violet accent, rounded corners, requested files as tiles.
	 *
	 * @return array
	 */
	private static function modern() {
		return array(
			'label'       => __( 'Modern', 'renevo-file-request-manager' ),
			'description' => __( 'Rounded corners, a violet accent, requested files as tiles.', 'renevo-file-request-manager' ),
			'layout'      => 'grid',
			'defaults'    => array(
				'background_color'           => '#ffffff',
				'page_background_color'      => '',
				'border_color'               => '#e4defb',
				'border_radius'              => 20,
				'padding'                    => 24,
				'hover_color'                => '#6d28d9',
				'font_family'                => 'sans',
				'font_size'                  => 16,
				'submit_button_color'        => '#7c3aed',
				'submit_button_hover_color'  => '#6d28d9',
				'submit_button_border_color' => '',
				'required_badge_color'       => '#7c3aed',
				'file_block_wrap'            => 'card',
				'contact_fields_wrap'        => 'card',
			),
		);
	}

	/**
	 * "Editorial": borderless, warm off-white, numbered entries, serif type.
	 *
	 * @return array
	 */
	private static function editorial() {
		return array(
			'label'       => __( 'Editorial', 'renevo-file-request-manager' ),
			'description' => __( 'Borderless, numbered entries, big bold type.', 'renevo-file-request-manager' ),
			'layout'      => 'editorial',
			'defaults'    => array(
				'background_color'           => '#fdfcf9',
				'page_background_color'      => '',
				'border_color'               => '#e7e2d8',
				'border_radius'              => 0,
				'padding'                    => 32,
				'hover_color'                => '#9a3412',
				'font_family'                => 'serif',
				'font_size'                  => 17,
				'submit_button_color'        => '#18181b',
				'submit_button_hover_color'  => '#3f3f46',
				'submit_button_border_color' => '',
				'required_badge_color'       => '#c2410c',
				'file_block_wrap'            => 'flat',
				'contact_fields_wrap'        => 'flat',
			),
		);
	}
}
