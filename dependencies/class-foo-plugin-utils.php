<?php
/*
* Foo_Utils
* A bunch of useful utility functions
*
* Version: 1.0
* Author: Brad Vincent
* Author URI: http://fooplugins.com
* License: GPL2
*/

if ( !class_exists( 'Foo_Plugin_Utils' ) ) {
	class Foo_Plugin_Utils {

		public $version = '1.0.0';

		function is_checked($data, $key, $default = false) {
			if (!is_array($data)) return $default;

			return array_key_exists($key, $data);

			return $default;
		}

		/**
		 * safely get a value from an array
		 *
		 * @param      $array	array
		 * @param      $key		string
		 * @param null $default	mixed
		 *
		 * @return mixed
		 */
		function safe_get($array, $key, $default = null) {
			if ( !is_array( $array ) ) return $default;
			$value = array_key_exists( $key, $array ) ? $array[$key] : null;
			if ( $value === null ) {
				return $default;
			}

			return $value;
		}

		function safe_get_from_request($key, $default = null) {
			return $this->safe_get( $_REQUEST, $key, $default );
		}

// check the version of PHP running on the server
		function check_php_version($plugin_title, $ver) {
			$php_version = phpversion();
			if ( version_compare( $php_version, $ver ) < 0 ) {
				throw new Exception($plugin_title . " requires at least version $ver of PHP. You are running an older version ($php_version). Please upgrade!");
			}
		}

// check the version of WP running
		function check_wp_version($plugin_title, $ver) {
			global $wp_version;
			if ( version_compare( $wp_version, $ver ) < 0 ) {
				throw new Exception($plugin_title . " requires at least version $ver of WordPress. You are running an older version ($wp_version). Please upgrade!");
			}
		}

		function to_key($input) {
			return str_replace( " ", "_", strtolower( $input ) );
		}

		function to_title($input) {
			return ucwords( str_replace( array("-", "_"), " ", $input ) );
		}

		/*
		* returns true if a needle can be found in a haystack
		*/
		function str_contains($haystack, $needle) {
			if ( empty($haystack) || empty($needle) ) {
				return false;
			}

			$pos = strpos( strtolower( $haystack ), strtolower( $needle ) );

			if ( $pos === false ) {
				return false;
			} else {
				return true;
			}
		}

		/**
		 * starts_with
		 * Tests if a text starts with an given string.
		 *
		 * @param     string
		 * @param     string
		 *
		 * @return    bool
		 */
		function starts_with($haystack, $needle) {
			return strpos( $haystack, $needle ) === 0;
		}

		function ends_with($haystack, $needle, $case = true) {
			$expectedPosition = strlen( $haystack ) - strlen( $needle );

			if ( $case ) {
				return strrpos( $haystack, $needle, 0 ) === $expectedPosition;
			}

			return strripos( $haystack, $needle, 0 ) === $expectedPosition;
		}
	}
}