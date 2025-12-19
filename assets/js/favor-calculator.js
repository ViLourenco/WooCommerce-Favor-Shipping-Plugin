/**
 * Favor Shipping Calculator Frontend Script
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        const $calculator = $('#favor-shipping-calculator');
        if (!$calculator.length) {
            return;
        }

        const $cepInput = $('#favor-cep-input');
        const $calculateBtn = $('#favor-calculate-btn');
        const $results = $('#favor-calculator-results');
        const $loading = $('#favor-calculator-loading');
        const $error = $('#favor-calculator-error');

        // Format CEP input (XXXXX-XXX)
        $cepInput.on('input', function() {
            let value = $(this).val().replace(/\D/g, '');
            if (value.length > 5) {
                value = value.substring(0, 5) + '-' + value.substring(5, 8);
            }
            $(this).val(value);
        });

        // Calculate shipping
        $calculateBtn.on('click', function() {
            const cep = $cepInput.val().replace(/\D/g, '');
            
            if (cep.length !== 8) {
                showError(favorCalculator.strings.invalidCep);
                return;
            }

            calculateShipping(cep);
        });

        // Allow Enter key to trigger calculation
        $cepInput.on('keypress', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                $calculateBtn.trigger('click');
            }
        });

        /**
         * Calculate shipping via AJAX
         */
        function calculateShipping(cep) {
            // Hide previous results and errors
            $results.hide();
            $error.hide();
            $loading.show();
            $calculateBtn.prop('disabled', true);

            const productId = $calculator.data('product-id') || getProductIdFromPage();

            $.ajax({
                url: favorCalculator.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'favor_calculate_shipping',
                    nonce: favorCalculator.nonce,
                    cep: cep,
                    product_id: productId
                },
                success: function(response) {
                    $loading.hide();
                    $calculateBtn.prop('disabled', false);

                    if (response.success && response.data.results) {
                        displayResults(response.data.results);
                    } else {
                        const errorMsg = response.data && response.data.message 
                            ? response.data.message 
                            : favorCalculator.strings.error;
                        showError(errorMsg);
                    }
                },
                error: function() {
                    $loading.hide();
                    $calculateBtn.prop('disabled', false);
                    showError(favorCalculator.strings.error);
                }
            });
        }

        /**
         * Display shipping results
         */
        function displayResults(results) {
            if (!results || results.length === 0) {
                showError('Nenhuma opção de frete disponível para este CEP.');
                return;
            }

            let html = '<div class="favor-results-title">Opções de Entrega</div>';
            html += '<div class="favor-results-list">';

            results.forEach(function(result) {
                const price = formatPrice(result.price);
                const deadline = result.deadline ? ' - (' + result.deadline + ')' : '';
                
                html += '<div class="favor-result-item">';
                html += '<div class="favor-result-name">' + escapeHtml(result.name) + deadline + '</div>';
                html += '<div class="favor-result-price">' + price + '</div>';
                html += '</div>';
            });

            html += '</div>';

            $results.html(html).show();
        }

        /**
         * Show error message
         */
        function showError(message) {
            $error.text(message).show();
        }

        /**
         * Format price as Brazilian Real
         */
        function formatPrice(price) {
            return 'R$ ' + parseFloat(price).toFixed(2).replace('.', ',');
        }

        /**
         * Escape HTML to prevent XSS
         */
        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        }

        /**
         * Get product ID from page
         */
        function getProductIdFromPage() {
            // Try to get from WooCommerce product data
            if (typeof wc_add_to_cart_params !== 'undefined') {
                return wc_add_to_cart_params.product_id || 0;
            }
            
            // Try to get from body class
            const bodyClasses = $('body').attr('class').split(' ');
            for (let i = 0; i < bodyClasses.length; i++) {
                const match = bodyClasses[i].match(/^postid-(\d+)$/);
                if (match) {
                    return parseInt(match[1], 10);
                }
            }
            
            // Try to get from product form
            const $productForm = $('form.cart');
            if ($productForm.length) {
                const productId = $productForm.find('input[name="product_id"]').val() || 
                                 $productForm.find('input[name="add-to-cart"]').val();
                if (productId) {
                    return parseInt(productId, 10);
                }
            }

            return 0;
        }
    });
})(jQuery);
