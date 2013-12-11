<?php

if ( !class_exists( 'Foo_Plugin_Settings' ) ) {
	class Foo_Plugin_Settings {

		public $version = '1.0.0';

		protected $plugin_slug;

		/** @var Foo_Utils_v1_0 */
		protected $_utils = false;

		protected $_settings = array(); //the plugin settings array
		protected $_settings_sections = array(); //the plugin sections array
		protected $_settings_tabs = array(); //the plugin tabs array
		protected $_admin_errors = false; //store of admin errors

		function __construct($plugin_slug) {
			$this->plugin_slug = $plugin_slug;
			$this->_utils      = new Foo_Utils_v1_0();
		}

		function get_tabs() {
			return $this->_settings_tabs;
		}

		//check if we have any setting of a certain type
		function has_setting_of_type($type) {
			foreach ( $this->_settings as $setting ) {
				if ( $setting['type'] == $type ) return true;
			}

			return false;
		}

		// add a setting tab
		function add_tab($tab_id, $title) {
			if ( !array_key_exists( $tab_id, $this->_settings_tabs ) ) {

				//pre action
				do_action( $this->plugin_slug . '-before_settings_tab', $tab_id, $title );

				$tab = array(
					'id'    => $tab_id,
					'title' => $title
				);

				$this->_settings_tabs[$tab_id] = $tab;

				//post action
				do_action( $this->plugin_slug . '-after_settings_tab', $tab_id, $title );
			}
		}

		// add a setting section
		function add_section($section_id, $title, $desc = '') {

			//check we have the section
			if ( !array_key_exists( $section_id, $this->_settings_sections ) ) {

				//pre action
				do_action( $this->plugin_slug . '-before_settings_section', $section_id, $title, $desc );

				$section = array(
					'id'    => $section_id,
					'title' => $title,
					'desc'  => $desc
				);

				$this->_settings_sections[$section_id] = $section;

				$section_callback = create_function( '',
					'echo "' . $desc . '";' );

				add_settings_section( $section_id, $title, $section_callback, $this->plugin_slug );

				//post action
				do_action( $this->plugin_slug . '-after_settings_section', $section_id, $title, $desc );
			}
		}

		function add_section_to_tab($tab_id, $section_id, $title, $desc = '') {
			if ( array_key_exists( $tab_id, $this->_settings_tabs ) ) {

				//get the correct section id for the tab
				$section_id = $tab_id . '-' . $section_id;

				//add the section to the tab
				if ( !array_key_exists( $section_id, $this->_settings_sections ) ) {
					$this->_settings_tabs[$tab_id]['sections'][$section_id] = $section_id;
				}

				//add the section
				$this->add_section( $section_id, $title, $desc );

			}

			return $section_id;
		}

		// add a settings field
		function add_setting($args = array()) {

			$defaults = array(
				'id'          => 'default_field',
				'title'       => 'Default Field',
				'desc'        => '',
				'default'     => '',
				'placeholder' => '',
				'type'        => 'text',
				'section'     => '',
				'choices'     => array(),
				'class'       => '',
				'tab'         => ''
			);

			//only declare up front so no debug warnings are shown
			$title = $type = $id = $desc = $default = $placeholder = $choices = $class = $section = $tab = null;

			extract( wp_parse_args( $args, $defaults ) );

			$field_args = array(
				'type'        => $type,
				'id'          => $id,
				'desc'        => $desc,
				'default'     => $default,
				'placeholder' => $placeholder,
				'choices'     => $choices,
				'label_for'   => $id,
				'class'       => $class
			);

			if ( count( $this->_settings ) == 0 ) {
				//only do this once
				register_setting( $this->plugin_slug, $this->plugin_slug, array($this, 'validate') );
			}

			$this->_settings[] = $args;

			$section_id = $this->_utils->to_key( $section );

			//check we have the tab
			if ( !empty($tab) ) {
				$tab_id = $this->_utils->to_key( $tab );

				//add the tab
				$this->add_tab( $tab_id, $this->_utils->to_title( $tab ) );

				//add the section
				$section_id = $this->add_section_to_tab( $tab_id, $section_id, $this->_utils->to_title( $section ) );
			} else {
				//just add the section
				$this->add_section( $section_id, $this->_utils->to_title( $section ) );
			}

			do_action( $this->plugin_slug . '-before_setting', $args );

			//add the setting!
			add_settings_field( $id, $title, array($this, 'render'), $this->plugin_slug, $section_id, $field_args );

			do_action( $this->plugin_slug . '-after_setting', $args );
		}

		// render HTML for individual settings
		function render($args = array()) {

			//only declare up front so no debug warnings are shown
			$type = $id = $desc = $default = $placeholder = $choices = $class = $section = $tab = null;

			extract( $args );

			$options = get_option( $this->plugin_slug );

			if ( !isset($options[$id]) && $type != 'checkbox' ) {
				$options[$id] = $default;
			}

			$field_class = '';
			if ( $class != '' ) {
				$field_class = ' class="' . $class . '"';
			}

			$errors = get_settings_errors( $id );

			do_action( $this->plugin_slug . '-before_settings_render', $args );

			switch ( $type ) {

				case 'heading':
					echo '</td></tr><tr valign="top"><td colspan="2">' . $desc;
					break;

				case 'html':
					echo $desc;
					break;

				case 'checkbox':
					$checked = '';
					if ( isset($options[$id]) && $options[$id] == 'on' ) {
						$checked = ' checked="checked"';
					} else if ( $options === false && $default == 'on' ) {
						$checked = ' checked="checked"';
					}

					//echo '<input type="hidden" name="'.$this->plugin_slug.'[' . $id . '_default]" value="' . $default . '" />';
					echo '<input' . $field_class . ' type="checkbox" id="' . $id . '" name="' . $this->plugin_slug . '[' . $id . ']" value="on"' . $checked . ' /> <label for="' . $id . '"><small>' . $desc . '</small></label>';

					break;

				case 'select':
					echo '<select' . $field_class . ' name="' . $this->plugin_slug . '[' . $id . ']">';

					foreach ( $choices as $value => $label ) {
						$selected = '';
						if ( $options[$id] == $value ) {
							$selected = ' selected="selected"';
						}
						echo '<option ' . $selected . ' value="' . $value . '">' . $label . '</option>';
					}

					echo '</select>';

					break;

				case 'radio':
					$i           = 0;
					$saved_value = $options[$id];
					if ( empty($saved_value) ) {
						$saved_value = $default;
					}
					foreach ( $choices as $value => $label ) {
						$selected = '';
						if ( $saved_value == $value ) {
							$selected = ' checked="checked"';
						}
						echo '<input' . $field_class . $selected . ' type="radio" name="' . $this->plugin_slug . '[' . $id . ']" id="' . $id . $i . '" value="' . $value . '"> <label for="' . $id . $i . '">' . $label . '</label>';
						if ( $i < count( $choices ) - 1 ) {
							echo '<br />';
						}
						$i++;
					}

					break;

				case 'textarea':
					echo '<textarea' . $field_class . ' id="' . $id . '" name="' . $this->plugin_slug . '[' . $id . ']" placeholder="' . $placeholder . '">' . esc_attr( $options[$id] ) . '</textarea>';

					break;

				case 'password':
					echo '<input' . $field_class . ' type="password" id="' . $id . '" name="' . $this->plugin_slug . '[' . $id . ']" value="' . esc_attr( $options[$id] ) . '" />';

					break;

				case 'text':
					echo '<input class="regular-text ' . $class . '" type="text" id="' . $id . '" name="' . $this->plugin_slug . '[' . $id . ']" placeholder="' . $placeholder . '" value="' . esc_attr( $options[$id] ) . '" />';

					break;

				case 'checkboxlist':
					$i = 0;
					foreach ( $choices as $value => $label ) {

						$checked = '';
						if ( isset($options[$id][$value]) && $options[$id][$value] == 'true' ) {
							$checked = 'checked="checked"';
						}

						echo '<input' . $field_class . ' ' . $checked . ' type="checkbox" name="' . $this->plugin_slug . '[' . $id . '|' . $value . ']" id="' . $id . $i . '" value="on"> <label for="' . $id . $i . '">' . $label . '</label>';
						if ( $i < count( $choices ) - 1 ) {
							echo '<br />';
						}
						$i++;
					}

					break;
				case 'image':
					echo '<input class="regular-text image-upload-url" type="text" id="' . $id . '" name="' . $this->plugin_slug . '[' . $id . ']" placeholder="' . $placeholder . '" value="' . esc_attr( $options[$id] ) . '" />';
					echo '<input id="st_upload_button" class="image-upload-button" type="button" name="upload_button" value="' . __( 'Select Image', $this->plugin_slug ) . '" />';
					break;

				default:
					do_action( $this->plugin_slug . '-settings_custom_type_render', $args );
					break;
			}

			do_action( $this->plugin_slug . '-after_settings_render', $args );

			if ( is_array( $errors ) ) {
				foreach ( $errors as $error ) {
					echo "<span class='error'>{$error['message']}</span>";
				}
			}

			if ( $type != 'checkbox' && $type != 'heading' && $type != 'html' && $desc != '' ) {
				echo '<br /><small>' . $desc . '</small>';
			}
		}

		// validate our settings
		function validate($input) {

			//check to see if the options were reset
			if ( isset ($input['reset-defaults']) ) {
				delete_option( $this->plugin_slug );
				delete_option( $this->plugin_slug . '_valid' );
				delete_option( $this->plugin_slug . '_valid_expires' );
				add_settings_error(
					'reset',
					'reset_error',
					__( 'Settings restored to default values', $this->plugin_slug ),
					'updated'
				);

				return false;
			}

//            if (empty($input['sample_text'])) {
//
//                add_settings_error(
//                    'sample_text',           // setting title
//                    'sample_text_error',            // error ID
//                    'Please enter some sample text',   // error message
//                    'error'                        // type of message
//                );
//
//            }

			foreach ( $this->_settings as $setting ) {
				$this->validate_setting( $setting, $input );
			}

			return $input;
		}

		function validate_setting($setting, &$input) {
			//validate a single setting

			if ( $setting['type'] == 'checkboxlist' ) {

				unset($checkboxarray);

				foreach ( $setting['choices'] as $value => $label ) {
					if ( !empty($input[$setting['id'] . '|' . $value]) ) {
						// If it's not null, make sure it's true, add it to an array
						$checkboxarray[$value] = 'true';
					} else {
						$checkboxarray[$value] = 'false';
					}
				}

				if ( !empty($checkboxarray) ) {
					$input[$setting['id']] = $checkboxarray;
				}

			}
		}
	}
}