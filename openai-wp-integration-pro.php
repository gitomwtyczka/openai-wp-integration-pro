<?php
/**
 * Plugin Name:       OpenAI WP Integration Pro
 * Plugin URI:        https://example.com/openai-wp-integration-pro
 * Description:       Integracja WordPress z OpenAI/ChatGPT i YouTube.
 * Version:           0.3.0
 * Author:            Your Name
 * Author URI:        https://example.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       openai-wp-integration-pro
 * Requires at least: 6.0
 * Requires PHP:      7.4
 *
 * @package OpenAI_WP_Integration_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'OWP_INTEGRATION_PRO_VERSION', '0.3.0' );
define( 'OWP_INTEGRATION_PRO_PATH', plugin_dir_path( __FILE__ ) );
define( 'OWP_INTEGRATION_PRO_URL', plugin_dir_url( __FILE__ ) );

autoload_owp_integration_pro();

/**
 * Registers class autoloading for the plugin.
 *
 * @return void
 */
function autoload_owp_integration_pro() {
	spl_autoload_register(
		function ( $class_name ) {
			if ( 0 !== strpos( $class_name, 'Owp_' ) ) {
				return;
			}

			$file_name = 'class-' . strtolower( str_replace( '_', '-', $class_name ) ) . '.php';
			$file_path = trailingslashit( OWP_INTEGRATION_PRO_PATH . 'includes' ) . $file_name;

			if ( file_exists( $file_path ) ) {
				require_once $file_path;
			}
		}
	);
}

/**
 * Starts plugin execution.
 *
 * @return void
 */
function run_owp_integration_pro() {
	$loader = new Owp_Loader();
	$loader->run();
}

run_owp_integration_pro();
