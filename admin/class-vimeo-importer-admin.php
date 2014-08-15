<?php
/**
 * Plugin Name.
 *
 * @package   Vimeo_Importer_Admin
 * @author    Marc Hibbins <marc@marchibbins.com>
 * @license   GPL-2.0+
 * @link      http://marchibbins.com
 */

/**
 * Plugin class. This class should ideally be used to work with the
 * administrative side of the WordPress site.
 *
 * @package Vimeo_Importer_Admin
 * @author  Marc Hibbins <marc@marchibbins.com>
 */
class Vimeo_Importer_Admin {
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
	 * Unique identifier for your plugin.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_slug = 'vimeo-importer';

	/**
	 * Slug of the plugin screen.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_screen_hook_suffix = null;

	/**
	 * Initialize the plugin by loading admin scripts & styles and adding a
	 * settings page and menu.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {

		// Load admin style sheet and JavaScript.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

		// Add the options page and menu item.
		add_action( 'admin_menu', array( $this, 'add_plugin_admin_menu' ) );

		// Add an action link pointing to the options page.
		$plugin_basename = plugin_basename( plugin_dir_path( realpath( dirname( __FILE__ ) ) ) . $this->plugin_slug . '.php' );
		add_filter( 'plugin_action_links_' . $plugin_basename, array( $this, 'add_action_links' ) );

		/*
		 * Define custom functionality.
		 *
		 * Read more about actions and filters:
		 * http://codex.wordpress.org/Plugin_API#Hooks.2C_Actions_and_Filters
		 */

		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );

		add_action( 'admin_head', array( $this, 'admin_head' ) );

	}

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
	 * Register and enqueue admin-specific style sheet.
	 *
	 * @since     1.0.0
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_styles() {

		if ( ! isset( $this->plugin_screen_hook_suffix ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( $this->plugin_screen_hook_suffix == $screen->id ) {
			wp_enqueue_style( $this->plugin_slug .'-admin-styles', plugins_url( 'assets/css/admin.css', __FILE__ ), array(), self::VERSION );
		}

	}

	/**
	 * Register and enqueue admin-specific JavaScript.
	 *
	 * @since     1.0.0
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_scripts() {

		if ( ! isset( $this->plugin_screen_hook_suffix ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( $this->plugin_screen_hook_suffix == $screen->id ) {
			wp_enqueue_script( $this->plugin_slug . '-admin-script', plugins_url( 'assets/js/admin.js', __FILE__ ), array( 'jquery' ), self::VERSION );
		}

	}

	/**
	 * Register the administration menu for this plugin into the WordPress Dashboard menu.
	 *
	 * @since    1.0.0
	 */
	public function add_plugin_admin_menu() {

		/*
		 * Add a settings page for this plugin to the Settings menu.
		 *
		 * NOTE:  Alternative menu locations are available via WordPress administration menu functions.
		 *
		 *        Administration Menus: http://codex.wordpress.org/Administration_Menus
		 *
		 * @TODO:
		 *
		 * - Change 'manage_options' to the capability you see fit
		 *   For reference: http://codex.wordpress.org/Roles_and_Capabilities
		 */
		$this->plugin_screen_hook_suffix = add_options_page(
			'Vimeo Importer',
			'Vimeo Importer',
			'manage_options',
			$this->plugin_slug,
			array( $this, 'admin_options_page' )
		);

	}

	/**
	 * Render the settings page for this plugin.
	 *
	 * @since    1.0.0
	 */
	public function admin_options_page() {

		// Get current options
		$options = get_option( 'Vimeo_Importer' );

		if ( isset( $_POST['options_submit'] ) ) {

			// Form submitted
			$new_options['app_id'] = esc_attr( $_POST['app_id'] );
			$new_options['app_secret'] = esc_attr( $_POST['app_secret'] );
			$new_options['access_token'] = esc_attr( $_POST['access_token'] );

			// Save concatinate array to comma delimited string
			if ( !empty( $_POST['post_types'] ) ) {
				$new_options['post_types'] = esc_attr( implode( ',', $_POST['post_types'] ) );
			} else {
				$new_options['post_types'] = '';
			}

			// Update options with new values
			update_option( 'Vimeo_Importer', $new_options );
			$options = $new_options;
		}

		include_once( 'views/admin.php' );

	}

	/**
	 * Add settings action link to the plugins page.
	 *
	 * @since    1.0.0
	 */
	public function add_action_links( $links ) {

		return array_merge(
			array(
				'settings' => '<a href="' . admin_url( 'options-general.php?page=' . $this->plugin_slug ) . '">' . __( 'Settings', $this->plugin_slug ) . '</a>'
			),
			$links
		);

	}

	/**
	 * Add the Vimeo Importer meta box on supported posts add/edit screen.
	 *
	 * @since    1.0.0
	 */
	public function add_meta_boxes() {

		foreach ( $this->get_supported_post_types() as $post_type ) {
			add_meta_box( $this->plugin_slug, __( 'Vimeo Importer', $this->plugin_slug ), array( $this, 'get_meta_box' ), $post_type, 'normal' );
		}

	}

	/**
	 * Add Thickbox script and styles.
	 *
	 * @since    1.0.0
	 */
	public function add_thick_box() {

		wp_enqueue_script( 'thickbox' );
		wp_enqueue_style( 'thickbox' );

	}

	/**
	 * Load admin Javascript on supported posts add/edit screen.
	 *
	 * @since    1.0.0
	 */
	public function admin_head() {

		global $post_type;
		if ( in_array($post_type, $this->get_supported_post_types()) ) {

			$this->add_thick_box();

			wp_enqueue_script( $this->plugin_slug . '-admin-script', plugins_url( 'assets/js/admin.js', __FILE__ ), array( 'jquery' ), self::VERSION );
			wp_enqueue_style( $this->plugin_slug .'-admin-styles', plugins_url( 'assets/css/admin.css', __FILE__ ), array(), self::VERSION );
		}

	}

	/**
	 * Callback for the meta box, container for everything added by Javascript.
	 *
	 * @since    1.0.0
	 */
	public function get_meta_box() {

		echo '<div class="js-' . $this->plugin_slug . '">' .
				'<noscript>Enable Javascript to import Vimeo videos.</noscript>' .
			 '</div>';

	}

	/**
	 * Get the post types that can display the Vimeo Importer
	 *
	 * @since    1.0.0
	 *
	 * @param 	 boolean    $details    Whether to return the entire object for each post type,
	 *                                  if false only an array of names will be returned.
	 *
	 * @return   array
	 */
	public function get_supported_post_types( $details = false ) {

		$options = get_option( 'Vimeo_Importer' );
		$post_types = explode( ',', $options['post_types'] );

		if ( false === $details ) {
			return $post_types;
		}

		$details = array();
		foreach ( $post_types as $post_type ) {
			$post_type_details = get_post_types( array('name' => $post_type), 'object' );
			$details[$post_type] = $post_type_details[$post_type];
		}

		return $details;

	}

}
