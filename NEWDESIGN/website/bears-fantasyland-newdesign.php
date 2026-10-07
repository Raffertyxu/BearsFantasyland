<?php
/**
 * Plugin Name: 飛熊入夢 NEWDESIGN 官網
 * Description: 飛熊入夢品牌頁、家具作品、生活木作、木作學堂、日誌與詢問管理。
 * Version: 0.5.43
 * Author: Haotai Maker
 * Text Domain: bf-newdesign
 */
if (!defined('ABSPATH')) { exit; }

define('BFND_DIR', plugin_dir_path(__FILE__));
define('BFND_URL', plugin_dir_url(__FILE__));
define('BFND_VERSION', '0.5.43');
require_once BFND_DIR . 'src/hardening.php';
require_once BFND_DIR . 'src/content.php';
require_once BFND_DIR . 'src/work-options.php';
require_once BFND_DIR . 'src/course-commerce.php';
require_once BFND_DIR . 'src/store-operations.php';
require_once BFND_DIR . 'src/admin.php';
require_once BFND_DIR . 'src/page-editor.php';
require_once BFND_DIR . 'src/shortcode-pages.php';
require_once BFND_DIR . 'src/template-admin.php';
require_once BFND_DIR . 'src/yoast-seo.php';

add_action('init', 'bfnd_register_types');
add_action('add_meta_boxes', 'bfnd_meta_boxes');
add_action('add_meta_boxes_page', function () {
    // Page copy and images live in the main block editor. Yoast remains
    // available from its block-editor sidebar instead of the lower meta pane.
    remove_meta_box('wpseo_meta', 'page', 'normal');
    remove_meta_box('postcustom', 'page', 'normal');
}, 1000);
add_action('save_post', 'bfnd_save_meta');
add_action('admin_post_nopriv_bfnd_inquiry', 'bfnd_inquiry_action');
add_action('admin_post_bfnd_inquiry', 'bfnd_inquiry_action');
add_action('wp_enqueue_scripts', 'bfnd_enqueue_work_options', 105);
add_action('admin_enqueue_scripts', 'bfnd_admin_enqueue');
add_action('admin_enqueue_scripts', 'bfnd_yoast_analysis_admin_assets', 20);
add_action('enqueue_block_editor_assets', function () {
    $screen = get_current_screen();
    if ($screen && $screen->post_type === 'page') {
        wp_enqueue_style('bfnd-block-editor', bfnd_asset('public/block-editor.css'), array(), '0.5.1');
    }
});
add_action('template_redirect', 'bfnd_redirect_legacy_pages', 0);
add_filter('woocommerce_return_to_shop_redirect', 'bfnd_return_to_shop');

register_activation_hook(__FILE__, function () {
    bfnd_register_types();
    bfnd_create_preview_pages();
    flush_rewrite_rules();
});
register_deactivation_hook(__FILE__, 'flush_rewrite_rules');

function bfnd_asset($path) {
    return BFND_URL . ltrim($path, '/');
}

function bfnd_custom_theme_active() {
    return get_stylesheet() === 'bears-fantasyland';
}

/** The public pages are rendered by the 飛熊入夢 theme; the plugin no longer ships a fallback renderer. */
function bfnd_missing_theme_notice() {
    if (bfnd_custom_theme_active() || !current_user_can('switch_themes')) { return; }
    echo '<div class="notice notice-error"><p>「飛熊入夢 NEWDESIGN 官網」外掛需要搭配「飛熊入夢」佈景主題，前台頁面才會正常顯示。請到「外觀 → 佈景主題」啟用。</p></div>';
}
add_action('admin_notices', 'bfnd_missing_theme_notice');

function bfnd_enqueue_work_options() {
    $is_collaboration_page = is_page() && (
        get_post_meta(get_queried_object_id(), '_bfnd_page', true) === 'collaboration'
        || is_page('collaboration')
    );
    if (!is_singular('bf_work') && !$is_collaboration_page) { return; }
    wp_enqueue_style('bfnd-work-options', bfnd_asset('public/work-options.css'), array(), BFND_VERSION);
    wp_enqueue_script('bfnd-work-options', bfnd_asset('public/work-options.js'), array(), BFND_VERSION, true);
}

function bfnd_is_commerce_page() {
    if (!class_exists('WooCommerce')) { return false; }
    return is_page(array('woodshop', 'shop'))
        || (function_exists('is_cart') && is_cart())
        || (function_exists('is_checkout') && is_checkout())
        || (function_exists('is_account_page') && is_account_page())
        || (function_exists('is_shop') && is_shop())
        || (function_exists('is_product') && is_product())
        || (function_exists('is_product_category') && is_product_category())
        || (function_exists('is_product_tag') && is_product_tag());
}

function bfnd_is_journal_article() {
    return is_singular('post');
}

function bfnd_is_site_page() {
    if (bfnd_is_commerce_page() || bfnd_is_journal_article() || is_404()) { return true; }
    if (is_singular(array('bf_work', 'bf_course', 'bf_lifestyle'))) { return true; }
    return is_page() && (bool) get_post_meta(get_queried_object_id(), '_bfnd_page', true);
}

function bfnd_shop_url() {
    $page = get_page_by_path('woodshop');
    return $page ? get_permalink($page) : (function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : bfnd_page_url('furniture'));
}

function bfnd_return_to_shop($url) {
    return bfnd_shop_url();
}
