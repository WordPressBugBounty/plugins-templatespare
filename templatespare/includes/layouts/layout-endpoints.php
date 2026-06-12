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
        'permission_callback' => array($this, 'check_permissions'),
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
      error_log('Step (before casting): ' . $step);
      $step = (int) $step;
      error_log('Step (after casting): ' . $step);
      update_option('templatespare_wizard_next_step', $step);
      update_option('templatespare_wizard_category_value', $category);
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
      error_log('Step (before casting): ' . $step);
      $step = (int) $step;
      error_log('Step (after casting): ' . $step);
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

      $themecheck = explode('-', $theme);
      $parentNode = array();
      $final_array = array();

      foreach ($all_demos as $value) {
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
            'is_fse' => $value['themeType']

          );

          array_push($parentNode, $empty_array);
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

      // Ensure WordPress file API functions are available
      if (!function_exists('wp_handle_upload')) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
      }

      $uploaded_urls = array();

      // Normalize $_FILES array for multiple or single uploads
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

      // Configuration overrides for wp_handle_upload
      $upload_overrides = array(
        'test_form' => false, 
        'mimes'     => array(
            'jpg|jpeg|jpe' => 'image/jpeg',
            'png'          => 'image/png',
            'gif'          => 'image/gif',
            'webp'         => 'image/webp',
        ),
      );

      // Add dynamic hook to drop files explicitly in the /tmplsp-img/ directory safely
      add_filter('upload_dir', array($this, 'templatespare_custom_upload_dir'));

      // Process each file securely
      foreach ($files as $file) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
          continue;
        }

        // Pass file array through native validation handling
        $movefile = wp_handle_upload($file, $upload_overrides);

        if ($movefile && !isset($movefile['error'])) {
            $uploaded_urls[] = $movefile['url'];
        }
      }

      // Clean up the upload path directory modifier right away
      remove_filter('upload_dir', array($this, 'templatespare_custom_upload_dir'));

      // Return string if single file handled, or full array to maintain compatibility
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

    public function get_recommended_demo(WP_REST_Request $request)
    {
      $demo_lists = templatespare_templates_demo_list('all');

      $parentNode = array();


      foreach ($demo_lists as $value) {
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
            'homepage_type' => isset($filtered_data['homepage_type']) ? $filtered_data['homepage_type'] : 'static',
            'parent' => '',
            'plugins' => isset($filtered_data['plugins']) ? $filtered_data['plugins'] : "",
            //"theme_type" => ($theme == $filtered_data['slug'] && in_array('child', $filtered_data['tags'])) ? 'true' : $value['free'],
            'installed_themes' => $this->templatespare_installed_themes(),

          );

          array_push($parentNode, $empty_array);
        }
      }

      return $parentNode;
    }
  }
}