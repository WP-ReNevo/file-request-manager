<?php
/**
 * Central {token} placeholder replacement for emails and instructions.
 *
 * @package FileRequestManager
 */

namespace FileRequestManager\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Central {token} placeholder replacement for emails and instructions. Unresolved tokens become ''.
 */
class PlaceholderResolver {

	/**
	 * Replaces every `{token}` in a template with values from a context array.
	 *
	 * @param string               $template Raw template text.
	 * @param array<string, mixed> $context  Placeholder name => value (without braces).
	 * @return string
	 */
	public function resolve( $template, array $context ) {
		/**
		 * Filters the available placeholder context before resolution.
		 *
		 * Allows a future Pro add-on to add new tokens without patching this class.
		 *
		 * @param array $context Placeholder name => value.
		 */
		$context = apply_filters( 'renevo_email_placeholders', $context );

		return (string) preg_replace_callback(
			'/\{([a-zA-Z0-9_]+)\}/',
			static function ( $matches ) use ( $context ) {
				$key = $matches[1];

				return array_key_exists( $key, $context ) ? (string) $context[ $key ] : '';
			},
			(string) $template
		);
	}
}
