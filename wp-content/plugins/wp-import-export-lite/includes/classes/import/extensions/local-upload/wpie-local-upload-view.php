<?php
if (!defined('ABSPATH')) {
    die(__("Can't load this file directly", 'wp-import-export-lite'));
}
?>

<div class="wpie_upload_outer_container">
    <div id="wpie_upload_container" class="wpie_upload_container" >
        <div id="wpie_upload_drag_drop" class="wpie_upload_drag_drop">
            <div class="wpie_upload_file_label"><?php esc_html_e('Drop file here', 'wp-import-export-lite'); ?></div>
            <div class="wpie_upload_file_label_small"><?php esc_html_e('OR', 'wp-import-export-lite'); ?></div>
            <div class="wpie_upload_file_btn">
                <input id="plupload_browse_button" type="button" value="<?php esc_attr_e('Select Files', 'wp-import-export-lite'); ?>" class="wpie_btn wpie_btn_primary wpie_btn_radius wpie_plupload_browse_button" />
            </div>
        </div>
        <input type="hidden" value="" class="wpie_upload_drag_drop_data" wpie_status="processing"/>
    </div>
    <div class="wpie_uploaded_file_list_wrapper">
        <div class="wpie_local_uploaded_filename_wrapper">
            <div class="wpie_local_uploaded_filename_label"><?php esc_html_e('Uploading', 'wp-import-export-lite'); ?></div>
            <div class="wpie_local_uploaded_file_sep">-</div>
            <div class="wpie_local_uploaded_filename"></div>
        </div>
        <div class="progress wpie_import_upload_process">
            <div class="progress-bar progress-bar-striped progress-bar-animated wpie_import_upload_process_per" role="progressbar" style="" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
        </div>

    </div>
    <div class="wpie_file_list_wrapper"></div>
</div>