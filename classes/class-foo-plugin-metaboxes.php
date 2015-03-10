<?php
/**
 * Foo Metaboxes allows you to add custom metaboxes to any custom post types
 *
 * @package   Foo_Plugin_Base
 * @version   1.0.0
 * @author    Brad Vincent
 * @copyright Copyright (c) 2014, Brad Vincent
 * @license   http://opensource.org/licenses/gpl-2.0.php GPL v2 or later
 * @link      https://github.com/fooplugins/Foo_Plugin_Base
 */

/*
    Copyright 2014 Brad Vincent (fooplugins.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if ( !class_exists( 'Foo_Plugin_Metaboxes_v1' ) ) {
    /**
     * Easily create metaboxes for a custom post type
     *
     * @since   1.0.0
     *
     * @package Foo_Plugin_Base
     * @author  Brad Vincent
     */
    class Foo_Plugin_Metaboxes_v1 {

        private $custom_post_type;
        private $plugin_version;

        function __construct($custom_post_type, $plugin_version, $plugin_textdomain) {
            $this->custom_post_type = $custom_post_type;
            $this->plugin_version = $plugin_version;

            if ( is_admin() ) {
                //add metaboxes to the custom post type
                add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );

                //save extra post data from the metaboxes
                add_action( 'save_post', array( $this, 'save_post_data' ) );

                //add scripts used by metaboxes
                add_action( 'admin_enqueue_scripts', array( $this, 'include_required_scripts' ) );

                new Foo_Plugin_Metabox_Type_Visibility_v1( $custom_post_type, $plugin_version, $plugin_textdomain, $this );
            }
        }

        function add_meta_boxes() {
            $metaboxes = $this->get_metabox_config();

//            $metaboxes[] = array(
//                'id' => 'foogallery_items',
//                'title' => __( 'Gallery Items', 'foogallery' ),
//                'fields' => array(),
//                'context' => 'normal',
//                'priority' => 'high'
//            );

            if ( false !== $metaboxes ) {
                foreach( $metaboxes as $metabox ) {
                    add_meta_box(
                        $this->custom_post_type . '_' . $metabox['id'],
                        $metabox['title'],
                        array( $this, 'render_metabox_fields' ),
                        $this->custom_post_type,
                        $metabox['context'],
                        $metabox['priority'],
                        $metabox
                    );
                }
            }
        }

        function get_metabox_config() {
            global $post;
            return apply_filters( $this->custom_post_type . '_metaboxes', false, $post );
        }

        function is_field_type_being_used( $type ) {
            $metaboxes = $this->get_metabox_config();
            if ( false !== $metaboxes ) {
                foreach ( $metaboxes as $metabox ) {
                    $fields = isset( $metabox['fields'] ) ? $metabox['fields'] : array();
                    foreach ( $fields as $field ) {
                        if ( $type === $field['type'] ) {
                            return true;
                        }
                    }
                }
            }

            return false;
        }

        function save_post_data( $post_id ) {
            // check autosave
            if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
                return $post_id;
            }

            $metaboxes = $this->get_metabox_config();
            if ( false !== $metaboxes ) {
                foreach ( $metaboxes as $metabox ) {
                    $nonce_key = $this->custom_post_type . '_' . $metabox['id'];

                    // verify nonce
                    if ( array_key_exists( $nonce_key . '_nonce', $_POST ) &&
                         wp_verify_nonce( $_POST[$nonce_key . '_nonce'], $nonce_key )
                    ) {
                        //if we get here, we are dealing with the correct metabox and custom post type

                        $field_meta_key = $this->get_post_meta_key( $metabox );
                        $data = isset( $_POST[$field_meta_key] ) ? $_POST[$field_meta_key] : array();

                        $data = apply_filters( $nonce_key . '_save_post_data', $data, $metabox );
                        update_post_meta( $post_id, $field_meta_key, $data );

                        do_action( $nonce_key . '_after_save_post', $post_id, $data, $metabox );
                    }
                }
            }
        }

        function include_required_scripts() {
            //only include scripts if we on the foogallery page
            if ( $this->custom_post_type === foo_current_screen_post_type() ) {

                $base_url = plugins_url( '', dirname(__FILE__) );

                //include some default styles
                $url = $base_url . '/css/admin-metabox.css';
                wp_enqueue_style( 'foopluginbase-metabox-default', $url, array(), $this->plugin_version );

                //include some default javascript
                $url = $base_url . '/js/admin-metabox.js';
                wp_enqueue_script( 'foopluginbase-metabox-default', $url, array( 'jquery' ), $this->plugin_version );

                if ( $this->is_field_type_being_used( 'colorpicker' ) ) {
                    //spectrum needed for the colorpicker field
                    $url = $base_url . '/js/spectrum.js';
                    wp_enqueue_script( 'foopluginbase-metabox-spectrum', $url, array( 'jquery' ), $this->plugin_version );
                    $url = $base_url . '/css/spectrum.css';
                    wp_enqueue_style( 'foopluginbase-metabox-spectrum', $url, array(), $this->plugin_version );
                }
            }
        }

        function render_metabox_fields( $post, $callback_args ) {
            $metabox = $callback_args['args'];
            $metabox_key = $this->custom_post_type . '_metabox_' . $metabox['id'];
            $nonce_key = $this->custom_post_type . '_' . $metabox['id'];
            ?>
            <input type="hidden" name="<?php echo $nonce_key; ?>_nonce" id="<?php echo $nonce_key; ?>_nonce" value="<?php echo wp_create_nonce( $nonce_key ); ?>"/>
            <table class="foo_metabox_table <?php echo $metabox_key; ?>">
                <tbody>
                <?php

                do_action( $metabox_key, $post, $metabox );

                $fields = isset( $metabox['fields'] ) ? $metabox['fields'] : array();
                foreach ( $fields as $field ) {

                    //allow the field to be overridden
                    $field = apply_filters( $metabox_key . '_field', $field, $post, $metabox );
                    $field_type = isset( $field['type'] ) ? $field['type'] : 'text';
                    $field_id = "{$metabox_key}_{$field['id']}";
                    $field_class = "foo_metabox_field foo_metabox_field_{$field_type} {$metabox_key}_field {$field_id}";
                    if ( isset( $field['class'] ) ) {
                        $field_class = $field_class . ' ' . $field['class'];
                    }
                    $data_show = $display = '';
                    if ( isset( $field['show_when'] ) ) {
                        $display = ' style="display:none" ';

                        $data_show = ' data-show-field="' . $this->custom_post_type . '_metabox_' . $metabox['id'] . '_' . $field['show_when']['field'] . '"';
                        $data_show .= ' data-show-value="' . $field['show_when']['value'] . '" ';

                        $field_show_value = $this->get_post_meta_value( $post, $metabox, $field['show_when']['field'], false );
                        if ( $field_show_value === $field['show_when']['value'] ) {
                            $display = '';
                        }
                    }
                    ?>
                    <tr data-field-type="<?php echo esc_attr( $field_type ); ?>" <?php echo $data_show . $display; ?>class="<?php echo esc_attr( $field_class ); ?>"><?php
                    if ( isset( $field['label'] ) ) { ?>
                        <td class="foo_metabox_field_label">
                            <label for="<?php echo $metabox_key . '_' . $field['id']; ?>"><?php echo $field['label']; ?></label>
                        </td>
                        <td class="foo_metabox_field_input"><?php
                    } else {
                        ?><td class="foo_metabox_field_input" colspan="2"><?php
                    }
                        //render the field
                    $this->render_field( $field, $post, $metabox );

                    ?></td></tr><?php
                }?>
                </tbody>
            </table>
        <?php
        }

        function has_post_meta( $post, $metabox ) {
            $post_meta = $this->get_post_meta( $post->ID, $metabox );
            return empty( $post_meta );
        }

        function get_post_meta_key( $metabox ) {
            return isset( $metabox['meta_key'] ) ? $metabox['meta_key'] : $metabox['id'];
        }

        function get_post_meta( $post_id, $metabox ) {
            $meta_key = $this->get_post_meta_key( $metabox );
            return get_post_meta( $post_id, $meta_key, true );
        }

        function get_post_meta_value( $post, $metabox, $key, $default ) {
            $post_meta = $this->get_post_meta( $post->ID, $metabox );
            if ( is_array( $post_meta ) && array_key_exists( $key, $post_meta ) ) {
                return $post_meta[$key];
            }

            return $default;
        }

        /**
         * Renders a field into a metabox
         *
         * @param array $field
         * @param       $post WP_Post
         * @param       $metabox
         */
        function render_field( $field = array(), $post, $metabox ) {
            $metabox_key = $this->custom_post_type . '_metabox_' . $metabox['id'];
            $field_type = isset( $field['type'] ) ? $field['type'] : 'text';
            $field_id = "{$metabox_key}_{$field['id']}";

            $field_default = isset( $field['default'] ) ? $field['default'] : null;
            $field_value = $this->get_post_meta_value( $post, $metabox, $field['id'], $field_default );
            $field_meta_key = $this->get_post_meta_key( $metabox );

            $field_choices = isset( $field['choices'] ) ? $field['choices'] : array();
            $field_choices = apply_filters( $metabox_key . '_field_choices', $field_choices, $field, $post, $metabox );

            $field_attributes = isset( $field['attributes'] ) ? $field['attributes'] : array();
            $field_attributes = array_map( 'esc_attr', $field_attributes );
            $field_attributes_html = '';
            foreach ( $field_attributes as $name => $value ) {
                $field_attributes_html .= " $name=" . '"' . $value . '"';
            }
            $field_attributes_html = empty( $field_attributes_html ) ? '' : ' ' . $field_attributes_html;

            //allow for customization before
            do_action( 'foo_metabox_field_before', $field, $post, $metabox, $field_meta_key, $field_value, $field_choices, $field_attributes_html );

            switch ( $field_type ) {

                case 'html':
                    echo $field['desc'];
                    $field['desc'] = '';
                    break;

                case 'section':
                    echo '<h4>' . $field['desc'] . '</h4>';
                    $field['desc'] = '';
                    break;

                case 'help':
                    echo '<p>' . $field['desc'] . '</p>';
                    $field['desc'] = '';
                    break;

                case 'checkbox':
                    if ( ! $this->has_post_meta( $post, $metabox ) && $field_default == 'on' ) {
                        $field_value = 'on';
                    } else {
                        $field_value = '';
                    }

                    $checked = 'on' === $field_value ? ' checked="checked"' : '';
                    echo '<input type="checkbox" id="' . $field_id . '" name="' . $field_meta_key . '[' . $field['id'] . ']" value="on"' . $checked . $field_attributes_html . ' />';
                    break;

                case 'select':
                    echo '<select id="' . $field_id . '" name="' . $field_meta_key . '[' . $field['id'] . ']"' . $field_attributes_html . '>';
                    foreach ( $field_choices as $value => $label ) {
                        $selected = '';
                        if ( $field_value == $value ) {
                            $selected = ' selected="selected"';
                        }
                        echo '<option ' . $selected . ' value="' . $value . '">' . $label . '</option>';
                    }

                    echo '</select>';
                    break;

                case 'radio':
                    $i = 0;
                    $spacer = isset( $field['inline'] ) && true === $field['inline'] ? '<span class="foo_metabox_field_choice_inline"></span>' : '<br />';
                    foreach ( $field_choices as $value => $label ) {
                        $selected = '';
                        if ( $field_value == $value ) {
                            $selected = ' checked="checked"';
                        }
                        echo '<input' . $selected . ' type="radio" name="' . $field_meta_key . '[' . $field['id'] . ']"  id="' . $field_id . '_' . $i . '" value="' . $value . '"> <label for="' . $field_id . '_' . $i . '">' . $label . '</label>';
                        if ( $i < count( $field_choices ) - 1 ) {
                            echo $spacer;
                        }
                        $i++;
                    }
                    break;

                case 'textarea':
                    echo '<textarea id="' . $field_id . '" name="' . $field_meta_key . '[' . $field['id'] . ']"' . $field_attributes_html . '>' . esc_attr( $field_value ) . '</textarea>';

                    break;

                case 'text':
                    echo '<input type="text" id="' . $field_id . '" name="' . $field_meta_key . '[' . $field['id'] . ']" value="' . esc_attr( $field_value ) . '"' . $field_attributes_html . '/>';

                    break;

                case 'colorpicker':

                    $opacity_attribute = isset( $field['opacity'] ) ? ' data-show-alpha="true"' : '';

                    echo '<input ' . $opacity_attribute . ' class="colorpicker" type="text" id="' . $field_id . '" name="' . $field_meta_key . '[' . $field['id'] . ']" value="' . esc_attr( $field_value ) . '"' . $field_attributes_html . '/>';

                    break;

                case 'number':
                    $min = isset($min) ? $min : 0;
                    $step = isset($step) ? $step : 1;
                    echo '<input type="number" id="' . $field_id . '" name="' . $field_meta_key . '[' . $field['id'] . ']" " value="' . esc_attr( $field_value ) . '"' . $field_attributes_html . '/>';

                    break;

                case 'checkboxlist':
                    $i = 0;
                    $input_name = $field_meta_key . '[' . $field['id'] . '][]';
                    $spacer = isset( $field['inline'] ) && true === $field['inline'] ? '<span class="foo_metabox_field_choice_inline"></span>' : '<br />';
                    foreach ( $field_choices as $value => $label ) {

                        $checked = '';
                        if ( is_array( $field_value ) && in_array( $value, $field_value ) ) {
                            $checked = 'checked="checked"';
                        }

                        echo '<input ' . $checked . ' type="checkbox" name="' . $input_name . '" id="' . $field_id . '_' . $i . '" value="' . $value . '"> <label for="' . $field_id . '_' . $i . '">' . $label . '</label>';
                        if ( $i < count( $field_choices ) - 1 ) {
                            echo $spacer;
                        }
                        $i++;
                    }

                    break;
                case 'icon':
                    $i = 0;
                    $input_name = $field_meta_key . '[' . $field['id'] . ']';
                    $icon_html = '';
                    foreach ( $field_choices as $value => $icon ) {
                        $selected = ( $field_value == $value ) ? ' checked="checked"' : '';
                        $icon_html .= '<input style="display:none" name="' . $input_name. '" id="' . $field_id . '_' . $i . '" ' . $selected . ' type="radio" value="' . $value . '" tabindex="' . $i . '"/>';
                        $title = $icon['label'];
                        $img = $icon['img'];
                        $icon_html .= '<label for="' . $field_id . '_' . $i . '" title="' . $title . '"><img src="' . $img . '" /></label>';
                        $i++;
                    }
                    echo $icon_html;
                    break;

                case 'palette':
                    $i = 0;
                    $input_name = $field_meta_key . '[' . $field['id'] . ']';
                    $icon_html = '';
                    foreach ( $field_choices as $value => $icon ) {
                        $selected = ( $field_value == $value ) ? ' checked="checked"' : '';
                        $icon_html .= '<input style="display:none" name="' . $input_name. '" id="' . $field_id . '_' . $i . '" ' . $selected . ' type="radio" value="' . $value . '" tabindex="' . $i . '"/>';
                        $title = $icon['label'];
                        $color = $icon['color'];
                        $icon_html .= '<label style="background-color:' . $color . '" for="' . $field_id . '_' . $i . '" title="' . $title . '"></label>';
                        $i++;
                    }
                    echo $icon_html;
                    break;

                default:
                    //action for a field type
                    do_action( 'foo_metabox_field_' . $field_type, $field, $post, $metabox, $field_meta_key, $field_value, $field_choices, $field_attributes_html );

                    //action for a custom post type and field type
                    do_action( 'foo_metabox_field_' . $this->custom_post_type . '_' . $field_type, $field, $post, $metabox, $field_meta_key, $field_value, $field_choices, $field_attributes_html );
                    break;
            }

            if ( isset( $field['suffix'] ) ) {
                echo $field['suffix'];
            }

            if ( isset( $field['desc'] ) ) {
                echo '<small>' . $field['desc'] . '</small>';
            }

            //allow for more customization
            do_action( 'foo_metabox_field_after', $field, $post, $metabox, $field_meta_key, $field_value, $field_choices, $field_attributes_html );
        }
    }
}