<?php
/**
 * Plugin Name.
 *
 * @package   Vimeo_Importer_Api
 * @author    Marc Hibbins <marc@marchibbins.com>
 * @license   GPL-2.0+
 * @link      http://marchibbins.com
 */

/**
 * Api class. This class uses the Vimeo PHP library
 * to handle calls to the Vimeo API with OAuth 2.0.
 *
 * @package Vimeo_Importer_Api
 * @author  Marc Hibbins <marc@marchibbins.com>
 */

function return_json ($data ) {
    header( 'Content-Type: application/json' );
    echo json_encode( $data );
    die;
}

// Match only a few endpoints
$endpoints = array(
    'albums' => '/me/albums',
    'videos' => '/me/videos'
);

if ( !isset( $_GET['endpoint'] ) ) {
    return_json( array( 'body' => array( 'error' => 'Missing endpoint' ), 'status' => 400 ) );
}

if ( !isset( $endpoints[$_GET['endpoint']] ) ) {
    return_json( array( 'body' => array( 'error' => 'Bad endpoint' ), 'status' => 400 ) );
}

$endpoint = $endpoints[$_GET['endpoint']] . '?';

unset( $_GET['endpoint'] );
foreach ( $_GET as $key => $value ) {
    $endpoint .= $key . '=' . $value . '&';
}

// Init WP
include_once( '../../../../wp-load.php' );
$options = get_option( 'Vimeo_Importer' );

// Check configuration
if ( !$options || !$options['app_id'] ) {
    return_json( array( 'body' => array( 'error' => 'Application ID not configured' ), 'status' => 401 ) );
}
 elseif ( !$options['app_secret'] ) {
    return_json( array( 'body' => array( 'error' => 'Application secret not configured' ), 'status' => 401 ) );
}
 elseif ( !$options['access_token'] ) {
    return_json( array( 'body' => array( 'error' => 'Access token not configured' ), 'status' => 401 ) );
}

// Init Vimeo
include_once( 'includes/vimeo.php' );

$APP_ID = $options['app_id'];
$APP_SECRET = $options['app_secret'];
$ACCESS_TOKEN = $options['access_token'];

$vimeo = new Vimeo( $APP_ID, $APP_SECRET, $ACCESS_TOKEN );

// Dump data as JSON
$data = $vimeo->request( $endpoint );
return_json( $data );
