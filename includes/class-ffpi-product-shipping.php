<?php
/**
 * Classe principal para gerenciar o frete fixo por produto
 *
 * @package Frete_Fixo_Para_Produto_Individual
 * @subpackage Includes
 */

if (!defined('WPINC')) {
    die;
}

class FFPI_Product_Shipping {

    /**
     * Inicializa a classe e define os hooks
     */
    public function __construct() {
        // Adicionar campos de frete fixo ao produto
        add_action('woocommerce_product_options_shipping', array($this, 'add_product_shipping_fields'));
        add_action('woocommerce_process_product_meta', array($this, 'save_product_shipping_fields'));

        // Adicionar campos de frete fixo às variações do produto
        add_action('woocommerce_product_after_variable_attributes', array($this, 'add_variation_shipping_fields'), 10, 3);
        add_action('woocommerce_save_product_variation', array($this, 'save_variation_shipping_fields'), 10, 2);

        // Modificar o cálculo de frete
        add_filter('woocommerce_package_rates', array($this, 'apply_product_fixed_shipping'), 100, 2);
        
        // Verificar se deve mostrar a coluna de frete na tabela de produtos
        $options = get_option('ffpi_settings');
        $show_in_product_list = isset($options['show_in_product_list']) ? $options['show_in_product_list'] : 'yes';
        
        if ($show_in_product_list === 'yes') {
            // Adicionar coluna de frete na listagem de produtos
            add_filter('manage_edit-product_columns', array($this, 'add_product_shipping_column'));
            add_action('manage_product_posts_custom_column', array($this, 'display_product_shipping_column'), 10, 2);
        }
    }

    /**
     * Adiciona campos de frete fixo na aba de envio do produto
     */
    public function add_product_shipping_fields() {
        global $post;

        // Obter configurações do plugin
        $options = get_option('ffpi_settings');
        $default_per_unit = isset($options['default_per_unit']) && $options['default_per_unit'] === 'yes' ? 'yes' : 'no';

        echo '<div class="options_group">';
        
        // Campo para habilitar frete fixo
        woocommerce_wp_checkbox(array(
            'id' => '_ffpi_enable_fixed_shipping',
            'label' => __('Habilitar frete fixo', 'frete-fixo-para-produto-individual'),
            'description' => __('Marque esta opção para habilitar o frete fixo para este produto.', 'frete-fixo-para-produto-individual')
        ));

        // Campo para valor do frete fixo
        woocommerce_wp_text_input(array(
            'id' => '_ffpi_fixed_shipping_cost',
            'label' => __('Valor do frete fixo (R$)', 'frete-fixo-para-produto-individual'),
            'description' => __('Insira o valor do frete fixo para este produto.', 'frete-fixo-para-produto-individual'),
            'type' => 'number',
            'custom_attributes' => array(
                'step' => '0.01',
                'min' => '0'
            )
        ));

        // Campo para definir se o frete é por unidade
        woocommerce_wp_checkbox(array(
            'id' => '_ffpi_per_unit_shipping',
            'label' => __('Frete por unidade', 'frete-fixo-para-produto-individual'),
            'description' => __('Marque esta opção para cobrar o frete fixo por unidade do produto. Se desmarcado, o frete será um valor único independente da quantidade.', 'frete-fixo-para-produto-individual'),
            'value' => get_post_meta($post->ID, '_ffpi_per_unit_shipping', true) ?: $default_per_unit
        ));

        // Campo para habilitar frete grátis
        woocommerce_wp_checkbox(array(
            'id' => '_ffpi_enable_free_shipping',
            'label' => __('Frete grátis', 'frete-fixo-para-produto-individual'),
            'description' => __('Marque esta opção para oferecer frete grátis para este produto.', 'frete-fixo-para-produto-individual')
        ));

        echo '</div>';
    }

    /**
     * Salva os campos de frete fixo do produto
     */
    public function save_product_shipping_fields($post_id) {
        // Salvar campo de habilitar frete fixo
        $enable_fixed_shipping = isset($_POST['_ffpi_enable_fixed_shipping']) ? 'yes' : 'no';
        update_post_meta($post_id, '_ffpi_enable_fixed_shipping', $enable_fixed_shipping);

        // Salvar campo de valor do frete fixo
        if (isset($_POST['_ffpi_fixed_shipping_cost'])) {
            update_post_meta($post_id, '_ffpi_fixed_shipping_cost', wc_format_decimal($_POST['_ffpi_fixed_shipping_cost']));
        }

        // Salvar campo de frete por unidade
        $per_unit_shipping = isset($_POST['_ffpi_per_unit_shipping']) ? 'yes' : 'no';
        update_post_meta($post_id, '_ffpi_per_unit_shipping', $per_unit_shipping);

        // Salvar campo de frete grátis
        $enable_free_shipping = isset($_POST['_ffpi_enable_free_shipping']) ? 'yes' : 'no';
        update_post_meta($post_id, '_ffpi_enable_free_shipping', $enable_free_shipping);
    }

    /**
     * Adiciona campos de frete fixo às variações do produto
     */
    public function add_variation_shipping_fields($loop, $variation_data, $variation) {
        // Obter configurações do plugin
        $options = get_option('ffpi_settings');
        $default_per_unit = isset($options['default_per_unit']) && $options['default_per_unit'] === 'yes' ? 'yes' : 'no';
        
        echo '<div class="variation-shipping-options">';
        
        // Campo para habilitar frete fixo na variação
        woocommerce_wp_checkbox(array(
            'id' => '_ffpi_enable_fixed_shipping_' . $variation->ID,
            'name' => '_ffpi_enable_fixed_shipping[' . $variation->ID . ']',
            'label' => __('Habilitar frete fixo', 'frete-fixo-para-produto-individual'),
            'value' => get_post_meta($variation->ID, '_ffpi_enable_fixed_shipping', true),
            'wrapper_class' => 'form-row form-row-first',
        ));

        // Campo para valor do frete fixo na variação
        woocommerce_wp_text_input(array(
            'id' => '_ffpi_fixed_shipping_cost_' . $variation->ID,
            'name' => '_ffpi_fixed_shipping_cost[' . $variation->ID . ']',
            'label' => __('Valor do frete fixo (R$)', 'frete-fixo-para-produto-individual'),
            'value' => get_post_meta($variation->ID, '_ffpi_fixed_shipping_cost', true),
            'type' => 'number',
            'custom_attributes' => array(
                'step' => '0.01',
                'min' => '0'
            ),
            'wrapper_class' => 'form-row form-row-last',
        ));

        // Campo para definir se o frete é por unidade na variação
        woocommerce_wp_checkbox(array(
            'id' => '_ffpi_per_unit_shipping_' . $variation->ID,
            'name' => '_ffpi_per_unit_shipping[' . $variation->ID . ']',
            'label' => __('Frete por unidade', 'frete-fixo-para-produto-individual'),
            'value' => get_post_meta($variation->ID, '_ffpi_per_unit_shipping', true) ?: $default_per_unit,
            'wrapper_class' => 'form-row form-row-first',
        ));

        // Campo para habilitar frete grátis na variação
        woocommerce_wp_checkbox(array(
            'id' => '_ffpi_enable_free_shipping_' . $variation->ID,
            'name' => '_ffpi_enable_free_shipping[' . $variation->ID . ']',
            'label' => __('Frete grátis', 'frete-fixo-para-produto-individual'),
            'value' => get_post_meta($variation->ID, '_ffpi_enable_free_shipping', true),
            'wrapper_class' => 'form-row form-row-last',
        ));

        echo '</div>';
    }

    /**
     * Salva os campos de frete fixo das variações do produto
     */
    public function save_variation_shipping_fields($variation_id, $loop) {
        // Salvar campo de habilitar frete fixo
        $enable_fixed_shipping = isset($_POST['_ffpi_enable_fixed_shipping'][$variation_id]) ? 'yes' : 'no';
        update_post_meta($variation_id, '_ffpi_enable_fixed_shipping', $enable_fixed_shipping);

        // Salvar campo de valor do frete fixo
        if (isset($_POST['_ffpi_fixed_shipping_cost'][$variation_id])) {
            update_post_meta($variation_id, '_ffpi_fixed_shipping_cost', wc_format_decimal($_POST['_ffpi_fixed_shipping_cost'][$variation_id]));
        }

        // Salvar campo de frete por unidade
        $per_unit_shipping = isset($_POST['_ffpi_per_unit_shipping'][$variation_id]) ? 'yes' : 'no';
        update_post_meta($variation_id, '_ffpi_per_unit_shipping', $per_unit_shipping);

        // Salvar campo de frete grátis
        $enable_free_shipping = isset($_POST['_ffpi_enable_free_shipping'][$variation_id]) ? 'yes' : 'no';
        update_post_meta($variation_id, '_ffpi_enable_free_shipping', $enable_free_shipping);
    }

    /**
     * Aplica o frete fixo por produto
     */
    public function apply_product_fixed_shipping($rates, $package) {
        if (empty($package['contents'])) {
            return $rates;
        }

        $has_fixed_shipping = false;
        $total_fixed_shipping = 0;
        $all_free_shipping = true;

        // Verificar cada item no carrinho
        foreach ($package['contents'] as $item) {
            $product_id = $item['product_id'];
            $variation_id = $item['variation_id'];
            $quantity = $item['quantity'];
            
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

            // Se o produto tem frete grátis
            if ($enable_free_shipping === 'yes') {
                continue;
            }

            // Se não tem frete grátis, então nem todos os produtos têm frete grátis
            $all_free_shipping = false;

            // Se o produto tem frete fixo
            if ($enable_fixed_shipping === 'yes' && $fixed_shipping_cost > 0) {
                $has_fixed_shipping = true;
                
                // Se o frete é por unidade, multiplicar pelo quantidade
                if ($per_unit_shipping === 'yes') {
                    $total_fixed_shipping += floatval($fixed_shipping_cost) * $quantity;
                } else {
                    // Se não é por unidade, adicionar apenas uma vez o valor do frete
                    $total_fixed_shipping += floatval($fixed_shipping_cost);
                }
            }
        }

        // Se todos os produtos têm frete grátis
        if ($all_free_shipping) {
            // Criar um método de frete grátis
            $free_rate = array(
                'id' => 'ffpi_free_shipping',
                'label' => __('Frete Grátis', 'frete-fixo-para-produto-individual'),
                'cost' => 0,
                'taxes' => false,
                'method_id' => 'ffpi_free_shipping',
                'package' => $package,
            );
            
            $rates = array();
            $rates['ffpi_free_shipping'] = new WC_Shipping_Rate($free_rate['id'], $free_rate['label'], $free_rate['cost'], $free_rate['taxes'], $free_rate['method_id']);
            
            return $rates;
        }

        // Se há produtos com frete fixo
        if ($has_fixed_shipping) {
            // Criar um método de frete fixo
            $fixed_rate = array(
                'id' => 'ffpi_fixed_shipping',
                'label' => __('Frete Fixo', 'frete-fixo-para-produto-individual'),
                'cost' => $total_fixed_shipping,
                'taxes' => false,
                'method_id' => 'ffpi_fixed_shipping',
                'package' => $package,
            );
            
            $rates = array();
            $rates['ffpi_fixed_shipping'] = new WC_Shipping_Rate($fixed_rate['id'], $fixed_rate['label'], $fixed_rate['cost'], $fixed_rate['taxes'], $fixed_rate['method_id']);
            
            return $rates;
        }

        return $rates;
    }

    /**
     * Adiciona coluna de frete na listagem de produtos
     */
    public function add_product_shipping_column($columns) {
        $new_columns = array();

        foreach ($columns as $key => $column) {
            $new_columns[$key] = $column;
            
            // Adicionar coluna de frete após a coluna de preço
            if ($key === 'price') {
                $new_columns['ffpi_shipping'] = __('Frete', 'frete-fixo-para-produto-individual');
            }
        }

        return $new_columns;
    }

    /**
     * Exibe o valor do frete na coluna de frete
     */
    public function display_product_shipping_column($column, $post_id) {
        if ($column === 'ffpi_shipping') {
            $product = wc_get_product($post_id);
            
            if (!$product) {
                return;
            }

            $enable_fixed_shipping = get_post_meta($post_id, '_ffpi_enable_fixed_shipping', true);
            $enable_free_shipping = get_post_meta($post_id, '_ffpi_enable_free_shipping', true);
            $fixed_shipping_cost = get_post_meta($post_id, '_ffpi_fixed_shipping_cost', true);

            if ($enable_free_shipping === 'yes') {
                echo '<span style="color: green;">' . __('Grátis', 'frete-fixo-para-produto-individual') . '</span>';
            } elseif ($enable_fixed_shipping === 'yes' && $fixed_shipping_cost > 0) {
                echo 'R$ ' . number_format($fixed_shipping_cost, 2, ',', '.');
            } else {
                echo '-';
            }

            // Se for um produto variável, mostrar que há valores diferentes para variações
            if ($product->is_type('variable')) {
                $variations = $product->get_available_variations();
                $has_variation_shipping = false;

                foreach ($variations as $variation) {
                    $variation_id = $variation['variation_id'];
                    $variation_enable_fixed_shipping = get_post_meta($variation_id, '_ffpi_enable_fixed_shipping', true);
                    $variation_enable_free_shipping = get_post_meta($variation_id, '_ffpi_enable_free_shipping', true);
                    
                    if ($variation_enable_fixed_shipping === 'yes' || $variation_enable_free_shipping === 'yes') {
                        $has_variation_shipping = true;
                        break;
                    }
                }

                if ($has_variation_shipping) {
                    echo '<br><small>' . __('(Variações têm valores diferentes)', 'frete-fixo-para-produto-individual') . '</small>';
                }
            }
        }
    }
}
