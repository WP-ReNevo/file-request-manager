<?php
/**
 * Tests for FileRequestManager\Services\PlaceholderResolver.
 *
 * @package FileRequestManager
 */

use FileRequestManager\Services\PlaceholderResolver;

/**
 * Class Test_Placeholder_Resolver
 */
class Test_Placeholder_Resolver extends WP_UnitTestCase {

	/**
	 * @var PlaceholderResolver
	 */
	private $resolver;

	public function setUp(): void {
		parent::setUp();
		$this->resolver = new PlaceholderResolver();
	}

	public function test_replaces_known_tokens() {
		$result = $this->resolver->resolve(
			'Hello {name}, thanks for submitting to {request_title}.',
			array(
				'name'          => 'Ada',
				'request_title' => 'Onboarding documents',
			)
		);

		$this->assertSame( 'Hello Ada, thanks for submitting to Onboarding documents.', $result );
	}

	public function test_unresolved_token_becomes_empty_string_not_left_literal() {
		$result = $this->resolver->resolve( 'Hello {name}, your code is {unknown_token}.', array( 'name' => 'Ada' ) );

		$this->assertSame( 'Hello Ada, your code is .', $result );
		$this->assertStringNotContainsString( '{unknown_token}', $result );
	}

	public function test_unresolved_token_does_not_trigger_a_php_notice() {
		// A naive implementation might do array access on a missing key and
		// emit an "Undefined array key" notice. PHPUnit's error handler
		// promotes notices/warnings to visible failures in strict setups, so
		// simply asserting the return value already proves none occurred —
		// but we assert explicitly here for clarity of intent.
		$error_triggered = false;
		set_error_handler( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler
			static function () use ( &$error_triggered ) {
				$error_triggered = true;
				return true;
			}
		);

		$result = $this->resolver->resolve( '{totally_missing}', array() );

		restore_error_handler();

		$this->assertFalse( $error_triggered );
		$this->assertSame( '', $result );
	}

	public function test_multiple_occurrences_of_same_token_all_replaced() {
		$result = $this->resolver->resolve( '{x}-{x}-{x}', array( 'x' => '7' ) );

		$this->assertSame( '7-7-7', $result );
	}

	public function test_context_is_filterable() {
		$add_token = static function ( $context ) {
			$context['injected'] = 'from-filter';
			return $context;
		};

		add_filter( 'renevo_email_placeholders', $add_token );

		try {
			$result = $this->resolver->resolve( 'Value: {injected}', array() );
			$this->assertSame( 'Value: from-filter', $result );
		} finally {
			remove_filter( 'renevo_email_placeholders', $add_token );
		}
	}
}
