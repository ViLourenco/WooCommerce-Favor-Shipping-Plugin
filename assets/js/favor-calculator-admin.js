/**
 * Favor Shipping Calculator Admin Script
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        // Initialize color pickers
        if ($.fn.wpColorPicker) {
            $('.favor-color-picker').wpColorPicker({
                change: function(event, ui) {
                    // Small delay to let the input value update
                    setTimeout(updatePreview, 10);
                },
                clear: function() {
                    setTimeout(updatePreview, 10);
                }
            });
        }

        // Update preview on any setting change
        $(document).on('change', 'input[type="text"], select, input[type="checkbox"]', function() {
            updatePreview();
        });

        // Listen for theme preset changes specifically
        $('#woocommerce_favor_calculator_theme').on('change', function() {
            updatePreview();
        });

        // Initial preview update
        setTimeout(updatePreview, 500);
    });

    /**
     * Update the live preview
     */
    function updatePreview() {
        const settings = getSettings();
        applyPreviewStyles(settings);
    }

    /**
     * Get current settings values
     */
    function getSettings() {
        return {
            primaryColor: $('#woocommerce_favor_calculator_primary_color').val() || '#0fae79',
            errorColor: $('#woocommerce_favor_calculator_error_color').val() || '#e74c3c',
            bgColor: $('#woocommerce_favor_calculator_bg_color').val() || '#ffffff',
            resultsBgColor: $('#woocommerce_favor_calculator_results_bg_color').val() || '#ffffff',
            textColor: $('#woocommerce_favor_calculator_text_color').val() || '#1a1a1a',
            textSecondaryColor: $('#woocommerce_favor_calculator_text_secondary_color').val() || '#777777',
            borderColor: $('#woocommerce_favor_calculator_border_color').val() || '#e0e0e0',
            fontSize: $('#woocommerce_favor_calculator_font_size').val() || 'medium',
            borderRadius: $('#woocommerce_favor_calculator_border_radius').val() || 'medium',
            theme: $('#woocommerce_favor_calculator_theme').val() || 'light'
        };
    }

    /**
     * Apply styles to preview
     */
    function applyPreviewStyles(settings) {
        const $preview = $('#favor-calculator-preview');
        if (!$preview.length) {
            return;
        }

        // Handle theme presets
        let bgColor = settings.bgColor;
        let resultsBgColor = settings.resultsBgColor;
        let textColor = settings.textColor;
        let textSecondaryColor = settings.textSecondaryColor;
        let borderColor = settings.borderColor;

        if (settings.theme === 'dark') {
            bgColor = '#1a1a1a';
            resultsBgColor = '#242424';
            textColor = '#ffffff';
            textSecondaryColor = '#aaaaaa';
            borderColor = '#333333';
        } else if (settings.theme === 'light') {
            bgColor = '#ffffff';
            resultsBgColor = '#ffffff';
            textColor = '#1a1a1a';
            textSecondaryColor = '#777777';
            borderColor = '#e0e0e0';
        }

        // Map font sizes
        const fontSizes = {
            'small': '12px',
            'medium': '14px',
            'large': '16px'
        };
        const fontSizeValue = fontSizes[settings.fontSize] || '14px';

        // Map border radius
        const borderRadiusMap = {
            'none': '0',
            'small': '4px',
            'medium': '8px',
            'large': '12px'
        };
        const borderRadiusValue = borderRadiusMap[settings.borderRadius] || '8px';

        // Apply CSS variables
        $preview.css({
            '--favor-primary-color': settings.primaryColor,
            '--favor-error-color': settings.errorColor,
            '--favor-bg-color': bgColor,
            '--favor-results-bg-color': resultsBgColor,
            '--favor-text-color': textColor,
            '--favor-text-secondary-color': textSecondaryColor,
            '--favor-border-color': borderColor,
            '--favor-font-size': fontSizeValue,
            '--favor-border-radius': borderRadiusValue
        });
    }
})(jQuery);
