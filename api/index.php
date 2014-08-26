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
	 */
	const VERSION = '1.0.0';

	/**
	 * Instance of this class.
	 */
	protected static $instance = null;

	/**
	 * Only support a limited number of endpoints, with specific methods.
	 */
	protected static $endpoints = array(
		'albums' => array(
			'method' => 'GET',
			'resource' => 'vimeo',
			'fields' => 'uri,name,metadata',
			'url' => '/me/albums'
		),
		'videos' => array(
			'method' => 'GET',
			'resource' => 'vimeo',
			'fields' => 'uri,name,description,pictures',
			'url' => '/me/videos'
		),
		'import_albums' => array(
			'method' => 'POST',
			'resource' => 'wordpress',
			'url' => null
		),
		'import_videos' => array(
			'method' => 'POST',
			'resource' => 'wordpress',
			'url' => null
		)
	);

	private $_endpoint = null;
	private $_params = null;
	private $_options = null;
	private $_vimeo = null;

	/**
	 * Return an instance of this class.
	 */
	public static function get_instance () {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}


	/**
	 * Make it so.
	 */
	private function __construct () {

		$this->init_wordpress();

		$this->check_endpoint();
		$this->check_configuration();

		$this->init_vimeo();

		$this->get_params();

		$data = $this->get_data();
		$this->return_json( $data );

	}

	/**
	 * Check that endpoint is specified and allowed.
	 */
	private function check_endpoint () {

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

		if ( self::$endpoints[$this->_endpoint]['method'] !== $_SERVER['REQUEST_METHOD'] ) {
			$this->return_json(
				array(
					'body' => array( 'error' => 'Method not allowed for endpoint' ),
					'status' => 405
				)
			);
		}

	}

	/**
	 * Load Wordpress hooks, etc. Presumably this isn't the right way.
	 */
	private function init_wordpress () {

		require_once( '../../../../wp-load.php' );
		require_once( '../../../../wp-admin/includes/image.php' );

	}

	/**
	 * Check that Wordpress and Vimeo settings are configured.
	 */
	private function check_configuration () {

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
	 */
	private function init_vimeo () {

		include_once( 'includes/vimeo.php' );

		$APP_ID = $this->_options['app_id'];
		$APP_SECRET = $this->_options['app_secret'];
		$ACCESS_TOKEN = $this->_options['access_token'];

		$this->_vimeo = new Vimeo( $APP_ID, $APP_SECRET, $ACCESS_TOKEN );

	}

	/**
	 * Create querystring from REQUEST params, excluding endpoint.
	 */
	private function get_params () {

		$this->_params = '?';

		foreach ( $_REQUEST as $key => $value ) {
			if ( $key !== 'endpoint' ) {
				$this->_params .= $key . '=' . $value . '&';
			}
		}

		if ( isset( self::$endpoints[$this->_endpoint]['fields'] ) ) {
			$this->_params .= 'fields=' . self::$endpoints[$this->_endpoint]['fields'];
		}

	}

	/**
	 * Perform the request.
	 */
	private function get_data () {

		if ( self::$endpoints[$this->_endpoint]['resource'] === 'vimeo' ) {

			// Requests for Vimeo API
			$url = self::$endpoints[$this->_endpoint]['url'] . $this->_params;
			$data = $this->_vimeo->request( $url );

			return $data;

		} else {

			// Requests for Wordpress
			switch ( $this->_endpoint ) {

				case 'import_videos':
					$data = array(
						'body' => array( 'data' => $this->import_videos() ),
						'status' => 200
					);
					break;

				case 'import_albums':
					$data = array(
						'body' => array( 'data' => $this->import_albums() ),
						'status' => 200
					);
					break;

				default:
					$data = array(
						'body' => array( 'error' => 'Not implemented' ),
						'status' => 501
					);
					break;
			}

			return $data;

		}

	}

	/**
	 * Set Content type and encode data as JSON.
	 */
	private function return_json ( $data ) {

		header( 'Content-Type: application/json' );
		echo json_encode( $data );

		die;

	}

	/**
	 * Retrieve and attempt import all videos for an album.
	 */
	private function import_albums () {

		$response = array();

		if ( !isset ( $_POST['album'] ) ) {

			return array(
				'message' => 'No videos imported'
			);

		}

		// Direct album URI
		$url = '/albums/' . (int) $_POST['album'] . '/videos';

		// Reuse minimum fields for videos
		$url .= '?fields=' . self::$endpoints['videos']['fields'];

		$data = $this->_vimeo->request( $url );

		if ( isset($data['body']['data'] ) ) {

			$videos = array();

			foreach ( $data['body']['data'] as $video ) {

				$uri = explode('/', $video['uri']);
				$picture_uri = explode('/', $video['pictures']['uri']);

				// Store required data
				array_push( $videos, array(
					'dsv_vimeo_id' => $uri[2],
					'post_title' => $video['name'],
					'post_content' => $video['description'],
					'dsv_vimeo_holdingframe_url' => 'http://i.vimeocdn.com/video/' . $picture_uri[4] . '.jpg',
					'dsv_vimeo_link' => 'vimeo.com/' . $uri[2]
				) );

			}

			return $this->import_videos( $videos );

		} else {

			return array(
				'message' => 'Could not import album'
			);

		}

	}

	/**
	 * Create video posts or report if already imported.
	 */
	private function import_videos ( $videos = null ) {

		$response = array();

		if ( !$videos && !isset ( $_POST['videos'] ) ) {

			return array(
				'message' => 'No videos imported'
			);

		}

		if ( !$videos ) {
			$videos = $_POST['videos'];
		}

		foreach ( $videos as $video ) {

			// Check whether video already exists
			$id_query = new WP_Query( array(
				'post_type' => 'dsv_video',
				'meta_key' => 'dsv_vimeo_id',
				'meta_value' => $video['dsv_vimeo_id']
			) );

			if ( $id_query->post_count === 0 ) {

				$post = $this->create_video_post($video);

				// Video created
				array_push( $response, array(
					'id' => $post['id'],
					'image' => $post['image'],
					'status' => 'created'
				) );

			} else {

				// Video not created
				array_push( $response, array(
					'id' => $id_query->posts[0]->ID,
					'status' => 'exists'
				) );

			}

		}

		return $response;

	}

	/**
	 * Inserts Video CPT from data.
	 */
	private function create_video_post ( $obj ) {

		// Basic CPT object
		$post_id = wp_insert_post( array(
			'post_type' => 'dsv_video',
			'post_status' => 'publish',
			'post_title' => $obj['post_title'],
			'post_content' => $obj['post_content']
		) );

		// Image
		$image = $this->create_image_post( $obj['dsv_vimeo_holdingframe_url'], $post_id );
		if ( $image['message'] === 'success' ) {
			set_post_thumbnail( $post_id, $image['id'] );
		}

		// Custom fields, add anything POSTed that starts with 'dsv_'
		foreach ( $obj as $key => $value ) {
			if ( strpos( $key, 'dsv_' ) > -1) {
				add_post_meta( $post_id, $key, $value );
			}
		}

		return array(
			'id' => $post_id,
			'image' => $image
		);

	}

	/**
	 * Inserts media attachment with video holding image.
	 */
	private function create_image_post ( $image_url, $post_id ) {

		$uploads = wp_upload_dir();
		$filename = wp_unique_filename( $uploads['path'], basename($image_url) );

		$wp_filetype = wp_check_filetype( $filename, null );
		$full_path = $uploads['path'] . '/' . $filename;

		try {

			if ( !substr_count($wp_filetype['type'], 'image') ) {
				throw new Exception( '"' . basename($image_url) . '" is not a valid image. ' . $wp_filetype['type'] );
			}

			$image_string = $this->fetch_image($image_url);

			$file_saved = file_put_contents($full_path, $image_string);
			if ( !$file_saved ) {
				throw new Exception('The file cannot be saved.');
			}

			$attachment = array(
				'post_mime_type' => $wp_filetype['type'],
				'post_title' => preg_replace('/\.[^.]+$/', '', $filename),
				'post_status' => 'inherit',
				'guid' => $uploads['url'] . '/' . $filename
			);

			$attach_id = wp_insert_attachment( $attachment, $full_path, $post_id );
			if ( !$attach_id ) {
				throw new Exception('Failed to save record into database.');
			}

			$attach_data = wp_generate_attachment_metadata( $attach_id, $full_path );
			wp_update_attachment_metadata( $attach_id,  $attach_data );

			return array(
				'id' => $attach_id,
				'message' => 'success'
			);

		} catch (Exception $e) {

			return array(
				'message' => $e->getMessage()
			);

		}

	}

	/**
	 * Fetch Image from URL with Curl.
	 */
	private function fetch_image ( $url ) {

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		$image = curl_exec($curl);
		curl_close($curl);

		return $image;

	}

}

Vimeo_Importer_Api::get_instance();
