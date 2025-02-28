<?php
/**
 * Classe para gerenciar a parte pública do plugin
 *
 * @package Frete_Fixo_Para_Produto_Individual
 * @subpackage Public
 */

if (!defined('WPINC')) {
    die;
}

class FFPI_Public {

    /**
     * Inicializa a classe e define os hooks
     */
    public function __construct() {
        // Adicionar scripts e estilos no frontend
        add_action('wp_enqueue_scripts', array($this, 'enqueue_public_scripts'));
        
        // Mostrar informações de frete na página do produto
        add_action('woocommerce_single_product_summary', array($this, 'display_product_shipping_info'), 25);
        
        // Mostrar informações de frete na lista de produtos
        // Prioridade 5 para exibir antes do título
        add_action('woocommerce_shop_loop_item_title', array($this, 'display_product_shipping_info_loop'), 5);
        
        // Adicionar informações de frete ao título do produto no carrinho
        add_filter('woocommerce_cart_item_name', array($this, 'add_shipping_info_to_cart_item'), 10, 3);
        
        // Adicionar classe ao body para identificar o tema
        add_filter('body_class', array($this, 'add_theme_body_class'));
    }

    /**
     * Registra e carrega os scripts e estilos no frontend
     */
    public function enqueue_public_scripts() {
        if (is_product() || is_shop() || is_product_category() || is_product_tag() || is_cart() || is_checkout()) {
            wp_enqueue_style('ffpi-public-css', FFPI_PLUGIN_URL . 'assets/css/public.css', array(), FFPI_VERSION);
            wp_enqueue_script('ffpi-public-js', FFPI_PLUGIN_URL . 'assets/js/public.js', array('jquery'), FFPI_VERSION, true);
            
            // Passar variáveis para o script
            wp_localize_script('ffpi-public-js', 'ffpi_public_vars', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'currency_symbol' => get_woocommerce_currency_symbol(),
                'free_shipping_text' => __('Frete grátis para este produto!', 'frete-fixo-para-produto-individual'),
                'fixed_shipping_text' => __('Frete fixo:', 'frete-fixo-para-produto-individual'),
                'per_unit_text' => __('por unidade', 'frete-fixo-para-produto-individual'),
                'single_value_text' => __('valor único', 'frete-fixo-para-produto-individual')
            ));
        }
    }

    /**
     * Exibe informações de frete na página do produto
     */
    public function display_product_shipping_info() {
        global $product;
        
        // Obter informações de frete do produto
        $shipping_info = $this->get_product_shipping_info($product);
        
        if (!$shipping_info) {
            return;
        }
        
        echo '<div class="ffpi-product-shipping-info">';
        
        if ($shipping_info['enable_free_shipping'] === 'yes') {
            // Exibir informação de frete grátis
            echo '<p class="ffpi-free-shipping"><span class="dashicons dashicons-yes"></span> ' . 
                __('Frete grátis para este produto!', 'frete-fixo-para-produto-individual') . '</p>';
        } elseif ($shipping_info['enable_fixed_shipping'] === 'yes' && $shipping_info['fixed_shipping_cost'] > 0) {
            // Exibir informação de frete fixo
            $formatted_price = wc_price($shipping_info['fixed_shipping_cost']);
            echo '<p class="ffpi-fixed-shipping">' . 
                __('Frete fixo:', 'frete-fixo-para-produto-individual') . ' <span class="amount">' . 
                $formatted_price . '</span>';
            
            // Adicionar informação se o frete é por unidade ou valor único
            if ($shipping_info['per_unit_shipping'] === 'yes') {
                echo ' <span class="ffpi-per-unit">(' . __('por unidade', 'frete-fixo-para-produto-individual') . ')</span>';
            } else {
                echo ' <span class="ffpi-per-unit">(' . __('valor único', 'frete-fixo-para-produto-individual') . ')</span>';
            }
            
            echo '</p>';
        }
        
        // Adicionar div para informações de frete da variação
        if ($product->is_type('variable')) {
            echo '<div id="ffpi-variation-shipping-info"></div>';
            
            // Passar dados das variações para o JavaScript
            $this->pass_variations_data_to_js($product);
        }
        
        echo '</div>';
    }

    /**
     * Exibe informações de frete na lista de produtos
     */
    public function display_product_shipping_info_loop() {
        global $product;
        
        // Obter informações de frete do produto
        $shipping_info = $this->get_product_shipping_info($product);
        
        if (!$shipping_info) {
            return;
        }
        
        echo '<div class="ffpi-product-shipping-info-loop">';
        
        if ($shipping_info['enable_free_shipping'] === 'yes') {
            // Exibir informação de frete grátis
            echo '<p class="ffpi-free-shipping-loop"><span class="dashicons dashicons-yes"></span> ' . 
                __('Frete grátis para este produto!', 'frete-fixo-para-produto-individual') . '</p>';
        } elseif ($shipping_info['enable_fixed_shipping'] === 'yes' && $shipping_info['fixed_shipping_cost'] > 0) {
            // Exibir informação de frete fixo
            $formatted_price = wc_price($shipping_info['fixed_shipping_cost']);
            echo '<p class="ffpi-fixed-shipping-loop">' . 
                __('Frete fixo:', 'frete-fixo-para-produto-individual') . ' <span class="amount">' . 
                $formatted_price . '</span>';
            
            // Adicionar informação se o frete é por unidade ou valor único
            if ($shipping_info['per_unit_shipping'] === 'yes') {
                echo ' <small>(' . __('por unidade', 'frete-fixo-para-produto-individual') . ')</small>';
            } else {
                echo ' <small>(' . __('valor único', 'frete-fixo-para-produto-individual') . ')</small>';
            }
            
            echo '</p>';
        }
        
        echo '</div>';
    }

    /**
     * Adiciona informações de frete ao título do produto no carrinho
     */
    public function add_shipping_info_to_cart_item($product_name, $cart_item, $cart_item_key) {
        if (is_cart() || is_checkout()) {
            $product_id = $cart_item['product_id'];
            $variation_id = $cart_item['variation_id'];
            
            // Verificar se é uma variação
            if ($variation_id) {
                $enable_fixed_shipping = get_post_meta($variation_id, '_ffpi_enable_fixed_shipping', true);
                $enable_free_shipping = get_post_meta($variation_id, '_ffpi_enable_free_shipping', true);
                $fixed_shipping_cost = get_post_meta($variation_id, '_ffpi_fixed_shipping_cost', true);
                $per_unit_shipping = get_post_meta($variation_id, '_ffpi_per_unit_shipping', true);
            } else {
                $enable_fixed_shipping = get_post_meta($product_id, '_ffpi_enable_fixed_shipping', true);
                $enable_free_shipping = get_post_meta($product_id, '_ffpi_enable_free_shipping', true);
                $fixed_shipping_cost = get_post_meta($product_id, '_ffpi_fixed_shipping_cost', true);
                $per_unit_shipping = get_post_meta($product_id, '_ffpi_per_unit_shipping', true);
            }

            if ($enable_free_shipping === 'yes') {
                $product_name .= '<div class="ffpi-cart-shipping-info"><span class="ffpi-free-shipping-cart">' . __('Frete grátis!', 'frete-fixo-para-produto-individual') . '</span></div>';
            } elseif ($enable_fixed_shipping === 'yes' && $fixed_shipping_cost > 0) {
                $product_name .= '<div class="ffpi-cart-shipping-info"><span class="ffpi-fixed-shipping-cart">' . __('Frete: ', 'frete-fixo-para-produto-individual') . wc_price($fixed_shipping_cost);
                
                // Mostrar se o frete é por unidade ou valor único
                if ($per_unit_shipping === 'yes') {
                    $product_name .= ' <small>(' . __('por unidade', 'frete-fixo-para-produto-individual') . ')</small>';
                } else {
                    $product_name .= ' <small>(' . __('valor único', 'frete-fixo-para-produto-individual') . ')</small>';
                }
                
                $product_name .= '</span></div>';
            }
        }
        
        return $product_name;
    }
    
    /**
     * Obtem informações de frete do produto
     */
    public function get_product_shipping_info($product) {
        $product_id = $product->get_id();
        $enable_fixed_shipping = get_post_meta($product_id, '_ffpi_enable_fixed_shipping', true);
        $enable_free_shipping = get_post_meta($product_id, '_ffpi_enable_free_shipping', true);
        $fixed_shipping_cost = get_post_meta($product_id, '_ffpi_fixed_shipping_cost', true);
        $per_unit_shipping = get_post_meta($product_id, '_ffpi_per_unit_shipping', true);
        
        return array(
            'enable_fixed_shipping' => $enable_fixed_shipping,
            'enable_free_shipping' => $enable_free_shipping,
            'fixed_shipping_cost' => $fixed_shipping_cost,
            'per_unit_shipping' => $per_unit_shipping
        );
    }
    
    /**
     * Passa dados das variações para o JavaScript
     */
    public function pass_variations_data_to_js($product) {
        $variations_data = array();
        $variations = $product->get_available_variations();
        
        foreach ($variations as $variation) {
            $variation_id = $variation['variation_id'];
            $variation_enable_fixed_shipping = get_post_meta($variation_id, '_ffpi_enable_fixed_shipping', true);
            $variation_enable_free_shipping = get_post_meta($variation_id, '_ffpi_enable_free_shipping', true);
            $variation_fixed_shipping_cost = get_post_meta($variation_id, '_ffpi_fixed_shipping_cost', true);
            $variation_per_unit_shipping = get_post_meta($variation_id, '_ffpi_per_unit_shipping', true);
            
            $variations_data[$variation_id] = array(
                'enable_fixed_shipping' => $variation_enable_fixed_shipping,
                'enable_free_shipping' => $variation_enable_free_shipping,
                'fixed_shipping_cost' => $variation_fixed_shipping_cost,
                'per_unit_shipping' => $variation_per_unit_shipping,
                'formatted_price' => wc_price($variation_fixed_shipping_cost)
            );
        }
        
        // Adicionar dados das variações ao script
        wp_localize_script('ffpi-public-js', 'ffpi_variations', $variations_data);
    }
    
    /**
     * Adiciona classe ao body para identificar o tema
     */
    public function add_theme_body_class($classes) {
        $theme = wp_get_theme();
        $theme_name = strtolower($theme->get('Template'));
        
        if (empty($theme_name)) {
            $theme_name = strtolower($theme->get('Name'));
        }
        
        $classes[] = 'theme-' . sanitize_html_class($theme_name);
        
        return $classes;
    }
}
