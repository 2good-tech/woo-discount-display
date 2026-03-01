<?php
/**
 * Plugin Name: WooCommerce Discount Display
 * Description: Displays discount information below product prices when products are on promotion. Shows "Save: [amount] -x%" for products with discounts.
 * Version: 1.2.0
 * Author: 2GOOD Technologies Ltd.
 * Author URI: https://2good.tech
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: woo-discount-display
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.5
 * WC requires at least: 5.0
 * WC tested up to: 8.8
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Check if WooCommerce is active
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    return;
}

// Define plugin constants
define('WDD_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WDD_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('WDD_VERSION', '1.2.0');

/**
 * Feature Toggles - Set to true/false to enable/disable features
 */
define('WDD_ENABLE_SALE_COUNTDOWN', true);  // Enable/disable sale end date countdown feature
define('WDD_COUNTDOWN_THRESHOLD_HOURS', 48); // Hours threshold for showing countdown vs static date

// Load admin menu
if (is_admin()) {
    require_once WDD_PLUGIN_PATH . 'includes/class-2good-admin-menu.php';
    _2GOOD_Admin_Menu::init();
}

// GitHub auto-updater
require_once WDD_PLUGIN_PATH . 'includes/class-2good-github-updater.php';
new _2GOOD_GitHub_Updater(
    'woo-discount-display/woo-discount-display.php',
    '2good-tech',
    'woo-discount-display'
);

/**
 * Main Plugin Class
 */
class WooDiscountDisplay {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'init'));
    }
      /**
     * Initialize the plugin
     */
    public function init() {
        // Load text domain for translations
        load_plugin_textdomain('woo-discount-display', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Hook into WooCommerce
        add_action('woocommerce_single_product_summary', array($this, 'display_discount_info'), 11);
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }    /**
     * Enqueue plugin styles and scripts
     */
    public function enqueue_scripts() {
        if (is_product()) {
            wp_enqueue_style('wdd-styles', WDD_PLUGIN_URL . 'assets/style.css', array(), WDD_VERSION);
            
            // Only enqueue JavaScript for variable products with different prices
            global $product;
            
            // Ensure we have a valid product object
            if (!$product || !is_object($product)) {
                $product = wc_get_product();
            }
            
            if ($product && is_object($product) && method_exists($product, 'is_type') && $product->is_type('variable') && $this->has_different_variation_prices($product)) {
                wp_enqueue_script('wdd-variation-script', WDD_PLUGIN_URL . 'assets/variation-discount.js', array('jquery'), WDD_VERSION, true);
                
                // Localize script for translation and currency formatting
                wp_localize_script('wdd-variation-script', 'wdd_params', array(
                    'save_text' => __('Save: ', 'woo-discount-display'),
                    'currency_symbol' => get_woocommerce_currency_symbol(),
                    'currency_pos' => get_option('woocommerce_currency_pos'),
                    'currency_decimals' => wc_get_price_decimals(),
                    'currency_decimal_sep' => wc_get_price_decimal_separator(),
                    'currency_thousand_sep' => wc_get_price_thousand_separator()
                ));
            }
            
            // Enqueue countdown script if feature is enabled and product has sale end date
            if (WDD_ENABLE_SALE_COUNTDOWN && $product && $this->product_has_sale_end_date($product)) {
                wp_enqueue_style('wdd-countdown-styles', WDD_PLUGIN_URL . 'assets/countdown.css', array(), WDD_VERSION);
                wp_enqueue_script('wdd-countdown-script', WDD_PLUGIN_URL . 'assets/countdown.js', array('jquery'), WDD_VERSION, true);
                
                // Localize countdown script
                wp_localize_script('wdd-countdown-script', 'wdd_countdown_params', array(
                    'sale_expires_text' => __('Sale expires at:', 'woo-discount-display'),
                    'sale_ends_in_text' => __('Sale ends in:', 'woo-discount-display'),
                    'expired_text' => __('Sale ended', 'woo-discount-display'),
                    'threshold_hours' => WDD_COUNTDOWN_THRESHOLD_HOURS
                ));
            }
        }
    }
    
    /**
     * Check if product has a sale end date
     */
    private function product_has_sale_end_date($product) {
        if (!$product || !$product->is_on_sale()) {
            return false;
        }
        
        if ($product->is_type('variable')) {
            // Check if any variation has a sale end date
            $variations = $product->get_children();
            foreach ($variations as $variation_id) {
                $variation = wc_get_product($variation_id);
                if ($variation) {
                    $sale_end = $variation->get_date_on_sale_to();
                    if ($sale_end) {
                        return true;
                    }
                }
            }
            return false;
        } else {
            $sale_end = $product->get_date_on_sale_to();
            return !empty($sale_end);
        }
    }
    
    /**
     * Get sale end timestamp for a product
     */
    private function get_sale_end_timestamp($product) {
        if ($product->is_type('variable')) {
            // Get the earliest sale end date among variations
            $earliest_end = null;
            $variations = $product->get_children();
            foreach ($variations as $variation_id) {
                $variation = wc_get_product($variation_id);
                if ($variation) {
                    $sale_end = $variation->get_date_on_sale_to();
                    if ($sale_end) {
                        $timestamp = $sale_end->getTimestamp();
                        if ($earliest_end === null || $timestamp < $earliest_end) {
                            $earliest_end = $timestamp;
                        }
                    }
                }
            }
            return $earliest_end;
        } else {
            $sale_end = $product->get_date_on_sale_to();
            return $sale_end ? $sale_end->getTimestamp() : null;
        }
    }
    /**
     * Display discount information below product price
     */
    public function display_discount_info() {
        global $product;
        
        if (!$product) {
            return;
        }
        
        // Check if product is on sale
        if (!$product->is_on_sale()) {
            return;
        }
        
        // Handle variable products differently
        if ($product->is_type('variable')) {
            $this->display_variable_discount_info($product);
        } else {
            $this->display_simple_discount_info($product);
        }
    }
    
    /**
     * Display discount info for simple products
     */
    private function display_simple_discount_info($product) {
        $regular_price = (float) $product->get_regular_price();
        $sale_price = (float) $product->get_sale_price();
        
        if ($regular_price <= 0 || $sale_price <= 0) {
            return;
        }
        
        $discount_amount = $regular_price - $sale_price;
        $discount_percentage = floor(($discount_amount / $regular_price) * 100);
        
        if ($discount_amount > 0) {
            echo $this->render_discount_info($discount_amount, $discount_percentage, $product);
        }
    }    /**
     * Display discount info for variable products
     */
    private function display_variable_discount_info($product) {
        // Check if all variations have the same price/discount
        // If so, display discount info here, otherwise let JavaScript handle it
        
        $variations = $product->get_available_variations();
        if (empty($variations)) {
            return;
        }
        
        $price_data = $this->get_variation_price_data($variations);
        
        // If all variations have the same prices and there's a discount, show it
        if ($price_data['same_prices'] && $price_data['regular_price'] > $price_data['sale_price'] && $price_data['sale_price'] > 0) {
            $discount_amount = $price_data['regular_price'] - $price_data['sale_price'];
            $discount_percentage = round(($discount_amount / $price_data['regular_price']) * 100);
            echo $this->render_discount_info($discount_amount, $discount_percentage, $product);
        }
        // If variations have different prices, JavaScript will handle the display
    }
      /**
     * Check if variations have different prices/discounts
     */
    private function has_different_variation_prices($product) {
        if (!$product || !is_object($product) || !method_exists($product, 'get_available_variations')) {
            return false;
        }
        
        $variations = $product->get_available_variations();
        if (empty($variations)) {
            return false;
        }
        
        $price_data = $this->get_variation_price_data($variations);
        return !$price_data['same_prices'];
    }
    
    /**
     * Get variation price data and check if all have same prices
     */
    private function get_variation_price_data($variations) {
        $first_variation = reset($variations);
        $same_prices = true;
        $regular_price = $first_variation['display_regular_price'];
        $sale_price = $first_variation['display_price'];
        
        foreach ($variations as $variation) {
            if ($variation['display_regular_price'] != $regular_price || 
                $variation['display_price'] != $sale_price) {
                $same_prices = false;
                break;
            }
        }
        
        return array(
            'same_prices' => $same_prices,
            'regular_price' => $regular_price,
            'sale_price' => $sale_price
        );
    }
    
    /**
     * Render the discount information HTML
     */
    private function render_discount_info($discount_amount, $discount_percentage, $product = null) {
        $currency_symbol = get_woocommerce_currency_symbol();
        $formatted_amount = wc_price($discount_amount);
        
        $html = '<div class="wdd-discount-container">';
        $html .= '<div class="wdd-discount-info">';
        $html .= sprintf(
            __('Save: %s', 'woo-discount-display'),
            $formatted_amount
        );
        $html .= ' <span class="wdd-percentage">-' . $discount_percentage . '%</span>';
        $html .= '</div>';
        
        // Add countdown if enabled and product has sale end date
        if (WDD_ENABLE_SALE_COUNTDOWN && $product) {
            $sale_end_timestamp = $this->get_sale_end_timestamp($product);
            if ($sale_end_timestamp) {
                $html .= $this->render_countdown($sale_end_timestamp);
            }
        }
        
        $html .= '</div>';        
        return $html;
    }
    
    /**
     * Render the countdown HTML
     */
    private function render_countdown($end_timestamp) {
        $now = current_time('timestamp');
        $time_remaining = $end_timestamp - $now;
        $threshold_seconds = WDD_COUNTDOWN_THRESHOLD_HOURS * 3600;
        
        // Format the end date for display - only show date without time
        // WooCommerce stores sale end as end of day, so showing time can be confusing
        $date_format = get_option('date_format');
        $formatted_date = date_i18n($date_format, $end_timestamp);
        
        $html = '<div class="wdd-countdown-container" data-end-timestamp="' . esc_attr($end_timestamp) . '" data-threshold="' . esc_attr($threshold_seconds) . '">';
        
        if ($time_remaining <= 0) {
            // Sale has ended
            $html .= '<div class="wdd-countdown wdd-expired">';
            $html .= '<span class="wdd-countdown-label">' . esc_html__('Sale ended', 'woo-discount-display') . '</span>';
            $html .= '</div>';
        } elseif ($time_remaining <= $threshold_seconds) {
            // Less than threshold - show live countdown
            $html .= '<div class="wdd-countdown wdd-countdown-urgent">';
            $html .= '<span class="wdd-countdown-label">' . esc_html__('Sale ends in:', 'woo-discount-display') . '</span> ';
            $html .= '<span class="wdd-countdown-timer" data-end="' . esc_attr($end_timestamp) . '"></span>';
            $html .= '</div>';
        } else {
            // More than threshold - show static date
            $html .= '<div class="wdd-countdown wdd-countdown-static">';
            $html .= '<span class="wdd-countdown-label">' . esc_html__('Sale expires at:', 'woo-discount-display') . '</span> ';
            $html .= '<span class="wdd-countdown-date">' . esc_html($formatted_date) . '</span>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
}

// Initialize the plugin
new WooDiscountDisplay();

/**
 * Plugin activation hook
 */
register_activation_hook(__FILE__, function() {
    // Check if WooCommerce is active
    if (!class_exists('WooCommerce')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(__('WooCommerce Discount Display requires WooCommerce to be installed and active.', 'woo-discount-display'));
    }
});
