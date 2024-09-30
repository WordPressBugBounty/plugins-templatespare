<?php
/**
 * Plugin Name: TemplateSpare - Fast WordPress Site Builder
 * Plugin URI: https://templatespare.com/?uri=plugin
 * Description: 1000+ Starter Sites & Templates for Blogs, News, eCommerce & More. Customizer, Gutenberg & Elementor Ready. Import, Personalize, Go Live – No Coding Required
 * Version: 2.4.4
 * Author:            TemplateSpare
 * Author URI:        https://templatespare.com/
 * Text Domain:       templatespare
 * License:           GPLv3 or later
 * License URI:       https://www.gnu.org/licenses/gpl.html
 */
 

 /**
 * Exit if accessed directly
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

defined('AFTMLS_BASE_FILE') or define('AFTMLS_BASE_FILE', __FILE__);
defined('AFTMLS_PLUGIN_BASE') or define('AFTMLS_PLUGIN_BASE', plugin_basename( AFTMLS_BASE_FILE ));
defined('AFTMLS_BASE_DIR') or define('AFTMLS_BASE_DIR', dirname(AFTMLS_BASE_FILE));
defined('AFTMLS_PLUGIN_URL') or define('AFTMLS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AFTMLS_PLUGIN_DIR', plugin_dir_path(__FILE__));


if ( ! function_exists( 'templatespare_main_plugin_file' ) ) {
	/**
	 * Returns the full path and filename of the main Afthemes Templates  plugin file.
	 *
	 * @return string
	 */
	function templatespare_main_plugin_file() {
		return __FILE__;
	}
	

	// Load the rest of the plugin.
	//require_once plugin_dir_path( __FILE__ ) . 'loader.php';

	$aftmls_includes_dir = AFTMLS_PLUGIN_DIR . 'includes/';
	require  $aftmls_includes_dir.'templatespare-kit.php';
	require_once $aftmls_includes_dir. 'layouts/demo-data-lists.php';
	require_once $aftmls_includes_dir. 'layouts/theme-bundle-list.php';
    require  $aftmls_includes_dir.'init.php';  
	require $aftmls_includes_dir. 'companion/class-aftc-main.php';

	// Instantiate the main plugin class *Singleton*.
	$AFMLS_Companion = AFTMLS_Companion::getInstance();  
	

    /**
	 * Layout Component Registry.
	 */
	if ( PHP_VERSION_ID >= 50600 ) {
		require_once AFTMLS_PLUGIN_DIR. 'includes/layouts/layout-endpoints.php';
		
		
	}
}
add_action('init','templatespare_main_plugin_file');


function templatespare_activation_redirect( $plugin ) {
    // Check if we're in the admin and if it's not an AJAX request
    if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
        return;
    }

    // Skip redirection if running in WordPress Playground
    if ( isset( $_SERVER['HTTP_HOST'] ) && strpos( $_SERVER['HTTP_HOST'], 'playground' ) !== false ) {
        return; // Skip redirection in the WordPress Playground environment
    }

    // Check if the activated plugin matches our plugin
    if ( is_admin() && $plugin == plugin_basename( __FILE__ ) ) {
        add_action( 'admin_init', 'templatespare_do_redirect' );
    }
}
add_action( 'activated_plugin', 'templatespare_activation_redirect' );

function templatespare_do_redirect() {
    $redirect_url = add_query_arg( array( 'page' => 'templatespare-main-dashboard' ), admin_url( 'admin.php' ) );
    
    // Safely redirect to the desired admin page if no headers have been sent
    if ( ! headers_sent() ) {
        wp_safe_redirect( $redirect_url );
        exit;
    }
}



