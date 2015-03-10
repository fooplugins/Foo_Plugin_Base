<?php
/**
 * Visibility Field for FoooPluginBase Metaboxes
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

if ( ! class_exists( 'Foo_Plugin_Metabox_Type_Visibility_v1' ) ) {

	/**
	 * Visibility Field for FoooPluginBase Metaboxes
	 *
	 * @since   1.0.0
	 *
	 * @package Foo_Plugin_Base
	 * @author  Brad Vincent
	 */
	class Foo_Plugin_Metabox_Type_Visibility_v1 {

		private $custom_post_type;
		private $plugin_version;
		private $plugin_textdomain;
		private $metabox_instance;

		function __construct ( $custom_post_type, $plugin_version, $plugin_textdomain, $metabox_instance ) {
			$this->custom_post_type  = $custom_post_type;
			$this->plugin_version    = $plugin_version;
			$this->plugin_textdomain = $plugin_textdomain;
			$this->metabox_instance  = $metabox_instance;

			if ( is_admin() ) {
				add_action( 'foo_metabox_field_visibility', array( $this, 'render_field' ), 10, 7 );

				//add scripts used by visibility field
				add_action( 'admin_enqueue_scripts', array( $this, 'include_required_scripts' ) );

				//ajax request for visibility field options
				add_action( 'wp_ajax_metabox_visibility_conditions_options', array(
					$this,
					'visibility_conditions_options'
				) );
			}
		}

		function include_required_scripts () {
			//only include scripts if we on the foogallery page
			if ( $this->custom_post_type === foo_current_screen_post_type() ) {

				$base_url = plugins_url( '', dirname( __FILE__ ) );

				if ( $this->metabox_instance->is_field_type_being_used( 'visibility' ) ) {
					$url = $base_url . '/css/admin-metabox-visibility.css';
					wp_enqueue_style( 'foopluginbase-metabox-visibility', $url, array(), $this->plugin_version );

					//include some default javascript
					$url = $base_url . '/js/admin-metabox-visibility.js';
					wp_enqueue_script( 'foopluginbase-metabox-visibility', $url, array( 'jquery' ), $this->plugin_version );
				}
			}
		}

		/**
		 * Renders the visibility field into a metabox
		 *
		 * @param array $field
		 * @param       $post WP_Post
		 * @param       $metabox
		 */
		function render_field ( $field, $post, $metabox, $field_meta_key, $field_value, $field_choices, $field_attributes_html ) {
			$conditions = array();

			if ( isset( $field_value['major'] ) ) {
				for ( $i = 0; $i < count( $field_value['major'] ); $i ++ ) {
					if ( isset( $field_value['major'][ $i ] ) && isset( $field_value['minor'][ $i ] ) ) {
						$conditions[] = array(
							'major' => $field_value['major'][ $i ],
							'minor' => $field_value['minor'][ $i ]
						);
					}
				}
			}

			if ( 0 === count( $conditions ) ) {
				$conditions[] = array( 'major' => '', 'minor' => '' );
			}

			$condition_count = count( $conditions );
			$condition_counter = 0;

			foreach ( $conditions as $condition ) {
				?>
				<div class="foo_metabox_field_visibility_rule">
					<div class="selection">
						<select class="conditions-rule-major" name="<?php echo $field_meta_key . '[' . $field['id'] . '][major][]'; ?>">
							<option value="" <?php selected( "", $condition['major'] ); ?>><?php echo esc_html_x( '-- select --', $this->plugin_textdomain ); ?></option>
							<optgroup label="<?php esc_html_e( 'General', $this->plugin_textdomain ); ?>">
								<option value="user" <?php selected( "user", $condition['major'] ); ?>><?php echo esc_html_x( 'User', $this->plugin_textdomain ); ?></option>
								<?php if ( current_theme_supports( 'post-formats' ) ) { ?>
									<option value="post_format" <?php selected( "post_format", $condition['major'] ); ?>><?php echo esc_html_x( 'Post Format', $this->plugin_textdomain ); ?></option>
								<?php } ?>
								<option value="page_template" <?php selected( "page_template", $condition['major'] ); ?>><?php echo esc_html_x( 'Page Template', $this->plugin_textdomain ); ?></option>
								<option value="page_type" <?php selected( "page_type", $condition['major'] ); ?>><?php echo esc_html_x( 'Page Type', $this->plugin_textdomain ); ?></option>
								<option value="theme" <?php selected( "theme", $condition['major'] ); ?>><?php echo esc_html_x( 'Theme', $this->plugin_textdomain ); ?></option>
								<option value="device" <?php selected( "device", $condition['major'] ); ?>><?php echo esc_html_x( 'Device', $this->plugin_textdomain ); ?></option>
								<option value="browser" <?php selected( "browser", $condition['major'] ); ?>><?php echo esc_html_x( 'Browser', $this->plugin_textdomain ); ?></option>
							</optgroup>
							<optgroup label="<?php esc_html_e( 'Post Type', $this->plugin_textdomain ); ?>">
								<?php
								$post_types = get_post_types( array( 'public' => true ), 'objects' );
								foreach ( $post_types as $post_type ) { ?>
									<option value="<?php echo esc_attr( 'post_type-' . $post_type->name ); ?>" <?php selected( 'post_type-' . $post_type->name, $condition['major'] ); ?>><?php echo esc_html( $post_type->labels->singular_name ); ?></option>
								<?php
								} ?>
							</optgroup>
							<optgroup label="<?php esc_html_e( 'Taxonomy', $this->plugin_textdomain ); ?>">
								<?php
								$taxonomies = get_taxonomies( array( 'public' => true ), 'objects' );
								foreach ( $taxonomies as $taxonomy ) { ?>
									<option value="<?php echo esc_attr( 'taxonomy-' . $taxonomy->name ); ?>" <?php selected( 'taxonomy-' . $taxonomy->name, $condition['major'] ); ?>><?php echo esc_html( $taxonomy->labels->singular_name ); ?></option>
								<?php
								} ?>
							</optgroup>
							<optgroup label="<?php esc_html_e( 'User', $this->plugin_textdomain ); ?>">
								<option value="author" <?php selected( "author", $condition['major'] ); ?>><?php echo esc_html_x( 'Author', $this->plugin_textdomain ); ?></option>
								<option value="role" <?php selected( "role", $condition['major'] ); ?>><?php echo esc_html_x( 'Role', $this->plugin_textdomain ); ?></option>
							</optgroup>
							<optgroup label="<?php esc_html_e( 'Archives', $this->plugin_textdomain ); ?>">
								<?php
								foreach ( $taxonomies as $taxonomy ) { ?>
									<option value="<?php echo esc_attr( 'taxonomy-archive-' . $taxonomy->name ); ?>" <?php selected( 'taxonomy-' . $taxonomy->name, $condition['major'] ); ?>><?php echo esc_html( $taxonomy->labels->singular_name ); ?></option>
								<?php
								} ?>
								<option value="date" <?php selected( "date", $condition['major'] ); ?>><?php echo esc_html_x( 'Date', $this->plugin_textdomain ); ?></option>
							</optgroup>
						</select>
						<?php _ex( 'is', $this->plugin_textdomain ); ?>
						<select class="conditions-rule-minor" name="<?php echo $field_meta_key . '[' . $field['id'] . '][minor][]'; ?>" <?php if ( ! $condition['major'] ) { ?> disabled="disabled"<?php } ?> data-loading-text="<?php esc_attr_e( 'Loading...', $this->plugin_textdomain ); ?>">
							<?php $this->visibility_conditions_options_echo( $condition['major'], $condition['minor'] ); ?>
						</select>
						<a href="#" title="<?php esc_html_e( 'Delete Condition', $this->plugin_textdomain ); ?>" class="delete-condition"></a>
					</div>
					<div <?php echo ( $condition_counter === $condition_count - 1 ) ? 'style="display:none"' : ''; ?> class="condition-seperator">
						<hr class="left-divider"/>
						<?php echo esc_html_x( 'or', 'Shown between widget visibility conditions.', $this->plugin_textdomain ); ?>
						<hr class="right-divider"/>
					</div>
				</div><!-- .condition -->
				<?php
				$condition_counter ++;
			}
			?>
			<div class="foo_metabox_field_visibility_control">
				<input type="submit" class="add-condition" name="add_condition" value="<?php esc_html_e( 'Add a new condition', $this->plugin_textdomain ); ?>" class="button">
			</div>
		<?php
		}

		/**
		 * Provided a second level of granularity for widget conditions.
		 */
		function visibility_conditions_options_echo ( $major = '', $minor = '' ) {

			if ( foo_starts_with( $major, 'post_type-' ) ) {

				$post_type        = str_replace( 'post_type-', '', $major );
				$post_type_object = get_post_type_object( $post_type );

				?>
				<option value=""><?php printf( __( 'Any %s', $this->plugin_textdomain ), $post_type_object->labels->singular_name ); ?></option>
				<?php

				if ( $post_type_object->hierarchical ) {

					$options = wp_dropdown_pages( array( 'echo' => false, 'post_type' => $post_type ) );
					echo str_replace( ' value="' . esc_attr( $minor ) . '"', ' value="' . esc_attr( $minor ) . '" selected="selected"', preg_replace( '/<\/?select[^>]*?>/i', '', $options ) );

				} else {

					$posts = get_posts( array( 'post_type' => $post_type, 'posts_per_page' => 250 ) );

					foreach ( $posts as $post ) {
						?>
						<option value="<?php echo esc_attr( $post->ID ); ?>" <?php selected( $post->ID, $minor ); ?>><?php echo esc_html( $post->post_title ); ?></option>
					<?php
					}

				}

			} else if ( foo_starts_with( $major, 'taxonomy-' ) ) {

				$taxonomy        = str_replace( 'taxonomy-', '', $major );
				$taxonomy_object = get_taxonomy( $taxonomy );

				?>
				<option value=""><?php printf( __( 'Any %s', $this->plugin_textdomain ), $taxonomy_object->labels->singular_name ); ?></option>
				<?php

				$terms = get_terms( array( $taxonomy ), array( 'number' => 250, 'hide_empty' => false ) );
				foreach ( $terms as $term ) {
					?>
					<option value="<?php echo esc_attr( $taxonomy . '_tax_' . $term->term_id ); ?>" <?php selected( $taxonomy . '_tax_' . $term->term_id, $minor ); ?>><?php echo esc_html( $term->name ); ?></option>
				<?php
				}

			} else {
				switch ( $major ) {
					case 'category':
						?>
						<option value=""><?php _e( 'All category pages', $this->plugin_textdomain ); ?></option>
						<?php

						$categories = get_categories( array(
							'number'  => 1000,
							'orderby' => 'count',
							'order'   => 'DESC'
						) );
						usort( $categories, array( __CLASS__, 'strcasecmp_name' ) );

						foreach ( $categories as $category ) {
							?>
							<option value="<?php echo esc_attr( $category->term_id ); ?>" <?php selected( $category->term_id, $minor ); ?>><?php echo esc_html( $category->name ); ?></option>
						<?php
						}
						break;
					case 'user':
						?>
						<option value="" <?php selected( '', $minor ); ?>><?php _e( 'Logged In', $this->plugin_textdomain ); ?></option>
						<option value="loggedout" <?php selected( 'loggedout', $minor ); ?>><?php _e( 'Logged Out', $this->plugin_textdomain ); ?></option>
						<?php
						break;
					case 'author':
						?>
						<option value=""><?php _e( 'All author pages', $this->plugin_textdomain ); ?></option>
						<?php

						foreach ( get_users( array( 'orderby' => 'name', 'exclude_admin' => true ) ) as $author ) {
							?>
							<option value="<?php echo esc_attr( $author->ID ); ?>" <?php selected( $author->ID, $minor ); ?>><?php echo esc_html( $author->display_name ); ?></option>
						<?php
						}
						break;
					case 'role':
						global $wp_roles;

						foreach ( $wp_roles->roles as $role_key => $role ) {
							?>
							<option value="<?php echo esc_attr( $role_key ); ?>" <?php selected( $role_key, $minor ); ?> ><?php echo esc_html( $role['name'] ); ?></option>
						<?php
						}
						break;
					case 'date':
						?>
						<option value="" <?php selected( '', $minor ); ?>><?php _e( 'All date archives', $this->plugin_textdomain ); ?></option>
						<option value="day"<?php selected( 'day', $minor ); ?>><?php _e( 'Daily archives', $this->plugin_textdomain ); ?></option>
						<option value="month"<?php selected( 'month', $minor ); ?>><?php _e( 'Monthly archives', $this->plugin_textdomain ); ?></option>
						<option value="year"<?php selected( 'year', $minor ); ?>><?php _e( 'Yearly archives', $this->plugin_textdomain ); ?></option>
						<?php
						break;
					case 'post_format':
						if ( current_theme_supports( 'post-formats' ) ) {
							$post_formats = get_theme_support( 'post-formats' );

							if ( $post_formats && is_array( $post_formats[0] ) ) {
								foreach ( $post_formats[0] as $post_format ) {
									?>
									<option
										value="<?php echo esc_attr( $post_format ); ?>" <?php selected( $post_format, $minor ); ?>><?php echo esc_html( $post_format ); ?></option>
								<?php
								}
							}
						}
						break;
					case 'page_template':
						$templates = get_page_templates();
						foreach ( $templates as $template ) {
							?>
							<option value="<?php echo esc_attr( $template ); ?>" <?php selected( $template, $minor ); ?>><?php echo esc_html( $template ); ?></option>
						<?php
						}
						break;
					case 'page_type':
						?>
						<option value="front" <?php selected( 'front', $minor ); ?>><?php _e( 'Front page', $this->plugin_textdomain ); ?></option>
						<option value="posts" <?php selected( 'posts', $minor ); ?>><?php _e( 'Posts page', $this->plugin_textdomain ); ?></option>
						<option value="archive" <?php selected( 'archive', $minor ); ?>><?php _e( 'Archive page', $this->plugin_textdomain ); ?></option>
						<option value="404" <?php selected( '404', $minor ); ?>><?php _e( '404 error page', $this->plugin_textdomain ); ?></option>
						<option value="search" <?php selected( 'search', $minor ); ?>><?php _e( 'Search results', $this->plugin_textdomain ); ?></option>
						<?php
						break;
					case 'theme':
						$themes = wp_get_themes();
						foreach ( $themes as $theme ) {
							?>
							<option value="<?php echo esc_attr( $theme->name ); ?>" <?php selected( $theme->name, $minor ); ?>><?php echo esc_html( $theme->name ); ?></option>
						<?php
						}
						break;
					case 'device':
						?>
						<option value="" <?php selected( '', $minor ); ?>><?php _e( 'Desktop', $this->plugin_textdomain ); ?></option>
						<option value="mobile" <?php selected( 'mobile', $minor ); ?>><?php _e( 'Mobile', $this->plugin_textdomain ); ?></option>
						<?php
						break;
					case 'browser':
						?>
						<option value="ie" <?php selected( 'ie', $minor ); ?>><?php _e( 'IE', $this->plugin_textdomain ); ?></option>
						<option value="opera" <?php selected( 'opera', $minor ); ?>><?php _e( 'Opera', $this->plugin_textdomain ); ?></option>
						<option value="chrome" <?php selected( 'chrome', $minor ); ?>><?php _e( 'Chrome', $this->plugin_textdomain ); ?></option>
						<option value="safari" <?php selected( 'safari', $minor ); ?>><?php _e( 'Safari', $this->plugin_textdomain ); ?></option>
						<option value="gecko" <?php selected( 'gecko', $minor ); ?>><?php _e( 'FreFox', $this->plugin_textdomain ); ?></option>
						<?php
						break;
				}
			}
		}

		/**
		 * This is the AJAX endpoint for the second level of conditions.
		 */
		function visibility_conditions_options () {
			$this->visibility_conditions_options_echo( $_REQUEST['major'], isset( $_REQUEST['minor'] ) ? $_REQUEST['minor'] : '' );
			die;
		}

		/**
		 * Determine the visibility based on the conditions set by the user.
		 *
		 * @param array $field_value The post_meta value saved
		 * @return bool false to hide.
		 */
		public static function determine_visibility( $field_value ) {
			global $wp_query;

			if ( empty( $field_value ) ) {
				return false;
			}

			$conditions = array();

			if ( isset( $field_value['major'] ) ) {
				for ( $i = 0; $i < count( $field_value['major'] ); $i ++ ) {
					$conditions[] = array(
						'major' => $field_value['major'][ $i ],
						'minor' => $field_value['minor'][ $i ]
					);
				}
			} else {
				return false;
			}

			$condition_result = false;

			foreach ( $conditions as $condition ) {
				$major = $condition['major'];
				$minor = $condition['minor'];

				if ( foo_starts_with( $major, 'post_type-' ) ) {

					$post_type = substr( $major, 10 );
					$queried_post_type = get_query_var('post_type');
					if ( '' === $minor ) {
						$condition_result = is_singular( $post_type );
					} else {

					}

				} else if ( foo_starts_with( $condition['major'], 'taxonomy-archive-' ) ) {

				} else if ( foo_starts_with( $condition['major'], 'taxonomy-' ) ) {

				} else {
					switch ( $condition['major'] ) {
						case 'date':
							switch ( $condition['minor'] ) {
								case '':
									$condition_result = is_date();
									break;
								case 'month':
									$condition_result = is_month();
									break;
								case 'day':
									$condition_result = is_day();
									break;
								case 'year':
									$condition_result = is_year();
									break;
							}
							break;
						case 'page_type':

							switch ( $condition['minor'] ) {
								case '404':
									$condition_result = is_404();
									break;
								case 'search':
									$condition_result = is_search();
									break;
								case 'archive':
									$condition_result = is_archive();
									break;
								case 'posts':
									$condition_result = $wp_query->is_posts_page;
									break;
								case 'home':
									$condition_result = is_home();
									break;
								case 'front':
									if ( current_theme_supports( 'infinite-scroll' ) )
										$condition_result = is_front_page();
									else {
										$condition_result = is_front_page() && !is_paged();
									}
									break;
								default:
									if ( substr( $condition['minor'], 0, 10 ) == 'post_type-' )
										$condition_result = is_singular( substr( $condition['minor'], 10 ) );
									else {
										// $condition['minor'] is a page ID
										$condition_result = is_page( $condition['minor'] );
									}
									break;
							}
							break;
						case 'tag':
							if ( ! $condition['minor'] && is_tag() )
								$condition_result = true;
							else if ( is_singular() && $condition['minor'] && has_tag( $condition['minor'] ) )
								$condition_result = true;
							else {
								$tag = get_tag( $condition['minor'] );

								if ( $tag && is_tag( $tag->slug ) )
									$condition_result = true;
							}
							break;
						case 'category':
							if ( ! $condition['minor'] && is_category() )
								$condition_result = true;
							else if ( is_category( $condition['minor'] ) )
								$condition_result = true;
							else if ( is_singular() && $condition['minor'] && in_array( 'category', get_post_taxonomies() ) &&  has_category( $condition['minor'] ) )
								$condition_result = true;
							break;
						case 'loggedin':
							$condition_result = is_user_logged_in();
							if ( 'loggedin' !== $condition['minor'] ) {
								$condition_result = ! $condition_result;
							}
							break;
						case 'author':
							$post = get_post();
							if ( ! $condition['minor'] && is_author() )
								$condition_result = true;
							else if ( $condition['minor'] && is_author( $condition['minor'] ) )
								$condition_result = true;
							else if ( is_singular() && $condition['minor'] && $condition['minor'] == $post->post_author )
								$condition_result = true;
							break;
						case 'role':
							if( is_user_logged_in() ) {
								global $current_user;
								get_currentuserinfo();

								$user_roles = $current_user->roles;

								if( in_array( $condition['minor'], $user_roles ) ) {
									$condition_result = true;
								} else {
									$condition_result = false;
								}

							} else {
								$condition_result = false;
							}
							break;
						case 'taxonomy':
							$term = explode( '_tax_', $condition['minor'] ); // $term[0] = taxonomy name; $term[1] = term id

							if ( isset( $term[1] ) && is_tax( $term[0], $term[1] ) )
								$condition_result = true;
							else if ( isset( $term[1] ) && is_singular() && $term[1] && has_term( $term[1], $term[0] ) )
								$condition_result = true;
							else if ( is_singular() && $post_id = get_the_ID() ){
								$terms = get_the_terms( $post_id, $condition['minor'] ); // Does post have terms in taxonomy?
								if( $terms & ! is_wp_error( $terms ) ) {
									$condition_result = true;
								}
							}
							break;
					}
				}



				if ( $condition_result )
					break;
			}

			return $condition_result;
		}
	}
}
