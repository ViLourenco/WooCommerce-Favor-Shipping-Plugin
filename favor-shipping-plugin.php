<?php
/**
 * Plugin Name: Favor Shipping Plugin
 * Version: 1.0.0
 * Plugin URI: https://favor.com.br
 * Description: Favor Shipping Plugin
 * Author: Vinícius Lourenço
 * Author URI: https://codyss.com.br
 * Requires at least: 4.4.0
 * Tested up to: 4.6.0
 *
 * Text Domain: favor-chipping-plugin
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
		const VERSION = '1.0.0';


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
			include_once 'includes/api/class-wc-favor-shipping-api.php';
			include_once 'includes/order/class-wc-favor-shipping-order.php';

			if( class_exists( 'WC_Integration' ) ) {
				include_once 'includes/integrations/class-wc-favor-shipping-integration.php';
				add_filter( 'woocommerce_integrations', array( $this, 'register_integration' ) );
			}
			add_action( 'woocommerce_shipping_init', array( $this, 'include_shipping' ) );
			add_filter( 'woocommerce_shipping_methods', array( $this, 'include_shipping_method' ) );
		}

		/**
		 * Registers the integration by adding the 'Favor_Shipping_WC_Integration' class to the integrations array.
		 *
		 * @param array $integrations The array of integrations.
		 * @return array The updated array of integrations.
		 */
		public function register_integration( $integrations ) {
			$integrations[] = 'WC_Favor_Shipping_Integration';
			return $integrations;
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

//https://foam-divan-776.notion.site/API-Favor-Despaches-Cota-o-de-Envios-25a413eccc8b4d03843cb7ccff968c12#ae1be7c23b3443d4a09afbc0ff80492d

// https://universal-capsule-876329.postman.co/workspace/API's-Favor-Despaches~dfdd2582-33a4-4c02-8eea-0ab2967ba186/request/31045824-d437ab10-ee77-4f8f-8264-5d9453badcdd?tab=body

// https://woo.com/document/shipping-method-api/

// https://webkul.com/blog/creating-new-shipping-method-woocommerce/

// https://www.cloudways.com/blog/create-woocommerce-custom-shipping-method-plugin/

// https://dev.codyss.com.br/favor-plugin/wp-admin/admin.php?page=wc-settings&tab=shipping&section=favorc-shipping-method

// https://github.com/siddkb/WooCommerceAramexShippingIntegration/blob/master/includes/class-wc-integration-aramex-integration.php#L17

// https://wordpress.org/support/topic/my-custom-shipping-method-doesnt-show-up-in-the-checkout-page/