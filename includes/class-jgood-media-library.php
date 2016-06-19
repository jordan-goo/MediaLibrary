<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the dashboard.
 *
 * @link       http://jgoodesign@gmail.com
 * @since      1.0.0
 *
 * @package    JGood_Media_Library
 * @subpackage JGood_Media_Library/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, dashboard-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    JGood_Media_Library
 * @subpackage JGood_Media_Library/includes
 * @author     Jordan Good <jgoodesign@gmail.com>
 */
class JGood_Media_Library {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      JGood_Media_Library_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $jgood_media_library    The string used to uniquely identify this plugin.
	 */
	protected $jgood_media_library;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the Dashboard and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {

		$this->jgood_media_library = 'jgood-media-library';
		$this->version = '1.0.0';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - JGood_Media_Library_Loader. Orchestrates the hooks of the plugin.
	 * - JGood_Media_Library_i18n. Defines internationalization functionality.
	 * - JGood_Media_Library_Admin. Defines all hooks for the dashboard.
	 * - JGood_Media_Library_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-jgood-media-library-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-jgood-media-library-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the Dashboard.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-jgood-media-library-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-jgood-media-library-public.php';


		$this->loader = new JGood_Media_Library_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the JGood_Media_Library_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new JGood_Media_Library_i18n();
		$plugin_i18n->set_domain( $this->get_jgood_media_library() );

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the dashboard functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new JGood_Media_Library_Admin( $this->get_jgood_media_library(), $this->get_version() );

		// add new menu item to wp dashboard
		$this->loader->add_action('admin_menu', $plugin_admin, 'jgood_media_menu');

		// define ajax functions
		$this->loader->add_action( 'wp_ajax_jgood-query-attachments', $plugin_admin, 'jgood_media_ajax_query_attachments', 1);
		$this->loader->add_action( 'wp_ajax_jgood-library-add-folder', $plugin_admin, 'jgood_media_ajax_add_folder', 1);
		$this->loader->add_action( 'wp_ajax_jgood-library-delete-folder', $plugin_admin, 'jgood_media_ajax_delete_folder', 1);
		$this->loader->add_action( 'wp_ajax_jgood-library-save-folder-name', $plugin_admin, 'jgood_media_ajax_save_folder_name', 1);
		$this->loader->add_action( 'wp_ajax_jgood-library-create-file', $plugin_admin, 'jgood_media_ajax_create_file', 1);
		$this->loader->add_action( 'wp_ajax_jgood-library-get-history', $plugin_admin, 'jgood_media_ajax_get_history', 1);

		// add new button on page edit
		$this->loader->add_action('media_buttons', $plugin_admin, 'jgood_add_media_button');

		// add our templates to the footer
		$this->loader->add_action( 'admin_footer', $plugin_admin, 'jgood_media_templates', 5);

		// enqueue scripts/styles
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new JGood_Media_Library_Public( $this->get_jgood_media_library(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_jgood_media_library() {
		return $this->jgood_media_library;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    JGood_Media_Library_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
