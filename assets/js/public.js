/**
 * Scripts para a parte pública do plugin
 */
jQuery(document).ready(function($) {
    // Atualizar informações de frete quando a variação for alterada
    if (typeof ffpi_variations !== 'undefined') {
        $(document).on('found_variation', 'form.variations_form', function(event, variation) {
            var variationId = variation.variation_id;
            
            if (ffpi_variations.hasOwnProperty(variationId)) {
                var variationData = ffpi_variations[variationId];
                var $shippingInfo = $('#ffpi-variation-shipping-info');
                
                $shippingInfo.empty();
                
                if (variationData.enable_free_shipping === 'yes') {
                    $shippingInfo.html('<p class="ffpi-free-shipping"><span class="dashicons dashicons-yes"></span> ' + 
                        ffpi_public_vars.free_shipping_text + '</p>');
                } else if (variationData.enable_fixed_shipping === 'yes' && variationData.fixed_shipping_cost > 0) {
                    var shippingText = '<p class="ffpi-fixed-shipping">' + 
                        ffpi_public_vars.fixed_shipping_text + ' <span class="amount">' + 
                        variationData.formatted_price + '</span>';
                    
                    // Adicionar informação se o frete é por unidade ou valor único
                    if (variationData.per_unit_shipping === 'yes') {
                        shippingText += ' <span class="ffpi-per-unit">(' + ffpi_public_vars.per_unit_text + ')</span>';
                    } else {
                        shippingText += ' <span class="ffpi-per-unit">(' + ffpi_public_vars.single_value_text + ')</span>';
                    }
                    
                    shippingText += '</p>';
                    $shippingInfo.html(shippingText);
                }
            }
        });
        
        // Limpar informações de frete quando a variação for resetada
        $(document).on('reset_data', 'form.variations_form', function() {
            $('#ffpi-variation-shipping-info').empty();
        });
    }
    
    // Adicionar ícone de frete grátis ao botão de adicionar ao carrinho para produtos com frete grátis
    if ($('.ffpi-free-shipping').length > 0) {
        $('.single_add_to_cart_button').addClass('ffpi-free-shipping-button');
    }
});
