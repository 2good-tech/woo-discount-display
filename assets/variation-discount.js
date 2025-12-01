/**
 * WooCommerce Discount Display - Variation Handler
 * Handles dynamic discount display for variable products
 */

jQuery(document).ready(function ($) {
    // Only run if we have variation forms on the page
    if (!$('form.variations_form').length) {
        return;
    }    // Function to calculate and display discount
    function updateDiscountDisplay(variation) {
        // Find the variation price container
        var priceContainer = $('.woocommerce-variation-price');

        // Get or create the discount element (persistent across variations)
        var discountElement = priceContainer.find('.wdd-discount-info.wdd-js-discount');
        if (!discountElement.length) {
            discountElement = $('<div class="wdd-discount-info wdd-js-discount"></div>');
            priceContainer.append(discountElement);
        }
        // Hide discount if no variation data or price container isn't ready
        if (!variation || !variation.display_price || !variation.display_regular_price ||
            !priceContainer.length || !priceContainer.is(':visible') || priceContainer.html().trim() === '') {
            //discountElement.addClass('wdd-hidden');
            discountElement.remove();

            //console.log('No variation data or price container not ready');
            return;
        }

        var regularPrice = parseFloat(variation.display_regular_price);
        var salePrice = parseFloat(variation.display_price);

        // Hide discount if product is not on sale
        if (regularPrice <= salePrice || salePrice <= 0) {
            //discountElement.addClass('wdd-hidden');
            discountElement.remove();
            //console.log('Product is not on sale or sale price is invalid');
            return;
        }

        var discountAmount = regularPrice - salePrice;
        // var discountPercentage = Math.floor((discountAmount / regularPrice) * 100);
		// Fix floating-point precision issues by rounding to 10 decimal places before floor
        var discountPercentage = Math.floor(Math.round((discountAmount / regularPrice) * 100 * 10000000000) / 10000000000);

        // Format the discount amount using WooCommerce's built-in formatting
        var formattedAmount;

        // First try to use our localized parameters
        if (typeof wdd_params !== 'undefined' && wdd_params.currency_symbol) {
            var symbol = wdd_params.currency_symbol;
            var decimals = parseInt(wdd_params.currency_decimals) || 2;
            var decimalSep = wdd_params.currency_decimal_sep || '.';
            var thousandSep = wdd_params.currency_thousand_sep || ' ';
            var currencyPos = wdd_params.currency_pos || 'left';

            // Format the number
            var formattedNumber = discountAmount.toFixed(decimals);
            if (thousandSep && discountAmount >= 1000) {
                formattedNumber = formattedNumber.replace(/\B(?=(\d{3})+(?!\d))/g, thousandSep);
            }
            formattedNumber = formattedNumber.replace('.', decimalSep);

            // Apply currency symbol position
            switch (currencyPos) {
                case 'left':
                    formattedAmount = symbol + formattedNumber;
                    break;
                case 'right':
                    formattedAmount = formattedNumber + symbol;
                    break;
                case 'left_space':
                    formattedAmount = symbol + ' ' + formattedNumber;
                    break;
                case 'right_space':
                    formattedAmount = formattedNumber + ' ' + symbol;
                    break;
                default:
                    formattedAmount = symbol + formattedNumber;
            }
        } else if (typeof wc_add_to_cart_variation_params !== 'undefined' && wc_add_to_cart_variation_params.currency_format_symbol) {
            // Fallback to WooCommerce variation params
            var symbol = wc_add_to_cart_variation_params.currency_format_symbol;
            var decimals = parseInt(wc_add_to_cart_variation_params.currency_format_num_decimals) || 2;
            var decimalSep = wc_add_to_cart_variation_params.currency_format_decimal_sep || '.';
            var thousandSep = wc_add_to_cart_variation_params.currency_format_thousand_sep || ' ';

            // Format the number
            var formattedNumber = discountAmount.toFixed(decimals);
            if (thousandSep && discountAmount >= 1000) {
                formattedNumber = formattedNumber.replace(/\B(?=(\d{3})+(?!\d))/g, thousandSep);
            }
            formattedNumber = formattedNumber.replace('.', decimalSep);

            // Apply currency symbol position
            var currencyPos = wc_add_to_cart_variation_params.currency_pos || 'left';
            switch (currencyPos) {
                case 'left':
                    formattedAmount = symbol + formattedNumber;
                    break;
                case 'right':
                    formattedAmount = formattedNumber + symbol;
                    break;
                case 'left_space':
                    formattedAmount = symbol + ' ' + formattedNumber;
                    break;
                case 'right_space':
                    formattedAmount = formattedNumber + ' ' + symbol;
                    break;
                default:
                    formattedAmount = symbol + formattedNumber;
            }
        } else {
            // Final fallback - try to get currency from the page
            var currencyElement = $('.woocommerce-Price-currencySymbol').first();
            var symbol = currencyElement.length ? currencyElement.text() : '€'; //default to euro symbol
            formattedAmount = symbol + discountAmount.toFixed(2);
        }
        var saveText = (typeof wdd_params !== 'undefined' && wdd_params.save_text) ? wdd_params.save_text : 'Save: ';
        var discountText = saveText + formattedAmount + ' <span class="wdd-percentage">-' + discountPercentage + '%</span>';

        // Update the content and show the element with smooth transition
        discountElement.html(discountText).removeClass('wdd-hidden');
    }

    // Listen for variation changes with longer delay to let WooCommerce finish its DOM updates
    $('form.variations_form').on('show_variation', function (event, variation) {
        updateDiscountDisplay(variation);
        //console.log('Variation changed, updating discount display');
    });

    // Also listen for variation found event
    $('form.variations_form').on('found_variation', function (event, variation) {
        updateDiscountDisplay(variation);
        //console.log('Variation found, updating discount display');
    });    
    
    // Hide discount when variation is cleared
    /*$('form.variations_form').on('hide_variation', function () {
        //$('.wdd-discount-info.wdd-js-discount').addClass('wdd-hidden');
        //$('.wdd-discount-info.wdd-js-discount').remove();
        //console.log('Variation cleared, hiding discount');
    });*/

    // Also listen for reset variations
    /*$('form.variations_form').on('reset_data', function () {
        //$('.wdd-discount-info.wdd-js-discount').addClass('wdd-hidden');
        //$('.wdd-discount-info.wdd-js-discount').remove();
        //console.log('Variations reset, hiding discount');
    });*/

    // Alternative approach: Use MutationObserver to watch for WooCommerce price updates
    if (window.MutationObserver) {
        //console.log('Using MutationObserver to watch for price updates');
        var observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                if (mutation.type === 'childList' &&
                    $(mutation.target).hasClass('woocommerce-variation-price') &&
                    !$(mutation.target).find('.wdd-discount-info.wdd-js-discount').length) {

                    // WooCommerce updated the price container, add our discount
                    var form = $(mutation.target).closest('form.variations_form');
                    if (form.length) {
                        var currentVariation = form.find('.single_variation_wrap').data('variation');
                        if (currentVariation) {
                            setTimeout(function () {
                                updateDiscountDisplay(currentVariation);
                            }, 50);
                        }
                    }
                }
            });
        });

        // Start observing price container changes
        var priceContainers = $('.woocommerce-variation-price');
        priceContainers.each(function () {
            observer.observe(this, {
                childList: true,
                subtree: true
            });
        });
    }

    // Check if a variation is already selected on page load
    var selectedVariation = $('form.variations_form').data('product_variations');
    if (selectedVariation && selectedVariation.length > 0) {
        var currentVariation = $('form.variations_form').find('select').first().val();
        if (currentVariation) {
            setTimeout(function () {
                $('form.variations_form').trigger('check_variations');
            }, 300);
        }
    }
});
