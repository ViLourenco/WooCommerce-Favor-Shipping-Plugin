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
        foreach($order->get_shipping_methods() as $shipping_method ){
            $cod_servico = $shipping_method->get_meta('Serviço');             
        } 

        $remetente = array(
            "nomeRemetente" => get_option( 'blogname' ),
            "cepRemetente" =>  WC()->countries->get_base_postcode(),
            "logradouroRemetente" => WC()->countries->get_base_address(),
            "numeroRemetente" => "N/A",
            "complementoRemetente" => "",
            "bairroRemetente" => WC()->countries->get_base_address_2(),
            "cidadeRemetente" => WC()->countries->get_base_city(),
            "ufRemetente" => WC()->countries->get_base_state(),           
        );
        
        foreach( $order->get_items() as $item_id => $item ) {

            $product = wc_get_product( $item->get_product_id() );

            //Minimal dimensions
            $dimensaoLargura = wc_get_dimension( (float) $product->get_width(), 'cm' );
            if( $dimensaoLargura < 10 ) { $dimensaoLargura = 10; }

            $dimensaoComprimento = wc_get_dimension( (float) $product->get_length(), 'cm' );
            if( $dimensaoComprimento < 16 ) { $dimensaoComprimento = 16; }

            $dimensaoAltura = wc_get_dimension( (float) $product->get_height(), 'cm' );
            if( $dimensaoAltura < 2 ) { $dimensaoAltura = 2; }

            $objetosPostais[] = array(
                "codigoServicoPostagem" => $cod_servico,
                "peso" => wc_get_weight( (float) $product->get_weight(), 'kg' ) * 1000, 
                "destinatario" => array(
                    "nomeDestinatario" => $order->get_billing_first_name() . " " . $order->get_billing_last_name(),
                    "logradouroDestinatario" => $order->get_billing_address_1(),
                    "complementoDestinatario" => $order->get_billing_address_2(),
                    "numeroDestinatario" =>  $order->get_meta( '_billing_number' ),
                    "cpfCnpjDestinatario" => $order->get_meta( '_billing_cpf' ),
                    "bairroDestinatario" => $order->get_meta( '_billing_neighborhood' ),
                    "cidadeDestinatario" => $order->get_billing_city(),
                    "ufDestinatario" => $order->get_billing_state(),
                    "cepDestinatario" => str_replace( "-", "", $order->get_billing_postcode() ),
                    "telefoneDestinatario" => $order->get_meta( '_billing_cellphone' ),
                ),
                "dimensaoObjeto" => array(
                    "tipoObjeto" => "002",
                    "dimensaoAltura" => $dimensaoAltura,
                    "dimensaoLargura" => $dimensaoLargura,
                    "dimensaoComprimento" => $dimensaoComprimento,
                ),
                "declaracaoConteudo" => array(
                    array(
                        "conteudo" => $item->get_name(),
                        "quantidade" => $item->get_quantity(),
                        "valorUnitario" => intval( $product->get_price() ),
                    ),
                ), 
            );          
        }
        
        $api = new WC_Favor_Shipping_API();
        $api->get_label_data( $remetente, $objetosPostais, $cod_servico, $order );
    }
}

new WC_Favor_Shipping_Order();