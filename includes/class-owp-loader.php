<?php
/**
 * Main plugin loader.
 *
 * @package OpenAI_WP_Integration_Pro
 */

class Owp_Loader {
	/**
	 * Admin handler instance.
	 *
	 * @var Owp_Admin
	 */
	private $admin;

	/**
	 * REST API handler instance.
	 *
	 * @var Owp_REST_API
	 */
	private $rest_api;

	/**
	 * Initialize loader dependencies.
	 */
	public function __construct() {
		$this->admin    = new Owp_Admin();
		$this->rest_api = new Owp_REST_API();
	}

	/**
	 * Execute plugin hooks.
	 *
	 * @return void
	 */
	public function run() {
		if ( is_admin() ) {
			$this->admin->register();
		}

		add_action( 'rest_api_init', array( $this->rest_api, 'register_routes' ) );
	}
}
