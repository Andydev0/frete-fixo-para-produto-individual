<?php
/**
 * Classe para gerenciar a parte administrativa do plugin
 *
 * @package Frete_Fixo_Para_Produto_Individual
 * @subpackage Admin
 */

if (!defined('WPINC')) {
    die;
}

class FFPI_Admin {

    /**
     * Inicializa a classe e define os hooks
     */
    public function __construct() {
        // Adicionar scripts e estilos no admin
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Adicionar filtros na listagem de produtos
        add_action('restrict_manage_posts', array($this, 'add_product_shipping_filter'));
        add_filter('parse_query', array($this, 'filter_products_by_shipping'));
        
        // Adicionar meta box para configurações do plugin
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    /**
     * Registra e carrega os scripts e estilos no admin
     */
    public function enqueue_admin_scripts($hook) {
        // Carregar apenas nas páginas de produto
        if ('post.php' !== $hook && 'post-new.php' !== $hook) {
            return;
        }

        global $post;
        
        // Verificar se é um produto
        if (isset($post) && 'product' === $post->post_type) {
            wp_enqueue_style('ffpi-admin-css', FFPI_PLUGIN_URL . 'assets/css/admin.css', array(), FFPI_VERSION);
            wp_enqueue_script('ffpi-admin-js', FFPI_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), FFPI_VERSION, true);
            
            // Passar variáveis para o script
            wp_localize_script('ffpi-admin-js', 'ffpi_admin_vars', array(
                'currency_symbol' => get_woocommerce_currency_symbol(),
                'decimal_separator' => wc_get_price_decimal_separator(),
                'thousand_separator' => wc_get_price_thousand_separator(),
                'decimals' => wc_get_price_decimals(),
                'price_format' => get_woocommerce_price_format(),
            ));
        }
    }

    /**
     * Adiciona filtro de frete na listagem de produtos
     */
    public function add_product_shipping_filter() {
        global $typenow;
        
        if ('product' === $typenow) {
            $current = isset($_GET['ffpi_shipping_filter']) ? $_GET['ffpi_shipping_filter'] : '';
            ?>
            <select name="ffpi_shipping_filter" id="ffpi_shipping_filter">
                <option value=""><?php _e('Filtrar por frete', 'frete-fixo-para-produto-individual'); ?></option>
                <option value="fixed" <?php selected($current, 'fixed'); ?>><?php _e('Frete Fixo', 'frete-fixo-para-produto-individual'); ?></option>
                <option value="free" <?php selected($current, 'free'); ?>><?php _e('Frete Grátis', 'frete-fixo-para-produto-individual'); ?></option>
                <option value="none" <?php selected($current, 'none'); ?>><?php _e('Sem Frete Fixo', 'frete-fixo-para-produto-individual'); ?></option>
            </select>
            <?php
        }
    }

    /**
     * Filtra produtos por tipo de frete
     */
    public function filter_products_by_shipping($query) {
        global $pagenow, $typenow;
        
        if ('edit.php' === $pagenow && 'product' === $typenow && isset($_GET['ffpi_shipping_filter']) && !empty($_GET['ffpi_shipping_filter'])) {
            $filter = $_GET['ffpi_shipping_filter'];
            
            if ('fixed' === $filter) {
                $query->query_vars['meta_query'][] = array(
                    'key' => '_ffpi_enable_fixed_shipping',
                    'value' => 'yes',
                    'compare' => '='
                );
            } elseif ('free' === $filter) {
                $query->query_vars['meta_query'][] = array(
                    'key' => '_ffpi_enable_free_shipping',
                    'value' => 'yes',
                    'compare' => '='
                );
            } elseif ('none' === $filter) {
                $query->query_vars['meta_query'][] = array(
                    'relation' => 'AND',
                    array(
                        'relation' => 'OR',
                        array(
                            'key' => '_ffpi_enable_fixed_shipping',
                            'value' => 'yes',
                            'compare' => '!='
                        ),
                        array(
                            'key' => '_ffpi_enable_fixed_shipping',
                            'compare' => 'NOT EXISTS'
                        )
                    ),
                    array(
                        'relation' => 'OR',
                        array(
                            'key' => '_ffpi_enable_free_shipping',
                            'value' => 'yes',
                            'compare' => '!='
                        ),
                        array(
                            'key' => '_ffpi_enable_free_shipping',
                            'compare' => 'NOT EXISTS'
                        )
                    )
                );
            }
        }
    }

    /**
     * Adiciona página de configurações do plugin
     */
    public function add_settings_page() {
        add_submenu_page(
            'woocommerce',
            __('Configurações de Frete Fixo', 'frete-fixo-para-produto-individual'),
            __('Frete Fixo', 'frete-fixo-para-produto-individual'),
            'manage_options',
            'ffpi-settings',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Renderiza a página de configurações do plugin
     */
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <?php settings_errors('ffpi_settings'); ?>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('ffpi_settings');
                do_settings_sections('ffpi_settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Registra as configurações do plugin
     */
    public function register_settings() {
        register_setting(
            'ffpi_settings', 
            'ffpi_settings',
            array($this, 'sanitize_settings')
        );
        
        add_settings_section(
            'ffpi_general_section',
            __('Configurações Gerais', 'frete-fixo-para-produto-individual'),
            array($this, 'render_general_section'),
            'ffpi_settings'
        );
        
        add_settings_field(
            'ffpi_shipping_label',
            __('Rótulo do Frete Fixo', 'frete-fixo-para-produto-individual'),
            array($this, 'render_shipping_label_field'),
            'ffpi_settings',
            'ffpi_general_section'
        );
        
        add_settings_field(
            'ffpi_free_shipping_label',
            __('Rótulo do Frete Grátis', 'frete-fixo-para-produto-individual'),
            array($this, 'render_free_shipping_label_field'),
            'ffpi_settings',
            'ffpi_general_section'
        );
        
        add_settings_field(
            'ffpi_default_per_unit',
            __('Frete por unidade como padrão', 'frete-fixo-para-produto-individual'),
            array($this, 'render_default_per_unit_field'),
            'ffpi_settings',
            'ffpi_general_section'
        );
        
        add_settings_field(
            'ffpi_show_in_product_list',
            __('Mostrar frete na lista de produtos', 'frete-fixo-para-produto-individual'),
            array($this, 'render_show_in_product_list_field'),
            'ffpi_settings',
            'ffpi_general_section'
        );
    }

    /**
     * Sanitiza as configurações antes de salvar
     */
    public function sanitize_settings($input) {
        $sanitized_input = array();
        
        // Sanitizar campos de texto
        if (isset($input['shipping_label'])) {
            $sanitized_input['shipping_label'] = sanitize_text_field($input['shipping_label']);
        }
        
        if (isset($input['free_shipping_label'])) {
            $sanitized_input['free_shipping_label'] = sanitize_text_field($input['free_shipping_label']);
        }
        
        // Tratar checkboxes - importante: quando não marcados, eles não são enviados
        $sanitized_input['default_per_unit'] = isset($input['default_per_unit']) ? 'yes' : 'no';
        $sanitized_input['show_in_product_list'] = isset($input['show_in_product_list']) ? 'yes' : 'no';
        
        return $sanitized_input;
    }

    /**
     * Renderiza a seção geral de configurações
     */
    public function render_general_section() {
        echo '<p>' . __('Configure as opções gerais do plugin de Frete Fixo por Produto.', 'frete-fixo-para-produto-individual') . '</p>';
    }

    /**
     * Renderiza o campo de rótulo do frete fixo
     */
    public function render_shipping_label_field() {
        $options = get_option('ffpi_settings');
        $value = isset($options['shipping_label']) ? $options['shipping_label'] : __('Frete Fixo', 'frete-fixo-para-produto-individual');
        ?>
        <input type="text" name="ffpi_settings[shipping_label]" value="<?php echo esc_attr($value); ?>" class="regular-text">
        <p class="description"><?php _e('Rótulo exibido para o método de frete fixo no checkout.', 'frete-fixo-para-produto-individual'); ?></p>
        <?php
    }

    /**
     * Renderiza o campo de rótulo do frete grátis
     */
    public function render_free_shipping_label_field() {
        $options = get_option('ffpi_settings');
        $value = isset($options['free_shipping_label']) ? $options['free_shipping_label'] : __('Frete Grátis', 'frete-fixo-para-produto-individual');
        ?>
        <input type="text" name="ffpi_settings[free_shipping_label]" value="<?php echo esc_attr($value); ?>" class="regular-text">
        <p class="description"><?php _e('Rótulo exibido para o método de frete grátis no checkout.', 'frete-fixo-para-produto-individual'); ?></p>
        <?php
    }

    /**
     * Renderiza o campo de frete por unidade como padrão
     */
    public function render_default_per_unit_field() {
        $options = get_option('ffpi_settings');
        $value = isset($options['default_per_unit']) ? $options['default_per_unit'] : 'yes';
        ?>
        <label>
            <input type="checkbox" name="ffpi_settings[default_per_unit]" value="yes" <?php checked($value, 'yes'); ?>>
            <?php _e('Marcar "Frete por unidade" como padrão para novos produtos', 'frete-fixo-para-produto-individual'); ?>
        </label>
        <p class="description"><?php _e('Se marcado, novos produtos terão a opção "Frete por unidade" habilitada por padrão.', 'frete-fixo-para-produto-individual'); ?></p>
        <?php
    }

    /**
     * Renderiza o campo de mostrar frete na lista de produtos
     */
    public function render_show_in_product_list_field() {
        $options = get_option('ffpi_settings');
        $value = isset($options['show_in_product_list']) ? $options['show_in_product_list'] : 'yes';
        ?>
        <label>
            <input type="checkbox" name="ffpi_settings[show_in_product_list]" value="yes" <?php checked($value, 'yes'); ?>>
            <?php _e('Mostrar a coluna de frete na tabela de produtos do painel administrativo', 'frete-fixo-para-produto-individual'); ?>
        </label>
        <?php
    }
}
