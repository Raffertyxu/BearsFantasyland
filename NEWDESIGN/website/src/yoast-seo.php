<?php
if (!defined('ABSPATH')) { exit; }

/**
 * Yoast normally sees only the one-line [bfnd_page] shortcode on these pages.
 * Add the saved public layout to its editor analysis without changing the page
 * body or the rendered frontend.
 */
function bfnd_yoast_analysis_content($post_id) {
    $key = get_post_meta($post_id, '_bfnd_page', true);
    if (!isset(bfnd_pages()[$key]) || !function_exists('bfnd_page_layout_html')) { return ''; }

    $was_public_render = !empty($GLOBALS['bfnd_public_content_render']);
    $GLOBALS['bfnd_public_content_render'] = true;
    try {
        $html = bfnd_page_layout_html($key, $post_id);
    } finally {
        if ($was_public_render) { $GLOBALS['bfnd_public_content_render'] = true; }
        else { unset($GLOBALS['bfnd_public_content_render']); }
    }

    $html = preg_replace('#<(script|style|svg)\b[^>]*>.*?</\1>#is', ' ', $html);
    return (string) apply_filters('bfnd_yoast_analysis_content', $html, $post_id, $key);
}

function bfnd_yoast_analysis_admin_assets($hook) {
    if (!defined('WPSEO_VERSION') || !in_array($hook, array('post.php', 'post-new.php'), true)) { return; }
    $post_id = isset($_GET['post']) ? absint($_GET['post']) : 0;
    if (!$post_id || !current_user_can('edit_post', $post_id)) { return; }
    $key = get_post_meta($post_id, '_bfnd_page', true);
    if (!isset(bfnd_pages()[$key])) { return; }

    wp_enqueue_script('bfnd-yoast-analysis', bfnd_asset('public/yoast-analysis.js'), array('jquery'), '0.5.8', true);
    wp_localize_script('bfnd-yoast-analysis', 'BFNDYoastPageContent', array(
        'content' => bfnd_yoast_analysis_content($post_id),
    ));
}

/** Preserve SEO values from the older custom page fields until they are moved
 * into Yoast's standard per-page fields in the Website Layout editor.
 */
function bfnd_yoast_legacy_title($title, $presentation = null) {
    if (!is_singular()) { return $title; }
    $post_id = get_queried_object_id();
    if (!$post_id || get_post_meta($post_id, '_yoast_wpseo_title', true)) { return $title; }
    $legacy = get_post_meta($post_id, '_bfnd_seo_title', true);
    return $legacy ? $legacy : $title;
}

function bfnd_yoast_legacy_description($description, $presentation = null) {
    if (!is_singular()) { return $description; }
    $post_id = get_queried_object_id();
    if (!$post_id || get_post_meta($post_id, '_yoast_wpseo_metadesc', true)) { return $description; }
    $legacy = get_post_meta($post_id, '_bfnd_seo_description', true);
    return $legacy ? $legacy : $description;
}

add_filter('wpseo_title', 'bfnd_yoast_legacy_title', 20, 2);
add_filter('wpseo_metadesc', 'bfnd_yoast_legacy_description', 20, 2);
