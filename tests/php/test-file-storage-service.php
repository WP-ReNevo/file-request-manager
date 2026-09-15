<?php
/**
 * Tests for FileRequestManager\Services\FileStorageService::build_zip().
 *
 * @package FileRequestManager
 */

use FileRequestManager\Services\FileStorageService;

/**
 * Class Test_File_Storage_Service
 */
class Test_File_Storage_Service extends WP_UnitTestCase {

	/**
	 * @var FileStorageService
	 */
	private $storage;

	/**
	 * @var string[]
	 */
	private $temp_files = array();

	/**
	 * @var string[]
	 */
	private $temp_zips = array();

	public function setUp(): void {
		parent::setUp();
		$this->storage    = new FileStorageService();
		$this->temp_files = array();
		$this->temp_zips  = array();
	}

	public function tearDown(): void {
		foreach ( $this->temp_files as $path ) {
			if ( file_exists( $path ) ) {
				wp_delete_file( $path );
			}
		}
		foreach ( $this->temp_zips as $path ) {
			if ( file_exists( $path ) ) {
				wp_delete_file( $path );
			}
		}
		parent::tearDown();
	}

	/**
	 * Creates a real temp file with the given content and tracks it for cleanup.
	 *
	 * @param string $content File content.
	 * @return string Absolute path.
	 */
	private function make_temp_file( $content ) {
		$path = $this->storage->temp_dir() . '/' . wp_generate_uuid4() . '.txt';
		file_put_contents( $path, $content ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		$this->temp_files[] = $path;

		return $path;
	}

	public function test_build_zip_creates_archive_with_folder_structure() {
		$logo_path = $this->make_temp_file( 'logo-bytes' );
		$team_path = $this->make_temp_file( 'team-bytes' );

		$zip_path          = $this->storage->build_zip(
			array(
				array(
					'path'         => $logo_path,
					'archive_name' => 'REQ-1001/logo/image.png',
				),
				array(
					'path'         => $team_path,
					'archive_name' => 'REQ-1001/team-photos/mark.jpg',
				),
			)
		);
		$this->temp_zips[] = $zip_path;

		$this->assertIsString( $zip_path );
		$this->assertFileExists( $zip_path );

		$zip = new ZipArchive();
		$zip->open( $zip_path );

		$this->assertSame( 'logo-bytes', $zip->getFromName( 'REQ-1001/logo/image.png' ) );
		$this->assertSame( 'team-bytes', $zip->getFromName( 'REQ-1001/team-photos/mark.jpg' ) );

		$zip->close();
	}

	public function test_build_zip_skips_entries_whose_file_is_missing() {
		$logo_path = $this->make_temp_file( 'logo-bytes' );

		$zip_path          = $this->storage->build_zip(
			array(
				array(
					'path'         => $logo_path,
					'archive_name' => 'REQ-1001/logo/image.png',
				),
				array(
					'path'         => $this->storage->temp_dir() . '/does-not-exist.png',
					'archive_name' => 'REQ-1001/missing/ghost.png',
				),
			)
		);
		$this->temp_zips[] = $zip_path;

		$zip = new ZipArchive();
		$zip->open( $zip_path );

		$this->assertSame( 1, $zip->numFiles ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- PHP core ZipArchive property.
		$this->assertSame( 'logo-bytes', $zip->getFromName( 'REQ-1001/logo/image.png' ) );

		$zip->close();
	}
}
