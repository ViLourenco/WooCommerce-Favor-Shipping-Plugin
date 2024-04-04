<?php

if( ! class_exists( 'WC_Favor_Shipping_Integration' ) ) :

    class WC_Favor_Shipping_Integration extends WC_Integration {

    /**
     * Constructor for the class.
     *
     * Initializes the object by setting the ID, title, and description of the shipping method.
     * Initializes the form fields and settings.
     * Retrieves the token from the options.
     * Adds an action to update the integration options.
     *
     * @return void
     * 
     * @since 1.0.0
     */

        /**
        * The API key for authentication.
        *
        * @var string
        */
        protected $api_key = '';

        /**
        * The CNPJ or CPF for authentication.
        *
        * @var string
        */
        protected $cpf_cnpj = '';

        /**
         * A description of the entire PHP function.
         *
         */
        public function __construct() {
            global $woocommerce;

            $this->id = 'favor_plugin_shipping';
            $this->method_title = 'Favor Shipping';
            $this->method_description = 'Plugin de integração que conecta a API da Favor ao checkout WooCommerce.';

            $this->init_form_fields();
            $this->init_settings();

            $this->api_key = $this->get_option('api_key');
            $this->cpf_cnpj = $this->get_option('cpf_cnpj');

            add_action( 'woocommerce_update_options_integration_' . $this->id, array( $this, 'process_admin_options' ) );
        }

        /**
         * Initializes the form fields for the PHP function.
         *
         */
        public function init_form_fields() {
            $this->form_fields = array(
                'api_key' => array(
                    'title'       => __( 'Api Key Favor:', 'favor-shipping-shipping' ),
                    'type'        => 'text',
                    'description' => 'Digite seu api key referente a sua conta na Favor.',
                    'desc_tip'    => true,
                    'default'     => '',
                ),               
                'cpf_cnpj' => array(
                    'title'       => __( 'CPF/CNPJ:', 'favor-shipping-shipping' ),
                    'type'        => 'text',
                    'description' => 'Digite seu CNPJ ou CPF que é utilizado na sua loja.',
                    'desc_tip'    => true,
                    'default'     => '',
                ),                   
            );
        }        
    }

endif;

return new WC_Favor_Shipping_Integration();