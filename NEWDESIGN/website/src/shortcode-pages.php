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

add_shortcode('bfnd_page', 'bfnd_page_shortcode');
add_shortcode('bfnd_show_works', function () { return ''; });
add_filter('render_block', 'bfnd_hide_editor_data_blocks', 10, 2);
