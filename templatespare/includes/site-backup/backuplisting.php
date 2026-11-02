<?php
global $wp_filesystem;
if (empty($wp_filesystem)) {
  require_once ABSPATH . 'wp-admin/includes/file.php';
  WP_Filesystem();
}

$year = gmdate('Y');
$month = gmdate('m');
$uploadDir = wp_upload_dir();
$backupDir = $uploadDir['basedir'] . '/templatespare-backup/';

// Pagination setup
$per_page = 10;
$paged = isset($_GET['paged']) ? absint($_GET['paged']) : 1;

$folders = templatespare_get_folders($backupDir);

// Sort folders by parsed timestamp in descending order
usort($folders, function ($a, $b) {
  return strcmp(templatespare_parse_timestamp($b), templatespare_parse_timestamp($a));
});

// Paginate folders
$total_items = count($folders);
$folders = array_slice($folders, ($paged - 1) * $per_page, $per_page);

function templatespare_get_folders($dir)
{
  $folders = glob($dir . '*');
  if (!$folders) {
    return [];
  }
  return array_map('basename', $folders);
}

function templatespare_parse_timestamp($folder)
{
  $parts = explode('__', $folder);
  if (count($parts) === 2) {
    $date = str_replace('-', '', $parts[0]);
    $time = str_replace('-', '', $parts[1]);
    return $date . $time;
  }
  return '';
}

function convert_folder_name_to_datetime($folder_name)
{
  $parts = explode('__', $folder_name);
  if (count($parts) === 2) {
    $date = $parts[0];
    $time = $parts[1];
    $datetime = DateTime::createFromFormat('Y-m-d H-i-s', $date . ' ' . $time);
    if ($datetime) {
      return $datetime->format('F j, Y, g:i A');
    }
  }
  return $folder_name;
}

if (isset($_POST['templastespare_sitebackup'])) {
  // Logic for creating a backup
  $backup_name = gmdate('Y-m-d__H-i-s');
  $backup_path = trailingslashit($backupDir) . $backup_name;

  echo '<div class="updated notice is-dismissible"><p>' . esc_html__('Export package generated successfully!', 'templatespare') . '</p></div>';
}

if (!class_exists('ZipArchive')) {
  echo '<div class="notice notice-error">
      <p><strong>' . esc_html__('Notice:', 'templatespare') . '</strong> ' . esc_html__('The ZipArchive PHP extension is required to download and export site packages.', 'templatespare') . '</p>
      <p>' . sprintf(
    '<a href="https://www.php.net/manual/en/book.zip.php" target="_blank">%s</a>',
    esc_html__('Learn more about the Zip extension', 'templatespare')
  ) . '</p>
      <p>' . esc_html__('Please contact your hosting provider for assistance.', 'templatespare') . '</p>
  </div>';
}
?>

<div class="wrap">
  <h1 class="wp-heading-inline"><?php esc_html_e('Site Export Manager', 'templatespare'); ?></h1>
  <p><?php esc_html_e('Export your entire WordPress site, including themes, plugins, uploads, and database settings, for seamless migration or safe backups. View and manage your recent archives below, or head over to the ', 'templatespare'); ?>
  <a href="<?php echo esc_url(admin_url('admin.php?page=templatespare-site-import')); ?>"><?php esc_html_e('Import Dashboard', 'templatespare'); ?></a> 
  <?php esc_html_e(' to restore or migrate your site anytime.', 'templatespare'); ?></p>
  
  <h2><?php esc_html_e('Recent Site Exports', 'templatespare'); ?></h2>
  <hr class="wp-header-end">

  <!-- Table for listing backups -->
  <table class="wp-list-table widefat fixed striped table-view-list pages">
    <thead>
      <tr>
        <th scope="col" id="created" class="manage-column column-id"><?php esc_html_e('Created Date & Time', 'templatespare') ?></th>
        <th scope="col" id="size" class="manage-column column-size"><?php esc_html_e('Package Size', 'templatespare') ?></th>
        <th scope="col" id="backup-name" class="manage-column column-backup-name"><?php esc_html_e('Export Archive Name', 'templatespare') ?></th>
        <th scope="col" id="actions" class="manage-column column-actions"><?php esc_html_e('Actions', 'templatespare') ?></th>
      </tr>
    </thead>
    <tbody id="the-list">
      <?php if (!empty($folders)) {
        foreach ($folders as $res) {
          $link = add_query_arg([
            'page' => 'templatespare-site-backup',
            'download_folder' => $res,
          ], admin_url('admin.php'));
          $link = wp_nonce_url($link, 'download_folder_' . $res, '_wpnonce');

          $delete_url = add_query_arg([
            'delete_folder' => $res,
            'page' => 'templatespare-site-backup',
          ], admin_url('admin.php'));
          $delete_url = wp_nonce_url($delete_url, 'delete_folder_' . $res, '_wpnonce');

          $formattedDatetime = convert_folder_name_to_datetime($res);
      ?>
          <tr>
            <td class="column-backup-name column-backup-date"><?php echo esc_html($formattedDatetime); ?></td>
            <td class="column-id load-folder-size" data-folder="<?php echo esc_attr($res); ?>"><?php esc_html_e('Calculating...', 'templatespare'); ?></td>
            <td class="column-backup-name"><?php echo esc_html($res); ?></td>
            <td class="column-actions">
              <?php if (class_exists('ZipArchive')) { ?>
                <a href="<?php echo esc_url($link); ?>" class="button button-primary">
                  <i class="dashicons dashicons-download"></i>
                  <span><?php esc_html_e('Download', 'templatespare'); ?></span>
                </a>
              <?php } ?>
              <a href="<?php echo esc_url($delete_url); ?>" class="button button-danger" onclick="return confirm('<?php esc_html_e('Are you sure you want to delete this export package permanently?', 'templatespare'); ?>')">
                <i class="dashicons dashicons-trash"></i>
                <span><?php esc_html_e('Delete', 'templatespare'); ?></span>
              </a>
            </td>
          </tr>
      <?php 
        }
      } else { ?>
          <tr>
            <td colspan="4"><?php esc_html_e('No site export archives found yet. Generate your first export below.', 'templatespare'); ?></td>
          </tr>
      <?php } ?>
    </tbody>
  </table>

  <!-- Form for creating backup & Pagination -->
  <div class="tablenav bottom">
    <div class="alignleft">
      <form method="post">
        <button type="submit" name="templastespare_sitebackup" class="button button-primary templastespare_sitebackup" value="Backup Now">
          <span class="dashicons dashicons-plus-alt"></span>
          <?php echo esc_html('Create New Site Export', 'templatespare'); ?>
        </button>
      </form>
    </div>
    
    <?php
    $total_pages = ceil($total_items / $per_page);

    if ($total_pages > 1) {
      $current_page = max(1, $paged);

      $pagination = paginate_links([
        'base'         => add_query_arg('paged', '%#%'),
        'format'       => '?paged=%#%',
        'total'        => $total_pages,
        'current'      => $current_page,
        'show_all'     => false,
        'end_size'     => 3,
        'mid_size'     => 2,
        'prev_text'    => __('« Prev', 'templatespare'),
        'next_text'    => __('Next »', 'templatespare'),
        'type'         => 'grid',
      ]);

      if ($pagination) { ?>
        <div class="tablenav-pages">
          <span class="displaying-num"><?php echo esc_html($total_items); ?> <?php esc_html_e('items', 'templatespare'); ?></span>
          <span class="pagination-links">
            <?php echo wp_kses_post($pagination); ?>
          </span>
        </div>
    <?php  
      }
    }
    ?>
  </div>
</div>