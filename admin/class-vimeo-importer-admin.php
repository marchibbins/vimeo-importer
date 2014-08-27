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
	 */
	const VERSION = '1.0.0';

	/**
	 * Instance of this class.
	 */
	protected static $instance = null;

	/**
	 * Unique identifier for your plugin.
	 */
	protected $plugin_slug = 'vimeo-importer';

	/**
	 * Slug of the plugin screen.
	 */
	protected $plugin_screen_hook_suffix = null;

	/**
	 * Initialize the plugin by loading admin scripts & styles and adding a
	 * settings page and menu.
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
	 */
	public function add_plugin_admin_menu() {

		/*
		 * Add a settings page for this plugin to the Settings menu.
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
			if ( !empty( $_POST['relate_types'] ) ) {
				$new_options['relate_types'] = esc_attr( implode( ',', $_POST['relate_types'] ) );
			} else {
				$new_options['relate_types'] = '';
			}

			// Update options with new values
			update_option( 'Vimeo_Importer', $new_options );
			$options = $new_options;
		}

		include_once( 'views/admin.php' );

	}

	/**
	 * Add settings action link to the plugins page.
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
	 */
	public function add_meta_boxes() {

		foreach ( $this->get_supported_post_types() as $post_type ) {
			add_meta_box( $this->plugin_slug, __( 'Vimeo Importer', $this->plugin_slug ), array( $this, 'get_meta_box' ), $post_type, 'side' );
		}

	}

	/**
	 * Add Thickbox script and styles.
	 */
	public function add_thick_box() {

		wp_enqueue_script( 'thickbox' );
		wp_enqueue_style( 'thickbox' );

	}

	/**
	 * Load admin Javascript on supported posts add/edit screen.
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
	 */
	public function get_meta_box() {

		global $post_type;

		$relate = in_array($post_type, $this->get_relate_post_types()) ? 'true' : 'false';

		echo '<div class="js-' . $this->plugin_slug . '" data-relate="' . $relate . '">' .
				'<noscript>Enable Javascript to import Vimeo videos.</noscript>' .
			 '</div>';

	}

	/**
	 * Get the post types that can display the Vimeo Importer
	 */
	public function get_supported_post_types( ) {

		$options = get_option( 'Vimeo_Importer' );
		$post_types = explode( ',', $options['post_types'] );

		return $post_types;

	}

	/**
	 * Get the post types that can display the Vimeo Importer
	 */
	public function get_relate_post_types( ) {

		$options = get_option( 'Vimeo_Importer' );
		$relate_types = explode( ',', $options['relate_types'] );

		return $relate_types;

	}

}
