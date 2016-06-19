<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       http://jgoodesign@gmail.com
 * @since      1.0.0
 *
 * @package    JGood_Media_Library
 * @subpackage JGood_Media_Library/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the dashboard-specific stylesheet and JavaScript.
 *
 * @package    JGood_Media_Library
 * @subpackage JGood_Media_Library/public
 * @author     Jordan Good <jgoodesign@gmail.com>
 */
class JGood_Media_Library_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $jgood_media_library    The ID of this plugin.
	 */
	private $jgood_media_library;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @var      string    $jgood_media_library       The name of the plugin.
	 * @var      string    $version    The version of this plugin.
	 */
	public function __construct( $jgood_media_library, $version ) {

		$this->jgood_media_library = $jgood_media_library;
		$this->version = $version;

		// include html file to access display functions
		require_once plugin_dir_path( __FILE__ ) . 'partials/jgood-media-library-public-display.php';
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in JGood_Media_Library_Public_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The JGood_Media_Library_Public_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->jgood_media_library, plugin_dir_url( __FILE__ ) . 'css/jgood-media-library-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in JGood_Media_Library_Public_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The JGood_Media_Library_Public_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->jgood_media_library, plugin_dir_url( __FILE__ ) . 'js/jgood-media-library-public.js', array( 'jquery' ), $this->version, false );

	}

}
