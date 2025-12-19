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
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
        
        // Custom field types
        add_action( 'woocommerce_admin_field_favor_color', array( $this, 'output_color_field' ) );
        add_action( 'woocommerce_admin_field_favor_preview', array( $this, 'output_preview_field' ) );
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
            // Calculator Enable Section
            array(
                'title' => __( 'Calculadora de Frete', 'favor-despaches-woocommerce-plugin' ),
                'type'  => 'title',
                'desc'  => __( 'Configure a calculadora de frete na página do produto.', 'favor-despaches-woocommerce-plugin' ),
                'id'    => 'favor_calculator_settings',
            ),
            array(
                'title'    => __( 'Ativar Calculadora', 'favor-despaches-woocommerce-plugin' ),
                'desc'     => __( 'Habilitar calculadora de frete na página do produto', 'favor-despaches-woocommerce-plugin' ),
                'id'       => 'woocommerce_favor_calculator_enabled',
                'type'     => 'checkbox',
                'default'  => 'no',
                'desc_tip' => __( 'Ativar a calculadora de frete na página do produto.', 'favor-despaches-woocommerce-plugin' ),
            ),
            array(
                'type' => 'sectionend',
                'id'   => 'favor_calculator_settings',
            ),
            // Visual Customization Section
            array(
                'title' => __( 'Personalização Visual', 'favor-despaches-woocommerce-plugin' ),
                'type'  => 'title',
                'desc'  => __( 'Personalize as cores e aparência da calculadora de frete.', 'favor-despaches-woocommerce-plugin' ),
                'id'    => 'favor_calculator_visual_settings',
            ),
            array(
                'title'    => __( 'Presets de Tema', 'favor-despaches-woocommerce-plugin' ),
                'desc'     => __( 'Escolha um tema pré-configurado ou personalize manualmente', 'favor-despaches-woocommerce-plugin' ),
                'id'       => 'woocommerce_favor_calculator_theme',
                'type'     => 'select',
                'default'  => 'light',
                'options'  => array(
                    'light' => '🌞 Tema Claro',
                    'dark'  => '🌙 Tema Escuro',
                    'auto'  => '🎨 Auto-Detectar',
                ),
                'desc_tip' => true,
            ),
            array(
                'title'    => __( 'Cor Principal', 'favor-despaches-woocommerce-plugin' ),
                'desc'     => __( 'Cor principal dos botões, preços e elementos interativos', 'favor-despaches-woocommerce-plugin' ),
                'id'       => 'woocommerce_favor_calculator_primary_color',
                'type'     => 'favor_color',
                'default'  => '#0fae79',
                'desc_tip' => true,
            ),
            array(
                'title'    => __( 'Cor de Erro', 'favor-despaches-woocommerce-plugin' ),
                'desc'     => __( 'Cor para mensagens de erro e alertas', 'favor-despaches-woocommerce-plugin' ),
                'id'       => 'woocommerce_favor_calculator_error_color',
                'type'     => 'favor_color',
                'default'  => '#e74c3c',
                'desc_tip' => true,
            ),
            array(
                'title'    => __( 'Fundo da Calculadora', 'favor-despaches-woocommerce-plugin' ),
                'desc'     => __( 'Cor de fundo principal da calculadora', 'favor-despaches-woocommerce-plugin' ),
                'id'       => 'woocommerce_favor_calculator_bg_color',
                'type'     => 'favor_color',
                'default'  => '#ffffff',
                'desc_tip' => true,
            ),
            array(
                'title'    => __( 'Fundo dos Resultados', 'favor-despaches-woocommerce-plugin' ),
                'desc'     => __( 'Cor de fundo da área de resultados', 'favor-despaches-woocommerce-plugin' ),
                'id'       => 'woocommerce_favor_calculator_results_bg_color',
                'type'     => 'favor_color',
                'default'  => '#ffffff',
                'desc_tip' => true,
            ),
            array(
                'title'    => __( 'Cor do Texto Principal', 'favor-despaches-woocommerce-plugin' ),
                'desc'     => __( 'Cor do texto principal e títulos', 'favor-despaches-woocommerce-plugin' ),
                'id'       => 'woocommerce_favor_calculator_text_color',
                'type'     => 'favor_color',
                'default'  => '#1a1a1a',
                'desc_tip' => true,
            ),
            array(
                'title'    => __( 'Cor do Texto Secundário', 'favor-despaches-woocommerce-plugin' ),
                'desc'     => __( 'Cor do texto secundário e placeholders', 'favor-despaches-woocommerce-plugin' ),
                'id'       => 'woocommerce_favor_calculator_text_secondary_color',
                'type'     => 'favor_color',
                'default'  => '#777777',
                'desc_tip' => true,
            ),
            array(
                'title'    => __( 'Cor das Bordas', 'favor-despaches-woocommerce-plugin' ),
                'desc'     => __( 'Cor das bordas dos elementos', 'favor-despaches-woocommerce-plugin' ),
                'id'       => 'woocommerce_favor_calculator_border_color',
                'type'     => 'favor_color',
                'default'  => '#e0e0e0',
                'desc_tip' => true,
            ),
            array(
                'title'    => __( 'Tamanho da Fonte', 'favor-despaches-woocommerce-plugin' ),
                'desc'     => __( 'Tamanho base da fonte na calculadora', 'favor-despaches-woocommerce-plugin' ),
                'id'       => 'woocommerce_favor_calculator_font_size',
                'type'     => 'select',
                'default'  => 'medium',
                'options'  => array(
                    'small'  => __( 'Pequeno (12px)', 'favor-despaches-woocommerce-plugin' ),
                    'medium' => __( 'Médio (14px)', 'favor-despaches-woocommerce-plugin' ),
                    'large'  => __( 'Grande (16px)', 'favor-despaches-woocommerce-plugin' ),
                ),
                'desc_tip' => true,
            ),
            array(
                'title'    => __( 'Bordas Arredondadas', 'favor-despaches-woocommerce-plugin' ),
                'desc'     => __( 'Nível de arredondamento das bordas', 'favor-despaches-woocommerce-plugin' ),
                'id'       => 'woocommerce_favor_calculator_border_radius',
                'type'     => 'select',
                'default'  => 'medium',
                'options'  => array(
                    'none'   => __( 'Nenhum', 'favor-despaches-woocommerce-plugin' ),
                    'small'  => __( 'Pequeno', 'favor-despaches-woocommerce-plugin' ),
                    'medium' => __( 'Médio', 'favor-despaches-woocommerce-plugin' ),
                    'large'  => __( 'Grande', 'favor-despaches-woocommerce-plugin' ),
                ),
                'desc_tip' => true,
            ),
            array(
                'type' => 'sectionend',
                'id'   => 'favor_calculator_visual_settings',
            ),
            // Preview Section
            array(
                'title' => __( 'Pré-visualização', 'favor-despaches-woocommerce-plugin' ),
                'type'  => 'title',
                'desc'  => __( 'Veja como a calculadora ficará com suas personalizações', 'favor-despaches-woocommerce-plugin' ),
                'id'    => 'favor_calculator_preview_settings',
            ),
            array(
                'id'   => 'favor_calculator_preview',
                'type' => 'favor_preview',
            ),
            array(
                'type' => 'sectionend',
                'id'   => 'favor_calculator_preview_settings',
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

    /**
     * Check if calculator is enabled.
     *
     * @return bool
     */
    public static function is_calculator_enabled() {
        return 'yes' === get_option( 'woocommerce_favor_calculator_enabled', 'no' );
    }

    /**
     * Get calculator theme.
     *
     * @return string
     */
    public static function get_calculator_theme() {
        return get_option( 'woocommerce_favor_calculator_theme', 'light' );
    }

    /**
     * Get calculator primary color.
     *
     * @return string
     */
    public static function get_calculator_primary_color() {
        return get_option( 'woocommerce_favor_calculator_primary_color', '#0fae79' );
    }

    /**
     * Get calculator error color.
     *
     * @return string
     */
    public static function get_calculator_error_color() {
        return get_option( 'woocommerce_favor_calculator_error_color', '#e74c3c' );
    }

    /**
     * Get calculator background color.
     *
     * @return string
     */
    public static function get_calculator_bg_color() {
        return get_option( 'woocommerce_favor_calculator_bg_color', '#ffffff' );
    }

    /**
     * Get calculator results background color.
     *
     * @return string
     */
    public static function get_calculator_results_bg_color() {
        return get_option( 'woocommerce_favor_calculator_results_bg_color', '#ffffff' );
    }

    /**
     * Get calculator text color.
     *
     * @return string
     */
    public static function get_calculator_text_color() {
        return get_option( 'woocommerce_favor_calculator_text_color', '#1a1a1a' );
    }

    /**
     * Get calculator secondary text color.
     *
     * @return string
     */
    public static function get_calculator_text_secondary_color() {
        return get_option( 'woocommerce_favor_calculator_text_secondary_color', '#777777' );
    }

    /**
     * Get calculator border color.
     *
     * @return string
     */
    public static function get_calculator_border_color() {
        return get_option( 'woocommerce_favor_calculator_border_color', '#e0e0e0' );
    }

    /**
     * Get calculator font size.
     *
     * @return string
     */
    public static function get_calculator_font_size() {
        return get_option( 'woocommerce_favor_calculator_font_size', 'medium' );
    }

    /**
     * Get calculator border radius.
     *
     * @return string
     */
    public static function get_calculator_border_radius() {
        return get_option( 'woocommerce_favor_calculator_border_radius', 'medium' );
    }

    /**
     * Enqueue admin scripts and styles.
     */
    public function enqueue_admin_scripts( $hook ) {
        // Only load on WooCommerce settings pages
        if ( 'woocommerce_page_wc-settings' !== $hook ) {
            return;
        }

        $plugin_url = plugin_dir_url( dirname( dirname( dirname( __FILE__ ) ) ) . '/favor-shipping-plugin.php' );

        // Enqueue WordPress color picker
        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_script( 'wp-color-picker' );

        // Enqueue admin styles
        wp_enqueue_style(
            'favor-calculator-admin',
            $plugin_url . 'assets/css/favor-calculator-admin.css',
            array(),
            Favor_Shipping_Plugin::VERSION
        );

        // Enqueue admin scripts
        wp_enqueue_script(
            'favor-calculator-admin',
            $plugin_url . 'assets/js/favor-calculator-admin.js',
            array( 'jquery', 'wp-color-picker' ),
            Favor_Shipping_Plugin::VERSION,
            true
        );
    }

    /**
     * Output color picker field.
     *
     * @param array $value Field value.
     */
    public function output_color_field( $value ) {
        $option_value = get_option( $value['id'], $value['default'] );
        ?>
        <tr class="favor-color-field">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
                <?php if ( ! empty( $value['desc_tip'] ) ) : ?>
                    <span class="woocommerce-help-tip" data-tip="<?php echo esc_attr( $value['desc'] ); ?>"></span>
                <?php endif; ?>
            </th>
            <td class="forminp">
                <div class="favor-color-picker-wrapper">
                    <input
                        type="text"
                        id="<?php echo esc_attr( $value['id'] ); ?>"
                        name="<?php echo esc_attr( $value['id'] ); ?>"
                        value="<?php echo esc_attr( $option_value ); ?>"
                        class="favor-color-picker"
                        data-default-color="<?php echo esc_attr( $value['default'] ); ?>"
                    />
                    <?php if ( ! empty( $value['desc'] ) && empty( $value['desc_tip'] ) ) : ?>
                        <p class="description"><?php echo wp_kses_post( $value['desc'] ); ?></p>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
        <?php
    }

    /**
     * Output preview field.
     *
     * @param array $value Field value.
     */
    public function output_preview_field( $value ) {
        ?>
        <tr class="favor-preview-field">
            <td colspan="2">
                <div class="favor-calculator-preview-container">
                    <div id="favor-calculator-preview">
                        <div class="favor-preview-calculator-container">
                            <div class="favor-preview-title">Pré-visualização da Calculadora</div>
                            <div class="favor-preview-input-group">
                                <input
                                    type="text"
                                    class="favor-preview-cep-input"
                                    value="22775-360"
                                    readonly
                                />
                            </div>
                            <div class="favor-preview-results">
                                <div class="favor-preview-results-title">Opções de Entrega</div>
                                <div class="favor-preview-results-list">
                                    <div class="favor-preview-result-item">
                                        <div class="favor-preview-result-name">PAC - (5 dias úteis)</div>
                                        <div class="favor-preview-result-price">R$ 20,78</div>
                                    </div>
                                    <div class="favor-preview-result-item">
                                        <div class="favor-preview-result-name">SEDEX - (1 dia útil)</div>
                                        <div class="favor-preview-result-price">R$ 13,20</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="favor-preview-description">
                            <p>Esta é uma pré-visualização. As alterações são aplicadas automaticamente conforme você modifica as configurações acima.</p>
                            <p>Veja como a calculadora ficará com suas personalizações</p>
                        </div>
                    </div>
                </div>
            </td>
        </tr>
        <?php
    }
}

new WC_Favor_Shipping_Settings();
