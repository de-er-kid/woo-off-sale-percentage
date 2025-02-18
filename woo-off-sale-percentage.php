<?php
/**
 * Plugin Name: WooCommerce Off-Sale Percentage Data
 * Plugin URI: https://github.com/de-er-kid/woo-off-sale-percentage
 * Description: Replace the 'sale' badge in single product page to product's off price percentage.
 * Version: 1.0.1
 * Author: Sinan
 * Author URI: https://github.com/de-er-kid
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * GitHub Plugin URI: de-er-kid/woo-off-sale-percentage
 **/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Display the WooCommerce Discount Percentage on the Sale Badge for variable products and single products
add_filter( 'woocommerce_sale_flash', 'display_percentage_on_sale_badge', 20, 3 );
function display_percentage_on_sale_badge( $html, $post, $product ) {
    if ( ! is_a( $product, 'WC_Product' ) ) {
        $product = wc_get_product( $post->ID );
    }

    if ( ! $product || ! $product->is_on_sale() ) {
        return $html;
    }

    if ( $product->is_type('variable') ) {
        $percentages = array();
        $prices = $product->get_variation_prices();

        foreach( $prices['price'] as $key => $price ) {
            if( $prices['regular_price'][$key] !== $price ) {
                $percentages[] = round( 100 - ( floatval($prices['sale_price'][$key]) / floatval($prices['regular_price'][$key]) * 100 ) );
            }
        }
        $percentage = max($percentages) . '%';
    } elseif ( $product->is_type('grouped') ) {
        $percentages = array();
        $children_ids = $product->get_children();

        foreach( $children_ids as $child_id ) {
            $child_product = wc_get_product($child_id);

            if ( $child_product ) {
                $regular_price = (float) $child_product->get_regular_price();
                $sale_price    = (float) $child_product->get_sale_price();

                if ( $sale_price > 0 ) {
                    $percentages[] = round(100 - ($sale_price / $regular_price * 100));
                }
            }
        }
        $percentage = max($percentages) . '%';
    } else {
        $regular_price = (float) $product->get_regular_price();
        $sale_price    = (float) $product->get_sale_price();

        if ( $sale_price > 0 ) {
            $percentage = round(100 - ($sale_price / $regular_price * 100)) . '%';
        } else {
            return $html;
        }
    }

    return '<span class="onsale">' . esc_html__( 'Save', 'woocommerce' ) . ' ' . esc_html($percentage) . '</span>'; 
}

// New: Add a separate function for shortcode to avoid parameter mismatch
function onsale_percentage_shortcode() {
    global $post;
    $product = wc_get_product( $post->ID );
    if ( $product ) {
        return display_percentage_on_sale_badge( '', $post, $product );
    }
    return '';
}
add_shortcode('onsale_percentage_html', 'onsale_percentage_shortcode');
