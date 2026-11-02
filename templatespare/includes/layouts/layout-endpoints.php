<?php

if (!class_exists('AFTMLS_RestApi_Request')) {

  class AFTMLS_RestApi_Reques_Controller
  {
    private $namespace;
    private $query_base;

    public function __construct()
    {
      $this->namespace = 'templatespare/v1';
      $this->query_base = 'demo-lists';
      
    }

    public function templatespare_register_routes()
    {

      register_rest_route(
        'templatespare/v1',
        'single-demo-content',
        array(
          array(
            'methods' => \WP_REST_Server::READABLE,
            'callback' => array($this, 'templatespare_get_single_demo_list_items'),
            'permission_callback' => function () {
              return true;
            },
          ),
        )
      );

      //wizard
      register_rest_route('templatespare/v1', '/steps', array(
        'methods' => 'GET',
        'callback' => array($this, 'get_wizard_steps'),
        'permission_callback' => array($this, 'check_permissions'),

      ));
      register_rest_route('templatespare/v1', '/steps', array(
        'methods' => 'POST',
        'callback' => array($this, 'save_wizard_step'),
        'permission_callback' => array($this, 'check_permissions'),
      ));
      register_rest_route('templatespare/v1', '/steps', array(
        'methods' => 'POST',
        'callback' => array($this, 'save_jump_wizard_step'),
        'permission_callback' => array($this, 'check_permissions'),
      ));


      register_rest_route('templatespare/v1', '/temp-upload', array(
        'methods' => 'POST',
        'callback' => array($this, 'upload_images_in'),
        'permission_callback' => array($this, 'check_permissions'),
      ));
      register_rest_route('templatespare/v1', '/temp-delete', array(
        'methods' => 'POST',
        'callback' => array($this, 'delete_images_in'),
        'permission_callback' => array($this, 'check_permissions'),
      ));

      register_rest_route('templatespare/v1', '/get-recommended-demo', array(
        'methods' => 'GET',
        'callback' => array($this, 'get_recommended_demo'),
        'permission_callback' => '__return_true',
      ));

      
    }

    public function check_permissions($request)
    {
      // 1. Check if user has capability
      if (! current_user_can('import')) {
        return false;
      }

      // 2. Check nonce
      $nonce = $request->get_header('X-WP-Nonce');
      if (! wp_verify_nonce($nonce, 'wp_rest')) {
        return false;
      }
      return true;
    }

    // Get wizard steps
    public function get_wizard_steps(WP_REST_Request $request)
    {
      $step = (int) get_option('templatespare_wizard_next_step');

      $id = ($step) ? $step : 0;
      $category = get_option('templatespare_wizard_category_value', true);


      $saved_category = '';
      if (is_array($category) && isset($category)) {
        $saved_category = $category;
      }
      $plugins = '';
      if (!empty($saved_category)) {
        $plugins = get_require_plugins($saved_category);
      }

      $steps = templatespare_get_default_text($saved_category);

      return new WP_REST_Response(array('step' => $id, 'category' => $saved_category, 'steps' => $steps), 200);
    }

    // Save wizard step
    public function save_wizard_step(WP_REST_Request $request)
    {
      $step = $request->get_param('step');
      $category = $request->get_param('category');

      // Debugging
      // error_log('Step (before casting): ' . $step);
      $step = (int) $step;
      // error_log('Step (after casting): ' . $step);
      update_option('templatespare_wizard_next_step', $step);
      update_option('templatespare_wizard_category_value', $category);

      // 🧹 FLUSH RECOMMENDATION CACHE HERE INSTANTLY
      delete_transient('templatespare_smart_recommended_demos_v2');
      delete_transient('templatespare_site_intelligence_cache');

      return new WP_REST_Response(array('step' => $step, 'cat' => $category), 200);
    }

    public function save_jump_wizard_step(WP_REST_Request $request)
    {
      $step = $request->get_param('step');
      $category = get_option('templatespare_wizard_category_value', true);


      $saved_category = '';
      if (is_array($category) && isset($category)) {
        $saved_category = $category;
      }

      // Debugging
      // error_log('Step (before casting): ' . $step);
      $step = (int) $step;
      // error_log('Step (after casting): ' . $step);
      update_option('templatespare_wizard_next_step', $step);
      update_option('templatespare_wizard_category_value', $saved_category);
      return new WP_REST_Response(array('step' => $step, 'cat' => $saved_category), 200);
    }

    public function templatespare_get_single_demo_list_items(\WP_REST_Request $request)
    {
      $params = $request->get_params();
      $data['singleDemo'] = $this->templatespare_ajax_render_demo_lists($params['cat'], $params['selectedtheme']);
      $data['tags'] = $this->templatespare_ajax_render_demo_tags_lists($params['selectedtheme']);
      $data['mainCategory'] = $this->templatespare_ajax_render_demo_mainCatgory_lists($params['selectedtheme']);

      return $data;
    }

    public function templatespare_ajax_render_demo_lists($slug, $theme)
    {
      $all_demos = templatespare_templates_demo_list($theme);

      $parentNode = array();

      if (!empty($all_demos) && is_array($all_demos)) {
        foreach ($all_demos as $value) {
          if (empty($value['demodata']) || !is_array($value['demodata'])) {
            continue;
          }
          foreach ($value['demodata'] as $filtered_data) {

            $empty_array = array(
              'data' => $value['data'],
              'free' => $value['free'],
              'premium' => $value['premium'],
              'slug' => $filtered_data['slug'],
              'theme' => $filtered_data['theme'],
              'name' => $filtered_data['name'],
              'preview' => $filtered_data['preview'],
              'tags' => $filtered_data['tags'],
              'mainCategory' => $filtered_data['main_category'],
              'mainCategories' => $filtered_data['main_categories'],
              'imageKeywords' => isset($filtered_data['image_keywords']) ? $filtered_data['image_keywords'] : '',
              'homepage_type' => isset($filtered_data['homepage_type']) ? $filtered_data['homepage_type'] : 'static',
              'parent' => '',
              'plugins' => isset($filtered_data['plugins']) ? $filtered_data['plugins'] : "",
              "theme_type" => ($theme == $filtered_data['slug'] && in_array('child', $filtered_data['tags'])) ? 'true' : $value['free'],
              'installed_themes' => $this->templatespare_installed_themes(),
              'is_builder_pro' => isset($filtered_data['is_builder_pro']) ? $filtered_data['is_builder_pro'] : '',
              'is_fse' => isset($value['themeType']) ? $value['themeType'] : ''
            );

            array_push($parentNode, $empty_array);
          }
        }
      }

      return $parentNode;
    }

    public function templatespare_installed_themes()
    {
      $installed_themes = [];
      foreach ((array) wp_get_themes() as $theme_dir => $themes) {
        $installed_themes[] = $themes->name;
      }

      return $installed_themes;
    }

    public function templatespare_ajax_render_demo_tags_lists($theme)
    {
      $tagsdata = templatespare_get_tags_filtered_data($theme);
      return json_encode($tagsdata);
    }

    public function templatespare_ajax_render_demo_mainCatgory_lists($theme)
    {
      $tagsdata = templatespare_get_main_category_filtered_data($theme);
      return json_encode($tagsdata);
    }

    public function templatespare_get_theme_count($parent)
    {
      $all_demos = templatespare_templates_demo_list();
      $numberoftheme = count(array_values($all_demos[$parent]['demodata']));

      return $numberoftheme;
    }

    // Safe Custom Upload Directory Filter Callback
    public function templatespare_custom_upload_dir($uploads)
    {
      $uploads['path']   = $uploads['basedir'] . '/tmplsp-img';
      $uploads['url']    = $uploads['baseurl'] . '/tmplsp-img';
      $uploads['subdir'] = '/tmplsp-img';
      return $uploads;
    }

    // Secure upload image implementation
    public function upload_images_in()
    {
      if (empty($_FILES['file'])) {
        return new WP_Error('no_file', 'No file uploaded', array('status' => 400));
      }

      if (!function_exists('wp_handle_upload')) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
      }

      $uploaded_urls = array();

      $files = [];
      if (is_array($_FILES['file']['name'])) {
        foreach ($_FILES['file']['name'] as $key => $name) {
          $files[] = [
            'name'     => $name,
            'type'     => $_FILES['file']['type'][$key],
            'tmp_name' => $_FILES['file']['tmp_name'][$key],
            'error'    => $_FILES['file']['error'][$key],
            'size'     => $_FILES['file']['size'][$key],
          ];
        }
      } else {
        $files[] = $_FILES['file'];
      }

      $upload_overrides = array(
        'test_form' => false,
        'mimes'     => array(
          'jpg|jpeg|jpe' => 'image/jpeg',
          'png'          => 'image/png',
          'gif'          => 'image/gif',
          'webp'         => 'image/webp',
        ),
      );

      add_filter('upload_dir', array($this, 'templatespare_custom_upload_dir'));

      foreach ($files as $file) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
          continue;
        }

        $movefile = wp_handle_upload($file, $upload_overrides);

        if ($movefile && !isset($movefile['error'])) {
          $uploaded_urls[] = $movefile['url'];
        }
      }

      remove_filter('upload_dir', array($this, 'templatespare_custom_upload_dir'));

      return array(
        'url' => (count($uploaded_urls) === 1) ? $uploaded_urls : $uploaded_urls
      );
    }

    public function delete_images_in($request)
    {
      $params = $request->get_json_params();
      $url = $params['url'] ?? '';

      if (!$url) return array('deleted' => false);

      $upload_dir = wp_upload_dir();
      $filepath = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $url);

      if (file_exists($filepath)) {
        unlink($filepath);
      }

      return array('deleted' => true);
    }

    /**
     * ADVANCED ENVIRONMENT INTELLIGENCE SCANNER (THEMES, PLUGINS & FILE DIRECTORIES)
     */
    private function templatespare_get_site_intelligence()
    {
      $active_theme = wp_get_theme();
      $theme_slug   = strtolower($active_theme->get_stylesheet());
      $theme_name   = strtolower($active_theme->get('Name'));
      
      // 1. Scan installed themes (active + inactive directories)
      $installed_themes_slugs = array();
      $theme_roots = function_exists('wp_get_themes') ? wp_get_themes() : array();
      foreach ($theme_roots as $dir => $theme_obj) {
        $installed_themes_slugs[] = strtolower($theme_obj->get('Name'));
        $installed_themes_slugs[] = strtolower($dir);
      }

      // 2. Scan active plugins and check physical plugins folder for BlockSpare, EleSpare, WooCommerce, etc.
      $active_plugins = get_option('active_plugins', array());
      $plugin_slugs   = array();
      foreach ($active_plugins as $plugin_path) {
        $parts = explode('/', $plugin_path);
        if (!empty($parts[0])) {
          $plugin_slugs[] = strtolower($parts[0]);
        }
      }

      // Also scan WP_PLUGIN_DIR safely to detect installed-but-inactive ecosystem plugins
      if (defined('WP_PLUGIN_DIR') && is_dir(WP_PLUGIN_DIR)) {
        $plugin_dir = @opendir(WP_PLUGIN_DIR);
        if ($plugin_dir) {
          while (($file = readdir($plugin_dir)) !== false) {
            if ($file != '.' && $file != '..' && is_dir(WP_PLUGIN_DIR . '/' . $file)) {
              $plugin_slugs[] = strtolower($file);
            }
          }
          closedir($plugin_dir);
        }
      }
      $plugin_slugs = array_unique($plugin_slugs);

      // Dynamic cache key tied to the exact environment state
      $cache_key    = 'templatespare_site_intel_v6_' . md5($theme_slug . implode('', $plugin_slugs) . get_locale());
      $intelligence = get_transient($cache_key);

      if (false !== $intelligence) {
        return $intelligence;
      }

      $site_locale = get_locale();

      global $wpdb;
      $recent_posts = $wpdb->get_col(
        "SELECT post_title FROM {$wpdb->posts} WHERE post_type = 'post' AND post_status = 'publish' ORDER BY post_date DESC LIMIT 15"
      );

      $post_keywords = array();
      if (!empty($recent_posts)) {
        foreach ($recent_posts as $title) {
          $words = explode(' ', strtolower(preg_replace('/[^\p{L}\p{N} ]/u', '', $title)));
          foreach ($words as $w) {
            if (mb_strlen($w) > 3) {
              $post_keywords[] = $w;
            }
          }
        }
      }

      $intelligence = array(
        'theme_name'       => $theme_name,
        'theme_slug'       => $theme_slug,
        'installed_themes' => $installed_themes_slugs,
        'active_plugins'   => $plugin_slugs,
        'locale'           => $site_locale,
        'keywords'         => array_count_values($post_keywords),
      );

      set_transient($cache_key, $intelligence, 12 * HOUR_IN_SECONDS);

      return $intelligence;
    }

    /**
     * RETRIEVE USER IMPORT HISTORY FROM THE IMPORTER CLASS
     */
    private function templatespare_get_user_import_history()
    {
      $history = array();

      if (class_exists('AFTMLS_Templates_Importer') && method_exists('AFTMLS_Templates_Importer', 'get_instance')) {
        $importer_instance = AFTMLS_Templates_Importer::get_instance();
        
        if ($importer_instance && method_exists($importer_instance, 'templatespareget_user_meta_demo_import')) {
          $history = $importer_instance->templatespareget_user_meta_demo_import();
        }
      }

      if (empty($history) || !is_array($history)) {
        $history = get_option('templatespare_user_demo_import_history', array());
      }

      return is_array($history) ? $history : array();
    }

    /**
     * Clear smart recommendation caches
     */
    public function templatespare_clear_recommendation_cache()
    {
      delete_transient('templatespare_site_intel_v6_' . md5(wp_get_theme()->get_stylesheet() . get_locale()));
      delete_transient('templatespare_smart_demos_v6_' . md5(wp_get_theme()->get_stylesheet() . get_locale()));
    }

    /**
     * UNIFORM 15-ITEM CURATED DEMO ENDPOINT (LIVE SESSION SHUFFLE & DYNAMIC ECOSYSTEM CHECK)
     */
    public function get_recommended_demo(WP_REST_Request $request)
    {
      $site_intel = $this->templatespare_get_site_intelligence();
      
      // Dynamic key for the scored pool transient
      $pool_cache_key = 'templatespare_scored_pool_' . md5($site_intel['theme_slug'] . implode('', $site_intel['active_plugins']) . '_' . $site_intel['locale']);
      $candidate_pool = get_transient($pool_cache_key);

      // 1. BUILD THE POOL ONLY IF NOT CACHED
      if (false === $candidate_pool) {
        $import_history = $this->templatespare_get_user_import_history();
        $demo_lists     = templatespare_templates_demo_list('all');
        $scored_demos   = array();
        $fallback_pool  = array();

        if (empty($demo_lists) || !is_array($demo_lists)) {
          return array();
        }

        $historic_themes = array();
        $historic_cats   = array();
        $historic_tags   = array();

        if (!empty($import_history) && is_array($import_history)) {
          foreach ($import_history as $import) {
            if (!empty($import['selectedTheme'])) {
              $historic_themes[strtolower($import['selectedTheme'])] = true;
            }
            if (!empty($import['mainCategories']) && is_array($import['mainCategories'])) {
              foreach ($import['mainCategories'] as $cat) {
                $historic_cats[strtolower($cat)] = true;
              }
            }
            if (!empty($import['tags']) && is_array($import['tags'])) {
              foreach ($import['tags'] as $tag) {
                $historic_tags[strtolower($tag)] = true;
              }
            }
          }
        }

        $foreign_keywords = array(
          'arabic', 'russian', 'hindi', 'japanese', 'german', 
          'bangladesh', 'korean', 'french', 'romanian', 'brazil', 
          'myanmar', 'chinese', 'spanish', 'italian', 'rtl'
        );
        
        $is_english_site = (false !== strpos(strtolower($site_intel['locale']), 'en'));
        $active_slug     = strtolower($site_intel['theme_slug']);
        $active_name     = strtolower($site_intel['theme_name']);

        // Check dynamically if the active theme matches any theme in our ecosystem catalog
        $is_ecosystem_theme = false;
        foreach ($demo_lists as $cat_key => $cat_val) {
          if (!empty($cat_val['data']) && (false !== strpos(strtolower($cat_val['data']), $active_slug) || false !== strpos($active_slug, strtolower($cat_val['data'])))) {
            $is_ecosystem_theme = true;
            break;
          }
        }

        foreach ($demo_lists as $category_key => $value) {
          if (empty($value['demodata']) || !is_array($value['demodata'])) {
            continue;
          }

          $is_category_free = isset($value['free']) && (strtolower($value['free']) === 'free' || $value['free'] === true);

          foreach ($value['demodata'] as $slug => $filtered_data) {
            $demo_tags = isset($filtered_data['tags']) ? array_map('strtolower', (array) $filtered_data['tags']) : array();
            $demo_name = strtolower(isset($filtered_data['name']) ? $filtered_data['name'] : '');

            $unique_slug_key = $category_key . '_' . $slug;

            $empty_array = array(
              'data'             => $value['data'],
              'free'             => $value['free'],
              'premium'          => $value['premium'],
              'slug'             => $filtered_data['slug'],
              'theme'            => $filtered_data['theme'],
              'name'             => $filtered_data['name'],
              'preview'          => $filtered_data['preview'],
              'tags'             => $filtered_data['tags'],
              'mainCategory'     => isset($filtered_data['main_category']) ? $filtered_data['main_category'] : '',
              'mainCategories'   => isset($filtered_data['main_categories']) ? $filtered_data['main_categories'] : array(),
              'homepage_type'    => isset($filtered_data['homepage_type']) ? $filtered_data['homepage_type'] : 'static',
              'parent'           => '',
              'plugins'          => isset($filtered_data['plugins']) ? $filtered_data['plugins'] : "",
              'installed_themes' => $this->templatespare_installed_themes(),
              'relevance_score'  => 10
            );

            $fallback_pool[$unique_slug_key] = $empty_array;

            // Strict language & RTL filtering
            if ($is_english_site) {
              $is_foreign_or_rtl = false;
              foreach ($foreign_keywords as $keyword) {
                if (in_array($keyword, $demo_tags) || false !== strpos($demo_name, $keyword)) {
                  $is_foreign_or_rtl = true;
                  break;
                }
              }
              if ($is_foreign_or_rtl) {
                continue;
              }
            }

            $score = 10;

            $demo_theme     = strtolower(isset($filtered_data['theme']) ? $filtered_data['theme'] : '');
            $demo_main_cat  = strtolower(isset($filtered_data['main_category']) ? $filtered_data['main_category'] : '');
            $demo_plugins   = isset($filtered_data['plugins']) ? strtolower($filtered_data['plugins']) : '';
            $is_demo_free   = in_array('free', $demo_tags) || $is_category_free;

            if ($is_demo_free) {
              $score += 25;
            }

            if (!empty($demo_theme) && (false !== strpos($demo_theme, $active_slug) || false !== strpos($active_slug, $demo_theme) || false !== strpos($demo_theme, $active_name))) {
              $score += 100;
            }

            foreach ($site_intel['installed_themes'] as $inst_theme) {
              if (!empty($demo_theme) && (false !== strpos($demo_theme, $inst_theme) || false !== strpos($inst_theme, $demo_theme))) {
                $score += 50;
                break;
              }
            }

            // DYNAMIC ECOSYSTEM FALLBACK: If running a third-party/external theme, reward versatile starter sites
            if (!$is_ecosystem_theme) {
              if ($is_demo_free && (in_array('blog', $demo_tags) || in_array('general', $demo_tags) || in_array('magazine', $demo_tags))) {
                $score += 40;
              }
            }

            foreach ($historic_themes as $h_theme => $true) {
              if (false !== strpos($demo_theme, $h_theme) || false !== strpos($h_theme, $demo_theme)) {
                $score += 30;
              }
            }

            $empty_array['relevance_score'] = $score;
            $scored_demos[$unique_slug_key] = $empty_array;
          }
        }

        uasort($scored_demos, function ($a, $b) {
          if ($b['relevance_score'] == $a['relevance_score']) {
            return 0;
          }
          return ($b['relevance_score'] < $a['relevance_score']) ? -1 : 1;
        });

        // Store top 40 candidates in pool
        $candidate_pool = array_slice($scored_demos, 0, 40, true);

        // Fill pool to 40 if needed using fallbacks
        if (count($candidate_pool) < 40) {
          foreach ($fallback_pool as $key => $fallback_item) {
            if (count($candidate_pool) >= 40) {
              break;
            }
            if (!isset($candidate_pool[$key])) {
              $candidate_pool[$key] = $fallback_item;
            }
          }
        }

        // Cache candidate pool safely for 12 hours
        set_transient($pool_cache_key, $candidate_pool, 12 * HOUR_IN_SECONDS);
      }

      // 2. LIVE ROTATION: SHUFFLE & SLICE OUTSIDE THE TRANSIENT CHECK
      $pool_values = array_values($candidate_pool);
      shuffle($pool_values);

      return array_slice($pool_values, 0, 15);
    }

  }
}
