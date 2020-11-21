<?php

namespace wpie\export\bg;

if (!defined('ABSPATH')) {
    die(__("Can't load this file directly", 'wp-import-export-lite'));
}

if (file_exists(WPIE_EXPORT_CLASSES_DIR . '/class-wpie-export.php')) {

    require_once(WPIE_EXPORT_CLASSES_DIR . '/class-wpie-export.php');
}

class WPIE_BG extends \wpie\export\WPIE_Export {

    public function __construct() {

        add_action('init', array($this, 'unlock_export_process'), 200);
    }

    public function init() {

        add_action('init', array($this, 'init_bg_export'), 100);

        add_filter('wpie_add_export_extension_process_btn', array($this, 'add_bg_export_btn'), 10, 1);
    }

    public function init_bg_export($export_type = "") {

        global $wpdb;

        if (empty($export_type)) {

            $export_type = "'export','schedule_export'";
        } else {
            $export_type = "'" . $export_type . "'";
        }

        $template = $wpdb->get_row("SELECT * FROM " . $wpdb->prefix . "wpie_template where `opration` in (" . $export_type . ") and status LIKE '%background%' and process_lock = 0 ORDER BY `id` ASC limit 1");

        if (isset($template->id) && absint($template->id) > 0) {

            $export_type = isset($template->opration_type) ? $template->opration_type : "post";

            $opration = isset($template->opration) ? $template->opration : "export";

            $process_log = $this->init_export($export_type, $opration, $template);

            unset($export_type, $process_log);
        }
        unset($template);
    }

    public function add_bg_export_btn($files = array()) {

        $fileName = WPIE_EXPORT_CLASSES_DIR . '/extensions/bg/wpie_bg_btn.php';

        if (!in_array($fileName, $files)) {

            $files[] = $fileName;
        }

        return $files;
    }

    public function unlock_export_process() {

        global $wpdb;

        $current_time = date('Y-m-d H:i:s', strtotime('-1 hour', strtotime(current_time("mysql"))));

        $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}wpie_template SET process_lock = 0 WHERE process_lock = 1 and last_update_date < %s", $current_time));

        unset($current_time);
    }

}
