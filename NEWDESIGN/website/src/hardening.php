<?php
if (!defined('ABSPATH')) { exit; }

// XML-RPC is unused here: Jetpack is not installed, and the block editor, WooCommerce
// and its apps use the REST API. Left on, system.multicall allows bulk password
// guessing and pingback.ping can be abused against other sites (SECURITY-AUDIT-2026-10-06.md).
if (defined('XMLRPC_REQUEST') && XMLRPC_REQUEST) {
    add_action('plugins_loaded', function () {
        status_header(403);
        nocache_headers();
        header('Content-Type: text/plain; charset=utf-8');
        exit('XML-RPC is disabled on this site.');
    }, 0);
}
add_filter('xmlrpc_enabled', '__return_false');
add_filter('xmlrpc_methods', function () { return array(); }, PHP_INT_MAX);
add_filter('pings_open', '__return_false', PHP_INT_MAX);
add_filter('wp_headers', function ($headers) {
    unset($headers['X-Pingback']);
    return $headers;
});
remove_action('wp_head', 'rsd_link');

// Comments and product reviews are off: the journal never shows comments, the shop had no reviews,
// and visitors use the inquiry form instead. Turn them back on by removing this block.
add_filter('comments_open', '__return_false', PHP_INT_MAX);
add_filter('comments_array', '__return_empty_array', PHP_INT_MAX);
add_filter('pre_option_woocommerce_enable_reviews', function () { return 'no'; });
add_action('init', function () {
    foreach (array('post', 'page', 'product', 'attachment') as $type) {
        if (post_type_supports($type, 'comments')) { remove_post_type_support($type, 'comments'); }
        if (post_type_supports($type, 'trackbacks')) { remove_post_type_support($type, 'trackbacks'); }
    }
}, 100);
add_action('admin_menu', function () { remove_menu_page('edit-comments.php'); }, 100);
add_action('admin_bar_menu', function ($bar) { $bar->remove_node('comments'); }, 100);
add_action('wp_dashboard_setup', function () { remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal'); }, 100);
