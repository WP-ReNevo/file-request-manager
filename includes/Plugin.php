<?php
/**
 * Core plugin bootstrap: a lightweight service container + hook manager.
 *
 * @package FileRequestManager
 */

namespace FileRequestManager;

use FileRequestManager\Admin\AdminMenu;
use FileRequestManager\Admin\Assets;
use FileRequestManager\Blocks\FileRequestBlock;
use FileRequestManager\Core\Migrations;
use FileRequestManager\Core\PostType;
use FileRequestManager\Frontend\RequestRenderer;
use FileRequestManager\Frontend\Shortcode;
use FileRequestManager\Privacy\Privacy;
use FileRequestManager\Repositories\RequestRepository;
use FileRequestManager\Repositories\SubmissionRepository;
use FileRequestManager\Rest\PublicController;
use FileRequestManager\Rest\RequestsController;
use FileRequestManager\Rest\SettingsController;
use FileRequestManager\Rest\SubmissionsController;
use FileRequestManager\Services\FileStorageService;
use FileRequestManager\Services\NotificationService;
use FileRequestManager\Services\PlaceholderResolver;
use FileRequestManager\Services\TemplateRegistry;
use FileRequestManager\Services\UploadValidator;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Service container and hook manager for the plugin.
 */
final class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Shared service instances, keyed by class name.
	 *
	 * @var array<string, object>
	 */
	private $services = array();

	/**
	 * Whether boot() has already run.
	 *
	 * @var bool
	 */
	private $booted = false;

	/**
	 * Gets the singleton instance.
	 *
	 * @return Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Private constructor, use instance().
	 */
	private function __construct() {}

	/**
	 * Builds services and registers all WordPress hooks.
	 *
	 * @return void
	 */
	public function boot() {
		if ( $this->booted ) {
			return;
		}
		$this->booted = true;

		$this->build_services();

		Migrations::maybe_upgrade();

		add_action( 'init', array( $this->get( PostType::class ), 'register' ) );
		add_action( 'init', array( $this->get( Shortcode::class ), 'register' ) );
		add_action( 'init', array( $this->get( FileRequestBlock::class ), 'register' ) );

		add_action( 'rest_api_init', array( $this->get( RequestsController::class ), 'register_routes' ) );
		add_action( 'rest_api_init', array( $this->get( SubmissionsController::class ), 'register_routes' ) );
		add_action( 'rest_api_init', array( $this->get( SettingsController::class ), 'register_routes' ) );
		add_action( 'rest_api_init', array( $this->get( PublicController::class ), 'register_routes' ) );

		add_action( 'admin_menu', array( $this->get( AdminMenu::class ), 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this->get( Assets::class ), 'enqueue_admin_assets' ) );
		add_action( 'enqueue_block_editor_assets', array( $this->get( Assets::class ), 'enqueue_block_editor_assets' ) );
		add_action( 'wp_enqueue_scripts', array( $this->get( Assets::class ), 'enqueue_frontend_assets' ) );

		$this->get( Privacy::class )->register();

		// Safety net for sites that had the plugin active before this hook existed —
		// activation alone won't re-run for them, so ensure it's scheduled on every load too.
		if ( ! wp_next_scheduled( 'renevo_hourly_maintenance' ) ) {
			wp_schedule_event( time(), 'hourly', 'renevo_hourly_maintenance' );
		}

		add_action( 'renevo_hourly_maintenance', array( $this->get( FileStorageService::class ), 'purge_orphaned_temp_files' ) );
		add_action( 'renevo_daily_maintenance', array( $this->get( SubmissionRepository::class ), 'purge_expired_submissions' ) );

		do_action( 'renevo_loaded', $this );
	}

	/**
	 * Instantiates every shared service used by the plugin.
	 *
	 * @return void
	 */
	private function build_services() {
		$this->services[ PlaceholderResolver::class ] = new PlaceholderResolver();
		$this->services[ TemplateRegistry::class ]    = new TemplateRegistry();

		$this->services[ RequestRepository::class ]    = new RequestRepository();
		$this->services[ FileStorageService::class ]   = new FileStorageService();
		$this->services[ SubmissionRepository::class ] = new SubmissionRepository( $this->get( FileStorageService::class ) );

		$this->services[ UploadValidator::class ]     = new UploadValidator();
		$this->services[ NotificationService::class ] = new NotificationService( $this->get( PlaceholderResolver::class ) );

		$this->services[ PostType::class ] = new PostType();

		$this->services[ RequestRenderer::class ]  = new RequestRenderer( $this->get( RequestRepository::class ) );
		$this->services[ Shortcode::class ]        = new Shortcode( $this->get( RequestRenderer::class ) );
		$this->services[ FileRequestBlock::class ] = new FileRequestBlock( $this->get( RequestRenderer::class ) );

		$this->services[ RequestsController::class ]    = new RequestsController( $this->get( RequestRepository::class ), $this->get( TemplateRegistry::class ) );
		$this->services[ SubmissionsController::class ] = new SubmissionsController( $this->get( SubmissionRepository::class ), $this->get( FileStorageService::class ), $this->get( RequestRepository::class ) );
		$this->services[ SettingsController::class ]    = new SettingsController();
		$this->services[ PublicController::class ]      = new PublicController(
			$this->get( RequestRepository::class ),
			$this->get( SubmissionRepository::class ),
			$this->get( FileStorageService::class ),
			$this->get( UploadValidator::class ),
			$this->get( NotificationService::class )
		);

		$this->services[ AdminMenu::class ] = new AdminMenu();
		$this->services[ Assets::class ]    = new Assets();
		$this->services[ Privacy::class ]   = new Privacy( $this->get( SubmissionRepository::class ), $this->get( FileStorageService::class ) );

		do_action( 'renevo_services_ready', $this );
	}

	/**
	 * Gets a shared service instance by class name.
	 *
	 * @param string $class_name Fully qualified class name.
	 * @return object
	 */
	public function get( $class_name ) {
		return $this->services[ $class_name ];
	}
}
