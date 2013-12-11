<?php
/*
* Foo_Options_Helper class
* A helper class for storing all your plugin settings as a single WP option
*
* Version: 1.1
* Author: Brad Vincent
* Author URI: http://fooplugins.com
* License: GPL2
*/

if ( !class_exists( 'Foo_Plugin_Options' ) ) {
	class Foo_Plugin_Options {

		public $version = '1.0.0';

		protected $plugin_slug;

		function __construct($plugin_slug) {
			$this->plugin_slug = $plugin_slug;
		}

		private function get_all() {
			return get_option( $this->plugin_slug );
		}

		// save a WP option for the plugin. Stores an array of data, so only 1 option is saved for the whole plugin to save DB space and so that the options table is not polluted with hundreds of entries
		function save($key, $value) {
			$options = $this->get_all();
			if ( !$options ) {
				//no options have been saved for this plugin
				add_option( $this->plugin_slug, array($key => $value) );
			} else {
				$options[$key] = $value;
				update_option( $this->plugin_slug, $options );
			}
		}

		//get a WP option value for the plugin
		function get($key, $default = false) {
			$options = $this->get_all();
			if ( $options ) {
				return (array_key_exists( $key, $options )) ? $options[$key] : $default;
			}

			return $default;
		}

		function is_checked($key, $default = false) {
			$options = $this->get_all();
			if ( $options ) {
				return array_key_exists( $key, $options );
			}

			return $default;
		}

		function delete($key) {
			$options = $this->get_all();
			if ( $options ) {
				unset($options[$key]);
				update_option( $this->plugin_slug, $options );
			}
		}

		function get_int($key, $default = 0) {
			return intval( $this->get($key, $default) );
		}

		function get_float($key, $default = 0) {
			return floatval( $this->get($key, $default) );
		}
	}
}