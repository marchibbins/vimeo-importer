<?php
/**
 * @package   Vimeo_Importer
 * @author    Marc Hibbins <marc@marchibbins.com>
 * @license   GPL-2.0+
 * @link      http://marchibbins.com
 *
 * Plugin Name:       Vimeo Importer
 * Description:       Imports Vimeo videos as Video CPTs
 * Version:           1.0.0
 * Author:            Marc Hibbins
 * Author URI:        http://marchibbins.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/*
 * Initialise for Admin interface only.
 */
if ( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {

	require_once( plugin_dir_path( __FILE__ ) . 'admin/class-vimeo-importer-admin.php' );
	add_action( 'plugins_loaded', array( 'Vimeo_Importer_Admin', 'get_instance' ) );

}
