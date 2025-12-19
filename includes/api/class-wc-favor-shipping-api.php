<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * WC_Favor_Shipping_Package_WebService class.
 */
class WC_Favor_Shipping_API {

    /**
     * The URL of the API endpoint.
     *
     * @var string
     */
    protected $url;

    /**
     * The HTTP method to use for the API request.
     *
     * @var string
     */
    protected $method;

    /**
     * The base postcode for the shipping origin.
     *
     * @var string
     */
    protected $base_postcode;

    /**
     * The destination postcode for the shipping destination.
     *
     * @var string
     */
    protected $destiny_postcode;

    /**
     * The package details for the shipping.
     *
     * @var array
     */
    protected $package = array();

    /**
     * The API key for authentication.
     *
     * @var string
     */
    protected $api_key;

    /**
     * The user ID for authentication.
     *
     * @var string
     */
    protected $user_id;

    /**
     * The CNPJ or CPF for authentication.
     *
     * @var string
     */
    protected $cpf_cnpj;


    public function __construct( $package = '', $destiny_postcode = '' ) {        
        $this->destiny_postcode = str_replace( "-", "", $destiny_postcode );                   
        $this->package = $package;                 
        $this->set_method();
        $this->set_base_postcode();
        $this->get_data_access();
    }

    /**
     * Set the URL for the API endpoint.
     */
    protected function set_url( $url ) {
        $this->url = $url;
    }

    /**
     * Set the HTTP method to POST.
     */
    protected function set_method() {
        $this->method = 'POST';
    }   

    /**
     * Sets the base postcode for the object.
     *
     * This function retrieves the base postcode from the WC()->countries object and assigns it to the $base_postcode property of the current object.
     *
     * @return void
     */
    protected function set_base_postcode() {
        $this->base_postcode = str_replace( "-", "", WC()->countries->get_base_postcode() );
    }  
    
    /**
     * Retrieves the data access settings and assigns the API key and user ID to class properties.
     *
     * @return void
     */
    protected function get_data_access() {
        $this->api_key = WC_Favor_Shipping_Settings::get_api_key();
        $this->cpf_cnpj = WC_Favor_Shipping_Settings::get_cpf_cnpj();        
    }

    /**
     * Get the shipping data from the API.
     *
     * @return mixed The shipping data from the API
     */
    public function get_shipping_data() {
        $this->set_url('https://www.favordespaches.com.br/api/v1/calc-preco-prazo/woocommerce');

        WC_Favor_Shipping_Logger::info( 'Requisição API: Obter dados de envio', array(
            'url' => $this->url,
            'origin_cep' => $this->base_postcode,
            'destiny_cep' => $this->destiny_postcode,
        ) );

        if ( empty( $this->api_key ) ) {
            WC_Favor_Shipping_Logger::error( 'Chave de API Favor não configurada' );
            return false;
        }

        $response = wp_remote_post($this->url, array(
            'method'    => $this->method,
            'body'      => json_encode(array(
                "cepDestino" => $this->destiny_postcode,
                "cepOrigem" => $this->base_postcode,
                "formato" => 1,
                "peso" => $this->package['weight'],
                "altura" => $this->package['height'],
                "comprimento" => $this->package['length'],
                "diametro" => 0,
                "largura" => $this->package['width'],
                "avisoRecebimento" => "N",
                "maoPropria" => "N",
                "valorDeclarado" => 0
            )),
            'headers'   => array(
                'Content-Type' => 'application/json',
                'x-api-key' => $this->api_key,
            ),
            'timeout'   => 45,
            'redirection' => 5,
            'httpversion' => '1.0',
            'blocking' => true,
            'cookies' => array(),
        ));

        if ( is_wp_error( $response ) ) {
            WC_Favor_Shipping_Logger::error( 'Calculo de Frete - Erro de conexão API: ' . $response->get_error_message() );
            wc_add_notice('Calculo de Frete - Problemas na conexão com a API de fretes. Por favor, tente novamente!', 'error');
            return false;
        }

        $response_code = wp_remote_retrieve_response_code( $response );
        $response_body = wp_remote_retrieve_body( $response );
        $decoded_body = json_decode( $response_body, true );

        // Check for HTTP errors or error message in body
        if ( $response_code !== 200 || ! empty( $decoded_body['msgErro'] ) ) {
            $error_msg = ! empty( $decoded_body['msgErro'] ) ? $decoded_body['msgErro'] : 'Erro ' . $response_code;
            
            WC_Favor_Shipping_Logger::error( 'Calculo de Frete - Erro de API Favor (' . $response_code . '): ' . $error_msg, array(
                'body' => $response_body
            ) );

            if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
                wc_add_notice( 'Calculo de Frete - Erro de API Favor: ' . $error_msg, 'error' );
            }
            
            return false;
        }

        return $decoded_body;
    }

    public function get_label_data( $request_body, $order_obj ) {
        $this->set_url('https://favordespaches.com.br/api/shipments/woocommerce');

        if ( empty( $this->api_key ) ) {
            WC_Favor_Shipping_Logger::error( 'Chave de API Favor não configurada' );
            if( is_object( $order_obj ) ) {
                $order_obj->add_order_note( 'Falha ao gerar etiqueta Favor: Chave de API não configurada' );
            }
            return false;
        }

        $order_id = is_object( $order_obj ) ? $order_obj->get_id() : 0;
        WC_Favor_Shipping_Logger::info( 'Requisição API: Gerar etiqueta para o pedido #' . $order_id, array(
            'url' => $this->url,
        ) );

        $response = wp_remote_post($this->url, array(
            'method'    => $this->method,
            'body'      => json_encode( $request_body ),
            'headers'   => array(
                'Content-Type' => 'application/json',
                'x-api-key' => $this->api_key,
            ),
            'timeout'   => 45,
            'redirection' => 5,
            'httpversion' => '1.0',
            'blocking' => true,
            'cookies' => array(),
        ));

        if ( is_wp_error( $response ) ) {
            WC_Favor_Shipping_Logger::error( 'Erro de conexão API ao gerar etiqueta: ' . $response->get_error_message() );
            if( is_object( $order_obj ) ) {
                $order_obj->add_order_note( 'Falha ao gerar etiqueta Favor: Erro de conexão (' . $response->get_error_message() . ')' );
            }
            return false;
        }

        $response_body = wp_remote_retrieve_body( $response );
        $response_body_decoded = json_decode( $response_body, true );
        
        if ( ! isset( $response_body_decoded['success'] ) || ! $response_body_decoded['success'] ) {
            WC_Favor_Shipping_Logger::error( 'A API retornou erro ou sucesso=false para o pedido #' . $order_id ); 
            WC_Favor_Shipping_Logger::error( 'Resposta da API: ' . $response_body );
            
            if( is_object( $order_obj ) ) {
                $error_msg = isset( $response_body_decoded['error'] ) ? $response_body_decoded['error'] : 'Erro desconhecido';
                $order_obj->add_order_note( 'Falha ao gerar etiqueta Favor: ' . $error_msg );
            }
            return false;
        }

        if( empty( $response_body_decoded['label'] ) ) {
            WC_Favor_Shipping_Logger::error( 'Campo da etiqueta está vazio na resposta de sucesso para o pedido #' . $order_id );
            return false;
        }

        // Prepare upload directory
        $upload_dir = wp_upload_dir();
        $favor_dir = $upload_dir['basedir'] . '/favor-shipping-labels';
        
        if ( ! file_exists( $favor_dir ) ) {
            wp_mkdir_p( $favor_dir );
        }

        $timestamp = time();

        // Process and save label PDF
        $label_base64 = $response_body_decoded['label'];
        $label_bin = base64_decode($label_base64, true);

        if (strpos($label_bin, '%PDF') !== 0) {            
            WC_Favor_Shipping_Logger::error( 'Assinatura de arquivo PDF ausente para a etiqueta no pedido #' . $order_id ); 
            WC_Favor_Shipping_Logger::error( print_r( $response, true ) );   
            if( is_object( $order_obj ) ) {
                $order_obj->add_order_note( 'PDF da etiqueta não gerado (assinatura inválida), contatar o suporte!' );
            }
            return false;                     
        }

        $label_filename = 'order-' . $order_id . '-label-' . $timestamp . '.pdf';
        $label_filepath = $favor_dir . '/' . $label_filename;
        file_put_contents($label_filepath, $label_bin);
        $label_url = $upload_dir['baseurl'] . '/favor-shipping-labels/' . $label_filename;

        WC_Favor_Shipping_Logger::info( 'PDF da etiqueta salvo com sucesso para o pedido #' . $order_id . ': ' . $label_filename );

        // Process and save content declaration PDF if present
        $content_declaration_url = '';
        if( ! empty( $response_body_decoded['contentDeclaration'] ) ) {
            $content_base64 = $response_body_decoded['contentDeclaration'];
            $content_bin = base64_decode($content_base64, true);

            if (strpos($content_bin, '%PDF') === 0) {
                $content_filename = 'order-' . $order_id . '-content-declaration-' . $timestamp . '.pdf';
                $content_filepath = $favor_dir . '/' . $content_filename;
                file_put_contents($content_filepath, $content_bin);
                $content_declaration_url = $upload_dir['baseurl'] . '/favor-shipping-labels/' . $content_filename;
                WC_Favor_Shipping_Logger::info( 'PDF da declaração de conteúdo salvo para o pedido #' . $order_id . ': ' . $content_filename );
            } else {
                WC_Favor_Shipping_Logger::error( 'Assinatura de arquivo PDF ausente para a declaração de conteúdo no pedido #' . $order_id );
            }
        }

        // Save URLs to order meta
        if( is_object( $order_obj ) ) {
            $order_obj->update_meta_data( '_favor_shipping_label_url', $label_url );
            $order_obj->update_meta_data( '_favor_shipping_label_generated', $timestamp );
            
            if( ! empty( $content_declaration_url ) ) {
                $order_obj->update_meta_data( '_favor_content_declaration_url', $content_declaration_url );
            }
            
            $order_obj->save();
            $date = new DateTime("@$timestamp");
            $date->setTimezone(new DateTimeZone('America/Sao_Paulo'));
            $order_obj->add_order_note( 'Etiqueta Favor gerada com sucesso em ' . $date->format('d/m/Y H:i:s') );
        }

        return true;
    }
}
