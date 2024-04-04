<?php

if( ! class_exists( 'WC_Favor_Shipping_Methods' ) ) {

    class WC_Favor_Shipping_Methods extends WC_Shipping_Method {

        /**
         * Constructor for the class.
         *
         * @param int $instance_id The instance ID
         */
        public function __construct( $instance_id = 0 ) {
            $this->id = 'wc-favor-shipping-methods';       
            $this->instance_id = absint( $instance_id );                             
            $this->method_title = __('Favor Despaches - Método de Entrega' );
            $this->method_description = __('Plugin que conecta sua loja WooCommerce a Favor Despaches' );
            $this->title = __( 'Favor Despaches' );
            $this->supports = array( 'shipping-zones', 'instance-settings', 'instance-settings-modal');                      
            
            $this->init_settings();
            $this->init_form_fields();

            $this->enabled = $this->get_option( 'enabled' );
            
            add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
        }

        /**
         * Initializes the form fields.
         *
         */
        public function init_form_fields() {
            $this->instance_form_fields = array(
                'enabled' => array(
                    'title'   => __('Habilitar / Desabilitar' ),
                    'type'    => 'checkbox',
                    'label'   => __('Habilita este modo de entrega'  ),
                ),              
            );                   
        }

        /**
         * A description of the entire PHP function.
         *
         * @param array $package 
         */
        public function calculate_shipping( $package = array() ) {
            // Check if valid to be calculeted.
            if ( '' === $package['destination']['postcode'] || 'BR' !== $package['destination']['country'] ) {
                return;
            }                    
                
            $shipping = new WC_Favor_Shipping_Package( $package );
            $data = $shipping->get_data();

            $api = new WC_Favor_Shipping_API( $data, $package['destination']['postcode'] );
            $shipping_values = $api->get_shipping_data();

            $i = 0;
            foreach( $shipping_values as $shipping_name => $shipping_data ) {

                if( empty( $shipping_data['disponivel'] ) ) { continue; }


                $shipping_deadline = "";
                if( ! empty( $shipping_data['prazoEntrega'] ) ) { $shipping_deadline = " <small>(" . $shipping_data['prazoEntrega'] . " dias)</small>"; }

                $meta = array(
                    'Serviço' => $shipping_data['codigoServicoPostagem'],
                );

                $rates = array(
                    'id' => $shipping_name . "_" . $i,
                    'label'  => $shipping_name . $shipping_deadline,
                    'cost'   => $shipping_data['precoClienteTotal'],
                    'meta_data'   => $meta,            
                ); 
                
                $this->add_rate( $rates );
                $i++;
            }    
        }   
    }
}