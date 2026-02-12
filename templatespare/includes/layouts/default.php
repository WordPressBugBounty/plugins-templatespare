<?php
function templatespare_get_default_text($saved_category = '')
{

  $steps = array(
    array(
      'type' => 'intro',
      'title' => __('Welcome to Your WordPress Adventure 🎉', 'templatespare'),
      'button_next_text' => __('Start Now 🚀', 'templatespare'),
      'button_prev_text' => '',
      'subtitle' => __('You\'re just a few clicks away from building something awesome.
      Let’s launch your website and make magic happen!', 'templatespare'),
      'completed' => false,
      'items' => array(),
      'do_install' => false,
      'non_ai_flow_skip' => false,
      'skippable' => false, // <- important!
    ),
    array(
      'type' => 'category',
      'title' => __('What Kind of Website Are We Making Today?', 'templatespare'),
      'button_next_text' => __('Next', 'templatespare'),
      'button_prev_text' => __('Previous', 'templatespare'),
      'subtitle' => __('Pick your website type and we’ll hand-pick the perfect designs just for you.', 'templatespare'),
      'completed' => false,
      'items' => get_all_categories(),
      'do_install' => false,
      'non_ai_flow_skip' => false,
      'skippable' => true, // <- important!
    ),
    array(
      'type' => 'builder',
      'title' => __('Choose Your Building Superpower 🧰', 'templatespare'),
      'button_next_text' => __('Next', 'templatespare'),
      'button_prev_text' => __('Previous', 'templatespare'),
      'subtitle' => __("Select your favorite page builder and we’ll show you demos made to match.", 'templatespare'),
      'completed' => false,
      'items' => array(),
      'do_install' => false,
      'non_ai_flow_skip' => false,
      'skippable' => true, // <- important!
    ),
    array(
      'type' => 'website_intro',
      'title' => __('Choose Your Language 🌍', 'templatespare'),
      'button_next_text' => __('Next', 'templatespare'),
      'button_prev_text' => __('Previous', 'templatespare'),
      'subtitle' => __('Select your preferred language to continue. Let’s make your website feel like home!', 'templatespare'),
      'completed' => false,
      'items' => array(),
      'do_install' => false,
      'non_ai_flow_skip' => false,
      'skippable' => true, // <- important!
    ),

    // array(
    //   'type' => 'plugins',
    //   'title' => __('Recommended useful functionality for your site!', 'templatespare'),
    //   'button_next_text' => __('Next', 'templatespare'),
    //   'button_prev_text' => __('Previous', 'templatespare'),
    //   'completed' => false,
    //   'items' => get_require_plugins($saved_category),
    //   'do_install' => false,
    //   'non_ai_flow_skip' => false,
    // ),
    array(
      'type' => 'theme',
      'title' => '',
      'button_next_text' => __('Next', 'templatespare'),
      'button_prev_text' => __('Previous', 'templatespare'),
      'completed' => false,
      'items' => get_option('templatespare_wizard_category_value', false),
      'do_install' => false,
      'non_ai_flow_skip' => false,
      'skippable' => false, // <- important!
    ),

    // Add more steps as needed

  );

  return $steps;
}

function getall_tags()
{
  $all_demos = get_all_demo('all');

  $final_demodata = array();
  $empty_array = array();

  foreach ($all_demos as $keys => $demos) {
    if (isset($demos['demodata'])) {
      foreach ($demos['demodata'] as $demo) {
        $final_demodata[] = $demo;
      }
    }

    $empty_array['demos'][] = $keys;
  }

  $final_demotags = array();
  $demodata = array();
  foreach ($final_demodata as $demos) {
    if (isset($demos['tags'])) {
      foreach ($demos['tags'] as $demo_tags) {
        //$final_demotags[] = $demo_tags;
        $final_demotags[] = array(
          'value' => $demo_tags,
          'label' => ucfirst($demo_tags),
        );
      }
    }
  }
  $final_demotags = array_map("unserialize", array_unique(array_map("serialize", $final_demotags)));

  // Sort the array by 'label' in alphabetical order
  usort($final_demotags, function ($a, $b) {
    return strcmp($a['label'], $b['label']);
  });

  // Re-index the array
  $final_demotags = array_values($final_demotags);

  // Return the array as JSON
  return $final_demotags;
}

function get_all_categories()
{
  $all_demos = get_all_demo('all');

  $final_demodata = array();
  $empty_array = array();

  foreach ($all_demos as $keys => $demos) {
    if (isset($demos['demodata'])) {
      foreach ($demos['demodata'] as $demo) {
        $final_demodata[] = $demo;
      }
    }

    $empty_array['demos'][] = $keys;
  }

  $final_demotags = array();
  $demodata = array();
  foreach ($final_demodata as $demos) {
    if (isset($demos['main_category'])) {
      //foreach ($demos['tags'] as $demo_tags) {
      //$final_demotags[] = $demo_tags;
      $final_demotags[] = array(
        'value' => $demos['main_category'],
        'label' => ucfirst($demos['main_category']),
      );
      //}
    }
  }
  $final_demotags = array_map("unserialize", array_unique(array_map("serialize", $final_demotags)));

  // Sort the array by 'label' in alphabetical order
  usort($final_demotags, function ($a, $b) {
    return strcmp($a['label'], $b['label']);
  });

  // Re-index the array
  $final_demotags = array_values($final_demotags);

  // Return the array as JSON
  return $final_demotags;
}


function get_all_demo()
{
  $all_demos = array();
  ob_start();
  $remote_json_url = "https://raw.githubusercontent.com/afthemes/templatespare-demo-data/master/demo-list.json";
  $response = wp_remote_get($remote_json_url);
  if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
    // Get the body of the response
    $remote_json_content = wp_remote_retrieve_body($response);

    // Decode the JSON content
    $all_demos = json_decode($remote_json_content, true);
  } else {
    // Handle error, if any
    $error_message = is_wp_error($response) ? $response->get_error_message() : 'HTTP request failed';
    error_log("Error: $error_message");
  }
  ob_get_clean();
  $matchedData = [];

  foreach ($all_demos['democontent'] as $key => $res) {

    $matchedData[$key]['free'] = $res['free'];
    $matchedData[$key]['data'] = $res['data'];
    $matchedData[$key]['premium'] = $res['premium'];
    $matchedData[$key]['demodata'] = $res['demodata'];
  }

  return $matchedData;
}

function get_require_plugins($cat)
{
  $plugins_list = [];
  if (!empty($cat)) {
    if ($cat == 'gutenberg') {
      $plugins_list[] = [
        'value' => 'blockspare',

      ];
    } else if ($cat == 'elementor') {
      $plugins_list[] = [
        'value' => 'elementor',
      ];
      $plugins_list[] = [
        'value' => 'elspare',
      ];
    }
  }

  return $plugins_list;
}

function templatespare_get_all_lang_list()
{


  $remote_json_url = "https://raw.githubusercontent.com/afthemes/templatespare-demo-data/master/demo-list.json";
  // Get remote JSON
  $response = wp_remote_get($remote_json_url);

  // Check for WP errors
  if (is_wp_error($response)) {
    return; // or handle error
  }

  // Extract body
  $body = wp_remote_retrieve_body($response);

  // Decode JSON
  $data = json_decode($body, true);

  // Now safely access keys
  $demodata = templatespare_templates_demo_list('all');
  //var_dump($demodata);

  // Map of language slugs to flag URLs
  $flag_map = [
    'english' => ['label' => 'English', 'flag' => 'us.svg'],
    'french'  => ['label' => 'Français', 'flag' => 'fr.svg'],
    'german'  => ['label' => 'Deutsch', 'flag' => 'de.svg'],
    'nepali'  => ['label' => 'नेपाली', 'flag' => 'np.svg'],
    'arabic'  => ['label' => 'العربية', 'flag' => 'sa.svg'],
    'indian'  => ['label' => 'हिन्दी', 'flag' => 'in.svg'],
    'spanish' => ['label' => 'Español', 'flag' => 'es.svg'],
    'russian' => ['label' => 'Русский', 'flag' => 'ru.svg'],
    'japanese' => ['label' => '日本語', 'flag' => 'jp.svg'],
    'chinese'   => ['label' => '简体中文', 'flag' => 'cn.svg'],
    'turkish' => ['label' => 'Türkçe', 'flag' => 'tr.svg'],
  ];
  $result = [];
  $result[] = [
    'value' => 'english',
    'label' => $flag_map['english']['label'],
    'flag'  => $flag_map['english']['flag'],
  ];

  foreach ($demodata as $items) {
    foreach ($items['demodata'] as $item) {
      if (isset($item['tags']) && is_array($item['tags'])) {
        foreach ($item['tags'] as $tag) {
          $tag_lower = strtolower($tag);

          // Only add tag if it exists in the flag map
          if (isset($flag_map[$tag_lower])) {
            $result[] = [
              'value' => $tag_lower,
              'label' => $flag_map[$tag_lower]['label'],
              'flag'  => $flag_map[$tag_lower]['flag'],

            ];
          }
        }
      }
    }
  }

  // Remove duplicates
  $result = array_values(array_unique($result, SORT_REGULAR));


  return $result;
}
