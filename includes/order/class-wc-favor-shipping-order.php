<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * WC_Favor_Shipping_Order class.
 */
class WC_Favor_Shipping_Order {

    /**
     * Constructor for the class. Adds filters and actions for WooCommerce order actions.
     */
    public function __construct() {
        add_filter( 'woocommerce_order_actions', array( $this, 'generate_favor_shipping_label_order_action' ), 9999, 2 );
        add_action( 'woocommerce_order_action_favor_shipping_label', array( $this, 'get_labels' ) );
        add_action( 'add_meta_boxes', array( $this, 'add_order_labels_meta_box' ) );
    }

    /**
     * Generate the favor shipping label order action.
     *
     * @param datatype $actions description
     * @param datatype $order_id description
     * @return datatype
     */
    public function generate_favor_shipping_label_order_action( $actions, $order_id ) {
        $order = wc_get_order( $order_id );
        if ( in_array( $order->get_status(), wc_get_is_paid_statuses() ) ) {
            $actions['favor_shipping_label'] = 'Exibir etiqueta de despacho';
        }
        return $actions;
    }

    /**
     * Get labels from the order's shipping methods and display the 'codi_z' meta field.
     *
     * @param datatype $order The order object to retrieve shipping methods from.
     */
    public function get_labels( $order ) {
        // Check if labels already exist
        $existing_label_url = $order->get_meta( '_favor_shipping_label_url' );
        if ( ! empty( $existing_label_url ) ) {
            $order->add_order_note( 'Etiqueta Favor já foi gerada anteriormente. Use os links na barra lateral para baixar.' );
            return;
        }
        
        $data_access = get_option( 'woocommerce_favor_plugin_shipping_settings' );
        $cpf_cnpj_sender = isset($data_access['cpf_cnpj']) ? $data_access['cpf_cnpj'] : '';

        $remetente = array(
            "name" => get_option( 'blogname' ),
            "email" => get_option( 'admin_email' ), // Added email as it might be required or useful, though defined as undefined in schema example it often helps. keeping as per schema undefined if empty. Schema says string | undefined.
            "phone" => "", // Schema says string | undefined.
            "cpf_cnpj" => $cpf_cnpj_sender,
            "address" => array(
                "zip" => str_replace( "-", "", WC()->countries->get_base_postcode() ),
                "street" => WC()->countries->get_base_address(),
                "number" => "N/A", // WooCommerce base address doesn't usually store number separately? Defaulting to N/A as per previous code.
                "complement" => "",
                "neighborhood" => WC()->countries->get_base_address_2(),
                "city" => WC()->countries->get_base_city(),
                "state" => WC()->countries->get_base_state(),
            )
        );

        $packages = array();
        
        // Get shipping service from order shipping methods
        $service_name = 'PAC'; // Default
        foreach($order->get_shipping_methods() as $shipping_method ){
            $cod_servico = $shipping_method->get_meta('Serviço');
            if( $cod_servico ) {
                $service_name = $this->get_service_name_from_code( $cod_servico );
                break; 
            }
        } 
        
        foreach( $order->get_items() as $item_id => $item ) {
            $product = wc_get_product( $item->get_product_id() );
            //Minimal dimensions
            $width = wc_get_dimension( (float) $product->get_width(), 'cm' );
            if( $width < 10 ) { $width = 10; }

            $length = wc_get_dimension( (float) $product->get_length(), 'cm' );
            if( $length < 16 ) { $length = 16; }

            $height = wc_get_dimension( (float) $product->get_height(), 'cm' );
            if( $height < 2 ) { $height = 2; }

            $weight = wc_get_weight( (float) $product->get_weight(), 'kg' ) * 1000;

            $packages[] = array(
                "shipping_service_name" => $service_name,
                "content_declaration" => array(
                    array(
                        "content" => $item->get_name(),
                        "quantity" => $item->get_quantity(),
                        "unit_value" => intval( $product->get_price() )
                    )
                ),
                "package_data" => array(
                    "package_weight_grams" => $weight,
                    "package_width" => $width,
                    "package_height" => $height,
                    "package_length" => $length,
                    "package_type" => "BOX" // Defaulting to BOX
                )
            );
        }

        $receivers = array(
            array(
                "name" => $order->get_billing_first_name() . " " . $order->get_billing_last_name(),
                "email" => $order->get_billing_email(),
                "phone" => $order->get_meta( '_billing_cellphone' ) ? $order->get_meta( '_billing_cellphone' ) : $order->get_billing_phone(),
                "cpf_cnpj" => $order->get_meta( '_billing_cpf' ), // Assuming this meta key exists as per previous code
                "address" => array(
                    "zip" => str_replace( "-", "", $order->get_billing_postcode() ),
                    "street" => $order->get_billing_address_1(),
                    "number" => $order->get_meta( '_billing_number' ),
                    "complement" => $order->get_billing_address_2(),
                    "neighborhood" => $order->get_meta( '_billing_neighborhood' ),
                    "city" => $order->get_billing_city(),
                    "state" => $order->get_billing_state()
                ),
                "packages" => $packages
            )
        );

        $request_body = array(
            "sender" => $remetente,
            "receivers" => $receivers
        );
        
        $api = new WC_Favor_Shipping_API();
        $result = $api->get_label_data( $request_body, $order );
        
        if ( $result ) {
            $order->add_order_note( 'Etiqueta Favor gerada com sucesso!' );
        } else {
            $order->add_order_note( 'Erro ao gerar etiqueta Favor. Verifique os logs.' );
        }
    }

    /**
     * Add meta box to display Favor shipping labels on order edit page.
     */
    public function add_order_labels_meta_box() {
        add_meta_box(
            'favor_shipping_labels',
            'Etiquetas Favor',
            array( $this, 'render_order_labels_meta_box' ),
            'shop_order',
            'side',
            'high'
        );

        // For HPOS (High-Performance Order Storage)
        add_meta_box(
            'favor_shipping_labels',
            'Etiquetas Favor',
            array( $this, 'render_order_labels_meta_box' ),
            'woocommerce_page_wc-orders',
            'side',
            'high'
        );
    }

    /**
     * Render the meta box content with label download links.
     *
     * @param WP_Post|WC_Order $post_or_order The order post or order object.
     */
    public function render_order_labels_meta_box( $post_or_order ) {
        // Get order object
        if ( $post_or_order instanceof WC_Order ) {
            $order = $post_or_order;
        } else {
            $order = wc_get_order( $post_or_order->ID );
        }

        if ( ! $order ) {
            echo '<p>Pedido não encontrado.</p>';
            return;
        }

        $label_url = $order->get_meta( '_favor_shipping_label_url' );
        $content_url = $order->get_meta( '_favor_content_declaration_url' );
        $generated_timestamp = $order->get_meta( '_favor_shipping_label_generated' );

        if ( empty( $label_url ) ) {
            echo '<p>Nenhuma etiqueta gerada ainda.</p>';
            echo '<p><em>Use a ação "Exibir etiqueta de despacho" acima para gerar.</em></p>';
            return;
        }

        echo '<div class="favor-shipping-labels-box">';
        
        if ( $generated_timestamp ) {
            echo '<p><strong>Gerado em:</strong> ' . date( 'd/m/Y H:i:s', $generated_timestamp ) . '</p>';
        }

        echo '<p><strong>Documentos disponíveis:</strong></p>';
        echo '<ul style="margin-left: 20px;">';
        
        if ( ! empty( $label_url ) ) {
            echo '<li><a href="' . esc_url( $label_url ) . '" target="_blank" class="button button-primary" style="margin-bottom: 5px; display: inline-block;">📄 Download Etiqueta</a></li>';
        }
        
        if ( ! empty( $content_url ) ) {
            echo '<li><a href="' . esc_url( $content_url ) . '" target="_blank" class="button button-secondary" style="margin-bottom: 5px; display: inline-block;">📋 Download Declaração de Conteúdo</a></li>';
        }
        
        echo '</ul>';
        echo '</div>';
    }

    private function get_service_name_from_code( $code ) {
        $map = array(
            '04510' => 'PAC',
            '04014' => 'SEDEX',
            '04227' => 'MINI ENVIOS',
            '40169' => 'SEDEX 12',
            '40215' => 'SEDEX 10',
            '40290' => 'SEDEX HOJE',
        );
        return isset( $map[$code] ) ? $map[$code] : 'PAC';
    }
}

new WC_Favor_Shipping_Order();