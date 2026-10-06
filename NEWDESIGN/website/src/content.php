<?php
if (!defined('ABSPATH')) { exit; }

function bfnd_pages() {
    return array(
        'home' => array('飛熊入夢', 'home'),
        'furniture' => array('家具作品', 'furniture'),
        'lifestyle' => array('生活木作', 'lifestyle'),
        'school' => array('木作學堂', 'woodworking-school'),
        'story' => array('品牌故事', 'brand-story'),
        'collaboration' => array('合作提案', 'collaboration'),
        'journal' => array('飛熊日誌', 'journal'),
        'service' => array('購買與服務', 'service'),
    );
}

function bfnd_page_post($key) {
    $pages = bfnd_pages();
    if (!isset($pages[$key])) { return null; }
    $found = get_posts(array(
        'post_type' => 'page', 'post_status' => 'any', 'numberposts' => 1,
        'meta_key' => '_bfnd_page', 'meta_value' => $key,
    ));
    return $found ? $found[0] : null;
}

function bfnd_create_preview_pages() {
    foreach (bfnd_pages() as $key => $info) {
        if (bfnd_page_post($key) || get_page_by_path($info[1])) { continue; }
        wp_insert_post(array(
            'post_type' => 'page', 'post_status' => 'private', 'post_title' => $info[0],
            'post_name' => $info[1], 'post_content' => '',
            'meta_input' => array('_bfnd_page' => $key),
        ));
    }
}

function bfnd_page_url($key) {
    $page = bfnd_page_post($key);
    return $page ? get_permalink($page) : home_url('/');
}

function bfnd_redirect_legacy_pages() {
    $archives = array('bf_work' => 'furniture', 'bf_lifestyle' => 'lifestyle', 'bf_course' => 'school');
    foreach ($archives as $type => $key) {
        if (is_post_type_archive($type)) { wp_safe_redirect(bfnd_page_url($key), 301); exit; }
    }
    if (is_tax(array('bf_work_cat', 'bf_series'))) { wp_safe_redirect(bfnd_page_url('furniture'), 301); exit; }
}

function bfnd_nonfinal_photo($url, $attachment_id = 0, $alt = '') {
    if ($attachment_id) {
        $source = (string) get_post_meta($attachment_id, '_bfnd_source', true);
        if ($alt === '') { $alt = (string) get_post_meta($attachment_id, '_wp_attachment_image_alt', true); }
    } else { $source = ''; }
    return stripos((string) $url . ' ' . $source, 'editorial') !== false
        || stripos((string) $url . ' ' . $source, 'wooden-cup') !== false
        || strpos((string) $alt, "\xe7\xa4\xba\xe6\x84\x8f") !== false;
}

function bfnd_filter_nonfinal_attachment_html($html, $attachment_id, $size = 'thumbnail', $icon = false, $attr = array()) {
    $url = wp_get_attachment_url((int) $attachment_id);
    $alt = (string) get_post_meta((int) $attachment_id, '_wp_attachment_image_alt', true);
    return bfnd_nonfinal_photo($url, (int) $attachment_id, $alt) ? '' : $html;
}
add_filter('wp_get_attachment_image', 'bfnd_filter_nonfinal_attachment_html', 10, 5);

function bfnd_filter_nonfinal_inline_images($content) {
    if (!is_string($content) || stripos($content, '<img') === false) { return $content; }
    return preg_replace_callback('/<img\\b[^>]*>/i', function ($match) {
        $tag = $match[0];
        preg_match('/\\bsrc=["\\\']([^"\\\']+)["\\\']/i', $tag, $src);
        preg_match('/\\balt=["\\\']([^"\\\']*)["\\\']/i', $tag, $alt);
        preg_match('/\\bwp-image-(\\d+)\\b/', $tag, $attachment);
        $url = html_entity_decode($src[1] ?? '', ENT_QUOTES, 'UTF-8');
        $image_alt = html_entity_decode($alt[1] ?? '', ENT_QUOTES, 'UTF-8');
        return bfnd_nonfinal_photo($url, (int) ($attachment[1] ?? 0), $image_alt) ? '' : $tag;
    }, $content);
}
add_filter('the_content', 'bfnd_filter_nonfinal_inline_images', 20);
add_filter('woocommerce_short_description', 'bfnd_filter_nonfinal_inline_images', 20);

function bfnd_media_src($path) {
    if (!$path) { return ''; }
    if (is_numeric($path)) {
        $id = (int) $path;
        $url = wp_get_attachment_image_url($id, 'full') ?: '';
        return bfnd_nonfinal_photo($url, $id) ? '' : $url;
    }
    if (preg_match('#^https?://#', $path)) { $url = esc_url_raw($path); }
    // Legacy relative paths pointed at the import assets, which are no longer packaged.
    elseif (is_file(BFND_DIR . ltrim($path, '/'))) { $url = bfnd_asset($path); }
    else { return ''; }
    return bfnd_nonfinal_photo($url) ? '' : $url;
}

function bfnd_work_image($post_id, $size = 'large') {
    if (get_post_type($post_id) === 'bf_course') {
        if (!function_exists('get_post_thumbnail_id')) { return ''; }
        $attachment_id = (int) get_post_thumbnail_id($post_id);
        if (!$attachment_id) { return ''; }
        $url = wp_get_attachment_image_url($attachment_id, $size) ?: '';
        return bfnd_nonfinal_photo($url, $attachment_id) ? '' : $url;
    }
    $url = get_the_post_thumbnail_url($post_id, $size);
    if ($url) {
        $attachment_id = function_exists('get_post_thumbnail_id') ? (int) get_post_thumbnail_id($post_id) : 0;
        return bfnd_nonfinal_photo($url, $attachment_id) ? '' : $url;
    }
    return bfnd_media_src(get_post_meta($post_id, '_bfnd_image', true));
}

function bfnd_gallery($post_id) {
    $ids = get_post_meta($post_id, '_bfnd_gallery_ids', true);
    $has_override = get_post_meta($post_id, '_bfnd_gallery_override', true);
    if (get_post_type($post_id) === 'bf_course' && !$has_override) { return array(); }
    if ($has_override) {
        return is_array($ids) ? array_values(array_filter(array_map(function ($id) { return bfnd_media_src($id); }, $ids))) : array();
    }
    if (is_array($ids) && $ids) {
        return array_values(array_filter(array_map(function ($id) { return bfnd_media_src($id); }, $ids)));
    }
    $sources = get_post_meta($post_id, '_bfnd_gallery', true);
    return is_array($sources) ? array_map('bfnd_media_src', $sources) : array();
}

function bfnd_work_query($args = array()) {
    return new WP_Query(array_merge(array(
        'post_type' => 'bf_work', 'post_status' => empty($GLOBALS['bfnd_public_content_render']) && current_user_can('read_private_posts') ? array('publish', 'private') : 'publish',
        'posts_per_page' => -1,
    ), $args));
}
