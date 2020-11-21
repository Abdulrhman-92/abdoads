<?php

namespace wpie\export\actions;

if ( ! defined( 'ABSPATH' ) ) {
        die( __( "Can't load this file directly", 'wp-import-export-lite' ) );
}

if ( file_exists( WPIE_EXPORT_CLASSES_DIR . '/class-wpie-export.php' ) ) {
        require_once(WPIE_EXPORT_CLASSES_DIR . '/class-wpie-export.php');
}

class WPIE_Export_Actions extends \wpie\export\WPIE_Export {

        public function __construct() {

                add_action( 'wp_ajax_wpie_export_get_template_list', array( $this, 'wpie_export_get_template_list' ) );

                add_action( 'wp_ajax_wpie_export_save_template', array( $this, 'wpie_export_save_template' ) );

                add_action( 'wp_ajax_wpie_export_get_template_data', array( $this, 'wpie_export_get_template_data' ) );

                add_action( 'wp_ajax_wpie_export_records_count', array( $this, 'wpie_export_records_count' ) );

                add_action( 'wp_ajax_wpie_export_field_list', array( $this, 'wpie_export_field_list' ) );

                add_action( 'wp_ajax_wpie_export_get_rule_list', array( $this, 'wpie_export_get_rule_list' ) );

                add_action( 'wp_ajax_wpie_export_create_data', array( $this, 'wpie_export_create_data' ) );

                add_action( 'wp_ajax_wpie_export_update_data', array( $this, 'wpie_export_update_data' ) );

                add_action( 'wp_ajax_wpie_export_prepare_file', array( $this, 'wpie_export_prepare_file' ) );

                add_action( 'wp_ajax_wpie_export_get_preview_data', array( $this, 'wpie_export_get_preview_data' ) );

                add_action( 'wp_ajax_wpie_export_update_status', array( $this, 'wpie_export_update_status' ) );
        }

        public function wpie_export_get_template_list() {

                parent::get_template_list();
        }

        public function wpie_export_records_count() {

                parent::get_item_count();
        }

        public function wpie_export_field_list() {

                parent::get_field_list();
        }

        public function wpie_export_get_rule_list() {

                parent::get_export_rule();
        }

        public function wpie_export_save_template() {

                parent::save_template_data();
        }

        public function wpie_export_get_template_data() {

                parent::get_template();
        }

        public function wpie_export_create_data() {

                $is_package = isset( $_POST[ 'is_package' ] ) ? intval( $_POST[ 'is_package' ] ) === 1 : false;

                if ( $is_package && ! class_exists( '\ZipArchive' ) ) {
                        $error_data = [
                                'status'  => "error",
                                'message' => __( 'Please enable PHP ZIP extension', 'wp-import-export-lite' )
                        ];

                        echo json_encode( $error_data );

                        die();
                }
                parent::init_new_export();
        }

        public function wpie_export_update_data() {

                parent::init_export_process();
        }

        public function wpie_export_prepare_file() {

                parent::prepare_file();
        }

        public function wpie_export_get_preview_data() {

                parent::get_preview();
        }

        public function wpie_export_update_status() {

                parent::update_process_status();
        }

}
