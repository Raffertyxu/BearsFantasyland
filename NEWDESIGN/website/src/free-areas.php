<?php
if (!defined('ABSPATH')) { exit; }

// 自由內容區: an optional block-editor area at the end of selected pages (before the footer).
// Stored as native synced patterns (wp_block) so editors use the normal block editor; the fixed
// 網站版面 sections are untouched, so their position-keyed overrides cannot shift.

function bfnd_free_area_keys() {
    return array('home' => '首頁', 'lifestyle' => '生活木作', 'school' => '木作學堂', 'service' => '購買與服務');
}

function bfnd_free_area_post($key) {
    if (!isset(bfnd_free_area_keys()[$key])) { return null; }
    $found = get_posts(array(
        'post_type' => 'wp_block', 'post_status' => array('publish', 'draft', 'private'), 'numberposts' => 1,
        'meta_key' => '_bfnd_free_area', 'meta_value' => $key, 'suppress_filters' => false,
    ));
    return $found ? $found[0] : null;
}

function bfnd_free_area_edit_url($key) {
    $post = bfnd_free_area_post($key);
    return $post ? admin_url('post.php?post=' . $post->ID . '&action=edit') : '';
}

// Create the four areas once, empty, so the edit buttons always have somewhere to go.
add_action('admin_init', function () {
    if (!current_user_can('edit_pages') || get_option('bfnd_free_areas_v1') === '1') { return; }
    foreach (bfnd_free_area_keys() as $key => $label) {
        if (bfnd_free_area_post($key)) { continue; }
        $id = wp_insert_post(array('post_type' => 'wp_block', 'post_status' => 'publish', 'post_title' => $label . '｜自由內容區（頁尾前）', 'post_content' => ''), true);
        if ($id && !is_wp_error($id)) { update_post_meta($id, '_bfnd_free_area', $key); }
    }
    update_option('bfnd_free_areas_v1', '1', false);
});

function bfnd_free_area_admin_box($key) {
    $url = bfnd_free_area_edit_url($key);
    if (!$url) { return; }
    $label = bfnd_free_area_keys()[$key];
    echo '<div class="notice notice-info inline"><p><strong>想在這頁多加一段內容（公告、說明、圖片、按鈕）？</strong>用<strong>自由內容區</strong>：它顯示在「' . esc_html($label) . '」頁最下方、頁尾之前，沒有內容就不顯示。上面的固定區塊照設計稿，只能改文字圖片或隱藏。</p><p><a class="button button-primary" href="' . esc_url($url) . '">編輯自由內容區</a></p></div>';
}
