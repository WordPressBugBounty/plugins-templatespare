<?php

trait Helper
{
  /**
   * A list of allowed mimes.
   *
   * @var array
   */
  protected $extensions = array(
    'jpg|jpeg|jpe' => 'image/jpeg',
    //'png'          => 'image/png',
    // 'webp'         => 'image/webp',
    'svg'          => 'image/svg+xml',
  );

  /**
   * Replace demo urls in meta with site urls.
   */
  public function replace_image_urls($markup)
  {
    $old_urls = $this->get_urls_to_replace($markup);
    if (! is_array($old_urls) || empty($old_urls)) {
      return $markup;
    }

    $urls = array_combine($old_urls, $old_urls);
    $urls = array_map('wp_unslash', $urls);
    $urls = array_map(array($this, 'remap_host'), $urls);
    $markup = str_replace(array_keys($urls), array_values($urls), $markup);

    // ✅ Replace with custom images if available
    $decoded = json_decode($markup, true);
    if (is_array($decoded)) {
      $decoded = $this->replace_images_in_json($decoded);
      $markup = wp_json_encode($decoded);
    }

    return $markup;
  }

  private function get_urls_to_replace($markup)
  {
    $regex = '/(?:http(?:s?):)(?:[\/\\\\\\\\|.|\w|\s|-])*\.(?:' . implode('|', array_keys($this->extensions)) . ')/m';
    if (! is_string($markup)) {
      return array();
    }

    preg_match_all($regex, $markup, $urls);

    $urls = array_map(
      function ($value) {
        return rtrim(html_entity_decode($value), '\\');
      },
      $urls[0]
    );

    $urls = array_unique($urls);

    return array_values($urls);
  }

  private function remap_host($url)
  {
    if (! strpos($url, '/uploads/')) {
      return $url;
    }
    $old_url   = $url;
    $url_parts = parse_url($url);

    if (! isset($url_parts['host'])) {
      return $url;
    }
    $url_parts['path'] = preg_split('/\//', $url_parts['path']);
    $url_parts['path'] = array_slice($url_parts['path'], -3);

    $uploads_dir = wp_get_upload_dir();
    $uploads_url = $uploads_dir['baseurl'];

    $new_url = esc_url($uploads_url . '/' . join('/', $url_parts['path']));

    return str_replace($old_url, $new_url, $url);
  }

  public function cleanup_page_slug($slug, $demo_slug)
  {
    $unhashed = array('shop', 'my-account', 'checkout', 'cart', 'blog', 'news');
    $slug     = str_replace($demo_slug, '', $slug);
    $slug     = str_replace('demo', '', $slug);
    $slug     = ltrim($slug, '-');

    if (in_array($slug, $unhashed, true)) {
      return $slug;
    }

    $hash = substr(md5($demo_slug), 0, 5);
    $slug = $hash . '-' . $slug;

    return $slug;
  }

  /**
   * 🔑 Replace images with custom uploaded images if available
   */
  private function replace_images_in_json($data)
  {
    $custom_images = get_option('templatespare_uploaded_image_ids', []);
    if (empty($custom_images)) {
      return $data;
    }

    static $image_counter = 0;
    $num_images = count($custom_images);

    return $this->replace_images_recursive($data, $custom_images, $image_counter, $num_images);
  }
  private function replace_images_recursive($data, $custom_images, &$image_counter, $num_images)
  {
    if (!is_array($data)) return $data;

    foreach ($data as &$value) {

      // Elementor image object
      if (is_array($value) && isset($value['url'])) {
        $old_url = $value['url'];

        if ($this->is_replaceable_image($old_url)) {
          // Only JPG/JPEG get replaced
          $attachment_id = $this->upload_custom_image(
            $custom_images[$image_counter % $num_images]
          );
          $image_counter++;

          if ($attachment_id) {
            $value['url'] = wp_get_attachment_url($attachment_id);
            $value['id']  = $attachment_id;

            if (isset($value['alt'])) {
              $value['alt'] = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
            }

            // error_log("[Elementor Import] Replaced JPG: $old_url => ID $attachment_id");
          }
        } else {
          // PNG/SVG only get remapped
          $value['url'] = $this->remap_host($old_url);
          //error_log("[Elementor Import] Skipped PNG/SVG: $old_url");
        }
      }

      // Direct URL string
      elseif (is_string($value)) {
        $old_url = $value;

        if ($this->is_replaceable_image($old_url)) {
          $attachment_id = $this->upload_custom_image(
            $custom_images[$image_counter % $num_images]
          );
          $image_counter++;

          if ($attachment_id) {
            $value = wp_get_attachment_url($attachment_id);
            //error_log("[Elementor Import] Replaced JPG URL: $old_url => ID $attachment_id");
          }
        } else {
          $value = $this->remap_host($old_url);
          //error_log("[Elementor Import] Skipped PNG/SVG URL: $old_url");
        }
      }

      // Recursively process nested arrays
      if (is_array($value)) {
        $value = $this->replace_images_recursive($value, $custom_images, $image_counter, $num_images);
      }
    }

    return $data;
  }

  /**
   * Check if the image is a JPG/JPEG
   */
  private function is_replaceable_image($url)
  {
    if (!is_string($url) || !filter_var($url, FILTER_VALIDATE_URL)) return false;

    $path = parse_url($url, PHP_URL_PATH);
    return preg_match('/\.(jpe?g)$/i', $path);
  }

  private function is_image_url($url)
  {
    if (!is_string($url) || empty($url)) return false;
    if (!filter_var($url, FILTER_VALIDATE_URL)) return false;

    $regex = '/\.(?:' . implode('|', array_keys($this->extensions)) . ')$/i';
    return preg_match($regex, parse_url($url, PHP_URL_PATH));
  }

  private function upload_custom_image($image)
  {
    if (is_numeric($image) && get_post($image)) {
      return (int) $image;
    }
    if (is_string($image)) {
      $attachment_id = attachment_url_to_postid($image);
      if ($attachment_id) return $attachment_id;
    }
    return false;
  }
}

/**
 * Class Elementor_Meta_Handler
 */
class AFTMLS_Elementor_Meta_Handler
{
  use Helper;

  private $meta_key = '_elementor_data';
  private $value = null;
  private $import_url = null;

  public function __construct($unfiltered_value, $site_url)
  {
    $this->value      = $unfiltered_value;
    $this->import_url = $site_url;
  }

  public function filter_meta()
  {
    add_filter('sanitize_post_meta_' . $this->meta_key, array($this, 'allow_escaped_json_meta'), 10, 3);
  }

  public function allow_escaped_json_meta($val, $key, $type)
  {
    if (empty($this->value)) {
      return $val;
    }

    // ✅ Replace demo images with custom images if available
    $this->value = $this->replace_image_urls($this->value);
    $this->replace_link_urls();

    return $this->value;
  }

  private function replace_link_urls()
  {
    $decoded_meta = json_decode($this->value, true);
    if (! is_array($decoded_meta)) return;

    $site_url  = get_site_url();
    $url_parts = parse_url($site_url);

    array_walk_recursive(
      $decoded_meta,
      function (&$value, $key) use ($site_url, $url_parts) {
        if (filter_var($value, FILTER_VALIDATE_URL) === false) return;

        $url = parse_url($value);

        if (!isset($url['host']) || !isset($url_parts['host'])) return;

        if ($url['host'] !== $url_parts['host']) {
          $value = str_replace($this->import_url, $site_url, $value);
        }
      }
    );

    $this->value = json_encode($decoded_meta);
  }
}
