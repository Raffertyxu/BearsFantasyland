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
