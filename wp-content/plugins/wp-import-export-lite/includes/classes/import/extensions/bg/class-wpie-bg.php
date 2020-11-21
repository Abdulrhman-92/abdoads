<?php

namespace wpie\import\bg;

if ( ! defined( 'ABSPATH' ) ) {
        die( __( "Can't load this file directly", 'wp-import-export-lite' ) );
}

if ( file_exists( WPIE_IMPORT_CLASSES_DIR . '/class-wpie-import.php' ) ) {

        require_once(WPIE_IMPORT_CLASSES_DIR . '/class-wpie-import.php');
}

class WPIE_BG_Import extends \wpie\import\WPIE_Import {

        public function __construct() {

                add_action( 'init', array( $this, 'wpie_bg_import' ), 100 );

                add_action( 'init', array( $this, 'wpie_bg_unlock_import' ), 200 );

                add_filter( 'wpie_add_import_extension_process_btn_files', array( $this, 'wpie_add_bg_process_btn' ), 10, 1 );
        }

        public function wpie_add_bg_process_btn( $files = array() ) {

                $fileName = WPIE_IMPORT_CLASSES_DIR . '/extensions/bg/wpie_bg_btn.php';

                if ( ! in_array( $fileName, $files ) ) {

                        $files[] = $fileName;
                }

                return $files;
        }

        public function wpie_bg_unlock_import() {

                global $wpdb;

                $current_time = date( 'Y-m-d H:i:s', strtotime( '-1 hour', strtotime( current_time( "mysql" ) ) ) );

                $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}wpie_template SET process_lock = 0 WHERE process_lock = 1 and last_update_date < %s", $current_time ) );

                unset( $current_time );
        }

        public function wpie_bg_import() {

                global $wpdb;

                $id = $wpdb->get_var( "SELECT `id` FROM " . $wpdb->prefix . "wpie_template where `opration` in ('import','schedule_import') and status LIKE '%background%' and process_lock = 0 ORDER BY `id` ASC limit 0,1" );

                if ( $id && absint( $id ) > 0 ) {

                        parent::wpie_import_process_data( $id );
                }
                unset( $id );
        }

}
