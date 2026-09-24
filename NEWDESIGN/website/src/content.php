<?php
if (!defined('ABSPATH')) { exit; }

function bfnd_manifest() {
    static $data = null;
    if ($data === null) {
        $data = json_decode(file_get_contents(BFND_DIR . 'data/content.json'), true);
    }
    return $data;
}

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

function bfnd_migrate_page_slugs() {
    if (get_option('bfnd_clean_slugs_v1')) { return; }
    bfnd_create_preview_pages();
    $complete = true;
    foreach (bfnd_pages() as $key => $info) {
        $page = bfnd_page_post($key);
        if (!$page) { $complete = false; continue; }
        if ($page->post_name === $info[1]) { continue; }
        $conflict = get_page_by_path($info[1]);
        if ($conflict && (int) $conflict->ID !== (int) $page->ID) { $complete = false; continue; }
        $updated = wp_update_post(array('ID' => $page->ID, 'post_name' => $info[1]), true);
        if (is_wp_error($updated)) { $complete = false; }
    }
    if ($complete) {
        update_option('bfnd_clean_slugs_v1', '1');
        flush_rewrite_rules();
    }
}

function bfnd_redirect_legacy_pages() {
    $legacy = array(
        'newdesign-preview' => 'home', 'newdesign-furniture' => 'furniture',
        'newdesign-lifestyle' => 'lifestyle', 'newdesign-school' => 'school',
        'newdesign-story' => 'story', 'newdesign-collaboration' => 'collaboration',
        'newdesign-journal' => 'journal', 'newdesign-service' => 'service',
        'tools' => 'shop', 'contact' => 'collaboration', 'faq' => 'service',
        'news' => 'journal', 'masters' => 'story', 'bearnews' => 'journal',
        '聯絡我們' => 'collaboration', 'custom-delivery' => 'service',
        'about' => 'story', 'youtube' => 'school', 'school' => 'school',
        '首頁' => 'home',
    );
    $anchors = array('contact' => 'inquiry', '聯絡我們' => 'inquiry', 'faq' => 'faq');
    $path = rawurldecode(trim((string) wp_parse_url(isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '', PHP_URL_PATH), '/'));
    if (isset($legacy[$path])) {
        $url = $legacy[$path] === 'shop' ? bfnd_shop_url() : bfnd_page_url($legacy[$path]);
        if (!empty($_SERVER['QUERY_STRING'])) { $url .= (strpos($url, '?') === false ? '?' : '&') . wp_unslash($_SERVER['QUERY_STRING']); }
        if (isset($anchors[$path])) { $url .= '#' . $anchors[$path]; }
        wp_safe_redirect($url, 301);
        exit;
    }
    $archives = array('bf_work' => 'furniture', 'bf_lifestyle' => 'lifestyle', 'bf_course' => 'school');
    foreach ($archives as $type => $key) {
        if (is_post_type_archive($type)) { wp_safe_redirect(bfnd_page_url($key), 301); exit; }
    }
    if (is_tax(array('bf_work_cat', 'bf_series'))) { wp_safe_redirect(bfnd_page_url('furniture'), 301); exit; }
}

function bfnd_media_src($path) {
    if (!$path) { return ''; }
    if (is_numeric($path)) { return wp_get_attachment_image_url((int) $path, 'full') ?: ''; }
    if (preg_match('#^https?://#', $path)) { return esc_url_raw($path); }
    return bfnd_asset($path);
}

function bfnd_work_image($post_id, $size = 'large') {
    $url = get_the_post_thumbnail_url($post_id, $size);
    if ($url) { return $url; }
    return bfnd_media_src(get_post_meta($post_id, '_bfnd_image', true));
}

function bfnd_gallery($post_id) {
    $ids = get_post_meta($post_id, '_bfnd_gallery_ids', true);
    if (get_post_meta($post_id, '_bfnd_gallery_override', true)) {
        return is_array($ids) ? array_values(array_filter(array_map(function ($id) { return wp_get_attachment_image_url((int) $id, 'large'); }, $ids))) : array();
    }
    if (is_array($ids) && $ids) {
        return array_values(array_filter(array_map(function ($id) { return wp_get_attachment_image_url((int) $id, 'large'); }, $ids)));
    }
    $sources = get_post_meta($post_id, '_bfnd_gallery', true);
    return is_array($sources) ? array_map('bfnd_media_src', $sources) : array();
}

function bfnd_work_query($args = array()) {
    return new WP_Query(array_merge(array(
        'post_type' => 'bf_work', 'post_status' => current_user_can('edit_posts') ? array('publish', 'private') : 'publish',
        'posts_per_page' => -1,
    ), $args));
}
