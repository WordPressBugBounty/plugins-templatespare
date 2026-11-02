<?php
wp_enqueue_script('templatespare_fileimport_script');
?>
<div class="wrap">
  <h1><?php echo esc_html__('Site Import & Migration Manager', 'templatespare'); ?></h1>
  <div id="templatespare-config"></div>
  <div id="templatespare-error"></div>
  <p><?php esc_html_e('Quickly restore, migrate, or set up your site using a TemplateSpare package. Upload your export archive below to replicate themes, plugins, and configurations effortlessly.', 'templatespare'); ?></p>

  <h2><?php esc_html_e('Upload & Import Package', 'templatespare'); ?></h2>

  <div class="templatespare-demo-drop">
    <form method="post" enctype="multipart/form-data" id="templatespare-upload-file">
      <div class="templatespare-drop-zone">
        <p class="templatespare-drop-zone-text"><?php echo esc_html__('Drag and drop your TemplateSpare export file here, or click to browse from your computer.', 'templatespare'); ?></p>
        <input type="file" name="templatespare_import_file" class="templatespare_import_file" required />
        <p class="file-name" style="display: none;"></p>
      </div>

      <button type="submit" name="nbm_migrate" id="templatespare_importBtn" class="button button-primary templatespare_import_zip">
        <span class="dashicons dashicons-database-import"></span>
        <?php echo esc_html__('Start Site Import', 'templatespare'); ?>
      </button>
    </form>

    <div class="importing" style="display: none;">
      <div class="templatespare-initial-msg">
        <h2><?php echo esc_html__('Importing your site. Please wait...', 'templatespare'); ?></h2>
        <p><?php echo esc_html__('This will only take a few moments.', 'templatespare'); ?></p>
      </div>
      <img class="templatespare-loader-img"
        src="<?php echo esc_url(AFTMLS_PLUGIN_URL . 'assets/images/importing.svg'); ?>"
        alt="<?php echo esc_attr__('Importing...', 'templatespare'); ?>">
      <div class="templatespare-success-img" style="display: none;">
        <p><?php echo esc_html__('Site imported successfully!', 'templatespare'); ?></p>
        <img src="<?php echo esc_url(AFTMLS_PLUGIN_URL . 'assets/images/success.svg'); ?>" alt="<?php echo esc_attr__('Success', 'templatespare'); ?>">
      </div>
    </div>
    
    <div class="templatespare-import-kit-popup-wrap" style="display: none;">
      <div class="overlay"></div>
      <div class="templatespare-import-kit-popup">
        <div class="content">
          <div class="progress-wrap">
            <div class="progress-bar-container">
              <div class="progress-bar" style="display:none;">
                <strong></strong>
              </div>
            </div>
            <strong></strong>
          </div>
        </div>
      </div>
    </div>
  </div>

  <h3><?php esc_html_e('How it works', 'templatespare'); ?></h3>
  <p><?php esc_html_e('Ensure you are uploading a valid TemplateSpare archive file containing your setup configuration, theme bundles, required plugins, and demo content.', 'templatespare'); ?></p>

  <p><?php esc_html_e('Need an export file? Visit the ', 'templatespare'); ?>
    <a href="<?php echo esc_url(admin_url('admin.php?page=templatespare-site-backup')); ?>"><?php esc_html_e('Export Dashboard', 'templatespare'); ?></a>
    <?php esc_html_e('to generate a fresh package.', 'templatespare'); ?>
  </p>
</div>