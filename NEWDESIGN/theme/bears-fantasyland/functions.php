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
    wp_enqueue_style('bfft-style', bfft_asset('public/style.css'), array(), '1.3.51');
    wp_enqueue_style('bfft-interactions', bfft_asset('public/interactions.css'), array('bfft-style'), '1.3.51');
    wp_enqueue_script('bfft-site', bfft_asset('public/site.js'), array(), '1.3.51', true);
    if (function_exists('is_product') && is_product()) {
        wp_enqueue_style('bfft-product', bfft_asset('public/product.css'), array('bfft-interactions'), '1.3.51');
    }
}, 100);

// Header and footer content is editable with WordPress's own tools: 外觀 → 選單 for links,
// 外觀 → 自訂 → 飛熊入夢頁首頁尾 for footer text and social URLs. Empty settings keep the built-in defaults.
add_action('after_setup_theme', function () {
    register_nav_menus(array(
        'bf_primary' => '頁首主選單',
        'bf_mobile_secondary' => '手機選單次要連結',
        'bf_footer' => '頁尾連結',
        'bf_legal' => '頁尾政策連結',
    ));
});

function bfft_footer_defaults() {
    return array(
        'bf_footer_motto' => '木，讓生活更美好。',
        'bf_footer_maker_title' => '台中 Maker 工藝基地',
        'bf_footer_maker_text' => '木作設計・木工教育・實木家具',
        'bf_footer_copyright' => '飛熊入夢 Bear’s Fantasyland. All rights reserved.',
        'bf_social_instagram' => 'https://www.instagram.com/bearloveearth/',
        'bf_social_facebook' => 'https://www.facebook.com/iaz2765b',
        'bf_social_youtube' => 'https://www.youtube.com/@%E9%A3%9B%E7%86%8A%E5%85%A5%E5%A4%A2',
        'bf_social_line' => 'https://line.me/R/ti/p/%40iaz2765b',
    );
}

function bfft_footer_setting($key) {
    $defaults = bfft_footer_defaults();
    $value = trim((string) get_theme_mod($key, ''));
    return $value !== '' ? $value : ($defaults[$key] ?? '');
}

add_action('customize_register', function ($wp_customize) {
    $wp_customize->add_section('bf_header_footer', array('title' => '飛熊入夢頁首頁尾', 'priority' => 30, 'description' => '選單連結請到「外觀 → 選單」設定（頁首主選單、手機選單次要連結、頁尾連結、頁尾政策連結）。這裡留空會使用原本的內容。'));
    $fields = array(
        'bf_footer_motto' => array('頁尾標語', 'text'),
        'bf_footer_maker_title' => array('頁尾右側標題', 'text'),
        'bf_footer_maker_text' => array('頁尾右側說明', 'text'),
        'bf_footer_copyright' => array('版權文字（年份會自動加在前面）', 'text'),
        'bf_social_instagram' => array('Instagram 網址', 'url'),
        'bf_social_facebook' => array('Facebook 網址', 'url'),
        'bf_social_youtube' => array('YouTube 網址', 'url'),
        'bf_social_line' => array('官方 LINE 網址', 'url'),
    );
    $defaults = bfft_footer_defaults();
    foreach ($fields as $key => $field) {
        $wp_customize->add_setting($key, array('default' => '', 'sanitize_callback' => $field[1] === 'url' ? 'esc_url_raw' : 'sanitize_text_field'));
        $wp_customize->add_control($key, array('label' => $field[0], 'section' => 'bf_header_footer', 'type' => $field[1], 'input_attrs' => array('placeholder' => $defaults[$key])));
    }
});

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
