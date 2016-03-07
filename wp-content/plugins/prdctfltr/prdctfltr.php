<?php
/*
Plugin Name: WooCommerce Product Filter
Plugin URI: http://www.mihajlovicnenad.com/product-filter
Description: Advanced product filter for any Wordpress template! - mihajlovicnenad.com
Author: Mihajlovic Nenad
Version: 5.3.6
Author URI: http://www.mihajlovicnenad.com
*/

	class WC_Prdctfltr {

		public static $version = '5.3.6';

		public static $dir;
		public static $path;
		public static $url_path;
		public static $settings;

		public static function init() {
			$class = __CLASS__;
			new $class;
		}

		function __construct() {

			global $prdctfltr_global;

			self::$dir = trailingslashit( dirname( __FILE__ ) );
			self::$path = trailingslashit( plugin_dir_path( __FILE__ ) );
			self::$url_path = plugins_url( trailingslashit( basename( self::$dir ) ) );

			self::$settings['permalink_structure'] = get_option( 'permalink_structure' );
			self::$settings['wc_settings_prdctfltr_disable_scripts'] = get_option( 'wc_settings_prdctfltr_disable_scripts', array() );
			self::$settings['wc_settings_prdctfltr_ajax_js'] = get_option( 'wc_settings_prdctfltr_ajax_js', '' );
			self::$settings['wc_settings_prdctfltr_custom_tax'] = get_option( 'wc_settings_prdctfltr_custom_tax', 'no' );
			self::$settings['wc_settings_prdctfltr_enable'] = get_option( 'wc_settings_prdctfltr_enable', 'yes' );

			self::$settings['wc_settings_prdctfltr_enable_overrides'] = get_option( 'wc_settings_prdctfltr_enable_overrides', array( 'orderby', 'result-count' ) );

			foreach( self::$settings['wc_settings_prdctfltr_enable_overrides'] as $k => $v ) {
				self::$settings['wc_settings_prdctfltr_enable_overrides'][$k] = 'loop/' . $v . '.php';
			}

			self::$settings['wc_settings_prdctfltr_enable_action'] = get_option( 'wc_settings_prdctfltr_enable_action', '' );
			self::$settings['wc_settings_prdctfltr_default_templates'] = get_option( 'wc_settings_prdctfltr_default_templates', 'no' );
			self::$settings['wc_settings_prdctfltr_force_categories'] = get_option( 'wc_settings_prdctfltr_force_categories', 'no' );
			self::$settings['wc_settings_prdctfltr_instock'] = get_option( 'wc_settings_prdctfltr_instock', 'no' );
			self::$settings['wc_settings_prdctfltr_use_ajax'] = get_option( 'wc_settings_prdctfltr_use_ajax', 'no' );
			self::$settings['wc_settings_prdctfltr_ajax_class'] = get_option( 'wc_settings_prdctfltr_ajax_class', '' );
			self::$settings['wc_settings_prdctfltr_ajax_category_class'] = get_option( 'wc_settings_prdctfltr_ajax_category_class', '' );
			self::$settings['wc_settings_prdctfltr_ajax_product_class'] = get_option( 'wc_settings_prdctfltr_ajax_product_class', '' );
			self::$settings['wc_settings_prdctfltr_ajax_pagination_class'] = get_option( 'wc_settings_prdctfltr_ajax_pagination_class', '' );
			self::$settings['wc_settings_prdctfltr_ajax_columns'] = get_option( 'wc_settings_prdctfltr_ajax_columns', '4' );
			self::$settings['wc_settings_prdctfltr_ajax_rows'] = get_option( 'wc_settings_prdctfltr_ajax_rows', '4' );
			self::$settings['wc_settings_prdctfltr_force_redirects'] = get_option( 'wc_settings_prdctfltr_force_redirects', 'no' );
			self::$settings['wc_settings_prdctfltr_force_emptyshop'] = get_option( 'wc_settings_prdctfltr_force_emptyshop', 'no' );
			self::$settings['wc_settings_prdctfltr_use_analytics'] = get_option( 'wc_settings_prdctfltr_use_analytics', 'no' );
			self::$settings['wc_settings_prdctfltr_shop_page_override'] = get_option( 'wc_settings_prdctfltr_shop_page_override', '' );

			self::$settings['wc_settings']['product_taxonomies'] = get_object_taxonomies( 'product' );
			self::$settings['range_js'] = array();

			if ( self::$settings['wc_settings_prdctfltr_enable'] == 'yes' ) {
				add_filter( 'woocommerce_locate_template', array( &$this, 'prdctrfltr_add_loop_filter' ), 10, 3 );
				add_filter( 'wc_get_template_part', array( &$this, 'prdctrfltr_add_filter' ), 10, 3 );
			}

			if ( self::$settings['wc_settings_prdctfltr_enable'] == 'no' && self::$settings['wc_settings_prdctfltr_default_templates'] == 'yes' ) {
				add_filter( 'woocommerce_locate_template', array( &$this, 'prdctrfltr_add_loop_filter_blank' ), 10, 3 );
				add_filter( 'wc_get_template_part', array( &$this, 'prdctrfltr_add_filter_blank' ), 10, 3 );
			}
			if ( self::$settings['wc_settings_prdctfltr_enable'] == 'action' && self::$settings['wc_settings_prdctfltr_enable_action'] !== '' ) {
				$curr_action = explode( ':', self::$settings['wc_settings_prdctfltr_enable_action'] );
				if ( isset( $curr_action[1] ) ) {
					$curr_action[1] = floatval( $curr_action[1] );
				}
				else {
					$curr_action[1] = 10;
				}
				add_filter( $curr_action[0], array( &$this, 'prdctfltr_get_filter' ), $curr_action[1] );
			}

			if ( !is_admin() && self::$settings['wc_settings_prdctfltr_force_categories'] == 'no' ) {
				if ( self::$settings['wc_settings_prdctfltr_force_redirects'] !== 'yes' && self::$settings['permalink_structure'] !== '' ) {
					add_action( 'template_redirect', array( &$this, 'prdctfltr_redirect' ), 999 );
				}
				if ( self::$settings['wc_settings_prdctfltr_force_emptyshop'] !== 'yes' ) {
					add_action( 'template_redirect',array( &$this, 'prdctfltr_redirect_empty_shop' ), 998 );
				}
			}

			if ( self::$settings['wc_settings_prdctfltr_use_ajax'] == 'yes' ) {
				add_filter( 'woocommerce_pagination_args', array( &$this, 'prdctfltr_pagination_filter' ), 999, 1 );
			}

			add_action( 'init', array( &$this, 'prdctfltr_text_domain' ), 1000 );
			add_action( 'wp_enqueue_scripts', array( &$this, 'prdctfltr_scripts' ) );
			add_action( 'wp_footer', array( &$this, 'localize_scripts' ) );
			add_filter( 'pre_get_posts', array( &$this, 'prdctfltr_wc_query' ), 999999, 1 );
			add_filter( 'parse_tax_query', array( &$this, 'prdctfltr_wc_tax' ), 999999, 1 );
			add_action( 'prdctfltr_filter_after', array( &$this, 'prdctfltr_add_css' ) );
			add_action( 'wp', array( &$this, 'prdctfltr_init' ) );
			add_action( 'prdctfltr_filter_hooks', array( &$this, 'prdctfltr_check_appearance' ), 10 );
			add_filter( 'woocommerce_shortcode_products_query', array( &$this, 'add_wc_shortcode_filter' ), 999999, 3 );

			if ( self::$settings['wc_settings_prdctfltr_use_analytics'] == 'yes' ) {
				add_action( 'wp_ajax_nopriv_prdctfltr_analytics', array( &$this, 'prdctfltr_analytics' ) );
				add_action( 'wp_ajax_prdctfltr_analytics', array( &$this, 'prdctfltr_analytics' ) );
			}

			add_action( 'prdctfltr_output', array( &$this, 'prdctfltr_get_filter' ), 10 );
			register_activation_hook( __FILE__, array( &$this, 'activate' ) );

			if ( !is_admin() ) {
				add_action( 'parse_query', array( &$this, 'prdctfltr_gets' ), 9999999, 1 );
			}

		}

		function prdctfltr_init() {
			$prdctfltr_global['is_shop'] = ( is_post_type_archive( 'product' ) || is_page( self::prdctfltr_wpml_get_id( wc_get_page_id( 'shop' ) ) ) ) ? true : false;
		}

		function prdctfltr_text_domain() {
			$dir = untrailingslashit( WP_LANG_DIR );
			load_plugin_textdomain( 'prdctfltr', false, $dir . '/plugins' );
		}

		function activate() {

			if ( false !== get_transient( 'prdctfltr_default' ) ) {
				delete_transient( 'prdctfltr_default' );
			}

			$active_presets = get_option( 'prdctfltr_templates', array() );

			if ( !empty( $active_presets ) && is_array( $active_presets ) ) {
				foreach( $active_presets as $k => $v ) {
					if ( false !== ( $transient = get_transient( 'prdctfltr_' . $k ) ) ) {
						delete_transient( 'prdctfltr_' . $k );
					}
				}
			}

		}

		function prdctfltr_scripts() {

			$curr_scripts = self::$settings['wc_settings_prdctfltr_disable_scripts'];

			wp_register_style( 'prdctfltr-main-css', self::$url_path .'lib/css/prdctfltr.css', false, self::$version );
			wp_enqueue_style( 'prdctfltr-main-css' );

			if ( !in_array( 'mcustomscroll', $curr_scripts ) ) {
				wp_register_style( 'prdctfltr-scrollbar-css', self::$url_path .'lib/css/jquery.mCustomScrollbar.css', false, self::$version );
				wp_enqueue_style( 'prdctfltr-scrollbar-css' );
				wp_register_script( 'prdctfltr-scrollbar-js', self::$url_path .'lib/js/jquery.mCustomScrollbar.concat.min.js', array( 'jquery' ), self::$version, true );
				wp_enqueue_script( 'prdctfltr-scrollbar-js' );
			}

			if ( !in_array( 'isotope', $curr_scripts ) ) {
				wp_register_script( 'prdctfltr-isotope-js', self::$url_path .'lib/js/isotope.js', array( 'jquery' ), self::$version, true );
				wp_enqueue_script( 'prdctfltr-isotope-js' );
			}

			if ( !in_array( 'ionrange', $curr_scripts ) ) {
				wp_register_style( 'prdctfltr-ionrange-css', self::$url_path .'lib/css/ion.rangeSlider.css', false, self::$version );
				wp_enqueue_style( 'prdctfltr-ionrange-css' );
				wp_register_script( 'prdctfltr-ionrange-js', self::$url_path .'lib/js/ion.rangeSlider.min.js', array( 'jquery' ), self::$version, false );
				wp_enqueue_script( 'prdctfltr-ionrange-js' );
			}

			wp_register_script( 'prdctfltr-main-js', self::$url_path .'lib/js/prdctfltr_main.js', array( 'jquery', 'hoverIntent' ), self::$version, true );
			wp_enqueue_script( 'prdctfltr-main-js' );


		}

		function localize_scripts() {

			global $prdctfltr_global;

			$curr_args = array(
				'ajax' => admin_url( 'admin-ajax.php' ),
				'url' => self::$url_path,
				'js' => self::$settings['wc_settings_prdctfltr_ajax_js'],
				'use_ajax' => self::$settings['wc_settings_prdctfltr_use_ajax'],
				'ajax_class' => self::$settings['wc_settings_prdctfltr_ajax_class'],
				'ajax_category_class' => self::$settings['wc_settings_prdctfltr_ajax_category_class'],
				'ajax_product_class' => self::$settings['wc_settings_prdctfltr_ajax_product_class'],
				'ajax_pagination_class' => self::$settings['wc_settings_prdctfltr_ajax_pagination_class'],
				'analytics' => self::$settings['wc_settings_prdctfltr_use_analytics'],
				'localization' => array(
					'close_filter' => __( 'Close filter', 'prdctfltr' ),
					'filter_terms' => __( 'Filter terms', 'prdctfltr' ),
					'ajax_error' => __( 'AJAX Error!', 'prdctfltr' ),
					'show_more' => __( 'Show More', 'prdctfltr' ),
					'show_less' => __( 'Show Less', 'prdctfltr' )
				),
				'rng_filters' => self::$settings['range_js'],
				'js_filters' => ( isset( $prdctfltr_global['filter_js'] ) ? $prdctfltr_global['filter_js'] : array() )
			);

			wp_localize_script( 'prdctfltr-main-js', 'prdctfltr', $curr_args );

		}

		function prdctfltr_gets( $query ) {
			self::make_global( $_REQUEST, $query );
		}

		public static function make_global( $set, $query = array() ) {

			global $prdctfltr_global;

			if ( ( isset( $prdctfltr_global['done_filters'] ) && $prdctfltr_global['done_filters'] === true ) === false ) :

			$stop = true;

			if ( $query == 'AJAX' || $query == 'FALSE' ) {
				$stop = false;
			}

			if ( $stop === true && ( isset( $query->query_vars['wc_query'] ) && $query->query_vars['wc_query'] == 'product_query' ) !== false ) {
				$stop = false;
			}

			if ( $stop === true && ( isset( $query->query_vars['prdctfltr'] ) && $query->query_vars['prdctfltr'] == 'active' ) !== false ) {
				$stop = false;
			}

			if ( $stop === false ) {

				$taxonomies_data = array();
				$taxonomies = array();
				$misc = array();
				$rng_terms = array();
				$rng_for_activated = array();


				$product_taxonomies = get_object_taxonomies( 'product' );
				$prdctfltr_global['taxonomies'] = $product_taxonomies;

				if ( isset( $set ) && !empty( $set ) ) {

					$get = $set;

					$allowed = array( 'orderby', 'min_price', 'max_price', 'instock_products', 'sale_products', 'products_per_page', 's' );

					foreach( $get as $k => $v ){

						if ( $v == '' ) {
							continue;
						}

						if ( in_array( $k, $allowed ) ) {
							$misc[$k] = $v;
						}
						else if ( substr($k, 0, 3) == 'pa_' || $k == 'product_cat' || $k == 'product_tag' || $k == 'characteristics' || in_array( $k, $product_taxonomies ) ) {

							if ( taxonomy_exists( $k ) ) {

								if ( strpos( $v, ',' ) ) {
									$selected = explode( ',', $v );
									$taxonomies_data[$k.'_relation'] = 'IN';
								}
								else if ( strpos( $v, '+' ) ) {
									$selected = explode( '+', $v );
									$taxonomies_data[$k.'_relation'] = 'AND';
								}
								else {
									$selected = array( $v );
								}
								$taxonomies_data[$k.'_string'] = $v;

								if ( substr($k, 0, 3) == 'pa_' ) {

									$f_attrs[] = 'attribute_' . $k;

									foreach( $selected as $val ) {
										if ( term_exists( $val, $k ) ) {
											$taxonomies[$k][] = $val;
											$f_terms[] = self::prdctfltr_utf8_decode($val);
										}
									}

								}
								else {

									foreach( $selected as $val ) {
										if ( term_exists( $val, $k ) ) {
											$taxonomies[$k][] = $val;
										}
									}

								}

							}

						}
						else if ( substr($k, 0, 4) == 'rng_' ) {

							if ( substr($k, 0, 8) == 'rng_min_' ) {
								$rng_for_activated[$k] = $v;
								$rng_terms[str_replace('rng_min_', '', $k)]['min'] = $v;
							}
							else if ( substr($k, 0, 8) == 'rng_max_' ) {
								$rng_for_activated[$k] = $v;
								$rng_terms[str_replace('rng_max_', '', $k)]['max'] = $v;
							}
							else if ( substr($k, 0, 12) == 'rng_orderby_' ) {
								$rng_terms[str_replace('rng_orderby_', '', $k)]['orderby'] = $v;
							}
							else if ( substr($k, 0, 10) == 'rng_order_' ) {
								$rng_terms[str_replace('rng_order_', '', $k)]['order'] = $v;
							}

						}

					}

				}

				$check_links = apply_filters( 'prdctfltr_check_permalinks', array( 'product_cat', 'product_tag', 'characteristics' ) );

				foreach( $check_links as $check_link ) {
					$pf_helper = array();
					if ( !isset( $taxonomies[$check_link] ) ) {
						$curr_link = ( ( $curr_var = get_query_var( $check_link ) ) !== '' ? $curr_var : ( isset( $prdctfltr_global['sc_query'][$check_link] ) ? $prdctfltr_global['sc_query'][$check_link] : '' ) );
						if ( $curr_link ) {

							if ( strpos( $curr_link, ',' ) ) {
								$pf_helper = explode( ',', $curr_link );
							}
							else if ( strpos( $curr_link, '+' ) ) {
								$pf_helper = explode( '+', $curr_link );
							}
							else {
								$pf_helper = array( $curr_link );
							}

							$taxonomies[$check_link] = $pf_helper;
							$taxonomies_data[$check_link . '_string'] = $curr_link;
						}
					}
				}

				$prdctfltr_global['done_filters'] = true;
				$prdctfltr_global['taxonomies_data'] = $taxonomies_data;
				$prdctfltr_global['active_taxonomies'] = $taxonomies;
				$prdctfltr_global['active_misc'] = $misc;
				$prdctfltr_global['range_filters'] = $rng_terms;
				$prdctfltr_global['active_filters'] = array_merge( $taxonomies, $misc, $rng_for_activated );

				$prdctfltr_global['active_in_filter'] = $prdctfltr_global['active_filters'];
				if ( isset( $prdctfltr_global['sc_query'] ) && !empty( $prdctfltr_global['sc_query'] ) ) {
					foreach ( $check_links as $check_link ) {
						if ( isset( $prdctfltr_global['sc_query'][$check_link] ) && isset( $prdctfltr_global['active_in_filter'][$check_link] ) ) {
							unset( $prdctfltr_global['active_in_filter'][$check_link] );
						}
						
					}
				}

				if ( isset( $f_attrs ) ) {
					$prdctfltr_global['f_attrs'] = $f_attrs;
				}
				if ( isset( $f_terms ) ) {
					$prdctfltr_global['f_terms'] = $f_terms;
				}
			}

			endif;

		}

		function prdctfltr_wc_query( $query ) {

			self::make_global( $_REQUEST, $query );

			global $prdctfltr_global;

			$stop = true;
			$wc_check_query = 'notactive';
			$pf_check_query = 'notactive';

			if ( is_admin() && ( isset( $query->query_vars['prdctfltr'] ) && $query->query_vars['prdctfltr'] == 'active' ) !== false && !$query->is_main_query() ) {
				global $wp_the_query, $wp_query;
				$wp_the_query = $query;
				$wp_query = $query;

				$stop = false;

			}

			if ( !$query->is_main_query() ) {
				if ( ( isset( $query->query_vars['prdctfltr'] ) && $query->query_vars['prdctfltr'] == 'active' ) === false ) {
					return $query;
				}
			}

			if ( $stop === true && ( isset( $query->query_vars['wc_query'] ) && $query->query_vars['wc_query'] == 'product_query' ) !== false ) {
				$wc_check_query = 'active';
				$stop = false;
			}

			if ( $stop === true && ( isset( $query->query_vars['prdctfltr'] ) && $query->query_vars['prdctfltr'] == 'active' ) !== false ) {
				$pf_check_query = 'active';
				$stop = false;
			}

			if ( $stop === true ) {
				return $query;
			}

			$curr_args = array();
			$f_attrs = array();
			$f_terms = array();
			$rng_terms = array();

			$pf_not_allowed = array( 'product_type' );
			$product_taxonomies = $prdctfltr_global['taxonomies'];

			$pf_allowed = array( 'products_per_page', 'instock_products', 'orderby' );

			$pf_not_allowed = array( 'product_cat', 'product_tag', 'characteristics', 'min_price', 'max_price', 'sale_products', 'instock_products', 'products_per_page', 'widget_search', 'page_id', 'lang' );

			if ( isset( $prdctfltr_global['active_filters'] ) ) {

				$pf_activated =  $prdctfltr_global['active_filters'];

				$allowed = array( 'orderby', 'min_price', 'max_price', 'instock_products', 'sale_products', 'products_per_page' );

				if ( isset( $prdctfltr_global['range_filters'] ) ) {
					$rng_terms = $prdctfltr_global['range_filters'];
				}

				if ( isset( $prdctfltr_global['f_attrs'] ) ) {

					$f_attrs = $prdctfltr_global['f_attrs'];

					if ( isset( $prdctfltr_global['f_terms'] ) ) {
						$f_terms = $prdctfltr_global['f_terms'];
					}

				}

			}

			if ( !empty( $rng_terms ) ) {

				foreach ( $rng_terms as $rng_name => $rng_inside ) {

					if ( ( isset($rng_inside['min']) && isset($rng_inside['max']) ) === false ) {
						continue;
					}

					if ( !in_array( $rng_name, array( 'price' ) ) ) {

						if ( isset($rng_terms[$rng_name]['orderby']) && $rng_terms[$rng_name]['orderby'] == 'number' ) {
							$attr_args = array(
								'hide_empty' => 1,
								'orderby' => 'slug'
							);
							$sort_args = array(
								'order' => ( isset( $rng_terms[$rng_name]['order'] ) ? $rng_terms[$rng_name]['order'] : 'ASC' )
							);
							$curr_attributes = self::prdctfltr_get_terms( $rng_name, $attr_args );
							$curr_attributes = self::prdctfltr_sort_terms_naturally( $curr_attributes, $sort_args );
						}
						else if ( isset($rng_terms[$rng_name]['orderby']) && $rng_terms[$rng_name]['orderby'] !== '' ) {
							$attr_args = array(
								'hide_empty' => 1,
								'orderby' => $rng_terms[$rng_name]['orderby'],
								'order' => ( isset( $rng_terms[$rng_name]['order'] ) ? $rng_terms[$rng_name]['order'] : 'ASC' )
							);
							$curr_attributes = self::prdctfltr_get_terms( $rng_name, $attr_args );
						}
						else {
							$attr_args = array(
								'hide_empty' => 1
							);
							$curr_attributes = self::prdctfltr_get_terms( $rng_name, $attr_args );
						}

						if ( empty( $curr_attributes ) ) {
							continue;
						}

						$rng_found = false;
						$curr_ranges = array();
						foreach ( $curr_attributes as $c => $s ) {
							if ( $rng_found == true ) {
								$curr_ranges[] = $s->slug;
								if ( $s->slug == $rng_inside['max'] ) {
									$rng_found = false;
									continue;
								}
							}
							if ( $s->slug == $rng_inside['min'] && $rng_found === false ) {
								$rng_found = true;
								$curr_ranges[] = $s->slug;
							}
						}
						$curr_args = array_merge( $curr_args, array(
								$rng_name => implode( $curr_ranges, ',' )
							) );

						$f_attrs[] = 'attribute_' . $rng_name;

						foreach ( $curr_ranges as $cr ) {
							$f_terms[] = $cr;
						}

					}

				}

			}

			if ( !isset($pf_activated['orderby']) && isset($query->query['orderby']) && $query->query['orderby'] !== '' ) {
				$pf_activated['orderby'] = $query->query['orderby'];
			}

			if ( isset($pf_activated['orderby']) && $pf_activated['orderby'] !== '' ) {
				if ( $pf_activated['orderby'] == 'price' || $pf_activated['orderby'] == 'price-desc' ) {
					$orderby = 'meta_value_num';
					$order = ( $pf_activated['orderby'] == 'price-desc' ? 'DESC' : 'ASC' );
					$curr_args = array_merge( $curr_args, array(
							'meta_key' => '_price',
							'orderby' => $orderby,
							'order' => $order
						) );
				}
				else if ( $pf_activated['orderby'] == 'rating' ) {
					add_filter( 'posts_clauses', array( WC()->query, 'order_by_rating_post_clauses' ) );
				}
				else if ( $pf_activated['orderby'] == 'popularity' ) {
					$orderby = 'meta_value_num';
					$order = 'DESC';
					$curr_args = array_merge( $curr_args, array(
							'meta_key' => 'total_sales',
							'orderby' => $orderby,
							'order' => $order
						) );
				}
				else {
					$orderby = $pf_activated['orderby'];
					$order = ( isset($pf_activated['order']) ? $pf_activated['order'] : in_array( $orderby, array( 'date', 'comment_count' ) ) ? 'DESC' : 'ASC' );
					$curr_args = array_merge( $curr_args, array(
							'orderby' => $orderby,
							'order' => $order
						) );
				}
			}

			if ( !isset($pf_activated['min_price']) && !isset($pf_activated['rng_min_price']) && isset($query->query['min_price']) && $query->query['min_price'] !== '' ) {
				$pf_activated['min_price'] = $query->query['min_price'];
			}
			if ( !isset($pf_activated['max_price']) && !isset($pf_activated['rng_max_price']) && isset($query->query['max_price']) && $query->query['max_price'] !== '' ) {
				$pf_activated['max_price'] = $query->query['max_price'];
			}

			if ( ( isset($pf_activated['min_price']) || isset($pf_activated['max_price']) ) !== false || ( isset($pf_activated['rng_min_price']) && isset($pf_activated['rng_max_price']) ) !== false || ( isset($pf_activated['sale_products']) || isset($query->query['sale_products']) ) !== false ) {
				add_filter( 'posts_join' , array( &$this, 'prdctfltr_join_price' ) );
				add_filter( 'posts_where' , array( &$this, 'prdctfltr_price_filter' ), 998, 2 );
			}

			if ( !isset($pf_activated['instock_products']) && isset($query->query['instock_products']) && $query->query['instock_products'] !== '' ) {
				$pf_activated['instock_products'] = $query->query['instock_products'];
			}

			$curr_instock = self::$settings['wc_settings_prdctfltr_instock'];

			if ( ( ( ( isset($pf_activated['instock_products']) && $pf_activated['instock_products'] !== '' && ( $pf_activated['instock_products'] == 'in' || $pf_activated['instock_products'] == 'out' ) ) || $curr_instock == 'yes' ) !== false ) && ( !isset($pf_activated['instock_products']) || $pf_activated['instock_products'] !== 'both' ) ) {
				
				if ( !isset($pf_activated['instock_products']) && $curr_instock == 'yes' ) {
					$i_arr['f_results'] = 'outofstock';
					$i_arr['s_results'] = 'instock';
				}
				else if ( isset($pf_activated['instock_products']) && $pf_activated['instock_products'] == 'in' ) {
					$i_arr['f_results'] = 'outofstock';
					$i_arr['s_results'] = 'instock';
				}
				else if ( isset($pf_activated['instock_products']) && $pf_activated['instock_products'] == 'out' ) {
					$i_arr['f_results'] = 'instock';
					$i_arr['s_results'] = 'outofstock';
				}

					$curr_count = count($f_attrs)+1;

					if ( $curr_count > 1 ) {

						$curr_atts =  implode( '","', array_map( 'esc_sql', $f_attrs ) );
						$curr_terms = implode( '","', array_map( 'esc_sql', $f_terms ) );

						global $wpdb;

						$pf_exclude_product = $wpdb->get_results( $wpdb->prepare( '
							SELECT DISTINCT(post_parent) FROM %1$s
							INNER JOIN %2$s ON (%1$s.ID = %2$s.post_id)
							WHERE %1$s.post_parent != "0"
							AND %2$s.meta_key IN ("_stock_status","'.$curr_atts.'")
							AND %2$s.meta_value IN ("'.$i_arr['f_results'].'","'.$curr_terms.'","")
							GROUP BY %2$s.post_id
							HAVING COUNT(DISTINCT %2$s.meta_value) = ' . $curr_count .'
							ORDER BY %1$s.ID ASC
						', $wpdb->posts, $wpdb->postmeta ) );

						$curr_in = array();
						foreach ( $pf_exclude_product as $p ) {
							$curr_in[] = $p->post_parent;
						}

						$pf_exclude_product_out = $wpdb->get_results( $wpdb->prepare( '
							SELECT DISTINCT(post_parent) FROM %1$s
							INNER JOIN %2$s ON (%1$s.ID = %2$s.post_id)
							WHERE %1$s.post_parent != "0"
							AND %2$s.meta_key IN ("_stock_status","'.$curr_atts.'")
							AND %2$s.meta_value IN ("'.$i_arr['s_results'].'","'.$curr_terms.'","")
							GROUP BY %2$s.post_id
							HAVING COUNT(DISTINCT %2$s.meta_value) = ' . $curr_count .'
							ORDER BY %1$s.ID ASC
						', $wpdb->posts, $wpdb->postmeta ) );

						$curr_in_out = array();
						foreach ( $pf_exclude_product_out as $p ) {
							$curr_in_out[] = $p->post_parent;
						}

						if ( $curr_instock == 'yes' || $pf_activated['instock_products'] == 'in' ) {

							foreach ( $curr_in as $q => $w ) {
								if ( in_array( $w, $curr_in_out) ) {
									unset($curr_in[$q]);
								}
							}

							$curr_args = array_merge( $curr_args, array(
										'post__not_in' => $curr_in
								) );

							add_filter( 'posts_join' , array( &$this, 'prdctfltr_join_instock' ) );
							add_filter( 'posts_where' , array( &$this, 'prdctfltr_instock_filter' ), 999, 2 );


						}
						else if ( $pf_activated['instock_products'] == 'out' ) {

							foreach ( $curr_in_out as $e => $r ) {
								if ( in_array( $r, $curr_in) ) {
									unset($curr_in_out[$e]);
								}
							}

							$pf_exclude_product_addon = $wpdb->get_results( $wpdb->prepare( '
								SELECT DISTINCT(ID) FROM %1$s
								INNER JOIN %2$s ON (%1$s.ID = %2$s.post_id)
								WHERE %1$s.post_parent = "0"
								AND %2$s.meta_key IN ("_stock_status","'.$curr_atts.'")
								AND %2$s.meta_value IN ("outofstock","'.$curr_terms.'","")
								GROUP BY %2$s.post_id
								ORDER BY %1$s.ID ASC
							', $wpdb->posts, $wpdb->postmeta ) );

							$curr_in_out_addon = array();
							foreach ( $pf_exclude_product_addon as $a ) {
								$curr_in_out_addon[] = $a->ID;
							}

							$curr_in_out = $curr_in_out + $curr_in_out_addon;

							$curr_args = array_merge( $curr_args, array(
										'post__in' => $curr_in_out
								) );

						}

					}
					else {
						if ( !isset($pf_activated['instock_products']) && $curr_instock == 'yes' ) {
							add_filter( 'posts_join' , array( &$this, 'prdctfltr_join_instock' ) );
							add_filter( 'posts_where' , array( &$this, 'prdctfltr_instock_filter' ), 999, 2 );
						}
						else if ( isset($pf_activated['instock_products']) && $pf_activated['instock_products'] == 'in' ) {
							add_filter( 'posts_join' , array( &$this, 'prdctfltr_join_instock' ) );
							add_filter( 'posts_where' , array( &$this, 'prdctfltr_instock_filter' ), 999, 2 );
						}
						else if ( isset($pf_activated['instock_products']) && $pf_activated['instock_products'] == 'out' ) {
							add_filter( 'posts_join' , array( &$this, 'prdctfltr_join_instock' ) );
							add_filter( 'posts_where' , array( &$this, 'prdctfltr_outofstock_filter' ), 999, 2 );
						}
					}

			}

			if ( isset($pf_activated['products_per_page']) && $pf_activated['products_per_page'] !== '' ) {
				$curr_args = array_merge( $curr_args, array(
					'posts_per_page' => floatval( $pf_activated['products_per_page'] )
				) );
			}

			if ( isset($pf_activated['s']) && $pf_activated['s'] !== '' ) {
				$curr_args = array_merge( $curr_args, array(
					's' => $pf_activated['s']
				) );
			}

			if ( isset($query->query_vars['http_query']) ) {
				parse_str(html_entity_decode($query->query['http_query']), $curr_http_args);
				$curr_args = array_merge( $curr_args, $curr_http_args );
			}

			if ( empty( $pf_activated ) ) {
				$prdctfltr_global['categories_active'] = true;
			}
			else {
				foreach( $pf_activated as $k => $v ) {
					if ( in_array( $k, array( 'orderby', 'order', 'products_per_page', 'instock_products', 'product_cat', 'sale_products', 's' ) ) ) {
						$cat_allowed = true;
					}
					else {
						$cat_not_allowed = true;
					}
				}

				if ( isset( $cat_not_allowed ) || $query->is_paged() ) {
					$prdctfltr_global['categories_active'] = false;
				}
				else if ( isset( $cat_allowed ) ) {
					$prdctfltr_global['categories_active'] = true;
				}
				
			}

			foreach ( $curr_args as $k => $v ) {
				$query->set( $k, $v );
			}

		}

		function prdctfltr_wc_tax( $query ) {

			self::make_global( $_REQUEST, $query );

			global $prdctfltr_global;

			$stop = true;
			$wc_check_query = 'notactive';
			$pf_check_query = 'notactive';
			$curr_args = array();

			if ( ( isset( $query->query_vars['wc_query'] ) && $query->query_vars['wc_query'] == 'product_query' ) !== false ) {
				$wc_check_query = 'active';
				$stop = false;
			}

			if ( $stop === true && ( isset( $query->query_vars['prdctfltr'] ) && $query->query_vars['prdctfltr'] == 'active' ) !== false ) {
				$pf_check_query = 'active';
				$stop = false;
			}

			if ( $stop === true ) {
				return $query;
			}

			if ( !empty( $prdctfltr_global['active_taxonomies'] ) ) {

				$pf_activated = $prdctfltr_global['active_taxonomies'];
				$product_taxonomies = $prdctfltr_global['taxonomies'];

				foreach ( $pf_activated as $k => $v ) {
					if ( in_array( $k, $product_taxonomies ) ) {
						$curr_args = array_merge( $curr_args, array(
								$k => $v
							) );
					}
				}

				$pf_tax_query = array();
				$tax_terms = array();
				$tc=0;
				foreach ( $curr_args as $k => $v ) {
					if ( in_array($k, $product_taxonomies ) ) {

						if ( count( $v ) > 1 ) {
							$pf_tax_query[$tc]['relation'] = isset( $prdctfltr_global['taxonomies_data'][$k.'_relation'] ) ? $prdctfltr_global['taxonomies_data'][$k.'_relation'] : 'IN';
							foreach( $v as $tax_term ) {
								$tax_terms[] = $tax_term;
							}
							$pf_tax_query[$tc][] = array( 'taxonomy' => $k, 'field' => 'slug', 'terms' => $tax_terms, 'include_children' => true, 'operator' => 'IN' );
							
						}
						else {
							$pf_tax_query[] = array( 'taxonomy' => $k, 'field' => 'slug', 'terms' => $v, 'include_children' => true, 'operator' => 'IN' );
						}
						$tc++;
					}
				}

				if ( !empty( $pf_tax_query ) ) {

					$pf_tax_query['relation'] = 'AND';

					$query->tax_query->queries = $pf_tax_query;
					$query->query_vars['tax_query'] = $query->tax_query->queries;

				}
			}

		}

		function prdctfltr_join_price($join){

			global $wpdb;
			$join .= " JOIN $wpdb->postmeta AS pf_price ON $wpdb->posts.ID = pf_price.post_id JOIN $wpdb->postmeta AS pf_price_max ON $wpdb->posts.ID = pf_price_max.post_id ";
			return $join;

		}


		function prdctfltr_join_instock($join){

			global $wpdb;
			$join .= " JOIN $wpdb->postmeta AS pf_instock ON $wpdb->posts.ID = pf_instock.post_id ";
			return $join;

		}


		function prdctfltr_price_filter ( $where, &$wp_query ) {
			global $wpdb, $prdctfltr_global;

			$pf_activated = $prdctfltr_global['active_filters'];

			if ( isset( $pf_activated['sale_products'] ) && $pf_activated['sale_products'] == 'on' ) {

				$pf_sale = true;
				$pf_where_keys = array(
					array(
						'_sale_price','_min_variation_sale_price'
					),
					array(
						'_sale_price','_max_variation_sale_price'
					)
				);

			}
			else {

				$pf_sale = false;
				$pf_where_keys = array(
					array(
						'_price','_min_variation_price'
					),
					array(
						'_price','_max_variation_price'
					)
				);

			}

			if ( isset( $wp_query->query_vars['rng_min_price'] ) ) {
				$_min_price = $wp_query->query_vars['rng_min_price'];
			}
			if ( isset( $wp_query->query_vars['min_price'] ) ) {
				$_min_price =  $wp_query->query_vars['min_price'];
			}
			if ( isset( $pf_activated['rng_min_price'] ) ) {
				$_min_price = $pf_activated['rng_min_price'];
			}
			if ( isset( $pf_activated['min_price'] ) ) {
				$_min_price =  $pf_activated['min_price'];
			}
			if ( !isset( $_min_price ) ) {

				$_min = floor( $wpdb->get_var( "
					SELECT min(meta_value + 0)
					FROM {$wpdb->posts} as posts
					LEFT JOIN {$wpdb->postmeta} as postmeta ON posts.ID = postmeta.post_id
					WHERE meta_key IN ('" . implode( "','", array_map( 'esc_sql', array( '_price', '_min_variation_price' ) ) ) . "')
					AND meta_value != ''
				" ) );

			}

			if ( isset( $wp_query->query_vars['rng_max_price'] ) ) {
				$_max_price = $wp_query->query_vars['rng_max_price'];
			}
			if ( isset( $wp_query->query_vars['max_price'] ) ) {
				$_max_price =  $wp_query->query_vars['max_price'];
			}
			if ( isset( $pf_activated['rng_max_price'] ) ) {
				$_max_price = $pf_activated['rng_max_price'];
			}
			if ( isset( $pf_activated['max_price'] ) ) {
				$_max_price =  $pf_activated['max_price'];
			}

			if ( !isset( $_max_price ) ) {

				$_max = ceil( $wpdb->get_var( "
					SELECT max(meta_value + 0)
					FROM {$wpdb->posts} as posts
					LEFT JOIN {$wpdb->postmeta} as postmeta ON posts.ID = postmeta.post_id
					WHERE meta_key IN ('" . implode( "','", array_map( 'esc_sql', array( '_price' ) ) ) . "')
				" ) );

			}

			if ( ( isset($_min_price) || isset($_max_price) ) !== false ) {

				if ( !isset( $_min_price) ) {
					$_min_price = $_min;
				}

				if ( !isset( $_max_price) ) {
					$_max_price = $_max;
				}

				$where .= " AND ( ( pf_price.meta_key IN ('" . implode( "','", array_map( 'esc_sql', $pf_where_keys[0] ) ) . "') AND pf_price.meta_value >= $_min_price AND pf_price.meta_value <= $_max_price AND pf_price.meta_value != '' ) OR ( pf_price_max.meta_key IN ('" . implode( "','", array_map( 'esc_sql', $pf_where_keys[1] ) ) . "') AND pf_price_max.meta_value >= $_min_price AND pf_price_max.meta_value <= $_max_price AND pf_price_max.meta_value != '' ) ) ";
			}
			else if ( $pf_sale === true ) {
				$where .= " AND ( pf_price.meta_key IN ('_sale_price','_min_variation_sale_price') AND pf_price.meta_value > 0 ) ";
			}

			remove_filter( 'posts_where' , 'prdctfltr_price_filter' );

			return $where;
			
		}

		function prdctfltr_instock_filter ( $where, &$wp_query ) {

			global $wpdb;

			$where = str_replace("AND ( ($wpdb->postmeta.meta_key = '_visibility' AND CAST($wpdb->postmeta.meta_value AS CHAR) IN ('visible','catalog')) )", "", $where);

			$where .= " AND ( pf_instock.meta_key LIKE '_stock_status' AND pf_instock.meta_value = 'instock' ) ";

			remove_filter( 'posts_where' , 'prdctfltr_instock_filter' );

			return $where;

		}

		function prdctfltr_outofstock_filter ( $where, &$wp_query ) {

			global $wpdb;

			$where = str_replace("AND ( ($wpdb->postmeta.meta_key = '_visibility' AND CAST($wpdb->postmeta.meta_value AS CHAR) IN ('visible','catalog')) )", "", $where);

			$where .= " AND ( pf_instock.meta_key LIKE '_stock_status' AND pf_instock.meta_value = 'outofstock' ) ";

			remove_filter( 'posts_where' , 'prdctfltr_outofstock_filter' );

			return $where;

		}

		function prdctrfltr_add_filter( $template, $slug, $name ) {

			if ( $slug == 'loop/no-products-found.php' ) {
				return $template;
			}
			else if ( in_array( $slug, self::$settings['wc_settings_prdctfltr_enable_overrides'] ) ) {
				if ( $name ) {
					$path = self::$path . WC()->template_path() . "{$slug}-{$name}.php";
				} else {
					$path = self::$path . WC()->template_path() . "{$slug}.php";
				}

				return file_exists( $path ) ? $path : $template;
			}
			else {
				return $template;
			}

		}

		function prdctrfltr_add_loop_filter( $template, $template_name, $template_path ) {

			if ( $template_name == 'loop/no-products-found.php' ) {
				return $template;
			}
			else if ( in_array( $template_name, self::$settings['wc_settings_prdctfltr_enable_overrides'] ) ) {
				$path = self::$path . $template_path . $template_name;
				return file_exists( $path ) ? $path : $template;
			}
			else {
				return $template;
			}

		}

		function prdctrfltr_add_filter_blank ( $template, $slug, $name ) {

			if ( $name ) {
				$path = self::$path . 'blank/' . WC()->template_path() . "{$slug}-{$name}.php";
			} else {
				$path = self::$path . 'blank/' . WC()->template_path() . "{$slug}.php";
			}

			return file_exists( $path ) ? $path : $template;

		}

		function prdctrfltr_add_loop_filter_blank ( $template, $template_name, $template_path ) {

			$path = self::$path . 'blank/' . $template_path . $template_name;
			return file_exists( $path ) ? $path : $template;

		}

		function prdctfltr_redirect() {

			if ( !is_shop() ) {
				return false;
			}

			if ( isset( $_REQUEST['product_cat'] ) ) {

				if ( empty( $_REQUEST['product_cat'] ) ) {
					$redirect = preg_replace( '/\?.*/', '', get_bloginfo( 'url' ) ) . '/';
				}
				else {
					if ( strpos( $_REQUEST['product_cat'], ',' ) || strpos( $_REQUEST['product_cat'], '+' ) ) {
						global $wp_rewrite;
						$redirect = $wp_rewrite->get_extra_permastruct( 'product_cat' );

						$redirect = preg_replace( '/\?.*/', '', get_bloginfo( 'url' ) ) . '/' . str_replace( '%product_cat%', $_REQUEST['product_cat'], $redirect );
					}
					else {
						$link = get_term_link( $_REQUEST['product_cat'], 'product_cat' );
						if ( !is_wp_error( $link ) ) {
							$redirect = preg_replace( '/\?.*/', '', $link );
						}
					}
				}

				if ( isset( $redirect ) ) {
					$redirect = trailingslashit( $redirect );

					unset( $_REQUEST['product_cat'] );

					if ( !empty( $_REQUEST ) ) {

						$req = '';

						foreach( $_REQUEST as $k => $v ) {
							if ( strpos( $v, ',' ) ) {
								$new_v = str_replace( ',', '%2C', $v );
							}
							else if ( strpos( $v, '+' ) ) {
								$new_v = str_replace( '+', '%2B', $v );
							}
							else {
								$new_v = $v;
							}

							$req .= $k . '=' . $new_v . '&';
						}

						$redirect = $redirect . '?' . $req;

						if ( substr( $redirect, -1 ) == '&' ) {
							$redirect = substr( $redirect, 0, -1 );
						}

					}

					header( "Location: $redirect", true, 302 );
					exit;
				}

			}

		}

		function prdctfltr_redirect_empty_shop() {

			$curr_display_disable = get_option( 'wc_settings_prdctfltr_disable_display', array( 'subcategories' ) );

			if ( !empty( $_REQUEST ) && ( !empty( $_GET ) && !isset($_GET['lang']) ) && is_shop() && !is_product_category() && in_array( get_option( 'woocommerce_shop_page_display' ), $curr_display_disable ) ) {

				$_REQUEST = array();

				$redirect = get_permalink( self::prdctfltr_wpml_get_id( wc_get_page_id( 'shop' ) ) );

				if ( substr( $redirect, -1 ) != '/' ) {
					$redirect .= '/';
				}

				remove_action( 'template_redirect', 'prdctfltr_redirect', 999 );

				header("Location: $redirect", true, 302);
				exit;
			}
			else if ( !empty( $_REQUEST ) && ( !empty( $_GET ) && !isset($_GET['lang']) ) && is_post_type_archive( 'product' ) || is_tax( self::$settings['wc_settings']['product_taxonomies'] ) ) {

				if ( isset( $_REQUEST['product_cat'] ) ) {

					if ( count($_REQUEST) == 1 ) return;

					$term = term_exists( $_REQUEST['product_cat'], 'product_cat' );

					if ($term !== 0 && $term !== null) {

						$display_type = get_woocommerce_term_meta( $term['term_id'], 'display_type', true );

						$display_type = ( $display_type == '' ? get_option( 'woocommerce_category_archive_display' ) : $display_type );

						if ( in_array( $display_type, $curr_display_disable ) ) {

							$redirect = get_term_link( $_REQUEST['product_cat'], 'product_cat' );

							$_REQUEST = array( 'product_cat', $_REQUEST['product_cat'] );

							remove_action( 'template_redirect', 'prdctfltr_redirect', 999 );

							header("Location: $redirect", true, 302);
							exit;

						}
					}
				}

			}

		}

		public static function prdctrfltr_search_array( $array, $attrs ) {
			$results = array();
			$found = 0;

			foreach ( $array as $subarray ) {

				if ( isset( $subarray['attributes'] ) ) {
					foreach ( $attrs as $k => $v ) {
						if ( in_array($v, $subarray['attributes'] ) ) {
							$found++;
						}
					}
				}
				if ( count($attrs) == $found ) {
					$results[] = $subarray;
				}
				else if ( $found > 0 ) {
					$results[] = $subarray;
				}
				$found = 0;

			}

			return $results;
		}

		public static function prdctfltr_sort_terms_hierarchicaly( Array &$cats, Array &$into, $parentId = 0 ) {
			foreach ( $cats as $i => $cat ) {
				if ( $cat->parent == $parentId ) {
					$into[$cat->term_id] = $cat;
					unset($cats[$i]);
				}
			}
			foreach ( $into as $topCat ) {
				$topCat->children = array();
				self::prdctfltr_sort_terms_hierarchicaly( $cats, $topCat->children, $topCat->term_id );
			}
		}

		public static function prdctfltr_sort_terms_naturally( $terms, $args ) {

			$sort_terms = array();

			foreach($terms as $term) {
				$sort_terms[$term->name] = $term;
			}

			uksort( $sort_terms, 'strnatcmp');

			if ( strtoupper( $args['order'] ) == 'DESC' ) {
				$sort_terms = array_reverse( $sort_terms );
			}

			return $sort_terms;

		}

		public static function prdctfltr_get_filter() {

			include( self::$dir . 'woocommerce/loop/product-filter.php' );

		}

		public static function prdctfltr_get_between( $content, $start, $end ){
			$r = explode($start, $content);
			if (isset($r[1])){
				$r = explode($end, $r[1]);
				return $r[0];
			}
			return '';
		}

		public static function prdctfltr_utf8_decode( $str ) {
			$str = preg_replace( "/%u([0-9a-f]{3,4})/i", "&#x\\1;", urldecode( $str ) );
			return html_entity_decode( $str, null, 'UTF-8' );
		}

		public static function prdctfltr_wpml_get_id( $id ) {
			if( function_exists( 'icl_object_id' ) ) {
				return icl_object_id( $id, 'page', true );
			}
			else {
				return $id;
			}
		}

		public static function prdctfltr_wpml_translate_terms( $curr_include, $attr ) {
			global $sitepress;

			if( function_exists( 'icl_object_id' ) && is_object( $sitepress ) ) {

				$translated_include = array();

				foreach( $curr_include as $curr ) {
					$current_term = get_term_by( 'slug', $curr, $attr );

					if($current_term) {
						$default_language = $sitepress->get_default_language();
						$current_language = $sitepress->get_current_language();

						$term_id = $current_term->term_id;
						if ( $default_language != $current_language ) {
							$term_id = icl_object_id( $term_id, $attr, false, $current_language );
						}

						$term = get_term( $term_id, $attr );
						$translated_include[] = $term->slug;

					}
				}

				return $translated_include;
			}
			else {
				return $curr_include;
			}
		}

		public static function prdctfltr_wpml_language() {

			if( class_exists( 'SitePress' ) ) {
				global $sitepress;

				$default_language = $sitepress->get_default_language();
				$current_language = $sitepress->get_current_language();

				if ( $default_language != $current_language ) {
					return sanitize_title( $current_language );
				}
				else {
					return false;
				}

			}
			else {
				return false;
			}
		}

		public static function prdctfltr_check_appearance() {

			global $prdctfltr_global;

			if ( isset($prdctfltr_global['active']) && $prdctfltr_global['active'] == 'true' ) {
				if ( isset( $prdctfltr_global['woo_template'] ) ) {
					unset($prdctfltr_global['woo_template']);
				}
				if ( isset( $prdctfltr_global['widget_search'] ) && !isset( $prdctfltr_global['sc_query'] ) ) {
					echo '<span class="prdctfltr_error"><small>' . __( 'Product Filter was already activated on this page using a template override. Uncheck the Enable/Disable Product Filter Template Overrides in the product filter advanced options tab to use the widget version instead of the order by filter template override.', 'prdctfltr' ) . '</small></span>';
				}
				else if ( isset( $prdctfltr_global['widget_search'] ) && isset( $prdctfltr_global['sc_query'] ) ) {
					echo '<span class="prdctfltr_error"><small>' . __( 'Product Filter was already activated on this page using a shortcode. You cannot use the widget filter and the shortcode filter on a same page. If you want to use the widget filter with the shortcode then use the shortcode parameter', 'prdctfltr' ) . ' <code>use_filter="no"</code> ' . __( 'This parameter will hide the shortcode filter and the widget filter will appear.', 'prdctfltr' ) . '</small></span>';
				}
				else if ( isset( $prdctfltr_global['sc_query'] ) && !isset( $prdctfltr_global['sc_ajax'] ) ) {
					echo '<span class="prdctfltr_error"><small>' . __( 'Product Filter was already activated on this page using a non-ajax shortcode. Multiple shortcode instances are only possible when AJAX is activated for all shortcodes used in the page.', 'prdctfltr' ) . '</small></span>';
				}
				else if ( isset( $prdctfltr_global['sc_query'] ) && isset( $prdctfltr_global['sc_ajax'] ) ) {
					$pf_dont_return = true;
				}
				
				if ( !isset( $pf_dont_return ) ) {
					return false;
				}

			}

			$curr_shop_disable = get_option( 'wc_settings_prdctfltr_shop_disable', 'no' );

			if ( $curr_shop_disable == 'yes' && is_shop() && !is_product_category() ) {
				if ( isset( $prdctfltr_global['woo_template'] ) ) {
					unset($prdctfltr_global['woo_template']);
				}
				return false;
			}

			$curr_display_disable = get_option( 'wc_settings_prdctfltr_disable_display', array( 'subcategories' ) );

			if ( is_shop() && !is_product_category() && in_array( get_option( 'woocommerce_shop_page_display' ), $curr_display_disable ) ) {
				if ( isset( $prdctfltr_global['woo_template'] ) ) {
					unset($prdctfltr_global['woo_template']);
				}
				return false;
			}

			if ( is_product_category() ) {

				$pf_queried_term = get_queried_object();
				$display_type = get_woocommerce_term_meta( $pf_queried_term->term_id, 'display_type', true );
				
				$display_type = ( $display_type == '' ? get_option( 'woocommerce_category_archive_display' ) : $display_type );

				if ( in_array( $display_type, $curr_display_disable ) ) {
					if ( isset( $prdctfltr_global['woo_template'] ) ) {
						unset($prdctfltr_global['woo_template']);
					}
					return false;
				}

			}
		}

		public static function prdctfltr_get_styles( $curr_options, $curr_mod ) {

			$curr_styles = array(
				( in_array( $curr_options['wc_settings_prdctfltr_style_preset'], array( 'pf_arrow', 'pf_arrow_inline', 'pf_default', 'pf_default_inline', 'pf_select', 'pf_default_select', 'pf_sidebar', 'pf_sidebar_right', 'pf_sidebar_css', 'pf_sidebar_css_right', 'pf_fullscreen' ) ) ? ' ' . $curr_options['wc_settings_prdctfltr_style_preset'] : 'pf_default' ),
				( $curr_options['wc_settings_prdctfltr_always_visible'] == 'no' && $curr_options['wc_settings_prdctfltr_disable_bar'] == 'no' || in_array( $curr_options['wc_settings_prdctfltr_style_preset'], array( 'pf_sidebar', 'pf_sidebar_right', 'pf_sidebar_css', 'pf_sidebar_css_right', 'pf_fullscreen' ) ) ? 'prdctfltr_slide' : 'prdctfltr_always_visible' ),
				( $curr_options['wc_settings_prdctfltr_click_filter'] == 'no' ? 'prdctfltr_click' : 'prdctfltr_click_filter' ),
				( $curr_options['wc_settings_prdctfltr_limit_max_height'] == 'no' ? 'prdctfltr_rows' : 'prdctfltr_maxheight' ),
				( $curr_options['wc_settings_prdctfltr_custom_scrollbar'] == 'no' ? '' : 'prdctfltr_scroll_active' ),
				( $curr_options['wc_settings_prdctfltr_disable_bar'] == 'no' || in_array( $curr_options['wc_settings_prdctfltr_style_preset'], array( 'pf_sidebar', 'pf_sidebar_right', 'pf_sidebar_css', 'pf_sidebar_css_right' ) ) ? '' : 'prdctfltr_disable_bar' ),
				$curr_mod,
				( $curr_options['wc_settings_prdctfltr_adoptive'] == 'no' ? '' : $curr_options['wc_settings_prdctfltr_adoptive_style'] ),
				$curr_options['wc_settings_prdctfltr_style_checkboxes'],
				( $curr_options['wc_settings_prdctfltr_show_search'] == 'no' ? '' : 'prdctfltr_search_fields' ),
				$curr_options['wc_settings_prdctfltr_style_hierarchy']
			);

			return $curr_styles;

		}

		public static function prdctfltr_get_settings() {

			global $prdctfltr_global;

			$pf_activated = ( isset ( $prdctfltr_global['active_filters'] ) ? $prdctfltr_global['active_filters'] : array() );

			if ( isset( $prdctfltr_global['preset'] ) && $prdctfltr_global['preset'] !== '' ) {
				$get_options = $prdctfltr_global['preset'];
			}

			if ( !isset($prdctfltr_global['disable_overrides']) || ( isset($prdctfltr_global['disable_overrides']) && $prdctfltr_global['disable_overrides'] !== 'yes' ) ) {

				$curr_overrides = get_option( 'prdctfltr_overrides', array() );
				$pf_check_overrides = array( 'product_cat', 'product_tag', 'characteristics' );

				foreach ( $pf_check_overrides as $pf_check_override ) {

					$override = ( isset($pf_activated[$pf_check_override][0] ) ? $pf_activated[$pf_check_override][0] : get_query_var( $pf_check_override ) );

					if ( $override !== '' ) {

						if ( !term_exists( $override, $pf_check_override ) ) {
							continue;
						}

						if ( is_array( $curr_overrides ) && isset( $curr_overrides[$pf_check_override] ) ) {

							if ( array_key_exists( $override, $curr_overrides[$pf_check_override] ) ) {
								$get_options = $curr_overrides[$pf_check_override][$override];
								break;
							}

							else if ( $pf_check_override == 'product_cat' ) {
								$curr_check = get_term_by( 'slug', $override, $pf_check_override );

								if ( $curr_check->parent !== 0 ) {

									$parents = get_ancestors( $curr_check->term_id, 'product_cat' );

									foreach( $parents as $parent_id ) {
										$curr_check_parent = get_term_by( 'id', $parent_id, $pf_check_override );
										if ( array_key_exists( $curr_check_parent->slug, $curr_overrides[$pf_check_override]) ) {
											$get_options = $curr_overrides[$pf_check_override][$curr_check_parent->slug];
											break;
										}
									}

								}
							}

						}
					}
				}
			}

			if ( self::$settings['wc_settings_prdctfltr_shop_page_override'] !== '' && isset( $prdctfltr_global['is_shop'] ) && $prdctfltr_global['is_shop'] === true ) {
				$get_options = self::$settings['wc_settings_prdctfltr_shop_page_override'];
			}

			if ( isset( $get_options ) && $get_options !== '' ) {
				if ( get_option('_transient_timeout_prdctfltr_' . $get_options ) === false ) {
					delete_transient( 'prdctfltr_' . $get_options );
				}
				$curr_transient = get_transient( 'prdctfltr_' . $get_options );
				$curr_transient_name = 'prdctfltr_' . $get_options;
			}
			else {
				if ( get_option('_transient_timeout_prdctfltr_default' ) === false ) {
					delete_transient( 'prdctfltr_default' );
				}
				$curr_transient = get_transient( 'prdctfltr_default' );
				$curr_transient_name = 'prdctfltr_default';
			}

			if ( $curr_transient === false ) {

				if ( isset($get_options) ) {
					$curr_or_presets = get_option( 'prdctfltr_templates', array() );
					if ( isset($curr_or_presets) && is_array($curr_or_presets) ) {
						if ( array_key_exists($get_options, $curr_or_presets) ) {
							$get_curr_options = json_decode(stripslashes($curr_or_presets[$get_options]), true);
						}
					}
				}

				$pf_chck_settings = array(
					'wc_settings_prdctfltr_style_preset' => 'pf_default',
					'wc_settings_prdctfltr_always_visible' => 'no',
					'wc_settings_prdctfltr_click_filter' => 'no',
					'wc_settings_prdctfltr_limit_max_height' => 'no',
					'wc_settings_prdctfltr_max_height' => 150,
					'wc_settings_prdctfltr_custom_scrollbar' => 'no',
					'wc_settings_prdctfltr_disable_bar' => 'no',
					'wc_settings_prdctfltr_icon' => '',
					'wc_settings_prdctfltr_max_columns' => 6,
					'wc_settings_prdctfltr_adoptive' => 'no',
					'wc_settings_prdctfltr_cat_adoptive' => 'no',
					'wc_settings_prdctfltr_tag_adoptive' => 'no',
					'wc_settings_prdctfltr_chars_adoptive' => 'no',
					'wc_settings_prdctfltr_price_adoptive' => 'no',
					'wc_settings_prdctfltr_orderby_title' => '',
					'wc_settings_prdctfltr_price_title' => '',
					'wc_settings_prdctfltr_price_range' => 100,
					'wc_settings_prdctfltr_price_range_add' => 100,
					'wc_settings_prdctfltr_price_range_limit' => 6,
					'wc_settings_prdctfltr_cat_title' => '',
					'wc_settings_prdctfltr_cat_orderby' => '',
					'wc_settings_prdctfltr_cat_order' => '',
					'wc_settings_prdctfltr_cat_relation' => 'IN',
					'wc_settings_prdctfltr_cat_limit' => 0,
					'wc_settings_prdctfltr_cat_hierarchy' => 'no',
					'wc_settings_prdctfltr_cat_multi' => 'no',
					'wc_settings_prdctfltr_include_cats' => array(),
					'wc_settings_prdctfltr_tag_title' => '',
					'wc_settings_prdctfltr_tag_orderby' => '',
					'wc_settings_prdctfltr_tag_order' => '',
					'wc_settings_prdctfltr_tag_relation' => 'IN',
					'wc_settings_prdctfltr_tag_limit' => 0,
					'wc_settings_prdctfltr_tag_multi' => 'no',
					'wc_settings_prdctfltr_include_tags' => array(),
					'wc_settings_prdctfltr_custom_tax_title' => '',
					'wc_settings_prdctfltr_custom_tax_orderby' => '',
					'wc_settings_prdctfltr_custom_tax_order' => '',
					'wc_settings_prdctfltr_custom_tax_relation' => 'IN',
					'wc_settings_prdctfltr_custom_tax_limit' => 0,
					'wc_settings_prdctfltr_chars_multi' => 'no',
					'wc_settings_prdctfltr_include_chars' => array(),
					'wc_settings_prdctfltr_disable_sale' => 'no',
					'wc_settings_prdctfltr_noproducts' => '',
					'wc_settings_prdctfltr_advanced_filters' => array(),
					'wc_settings_prdctfltr_range_filters' => array(),
					'wc_settings_prdctfltr_disable_instock' => 'no',
					'wc_settings_prdctfltr_title' => '',
					'wc_settings_prdctfltr_style_mode' => 'pf_mod_multirow',
					'wc_settings_prdctfltr_instock_title' => '',
					'wc_settings_prdctfltr_disable_reset' => 'no',
					'wc_settings_prdctfltr_include_orderby' => array( 'menu_order', 'popularity', 'rating', 'date' ,'price', 'price-desc' ),
					'wc_settings_prdctfltr_adoptive_style' => 'pf_adptv_default',
					'wc_settings_prdctfltr_show_counts' => 'no',
					'wc_settings_prdctfltr_show_counts_mode' => 'default',
					'wc_settings_prdctfltr_disable_showresults' => 'no',
					'wc_settings_prdctfltr_orderby_none' => 'no',
					'wc_settings_prdctfltr_price_none' => 'no',
					'wc_settings_prdctfltr_cat_none' => 'no',
					'wc_settings_prdctfltr_tag_none' => 'no',
					'wc_settings_prdctfltr_chars_none' => 'no',
					'wc_settings_prdctfltr_perpage_title' => '',
					'wc_settings_prdctfltr_perpage_label' => '',
					'wc_settings_prdctfltr_perpage_range' => 20,
					'wc_settings_prdctfltr_perpage_range_limit' => 5,
					'wc_settings_prdctfltr_cat_mode' => 'showall',
					'wc_settings_prdctfltr_style_checkboxes' => 'prdctfltr_round',
					'wc_settings_prdctfltr_cat_hierarchy_mode' => 'no',
					'wc_settings_prdctfltr_show_search' => 'no',
					'wc_settings_prdctfltr_style_hierarchy' => 'prdctfltr_hierarchy_circle',
					'wc_settings_prdctfltr_button_position' => 'bottom',
					'wc_settings_prdctfltr_submit' => '',
					'wc_settings_prdctfltr_loader' => 'spinning-circles',
					'wc_settings_prdctfltr_cat_term_customization' => '',
					'wc_settings_prdctfltr_tag_term_customization' => '',
					'wc_settings_prdctfltr_chars_term_customization' => '',
					'wc_settings_prdctfltr_price_term_customization' => '',
					'wc_settings_prdctfltr_perpage_term_customization' => '',
					'wc_settings_prdctfltr_price_filter_customization' => '',
					'wc_settings_prdctfltr_perpage_filter_customization' => '',
					'wc_settings_prdctfltr_orderby_term_customization' => '',
					'wc_settings_prdctfltr_instock_term_customization' => '',
					'wc_settings_prdctfltr_custom_action' => '',
					'wc_settings_prdctfltr_search_title' => __( 'Search Products', 'prdctfltr' )
				);

				if ( isset( $get_curr_options ) ) {
					$curr_options = $get_curr_options;

					foreach ( $pf_chck_settings as $z => $x) {
						if ( !isset($curr_options[$z]) ) {
							$curr_options[$z] = $x;
						}
					}

					$wc_settings_prdctfltr_active_filters = $curr_options['wc_settings_prdctfltr_active_filters'];

					$wc_settings_prdctfltr_selected = array();
					$wc_settings_prdctfltr_attributes = array();
					if ( is_array($wc_settings_prdctfltr_active_filters) ) {
						foreach ( $wc_settings_prdctfltr_active_filters as $k ) {
							if (substr($k, 0, 3) == 'pa_') {
								$wc_settings_prdctfltr_attributes[] = $k;
							}
						}
					}

					$curr_attrs = $wc_settings_prdctfltr_attributes;

					foreach ( $curr_attrs as $k => $attr ) {

						$curr_array = array(
							'wc_settings_prdctfltr_'.$attr.'_hierarchy' => 'no',
							'wc_settings_prdctfltr_'.$attr.'_hierarchy_mode' => 'no',
							'wc_settings_prdctfltr_'.$attr.'_mode' => 'showall',
							'wc_settings_prdctfltr_'.$attr.'_limit' => 0,
							'wc_settings_prdctfltr_'.$attr.'_none' => 'no',
							'wc_settings_prdctfltr_'.$attr.'_adoptive' => 'no',
							'wc_settings_prdctfltr_'.$attr.'_title' => '',
							'wc_settings_prdctfltr_'.$attr.'_orderby' => '',
							'wc_settings_prdctfltr_'.$attr.'_order' => '',
							'wc_settings_prdctfltr_'.$attr.'_relation' => 'IN',
							'wc_settings_prdctfltr_'.$attr => 'pf_attr_text',
							'wc_settings_prdctfltr_'.$attr.'_multi' => 'no',
							'wc_settings_prdctfltr_include_'.$attr => array(),
							'wc_settings_prdctfltr_'.$attr.'_term_customization' => ''
						);

						foreach ( $curr_array as $dk => $dv ) {
							if ( !isset($curr_options[$dk]) ) {
								$curr_options[$dk] = $dv;
							}
						}

					}

				}
				else {
					$wc_settings_prdctfltr_active_filters = get_option( 'wc_settings_prdctfltr_active_filters' );

					$wc_settings_prdctfltr_selected = array();
					$wc_settings_prdctfltr_attributes = array();

					if ( $wc_settings_prdctfltr_active_filters === false ) {
						$wc_settings_prdctfltr_selected = get_option( 'wc_settings_prdctfltr_selected', array('sort','price','cat') );
						$wc_settings_prdctfltr_attributes = get_option( 'wc_settings_prdctfltr_attributes', array() );
						$wc_settings_prdctfltr_active_filters = array();
						$wc_settings_prdctfltr_active_filters = array_merge( $wc_settings_prdctfltr_selected,  $wc_settings_prdctfltr_attributes );
					}
					else if ( is_array($wc_settings_prdctfltr_active_filters) ) {
						foreach ( $wc_settings_prdctfltr_active_filters as $k ) {
							if (substr($k, 0, 3) == 'pa_') {
								$wc_settings_prdctfltr_attributes[] = $k;
							}
						}
					}

					$curr_attrs = $wc_settings_prdctfltr_attributes;

					$curr_options = array(
						'wc_settings_prdctfltr_selected' => $wc_settings_prdctfltr_selected,
						'wc_settings_prdctfltr_attributes' => $wc_settings_prdctfltr_attributes,
						'wc_settings_prdctfltr_active_filters' => $wc_settings_prdctfltr_active_filters
					);
					
					foreach ( $pf_chck_settings as $z => $x) {
						$curr_z = get_option( $z );
						if ( $curr_z === false ) {
							$curr_options[$z] = $x;
						}
						else {
							$curr_options[$z] = $curr_z;
						}
					}

					foreach ( $curr_attrs as $k => $attr ) {
						$curr_options['wc_settings_prdctfltr_'.$attr.'_hierarchy'] = get_option( 'wc_settings_prdctfltr_'.$attr.'_hierarchy', 'no' );
						$curr_options['wc_settings_prdctfltr_'.$attr.'_hierarchy_mode'] = get_option( 'wc_settings_prdctfltr_'.$attr.'_hierarchy_mode', 'no' );
						$curr_options['wc_settings_prdctfltr_'.$attr.'_mode'] = get_option( 'wc_settings_prdctfltr_'.$attr.'_mode', 'showall' );
						$curr_options['wc_settings_prdctfltr_'.$attr.'_limit'] = get_option( 'wc_settings_prdctfltr_'.$attr.'_limit', 'no' );
						$curr_options['wc_settings_prdctfltr_'.$attr.'_none'] = get_option( 'wc_settings_prdctfltr_'.$attr.'_none', 'no' );
						$curr_options['wc_settings_prdctfltr_'.$attr.'_adoptive'] = get_option( 'wc_settings_prdctfltr_'.$attr.'_adoptive', 'no' );
						$curr_options['wc_settings_prdctfltr_'.$attr.'_title'] = get_option( 'wc_settings_prdctfltr_'.$attr.'_title', '' );
						$curr_options['wc_settings_prdctfltr_'.$attr.'_orderby'] = get_option( 'wc_settings_prdctfltr_'.$attr.'_orderby', '' );
						$curr_options['wc_settings_prdctfltr_'.$attr.'_order'] = get_option( 'wc_settings_prdctfltr_'.$attr.'_order', '' );
						$curr_options['wc_settings_prdctfltr_'.$attr.'_relation'] = get_option( 'wc_settings_prdctfltr_'.$attr.'_relation', 'IN' );
						$curr_options['wc_settings_prdctfltr_' . $attr] = get_option( 'wc_settings_prdctfltr_' . $attr, 'pf_attr_text' );
						$curr_options['wc_settings_prdctfltr_' . $attr . '_multi'] = get_option( 'wc_settings_prdctfltr_' . $attr . '_multi', 'no' );
						$curr_options['wc_settings_prdctfltr_include_' . $attr] = get_option( 'wc_settings_prdctfltr_include_' . $attr, array() );
						$curr_options['wc_settings_prdctfltr_' . $attr . '_term_customization'] = get_option( 'wc_settings_prdctfltr_' . $attr . '_term_customization', array() );
					}

				}

				if ( isset($get_options) ) {
					set_transient( 'prdctfltr_' . $get_options, $curr_options, 3600 );
				}
				else {
					set_transient( 'prdctfltr_default', $curr_options, 3600 );
				}

			}
			else {
				$curr_options = $curr_transient;
			}

			if ( $curr_options['wc_settings_prdctfltr_button_position'] == 'top' ) {
				add_action( 'prdctfltr_filter_form_before', 'WC_Prdctfltr::prdctfltr_filter_buttons', 10, 2 );
				remove_action( 'prdctfltr_filter_form_after', 'WC_Prdctfltr::prdctfltr_filter_buttons');
			}
			else {
				add_action( 'prdctfltr_filter_form_after', 'WC_Prdctfltr::prdctfltr_filter_buttons', 10, 2 );
				remove_action( 'prdctfltr_filter_form_before', 'WC_Prdctfltr::prdctfltr_filter_buttons');
			}

			$back_compatibility = array(
				'wc_settings_prdctfltr_cat_term_customization' => '',
				'wc_settings_prdctfltr_tag_term_customization' => '',
				'wc_settings_prdctfltr_chars_term_customization' => '',
				'wc_settings_prdctfltr_price_term_customization' => '',
				'wc_settings_prdctfltr_perpage_term_customization' => '',
				'wc_settings_prdctfltr_price_filter_customization' => '',
				'wc_settings_prdctfltr_perpage_filter_customization' => '',
				'wc_settings_prdctfltr_orderby_term_customization' => '',
				'wc_settings_prdctfltr_instock_term_customization' => ''
			);

			foreach( $back_compatibility as $k => $v ) {
				if ( !isset( $curr_options[$k] ) ) {
					$curr_options[$k] = $v;
				}
			}

			if ( isset( $curr_transient_name ) && $curr_transient_name == 'prdctfltr_default' ) {
				$curr_options = self::wpml_fix_translations( $curr_options );
			}


			self::$settings['instance'] = $curr_options;
 
			return $curr_options;

		}

		public static function wpml_fix_translations( $settings ) {
			if( function_exists( 'icl_object_id' ) ) {
				$valid = array(
					'wc_settings_prdctfltr_noproducts',
					'wc_settings_prdctfltr_title',
					'wc_settings_prdctfltr_submit'
				);
				foreach( $settings as $k => $v ) {
					if ( mb_substr( $k, -6 ) == '_title' || in_array( $k, $valid ) ) {
						$settings[$k] = get_option( $k, '' );
					}
				}
			}

			return $settings;
		}

		public static function prdctfltr_get_terms( $curr_term, $curr_term_args ) {
			global $prdctfltr_global;
			$pf_activated = ( isset( $prdctfltr_global['active_filters'] ) ? $prdctfltr_global['active_filters'] : array() );

			$curr_term_args['hide_empty'] = 0;

			if ( !isset($pf_activated['orderby']) && ( defined('DOING_AJAX') && DOING_AJAX ) === false || !isset($pf_activated['orderby']) ) {
				$curr_terms = get_terms( $curr_term, $curr_term_args );
			}
			else if ( isset($pf_activated['orderby']) ) {
				$curr_keep = $pf_activated['orderby'];
				unset($pf_activated['orderby']);
				$curr_terms = get_terms( $curr_term, $curr_term_args );
				$pf_activated['orderby'] = $curr_keep;
			}

			return $curr_terms;

		}

		public static function prdctfltr_in_array( $needle, $haystack ) {
			return in_array( strtolower( $needle ), array_map( 'strtolower', $haystack ) );
		}

		function prdctfltr_pagination_filter( $args ) {
			global $prdctfltr_global;
			
			if ( isset( $prdctfltr_global['sc_ajax'] ) || self::$settings['wc_settings_prdctfltr_use_ajax'] == 'yes' && is_woocommerce() ) {
				$args['base'] = esc_url( add_query_arg( 'paged','%#%' ) );
				$args['format'] = '';
			}

			return $args;
		}

		public static function prdctfltr_filter_buttons( $curr_options, $pf_activated ) {
			global $prdctfltr_global;
			$pf_activated = ( isset( $prdctfltr_global['active_in_filter'] ) ? $prdctfltr_global['active_in_filter'] : array() );

			$curr_elements = ( $curr_options['wc_settings_prdctfltr_active_filters'] !== NULL ? $curr_options['wc_settings_prdctfltr_active_filters'] : array() );

			ob_start();
		?>
			<div class="prdctfltr_buttons">
			<?php
				if ( $curr_options['wc_settings_prdctfltr_click_filter'] == 'no' ) {
			?>
				<a <?php ( isset( $prdctfltr_global['active'] ) ? '' : 'id="prdctfltr_woocommerce_filter_submit" ' ); ?>class="button prdctfltr_woocommerce_filter_submit" href="#">
					<?php
						if ( $curr_options['wc_settings_prdctfltr_submit'] !== '' ) {
							echo $curr_options['wc_settings_prdctfltr_submit'];
						}
						else {
							_e('Filter selected', 'prdctfltr');
						}
					?>
				</a>
			<?php
				}
				if ( $curr_options['wc_settings_prdctfltr_disable_sale'] == 'no' ) {
				?>
				<span class="prdctfltr_sale">
					<?php
					printf('<label%2$s><input name="sale_products" type="checkbox"%3$s/><span>%1$s</span></label>', __('Show only products on sale' , 'prdctfltr'), ( isset($pf_activated['sale_products']) ? ' class="prdctfltr_active"' : '' ), ( isset($pf_activated['sale_products']) ? ' checked' : '' ) );
					?>
				</span>
				<?php
				}
				if ( $curr_options['wc_settings_prdctfltr_disable_instock'] == 'no' && !in_array('instock', $curr_elements) ) {
				?>
				<span class="prdctfltr_instock">
				<?php
					$curr_instock = get_option( 'wc_settings_prdctfltr_instock', 'no' );

					if ( $curr_instock == 'yes' ) {
						printf('<label%2$s><input name="instock_products" type="checkbox" value="both"%3$s/><span>%1$s</span></label>', __('Show out of stock products' , 'prdctfltr'), ( isset($pf_activated['instock_products']) ? ' class="prdctfltr_active"' : '' ), ( isset($pf_activated['instock_products']) ? ' checked' : '' ) );
					}
					else {
						printf('<label%2$s><input name="instock_products" type="checkbox" value="in"%3$s/><span>%1$s</span></label>', __('In stock only' , 'prdctfltr'), ( isset($pf_activated['instock_products']) ? ' class="prdctfltr_active"' : '' ), ( isset($pf_activated['instock_products']) ? ' checked' : '' ) );
					}
				?>
				</span>
				<?php
				}
				if ( $curr_options['wc_settings_prdctfltr_disable_reset'] == 'no' && isset($pf_activated) && !empty($pf_activated) ) {
				?>
				<span class="prdctfltr_reset">
				<?php
					printf('<label><input name="reset_filter" type="checkbox" /><span>%1$s</span></label>', __('Clear all filters' , 'prdctfltr') );
				?>
				</span>
				<?php
				}
			?>
			</div>
		<?php
			$out = ob_get_clean();

			echo $out;
		}

		public static function get_customized_term( $value, $name, $count, $customization, $checked = '' ) {

			if ( !isset( $customization['style'] ) ) {
				return;
			}

			$key = 'term_' . $value;
			$tooltip = 'tooltip_' . $value;
			$input = '';

			if ( $checked !== '' ) {
				$input = '<input type="checkbox" value="' . $value . '"' . $checked . '/>';
			}

			$tip = ( $value == '' ? __( 'None', 'prdctfltr' ) : ( isset( $customization['settings'][$tooltip] ) ? $customization['settings'][$tooltip] : false ) );
			$count = $count !== false ? ' <span class="prdctfltr_customize_count">' . $count . '</span>' : '';

			switch ( $customization['style'] ) {
				case 'text':
					$insert = '<span class="prdctfltr_customize_' . $customization['settings']['type'] . ' prdctfltr_customize"><span class="prdctfltr_customize_name">' . $name . '</span>' . $count . ( $tip !== false ? '<span class="prdctfltr_tooltip"><span>' . $tip . '</span></span>' : '' ) . $input . '</span>';
				break;
				case 'color':
					if ( isset( $customization['settings'][$key] ) ) {
						$insert = '<span class="prdctfltr_customize_block prdctfltr_customize"><span class="prdctfltr_customize_color" style="background-color:' . $customization['settings'][$key] . ';"></span>' . $count . ( $tip !== false ? '<span class="prdctfltr_tooltip"><span>' . $tip . '</span></span>' : '' ) . $input . '</span>';
					}
				break;
				case 'image':
					if ( isset( $customization['settings'][$key] ) ) {
						$insert = '<span class="prdctfltr_customize_block prdctfltr_customize"><span class="prdctfltr_customize_image"><img src="' . esc_url( $customization['settings'][$key] ) . '" /></span>' . $count . ( $tip !== false ? '<span class="prdctfltr_tooltip"><span>' . $tip . '</span></span>' : '' ) . $input . '</span>';
					}
				break;
				case 'image-text':
					if ( isset( $customization['settings'][$key] ) ) {
						$insert = '<span class="prdctfltr_customize_block prdctfltr_customize"><span class="prdctfltr_customize_image_text"><img src="' . esc_url( $customization['settings'][$key] ) . '" /></span>' . $count . ( $tip !== false ? '<span class="prdctfltr_customize_image_text_tip">' . $tip . '</span>' : $name ) . $input . '</span>';
					}
				break;
				case 'select':
					$insert = '<span class="prdctfltr_customize_select prdctfltr_customize"><span class="prdctfltr_customize_name">' . $name . '</span>' . $count . $input . '</span>';
				break;
				default :
					if ( isset( $customization['settings'][$key] ) ) {
						$insert = $customization['settings'][$key];
					}
				break;
			}

			if ( !isset( $insert ) ) {
				$insert = '';
			}

			return $insert;

		}

		public static function add_customized_terms_css( $id, $customization ) {

			if ( $customization['settings']['type'] == 'border' ) {
				$css_entry = sprintf( '%1$s .prdctfltr_customize {border-color:%2$s;color:%2$s;}%1$s label.prdctfltr_active .prdctfltr_customize {border-color:%3$s;color:%3$s;}%1$s label.pf_adoptive_hide .prdctfltr_customize {border-color:%4$s;color:%4$s;}', '.' . $id, $customization['settings']['normal'], $customization['settings']['active'], $customization['settings']['disabled'] );
			}
			else if ( $customization['settings']['type'] == 'background' ) {
				$css_entry = sprintf( '%1$s .prdctfltr_customize {background-color:%2$s;}%1$s label.prdctfltr_active .prdctfltr_customize {background-color:%3$s;}%1$s label.pf_adoptive_hide .prdctfltr_customize {background-color:%4$s;}', '.' . $id, $customization['settings']['normal'], $customization['settings']['active'], $customization['settings']['disabled'] );
			}
			else if ( $customization['settings']['type'] == 'round' ) {
				$css_entry = sprintf( '%1$s .prdctfltr_customize {background-color:%2$s;border-radius:50%%;}%1$s label.prdctfltr_active .prdctfltr_customize {background-color:%3$s;}%1$s label.pf_adoptive_hide .prdctfltr_customize {background-color:%4$s;}', '.' . $id, $customization['settings']['normal'], $customization['settings']['active'], $customization['settings']['disabled'] );
			}
			else {
				$css_entry = '';
			}

			if ( !isset( self::$settings['css'] ) ) {
				self::$settings['css'] = $css_entry;
			}
			else {
				self::$settings['css'] .= $css_entry;
			}

		}

		public static function prdctfltr_add_css() {
			if ( isset( self::$settings['css'] ) ) {
?>
				<style type="text/css">
					<?php echo self::$settings['css']; ?>
				</style>
<?php
			}
		}

		public static function get_filter_customization( $filter, $key ) {

			$language = self::prdctfltr_wpml_language();

			if ( $key !== '' ) {
				if ( $language !== false ) {
					$get_customization = get_option( $key . '_' . $language, '' );
				}
				else {
					$get_customization = get_option( $key, '' );
				}

				if ( $get_customization !== '' && isset( $get_customization['filter'] ) && $get_customization['filter'] = $filter ) {
					$customization = $get_customization;
				}
			}

			if ( !isset( $customization ) ) {
				$customization = array();
			}

			return $customization;

		}

		function prdctfltr_analytics() {

			check_ajax_referer( 'prdctfltr_analytics', 'pf_nonce' );

			$data = isset( $_POST['pf_filters'] ) ? $_POST['pf_filters'] : '';

			if ( empty( $data ) ) {
				die();
				exit;
			}

			$forbidden = array( 'min_price', 'max_price', 'rng_min_price', 'rng_max_price', 'orderby', 'products_per_page', 'sale_products', 'instock_products' );
			foreach( $data as $k => $v ) {
				if ( in_array( $k, $forbidden ) ) {
					unset( $data[$k] );
				}
				else if ( substr( $k, 0, 4 ) == 'rng_' ) {
					unset( $data[$k] );
				}
			}

			$stats = get_option( 'wc_settings_prdctfltr_filtering_analytics_stats', array() );

			if ( empty( $stats ) ) {
				foreach( $data as $k =>$v ) {
					$stats[$k][$v] = 1;
				}
			}
			else {
				foreach( $data as $k =>$v ) {
					if ( array_key_exists( $k, $stats ) ) {
						if ( array_key_exists( $v, $stats[$k] ) ) {
							$stats[$k][$v] = $stats[$k][$v] + 1;
						}
						else {
							$stats[$k][$v] = 1;
						}
					}
					else {
						$stats[$k][$v] = 1;
					}
				}
			}

			update_option( 'wc_settings_prdctfltr_filtering_analytics_stats', $stats );

			die( 'Updated!' );
			exit;
		}

		public static function get_term_count( $has, $of ) {
			if ( isset( self::$settings['instance']['wc_settings_prdctfltr_show_counts_mode'] ) ) {

				$set = self::$settings['instance']['wc_settings_prdctfltr_show_counts_mode'];

				switch( $set ) {
					case 'default' :
						return $has . apply_filters( 'prdctfltr_count_separator', '/' ) . $of;
					break;
					case 'count' :
						return $has;
					break;
					case 'total' :
						return $of;
					break;
					default:
						return '';
					break;
				}
			}
		}

		public static function nice_number( $n ) {
			$n = ( 0 + str_replace( ',', '', $n ) );

			if( !is_numeric( $n ) ){
				return false;
			}

			if ( $n > 1000000000000 ) {
				return round( ( $n / 1000000000000 ) , 1 ).' ' . __( 'trillion' , 'prdctfltr' );
			}
			else if ( $n > 1000000000 ) {
				return round( ( $n / 1000000000 ) , 1 ).' ' . __( 'billion' , 'prdctfltr' );
			}
			else if ( $n > 1000000 ) {
				return round( ( $n / 1000000 ) , 1 ).' ' . __( 'million' , 'prdctfltr' );
			}
			else if ( $n > 1000 ) {
				return round( ( $n / 1000 ) , 1 ).' ' . __( 'thousand' , 'prdctfltr' );
			}

			return number_format($n);
		}

		public static function price_to_float( $s ) {

			$s = str_replace( ',', '.', $s );
			$s = preg_replace('/&.*?;/', '', $s);

			$hasCents = (substr( $s, -3, 1 ) == '.');
			$s = str_replace( '.', '', $s );
			if ( $hasCents ) {
				$s = substr( $s, 0, -2 ) . '.' . substr( $s, -2 );
			}

			return (float) $s;
		}

		function add_wc_shortcode_filter( $query_args, $atts, $loop_name ) {
			$query_args['prdctfltr'] = 'active';
			return $query_args;
		}

	}
	add_action( 'woocommerce_init', array( 'WC_Prdctfltr', 'init' ) );

	include_once ( 'lib/pf-characteristics.php' );
	include_once ( 'lib/pf-widget.php' );
	include_once ( 'lib/pf-shortcode.php' );
	include_once ( 'lib/pf-variable-override.php' );
	if ( is_admin() ) {
		include_once ( 'lib/pf-settings.php' );
		include_once ( 'lib/pf-attribute-thumbnails.php' );
		$purchase_code = get_option( 'wc_settings_prdctfltr_purchase_code', '' );

		if ( $purchase_code ) {
			require 'lib/update/plugin-update-checker.php';
			$pf_check = PucFactory::buildUpdateChecker(
				'http://mihajlovicnenad.com/envato/verify_json.php?k=' . $purchase_code,
				__FILE__
			);
		}

	}

?>