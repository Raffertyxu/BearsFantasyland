<?php
if (!defined('ABSPATH')) { exit; }

function bfft_asset($path) {
    return get_theme_file_uri(ltrim($path, '/'));
}

add_action('after_setup_theme', function () {
    add_theme_support('woocommerce');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', array('search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script'));
});

add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('bfft-style', bfft_asset('public/style.css'), array(), '1.2.3');
    wp_enqueue_script('bfft-site', bfft_asset('public/site.js'), array(), '1.2.3', true);
}, 100);

// The companion plugin owns content types, URLs, forms, and admin features.
// The theme owns only presentation. It does not process orders or payments.
if (function_exists('bfnd_pages') && !function_exists('bfnd_render_header')) {
    require_once __DIR__ . '/src/render.php';
}
