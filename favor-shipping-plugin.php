<?php
/**
 * Plugin Name: Favor Despaches WooCommerce
 * Version: 1.1.0
 * Plugin URI: https://favordespaches.com.br
 * Description: Plugin Favor Despaches para WooCommerce
 * Author: Vinícius Lourenço
 * Author URI: https://codyss.com.br
 * Requires at least: 4.4.0
 * Tested up to: 4.6.0
 *
 * Text Domain: favor-despaches-woocommerce-plugin
 * Domain Path: /languages
 *
 * @package WordPress
 * @author  Vinícius Lourenço
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}


if ( ! class_exists( 'Favor_Shipping_Plugin' ) ) {

	/**
	 * Main Class.
	 */
	class Favor_Shipping_Plugin {


		/**
		* Plugin version.
		*
		* @var string
		*/
		const VERSION = '1.1.0';


		/**
		 * Instance of this class.
		 *
		 * @var object
		 */
		protected static $instance = null;

		/**
		 * Return an instance of this class.
		 *
		 * @return object single instance of this class.
		 */
		public static function get_instance() {
			if ( null === self::$instance ) {
				self::$instance = new self;
			}
			return self::$instance;
		}

		/**
		 * Constructor
		 */
		private function __construct() {
			if ( ! class_exists( 'WooCommerce' ) ) {
				add_action( 'admin_notices', array( $this, 'fallback_notice' ) );
			} else {
				$this->load_plugin_textdomain();
				$this->includes();
			}
		}

        /**
         * Method to call and run all the things that you need to fire when your plugin is activated.
         *
         */
        public static function activate() {
            include_once 'includes/favor-shipping-activate.php';
            Favor_Shipping_Activate::activate();

        }

        /**
         * Method to call and run all the things that you need to fire when your plugin is deactivated.
         *
         */
        public static function deactivate() {
            include_once 'includes/favor-shipping-deactivate.php';
            Favor_Shipping_Deactivate::deactivate();
        }

		/**
		 * Method to includes our dependencies.
		 *
		 * @var string
		 */
		public function includes() {
			include_once 'includes/favor-shipping-functionality.php';	
			include_once 'includes/utils/class-wc-favor-shipping-package.php';
			include_once 'includes/utils/class-wc-favor-shipping-logger.php';
			include_once 'includes/api/class-wc-favor-shipping-api.php';
			include_once 'includes/order/class-wc-favor-shipping-order.php';
			include_once 'includes/admin/class-wc-favor-shipping-settings.php';
			include_once 'includes/admin/class-wc-favor-shipping-logs-page.php';

			add_action( 'woocommerce_shipping_init', array( $this, 'include_shipping' ) );
			add_filter( 'woocommerce_shipping_methods', array( $this, 'include_shipping_method' ) );
			
			// Add custom store address fields
			add_filter( 'woocommerce_general_settings', array( $this, 'add_store_address_fields' ) );
		}

		/**
		 * Includes the shipping class if the WC_Shipping class exists.
		 *
		 * This function checks if the WC_Shipping class exists and if it does, it includes the shipping class file.
		 *
		 * @return void
		 */		
		public function include_shipping() {
			if( class_exists( 'WC_Shipping' ) ) {
				include_once 'includes/abstracts/class-wc-favor-shipping-methods.php';
			}
		}

		public function include_shipping_method( $methods ) {
			$methods['wc-favor-shipping-methods'] = 'WC_Favor_Shipping_Methods';
			return $methods;
		}	
		/**
		 * Load the plugin text domain for translation.
		 *
		 * @access public
		 * @return bool
		 */
		public function load_plugin_textdomain() {
			$locale = apply_filters( 'favor-shipping-locale', get_locale(), 'favor-shipping-locale' );
			return true;
		}

		/**
		 * Fallback notice.
		 *
		 * We need some plugins to work, and if any isn't active we'll show you!
		 */
		public function fallback_notice() {
			echo '<div class="error">';
			echo '<p>' . __( 'Favor Shipping Plugin: Needs the WooCommerce Plugin activated.', 'favor-shipping-locale' ) . '</p>';
			echo '</div>';
		}

		/**
		 * Add custom store address fields (Número and Bairro) to WooCommerce general settings.
		 *
		 * @param array $settings The existing settings array.
		 * @return array The modified settings array.
		 */
		public function add_store_address_fields( $settings ) {
			$new_settings = array();

			foreach ( $settings as $setting ) {
				$new_settings[] = $setting;

				// Insert our custom fields after woocommerce_store_address_2
				if ( isset( $setting['id'] ) && 'woocommerce_store_address_2' === $setting['id'] ) {
					$new_settings[] = array(
						'title'    => __( 'Número', 'favor-despaches-woocommerce-plugin' ),
						'desc'     => __( 'Número do endereço da loja', 'favor-despaches-woocommerce-plugin' ),
						'id'       => 'favor_store_address_number',
						'type'     => 'text',
						'css'      => 'min-width:300px;',
						'default'  => '',
						'desc_tip' => true,
					);

					$new_settings[] = array(
						'title'    => __( 'Bairro', 'favor-despaches-woocommerce-plugin' ),
						'desc'     => __( 'Bairro da loja', 'favor-despaches-woocommerce-plugin' ),
						'id'       => 'favor_store_neighborhood',
						'type'     => 'text',
						'css'      => 'min-width:300px;',
						'default'  => '',
						'desc_tip' => true,
					);
				}
			}

			return $new_settings;
		}
	}
}

/**
* Hook to run when your plugin is activated
*/
register_activation_hook( __FILE__, array( 'Favor_Shipping_Plugin', 'activate' ) );

/**
* Hook to run when your plugin is deactivated
*/
register_deactivation_hook( __FILE__, array( 'Favor_Shipping_Plugin', 'deactivate' ) );

/**
* Initialize the plugin.
*/
add_action( 'plugins_loaded', array( 'Favor_Shipping_Plugin', 'get_instance' ) );