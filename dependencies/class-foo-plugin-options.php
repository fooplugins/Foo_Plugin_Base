<?php
/*
* Foo_Plugin_Options class
* A helper class for storing all your plugin options as a single WP option. Multi-site friendly.
*
* Version: 1.1
* Author: Brad Vincent
* Author URI: http://fooplugins.com
* License: GPL2
*/

if ( !class_exists( 'Foo_Plugin_Options' ) ) {
	class Foo_Plugin_Options {

		/**
		 * @var string The version of the Foo_Plugin_Options class.
		 */
		public $version = '1.0.0';

		/**
		 * @var string The name of the option that will be saved to the options table.
		 */
		protected $option_name;

		/**
		 * Foo_Plugin_Options Constructor
		 *
		 * @param string $option_name The name of the single option we want to save in the options table. Usually the plugin slug.
		 */
		function __construct($option_name) {
			$this->option_name = $option_name;
		}

		/**
		 * Returns the array of options.
		 * @return mixed
		 */
		private function get_options() {
			if ( is_network_admin() ) {
				return get_site_option( $this->option_name );
			} else {
				return wp_parse_args( get_option( $this->option_name ), get_site_option( $this->option_name ) );
			}
		}

		/**
		 * Save an individual option.
		 *
		 * @param string $key   The key of the individual option that will be stored.
		 * @param mixed  $value The value of the individual option that will be stored.
		 */
		function save($key, $value) {
			//first get the options
			$options = $this->get_options();

			if ( !$options ) {
				//no options have been saved yet, so add it

				if ( is_network_admin() ) {
					add_site_option( $this->option_name, array($key => $value) );
				} else {
					add_option( $this->option_name, array($key => $value) );
				}

			} else {
				//update the existing option
				$options[$key] = $value;

				if ( is_network_admin() ) {
					update_site_option( $this->option_name, $options );
				} else {
					update_option( $this->option_name, $options );
				}
			}
		}

		/**
		 * Get an individual option.
		 *
		 * @param string $key     The key of the individual option that will be stored.
		 * @param mixed  $default Optional. The default value to return if the key was not found.
		 *
		 * @return mixed
		 */
		function get($key, $default = false) {
			$options = $this->get_options();

			if ( $options ) {
				return (array_key_exists( $key, $options )) ? $options[$key] : $default;
			}

			return $default;
		}

		/**
		 * Delete an individual option.
		 *
		 * @param $key The key of the individual option we want to delete.
		 */
		function delete($key) {
			$options = $this->get_options();

			if ( $options ) {
				unset($options[$key]);

				if ( is_network_admin() ) {
					update_site_option( $this->option_name, $options );
				} else {
					update_option( $this->option_name, $options );
				}
			}
		}
	}
}