/**
 * Scripts para a parte administrativa do plugin
 */
jQuery(document).ready(function($) {
    // Função para formatar preço
    function formatPrice(price) {
        if (typeof price !== 'number') {
            price = parseFloat(price);
        }
        
        if (isNaN(price)) {
            return '';
        }
        
        return price.toFixed(ffpi_admin_vars.decimals)
            .replace('.', ffpi_admin_vars.decimal_separator)
            .replace(/\B(?=(\d{3})+(?!\d))/g, ffpi_admin_vars.thousand_separator);
    }
    
    // Mostrar/esconder campo de valor de frete fixo e frete por unidade quando o checkbox de habilitar frete fixo é alterado
    function toggleFixedShippingCost() {
        $('._ffpi_enable_fixed_shipping_field').each(function() {
            var $checkbox = $(this).find('input[type="checkbox"]');
            var $costField = $(this).closest('.options_group, .variation-shipping-options').find('._ffpi_fixed_shipping_cost_field');
            var $perUnitField = $(this).closest('.options_group, .variation-shipping-options').find('._ffpi_per_unit_shipping_field');
            
            if ($checkbox.is(':checked')) {
                $costField.show();
                $perUnitField.show();
            } else {
                $costField.hide();
                $perUnitField.hide();
            }
        });
    }
    
    // Esconder campo de frete fixo e frete por unidade quando o checkbox de frete grátis é marcado
    function toggleFreeShipping() {
        $('._ffpi_enable_free_shipping_field').each(function() {
            var $checkbox = $(this).find('input[type="checkbox"]');
            var $fixedShippingCheckbox = $(this).closest('.options_group, .variation-shipping-options').find('._ffpi_enable_fixed_shipping_field input[type="checkbox"]');
            var $costField = $(this).closest('.options_group, .variation-shipping-options').find('._ffpi_fixed_shipping_cost_field');
            var $perUnitField = $(this).closest('.options_group, .variation-shipping-options').find('._ffpi_per_unit_shipping_field');
            
            if ($checkbox.is(':checked')) {
                $fixedShippingCheckbox.prop('checked', false).prop('disabled', true);
                $costField.hide();
                $perUnitField.hide();
            } else {
                $fixedShippingCheckbox.prop('disabled', false);
                toggleFixedShippingCost();
            }
        });
    }
    
    // Inicializar estados dos campos
    toggleFixedShippingCost();
    toggleFreeShipping();
    
    // Eventos para os checkboxes
    $(document).on('change', '._ffpi_enable_fixed_shipping_field input[type="checkbox"]', function() {
        toggleFixedShippingCost();
    });
    
    $(document).on('change', '._ffpi_enable_free_shipping_field input[type="checkbox"]', function() {
        toggleFreeShipping();
    });
    
    // Validar valor do frete fixo
    $(document).on('change', '._ffpi_fixed_shipping_cost_field input[type="number"]', function() {
        var value = $(this).val();
        
        if (value < 0) {
            $(this).val(0);
        }
    });
    
    // Adicionar eventos para as variações
    $(document).on('woocommerce_variations_loaded', function() {
        toggleFixedShippingCost();
        toggleFreeShipping();
    });
    
    $(document).on('woocommerce_variation_added', function() {
        toggleFixedShippingCost();
        toggleFreeShipping();
    });
});
