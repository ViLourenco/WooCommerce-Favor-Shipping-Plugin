<?php
/**
 * Favor Shipping Logs Admin Page
 *
 * Creates the Logs Favor Despaches page under WooCommerce menu.
 *
 * @package Favor_Shipping
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * WC_Favor_Shipping_Logs_Page class.
 */
class WC_Favor_Shipping_Logs_Page {

    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
        add_action( 'admin_init', array( $this, 'handle_clear_logs' ) );
    }

    /**
     * Add the logs page and settings redirect to WooCommerce menu.
     */
    public function add_menu_page() {
        add_submenu_page(
            'woocommerce',
            __( 'Logs Favor Despaches', 'favor-despaches-woocommerce-plugin' ),
            __( 'Logs Favor Despaches', 'favor-despaches-woocommerce-plugin' ),
            'manage_woocommerce',
            'favor-despaches-logs',
            array( $this, 'render_page' )
        );

        // Add settings redirect menu item
        add_submenu_page(
            'woocommerce',
            __( 'Configurações Favor Despaches', 'favor-despaches-woocommerce-plugin' ),
            __( 'Configurações Favor Despaches', 'favor-despaches-woocommerce-plugin' ),
            'manage_woocommerce',
            'favor-despaches-settings',
            array( $this, 'redirect_to_settings' )
        );
    }

    /**
     * Redirect to shipping settings page.
     */
    public function redirect_to_settings() {
        wp_redirect( admin_url( 'admin.php?page=wc-settings&tab=shipping&section=favor_despaches' ) );
        exit;
    }

    /**
     * Handle the clear logs action.
     */
    public function handle_clear_logs() {
        if ( isset( $_POST['favor_clear_logs'] ) && check_admin_referer( 'favor_clear_logs_nonce' ) ) {
            WC_Favor_Shipping_Logger::clear_logs();
            wp_redirect( admin_url( 'admin.php?page=favor-despaches-logs&logs_cleared=1' ) );
            exit;
        }
    }

    /**
     * Get the list of missing setup items.
     *
     * @return array List of missing items with labels and links.
     */
    private function get_missing_setup_items() {
        $missing = array();
        $settings_url = admin_url( 'admin.php?page=wc-settings&tab=shipping&section=favor_despaches' );

        // Check store address fields
        $store_address = get_option( 'woocommerce_store_address', '' );
        if ( empty( $store_address ) ) {
            $missing[] = array(
                'field' => __( 'Endereço linha 1 (Logradouro)', 'favor-despaches-woocommerce-plugin' ),
                'link'  => admin_url( 'admin.php?page=wc-settings' ),
            );
        }

        $store_number = get_option( 'favor_store_address_number', '' );
        if ( empty( $store_number ) ) {
            $missing[] = array(
                'field' => __( 'Número', 'favor-despaches-woocommerce-plugin' ),
                'link'  => admin_url( 'admin.php?page=wc-settings' ),
            );
        }

        $store_city = get_option( 'woocommerce_store_city', '' );
        if ( empty( $store_city ) ) {
            $missing[] = array(
                'field' => __( 'Cidade', 'favor-despaches-woocommerce-plugin' ),
                'link'  => admin_url( 'admin.php?page=wc-settings' ),
            );
        }

        $store_neighborhood = get_option( 'favor_store_neighborhood', '' );
        if ( empty( $store_neighborhood ) ) {
            $missing[] = array(
                'field' => __( 'Bairro', 'favor-despaches-woocommerce-plugin' ),
                'link'  => admin_url( 'admin.php?page=wc-settings' ),
            );
        }

        $default_country = get_option( 'woocommerce_default_country', '' );
        if ( empty( $default_country ) ) {
            $missing[] = array(
                'field' => __( 'País / estado', 'favor-despaches-woocommerce-plugin' ),
                'link'  => admin_url( 'admin.php?page=wc-settings' ),
            );
        }

        $store_postcode = get_option( 'woocommerce_store_postcode', '' );
        if ( empty( $store_postcode ) ) {
            $missing[] = array(
                'field' => __( 'CEP', 'favor-despaches-woocommerce-plugin' ),
                'link'  => admin_url( 'admin.php?page=wc-settings' ),
            );
        }

        // Check Favor Despaches settings (new location)
        $api_key = WC_Favor_Shipping_Settings::get_api_key();
        if ( empty( $api_key ) ) {
            $missing[] = array(
                'field' => __( 'Chave de API Favor', 'favor-despaches-woocommerce-plugin' ),
                'link'  => $settings_url,
            );
        }

        $cpf_cnpj = WC_Favor_Shipping_Settings::get_cpf_cnpj();
        if ( empty( $cpf_cnpj ) ) {
            $missing[] = array(
                'field' => __( 'CPF/CNPJ', 'favor-despaches-woocommerce-plugin' ),
                'link'  => $settings_url,
            );
        }

        $contact_phone = WC_Favor_Shipping_Settings::get_contact_phone();
        if ( empty( $contact_phone ) ) {
            $missing[] = array(
                'field' => __( 'Telefone de Contato', 'favor-despaches-woocommerce-plugin' ),
                'link'  => $settings_url,
            );
        }

        return $missing;
    }

    /**
     * Render the logs page.
     */
    public function render_page() {
        $missing_items = $this->get_missing_setup_items();
        $logs = WC_Favor_Shipping_Logger::get_logs();
        $logs_cleared = isset( $_GET['logs_cleared'] ) && $_GET['logs_cleared'] == '1';
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__( 'Logs da Favor Despaches', 'favor-despaches-woocommerce-plugin' ); ?></h1>

            <?php if ( $logs_cleared ) : ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php echo esc_html__( 'Logs limpos com sucesso!', 'favor-despaches-woocommerce-plugin' ); ?></p>
                </div>
            <?php endif; ?>

            <?php if ( ! empty( $missing_items ) ) : ?>
                <div class="notice notice-warning" style="border-left-color: #0073aa; padding: 12px;">
                    <h3 style="margin-top: 0; margin-bottom: 10px;">
                        <?php echo esc_html__( 'Favor Despaches', 'favor-despaches-woocommerce-plugin' ); ?>
                    </h3>
                    <p>
                        <?php echo esc_html__( 'Para utilizar o plugin você deve completar a configuração. Os seguintes campos estão faltando:', 'favor-despaches-woocommerce-plugin' ); ?>
                    </p>
                    <ul style="list-style-type: disc; margin-left: 20px;">
                        <?php foreach ( $missing_items as $item ) : ?>
                            <li>
                                <a href="<?php echo esc_url( $item['link'] ); ?>">
                                    <?php echo esc_html( $item['field'] ); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php else : ?>
                <div class="notice notice-success" style="padding: 12px;">
                    <h3 style="margin-top: 0; margin-bottom: 10px;">
                        <?php echo esc_html__( 'Favor Despaches', 'favor-despaches-woocommerce-plugin' ); ?>
                    </h3>
                    <p>
                        <?php echo esc_html__( 'Configuração completa! O plugin está pronto para uso.', 'favor-despaches-woocommerce-plugin' ); ?>
                    </p>
                </div>
            <?php endif; ?>

            <div style="margin-top: 20px;">
                <textarea 
                    readonly 
                    style="width: 100%; height: 400px; font-family: monospace; font-size: 12px; background-color: #f9f9f9; padding: 10px;"
                ><?php echo esc_textarea( $logs ); ?></textarea>
            </div>

            <form method="post" style="margin-top: 10px;">
                <?php wp_nonce_field( 'favor_clear_logs_nonce' ); ?>
                <button type="submit" name="favor_clear_logs" class="button">
                    <?php echo esc_html__( 'Limpar Logs', 'favor-despaches-woocommerce-plugin' ); ?>
                </button>
            </form>
        </div>
        <?php
    }
}

new WC_Favor_Shipping_Logs_Page();
