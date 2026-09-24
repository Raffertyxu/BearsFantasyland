<?php
if (!defined('ABSPATH')) { exit; }

/**
 * The fixed client layouts live in the theme. Copy and photographs are stored
 * in the dedicated WordPress admin template settings and Media Library.
 * Legacy page blocks and post meta remain readable until migration.
 */
function bfnd_page_layout_renderer($key) {
    $renderers = array(
        'home' => 'bfnd_render_home', 'furniture' => 'bfnd_render_furniture',
        'lifestyle' => 'bfnd_render_lifestyle', 'school' => 'bfnd_render_school',
        'story' => 'bfnd_render_story', 'collaboration' => 'bfnd_render_collaboration',
        'journal' => 'bfnd_render_journal', 'service' => 'bfnd_render_service',
    );
    return isset($renderers[$key]) && function_exists($renderers[$key]) ? $renderers[$key] : '';
}

function bfnd_page_layout_html($key, $post_id = 0) {
    $renderer = bfnd_page_layout_renderer($key);
    if (!$renderer) { return ''; }
    ob_start();
    $renderer();
    $html = ob_get_clean();
    return $post_id ? bfnd_page_design_document($html, $post_id, true) : $html;
}

function bfnd_page_design_skip($node) {
    if (!$node instanceof DOMElement) { return false; }
    $class = ' ' . $node->getAttribute('class') . ' ';
    foreach (array('bf-work-grid', 'bf-course-grid', 'bf-journal-grid', 'bf-catalog-toolbar', 'bf-form-shell') as $excluded) {
        if (strpos($class, ' ' . $excluded . ' ') !== false) { return true; }
    }
    return false;
}

function bfnd_page_design_inner_html($node) {
    $html = '';
    foreach ($node->childNodes as $child) { $html .= $node->ownerDocument->saveHTML($child); }
    return $html;
}

function bfnd_page_design_set_inner_html($node, $html) {
    while ($node->firstChild) { $node->removeChild($node->firstChild); }
    $fragment = $node->ownerDocument->createDocumentFragment();
    if (@$fragment->appendXML($html)) { $node->appendChild($fragment); }
    else { $node->appendChild($node->ownerDocument->createTextNode(wp_strip_all_tags($html))); }
}

function bfnd_page_design_walk($node, $section, &$fields, $overrides, $apply, &$serial) {
    if (!$node instanceof DOMElement || bfnd_page_design_skip($node)) { return; }
    $tag = strtolower($node->tagName);
    $style = $node->getAttribute('style');
    if ($style && preg_match('/url\([\'\"]?([^\)\'\"]+)[\'\"]?\)/', $style, $background)) {
        $key = $section . '_image_' . ++$serial['image'];
        $fields[$key] = array('type' => 'image', 'label' => '背景圖片', 'default' => $background[1]);
        if ($apply && (!empty($overrides['image'][$key]) || !empty($overrides['image_url'][$key]))) {
            $url = !empty($overrides['image'][$key]) ? wp_get_attachment_image_url(absint($overrides['image'][$key]), 'full') : esc_url_raw($overrides['image_url'][$key]);
            if ($url) { $node->setAttribute('style', str_replace($background[1], esc_url_raw($url), $style)); }
        }
    }
    if ($tag === 'img') {
        $key = $section . '_image_' . ++$serial['image'];
        $fallback = $node->getAttribute('src');
        $fields[$key] = array('type' => 'image', 'label' => $node->getAttribute('alt') ?: '版面圖片', 'default' => $fallback);
        if ($apply && (!empty($overrides['image'][$key]) || !empty($overrides['image_url'][$key]))) {
            $id = !empty($overrides['image'][$key]) ? absint($overrides['image'][$key]) : 0;
            $url = $id ? wp_get_attachment_image_url($id, 'full') : esc_url_raw($overrides['image_url'][$key]);
            if ($url) {
                $node->setAttribute('src', $url);
                $alt = get_post_meta($id, '_wp_attachment_image_alt', true);
                if ($alt) { $node->setAttribute('alt', $alt); }
            }
        }
        return;
    }
    $text_tags = array('h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'small', 'blockquote', 'summary', 'dt', 'dd', 'strong', 'span');
    if (in_array($tag, $text_tags, true)) {
        $plain = trim(preg_replace('/\s+/u', ' ', $node->textContent));
        if ($plain !== '' && preg_match('/[\p{L}\p{N}]/u', $plain)) {
            $key = $section . '_text_' . ++$serial['text'];
            $default = bfnd_page_design_inner_html($node);
            $fields[$key] = array('type' => 'text', 'label' => mb_substr($plain, 0, 70), 'default' => $default, 'tag' => $tag);
            if ($apply && isset($overrides['text'][$key])) {
                bfnd_page_design_set_inner_html($node, bfnd_page_design_safe_inline($overrides['text'][$key]));
            }
            return;
        }
    }
    foreach ($node->childNodes as $child) {
        if ($child instanceof DOMElement) { bfnd_page_design_walk($child, $section, $fields, $overrides, $apply, $serial); }
    }
}

function bfnd_page_design_safe_inline($value) {
    $safe = wp_kses((string) $value, array(
        'br' => array(), 'em' => array(), 'strong' => array(), 'b' => array(),
        'i' => array(), 'sup' => array(), 'sub' => array(), 'span' => array('class' => true),
    ));
    return str_replace(array("\r\n", "\r", "\n"), '<br>', $safe);
}

function bfnd_page_design_document($html, $post_id, $apply = true, &$sections = array()) {
    if (!class_exists('DOMDocument')) { return $html; }
    $previous = libxml_use_internal_errors(true);
    $document = new DOMDocument('1.0', 'UTF-8');
    $document->loadHTML('<?xml encoding="utf-8"?><div id="bfnd-design-root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);
    $root = $document->getElementById('bfnd-design-root');
    if (!$root) { return $html; }
    $page_key = get_post_meta($post_id, '_bfnd_page', true);
    $saved = get_option('bfnd_page_design_' . $page_key, null);
    if (!is_array($saved)) {
        $saved = get_post_meta($post_id, '_bfnd_page_design', true);
        $saved = is_array($saved) ? $saved : array();
        $block_values = function_exists('bfnd_design_block_values') ? bfnd_design_block_values($post_id) : null;
        if (is_array($block_values)) { $saved = $block_values; }
    }
    $index = 0;
    $hidden = array();
    foreach ($root->childNodes as $child) {
        if (!$child instanceof DOMElement) { continue; }
        $index++;
        $key = 'section_' . $index;
        $fields = array();
        $serial = array('text' => 0, 'image' => 0);
        bfnd_page_design_walk($child, $key, $fields, $saved, $apply, $serial);
        $heading = '';
        foreach ($fields as $field) {
            if ($field['type'] === 'text' && in_array($field['tag'], array('h1', 'h2', 'h3'), true)) { $heading = $field['label']; break; }
        }
        $sections[$key] = array('title' => $heading ?: ('區塊 ' . $index), 'fields' => $fields);
        if ($apply && (isset($saved['_sections_present']) ? !in_array($key, $saved['_sections_present'], true) : !empty($saved['hidden'][$key]))) { $hidden[] = $child; }
    }
    foreach ($hidden as $child) { $root->removeChild($child); }
    $output = '';
    foreach ($root->childNodes as $child) { $output .= $document->saveHTML($child); }
    return $output;
}

function bfnd_page_design_meta_boxes() {
    $post_id = isset($_GET['post']) ? absint($_GET['post']) : 0;
    if (!$post_id || !get_post_meta($post_id, '_bfnd_page', true)) { return; }
    add_meta_box('bfnd_page_design', '飛熊入夢版面文字與圖片', 'bfnd_page_design_meta_box', 'page', 'normal', 'high');
}

function bfnd_page_design_meta_box($post) {
    $key = get_post_meta($post->ID, '_bfnd_page', true);
    if (!$key) { return; }
    $html = bfnd_page_layout_html($key);
    $sections = array();
    bfnd_page_design_document($html, $post->ID, false, $sections);
    $saved = get_post_meta($post->ID, '_bfnd_page_design', true);
    $saved = is_array($saved) ? $saved : array();
    wp_nonce_field('bfnd_page_design_' . $post->ID, 'bfnd_page_design_nonce');
    echo '<p>依照客戶手稿固定版型，逐區編輯文字與圖片。圖片從 WordPress 媒體庫選取；要恢復原文或原圖，請使用欄位旁的「恢復預設」按鈕。作品、課程、日誌請到各自的內容列表管理。文字可使用換行、&lt;br&gt;、&lt;em&gt; 與 &lt;strong&gt;。</p>';
    if ($key === 'lifestyle') {
        echo '<p class="bfnd-design-field"><label><input type="checkbox" name="bfnd_show_lifestyle_works" value="1"' . checked(get_post_meta($post->ID, '_bfnd_show_lifestyle_works', true), '1', false) . '> 顯示已發佈的生活木作作品</label><br><small>依客戶手稿，初始狀態只顯示「持續製作中」；準備公開作品時再勾選。</small></p>';
    }
    foreach ($sections as $section_key => $section) {
        echo '<details class="bfnd-design-section"><summary>' . esc_html($section['title']) . ' <small>（' . count($section['fields']) . ' 個欄位）</small></summary>';
        echo '<p class="bfnd-design-field"><label><input type="checkbox" name="bfnd_design_hidden[' . esc_attr($section_key) . ']" value="1"' . checked(!empty($saved['hidden'][$section_key]), true, false) . '> 隱藏整個區塊</label></p>';
        foreach ($section['fields'] as $field_key => $field) {
            if ($field['type'] === 'image') {
                $id = isset($saved['image'][$field_key]) ? absint($saved['image'][$field_key]) : 0;
                $url = $id ? wp_get_attachment_image_url($id, 'medium') : $field['default'];
                echo '<div class="bfnd-design-field bfnd-design-media"><label><strong>' . esc_html($field['label']) . '</strong></label><div class="bfnd-design-image-preview"><img src="' . esc_url($url) . '" data-default="' . esc_url($field['default']) . '" alt=""></div><input type="hidden" name="bfnd_design_image[' . esc_attr($field_key) . ']" value="' . esc_attr($id) . '"><button type="button" class="button bfnd-design-pick">從媒體庫選取</button> <button type="button" class="button bfnd-design-reset">恢復預設圖</button></div>';
            } else {
                $value = isset($saved['text'][$field_key]) ? $saved['text'][$field_key] : $field['default'];
                echo '<p class="bfnd-design-field"><label for="bfnd_design_' . esc_attr($field_key) . '"><strong>' . esc_html($field['label']) . '</strong></label><textarea id="bfnd_design_' . esc_attr($field_key) . '" name="bfnd_design_text[' . esc_attr($field_key) . ']" rows="' . (mb_strlen(wp_strip_all_tags($value)) > 160 ? '5' : '2') . '" data-default="' . esc_attr($field['default']) . '">' . esc_textarea($value) . '</textarea><button type="button" class="button-link bfnd-design-text-reset">恢復預設文字</button></p>';
            }
        }
        echo '</details>';
    }
}

function bfnd_page_design_save($post_id) {
    if (!isset($_POST['bfnd_page_design_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['bfnd_page_design_nonce'])), 'bfnd_page_design_' . $post_id)) { return; }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) { return; }
    if (!current_user_can('edit_post', $post_id) || !get_post_meta($post_id, '_bfnd_page', true)) { return; }
    $key = get_post_meta($post_id, '_bfnd_page', true);
    if ($key === 'lifestyle') {
        update_post_meta($post_id, '_bfnd_show_lifestyle_works', isset($_POST['bfnd_show_lifestyle_works']) ? '1' : '0');
    }
    $sections = array();
    bfnd_page_design_document(bfnd_page_layout_html($key), $post_id, false, $sections);
    $allowed = array();
    foreach ($sections as $section) { $allowed = array_merge($allowed, $section['fields']); }
    $text = isset($_POST['bfnd_design_text']) && is_array($_POST['bfnd_design_text']) ? wp_unslash($_POST['bfnd_design_text']) : array();
    $images = isset($_POST['bfnd_design_image']) && is_array($_POST['bfnd_design_image']) ? wp_unslash($_POST['bfnd_design_image']) : array();
    $hidden = isset($_POST['bfnd_design_hidden']) && is_array($_POST['bfnd_design_hidden']) ? wp_unslash($_POST['bfnd_design_hidden']) : array();
    $save = array('text' => array(), 'image' => array(), 'hidden' => array());
    foreach ($sections as $section_key => $section) { if (!empty($hidden[$section_key])) { $save['hidden'][$section_key] = 1; } }
    foreach ($allowed as $field_key => $field) {
        if ($field['type'] === 'text' && isset($text[$field_key]) && is_string($text[$field_key])) {
            $value = bfnd_page_design_safe_inline(mb_substr($text[$field_key], 0, 5000));
            if ($value !== $field['default']) { $save['text'][$field_key] = $value; }
        }
        if ($field['type'] === 'image' && isset($images[$field_key])) {
            $id = absint($images[$field_key]);
            if ($id && wp_attachment_is_image($id)) { $save['image'][$field_key] = $id; }
        }
    }
    update_post_meta($post_id, '_bfnd_page_design', $save);
}

function bfnd_page_design_admin_assets($hook) {
    if (!in_array($hook, array('post.php', 'post-new.php'), true)) { return; }
    $screen = get_current_screen();
    if (!$screen || $screen->post_type !== 'page') { return; }
    wp_enqueue_media();
    wp_enqueue_style('bfnd-page-design', bfnd_asset('public/page-design.css'), array(), '0.3.2');
    wp_enqueue_script('bfnd-page-design', bfnd_asset('public/page-design.js'), array('jquery'), '0.3.2', true);
}
