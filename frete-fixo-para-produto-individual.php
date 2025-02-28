<?php
/**
 * Plugin Name: Frete fixo para produto individual
 * Plugin URI: https://github.com/Andydev0/frete-fixo-para-produto-individual
 * Description: Plugin para adicionar frete fixo por produto e suas variações no WooCommerce.
 * Version: 1.0.0
 * Author: Anderson Silva
 * Author URI: https://andersondev.com.br
 * Text Domain: frete-fixo-para-produto-individual
 * Domain Path: /languages
 * WC requires at least: 3.0.0
 * WC tested up to: 8.0.0
 */

// Se este arquivo for chamado diretamente, aborte.
if (!defined('WPINC')) {
    die;
}

// Definir constantes
define('FFPI_VERSION', '1.0.0');
define('FFPI_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('FFPI_PLUGIN_URL', plugin_dir_url(__FILE__));
define('FFPI_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Verificar se o WooCommerce está ativo
 */
function ffpi_check_woocommerce_active() {
    if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
        add_action('admin_notices', 'ffpi_woocommerce_missing_notice');
        return false;
    }
    return true;
}

/**
 * Exibir aviso se o WooCommerce não estiver ativo
 */
function ffpi_woocommerce_missing_notice() {
    ?>
    <div class="error">
        <p><?php _e('Frete fixo para produto individual requer o plugin WooCommerce ativo para funcionar!', 'frete-fixo-para-produto-individual'); ?></p>
    </div>
    <?php
}

/**
 * Inicializar o plugin
 */
function ffpi_init() {
    if (!ffpi_check_woocommerce_active()) {
        return;
    }

    // Incluir arquivos necessários
    require_once FFPI_PLUGIN_DIR . 'includes/class-ffpi-product-shipping.php';
    require_once FFPI_PLUGIN_DIR . 'admin/class-ffpi-admin.php';
    require_once FFPI_PLUGIN_DIR . 'public/class-ffpi-public.php';

    // Inicializar as classes
    $product_shipping = new FFPI_Product_Shipping();
    $admin = new FFPI_Admin();
    $public = new FFPI_Public();

    // Registrar hooks de ativação e desativação
    register_activation_hook(__FILE__, 'ffpi_activate');
    register_deactivation_hook(__FILE__, 'ffpi_deactivate');
}
add_action('plugins_loaded', 'ffpi_init');

/**
 * Função executada na ativação do plugin
 */
function ffpi_activate() {
    // Código a ser executado na ativação do plugin
    flush_rewrite_rules();
    
    // Inicializar as opções padrão se não existirem
    $options = get_option('ffpi_settings', array());
    
    if (!isset($options['default_per_unit'])) {
        $options['default_per_unit'] = 'yes';
    }
    
    if (!isset($options['show_in_product_list'])) {
        $options['show_in_product_list'] = 'yes';
    }
    
    if (!isset($options['shipping_label'])) {
        $options['shipping_label'] = __('Frete Fixo', 'frete-fixo-para-produto-individual');
    }
    
    if (!isset($options['free_shipping_label'])) {
        $options['free_shipping_label'] = __('Frete Grátis', 'frete-fixo-para-produto-individual');
    }
    
    update_option('ffpi_settings', $options);
}

/**
 * Função executada na desativação do plugin
 */
function ffpi_deactivate() {
    // Código a ser executado na desativação do plugin
    flush_rewrite_rules();
}

/**
 * Carregar arquivos de tradução
 */
function ffpi_load_textdomain() {
    load_plugin_textdomain('frete-fixo-para-produto-individual', false, dirname(plugin_basename(__FILE__)) . '/languages/');
}
add_action('plugins_loaded', 'ffpi_load_textdomain');
