<?php
/**
 * Favor Shipping Calculator Class
 *
 * Handles the shipping calculator on product pages.
 *
 * @package Favor_Shipping
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * WC_Favor_Shipping_Calculator class.
 */
class WC_Favor_Shipping_Calculator {

    /**
     * Constructor.
     */
    public function __construct() {
        // Only load if calculator is enabled
        if ( ! WC_Favor_Shipping_Settings::is_calculator_enabled() ) {
            return;
        }

        // Enqueue scripts and styles
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

        // Add calculator to product page
        add_action( 'woocommerce_product_meta_end', array( $this, 'display_calculator' ), 10 );

        // AJAX handlers
        add_action( 'wp_ajax_favor_calculate_shipping', array( $this, 'ajax_calculate_shipping' ) );
        add_action( 'wp_ajax_nopriv_favor_calculate_shipping', array( $this, 'ajax_calculate_shipping' ) );

        // Add inline styles with custom colors
        add_action( 'wp_head', array( $this, 'add_custom_styles' ) );
    }

    /**
     * Enqueue frontend scripts and styles.
     */
    public function enqueue_scripts() {
        if ( ! is_product() ) {
            return;
        }

        $plugin_url = plugin_dir_url( dirname( dirname( dirname( __FILE__ ) ) ) . '/favor-shipping-plugin.php' );

        wp_enqueue_style(
            'favor-calculator',
            $plugin_url . 'assets/css/favor-calculator.css',
            array(),
            Favor_Shipping_Plugin::VERSION
        );

        wp_enqueue_script(
            'favor-calculator',
            $plugin_url . 'assets/js/favor-calculator.js',
            array( 'jquery' ),
            Favor_Shipping_Plugin::VERSION,
            true
        );

        wp_localize_script(
            'favor-calculator',
            'favorCalculator',
            array(
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'favor_calculate_shipping' ),
                'strings' => array(
                    'calculating' => __( 'Calculando...', 'favor-despaches-woocommerce-plugin' ),
                    'error'       => __( 'Erro ao calcular frete. Por favor, tente novamente.', 'favor-despaches-woocommerce-plugin' ),
                    'invalidCep'  => __( 'CEP inválido. Por favor, digite um CEP válido.', 'favor-despaches-woocommerce-plugin' ),
                ),
            )
        );
    }

    /**
     * Display calculator on product page.
     */
    public function display_calculator() {
        global $product;

        if ( ! $product || ! $product->needs_shipping() ) {
            return;
        }

        ?>
        <div class="favor-shipping-calculator" id="favor-shipping-calculator" data-product-id="<?php echo esc_attr( $product->get_id() ); ?>">
            <div class="favor-calculator-container">
                <h3 class="favor-calculator-title"><?php esc_html_e( 'Calcular Frete', 'favor-despaches-woocommerce-plugin' ); ?></h3>
                <div class="favor-calculator-input-group">
                    <input
                        type="text"
                        id="favor-cep-input"
                        class="favor-cep-input"
                        placeholder="00000-000"
                        maxlength="9"
                    />
                    <button type="button" id="favor-calculate-btn" class="favor-calculate-btn">
                        <?php esc_html_e( 'Calcular', 'favor-despaches-woocommerce-plugin' ); ?>
                    </button>
                </div>
                <div id="favor-calculator-results" class="favor-calculator-results" style="display: none;"></div>
                <div id="favor-calculator-loading" class="favor-calculator-loading" style="display: none;">
                    <span class="favor-spinner"></span>
                    <span><?php esc_html_e( 'Calculando...', 'favor-despaches-woocommerce-plugin' ); ?></span>
                </div>
                <div id="favor-calculator-error" class="favor-calculator-error" style="display: none;"></div>
            </div>
        </div>
        <?php
    }

    /**
     * AJAX handler for calculating shipping.
     */
    public function ajax_calculate_shipping() {
        check_ajax_referer( 'favor_calculate_shipping', 'nonce' );

        $cep = isset( $_POST['cep'] ) ? sanitize_text_field( $_POST['cep'] ) : '';
        $product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;

        // Validate CEP
        $cep = preg_replace( '/[^0-9]/', '', $cep );
        if ( strlen( $cep ) !== 8 ) {
            wp_send_json_error( array( 'message' => __( 'CEP inválido. Por favor, digite um CEP válido.', 'favor-despaches-woocommerce-plugin' ) ) );
        }

        // Get product
        $product = wc_get_product( $product_id );
        if ( ! $product || ! $product->needs_shipping() ) {
            wp_send_json_error( array( 'message' => __( 'Produto inválido ou não requer envio.', 'favor-despaches-woocommerce-plugin' ) ) );
        }

        // Create package from product
        $package_data = $this->get_product_package( $product );

        // Call API
        $api = new WC_Favor_Shipping_API( $package_data, $cep );
        $shipping_data = $api->get_shipping_data();

        if ( ! $shipping_data || is_wp_error( $shipping_data ) ) {
            wp_send_json_error( array( 'message' => __( 'Erro ao calcular frete. Por favor, tente novamente.', 'favor-despaches-woocommerce-plugin' ) ) );
        }

        // Format results
        $results = $this->format_shipping_results( $shipping_data );

        wp_send_json_success( array( 'results' => $results ) );
    }

    /**
     * Get package data from product.
     *
     * @param WC_Product $product Product object.
     * @return array Package data.
     */
    private function get_product_package( $product ) {
        $height = wc_get_dimension( (float) $product->get_height(), 'cm' );
        $width  = wc_get_dimension( (float) $product->get_width(), 'cm' );
        $length = wc_get_dimension( (float) $product->get_length(), 'cm' );
        $weight = WC_Favor_Shipping_Package::get_weight_in_grams( (float) $product->get_weight() );

        // Default values if not set
        if ( empty( $height ) ) {
            $height = 2;
        }
        if ( empty( $width ) ) {
            $width = 11;
        }
        if ( empty( $length ) ) {
            $length = 16;
        }
        if ( empty( $weight ) ) {
            // Default weight: 300 grams (equivalent to 0.3 kg)
            $weight = 300;
        }

        return array(
            'height' => $height,
            'width'  => $width,
            'length' => $length,
            'weight' => $weight,
        );
    }

    /**
     * Format shipping results for display.
     *
     * @param array $shipping_data Raw shipping data from API.
     * @return array Formatted results.
     */
    private function format_shipping_results( $shipping_data ) {
        $results = array();

        foreach ( $shipping_data as $service_name => $service_data ) {
            if ( empty( $service_data['disponivel'] ) ) {
                continue;
            }

            $deadline = '';
            if ( ! empty( $service_data['prazoEntrega'] ) ) {
                $deadline = $service_data['prazoEntrega'] . ' ' . _n( 'dia útil', 'dias úteis', (int) $service_data['prazoEntrega'], 'favor-despaches-woocommerce-plugin' );
            }

            $price = 0;
            if ( ! empty( $service_data['precoClienteTotal'] ) ) {
                $price = floatval( $service_data['precoClienteTotal'] );
            }

            $results[] = array(
                'name'     => $service_name,
                'deadline' => $deadline,
                'price'    => $price,
            );
        }

        return $results;
    }

    /**
     * Add custom inline styles based on settings.
     */
    public function add_custom_styles() {
        if ( ! is_product() ) {
            return;
        }

        $primary_color = WC_Favor_Shipping_Settings::get_calculator_primary_color();
        $error_color = WC_Favor_Shipping_Settings::get_calculator_error_color();
        $bg_color = WC_Favor_Shipping_Settings::get_calculator_bg_color();
        $results_bg_color = WC_Favor_Shipping_Settings::get_calculator_results_bg_color();
        $text_color = WC_Favor_Shipping_Settings::get_calculator_text_color();
        $text_secondary_color = WC_Favor_Shipping_Settings::get_calculator_text_secondary_color();
        $border_color = WC_Favor_Shipping_Settings::get_calculator_border_color();
        $font_size = WC_Favor_Shipping_Settings::get_calculator_font_size();
        $border_radius = WC_Favor_Shipping_Settings::get_calculator_border_radius();

        // Map font sizes
        $font_sizes = array(
            'small'  => '12px',
            'medium' => '14px',
            'large'  => '16px',
        );
        $font_size_value = isset( $font_sizes[ $font_size ] ) ? $font_sizes[ $font_size ] : '14px';

        // Map border radius
        $border_radius_map = array(
            'none'   => '0',
            'small'  => '4px',
            'medium' => '8px',
            'large'  => '12px',
        );
        $border_radius_value = isset( $border_radius_map[ $border_radius ] ) ? $border_radius_map[ $border_radius ] : '8px';

        // Handle theme presets
        $theme = WC_Favor_Shipping_Settings::get_calculator_theme();
        if ( 'dark' === $theme ) {
            $bg_color = '#1a1a1a';
            $results_bg_color = '#1a1a1a';
            $text_color = '#ffffff';
            $text_secondary_color = '#aaaaaa';
            $border_color = '#333333';
        } elseif ( 'light' === $theme ) {
            $bg_color = '#ffffff';
            $results_bg_color = '#ffffff';
            $text_color = '#1a1a1a';
            $text_secondary_color = '#777777';
            $border_color = '#e0e0e0';
        } elseif ( 'auto' === $theme ) {
            // Auto-detect based on system preference
            // This will be handled by CSS media query
        }

        ?>
        <style id="favor-calculator-custom-styles">
            .favor-shipping-calculator {
                --favor-primary-color: <?php echo esc_attr( $primary_color ); ?>;
                --favor-error-color: <?php echo esc_attr( $error_color ); ?>;
                --favor-bg-color: <?php echo esc_attr( $bg_color ); ?>;
                --favor-results-bg-color: <?php echo esc_attr( $results_bg_color ); ?>;
                --favor-text-color: <?php echo esc_attr( $text_color ); ?>;
                --favor-text-secondary-color: <?php echo esc_attr( $text_secondary_color ); ?>;
                --favor-border-color: <?php echo esc_attr( $border_color ); ?>;
                --favor-font-size: <?php echo esc_attr( $font_size_value ); ?>;
                --favor-border-radius: <?php echo esc_attr( $border_radius_value ); ?>;
            }
            <?php if ( 'auto' === $theme ) : ?>
            @media (prefers-color-scheme: dark) {
                .favor-shipping-calculator {
                    --favor-bg-color: #1a1a1a;
                    --favor-results-bg-color: #1a1a1a;
                    --favor-text-color: #ffffff;
                    --favor-text-secondary-color: #aaaaaa;
                    --favor-border-color: #333333;
                }
            }
            <?php endif; ?>
        </style>
        <?php
    }
}

new WC_Favor_Shipping_Calculator();
