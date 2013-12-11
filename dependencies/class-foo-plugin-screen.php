<?php
/*
* Foo_Options helper class
* A helper class for storing all your plugin settings as a single WP option
*
* Version: 1.1
* Author: Brad Vincent
* Author URI: http://fooplugins.com
* License: GPL2
*/

if ( !class_exists( 'Foo_Plugin_Screen' ) ) {
	class Foo_Plugin_Screen {

		public $version = '1.0.0';

		protected $plugin_slug;

		function __construct($plugin_slug) {
			$this->plugin_slug = $plugin_slug;
		}

		function get_screen_id() {
			$screen = get_current_screen();
			if ( empty($screen) ) return false;

			return $screen->id;
		}

		function get_screen_post_type() {
			$screen = get_current_screen();
			if ( empty($screen) ) return false;

			return $screen->post_type;
		}

		function is_plugin_settings_page() {
			return is_admin() && $this->get_screen_id() === 'settings_page_' . $this->plugin_slug;
		}

		function is_plugin_post_type_page($post_type) {
			return is_admin() && $this->get_screen_post_type() === $post_type;
		}

		/**
		 * gets the current post type in the WordPress Admin
		 */
		function current_post_type() {
			global $get_current_post_type, $post, $typenow, $current_screen;

			if ( $get_current_post_type ) return $get_current_post_type;

			//we have a post so we can just get the post type from that
			if ( $post && $post->post_type ) {
				$get_current_post_type = $post->post_type;
			} //check the global $typenow - set in admin.php
			elseif ( $typenow ) {
				$get_current_post_type = $typenow;
			} //check the global $current_screen object - set in sceen.php
			elseif ( $current_screen && $current_screen->post_type ) {
				$get_current_post_type = $current_screen->post_type;
			} //lastly check the post_type querystring
			elseif ( isset($_REQUEST['post_type']) ) {
				$get_current_post_type = sanitize_key( $_REQUEST['post_type'] );
			}

			return $get_current_post_type;
		}

		// returns the current URL
		function current_url() {
			global $wp;
			$current_url = add_query_arg( $wp->query_string, '', home_url( $wp->request ) );

			return $current_url;
		}

		// returns the current page name
		function current_page_name() {
			return basename( $_SERVER['SCRIPT_FILENAME'] );
		}
	}
}