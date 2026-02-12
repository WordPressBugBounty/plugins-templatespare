<?php

/**
 * ------------------------------------------------------------------------
 * GLOBAL STATE
 * ------------------------------------------------------------------------
 * - In-memory cache prevents duplicate uploads in the same request
 * - Shared index rotates attachment IDs across featured + content images
 */
$GLOBALS['templatespare_custom_image_cache'] = [];
$GLOBALS['templatespare_custom_image_index'] = 0;


/**
 * ------------------------------------------------------------------------
 * PRE-PROCESS IMPORT
 * ------------------------------------------------------------------------
 * Upload all configured custom images ONCE before any post is imported.
 * Attachment IDs are cached and stored for later reuse.
 *
 * Hook: wxr_importer.pre_process.post
 */
add_filter(
  'wxr_importer.pre_process.post',
  'templatespare_upload_images_before_import',
  10,
  4
);

function templatespare_upload_images_before_import($data, $meta, $comments, $terms)
{
  // List of image URLs defined by the theme/plugin
  $images = get_option('templatespare_custom_post_images', []);
  if (empty($images)) {
    return $data;
  }

  // Upload each image only once (cached internally)
  foreach ($images as $image_url) {
    templatespare_get_or_upload_custom_image($image_url);
  }

  return $data;
}


/**
 * ------------------------------------------------------------------------
 * FEATURED IMAGE ASSIGNMENT
 * ------------------------------------------------------------------------
 * Assign a featured image to the post after it is created,
 * using the previously uploaded attachment IDs.
 *
 * Hook: wxr_importer.processed.post
 */
add_action(
  'wxr_importer.processed.post',
  'templatespare_force_featured_image_after_import',
  20,
  5
);

function templatespare_force_featured_image_after_import($post_id, $data, $meta, $comments, $terms)
{
  // Only apply to posts and pages
  if (!in_array(get_post_type($post_id), ['post', 'page'], true)) {
    return;
  }

  // Allow disabling via option
  if (get_option('templatespare_use_images_in_featued', true) === 'no') {
    return;
  }

  // Rotate through saved attachment IDs
  $attachment_id = templatespare_get_next_uploaded_image_id();
  if (!$attachment_id) {
    return;
  }
  // Remove previous featured image if exists
  $current_thumbnail_id = get_post_thumbnail_id($post_id);
  if ($current_thumbnail_id) {
    delete_post_thumbnail($post_id); // unsets the featured image
    // Optional: delete attachment completely
    wp_delete_attachment($current_thumbnail_id, true);
  }

  // Replace existing featured image safely
  // delete_post_thumbnail($post_id);
  set_post_thumbnail($post_id, $attachment_id);
}


/**
 * ------------------------------------------------------------------------
 * CONTENT IMAGE REPLACEMENT
 * ------------------------------------------------------------------------
 * Replace all image URLs in post content (Classic + Gutenberg)
 * with uploaded attachment URLs.
 *
 * Hook: wp_import_post_data_processed
 */
add_filter(
  'wp_import_post_data_processed',
  'templatespare_replace_content_images_with_custom',
  10,
  2
);

function templatespare_replace_content_images_with_custom($postdata, $raw_post)
{
  // Allow disabling via option
  if (get_option('templatespare_use_images_in_content', true) === 'no') {
    return $postdata;
  }

  // Skip empty content and Elementor templates
  if (
    empty($postdata['post_content']) ||
    (isset($raw_post['post_type']) && $raw_post['post_type'] === 'elementor_library')
  ) {
    return $postdata;
  }

  /**
   * -----------------------------
   * CLASSIC EDITOR IMAGE HANDLING
   * -----------------------------
   */
  preg_match_all(
    '/https?:\/\/[^\s"\']+\.(jpg|jpeg)/i',
    $postdata['post_content'],
    $matches
  );

  foreach ($matches[0] as $old_url) {
    // Skip inline base64 images
    if (strpos($old_url, 'data:image') === 0) {
      continue;
    }

    $attachment_id = templatespare_get_next_uploaded_image_id();
    if (!$attachment_id) {
      continue;
    }

    $new_url = wp_get_attachment_url($attachment_id);
    $postdata['post_content'] = str_replace($old_url, $new_url, $postdata['post_content']);
  }

  /**
   * -----------------------------
   * GUTENBERG BLOCK HANDLING
   * -----------------------------
   */
  $blocks = parse_blocks($postdata['post_content']);
  if (!empty($blocks)) {
    $blocks = templatespare_replace_gutenberg_images($blocks);
    $postdata['post_content'] = serialize_blocks($blocks);
  }

  return $postdata;
}


/**
 * ------------------------------------------------------------------------
 * RECURSIVE GUTENBERG IMAGE REPLACER
 * ------------------------------------------------------------------------
 * Replaces images in core/image and core/gallery blocks,
 * including nested inner blocks.
 */
function templatespare_replace_gutenberg_images($blocks)
{
  foreach ($blocks as &$block) {

    // Single image block
    if ($block['blockName'] === 'core/image' && isset($block['attrs']['url'])) {
      $ext = strtolower(pathinfo($block['attrs']['url'], PATHINFO_EXTENSION));
      if (!in_array($ext, ['png', 'webp'])) { // skip PNG and WebP (any case)
        $id = templatespare_get_next_uploaded_image_id();
        if ($id) {
          $block['attrs']['url'] = wp_get_attachment_url($id);
          $block['attrs']['id']  = $id;
        }
      }
    }

    // Gallery block
    if ($block['blockName'] === 'core/gallery' && !empty($block['attrs']['images'])) {
      foreach ($block['attrs']['images'] as &$img) {
        $ext = strtolower(pathinfo($img['url'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['png', 'webp'])) { // skip PNG and WebP (any case)
          $id = templatespare_get_next_uploaded_image_id();
          if ($id) {
            $img['url'] = wp_get_attachment_url($id);
            $img['id']  = $id;
          }
        }
      }
    }

    // Recursively process inner blocks
    if (!empty($block['innerBlocks'])) {
      $block['innerBlocks'] = templatespare_replace_gutenberg_images($block['innerBlocks']);
    }
  }

  return $blocks;
}



/**
 * ------------------------------------------------------------------------
 * ATTACHMENT ID ROTATOR
 * ------------------------------------------------------------------------
 * Returns the next uploaded attachment ID in a round-robin manner.
 */
function templatespare_get_next_uploaded_image_id()
{
  $ids = get_option('templatespare_uploaded_image_ids', []);
  if (empty($ids)) {
    return 0;
  }

  $index = &$GLOBALS['templatespare_custom_image_index'];
  $id = $ids[$index % count($ids)];
  $index++;

  return (int) $id;
}


/**
 * ------------------------------------------------------------------------
 * IMAGE UPLOAD & CACHE HANDLER
 * ------------------------------------------------------------------------
 * Downloads and uploads an image only once.
 * Uses both in-memory and persistent cache to prevent duplicates.
 */
function templatespare_get_or_upload_custom_image($image_url)
{
  // Fast in-memory cache
  if (isset($GLOBALS['templatespare_custom_image_cache'][$image_url])) {
    return $GLOBALS['templatespare_custom_image_cache'][$image_url];
  }

  // Persistent cache across requests
  $cache = get_option('templatespare_image_upload_cache', []);
  if (isset($cache[$image_url])) {
    $id = (int) $cache[$image_url];
    $GLOBALS['templatespare_custom_image_cache'][$image_url] = $id;
    return $id;
  }

  require_once ABSPATH . 'wp-admin/includes/file.php';
  require_once ABSPATH . 'wp-admin/includes/media.php';
  require_once ABSPATH . 'wp-admin/includes/image.php';

  // Download image to temp file
  $tmp = download_url($image_url);
  if (is_wp_error($tmp)) {
    return false;
  }

  $file_array = [
    'name'     => basename(parse_url($image_url, PHP_URL_PATH)),
    'tmp_name' => $tmp,
  ];

  // Upload into Media Library
  $attachment_id = media_handle_sideload($file_array, 0);
  if (is_wp_error($attachment_id)) {
    @unlink($tmp);
    return false;
  }

  // Cache and store attachment ID
  $GLOBALS['templatespare_custom_image_cache'][$image_url] = $attachment_id;
  $cache[$image_url] = $attachment_id;
  update_option('templatespare_image_upload_cache', $cache);

  templatespare_store_uploaded_image_id($attachment_id);

  return $attachment_id;
}


/**
 * ------------------------------------------------------------------------
 * STORE UPLOADED ATTACHMENT IDS
 * ------------------------------------------------------------------------
 * Saves unique attachment IDs for reuse during import.
 */
function templatespare_store_uploaded_image_id($attachment_id)
{
  $stored = get_option('templatespare_uploaded_image_ids', []);

  if (!in_array($attachment_id, $stored, true)) {
    $stored[] = (int) $attachment_id;
    update_option('templatespare_uploaded_image_ids', $stored, false);
  }
}
