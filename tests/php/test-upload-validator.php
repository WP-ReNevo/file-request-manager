<?php
/**
 * Tests for FileRequestManager\Services\UploadValidator.
 *
 * @package FileRequestManager
 */

// phpcs:disable Universal.Namespaces.DisallowCurlyBraceSyntax.Forbidden, Universal.Namespaces.OneDeclarationPerFile.MultipleFound, Universal.Namespaces.DisallowDeclarationWithoutName.Forbidden, Universal.Files.SeparateFunctionsFromOO.Mixed
// This file deliberately mixes a named namespace block with a global one —
// the curly-brace multi-namespace syntax is the only way to do that in PHP
// — to install a namespaced is_uploaded_file() override (see the docblock
// below) alongside the global-namespace test class that relies on it.

namespace FileRequestManager\Services {

	/**
	 * Overrides is_uploaded_file() for code running inside this namespace only.
	 *
	 * PHP resolves an unqualified function call from within a namespace by
	 * first looking for that function *in the current namespace*, falling
	 * back to the global one only if it isn't defined. UploadValidator (in
	 * this same namespace) calls the bare `is_uploaded_file()`, which real
	 * PHP only ever returns true for a file that arrived via an actual
	 * multipart/form-data HTTP upload — never true for a plain temp file
	 * written by a test. Defining it here lets the real validation logic
	 * (extension/mime/size checks) be exercised without a real HTTP request.
	 */
	function is_uploaded_file( $filename ) {
		return file_exists( $filename );
	}
}

namespace {

	use FileRequestManager\Domain\Request;
	use FileRequestManager\Domain\RequestedFile;
	use FileRequestManager\Services\UploadValidator;

	// WP_Error is already in the global namespace here, so no `use` is needed
	// (and PHP warns on a `use` of a non-compound global name).

	/**
	 * Class Test_Upload_Validator
	 */
	class Test_Upload_Validator extends WP_UnitTestCase {

		/**
		 * @var UploadValidator
		 */
		private $validator;

		/**
		 * @var string[]
		 */
		private $temp_files = array();

		public function setUp(): void {
			parent::setUp();
			$this->validator  = new UploadValidator();
			$this->temp_files = array();
		}

		public function tearDown(): void {
			foreach ( $this->temp_files as $path ) {
				if ( file_exists( $path ) ) {
					wp_delete_file( $path );
				}
			}
			parent::tearDown();
		}

		/**
		 * Writes a temp file whose content starts with a real PDF signature,
		 * so wp_check_filetype_and_ext()'s fileinfo content-sniffing recognizes
		 * it as application/pdf regardless of the file's actual extension.
		 *
		 * @return string Absolute path.
		 */
		private function make_pdf_like_file() {
			$path = wp_tempnam( 'renevo-test-upload' );
			file_put_contents( $path, "%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			$this->temp_files[] = $path;

			return $path;
		}

		/**
		 * Writes a plain-text temp file (no particular signature).
		 *
		 * @return string Absolute path.
		 */
		private function make_text_file() {
			$path = wp_tempnam( 'renevo-test-upload' );
			file_put_contents( $path, 'just some plain text content' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			$this->temp_files[] = $path;

			return $path;
		}

		private function requested_file( array $overrides = array() ) {
			return RequestedFile::from_array(
				array_merge(
					array(
						'key'           => 'identity-doc',
						'title'         => 'Identity document',
						'required'      => true,
						'allowed_types' => array( 'pdf' ),
						'max_size_mb'   => 1,
						'max_files'     => 1,
					),
					$overrides
				)
			);
		}

		public function test_valid_pdf_within_size_is_accepted() {
			$path = $this->make_pdf_like_file();
			$file = array(
				'tmp_name' => $path,
				'name'     => 'identity.pdf',
				'size'     => 1024,
				'error'    => UPLOAD_ERR_OK,
			);

			$result = $this->validator->validate( $file, $this->requested_file() );

			$this->assertTrue( $result );
		}

		public function test_rejects_extension_not_in_allowed_types() {
			$path = $this->make_text_file();
			$file = array(
				'tmp_name' => $path,
				'name'     => 'notes.txt',
				'size'     => 20,
				'error'    => UPLOAD_ERR_OK,
			);

			// Requested file only allows 'pdf'; a small, otherwise-harmless .txt
			// file must still be rejected purely on extension.
			$result = $this->validator->validate( $file, $this->requested_file( array( 'allowed_types' => array( 'pdf' ) ) ) );

			$this->assertInstanceOf( WP_Error::class, $result );
			$this->assertSame( 'renevo_type_not_allowed', $result->get_error_code() );
		}

		public function test_rejects_file_over_max_size() {
			$path = $this->make_pdf_like_file();
			$file = array(
				'tmp_name' => $path,
				'name'     => 'identity.pdf',
				// UploadValidator trusts the reported size field (as a real
				// upload handler would populate from $_FILES), so we don't need
				// an actual multi-megabyte file on disk to exercise this path.
				'size'     => 5 * MB_IN_BYTES,
				'error'    => UPLOAD_ERR_OK,
			);

			$result = $this->validator->validate( $file, $this->requested_file( array( 'max_size_mb' => 1 ) ) );

			$this->assertInstanceOf( WP_Error::class, $result );
			$this->assertSame( 'renevo_too_large', $result->get_error_code() );
		}

		public function test_rejects_empty_file() {
			$path = $this->make_pdf_like_file();
			$file = array(
				'tmp_name' => $path,
				'name'     => 'identity.pdf',
				'size'     => 0,
				'error'    => UPLOAD_ERR_OK,
			);

			$result = $this->validator->validate( $file, $this->requested_file() );

			$this->assertInstanceOf( WP_Error::class, $result );
			$this->assertSame( 'renevo_empty_file', $result->get_error_code() );
		}

		public function test_hard_denied_extension_wins_even_if_admin_allowed_it() {
			// Simulate a misconfiguration: a filter adds a fake type whose
			// extension is `.php` to the selectable registry, and a requested
			// file is configured to allow it.
			$add_evil_type = static function ( $types ) {
				$types['evil'] = array(
					'label'      => 'Evil',
					'extensions' => array( 'php' ),
					'mimes'      => array( 'application/x-httpd-php' ),
				);
				return $types;
			};

			add_filter( 'renevo_requested_file_types', $add_evil_type );

			try {
				$requested_file = $this->requested_file( array( 'allowed_types' => array( 'evil' ) ) );
				$this->assertSame( array( 'evil' ), $requested_file->allowed_types, 'Sanity check: the fake type key was accepted.' );

				$path = wp_tempnam( 'renevo-test-upload' );
				file_put_contents( $path, '<?php echo "not a real file, content does not matter"; ?>' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
				$this->temp_files[] = $path;

				$file = array(
					'tmp_name' => $path,
					'name'     => 'shell.php',
					'size'     => 50,
					'error'    => UPLOAD_ERR_OK,
				);

				$result = $this->validator->validate( $file, $requested_file );

				$this->assertInstanceOf( WP_Error::class, $result );
				$this->assertSame( 'renevo_type_not_allowed', $result->get_error_code() );
			} finally {
				remove_filter( 'renevo_requested_file_types', $add_evil_type );
			}
		}

		public function test_validate_required_coverage_lists_missing_titles() {
			$request                  = new Request();
			$request->requested_files = array(
				$this->requested_file(
					array(
						'key'      => 'id-doc',
						'title'    => 'Identity document',
						'required' => true,
					)
				),
				$this->requested_file(
					array(
						'key'      => 'proof-address',
						'title'    => 'Proof of address',
						'required' => true,
					)
				),
				$this->requested_file(
					array(
						'key'      => 'optional-doc',
						'title'    => 'Optional extra',
						'required' => false,
					)
				),
			);

			$result = $this->validator->validate_required_coverage( $request, array( 'id-doc' => 1 ) );

			$this->assertInstanceOf( WP_Error::class, $result );
			$this->assertSame( array( 'Proof of address' ), $result->get_error_data()['missing'] );
		}

		public function test_validate_required_coverage_passes_when_all_required_present() {
			$request                  = new Request();
			$request->requested_files = array(
				$this->requested_file(
					array(
						'key'      => 'id-doc',
						'title'    => 'Identity document',
						'required' => true,
					)
				),
				$this->requested_file(
					array(
						'key'      => 'optional-doc',
						'title'    => 'Optional extra',
						'required' => false,
					)
				),
			);

			$result = $this->validator->validate_required_coverage( $request, array( 'id-doc' => 1 ) );

			$this->assertTrue( $result );
		}
	}
}
