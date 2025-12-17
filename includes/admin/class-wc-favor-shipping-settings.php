<?php
/**
 * Favor Shipping Settings Class
 *
 * Adds Favor Despaches configuration section to WooCommerce Shipping settings.
 *
 * @package Favor_Shipping
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * WC_Favor_Shipping_Settings class.
 */
class WC_Favor_Shipping_Settings {

    /**
     * Constructor.
     */
    public function __construct() {
        add_filter( 'woocommerce_get_settings_shipping', array( $this, 'get_settings' ), 99, 2 );
    }

    /**
     * Inject Favor Despaches settings into the main shipping options page.
     *
     * @param array  $settings        Existing settings.
     * @param string $current_section Current section ID.
     * @return array Modified settings.
     */
    public function get_settings( $settings, $current_section ) {
        // Only inject into the main shipping options page (empty section or 'options')
        if ( '' !== $current_section && 'options' !== $current_section ) {
            return $settings;
        }

        // Find the index of the debug mode setting
        $debug_mode_index = -1;
        foreach ( $settings as $index => $setting ) {
            if ( isset( $setting['id'] ) && 'woocommerce_shipping_debug_mode' === $setting['id'] ) {
                $debug_mode_index = $index;
                break;
            }
        }

        // Prepare Favor Despaches settings
        $favor_settings = array(
            array(
                'title' => __( 'Configurações Favor Despaches', 'favor-despaches-woocommerce-plugin' ),
                'type'  => 'title',
                'desc'  => __( 'Configure as credenciais de acesso à API da Favor Despaches.', 'favor-despaches-woocommerce-plugin' ),
                'id'    => 'favor_despaches_settings',
            ),
            array(
                'title'       => __( 'Chave de API Favor', 'favor-despaches-woocommerce-plugin' ),
                'desc'        => __( 'Digite sua chave de API referente a sua conta na Favor.', 'favor-despaches-woocommerce-plugin' ),
                'id'          => 'woocommerce_favor_api_key',
                'type'        => 'text',
                'css'         => 'min-width:300px;',
                'default'     => '',
                'desc_tip'    => true,
            ),
            array(
                'title'       => __( 'CPF/CNPJ', 'favor-despaches-woocommerce-plugin' ),
                'desc'        => __( 'Digite seu CNPJ ou CPF que é utilizado na sua loja.', 'favor-despaches-woocommerce-plugin' ),
                'id'          => 'woocommerce_favor_cpf_cnpj',
                'type'        => 'text',
                'css'         => 'min-width:300px;',
                'default'     => '',
                'desc_tip'    => true,
            ),
            array(
                'title'       => __( 'Telefone de Contato', 'favor-despaches-woocommerce-plugin' ),
                'desc'        => __( 'Digite o telefone de contato para entregas (apenas dígitos).', 'favor-despaches-woocommerce-plugin' ),
                'id'          => 'woocommerce_favor_contact_phone',
                'type'        => 'text',
                'css'         => 'min-width:300px;',
                'default'     => '',
                'desc_tip'    => true,
            ),
            array(
                'type' => 'sectionend',
                'id'   => 'favor_despaches_settings',
            ),
        );

        // If debug mode setting found, insert after its section; otherwise append to end
        if ( $debug_mode_index >= 0 ) {
            // Find the sectionend after debug mode, then insert AFTER it
            $insert_index = $debug_mode_index + 1;
            while ( $insert_index < count( $settings ) ) {
                if ( isset( $settings[ $insert_index ]['type'] ) && 'sectionend' === $settings[ $insert_index ]['type'] ) {
                    $insert_index++; // Move past the sectionend to insert after it
                    break;
                }
                $insert_index++;
            }
            array_splice( $settings, $insert_index, 0, $favor_settings );
        } else {
            // If debug mode not found, append to the end
            $settings = array_merge( $settings, $favor_settings );
        }

        return $settings;
    }

    /**
     * Get API key.
     *
     * @return string
     */
    public static function get_api_key() {
        // Check new location first, then fall back to old integration settings
        $api_key = get_option( 'woocommerce_favor_api_key', '' );
        if ( empty( $api_key ) ) {
            $old_settings = get_option( 'woocommerce_favor_plugin_shipping_settings', array() );
            $api_key = isset( $old_settings['api_key'] ) ? $old_settings['api_key'] : '';
        }
        return $api_key;
    }

    /**
     * Get CPF/CNPJ.
     *
     * @return string
     */
    public static function get_cpf_cnpj() {
        $cpf_cnpj = get_option( 'woocommerce_favor_cpf_cnpj', '' );
        if ( empty( $cpf_cnpj ) ) {
            $old_settings = get_option( 'woocommerce_favor_plugin_shipping_settings', array() );
            $cpf_cnpj = isset( $old_settings['cpf_cnpj'] ) ? $old_settings['cpf_cnpj'] : '';
        }
        return $cpf_cnpj;
    }

    /**
     * Get contact phone.
     *
     * @return string
     */
    public static function get_contact_phone() {
        $phone = get_option( 'woocommerce_favor_contact_phone', '' );
        if ( empty( $phone ) ) {
            $old_settings = get_option( 'woocommerce_favor_plugin_shipping_settings', array() );
            $phone = isset( $old_settings['contact_phone'] ) ? $old_settings['contact_phone'] : '';
        }
        return $phone;
    }
}

new WC_Favor_Shipping_Settings();
