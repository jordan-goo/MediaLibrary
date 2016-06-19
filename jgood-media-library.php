<?php


/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * Dashboard. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://jgoodesign.ca
 * @since             1.0.0
 * @package           JGood_Media_Library
 *
 * @wordpress-plugin
 * Plugin Name:       JGood Media Library
 * Plugin URI:        http://jgoodesign.ca
 * Description:       Change media library to a Windows file structure.
 * Version:           1.0.0
 * Author:            Jordan Good
 * Author URI:        mailto: jgoodesign@gmail.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       jgood-media-library
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-jgood-media-library-activator.php
 */
function activate_jgood_media_library() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-jgood-media-library-activator.php';
	JGood_Media_Library_Activator::activate();
	//manually register post type, create rewrite rules and then flush rewrite rules
	jgood_media_library_init_post_types();
	flush_rewrite_rules();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-jgood-media-library-deactivator.php
 */
function deactivate_jgood_media_library() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-jgood-media-library-deactivator.php';
	JGood_Media_Library_Deactivator::deactivate();
	//flush rewrite rules
	flush_rewrite_rules();
}

register_activation_hook( __FILE__, 'activate_jgood_media_library' );
register_deactivation_hook( __FILE__, 'deactivate_jgood_media_library' );

// add post type register to init action
add_action('init', 'jgood_media_library_init_post_types');

/**
 * Register custom post type
 *
 * @since    1.0.0
 */
function jgood_media_library_init_post_types() {
	// setup category tag for post type
	$labels = array(
		'name'              => _x( 'Categories', 'Project category name', 'JGood Media Library' ),
		'singular_name'     => _x( 'Category', 'Project category singular name', 'JGood Media Library' ),
		'search_items'      => __( 'Search Categories', 'JGood Media Library' ),
		'all_items'         => __( 'All Categories', 'JGood Media Library' ),
		'parent_item'       => __( 'Parent Category', 'JGood Media Library' ),
		'parent_item_colon' => __( 'Parent Category:', 'JGood Media Library' ),
		'edit_item'         => __( 'Edit Category', 'JGood Media Library' ),
		'update_item'       => __( 'Update Category', 'JGood Media Library' ),
		'add_new_item'      => __( 'Add New Category', 'JGood Media Library' ),
		'new_item_name'     => __( 'New Category Name', 'JGood Media Library' ),
		'menu_name'         => __( 'Categories', 'JGood Media Library' ),
	);

	// register category taxonomy
	register_taxonomy( 'jgood_media_library_category', array( 'attachment' ), array(
		'hierarchical'      => true,
		'labels'            => $labels,
		'show_ui'           => true,
		'show_admin_column' => true,
		'query_var'         => true,
	) );
}

/**
 * The core plugin class that is used to define internationalization,
 * dashboard-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-jgood-media-library.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_jgood_media_library() {

	$plugin = new JGood_Media_Library();
	$plugin->run();

	// include updater class
	require_once( plugin_dir_path( __FILE__ ) . 'includes/class-jgood-media-library-updater.php' );
	if ( is_admin() ) {
		// if admin, start updater class
		// __FILE__,
		// github user name
		// github repo name
		// optional private github access token
	    new JGood_Plugin_Updater( __FILE__, 'jgoodesign', 'MediaLibrary' );
	}

}
run_jgood_media_library();
