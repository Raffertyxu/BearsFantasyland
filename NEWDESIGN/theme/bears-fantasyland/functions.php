<?php
if (!defined('ABSPATH')) { exit; }

function bfft_asset($path) {
    return get_theme_file_uri(ltrim($path, '/'));
}

add_action('after_setup_theme', function () {
    add_theme_support('title-tag');
    add_theme_support('woocommerce');
    add_theme_support('wc-product-gallery-slider');
    add_theme_support('wc-product-gallery-lightbox');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', array('search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script'));
});

add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('bfft-style', bfft_asset('public/style.css'), array(), '1.3.48');
    wp_enqueue_style('bfft-interactions', bfft_asset('public/interactions.css'), array('bfft-style'), '1.3.48');
    wp_enqueue_script('bfft-site', bfft_asset('public/site.js'), array(), '1.3.48', true);
    if (function_exists('is_product') && is_product()) {
        wp_enqueue_style('bfft-product', bfft_asset('public/product.css'), array('bfft-interactions'), '1.3.48');
    }
}, 100);

// Gallery thumbnails sit in a five-column strip, wider than WooCommerce's 100px default.
add_filter('woocommerce_gallery_thumbnail_size', function () { return 'woocommerce_thumbnail'; });

// Hide the "SKU: N/A" line on product pages when neither the product nor any variation has a SKU.
add_filter('wc_product_sku_enabled', function ($enabled) {
    static $has_sku = null;
    if (!$enabled || is_admin() || !function_exists('is_product') || !is_product()) { return $enabled; }
    if ($has_sku === null) {
        $product = wc_get_product(get_queried_object_id());
        $has_sku = !$product || $product->get_sku() !== '';
        if (!$has_sku && $product->is_type('variable')) {
            foreach ($product->get_children() as $child_id) {
                $child = wc_get_product($child_id);
                if ($child && $child->get_sku('edit') !== '') { $has_sku = true; break; }
            }
        }
    }
    return $has_sku;
});

// The companion plugin owns content types, URLs, forms, and admin features.
// The theme owns only presentation. It does not process orders or payments.
if (function_exists('bfnd_pages') && !function_exists('bfnd_render_header')) {
    require_once __DIR__ . '/src/render.php';
}
