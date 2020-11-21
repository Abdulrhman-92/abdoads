<?php

if ( ! defined( 'ABSPATH' ) ) {
        die( __( "Can't load this file directly", 'wp-import-export-lite' ) );
}

class WPIE_Common_Actions {

        public function __construct() {

                add_action( 'wp_ajax_wpie_save_user_cap', array( $this, 'wpie_save_user_cap' ) );

                add_action( 'wp_ajax_wpie_get_user_cap', array( $this, 'wpie_get_user_cap' ) );

                add_action( 'wp_ajax_wpie_delete_tempaltes', array( $this, 'wpie_delete_tempaltes' ) );

                add_action( 'wp_ajax_wpie_tempalte_import', array( $this, 'wpie_tempalte_import' ) );

                add_action( 'wp_ajax_wpie_get_tempaltes', array( $this, 'wpie_get_tempalte_list' ) );

                add_action( 'wp_ajax_wpie_save_advance_option', array( $this, 'wpie_save_advance_option' ) );

                add_action( 'wp_ajax_wpie_update_process_status', array( $this, 'update_process_status' ) );

        }

        public function wpie_save_user_cap() {

                $new_export = isset( $_POST[ 'wpie_cap_new_export' ] ) ? intval( wpie_sanitize_field( $_POST[ 'wpie_cap_new_export' ] ) ) : 0;

                $manage_export = isset( $_POST[ 'wpie_cap_manage_export' ] ) ? intval( wpie_sanitize_field( $_POST[ 'wpie_cap_manage_export' ] ) ) : 0;

                $new_import = isset( $_POST[ 'wpie_cap_new_import' ] ) ? intval( wpie_sanitize_field( $_POST[ 'wpie_cap_new_import' ] ) ) : 0;

                $manage_import = isset( $_POST[ 'wpie_cap_manage_import' ] ) ? intval( wpie_sanitize_field( $_POST[ 'wpie_cap_manage_import' ] ) ) : 0;

                $cap_settings = isset( $_POST[ 'wpie_cap_settings' ] ) ? intval( wpie_sanitize_field( $_POST[ 'wpie_cap_settings' ] ) ) : 0;

                $cap_ext = isset( $_POST[ 'wpie_cap_ext' ] ) ? intval( wpie_sanitize_field( $_POST[ 'wpie_cap_ext' ] ) ) : 0;

                $add_shortcode = isset( $_POST[ 'wpie_cap_add_shortcode' ] ) ? intval( wpie_sanitize_field( $_POST[ 'wpie_cap_add_shortcode' ] ) ) : 0;

                $wpie_user_role = isset( $_POST[ 'wpie_user_role' ] ) ? wpie_sanitize_field( $_POST[ 'wpie_user_role' ] ) : "";

                $role = get_role( $wpie_user_role );

                unset( $wpie_user_role );

                if ( $role ) {

                        if ( $new_export == 1 ) {
                                if ( ! $role->has_cap( 'wpie_new_export' ) ) {
                                        $role->add_cap( 'wpie_new_export' );
                                }
                        } else {
                                $role->remove_cap( 'wpie_new_export' );
                        }

                        if ( $manage_export == 1 ) {
                                if ( ! $role->has_cap( 'wpie_manage_export' ) ) {
                                        $role->add_cap( 'wpie_manage_export' );
                                }
                        } else {
                                $role->remove_cap( 'wpie_manage_export' );
                        }

                        if ( $new_import == 1 ) {
                                if ( ! $role->has_cap( 'wpie_new_import' ) ) {
                                        $role->add_cap( 'wpie_new_import' );
                                }
                        } else {
                                $role->remove_cap( 'wpie_new_import' );
                        }
                        if ( $manage_import == 1 ) {
                                if ( ! $role->has_cap( 'wpie_manage_import' ) ) {
                                        $role->add_cap( 'wpie_manage_import' );
                                }
                        } else {
                                $role->remove_cap( 'wpie_manage_import' );
                        }
                        if ( $cap_settings == 1 ) {
                                if ( ! $role->has_cap( 'wpie_settings' ) ) {
                                        $role->add_cap( 'wpie_settings' );
                                }
                        } else {
                                $role->remove_cap( 'wpie_settings' );
                        }
                        if ( $cap_ext == 1 ) {
                                if ( ! $role->has_cap( 'wpie_extensions' ) ) {
                                        $role->add_cap( 'wpie_extensions' );
                                }
                        } else {
                                $role->remove_cap( 'wpie_extensions' );
                        }
                        if ( $add_shortcode == 1 ) {
                                if ( ! $role->has_cap( 'wpie_add_shortcode' ) ) {
                                        $role->add_cap( 'wpie_add_shortcode' );
                                }
                        } else {
                                $role->remove_cap( 'wpie_add_shortcode' );
                        }
                }

                unset( $new_export, $manage_export, $new_import, $manage_import, $cap_settings, $cap_ext, $role, $add_shortcode );

                $return_value = array();

                $return_value[ 'status' ] = 'success';

                $return_value[ 'message' ] = __( 'Role Successfully Saved', 'wp-import-export-lite' );

                echo json_encode( $return_value );

                unset( $return_value );

                die();
        }

        public function wpie_get_user_cap() {

                $wpie_user_role = isset( $_GET[ 'user_role' ] ) ? wpie_sanitize_field( $_GET[ 'user_role' ] ) : "";

                $role = get_role( $wpie_user_role );

                $cap = array();

                if ( $role ) {
                        if ( $role->has_cap( 'wpie_new_export' ) ) {
                                $cap[] = 'wpie_new_export';
                        }
                        if ( $role->has_cap( 'wpie_manage_export' ) ) {
                                $cap[] = 'wpie_manage_export';
                        }
                        if ( $role->has_cap( 'wpie_new_import' ) ) {
                                $cap[] = 'wpie_new_import';
                        }
                        if ( $role->has_cap( 'wpie_manage_import' ) ) {
                                $cap[] = 'wpie_manage_import';
                        }
                        if ( $role->has_cap( 'wpie_settings' ) ) {
                                $cap[] = 'wpie_settings';
                        }
                        if ( $role->has_cap( 'wpie_extensions' ) ) {
                                $cap[] = 'wpie_extensions';
                        }
                        if ( $role->has_cap( 'wpie_add_shortcode' ) ) {
                                $cap[] = 'wpie_add_shortcode';
                        }
                }

                $return_value = array();

                $return_value[ 'status' ] = 'success';

                $return_value[ 'cap' ] = $cap;

                unset( $role, $wpie_user_role, $cap );

                echo json_encode( $return_value );

                unset( $return_value );

                die();
        }

        public function wpie_get_tempalte_list() {

                $templates = $this->wpie_get_templates();

                $template_html = "";

                if ( ! empty( $templates ) ) {

                        foreach ( $templates as $data ) {

                                $id = isset( $data->id ) ? $data->id : 0;

                                $options = isset( $data->options ) ? maybe_unserialize( $data->options ) : array();

                                $name = isset( $options[ 'wpie_template_name' ] ) ? $options[ 'wpie_template_name' ] : "";

                                if ( $id > 0 && ! empty( $name ) ) {

                                        $template_html .= '<option value="' . esc_attr( $id ) . '">' . esc_html( $name ) . '</option>';
                                }

                                unset( $id, $options, $name );
                        }
                }
                $return_value = array();

                $return_value[ 'status' ] = 'success';

                $return_value[ 'html' ] = $template_html;

                unset( $templates, $template_html );

                echo json_encode( $return_value );

                unset( $return_value );

                die();
        }

        public function get_export_list() {

                global $wpdb;

                $results = $wpdb->get_results( "SELECT * FROM " . $wpdb->prefix . "wpie_template where `opration` IN ('export','schedule_export') ORDER BY `id` DESC" );

                return $results;
        }

        public function get_import_list() {

                global $wpdb;

                $results = $wpdb->get_results( "SELECT * FROM " . $wpdb->prefix . "wpie_template where `opration` IN ('import','schedule_import') ORDER BY `id` DESC" );

                return $results;
        }

        public function wpie_get_templates() {

                global $wpdb;

                $results = $wpdb->get_results( "SELECT * FROM " . $wpdb->prefix . "wpie_template where `opration` IN ('import_template','export_template')" );

                return $results;
        }

        public function wpie_delete_tempaltes() {

                $templates = isset( $_GET[ 'templates' ] ) ? wpie_sanitize_field( $_GET[ 'templates' ] ) : "";

                if ( ! empty( $templates ) ) {

                        $templates = explode( ",", $templates );

                        if ( is_array( $templates ) && ! empty( $templates ) ) {

                                $ids = implode( ',', array_map( 'absint', $templates ) );

                                $process_type = isset( $_GET[ 'process_type' ] ) ? wpie_sanitize_field( $_GET[ 'process_type' ] ) : "";

                                if ( $process_type == "schedule_import" ) {

                                        foreach ( $templates as $cron_id ) {
                                                wp_clear_scheduled_hook( 'wpie_cron_schedule_import', array( absint( $cron_id ) ) );
                                        }
                                } elseif ( $process_type == "schedule_export" ) {
                                        foreach ( $templates as $cron_id ) {
                                                wp_clear_scheduled_hook( 'wpie_cron_schedule_export', array( absint( $cron_id ) ) );
                                        }
                                }

                                global $wpdb;

                                $wpdb->query( "DELETE FROM " . $wpdb->prefix . "wpie_template WHERE id IN(" . $ids . ")" );

                                unset( $ids );
                        }
                }

                unset( $templates );

                $return_value = array();

                $return_value[ 'status' ] = 'success';

                $return_value[ 'message' ] = __( 'Templates Successfully Deleted', 'wp-import-export-lite' );

                echo json_encode( $return_value );

                unset( $return_value );

                die();
        }

        public function wpie_save_advance_option() {

                $is_delete_data = isset( $_GET[ 'is_delete_data' ] ) ? absint( wpie_sanitize_field( $_GET[ 'is_delete_data' ] ) ) : 0;

                update_option( "wpie_delete_on_uninstall", $is_delete_data );

                $return_value = array();

                $return_value[ 'status' ] = 'success';

                $return_value[ 'message' ] = __( 'Settings Successfully Saved', 'wp-import-export-lite' );

                echo json_encode( $return_value );

                unset( $return_value );

                die();
        }

        public function wpie_tempalte_import() {

                $return_value = array( "status" => 'error' );

                if ( $_FILES && file_exists( $_FILES[ 'wpie_template_file' ][ 'tmp_name' ] ) && is_uploaded_file( $_FILES[ 'wpie_template_file' ][ 'tmp_name' ] ) ) {

                        if ( ! function_exists( 'wp_handle_upload' ) ) {
                                require_once( ABSPATH . 'wp-admin/includes/file.php' );
                        }

                        $movefile = wp_handle_upload( $_FILES[ 'wpie_template_file' ], array( 'test_form' => false ) );

                        if ( $movefile && isset( $movefile[ 'file' ] ) && ! isset( $movefile[ 'error' ] ) ) {

                                $file_path = $movefile[ 'file' ];

                                $template_data = file_get_contents( $file_path );

                                if ( ! empty( $template_data ) ) {

                                        $template_data = maybe_unserialize( $template_data );

                                        if ( ! empty( $template_data ) ) {

                                                global $wpdb;

                                                $values = array();

                                                $place_holders = array();

                                                $query = "INSERT INTO " . $wpdb->prefix . "wpie_template (opration, opration_type, options,unique_id,username) VALUES ";

                                                foreach ( $template_data as $template ) {

                                                        $opration = isset( $template->opration ) ? $template->opration : "";

                                                        $opration_type = isset( $template->opration_type ) ? $template->opration_type : "";

                                                        $options = isset( $template->options ) ? $template->options : "";

                                                        $unique_id = isset( $template->unique_id ) ? $template->unique_id : "";

                                                        $username = isset( $template->username ) ? $template->username : "";

                                                        array_push( $values, $opration, $opration_type, $options, $unique_id, $username );

                                                        unset( $opration, $opration_type, $options, $unique_id, $username );

                                                        $place_holders[] = "(%s, %s, %s, %s, %s)";
                                                }

                                                $query .= implode( ', ', $place_holders );

                                                if ( $wpdb->query( $wpdb->prepare( "$query ", $values ) ) ) {
                                                        $return_value[ 'status' ] = 'success';
                                                        $return_value[ 'message' ] = __( 'Templates Successfully Imported', 'wp-import-export-lite' );
                                                } else {
                                                        $return_value[ 'status' ] = 'error';
                                                        $return_value[ 'message' ] = __( 'Error when insert to database', 'wp-import-export-lite' );
                                                }
                                                unset( $place_holders, $query, $values );
                                        } else {
                                                $return_value[ 'status' ] = 'error';
                                                $return_value[ 'message' ] = __( 'Empty Data', 'wp-import-export-lite' );
                                        }
                                } else {
                                        $return_value[ 'status' ] = 'error';
                                        $return_value[ 'message' ] = __( 'Empty Data', 'wp-import-export-lite' );
                                }
                                unset( $template_data );

                                unlink( $file_path );
                        } else {
                                $return_value[ 'status' ] = 'error';
                                $return_value[ 'message' ] = isset( $movefile[ 'error' ] ) ? $movefile[ 'error' ] : __( 'Error When move uploaded file', 'wp-import-export-lite' );
                        }
                }

                echo json_encode( $return_value );

                unset( $return_value, $is_success, $file_path );

                die();
        }

        public function update_process_status() {

                global $wpdb;

                $return_value = array( "status" => "error" );

                $wpie_import_id = isset( $_GET[ 'wpie_process_id' ] ) ? absint( wpie_sanitize_field( $_GET[ 'wpie_process_id' ] ) ) : 0;

                if ( $wpie_import_id > 0 ) {

                        $process_status = isset( $_GET[ 'process_status' ] ) ? wpie_sanitize_field( $_GET[ 'process_status' ] ) : "";

                        $new_satus = "";

                        if ( $process_status == "bg" ) {

                                $new_satus = "background";

                                $return_value[ 'message' ] = __( 'Background Process Successfully Set', 'wp-import-export-lite' );
                        } elseif ( $process_status == "stop" ) {

                                $new_satus = "stopped";

                                $return_value[ 'message' ] = __( 'Process Stopped Successfully', 'wp-import-export-lite' );
                        } elseif ( $process_status == "pause" ) {

                                $new_satus = "paused";

                                $return_value[ 'message' ] = __( 'Process Paused Successfully', 'wp-import-export-lite' );
                        }

                        unset( $process_status );

                        if ( $new_satus != "" ) {

                                $final_data = array(
                                        'last_update_date' => current_time( 'mysql' ),
                                        'status'           => $new_satus,
                                );

                                $wpdb->update( $wpdb->prefix . "wpie_template", $final_data, array( 'id' => $wpie_import_id ) );

                                unset( $final_data );

                                $return_value[ 'status' ] = 'success';
                        } else {
                                $return_value[ 'message' ] = __( 'Empty Status', 'wp-import-export-lite' );
                        }

                        unset( $new_satus );
                } else {
                        $return_value[ 'message' ] = __( 'Template id not found', 'wp-import-export-lite' );
                }

                unset( $wpie_import_id );

                echo json_encode( $return_value );

                die();
        }

        public function __destruct() {
                foreach ( $this as $key => $value ) {
                        unset( $this->$key );
                }
        }

}
