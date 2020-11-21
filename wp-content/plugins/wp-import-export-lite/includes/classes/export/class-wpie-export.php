<?php

namespace wpie\export;

use wpie\export\post;
use wpie\export\taxonomy;
use wpie\lib\xml\array2xml;
use PhpOffice\PhpSpreadsheet\Reader;
use PhpOffice\PhpSpreadsheet\Writer;

if ( ! defined( 'ABSPATH' ) ) {
        die( __( "Can't load this file directly", 'wp-import-export-lite' ) );
}

class WPIE_Export {

        protected function get_template_list() {

                global $wpdb;

                $content_type = isset( $_POST[ 'content_type' ] ) ? wpie_sanitize_field( $_POST[ 'content_type' ] ) : "post";

                $results = $wpdb->get_results( $wpdb->prepare( "SELECT `id`,`options` FROM " . $wpdb->prefix . "wpie_template where `opration_type` = %s AND `opration`='export_template'", $content_type ) );

                $data = array ();

                if ( ! empty( $results ) ) {

                        $count = 0;

                        foreach ( $results as $template ) {

                                $data[ $count ][ 'id' ] = isset( $template->id ) ? $template->id : 0;

                                $options = isset( $template->options ) ? maybe_unserialize( $template->options ) : array ();

                                $data[ $count ][ 'name' ] = isset( $options[ 'template_name' ] ) ? $options[ 'template_name' ] : "";

                                unset( $options );

                                $count ++;
                        }

                        unset( $count );
                }

                unset( $content_type, $results );

                $return_value = array ();

                $return_value[ 'status' ] = 'success';

                $return_value[ 'data' ] = $data;

                unset( $data );

                echo json_encode( $return_value );

                die();
        }

        public function prepare_fields( $export_type = "", $taxonomy_type = "", $attribute_taxonomy = "" ) {
                return $this->init_export( $export_type, "fields", [ "wpie_taxonomy_type" => $taxonomy_type, "wpie_attribute_taxonomy" => $attribute_taxonomy ] );
        }

        protected function get_field_list() {

                $export_type = isset( $_GET[ 'export_type' ] ) ? wpie_sanitize_field( $_GET[ 'export_type' ] ) : "post";

                $taxonomy_type = isset( $_GET[ 'taxonomy_type' ] ) ? wpie_sanitize_field( $_GET[ 'taxonomy_type' ] ) : "";

                $attribute_taxonomy = isset( $_GET[ 'attribute_taxonomy' ] ) && ! empty( $_GET[ 'attribute_taxonomy' ] ) ? explode( ",", wpie_sanitize_field( $_GET[ 'attribute_taxonomy' ] ) ) : [];

                $fields = $this->prepare_fields( $export_type, $taxonomy_type, $attribute_taxonomy );

                if ( is_wp_error( $fields ) ) {

                        $return_value[ 'status' ] = 'error';

                        $return_value[ 'message' ] = $fields->get_error_message();
                } else {
                        $return_value[ 'status' ] = 'success';

                        $return_value[ 'fields' ] = $fields;
                }

                unset( $export_type, $taxonomy_type, $fields );

                echo json_encode( $return_value );

                die();
        }

        public function init_export( $export_type = "post", $opration = "export", $template = null ) {

                $export_engine = "";

                if ( $export_type == "taxonomies" ) {

                        if ( file_exists( WPIE_EXPORT_CLASSES_DIR . '/class-wpie-taxonomy.php' ) ) {

                                require_once(WPIE_EXPORT_CLASSES_DIR . '/class-wpie-taxonomy.php');
                        }

                        $export_engine = '\wpie\export\taxonomy\WPIE_Taxonomy';
                } elseif ( $export_type == "comments" || $export_type == "product_reviews" ) {

                        if ( file_exists( WPIE_EXPORT_CLASSES_DIR . '/class-wpie-comment.php' ) ) {

                                require_once(WPIE_EXPORT_CLASSES_DIR . '/class-wpie-comment.php');
                        }
                        $export_engine = '\wpie\export\comment\WPIE_Comment';
                } else {

                        if ( file_exists( WPIE_EXPORT_CLASSES_DIR . '/class-wpie-post.php' ) ) {

                                require_once(WPIE_EXPORT_CLASSES_DIR . '/class-wpie-post.php');
                        }
                        $export_engine = '\wpie\export\post\WPIE_Post';
                }

                $export_engine = apply_filters( 'wpie_export_engine_init', $export_engine, $export_type, $template );

                $export_process = array ();

                if ( class_exists( $export_engine ) ) {

                        $export_data = new $export_engine();

                        if ( method_exists( $export_data, "init_engine" ) ) {
                                $export_process = $export_data->init_engine( $export_type, $opration, $template );
                        }

                        unset( $export_data );
                } else {
                        return new \WP_Error( 'wpie_import_error', sprintf( __( 'Class %s Not Exist', 'wp-import-export-lite' ), $export_engine ) );
                }

                unset( $export_engine, $export_type );

                return $export_process;
        }

        private function get_deafult_export_type() {

                return [
                        "post"               => __( 'Post', 'wp-import-export-lite' ),
                        "page"               => __( 'Page', 'wp-import-export-lite' ),
                        "product"            => __( 'WooCommerce Products', 'wp-import-export-lite' ),
                        "taxonomies"         => __( 'Taxonomies | Categories | Tags', 'wp-import-export-lite' ),
                        "users"              => __( 'Users', 'wp-import-export-lite' ),
                        "comments"           => __( 'Comments', 'wp-import-export-lite' ),
                        "product_reviews"    => __( 'Product Reviews', 'wp-import-export-lite' ),
                        "product_attributes" => __( 'Product Attributes', 'wp-import-export-lite' ),
                        "shop_order"         => __( 'WooCommerce Orders', 'wp-import-export-lite' ),
                        "shop_coupon"        => __( 'WooCommerce Coupons', 'wp-import-export-lite' ),
                        "shop_customer"      => __( 'WooCommerce Customers', 'wp-import-export-lite' ),
                ];
        }

        public function get_export_type() {

                $export_type = $this->get_deafult_export_type();

                $custom_export_type = get_post_types( [ '_builtin' => true ], 'objects' ) + get_post_types( [ '_builtin' => false, 'show_ui' => true ], 'objects' ) + get_post_types( [ '_builtin' => false, 'show_ui' => false ], 'objects' );

                if ( empty( $custom_export_type ) ) {
                        return $export_type;
                }

                $hidden_posts = [
                        'attachment',
                        'revision',
                        'nav_menu_item',
                        'shop_webhook',
                        'import_users',
                        'wp-types-group',
                        'wp-types-user-group',
                        'wp-types-term-group',
                        'acf-field',
                        'acf-field-group',
                        'custom_css',
                        'customize_changeset',
                        'oembed_cache',
                        'wp_block',
                        'user_request',
                        'scheduled-action',
                        'product_variation',
                        'shop_order_refund'
                ];

                foreach ( $custom_export_type as $key => $data ) {

                        if ( in_array( $key, $hidden_posts ) ) {
                                continue;
                        }

                        if ( isset( $export_type[ $key ] ) ) {
                                continue;
                        }

                        $label = isset( $data->labels ) && isset( $data->labels->singular_name ) ? $data->labels->singular_name : "";

                        if ( trim( $label ) === "" ) {

                                $label = isset( $data->labels ) && isset( $data->labels->name ) ? $data->labels->name : "";

                                if ( trim( $label ) === "" ) {
                                        continue;
                                }
                        }

                        $export_type[ $key ] = $label;
                }


                unset( $custom_export_type );

                return $export_type;
        }

        public function wpie_get_taxonomies() {

                $taxonomies = get_taxonomies( false, 'objects' );

                $data = [
                        "category"    => __( 'Post Categories', 'wp-import-export-lite' ),
                        "product_cat" => __( 'Product Categories', 'wp-import-export-lite' ),
                        "post_tag"    => __( 'Post Tags', 'wp-import-export-lite' ),
                        "product_tag" => __( 'Product Tags', 'wp-import-export-lite' ),
                ];

                if ( ! empty( $taxonomies ) ) {

                        foreach ( $taxonomies as $key => $taxonomy ) {

                                if ( in_array( $key, [ 'nav_menu', 'link_category' ] ) || isset( $data[ $key ] ) || (isset( $taxonomy->show_in_nav_menus ) && $taxonomy->show_in_nav_menus === false) ) {
                                        continue;
                                }

                                $data[ $key ] = ucwords( str_replace( '_', ' ', $key ) );
                        }
                }

                unset( $taxonomies );

                return $data;
        }

        public function get_attribute_list() {

                global $wpdb;

                return $wpdb->get_results( "SELECT attribute_name,attribute_label FROM {$wpdb->prefix}woocommerce_attribute_taxonomies WHERE attribute_name != ''  ORDER BY attribute_name ASC;" );
        }

        public function wpie_get_attribute_taxonomies() {

                $taxonomies = get_taxonomies( false, 'objects' );

                if ( ! empty( $taxonomies ) ) {

                        foreach ( $taxonomies as $key => $taxonomy ) {

                                if ( in_array( $key, [ 'nav_menu', 'link_category' ] ) || isset( $data[ $key ] ) || (isset( $taxonomy->show_in_nav_menus ) && $taxonomy->show_in_nav_menus === false) ) {
                                        continue;
                                }

                                $data[ $key ] = ucwords( str_replace( '_', ' ', $key ) );
                        }
                }

                unset( $taxonomies );

                return $data;
        }

        protected function get_export_rule() {

                $wpie_export_rules = array (
                        'wpie_tax'              => array (
                                'in'     => __( 'In', 'wp-import-export-lite' ),
                                'not_in' => __( 'Not In', 'wp-import-export-lite' )
                        ),
                        'wpie_date'             => array (
                                'equals'            => __( 'equals', 'wp-import-export-lite' ),
                                'not_equals'        => __( "doesn't equal", 'wp-import-export-lite' ),
                                'greater'           => __( 'newer than', 'wp-import-export-lite' ),
                                'equals_or_greater' => __( 'equal to or newer than', 'wp-import-export-lite' ),
                                'less'              => __( 'older than', 'wp-import-export-lite' ),
                                'equals_or_less'    => __( 'equal to or older than', 'wp-import-export-lite' ),
                                'contains'          => __( 'contains', 'wp-import-export-lite' ),
                                'not_contains'      => __( "doesn't contain", 'wp-import-export-lite' ),
                                'is_empty'          => __( 'is empty', 'wp-import-export-lite' ),
                                'is_not_empty'      => __( 'is not empty', 'wp-import-export-lite' ),
                        ),
                        'wpie_capabilities'     => array (
                                'contains'     => __( 'contains', 'wp-import-export-lite' ),
                                'not_contains' => __( "doesn't contain", 'wp-import-export-lite' ),
                        ),
                        'wpie_user'             => array (
                                'equals'       => __( 'equals', 'wp-import-export-lite' ),
                                'not_equals'   => __( "doesn't equal", 'wp-import-export-lite' ),
                                'contains'     => __( 'contains', 'wp-import-export-lite' ),
                                'not_contains' => __( "doesn't contain", 'wp-import-export-lite' ),
                                'is_empty'     => __( 'is empty', 'wp-import-export-lite' ),
                                'is_not_empty' => __( 'is not empty', 'wp-import-export-lite' ),
                        ),
                        'wpie_term_parent_slug' => array (
                                'equals'            => __( 'equals', 'wp-import-export-lite' ),
                                'not_equals'        => __( "doesn't equal", 'wp-import-export-lite' ),
                                'greater'           => __( 'greater than', 'wp-import-export-lite' ),
                                'equals_or_greater' => __( 'equal to or greater than', 'wp-import-export-lite' ),
                                'less'              => __( 'less than', 'wp-import-export-lite' ),
                                'equals_or_less'    => __( 'equal to or less than', 'wp-import-export-lite' ),
                                'is_empty'          => __( 'is empty', 'wp-import-export-lite' ),
                                'is_not_empty'      => __( 'is not empty', 'wp-import-export-lite' ),
                        ),
                        'default'               => array (
                                'equals'            => __( 'equals', 'wp-import-export-lite' ),
                                'not_equals'        => __( "doesn't equal", 'wp-import-export-lite' ),
                                'greater'           => __( 'greater than', 'wp-import-export-lite' ),
                                'equals_or_greater' => __( 'equal to or greater than', 'wp-import-export-lite' ),
                                'less'              => __( 'less than', 'wp-import-export-lite' ),
                                'equals_or_less'    => __( 'equal to or less than', 'wp-import-export-lite' ),
                                'contains'          => __( 'contains', 'wp-import-export-lite' ),
                                'not_contains'      => __( "doesn't contain", 'wp-import-export-lite' ),
                                'is_empty'          => __( 'is empty', 'wp-import-export-lite' ),
                                'is_not_empty'      => __( 'is not empty', 'wp-import-export-lite' ),
                                'in'                => __( 'In', 'wp-import-export-lite' ),
                                'not_in'            => __( 'Not In', 'wp-import-export-lite' )
                        )
                );


                $return_value = array ();

                $return_value[ 'status' ] = 'success';

                $return_value[ 'wpie_export_rule' ] = apply_filters( "wpie_export_ruels", $wpie_export_rules );

                echo json_encode( $return_value );

                die();
        }

        protected function save_template_data() {

                global $wpdb;

                $template_name = isset( $_POST[ 'template_name' ] ) ? wpie_sanitize_field( $_POST[ 'template_name' ] ) : "";

                $template_id = isset( $_POST[ 'template_id' ] ) ? absint( wpie_sanitize_field( $_POST[ 'template_id' ] ) ) : 0;

                if ( $template_id > 0 ) {

                        $options = $wpdb->get_var( $wpdb->prepare( "SELECT `options` FROM " . $wpdb->prefix . "wpie_template where `id`=%d", $template_id ) );

                        if ( ! is_null( $options ) ) {

                                $options = maybe_unserialize( $options );

                                $new_options = $_POST;

                                $new_options[ 'template_name' ] = isset( $options[ 'template_name' ] ) ? $options[ 'template_name' ] : "";

                                $new_values = array ();

                                $new_values[ 'options' ] = maybe_serialize( $new_options );

                                $wpdb->update( $wpdb->prefix . "wpie_template", $new_values, array ( 'id' => $template_id ) );

                                $return_value[ 'status' ] = 'success';

                                $return_value[ 'message' ] = __( 'Template Successfully Updated', 'wp-import-export-lite' );

                                echo json_encode( $return_value );

                                die();
                        }
                }
                $is_exist = false;

                if ( ! empty( $template_name ) ) {

                        $results = $wpdb->get_results( "SELECT `id`,`options` FROM " . $wpdb->prefix . "wpie_template where `opration`='export_template'" );

                        if ( ! empty( $results ) ) {

                                foreach ( $results as $template ) {

                                        $options = isset( $template->options ) ? maybe_unserialize( $template->options ) : array ();

                                        $temp_name = isset( $options[ 'template_name' ] ) ? $options[ 'template_name' ] : "";

                                        if ( ! empty( $temp_name ) && $temp_name == $template_name ) {
                                                $is_exist = true;
                                                break;
                                        }
                                        unset( $options, $temp_name );
                                }
                        }

                        unset( $results );
                }
                if ( $is_exist === false ) {

                        $new_values = array ();

                        $new_values[ 'opration' ] = "export_template";

                        $new_values[ 'opration_type' ] = isset( $_POST[ 'wpie_export_type' ] ) ? wpie_sanitize_field( $_POST[ 'wpie_export_type' ] ) : "post";

                        $new_values[ 'options' ] = maybe_serialize( $_POST );

                        $new_values[ 'create_date' ] = current_time( 'mysql' );

                        $new_values[ 'unique_id' ] = uniqid();

                        $current_user = wp_get_current_user();

                        if ( $current_user && isset( $current_user->user_login ) ) {
                                $new_values[ 'username' ] = $current_user->user_login;
                        }

                        $wpdb->insert( $wpdb->prefix . "wpie_template", $new_values );

                        unset( $new_values, $current_user );

                        $template_id = $wpdb->insert_id;

                        $return_value = array ();

                        if ( $template_id && absint( $template_id ) > 0 ) {

                                $return_value[ 'status' ] = 'success';

                                $return_value[ 'template_id' ] = $template_id;

                                $return_value[ 'message' ] = __( 'Template Successfully Saved', 'wp-import-export-lite' );
                        } else {

                                $return_value[ 'status' ] = 'error';

                                $return_value[ 'message' ] = __( 'Fail to save template in database', 'wp-import-export-lite' );
                        }
                        unset( $template_id );
                } else {
                        $return_value[ 'status' ] = 'error';

                        $return_value[ 'message' ] = __( 'Template Name Already Exists', 'wp-import-export-lite' );
                }

                echo json_encode( $return_value );

                die();
        }

        protected function get_template() {

                $return_value = array ();

                $template_id = isset( $_GET[ 'template_id' ] ) ? absint( wpie_sanitize_field( $_GET[ 'template_id' ] ) ) : 0;

                if ( $template_id > 0 ) {

                        $template_data = $this->get_template_by_id( $template_id );

                        if ( $template_data !== false && isset( $template_data->options ) ) {

                                $options = isset( $template_data->options ) ? wp_unslash( maybe_unserialize( $template_data->options ) ) : array ();

                                $template_data->fields_data = isset( $options[ 'fields_data' ] ) ? wp_unslash( $options[ 'fields_data' ] ) : array ();

                                $return_value[ 'message' ] = 'success';

                                $return_value[ 'data' ] = $options;
                        } else {
                                $return_value[ 'status' ] = 'error';

                                $return_value[ 'message' ] = __( 'Template Not Found', 'wp-import-export-lite' );
                        }

                        unset( $template_data );
                } else {
                        $return_value[ 'status' ] = 'error';

                        $return_value[ 'message' ] = __( 'Template Not Found', 'wp-import-export-lite' );
                }
                unset( $template_id );

                echo json_encode( $return_value );

                die();
        }

        protected function get_template_by_id( $export_id = 0 ) {

                if ( ! empty( $export_id ) && absint( $export_id ) > 0 ) {

                        global $wpdb;

                        $results = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM " . $wpdb->prefix . "wpie_template where `id` = %d", $export_id ) );

                        if ( ! empty( $results ) && isset( $results[ 0 ] ) ) {
                                return $results[ 0 ];
                        }
                }
                return false;
        }

        protected function update_process_status() {

                global $wpdb;

                $return_value = array ( "status" => "error" );

                $wpie_import_id = isset( $_GET[ 'wpie_export_id' ] ) ? absint( wpie_sanitize_field( $_GET[ 'wpie_export_id' ] ) ) : 0;

                if ( $wpie_import_id > 0 ) {

                        $process_status = isset( $_GET[ 'process_status' ] ) ? wpie_sanitize_field( $_GET[ 'process_status' ] ) : "";

                        $new_satus = "";

                        if ( $process_status == "bg" ) {

                                $new_satus = "background";

                                $return_value[ 'message' ] = __( 'Background Process Successfully Set', 'wp-import-export-lite' );
                        } elseif ( $process_status == "stop" ) {

                                $new_satus = "stopped";

                                $return_value[ 'message' ] = __( 'Process Stopped Successfully', 'wp-import-export-lite' );
                        }

                        unset( $process_status );

                        if ( $new_satus != "" ) {

                                $final_data = array (
                                        'last_update_date' => current_time( 'mysql' ),
                                        'status'           => $new_satus,
                                );

                                $wpdb->update( $wpdb->prefix . "wpie_template", $final_data, array ( 'id' => $wpie_import_id ) );

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

        protected function get_safe_dir_name( $str = "", $separator = 'dash', $lowercase = true ) {

                if ( $separator == 'dash' ) {
                        $search = '_';
                        $replace = '-';
                } else {
                        $search = '-';
                        $replace = '_';
                }

                $trans = array (
                        '&\#\d+?;'       => '',
                        '&\S+?;'         => '',
                        '\s+'            => $replace,
                        '[^a-z0-9\-\._]' => '',
                        $search . '+'    => $replace,
                        $search . '$'    => $replace,
                        '^' . $search    => $replace,
                        '\.+$'           => ''
                );

                $str = strip_tags( $str );

                foreach ( $trans as $key => $val ) {
                        $str = preg_replace( "#" . $key . "#i", $val, $str );
                }

                if ( $lowercase === true ) {
                        $str = strtolower( $str );
                }
                unset( $search, $replace, $trans );

                return md5( trim( wp_unslash( $str ) ) . time() );
        }

        protected function init_new_export() {

                $export_id = $this->generate_template( $_POST, 'export' );

                $return_value = array ();

                $return_value[ 'status' ] = 'success';

                $return_value[ 'export_id' ] = $export_id;

                unset( $export_id );

                echo json_encode( $return_value );

                die();
        }

        protected function generate_template( $options = array (), $template_type = 'export', $status = 'processing' ) {

                $options[ 'max_item_count' ] = apply_filters( 'wpie_export_max_item_count', 1, $options );

                $file_data = $this->set_file_headers( $options );

                $options[ 'fileName' ] = isset( $file_data[ 'filename' ] ) ? $file_data[ 'filename' ] : "";

                $options[ 'fileDir' ] = isset( $file_data[ 'filedir' ] ) ? $file_data[ 'filedir' ] : "";

                $total = 0;

                if ( isset( $options[ "total" ] ) ) {

                        $total = absint( $options[ "total" ] );

                        unset( $options[ "total" ] );
                }

                $wpie_export_type = (isset( $options[ 'wpie_export_type' ] ) && trim( $options[ 'wpie_export_type' ] ) != "") ? wpie_sanitize_field( $options[ 'wpie_export_type' ] ) : "post";

                $current_time = current_time( 'mysql' );

                $new_values = array ();

                $new_values[ 'opration' ] = $template_type;

                $new_values[ 'opration_type' ] = $wpie_export_type;

                $new_values[ 'process_lock' ] = 0;

                $new_values[ 'process_log' ] = maybe_serialize( array ( "total" => $total ) );

                $new_values[ 'status' ] = $status;

                $new_values[ 'options' ] = maybe_serialize( $options );

                $new_values[ 'create_date' ] = $current_time;

                $new_values[ 'last_update_date' ] = $current_time;

                $new_values[ 'unique_id' ] = uniqid();

                $current_user = wp_get_current_user();

                if ( $current_user && isset( $current_user->user_login ) ) {
                        $new_values[ 'username' ] = $current_user->user_login;
                }

                global $wpdb;

                $wpdb->insert( $wpdb->prefix . "wpie_template", $new_values );

                unset( $options, $file_data, $total, $wpie_export_type, $current_time, $new_values );

                return $wpdb->insert_id;
        }

        private function generate_config_file( $options = array () ) {

                $wpie_export_type = isset( $options[ 'wpie_export_type' ] ) ? $options[ 'wpie_export_type' ] : "post";

                $config = array ();

                $config[ "import_type" ] = $wpie_export_type;

                $config[ "site_url" ] = site_url();

                $config[ "import_sub_type" ] = isset( $options[ 'wpie_taxonomy_type' ] ) ? $options[ 'wpie_taxonomy_type' ] : "";

                $fields_data = (isset( $options[ 'fields_data' ] ) && trim( $options[ 'fields_data' ] ) != "") ? explode( "~||~", wpie_sanitize_field( wp_unslash( $options[ 'fields_data' ] ) ) ) : array ();

                $export_fields = array ( "is_exported" => 1 );

                if ( $wpie_export_type == "product_attributes" ) {
                        $export_fields[ "wpie_existing_item_search_logic" ] = "slug";
                        $export_fields[ "wpie_existing_item_search_logic_slug" ] = "{slug[1]}";
                } elseif ( $wpie_export_type == "taxonomies" ) {
                        $export_fields[ "wpie_existing_item_search_logic" ] = "slug";
                } elseif ( $wpie_export_type == "product" ) {
                        $export_fields[ "wpie_item_variation_import_method" ] = "match_unique_field";
                        $export_fields[ "wpie_item_product_variation_field_parent" ] = "{id[1]}";
                        $export_fields[ "wpie_item_product_variation_match_unique_field_parent" ] = "{parent[1]}";
                } elseif ( $wpie_export_type == "shop_order" ) {
                        /* billing fields */
                        $export_fields[ "wpie_item_order_number" ] = "{orderid[1]}";
                        $export_fields[ "wpie_item_order_billing_source" ] = "existing";
                        $export_fields[ "wpie_item_order_billing_match_by" ] = "email";
                        $export_fields[ "wpie_item_order_billing_match_by_email" ] = "{_customer_user_email[1]}";
                        $export_fields[ "wpie_item_order_billing_no_match_guest" ] = "1";
                        $export_fields[ "wpie_item_guest_billing_first_name" ] = "{_billing_first_name[1]}";
                        $export_fields[ "wpie_item_guest_billing_last_name" ] = "{_billing_last_name[1]}";
                        $export_fields[ "wpie_item_guest_billing_address_1" ] = "{_billing_address_1[1]}";
                        $export_fields[ "wpie_item_guest_billing_address_2" ] = "{_billing_address_2[1]}";
                        $export_fields[ "wpie_item_guest_billing_city" ] = "{_billing_city[1]}";
                        $export_fields[ "wpie_item_guest_billing_postcode" ] = "{_billing_postcode[1]}";
                        $export_fields[ "wpie_item_guest_billing_country" ] = "{_billing_country[1]}";
                        $export_fields[ "wpie_item_guest_billing_state" ] = "{_billing_state[1]}";
                        $export_fields[ "wpie_item_guest_billing_email" ] = "{_billing_email[1]}";
                        $export_fields[ "wpie_item_guest_billing_phone" ] = "{_billing_phone[1]}";
                        $export_fields[ "wpie_item_guest_billing_company" ] = "{_billing_company[1]}";

                        /* shipping fields */
                        $export_fields[ "wpie_item_order_shipping_source" ] = "guest";
                        $export_fields[ "wpie_item_order_shipping_no_match_billing" ] = "1";
                        $export_fields[ "wpie_item_shipping_first_name" ] = "{_shipping_first_name[1]}";
                        $export_fields[ "wpie_item_shipping_last_name" ] = "{_shipping_last_name[1]}";
                        $export_fields[ "wpie_item_shipping_address_1" ] = "{_shipping_address_1[1]}";
                        $export_fields[ "wpie_item_shipping_address_2" ] = "{_shipping_address_2[1]}";
                        $export_fields[ "wpie_item_shipping_city" ] = "{_shipping_city[1]}";
                        $export_fields[ "wpie_item_shipping_postcode" ] = "{_shipping_postcode[1]}";
                        $export_fields[ "wpie_item_shipping_country" ] = "{_shipping_country[1]}";
                        $export_fields[ "wpie_item_shipping_state" ] = "{_shipping_state[1]}";
                        $export_fields[ "wpie_item_shipping_email" ] = "";
                        $export_fields[ "wpie_item_shipping_phone" ] = "";
                        $export_fields[ "wpie_item_shipping_company" ] = "{_shipping_company[1]}";
                        $export_fields[ "wpie_item_order_customer_provided_note" ] = "{customernote[1]}";

                        /* payment fields */
                        $export_fields[ "wpie_item_order_payment_method" ] = "as_specified";
                        $export_fields[ "wpie_item_order_payment_method_as_specified_data" ] = "{paymentmethodtitle[1]}";
                        $export_fields[ "wpie_item_order_transaction_id" ] = "{transactionid[1]}";

                        /* Order Items List Start */

                        /* Product Item */
                        $export_fields[ "wpie_item_order_item_product_name" ] = "{productname1[1]}";
                        $export_fields[ "wpie_item_order_item_product_price" ] = "{itemcost1[1]}";
                        $export_fields[ "wpie_item_order_item_product_quantity" ] = "{quantity1[1]}";
                        $export_fields[ "wpie_item_order_item_product_sku" ] = "{sku1[1]}";
                        $export_fields[ "wpie_item_order_item_is_variation" ] = "{isvariation1[1]}";
                        $export_fields[ "wpie_item_order_item_original_product_title" ] = "{originalproducttitle1[1]}";
                        $export_fields[ "wpie_item_order_item_variation_attributes" ] = "{variationattributes1[1]}";
                        $export_fields[ "wpie_item_order_item_product_delim" ] = "|";

                        $wpie_order_item_count = isset( $options[ 'wpie_order_item_count' ] ) ? intval( $options[ 'wpie_order_item_count' ] ) : 0;

                        if ( $wpie_order_item_count > 1 ) {

                                for ( $i = 2; $i <= $wpie_order_item_count; $i ++ ) {

                                        $export_fields[ "wpie_item_order_item_product_name" ] .= "|{productname" . $i . "[1]}";
                                        $export_fields[ "wpie_item_order_item_product_price" ] .= "|{itemcost" . $i . "[1]}";
                                        $export_fields[ "wpie_item_order_item_product_quantity" ] .= "|{quantity" . $i . "[1]}";
                                        $export_fields[ "wpie_item_order_item_product_sku" ] .= "|{sku" . $i . "[1]}";
                                        $export_fields[ "wpie_item_order_item_is_variation" ] .= "|{isvariation" . $i . "[1]}";
                                        $export_fields[ "wpie_item_order_item_original_product_title" ] .= "|{originalproducttitle" . $i . "[1]}";
                                        $export_fields[ "wpie_item_order_item_variation_attributes" ] .= "|{variationattributes" . $i . "[1]}";
                                }
                        }

                        /* Fee Item */
                        $export_fields[ "wpie_item_order_item_fee" ] = "{feename[1]}";
                        $export_fields[ "wpie_item_order_item_fee_amount" ] = "{feeamountpersurcharge[1]}";
                        $export_fields[ "wpie_item_order_item_fees_delim" ] = "|";

                        /* Coupons Item */
                        $export_fields[ "wpie_item_order_item_coupon" ] = "{couponsused[1]}";
                        $export_fields[ "wpie_item_order_item_coupon_amount" ] = "{discountamountpercoupon[1]}";
                        $export_fields[ "wpie_item_order_item_coupon_amount_tax" ] = "";
                        $export_fields[ "wpie_item_order_item_coupon_delim" ] = "|";

                        /* Shipping Item */
                        $export_fields[ "wpie_item_order_item_shipping_name" ] = "{shippingmethod[1]}";
                        $export_fields[ "wpie_item_order_item_shipping_amount" ] = "{shippingcost[1]}";
                        $export_fields[ "wpie_item_order_item_shipping_method" ] = "{shippingmethod[1]}";
                        $export_fields[ "wpie_item_order_item_shipping_costs_delim" ] = "|";

                        /* Taxes Item */
                        $export_fields[ "wpie_item_order_item_tax_rate_amount" ] = "{amountpertax[1]}";
                        $export_fields[ "wpie_item_order_item_tax_shipping_tax_amount" ] = "{shippingtaxes[1]}";
                        $export_fields[ "wpie_item_order_item_tax_rate" ] = "{ratecodepertax[1]}";
                        $export_fields[ "wpie_item_order_item_shipping_costs_delim" ] = "|";

                        /* Order Items List End */

                        /* Refunds */
                        $export_fields[ "wpie_item_order_item_refund_amount" ] = "{refundamounts[1]}";
                        $export_fields[ "wpie_item_order_item_refund_reason" ] = "{refundreason[1]}";
                        $export_fields[ "wpie_item_order_item_refund_date" ] = "{refunddate[1]}";
                        $export_fields[ "wpie_item_order_item_refund_issued_match_by" ] = "existing";
                        $export_fields[ "wpie_item_order_item_refund_issued_by" ] = "email";
                        $export_fields[ "wpie_item_refund_customer_email" ] = "{refundauthoremail[1]}";

                        /* Order Total */
                        $export_fields[ "wpie_item_order_total" ] = "manually";
                        $export_fields[ "wpie_item_order_total_as_specified" ] = "{ordertotal[1]}";

                        /* Order Notes */
                        $export_fields[ "wpie_item_import_order_note_content" ] = "{notecontent[1]}";
                        $export_fields[ "wpie_item_import_order_note_date" ] = "{notedate[1]}";
                        $export_fields[ "wpie_item_import_order_note_visibility" ] = "{notevisibility[1]}";
                        $export_fields[ "wpie_item_import_order_note_username" ] = "{noteusername[1]}";
                        $export_fields[ "wpie_item_import_order_note_email" ] = "{noteuseremail[1]}";
                        $export_fields[ "wpie_item_import_order_note_delim" ] = "|";

                        /* Handle Existing Items */
                        $export_fields[ "wpie_existing_item_search_logic" ] = "cf";
                        $export_fields[ "wpie_existing_item_search_logic_cf_key" ] = "_order_key";
                        $export_fields[ "wpie_existing_item_search_logic_cf_value" ] = "{orderkey[1]}";
                } elseif ( $wpie_export_type == "comments" ) {

                        $export_fields[ "wpie_item_comment_parent_post" ] = "{parentposttitle[1]}";
                }

                if ( ! empty( $fields_data ) ) {

                        $configData = array ();
                        foreach ( $fields_data as $field ) {

                                if ( empty( $field ) ) {
                                        continue;
                                }
                                $new_field = explode( "|~|", $field );

                                $field_label = isset( $new_field[ 0 ] ) ? wpie_sanitize_field( $new_field[ 0 ] ) : "";

                                $field_option = isset( $new_field[ 1 ] ) ? json_decode( wpie_sanitize_field( $new_field[ 1 ] ), true ) : "";

                                unset( $new_field );

                                $field_type = isset( $field_option[ 'type' ] ) ? wpie_sanitize_field( $field_option[ 'type' ] ) : "";

                                $fielData = "{" . strtolower( preg_replace( '/[^a-z0-9_]/i', '', $field_label ) ) . "[1]}";

                                if ( in_array( $fielData, $configData ) ) {

                                        $tempField = $fielData;

                                        $count = 1;

                                        while ( in_array( $tempField, $configData ) ) {
                                                $tempField = "{" . strtolower( preg_replace( '/[^a-z0-9_]/i', '', $field_label ) ) . "_" . $count . "[1]}";
                                                $count ++;
                                        }

                                        $fielData = $tempField;

                                        unset( $tempField );

                                        unset( $count );
                                }

                                $new_key = $field_type;

                                if ( $field_type == "wc-product" ) {
                                        $field_type = "wpie_cf";
                                }

                                if ( $field_type == "wpie_cf" ) {
                                        $is_acf = isset( $field_option[ 'is_acf' ] ) ? intval( wpie_sanitize_field( $field_option[ 'is_acf' ] ) ) : 0;

                                        if ( $is_acf === 1 ) {
                                                continue;
                                        }

                                        $new_key = isset( $field_option[ 'metaKey' ] ) ? wpie_sanitize_field( $field_option[ 'metaKey' ] ) : "";
                                } elseif ( $field_type == "wpie_tax" ) {

                                        $new_key = isset( $field_option[ 'taxName' ] ) ? wpie_sanitize_field( $field_option[ 'taxName' ] ) : "";
                                }

                                if ( $field_type == "wpie-acf" ) {

                                        $acf_key = isset( $field_option[ 'acfKey' ] ) && ! empty( $field_option[ 'acfKey' ] ) ? $field_option[ 'acfKey' ] : "";

                                        if ( ! empty( $acf_key ) ) {

                                                $acf_field_id = isset( $field_option[ 'id' ] ) && ! empty( $field_option[ 'id' ] ) ? $field_option[ 'id' ] : "";

                                                $acf = [ $acf_key => $this->get_acf_field_data( $acf_field_id ) ];

                                                if ( ! empty( $acf ) ) {
                                                        if ( ! isset( $export_fields[ 'acf' ] ) ) {
                                                                $export_fields[ 'acf' ] = [];
                                                        }
                                                        $export_fields[ 'acf' ] = array_replace( $export_fields[ 'acf' ], $acf );
                                                }
                                                unset( $acf, $acf_field_id );
                                        }
                                        unset( $acf_key );
                                        continue;
                                }
                                if ( $wpie_export_type == "shop_order" ) {

                                        if ( $field_type == "wpie_cf" &&
                                                in_array( $new_key, array ( '_billing_first_name', '_billing_last_name', '_billing_company',
                                                        '_billing_address_1', '_billing_address_2', '_billing_city',
                                                        '_billing_postcode', '_billing_country', '_billing_state',
                                                        '_billing_email', '_customer_user_email', '_billing_phone',
                                                        '_shipping_first_name', '_shipping_last_name', '_shipping_company',
                                                        '_shipping_address_1', '_shipping_address_2', '_shipping_city',
                                                        '_shipping_postcode', '_shipping_country', '_shipping_state',
                                                        "_payment_method", "_transaction_id", "_payment_method_title", "_order_total",
                                                        '_customer_user'
                                                        )
                                                )
                                        ) {
                                                continue;
                                        } elseif ( $field_type == "wc-order" ) {

                                                $order_field_type = isset( $field_option[ 'field_type' ] ) ? wpie_sanitize_field( $field_option[ 'field_type' ] ) : "";

                                                $order_field_key = isset( $field_option[ 'field_key' ] ) ? wpie_sanitize_field( $field_option[ 'field_key' ] ) : "";

                                                if ( $order_field_type == "coupons" && in_array( $new_key, array ( "_cart_discount" ) ) ) {
                                                        continue;
                                                }
                                        }
                                }

                                if ( isset( $configData[ $new_key ] ) ) {

                                        $tempField = $new_key;

                                        $count = 0;

                                        while ( isset( $configData[ $tempField ] ) ) {
                                                $tempField = $new_key . "_" . $count;
                                                $count ++;
                                        }

                                        $new_key = $tempField;

                                        unset( $tempField );

                                        unset( $count );
                                }
                                if ( $wpie_export_type == "comments" ) {

                                        if ( $new_key == "comment_parent" ) {
                                                $export_fields[ "wpie_item_" . $new_key ] = $fielData;
                                                continue;
                                        }
                                        if ( $new_key == "comment_parent_content" ) {
                                                $export_fields[ "wpie_item_" . $new_key ] = $fielData;
                                                $export_fields[ "wpie_item_comment_parent" ] = $fielData;
                                                continue;
                                        }
                                }
                                if ( $field_type == "wpie_cf" ) {

                                        if ( $wpie_export_type == "product" && in_array( $new_key, array ( "_sku", "_regular_price", "_sale_price", "_sale_price_dates_from", "_sale_price_dates_to", "_virtual", "_downloadable", "_tax_status", "_tax_class", "_downloadable_files", "_downloadable_file_name", "_download_limit", "_download_expiry", "_manage_stock", "_stock", "_stock_status", "_backorders", "_sold_individually", "_weight", "_length", "_width", "_height", "_upsell_ids", "_crosssell_ids", "_purchase_note", "_featured", "_visibility" ) ) ) {

                                                $export_fields[ "wpie_item_meta" . $new_key ] = $fielData;

                                                if ( $wpie_export_type == "_downloadable_files" ) {
                                                        $export_fields[ "wpie_item_downloadable_files_delim" ] = ",";
                                                        $export_fields[ "wpie_item_downloadable_file_name_delim" ] = ",";
                                                }
                                        } else {
                                                $_uniqueid = uniqid();
                                                $export_fields[ "wpie_item_cf" ][ $_uniqueid ][ "name" ] = $new_key;
                                                $export_fields[ "wpie_item_cf" ][ $_uniqueid ][ "value" ] = $fielData;
                                        }
                                } elseif ( $field_type == "wpie_tax" ) {

                                        if ( in_array( $new_key, array ( "product_type", "product_shipping_class" ) ) ) {

                                                $export_fields[ "wpie_item_" . $new_key ] = $fielData;

                                                if ( $new_key == "product_shipping_class" ) {
                                                        $export_fields[ "wpie_item_product_shipping_class_logic" ] = "as_specified";
                                                }
                                        } else {
                                                $export_fields[ "wpie_item_set_taxonomy" ][ $new_key ] = 1;
                                                $export_fields[ "wpie_item_taxonomy" ][ $new_key ] = $fielData;
                                                if ( isset( $field_option[ 'hierarchical' ] ) && $field_option[ 'hierarchical' ] == 1 ) {
                                                        $export_fields[ "wpie_item_taxonomy_hierarchical_delim" ][ $new_key ] = ">";
                                                }
                                        }
                                } elseif ( $field_type == "wc-product-attr" ) {

                                        $attr_label = isset( $field_option[ 'name' ] ) ? $field_option[ 'name' ] : "";
                                        $attr_name = ! empty( $attr_label ) ? strtolower( preg_replace( '/[^a-z0-9_]/i', '', str_replace( [ '"', '&' ], [ "&quot;", "&amp;" ], $attr_label ) ) ) : "";

                                        $temp_attr_name = "{attributename" . $attr_name . "[1]}";

                                        if ( isset( $export_fields[ "wpie_attr_slug" ] ) && is_array( $export_fields[ "wpie_attr_slug" ] ) && ! empty( $export_fields[ "wpie_attr_slug" ] ) && in_array( $temp_attr_name, $export_fields[ "wpie_attr_slug" ] ) ) {

                                                $attr_count = 0;

                                                while ( in_array( $temp_attr_name, $export_fields[ "wpie_attr_slug" ] ) ) {
                                                        $attr_count ++;
                                                        $temp_attr_name = "{attributename" . $attr_name . "_" . $attr_count . "[1]}";
                                                }

                                                $attr_name = $attr_name . "_" . $attr_count;

                                                unset( $attr_count );
                                        }

                                        $export_fields[ "wpie_product_attr_name" ][] = $attr_label;
                                        $export_fields[ "wpie_attr_slug" ][] = "{attributename" . $attr_name . "[1]}";
                                        $export_fields[ "wpie_product_attr_value" ][] = "{attributevalue" . $attr_name . "[1]}";
                                        $export_fields[ "wpie_attr_in_variations" ][] = "{attributeinvariations" . $attr_name . "[1]}";
                                        $export_fields[ "wpie_attr_is_visible" ][] = "{attributeisvisible" . $attr_name . "[1]}";
                                        $export_fields[ "wpie_attr_is_taxonomy" ][] = "{attributeistaxonomy" . $attr_name . "[1]}";
                                        $export_fields[ "wpie_attr_is_auto_create_term" ][] = "yes";
                                        $export_fields[ "wpie_attr_position" ][] = "{attributeposition" . $attr_name . "[1]}";

                                        unset( $attr_name, $_attr_name );
                                        continue;
                                } elseif ( $new_key == "parent" ) {
                                        $export_fields[ "wpie_item_parent" ] = "{parentslug[1]}";
                                        $export_fields[ "wpie_item_parent_id" ] = "{parent[1]}";
                                } else {

                                        switch ( $new_key ) {
                                                case "author_email":
                                                        $export_fields[ "wpie_item_author" ] = $fielData;
                                                        break;
                                                case "term_parent_slug":
                                                        $export_fields[ "wpie_item_term_parent" ] = $fielData;
                                                        break;
                                                case "user_pass":
                                                        $export_fields[ "wpie_item_set_hashed_password" ] = 1;
                                                        break;

                                                case "image_title":
                                                case "image_caption":
                                                case "image_description":
                                                case "image_alt":
                                                        $export_fields[ "wpie_item_set_" . $new_key ] = 1;
                                                        break;
                                        }
                                        $export_fields[ "wpie_item_" . $new_key ] = $fielData;

                                        unset( $updated_key );
                                }

                                $configData[ $new_key ] = $fielData;

                                unset( $fielData, $field_option, $field_label, $field_type );
                        }
                        unset( $configData );
                }


                $config[ "fields" ] = $export_fields;

                $type = isset( $options[ 'wpie_export_file_type' ] ) && ! empty( $options[ 'wpie_export_file_type' ] ) ? $options[ 'wpie_export_file_type' ] : "csv";

                $fileName = isset( $options[ 'fileName' ] ) ? $options[ 'fileName' ] : "";

                if ( $type != "csv" && $fileName != "" ) {
                        $fileName = str_replace( ".csv", "." . $type, $fileName );
                }

                $config[ "fileName" ] = $fileName;

                $fileDir = isset( $options[ 'fileDir' ] ) ? $options[ 'fileDir' ] : "";

                $filePath = WPIE_UPLOAD_EXPORT_DIR . "/" . $fileDir . "/" . "config.json";

                file_put_contents( $filePath, json_encode( $config ) );

                unset( $config, $fields_data, $export_fields, $type, $fileName, $fileDir, $filePath );
        }

        private function get_acf_field_data( $field_id = "" ) {

                if ( empty( $field_id ) ) {
                        return;
                }

                $field = acf_get_field( $field_id );

                if ( ! is_array( $field ) || empty( $field ) ) {
                        return;
                }

                $data = array ();

                $type = isset( $field[ 'type' ] ) ? $field[ 'type' ] : "";

                $new_name = isset( $field[ 'label' ] ) && ! empty( $field[ 'label' ] ) ? strtolower( str_replace( ' ', '_', preg_replace( '/[^a-z0-9_]/i', '', $field[ 'label' ] ) ) ) : "";

                switch ( $type ) {
                        case "select":
                        case "checkbox":
                        case "radio":
                        case "button_group":
                        case "true_false":
                        case "taxonomy":
                        case 'repeater':
                        case 'flexible_content':
                        case 'clone':
                        case 'group':
                                $data = array (
                                        "value_option" => "custom",
                                        "custom_value" => "{" . $new_name . "[1]}",
                                        "type"         => $type
                                );
                                break;
                        case "image":
                        case "file":
                        case "gallery":
                                $data = array (
                                        "value"                => "{" . $new_name . "[1]}",
                                        "search_through_media" => "1",
                                        "use_upload_dir"       => "",
                                        "delim"                => ",",
                                        "type"                 => $type
                                );
                                break;
                        case "link":
                                $data = array (
                                        "value" => [
                                                "url"    => "{" . $new_name . "url[1]}",
                                                "title"  => "{" . $new_name . "title[1]}",
                                                "target" => "{" . $new_name . "target[1]}",
                                        ],
                                        "type"  => $type
                                );
                                break;
                        case "google_map":
                                $data = array (
                                        "value" => [
                                                "address" => "{" . $new_name . "address[1]}",
                                                "lat"     => "{" . $new_name . "lat[1]}",
                                                "lng"     => "{" . $new_name . "lng[1]}",
                                        ],
                                        "type"  => $type
                                );
                                break;
                        case "post_object":
                        case "page_link":
                        case "relationship":
                        case "user":
                                $data = array (
                                        "value" => "{" . $new_name . "[1]}",
                                        "delim" => ",",
                                        "type"  => $type
                                );
                                break;
                        default :
                                $data = array (
                                        "value" => "{" . $new_name . "[1]}",
                                        "type"  => $type
                                );

                                break;
                }

                return $data;
        }

        private function set_file_headers( $template_data = array () ) {

                $wpie_export_type = (isset( $template_data[ 'wpie_export_type' ] ) && trim( $template_data[ 'wpie_export_type' ] ) != "") ? array ( wpie_sanitize_field( $template_data[ 'wpie_export_type' ] ) ) : array ( "post" );

                $export_type = $this->get_export_type();

                $temp_wpie_export_type = $wpie_export_type[ 0 ];

                unset( $wpie_export_type );

                $exported_data = ( isset( $export_type[ $temp_wpie_export_type ] ) && ! empty( $export_type[ $temp_wpie_export_type ] ) ) ? $export_type[ $temp_wpie_export_type ] : "post";

                unset( $export_type );

                if ( $temp_wpie_export_type == "taxonomies" ) {

                        $taxonomy_data = $this->wpie_get_taxonomies();

                        $tax_temp_data = (isset( $template_data[ 'wpie_taxonomy_type' ] ) && trim( $template_data[ 'wpie_taxonomy_type' ] ) != "") ? wpie_sanitize_field( $template_data[ 'wpie_taxonomy_type' ] ) : "";

                        if ( ! empty( $tax_temp_data ) && isset( $taxonomy_data[ $tax_temp_data ] ) && ! empty( $taxonomy_data[ $tax_temp_data ] ) ) {
                                $exported_data = $taxonomy_data[ $tax_temp_data ];
                        }
                        unset( $tax_temp_data, $taxonomy_data );
                }
                unset( $temp_wpie_export_type );

                $filename = sanitize_file_name( (isset( $template_data[ 'wpie_export_file_name' ] ) && trim( $template_data[ 'wpie_export_file_name' ] ) != "") ? $template_data[ 'wpie_export_file_name' ] : $exported_data . ' Export ' . date( 'Y M d His' ) );

                $filename = apply_filters( 'wpie_export_file_name', $filename );

                $filename = pathinfo( $filename, PATHINFO_FILENAME ) . '.csv';

                $export_dir = $this->get_safe_dir_name( $filename );

                wp_mkdir_p( WPIE_UPLOAD_EXPORT_DIR . "/" . $export_dir );

                $filepath = WPIE_UPLOAD_EXPORT_DIR . '/' . $export_dir . '/' . $filename;

                $fh = @fopen( $filepath, 'w+' );

                $wpie_export_include_bom = (isset( $template_data[ 'wpie_export_include_bom' ] ) && trim( $template_data[ 'wpie_export_include_bom' ] ) != "") ? wpie_sanitize_field( $template_data[ 'wpie_export_include_bom' ] ) : "";

                if ( $wpie_export_include_bom == 1 ) {
                        fwrite( $fh, chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) );
                }

                fclose( $fh );

                unset( $exported_data, $filepath, $fh, $template_data, $wpie_export_include_bom );

                return array ( "filename" => $filename, "filedir" => $export_dir );
        }

        protected function init_export_process() {

                $return_value = array ( "status" => "error" );

                $export_id = isset( $_GET[ 'export_id' ] ) ? absint( wpie_sanitize_field( $_GET[ 'export_id' ] ) ) : 0;

                if ( $export_id > 0 ) {

                        $template = $this->get_template_by_id( $export_id );

                        if ( $template !== false ) {

                                $export_type = isset( $template->opration_type ) ? $template->opration_type : "post";

                                $process_log = $this->init_export( $export_type, "export", $template );

                                $return_value[ 'exported_records' ] = isset( $process_log[ 'exported' ] ) ? $process_log[ 'exported' ] : 0;

                                $total = isset( $process_log[ 'total' ] ) ? $process_log[ 'total' ] : 0;

                                unset( $export_type, $process_log );

                                if ( $return_value[ 'exported_records' ] >= $total ) {

                                        $return_value[ 'export_status' ] = 'completed';
                                } else {
                                        $return_value[ 'export_status' ] = 'processing';
                                }

                                unset( $total );

                                $return_value[ 'status' ] = 'success';
                        } else {
                                $return_value[ 'message' ] = __( 'Template not found', 'wp-import-export-lite' );
                        }
                        unset( $template );
                } else {
                        $return_value[ 'message' ] = __( 'Template not found', 'wp-import-export-lite' );
                }

                unset( $export_id );

                echo json_encode( $return_value );

                die();
        }

        protected function prepare_file() {

                $return_value = array ( "status" => "error" );

                $export_id = isset( $_GET[ 'export_id' ] ) ? absint( wpie_sanitize_field( $_GET[ 'export_id' ] ) ) : 0;

                $process = $this->process_export_file( $export_id );

                if ( is_wp_error( $process ) ) {
                        $return_value[ 'message' ] = $process->get_error_message();
                } else {
                        $return_value[ 'status' ] = 'success';
                }

                echo json_encode( $return_value );

                die();
        }

        protected function process_export_file( $export_id = "" ) {

                if ( $export_id > 0 ) {

                        $template = $this->get_template_by_id( $export_id );

                        if ( $template !== false ) {

                                $options = isset( $template->options ) ? maybe_unserialize( $template->options ) : array ();

                                $filename = isset( $options[ 'fileName' ] ) ? $options[ 'fileName' ] : "";

                                $fileDir = isset( $options[ 'fileDir' ] ) ? $options[ 'fileDir' ] : "";

                                $is_package = isset( $options[ 'is_package' ] ) ? intval( $options[ 'is_package' ] ) : 0;

                                $skip_empty_nodes = isset( $options[ 'wpie_skip_empty_nodes' ] ) ? intval( $options[ 'wpie_skip_empty_nodes' ] ) === 1 : false;

                                if ( $template->opration === "schedule_export" ) {
                                        $is_package = isset( $options[ 'is_migrate_package' ] ) ? intval( $options[ 'is_migrate_package' ] ) : 0;
                                }

                                $delim = isset( $options[ 'wpie_csv_field_separator' ] ) ? $options[ 'wpie_csv_field_separator' ] : ",";

                                $type = isset( $options[ 'wpie_export_file_type' ] ) && ! empty( $options[ 'wpie_export_file_type' ] ) ? $options[ 'wpie_export_file_type' ] : "csv";

                                $new_type = "";

                                if ( $is_package === 0 ) {

                                        if ( $type != "" || $type != "csv" ) {

                                                switch ( $type ) {

                                                        case "xml" :
                                                                $data = $this->csv2xml( $filename, $fileDir, $skip_empty_nodes );

                                                                break;
                                                        case "json" :
                                                                $data = $this->csv2json( $filename, $fileDir );
                                                                break;
                                                        case "xls" :
                                                        case "xlsx" :
                                                        case "ods" :
                                                                $data = $this->csv2excel( $filename, $fileDir, $type );
                                                                break;
                                                }

                                                if ( isset( $data ) && is_wp_error( $data ) ) {
                                                        return $data;
                                                }

                                                $new_type = $type;
                                        }
                                } else {

                                        $is_success = $this->create_zip( $options );

                                        if ( is_wp_error( $is_success ) ) {

                                                return $data;
                                        }

                                        unset( $is_success );

                                        $new_type = "zip";
                                }

                                if ( $new_type != "" ) {

                                        $options[ 'fileName' ] = str_replace( ".csv", "." . $new_type, $filename );

                                        global $wpdb;

                                        $wpdb->update( $wpdb->prefix . "wpie_template", array ( 'options' => maybe_serialize( $options ) ), array ( 'id' => $export_id ) );
                                }

                                $extra_copy_path = isset( $options[ 'extra_copy_path' ] ) && ! empty( $options[ 'extra_copy_path' ] ) ? ltrim( trailingslashit( sanitize_text_field( $options[ 'extra_copy_path' ] ) ), '/\\' ) : "";

                                if ( ! empty( $extra_copy_path ) && is_dir( WPIE_SITE_UPLOAD_DIR . "/" . $extra_copy_path ) ) {

                                        @copy( WPIE_UPLOAD_EXPORT_DIR . '/' . $fileDir . '/' . $options[ 'fileName' ], WPIE_SITE_UPLOAD_DIR . "/" . $extra_copy_path . $options[ 'fileName' ] );
                                }

                                unset( $options, $filename, $fileDir, $is_package, $delim, $type, $new_type );
                        } else {
                                return new \WP_Error( 'woo_import_export_error', __( 'Template not found', 'wp-import-export-lite' ) );
                        }
                        unset( $template );
                } else {
                        return new \WP_Error( 'woo_import_export_error', __( 'Template not found', 'wp-import-export-lite' ) );
                }

                return true;
        }

        protected function create_zip( $options = array () ) {

                $this->generate_config_file( $options );

                $zip = new \ZipArchive();

                $filename = isset( $options[ 'fileName' ] ) ? $options[ 'fileName' ] : "";

                $fileDir = isset( $options[ 'fileDir' ] ) ? $options[ 'fileDir' ] : "";

                $zipfile = WPIE_UPLOAD_EXPORT_DIR . "/" . $fileDir . "/" . str_replace( ".csv", ".zip", $filename );

                if ( $zip->open( $zipfile, \ZIPARCHIVE::CREATE ) != TRUE ) {

                        return new \WP_Error( 'woo_import_export_error', __( 'Could not open archive', 'wp-import-export-lite' ) );
                }

                unset( $zipfile );

                $zip->addFile( WPIE_UPLOAD_EXPORT_DIR . "/" . $fileDir . "/" . $filename, $filename );

                $zip->addFile( WPIE_UPLOAD_EXPORT_DIR . "/" . $fileDir . "/config.json", "config.json" );

                $zip->close();

                unset( $zip );

                return true;
        }

        private function csv2excel( $filename = "", $fileDir = "", $type = "xlsx" ) {

                $file = WPIE_UPLOAD_EXPORT_DIR . "/" . $fileDir . "/" . $filename;

                if ( ! file_exists( $file ) ) {
                        return new \WP_Error( 'wpie_import_error', __( 'File not found', 'wp-import-export-lite' ) );
                }

                if ( file_exists( WPIE_LIBRARIES_DIR . '/composer/vendor/autoload.php' ) ) {
                        require_once( WPIE_LIBRARIES_DIR . '/composer/vendor/autoload.php' );
                }

                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load( $file );

                unset( $reader, $file );

                if ( $type == "xls" ) {
                        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xls( $spreadsheet );
                } elseif ( $type == "ods" ) {
                        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Ods( $spreadsheet );
                } else {
                        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx( $spreadsheet );
                }
                $writer->save( WPIE_UPLOAD_EXPORT_DIR . "/" . $fileDir . "/" . str_replace( ".csv", "." . $type, $filename ) );

                $spreadsheet->disconnectWorksheets();

                unset( $writer, $spreadsheet );

                return true;
        }

        private function csv2json( $filename = "", $fileDir = "" ) {

                $file = WPIE_UPLOAD_EXPORT_DIR . "/" . $fileDir . "/" . $filename;

                if ( ! file_exists( $file ) ) {
                        return new \WP_Error( 'wpie_import_error', __( 'File not found', 'wp-import-export-lite' ) );
                }

                $csv = array ();

                if ( ($handle = fopen( $file, 'r' )) !== FALSE ) {
                        $i = 0;
                        while ( ($lineArray = fgetcsv( $handle, 4000, ",", '"' )) !== FALSE ) {
                                for ( $j = 0; $j < count( $lineArray ); $j ++ ) {
                                        $csv[ $i ][ $j ] = $lineArray[ $j ];
                                }
                                $i ++;
                        }
                        fclose( $handle );
                }

                unset( $file );

                array_walk( $csv, function( &$a ) use ( $csv ) {
                        $a = array_combine( $csv[ 0 ], $a );
                } );

                array_shift( $csv );

                file_put_contents( WPIE_UPLOAD_EXPORT_DIR . "/" . $fileDir . "/" . str_replace( ".csv", ".json", $filename ), json_encode( $csv ) );

                unset( $csv );

                return true;
        }

        private function csv2xml( $filename = "", $fileDir = "", $skip_empty_nodes = false ) {

                $file = WPIE_UPLOAD_EXPORT_DIR . "/" . $fileDir . "/" . $filename;

                if ( ! file_exists( $file ) ) {
                        return new \WP_Error( 'wpie_import_error', __( 'File not found', 'wp-import-export-lite' ) );
                }

                if ( file_exists( WPIE_LIBRARIES_DIR . '/xml/class-wpie-array2xml.php' ) ) {
                        require_once(WPIE_LIBRARIES_DIR . '/xml/class-wpie-array2xml.php');
                }

                $converter = new \wpie\lib\xml\array2xml\ArrayToXml();

                $converter->create_root( "wpiedata" );

                if ( $skip_empty_nodes ) {
                        $converter->skip_empty();
                }

                $headers = array ();

                $wfp = fopen( $file, "rb" );

                unset( $file );

                while ( ($keys = fgetcsv( $wfp, 0 )) !== false ) {

                        if ( empty( $headers ) ) {

                                foreach ( $keys as $key => $value ) {

                                        $value = trim( strtolower( preg_replace( '/[^a-z0-9_]/i', '', $value ) ) );

                                        if ( preg_match( '/^[0-9]{1}/', $value ) ) {
                                                $value = 'el_' . trim( strtolower( $value ) );
                                        }

                                        $value = ( ! empty( $value )) ? $value : 'undefined' . $key;

                                        if ( isset( $headers[ $key ] ) ) {
                                                $key = $this->unique_array_key_name( $key, $headers );
                                        }

                                        $headers[ $key ] = $value;
                                }

                                continue;
                        }

                        $fileData = array ();

                        foreach ( $keys as $key => $value ) {

                                $header = isset( $headers[ $key ] ) ? $headers[ $key ] : "";

                                if ( ! empty( $header ) ) {

                                        if ( isset( $fileData[ $header ] ) ) {
                                                $header = $this->unique_array_key_name( $header, $fileData );
                                        }

                                        $fileData[ $header ] = $value;
                                }
                                unset( $header );
                        }

                        $converter->addNode( $converter->root, "item", $fileData, 0 );

                        unset( $fileData );
                }

                $converter->saveFile( WPIE_UPLOAD_EXPORT_DIR . "/" . $fileDir . "/" . str_replace( ".csv", ".xml", $filename ) );

                unset( $converter, $headers );

                return true;
        }

        protected function get_item_count() {

                $export_type = isset( $_POST[ 'wpie_export_type' ] ) ? wpie_sanitize_field( $_POST[ 'wpie_export_type' ] ) : "post";

                $return_value = array ();

                $return_value[ "totalRecords" ] = $this->init_export( $export_type, "count", $_POST );

                unset( $export_type );

                $return_value[ 'status' ] = 'success';

                echo json_encode( $return_value );

                die();
        }

        protected function get_preview() {

                $export_type = isset( $_POST[ 'wpie_export_type' ] ) ? wpie_sanitize_field( $_POST[ 'wpie_export_type' ] ) : "post";

                $return_value = array ();

                $_POST[ 'wpie_records_per_iteration' ] = isset( $_POST[ 'length' ] ) ? absint( wpie_sanitize_field( $_POST[ 'length' ] ) ) : 10;

                $return_value[ 'data' ] = $this->init_export( $export_type, "preview", $_POST );

                unset( $export_type );

                $return_value[ 'recordsTotal' ] = isset( $_POST[ 'total' ] ) ? absint( $_POST[ 'total' ] ) : 0;

                $return_value[ 'recordsFiltered' ] = $return_value[ 'recordsTotal' ];

                $return_value[ 'status' ] = 'success';

                echo json_encode( $return_value );

                die();
        }

        private function unique_array_key_name( $key = "", $array = array () ) {

                $count = 1;

                $new_key = $key;

                while ( isset( $array[ $key ] ) ) {

                        $key = $new_key . "_" . $count;
                        $count ++;
                }

                unset( $count, $new_key );

                return $key;
        }

        public function __destruct() {
                foreach ( $this as $key => $value ) {
                        unset( $this->$key );
                }
        }

}
