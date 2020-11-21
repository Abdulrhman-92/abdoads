<?php

namespace wpie\import\Downloader;

if ( ! defined( 'ABSPATH' ) ) {
        die( __( "Can't load this file directly", 'wp-import-export-lite' ) );
}

class Download {

        private $url = "";
        private $sslverify = false;
        private $redirection = 5;
        private $timeout = 3000;

        public function __construct() {
                
        }

        public function download_file( $url = "" ) {

                $this->url = $url;

                if ( empty( $this->url ) ) {
                        return new \WP_Error( 'wpie_import_error', __( 'File Download Error : File URL is empty', 'wp-import-export-lite' ) );
                }

                $wp_file = $this->wp_download();

                if ( is_wp_error( $wp_file ) ) {

                        $curl_file = $this->curl_download();

                        if ( ! is_wp_error( $curl_file ) ) {
                                $wp_file = $curl_file;
                        }
                }

                return $wp_file;
        }

        private function curl_download() {

                $ch = curl_init( $this->url );

                curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );

                curl_setopt( $ch, CURLOPT_HEADER, true );

                curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );

                curl_setopt( $ch, CURLOPT_MAXREDIRS, $this->redirection );

                curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );

                curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, $this->sslverify );

                $url_data = parse_url( $this->url );

                if ( ! ( empty( $url_data[ 'user' ] ) || empty( $url_data[ 'pass' ] )) ) {

                        curl_setopt( $ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY );

                        curl_setopt( $ch, CURLOPT_USERPWD, $url_data[ 'user' ] . ":" . $url_data[ 'pass' ] );

                        $this->url = $url_data[ 'scheme' ] . '://' . $url_data[ 'host' ];

                        if ( ! empty( $url_data[ 'port' ] ) ) {
                                $this->url .= ':' . $url_data[ 'port' ];
                        }

                        $this->url .= $url_data[ 'path' ];
                        if ( ! empty( $url_data[ 'query' ] ) ) {
                                $this->url .= '?' . $url_data[ 'query' ];
                        }
                        curl_setopt( $ch, CURLOPT_URL, $this->url );
                }

                $rawdata = curl_exec( $ch );

                $http_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );

                curl_close( $ch );

                if ( $http_code !== 200 ) {
                        return new \WP_Error( 'download_error', __( "File Download Error : Invalid Status Code", 'wp-import-export-lite' ) );
                }
                if ( empty( $rawdata ) ) {
                        return new \WP_Error( 'download_error', __( "File Download Error : File is empty", 'wp-import-export-lite' ) );
                }

                $filename = time() . rand() . ".tmp";

                $file = get_temp_dir() . $filename;

                if ( ! file_put_contents( $file, $rawdata ) ) {
                        $fp = fopen( $file, 'w' );
                        fwrite( $fp, $rawdata );
                        fclose( $fp );
                }

                return $file;
        }

        private function wp_download() {

                $filename = time() . rand() . ".tmp";

                $file = get_temp_dir() . $filename;

                $response = wp_safe_remote_get( $this->url, [ 'timeout' => $this->timeout, 'stream' => true, 'filename' => $file ] );

                if ( is_wp_error( $response ) ) {

                        if ( file_exists( $file ) ) {
                                unlink( $file );
                        }
                        return $response;
                }

                if ( 200 != wp_remote_retrieve_response_code( $response ) ) {

                        if ( file_exists( $file ) ) {
                                unlink( $file );
                        }
                        return new \WP_Error( 'http_404', trim( wp_remote_retrieve_response_message( $response ) ) );
                }

                $content_md5 = wp_remote_retrieve_header( $response, 'content-md5' );

                if ( $content_md5 ) {

                        $md5_check = verify_file_md5( $file, $content_md5 );

                        if ( is_wp_error( $md5_check ) ) {

                                if ( file_exists( $file ) ) {
                                        unlink( $file );
                                }
                                return $md5_check;
                        }

                        unset( $md5_check );
                }


                return $file;
        }

}
