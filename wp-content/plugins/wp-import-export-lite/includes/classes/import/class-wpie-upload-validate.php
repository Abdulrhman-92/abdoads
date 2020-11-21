<?php

namespace wpie\import\upload\validate;

use wpie\import\chunk\csv;
use wpie\lib\xml\array2xml;
use PhpOffice\PhpSpreadsheet\Reader;
use PhpOffice\PhpSpreadsheet\Writer;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
        die( __( "Can't load this file directly", 'wp-import-export-lite' ) );
}

class WPIE_Upload_Validate {

        private $wpie_fileName = "wpie-import-data-";

        public function __construct() {
                
        }

        public function wpie_parse_upload_data( $template_data = null, $wpie_csv_delimiter = ",", $activeFile = false, $wpie_import_id = false ) {

                if ( empty( $template_data ) ) {
                        return false;
                }

                global $wpdb;

                if ( is_array( $template_data ) ) {
                        $template_options = $template_data;
                } else {
                        $template_options = isset( $template_data->options ) ? maybe_unserialize( $template_data->options ) : array ();
                }

                $importFile = isset( $template_options[ 'importFile' ] ) ? $template_options[ 'importFile' ] : array ();

                if ( $activeFile === false ) {
                        $activeFile = isset( $_GET[ 'activeFile' ] ) ? wpie_sanitize_field( $_GET[ 'activeFile' ] ) : "";
                }
                if ( $wpie_import_id === false ) {
                        $wpie_import_id = isset( $_GET[ "wpie_import_id" ] ) ? intval( wpie_sanitize_field( $_GET[ "wpie_import_id" ] ) ) : 0;
                }

                $is_new_request = isset( $_GET[ "is_new_request" ] ) ? intval( wpie_sanitize_field( $_GET[ "is_new_request" ] ) ) : 0;

                $fileData = isset( $importFile[ $activeFile ] ) ? $importFile[ $activeFile ] : array ();

                $file_path = isset( $fileData[ 'fileDir' ] ) ? wpie_sanitize_field( $fileData[ 'fileDir' ] ) : "";

                $file_name = isset( $fileData[ 'fileName' ] ) ? wpie_sanitize_field( $fileData[ 'fileName' ] ) : "";

                $baseDir = isset( $fileData[ 'baseDir' ] ) ? wpie_sanitize_field( $fileData[ 'baseDir' ] ) : "";

                $template_options[ 'activeFile' ] = $activeFile;

                if ( is_dir( WPIE_UPLOAD_IMPORT_DIR . "/" . $baseDir . "/parse/" ) ) {
                        $this->wpie_remove_old_files( WPIE_UPLOAD_IMPORT_DIR . "/" . $baseDir . "/parse/" );
                }

                $wpdb->update( $wpdb->prefix . "wpie_template", array ( "options" => maybe_serialize( $template_options ) ), array ( 'id' => $wpie_import_id ) );

                $file = WPIE_UPLOAD_IMPORT_DIR . "/" . $file_path . "/" . $file_name;

                if ( ! file_exists( $file ) ) {

                        unset( $template_options, $importFile, $activeFile, $file, $wpie_import_id, $fileData, $file_path, $file_name, $baseDir );

                        return new \WP_Error( 'wpie_import_error', __( 'File not found', 'wp-import-export-lite' ) );
                } elseif ( preg_match( '%\W(xls|xlsx|ods)$%i', trim( $file_name ) ) ) {

                        unset( $template_options, $importFile, $activeFile, $file, $wpie_import_id, $fileData );

                        return $this->wpie_convert_excel_2_csv( $file_path, $file_name, $baseDir );
                } elseif ( preg_match( '%\W(csv)$%i', trim( $file_name ) ) ) {

                        unset( $template_options, $importFile, $activeFile, $file, $wpie_import_id, $fileData );

                        return $this->wpie_convert_csv_2_xml( $file_path, $file_name, $baseDir, $wpie_csv_delimiter, $is_new_request );
                } elseif ( preg_match( '%\W(txt|json)$%i', trim( $file_name ) ) ) {

                        unset( $template_options, $importFile, $activeFile, $file, $wpie_import_id, $fileData );

                        return $this->wpie_convert_json_2_xml( $file_path, $file_name, $baseDir );
                } elseif ( preg_match( '%\W(xml)$%i', trim( $file_name ) ) ) {

                        copy( $file, WPIE_UPLOAD_IMPORT_DIR . "/" . $baseDir . "/parse/" . $this->wpie_fileName . "1.xml" );

                        unset( $template_options, $importFile, $activeFile, $file, $wpie_import_id, $fileData, $file_path, $file_name, $baseDir );

                        return true;
                }

                unset( $template_options, $importFile, $activeFile, $file, $wpie_import_id, $fileData, $file_path, $file_name, $baseDir );

                return new \WP_Error( 'wpie_import_error', __( 'Invalid File to parse. Please Choose other FIle', 'wp-import-export-lite' ) );
        }

        private function wpie_convert_excel_2_csv( $fileDir = "", $file_name = "", $baseDir = "" ) {

                $file = WPIE_UPLOAD_IMPORT_DIR . "/" . $fileDir . "/" . $file_name;

                if ( ! file_exists( $file ) ) {
                        return new \WP_Error( 'wpie_import_error', __( 'File not found', 'wp-import-export-lite' ) );
                }

                $newFileName = wp_unique_filename( WPIE_UPLOAD_IMPORT_DIR . "/" . $fileDir, preg_replace( '%\W(xls|xlsx|ods)$%i', ".csv", $file_name ) );

                if ( file_exists( WPIE_LIBRARIES_DIR . '/composer/vendor/autoload.php' ) ) {
                        require_once( WPIE_LIBRARIES_DIR . '/composer/vendor/autoload.php' );
                }

                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load( $file );

                $writer = new \PhpOffice\PhpSpreadsheet\Writer\Csv( $spreadsheet );

                $writer->save( WPIE_UPLOAD_IMPORT_DIR . "/" . $fileDir . "/" . preg_replace( '%\W(xls|xlsx|ods)$%i', ".csv", $file_name ) );

                $spreadsheet->disconnectWorksheets();

                $return_data = $this->wpie_convert_csv_2_xml( $fileDir, $newFileName, $baseDir );

                unset( $file, $newFileName, $reader, $spreadsheet, $writer );

                return $return_data;
        }

        private function wpie_convert_csv_2_xml( $fileDir = "", $file_name = "", $baseDir = "", $wpie_csv_delimiter = ",", $is_new_request = 0 ) {

                if ( file_exists( WPIE_IMPORT_CLASSES_DIR . '/class-wpie-csv-chunk.php' ) ) {
                        require_once(WPIE_IMPORT_CLASSES_DIR . '/class-wpie-csv-chunk.php');
                }

                $csv_chunk = new \wpie\import\chunk\csv\WPIE_CSV_Chunk();

                $return_data = $csv_chunk->process_csv( $fileDir, $file_name, $baseDir, $wpie_csv_delimiter, $this->wpie_fileName, $is_new_request );

                unset( $csv_chunk );

                return $return_data;
        }

        private function wpie_convert_json_2_xml( $fileDir = "", $file_name = "", $baseDir = "" ) {

                $file = WPIE_UPLOAD_IMPORT_DIR . "/" . $fileDir . "/" . $file_name;

                if ( ! file_exists( $file ) ) {
                        return false;
                }

                $json = file_get_contents( $file );

                $file_data = json_decode( $json, true );

                $fileName = $this->wpie_fileName . '1.xml';

                $xmlFilePath = WPIE_UPLOAD_IMPORT_DIR . "/" . $baseDir . "/parse/";

                $xmlfileName = wp_unique_filename( $xmlFilePath, $fileName );

                if ( file_exists( WPIE_LIBRARIES_DIR . '/xml/class-wpie-array2xml.php' ) ) {
                        require_once(WPIE_LIBRARIES_DIR . '/xml/class-wpie-array2xml.php');
                }

                $converter = new \wpie\lib\xml\array2xml\ArrayToXml();

                $converter->create_root( "wpiedata" );

                $converter->convertElement( $converter->root, $file_data, 0 );

                $converter->saveFile( $xmlFilePath . "/" . $xmlfileName );

                unset( $file, $json, $file_data, $fileName, $converter );

                return $xmlFilePath . "/" . $xmlfileName;
        }

        private function wpie_remove_old_files( $targetDir = "" ) {

                $cdir = scandir( $targetDir );

                if ( is_array( $cdir ) && ! empty( $cdir ) ) {
                        foreach ( $cdir as $key => $value ) {
                                if ( ! in_array( $value, array ( ".", ".." ) ) ) {
                                        if ( is_dir( $targetDir . '/' . $value ) ) {
                                                $this->wpie_remove_old_files( $targetDir . '/' . $value );
                                        } else {
                                                unlink( $targetDir . '/' . $value );
                                        }
                                }
                        }
                }
                unset( $cdir );
        }

        public function __destruct() {
                foreach ( $this as $key => $value ) {
                        unset( $this->$key );
                }
        }

}
