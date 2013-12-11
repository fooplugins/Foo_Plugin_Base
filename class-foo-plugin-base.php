<?php
/*
 * Foo_Plugin_Base
 * A base class for WordPress plugins. Get up and running quickly with this opinionated, convention based, plugin framework
 *
 * Version: 1.1
 * Author: Brad Vincent
 * Author URI: http://fooplugins.com
 * License: GPL2
*/

if ( !class_exists( 'Foo_Plugin_Base_v1_1' ) ) {

	abstract class Foo_Plugin_Base_v1_1 {

		/**
		 * Unique identifier for your plugin.
		 *
		 * @var      string
		 */
		protected $plugin_slug = false; //the slug (identifier) of the plugin

		/**
		 * The full name of your plugin.
		 *
		 * @var      string
		 */
		protected $plugin_title = false; //the friendly title of the plugin

		/**
		 * Plugin version, used for cache-busting of style and script file references.
		 *
		 * @var     string
		 */
		protected $plugin_version = false; //the version number of the plugin

		/* internal variables */
		protected $plugin_file; //the filename of the plugin
		protected $plugin_dir; //the folder path of the plugin
		protected $plugin_dir_name; //the folder name of the plugin
		protected $plugin_url; //the plugin url

		/**
		 * @var string Foo Plugin Base version number
		 */
		public $version = '2.0.0';

		/* internal dependencies */

		/** @var Foo_Plugin_Utils */
		protected $_utils = false; //a reference to our utils class

		/** @var Foo_Plugin_Settings */
		protected $_settings = false; //a ref to our settings helper class

		/** @var Foo_Plugin_Options */
		protected $_options = false; //a ref to our options helper class

		/** @var Foo_Plugin_Screen */
		protected $_screen; //a ref to our screen helper class

        /*
         * @return Foo_Plugin_Settings_v1_0
         */
        public function settings() {
            return $this->_settings;
        }

        /*
         * @return Foo_Plugin_Options_v1_1
         */
        public function options() {
            return $this->_options;
        }

        /*
         * @return Foo_Plugin_Screen_v1_0
         */
        public function screen() {
            return $this->_screen;
        }

        /*
         * @return Foo_Utils_v1_0
         */
        public function utils() {
            return $this->_utils;
        }

        /*
         * @return string
         */
        function get_slug() {
            return $this->plugin_slug;
        }

        function get_plugin_info() {
            return array(
                'slug'    => $this->plugin_slug,
                'title'   => $this->plugin_title,
                'version' => $this->plugin_version,
                'dir'     => $this->plugin_dir,
                'url'     => $this->plugin_url
            );
        }

		/*
		 * plugin constructor
		 * If the subclass makes use of a constructor, make sure the subclass calls parent::__construct() or parent::init()
		 */
		function __construct($file) {
			$this->init($file);
		}

		/*
		 * Initializes the plugin.
		 */
		function init($file, $slug = false, $version = false, $title = false) {

			$this->plugin_file     = $file;
			$this->plugin_dir      = trailingslashit( dirname( $file ) );
			$this->plugin_dir_name = plugin_basename( $this->plugin_dir );
			$this->plugin_url      = trailingslashit( plugins_url( '', $file ) );

			if ( $slug !== false ) $this->plugin_slug = $slug;
			if ( $version !== false ) $this->plugin_version = $version;
			if ( $title !== false ) $this->plugin_title = $title;

			//check to make sure the mandatory plugin fields have been set
			$this->check_mandatory_plugin_variables_set();

			//load any plugin dependencies
			$this->load_dependencies();

			//check we are using php 5
			$this->_utils->check_php_version( $this->plugin_title, '5.0.0' );

			// Load plugin text domain
			add_action( 'init', array($this, 'load_plugin_textdomain') );

			// Render any inline styles that need to go at the end of the head tag
			add_action( 'wp_head', array($this, 'inline_styles'), 100 );

			// Render any inline scripts at the bottom of the page just before the closing body tag
			add_action( 'wp_footer', array($this, 'inline_scripts'), 200 );

			if ( is_admin() ) {
				// Register any settings for the plugin
				add_action( 'admin_init', array($this, 'admin_create_settings') );

				// Add a settings page menu item
				add_action( 'admin_menu', array($this, 'admin_settings_page_menu') );

				// Add a links to the plugin listing
				add_filter( 'plugin_action_links_' . plugin_basename( $this->plugin_file ), array($this, 'admin_plugin_listing_actions') );

				// output CSS to the admin pages
				add_action( 'admin_print_styles', array($this, 'admin_print_styles') );

				// output JS to the admin pages
				add_action( 'admin_print_scripts', array($this, 'admin_print_scripts') );
			}

			do_action( $this->plugin_slug . '-' . (is_admin() ? 'admin' : '') . '_init' );
		}

		function check_mandatory_plugin_variables_set() {
			if ( empty($this->plugin_file) ) {
				throw new Exception('Required plugin variable not set : \'plugin_file\'. Please set this in the init() function of your plugin.');
			}
			if ( $this->plugin_slug === false ) {
				throw new Exception('Required plugin variable not set : \'plugin_slug\'. Please set this in the init() function of your plugin.');
			}
			if ( $this->plugin_title === false ) {
				throw new Exception('Required plugin variable not set : \'plugin_title\'. Please set this in the init() function of your plugin.');
			}
			if ( $this->plugin_version === false ) {
				throw new Exception('Required plugin variable not set : \'plugin_version\'. Please set this in the init() function of your plugin.');
			}
		}

		//load any dependencies
		function load_dependencies() {
			require_once 'dependencies/class-foo-plugin-utils.php';
			require_once 'dependencies/class-foo-plugin-settings.php';
			require_once 'dependencies/class-foo-plugin-options.php';
			require_once 'dependencies/class-foo-plugin-screen.php';

			$this->_utils    = new Foo_Plugin_Utils();
			$this->_settings = new Foo_Plugin_Settings($this->plugin_slug);
			$this->_options  = new Foo_Plugin_Options($this->plugin_slug);
			$this->_screen   = new Foo_Plugin_Screen($this->plugin_slug);

			//we need to make sure that we are running the correct versions of our dependancies
			$this->assert_dependency_version( $this->_utils, '1.0.0' );
			$this->assert_dependency_version( $this->_settings, '1.0.0' );
			$this->assert_dependency_version( $this->_options->version, '1.0.0' );
			$this->assert_dependency_version( $this->_screen, '1.0.0' );

			do_action( $this->plugin_slug . '-load_dependencies' );
		}

		/**
		 * Load the plugin text domain for translation.
		 *
		 * @since    1.0.0
		 */
		public function load_plugin_textdomain() {

			$domain = $this->plugin_slug;
			$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

			load_textdomain( $domain, WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale . '.mo' );
			load_plugin_textdomain( $domain, false, $this->plugin_dir . '/lang/' );
		}

		//wrapper around the apply_filters function that appends the plugin slug to the tag
		function apply_filters($tag, $value) {
			if ( !$this->_utils->starts_with( $tag, $this->plugin_slug ) ) {
				$tag = $this->plugin_slug . '-' . $tag;
			}

			return apply_filters( $tag, $value );
		}

		// register and enqueue a script
		function register_and_enqueue_js($file, $d = array('jquery'), $v = false, $f = false) {
			if ( $v === false ) {
				$v = $this->plugin_version;
			}

			$js_src_url = $file;
			if ( !$this->_utils->str_contains( $file, '://' ) ) {
				$js_src_url = $this->plugin_url . 'js/' . $file;
				if ( !file_exists( $this->plugin_dir . 'js/' . $file ) ) return;
			}
			$h = str_replace( '.', '-', pathinfo( $file, PATHINFO_FILENAME ) );

			wp_register_script(
				$handle = $h,
				$src = $js_src_url,
				$deps = $d,
				$ver = $v,
				$in_footer = $f );

			wp_enqueue_script( $h );
		}

		// register and enqueue a CSS
		function register_and_enqueue_css($file, $d = false, $v = false) {
			if ( $v === false ) {
				$v = $this->plugin_version;
			}

			$css_src_url = $file;
			if ( !$this->_utils->str_contains( $file, '://' ) ) {
				$css_src_url = $this->plugin_url . 'css/' . $file;
				if ( !file_exists( $this->plugin_dir . 'css/' . $file ) ) return;
			}

			$h = str_replace( '.', '-', pathinfo( $file, PATHINFO_FILENAME ) );

			wp_register_style(
				$handle = $h,
				$src = $css_src_url,
				$deps = $d,
				$ver = $v );

			wp_enqueue_style( $h );
		}

		// register any options/settings we may want to store for this plugin
		function admin_create_settings() {
			do_action( $this->plugin_slug . '-admin_create_settings', $this, $this->_settings );
		}

		// enqueue the admin scripts
		function admin_print_scripts() {

			//add a general admin script
			$this->register_and_enqueue_js( 'admin.js' );

			//if we are on the current plugin's settings page then check for file named /js/admin-settings.js
			if ( $this->_screen->is_plugin_settings_page() ) {
				$this->register_and_enqueue_js( 'admin-settings.js' );

				//check if we are using an upload setting and add media uploader scripts
				if ( $this->_settings->has_setting_of_type( 'image' ) ) {
					wp_enqueue_script( 'media-upload' );
					wp_enqueue_script( 'thickbox' );
					$this->register_and_enqueue_js( 'admin-uploader.js', array('jquery', 'media-upload', 'thickbox') );
				}
			}

			//add any scripts for the current post type
			$post_type = $this->_screen->get_screen_post_type();
			if ( !empty($post_type) ) {
				$this->register_and_enqueue_js( 'admin-' . $post_type . '.js' );
			}

			//finally try add any scripts for the current screen id /css/admin-screen-id.css
			$this->register_and_enqueue_js( 'admin-' . $this->_screen->get_screen_id() . '.js' );

			do_action( $this->plugin_slug . '-admin_print_scripts' );
		}

		// register the admin stylesheets
		function admin_print_styles() {

			//add a general admin stylesheet
			$this->register_and_enqueue_css( 'admin.css' );

			//if we are on the current plugin's settings page then check for file /css/admin-settings.css
			if ( $this->_screen->is_plugin_settings_page() ) {
				$this->register_and_enqueue_css( 'admin-settings.css' );

				//Media Uploader Style
				wp_enqueue_style( 'thickbox' );
			}

			//add any scripts for the current post type /css/admin-foobar.css
			$post_type = $this->_screen->current_post_type();
			if ( !empty($post_type) ) {
				$this->register_and_enqueue_css( 'admin-' . $post_type . '.css' );
			}

			//finally try add any styles for the current screen id /css/admin-screen-id.css
			$this->register_and_enqueue_css( 'admin-' . $this->_screen->get_screen_id() . '.css' );

			do_action( $this->plugin_slug . '-admin_print_styles' );
		}

		function admin_plugin_listing_actions($links) {
			if ( $this->has_admin_settings_page() ) {
				// Add the 'Settings' link to the plugin page
				$links[] = '<a href="options-general.php?page=' . $this->plugin_slug . '"><b>Settings</b></a>';
			}

			return apply_filters( $this->plugin_slug . '-plugin_action_links', $links );
		}

		function has_admin_settings_page() {
			return apply_filters( $this->plugin_slug . '-has_settings_page', true );
		}

		// add a settings admin menu
		function admin_settings_page_menu() {
			if ( $this->has_admin_settings_page() ) {

				$page_title = $this->apply_filters( 'settings_page_title', $this->plugin_title . __( ' Settings', $this->plugin_slug ) );
				$menu_title = $this->apply_filters( 'settings_menu_title', $this->plugin_title );

				add_options_page( $page_title, $menu_title, 'manage_options', $this->plugin_slug, array($this, 'admin_settings_render_page') );
			}
		}

		// render the setting page
		function admin_settings_render_page() {
			global $settings_data;

			//check if a settings.php file exists in the views folder. If so then include it
			if ( file_exists( $this->plugin_dir . 'views/settings.php' ) ) {

				//global variable that can be used by the included settings pages
				$settings_data = array(
					'plugin_info'      => $this->get_plugin_info(),
					'settings_summary' => $this->apply_filters( 'settings_page_summary', '' ),
					'settings_tabs'    => $this->_settings->get_tabs()
				);

                do_action( $this->plugin_slug . '-before_settings_page_render', $settings_data );

                include_once( $this->plugin_dir . 'views/settings.php' );

                do_action( $this->plugin_slug . '-after_settings_page_render', $settings_data );
			}
		}

		function inline_styles() {
            do_action( $this->plugin_slug . '-' . (is_admin() ? 'admin' : '') . '_inline_styles', $this );
		}

		function inline_scripts() {
			do_action( $this->plugin_slug . '-' . (is_admin() ? 'admin' : '') . '_inline_scripts', $this );
		}
	}
}