<?php
if (!defined('ABSPATH')) { exit; }

/**
 * Compatibility reader for the previously deployed block-based editor.
 * Existing blocks are copied into the dedicated template settings during
 * migration; live pages then contain only their layout shortcode.
 */
function bfnd_design_block_values($post_id) {
    $content = get_post_field('post_content', $post_id);
    if (!$content || !has_shortcode($content, 'bfnd_page')) { return null; }
    $values = array('text' => array(), 'image' => array(), 'image_url' => array(), '_sections_present' => array());
    bfnd_design_walk_blocks(parse_blocks($content), $values);
    return $values;
}

function bfnd_design_walk_blocks($blocks, &$values) {
    foreach ($blocks as $block) {
        $class = isset($block['attrs']['className']) ? $block['attrs']['className'] : '';
        if (preg_match('/(?:^|\s)bfnd-editor-section-(section_\d+)(?:\s|$)/', $class, $match)) {
            $values['_sections_present'][] = $match[1];
        }
        if (preg_match('/(?:^|\s)bfnd-field-(section_\d+_(?:text|image)_\d+)(?:\s|$)/', $class, $match)) {
            $key = $match[1];
            if ($block['blockName'] === 'core/image') {
                $id = isset($block['attrs']['id']) ? absint($block['attrs']['id']) : 0;
                if ($id && wp_attachment_is_image($id)) { $values['image'][$key] = $id; }
                elseif (preg_match('/<img\b[^>]*\bsrc=["\x27]([^"\x27]+)["\x27]/i', $block['innerHTML'], $image)) {
                    $values['image_url'][$key] = esc_url_raw(html_entity_decode($image[1], ENT_QUOTES, 'UTF-8'));
                }
            } elseif (in_array($block['blockName'], array('core/paragraph', 'core/heading'), true)) {
                $html = trim($block['innerHTML']);
                if (preg_match('~^<(?:p|h[1-6])\b[^>]*>(.*)</(?:p|h[1-6])>$~si', $html, $text)) {
                    $values['text'][$key] = bfnd_page_design_safe_inline($text[1]);
                }
            }
        }
        if (!empty($block['innerBlocks'])) { bfnd_design_walk_blocks($block['innerBlocks'], $values); }
    }
}

function bfnd_page_shortcode($atts = array()) {
    if (!is_page()) { return ''; }
    $id = get_queried_object_id();
    $key = get_post_meta($id, '_bfnd_page', true);
    if (!$key || !bfnd_page_layout_renderer($key)) { return ''; }
    $atts = shortcode_atts(array('key' => $key), $atts, 'bfnd_page');
    if ($atts['key'] !== $key) { return ''; }
    return bfnd_page_layout_html($key, $id);
}

function bfnd_hide_editor_data_blocks($output, $block) {
    if (!empty($block['attrs']['className']) && preg_match('/(?:^|\s)bfnd-editor-fields(?:\s|$)/', $block['attrs']['className'])) { return ''; }
    return $output;
}

function bfnd_design_block($name, $class, $html, $attrs = array()) {
    $attrs['className'] = $class;
    return '<!-- wp:' . $name . ' ' . wp_json_encode($attrs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ' -->' . $html . '<!-- /wp:' . $name . ' -->' . "\n";
}

function bfnd_design_group_open($class) {
    return '<!-- wp:group {"className":"' . esc_attr($class) . '"} --><div class="wp-block-group ' . esc_attr($class) . '">' . "\n";
}

function bfnd_design_group_close() { return '</div><!-- /wp:group -->' . "\n"; }

function bfnd_build_page_blocks($post) {
    $key = get_post_meta($post->ID, '_bfnd_page', true);
    $sections = array();
    bfnd_page_design_document(bfnd_page_layout_html($key), $post->ID, false, $sections);
    $old = get_post_meta($post->ID, '_bfnd_page_design', true);
    $old = is_array($old) ? $old : array();
    $out = '<!-- wp:shortcode -->[bfnd_page key="' . esc_attr($key) . '"]<!-- /wp:shortcode -->' . "\n";
    if ($key === 'lifestyle' && get_post_meta($post->ID, '_bfnd_show_lifestyle_works', true) === '1') {
        $out .= '<!-- wp:shortcode -->[bfnd_show_works]<!-- /wp:shortcode -->' . "\n";
    }
    $out .= bfnd_design_group_open('bfnd-editor-fields');
    foreach ($sections as $section_key => $section) {
        if (!empty($old['hidden'][$section_key])) { continue; }
        $out .= bfnd_design_group_open('bfnd-editor-section-' . $section_key);
        $out .= bfnd_design_block('heading', 'bfnd-editor-section-title', '<h2 class="bfnd-editor-section-title">' . esc_html($section['title']) . '</h2>', array('level' => 2));
        foreach ($section['fields'] as $field_key => $field) {
            $out .= bfnd_design_block('paragraph', 'bfnd-editor-label', '<p class="bfnd-editor-label">' . esc_html($field['label']) . '</p>');
            if ($field['type'] === 'image') {
                $id = !empty($old['image'][$field_key]) ? absint($old['image'][$field_key]) : 0;
                $src = $id ? wp_get_attachment_image_url($id, 'full') : $field['default'];
                $attributes = array('sizeSlug' => 'full');
                if ($id) { $attributes['id'] = $id; }
                $class = 'bfnd-field-' . $field_key;
                $image_html = '<figure class="wp-block-image size-full ' . esc_attr($class) . '"><img src="' . esc_url($src) . '" alt="' . esc_attr($field['label']) . '"' . ($id ? ' class="wp-image-' . $id . '"' : '') . '/></figure>';
                $out .= bfnd_design_block('image', $class, $image_html, $attributes);
            } else {
                $value = isset($old['text'][$field_key]) ? $old['text'][$field_key] : $field['default'];
                $class = 'bfnd-field-' . $field_key;
                $out .= bfnd_design_block('paragraph', $class, '<p class="' . esc_attr($class) . '">' . bfnd_page_design_safe_inline($value) . '</p>');
            }
        }
        $out .= bfnd_design_group_close();
    }
    $out .= bfnd_design_group_close();
    if (trim($post->post_content)) { $out .= "\n" . $post->post_content; }
    return $out;
}

function bfnd_shortcode_migration_page() {
    if (!current_user_can('manage_options')) { return; }
    echo '<div class="wrap"><h1>飛熊入夢頁面編輯方式</h1><p>版型使用 [bfnd_page] 短代碼；文字與圖片使用 WordPress 原生區塊。轉換前的頁面內容會留存備份，方便還原。</p>';
    if (isset($_GET['bfnd_migrated'])) { echo '<div class="notice notice-success"><p>轉換完成：' . absint($_GET['bfnd_migrated']) . ' 個頁面。</p></div>'; }
    if (isset($_GET['bfnd_restored'])) { echo '<div class="notice notice-success"><p>已還原：' . absint($_GET['bfnd_restored']) . ' 個頁面。</p></div>'; }
    echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="bfnd_migrate_shortcode_pages">';
    wp_nonce_field('bfnd_migrate_shortcode_pages');
    echo '<p><label>先轉換單頁測試 <select name="page_key"><option value="">全部尚未轉換的頁面</option>';
    foreach (bfnd_pages() as $key => $info) { echo '<option value="' . esc_attr($key) . '">' . esc_html($info[0]) . '</option>'; }
    echo '</select></label></p>';
    submit_button('將新版頁面轉為短代碼與原生區塊');
    echo '</form><form method="post" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="bfnd_restore_shortcode_pages">';
    wp_nonce_field('bfnd_restore_shortcode_pages');
    submit_button('還原轉換前的頁面內容', 'secondary');
    echo '</form></div>';
}

function bfnd_migrate_shortcode_pages_action() {
    if (!current_user_can('manage_options')) { wp_die('權限不足'); }
    check_admin_referer('bfnd_migrate_shortcode_pages');
    $selected = isset($_POST['page_key']) ? sanitize_key(wp_unslash($_POST['page_key'])) : '';
    $count = 0;
    foreach (bfnd_pages() as $key => $info) {
        if ($selected && $key !== $selected) { continue; }
        $page = bfnd_page_post($key);
        if (!$page || has_shortcode($page->post_content, 'bfnd_page')) { continue; }
        update_post_meta($page->ID, '_bfnd_content_before_shortcode', $page->post_content);
        $blocks = bfnd_build_page_blocks($page);
        $result = wp_update_post(wp_slash(array('ID' => $page->ID, 'post_content' => $blocks)), true);
        if (!is_wp_error($result)) { $count++; }
    }
    wp_safe_redirect(add_query_arg('bfnd_migrated', $count, admin_url('tools.php?page=bfnd-shortcode-pages')));
    exit;
}

add_shortcode('bfnd_page', 'bfnd_page_shortcode');
add_shortcode('bfnd_show_works', function () { return ''; });
add_filter('render_block', 'bfnd_hide_editor_data_blocks', 10, 2);
