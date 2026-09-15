<?php
/**
 * Registry of public-form structural layouts.
 *
 * @package FileRequestManager
 */

namespace FileRequestManager\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registry of built-in layout metadata (label/description only — the actual
 * React component lives client-side, registered via `window.renevo.registerLayout()`,
 * see src/shared/layoutRegistry.js). Filterable via `renevo_form_layouts` so a
 * Pro add-on can register a new layout's metadata here (for the picker UI
 * and server-side validation) alongside its own JS component registration —
 * the same two-part pattern WordPress itself uses for blocks.
 */
class FormLayoutRegistry {

	/**
	 * Gets every registered layout, keyed by layout key.
	 *
	 * @return array<string, array{label: string, description: string}>
	 */
	public static function all() {
		$layouts = array(
			'stacked'   => array(
				'label'       => __( 'Stacked', 'renevo-file-request-manager' ),
				'description' => __( 'One column, requested files listed top to bottom.', 'renevo-file-request-manager' ),
			),
			'grid'      => array(
				'label'       => __( 'Grid', 'renevo-file-request-manager' ),
				'description' => __( 'Requested files arranged as tiles.', 'renevo-file-request-manager' ),
			),
			'editorial' => array(
				'label'       => __( 'Editorial', 'renevo-file-request-manager' ),
				'description' => __( 'Borderless, numbered entries, big bold type.', 'renevo-file-request-manager' ),
			),
		);

		/**
		 * Filters the registry of form layouts.
		 *
		 * @param array $layouts Layouts keyed by layout key.
		 */
		return apply_filters( 'renevo_form_layouts', $layouts );
	}

	/**
	 * Whether a layout key exists in the registry.
	 *
	 * @param string $key Layout key.
	 * @return bool
	 */
	public static function is_valid_key( $key ) {
		return array_key_exists( $key, self::all() );
	}
}
