<?php

/**
 * Plugin Name:       TemplateSpare – Site Demo Importer & Exporter
 * Plugin URI:        https://templatespare.com/?uri=plugin
 * Description:       Build & migrate news, magazine, blog, and eCommerce sites in minutes. TemplateSpare delivers 1000+ professional starter templates, 1-click demo imports, and seamless site migration tools for Elementor, Gutenberg & AF themes.
 * Version:           4.5.0
 * Author:            AF themes
 * Author URI:        https://afthemes.com/
 * Text Domain:       templatespare
 * License:           GPLv3 or later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 */

// Exit if accessed directly.
if ( ! defined( 'ABSOLUTE_PATH' ) && ! defined( 'ABSPATH' ) ) {
    exit;
}

defined('AFTMLS_BASE_FILE') or define('AFTMLS_BASE_FILE', __FILE__);
defined('AFTMLS_PLUGIN_BASE') or define('AFTMLS_PLUGIN_BASE', plugin_basename(AFTMLS_BASE_FILE));
defined('AFTMLS_BASE_DIR') or define('AFTMLS_BASE_DIR', dirname(AFTMLS_BASE_FILE));
defined('AFTMLS_PLUGIN_URL') or define('AFTMLS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AFTMLS_PLUGIN_DIR', plugin_dir_path(__FILE__));

if (!function_exists('templatespare_main_plugin_file')) {
  /**
   * Returns the full path and filename of the main Afthemes Templates  plugin file.
   *
   * @return string
   */
  function templatespare_main_plugin_file()
  {
    return __FILE__;
  }

  // Load the rest of the plugin.
  //require_once plugin_dir_path( __FILE__ ) . 'loader.php';

  $aftmls_includes_dir = AFTMLS_PLUGIN_DIR . 'includes/';
  require $aftmls_includes_dir . 'templatespare-kit.php';
  require_once $aftmls_includes_dir . 'layouts/demo-data-lists.php';
  require_once $aftmls_includes_dir . 'layouts/theme-bundle-list.php';
  require $aftmls_includes_dir . 'init.php';
  require $aftmls_includes_dir . 'companion/class-aftc-main.php';

  // Instantiate the main plugin class *Singleton*.
  $AFMLS_Companion = AFTMLS_Companion::getInstance();

  /**
   * Layout Component Registry.
   */
  if (PHP_VERSION_ID >= 50600) {
    require_once AFTMLS_PLUGIN_DIR . 'includes/layouts/layout-endpoints.php';
  }

  /**
   * Freemius.
   */
  require_once(AFTMLS_PLUGIN_DIR . '/freemius.php');
}
add_action('init', 'templatespare_main_plugin_file');

function templatespare_activation_redirect($plugin)
{

  if (defined('DOING_AJAX') && DOING_AJAX) {
    // If activation is done via AJAX, don't perform redirection here
    return;
  }
}

add_action('activated_plugin', 'templatespare_activation_redirect');

// Place this inside templatespare.php (outside of any class)
function templatespare_flush_cache_on_theme_switch() {
    delete_transient('templatespare_smart_recommended_demos_v2');
    delete_transient('templatespare_site_intelligence_cache');
}
add_action('switch_theme', 'templatespare_flush_cache_on_theme_switch');
