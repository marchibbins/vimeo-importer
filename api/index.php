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

// Match only a few endpoints
$endpoints = array(
    'albums' => '/me/albums',
    'videos' => '/me/videos'
);

if ( !isset( $_GET['endpoint'] ) || !isset( $endpoints[$_GET['endpoint']] ) ) {
    die;
}

$endpoint = $endpoints[$_GET['endpoint']] . '?';

unset( $_GET['endpoint'] );
foreach ( $_GET as $key => $value ) {
    $endpoint .= $key . '=' . $value . '&';
}

// Init Vimeo
include_once( 'includes/vimeo.php' );
include_once( '../../../../wp-load.php' );

$options = get_option( 'Vimeo_Importer' );

if ( !$options || !$options['app_id'] ) {
    print 'app_id';
    die;
}
 elseif ( !$options['app_secret'] ) {
    print 'app_secret';
    die;
}
 elseif ( !$options['access_token'] ) {
    print 'access_token';
    die;
}

$APP_ID = $options['app_id'];
$APP_SECRET = $options['app_secret'];
$ACCESS_TOKEN = $options['access_token'];

$vimeo = new Vimeo( $APP_ID, $APP_SECRET, $ACCESS_TOKEN );

// Dump data as JSON
$data = $vimeo->request( $endpoint );

header( 'Content-Type: application/json' );
echo json_encode( $data );
