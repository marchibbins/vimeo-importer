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
class Vimeo_Importer_Api {

	/**
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 * @since   1.0.0
	 *
	 * @var     string
	 */
	const VERSION = '1.0.0';

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Only support a limited number of endpoints, with specific methods.
	 *
	 * @since    1.0.0
	 *
	 * @var      array
	 */
	protected static $endpoints = array(
		'albums' => array(
			'method' => 'GET',
			'url' => '/me/albums'
		),
		'videos' => array(
			'method' => 'GET',
			'url' => '/me/videos'
		)
	);

    private $_endpoint = null;
    private $_params = null;
    private $_options = null;
    private $_vimeo = null;

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}


	/**
	 * Make it so.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {

		$this->check_endpoint();
		$this->check_configuration();

		$this->init_vimeo();

		$this->get_params();
		$this->get_data();

	}

	/**
	 * Check that endpoint is specified and allowed.
	 *
	 * @since     1.0.0
	 */
	private function check_endpoint() {

		if ( !isset( $_REQUEST['endpoint'] ) ) {
			$this->return_json(
				array(
					'body' => array( 'error' => 'Missing endpoint' ),
					'status' => 400
				)
			);
		}

		$this->_endpoint = $_REQUEST['endpoint'];

		if ( !isset( self::$endpoints[$this->_endpoint] ) ) {
			$this->return_json(
				array(
					'body' => array( 'error' => 'Bad endpoint' ),
					'status' => 400
				)
			);
		}

		$this->_endpoint = self::$endpoints[$this->_endpoint];

		if ( $this->_endpoint['method'] !== $_SERVER['REQUEST_METHOD'] ) {
			$this->return_json(
				array(
					'body' => array( 'error' => 'Method not allowed for endpoint' ),
					'status' => 405
				)
			);
		}

	}

	/**
	 * Check that Wordpress and Vimeo settings are configured.
	 *
	 * @since     1.0.0
	 */
	private function check_configuration() {

		include_once( '../../../../wp-load.php' );

		$this->_options = get_option( 'Vimeo_Importer' );

		if ( !$this->_options ) {
			$this->return_json(
				array(
					'body' => array( 'error' => 'Plugin options not configured' ),
					'status' => 401
				)
			);
		}

		if ( !$this->_options['app_id'] ) {
			$this->return_json(
				array(
					'body' => array( 'error' => 'Application ID not configured' ),
					'status' => 401
				)
			);
		}

		if ( !$this->_options['app_secret'] ) {
			$this->return_json(
				array(
					'body' => array( 'error' => 'Application secret not configured' ),
					'status' => 401
				)
			);
		}

		if ( !$this->_options['access_token'] ) {
			$this->return_json(
				array(
					'body' => array( 'error' => 'Access token not configured' ),
					'status' => 401
				)
			);
		}

	}

	/**
	 * Initialise Vimeo library.
	 *
	 * @since     1.0.0
	 */
	private function init_vimeo() {

		include_once( 'includes/vimeo.php' );

		$APP_ID = $this->_options['app_id'];
		$APP_SECRET = $this->_options['app_secret'];
		$ACCESS_TOKEN = $this->_options['access_token'];

		$this->_vimeo = new Vimeo( $APP_ID, $APP_SECRET, $ACCESS_TOKEN );

	}

	/**
	 * Create querystring from REQUEST params, excluding endpoint.
	 *
	 * @since     1.0.0
	 */
	private function get_params() {

		$this->_params = '?';

		foreach ( $_REQUEST as $key => $value ) {
			if ( $key !== 'endpoint' ) {
				$this->_params .= $key . '=' . $value . '&';
			}
		}

	}

	/**
	 * Perform Vimeo API request.
	 *
	 * @since     1.0.0
	 */
	private function get_data() {

		$url = $this->_endpoint['url'] . $this->_params;
		$data = $this->_vimeo->request( $url );

		$this->return_json( $data );

	}

	/**
	 * Set Content type and encode data as JSON.
	 *
	 * @since     1.0.0
	 *
	 * @return    object    A single instance of this class.
	 */
	private function return_json ( $data ) {

		header( 'Content-Type: application/json' );
		echo json_encode( $data );

		die;

	}

}

Vimeo_Importer_Api::get_instance();
