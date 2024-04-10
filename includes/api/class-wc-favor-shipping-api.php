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
        $data_access = get_option( 'woocommerce_favor_plugin_shipping_settings' );
        $this->api_key = $data_access['api_key'];
        $this->cpf_cnpj = $data_access['cpf_cnpj'];        
    }

    /**
     * Get the shipping data from the API.
     *
     * @return mixed The shipping data from the API
     */
    public function get_shipping_data() {
        $this->set_url('https://www.favordespaches.com/api/v1/precoPrazo');

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
            wc_add_notice('Problemas no retorno de dados da API de entregas. Por favor, tente novamente! ' . $response->get_error_message(), 'error');
        } else {
            return json_decode( wp_remote_retrieve_body( $response ), true );
        }
    }

    public function get_label_data( $remetente, $objetosPostais, $cod_servico = '', $order_obj ) {
        $this->set_url('https://6c40ewverb.execute-api.sa-east-1.amazonaws.com/Prod/solicitar-etiquetas');

        $remetente['cpfCnpjRemetente'] = $this->cpf_cnpj;

        $request = array(
            'remetente' => $remetente,
            'objetosPostais' => $objetosPostais,
        );

        $response = wp_remote_post($this->url, array(
            'method'    => $this->method,
            'body'      => json_encode( $request ),
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

        if( 200 != $response['response']['code'] ) {
            $fsp_logger = wc_get_logger();
            $fsp_logger->error( print_r( $response, true ), array( 'source' => 'favor-shipping-plugin' ) );
        }

        $response_body_decoded = json_decode( $response['body'], true );
        
        if( empty( $response_body_decoded['etiquetas'] ) ) {
            $fsp_logger = wc_get_logger();
            $fsp_logger->error( 'In get_label_data, etiquetas array is empty!', array( 'source' => 'favor-shipping-plugin' ) ); 
            $fsp_logger->error( print_r( $response, true ), array( 'source' => 'favor-shipping-plugin' ) );                       
        }

        $labels_base_64 = $response_body_decoded['etiquetas']['base64String'];
        $bin = base64_decode($labels_base_64, true);
        if (strpos($bin, '%PDF') !== 0) {            
            $fsp_logger = wc_get_logger();
            $fsp_logger->error( 'Missing the PDF file signature', array( 'source' => 'favor-shipping-plugin' ) ); 
            $fsp_logger->error( print_r( $response, true ), array( 'source' => 'favor-shipping-plugin' ) );   
            if( is_object( $order_obj ) ) {
                $error_custom_message = "";
                if( ! empty( $response_body_decoded['message'] ) && ! empty( $response_body_decoded['error'] ) ) {
                    $error_custom_message = $response_body_decoded['message'] . " | " . $response_body_decoded['error'];
                }
                $order_obj->add_order_note( 'PDF não gerado, contatar o suporte! - ' . $error_custom_message );
            }
            return;                     
        }        

        $filename = 'favor_shipping_etiqueta_' . time() . '.pdf';

        file_put_contents($filename, $bin);
        
        header('Content-Description: File Transfer');
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filename));
            
        ob_clean();
        flush();
            
        readfile($filename);
                
        unlink($filename);
        exit;        
        //

    }
}
