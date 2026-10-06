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

function bfnd_youtube_video_id($url) {
    $parts = wp_parse_url(trim((string) $url));
    if (!is_array($parts)) { return ''; }
    $scheme = strtolower($parts['scheme'] ?? '');
    if (!in_array($scheme, array('http', 'https'), true)) { return ''; }
    $host = strtolower($parts['host'] ?? '');
    $host = preg_replace('/^(?:www|m)\./', '', $host);
    $path = trim((string) ($parts['path'] ?? ''), '/');
    $video_id = '';

    if ($host === 'youtu.be') {
        $video_id = explode('/', $path)[0] ?? '';
    } elseif ($host === 'youtube.com') {
        $query = array();
        parse_str((string) ($parts['query'] ?? ''), $query);
        if (!empty($query['v'])) { $video_id = (string) $query['v']; }
        elseif (preg_match('~^(?:embed|shorts|live)/([A-Za-z0-9_-]{11})(?:/|$)~', $path, $match)) { $video_id = $match[1]; }
    }

    return preg_match('/^[A-Za-z0-9_-]{11}$/', $video_id) ? $video_id : '';
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
    if ($node->hasAttribute('data-bfnd-page-design-skip')) { return true; }
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

function bfnd_page_design_image_is_blocked($url, $attachment_id = 0, $alt = '') {
    if (function_exists('bfnd_nonfinal_photo') && bfnd_nonfinal_photo($url, $attachment_id, $alt)) { return true; }
    $source = '';
    if ($attachment_id) {
        $source = (string) get_post_meta($attachment_id, '_bfnd_source', true);
        if ($alt === '') { $alt = (string) get_post_meta($attachment_id, '_wp_attachment_image_alt', true); }
    }
    return stripos((string) $url . ' ' . $source, 'editorial') !== false
        || stripos((string) $url . ' ' . $source, 'wooden-cup') !== false
        || strpos((string) $alt, "\xe7\xa4\xba\xe6\x84\x8f") !== false;
}

function bfnd_filter_nonfinal_image_block($content, $block) {
    if (!is_array($block) || ($block['blockName'] ?? '') !== 'core/image') { return $content; }
    if (!preg_match('/<img\\b[^>]*\\bsrc=["\\\']([^"\\\']+)["\\\'][^>]*>/i', $content, $image)) { return $content; }
    preg_match('/\\bwp-image-(\\d+)\\b/', $image[0], $attachment);
    preg_match('/\\balt=["\\\']([^"\\\']*)["\\\']/i', $image[0], $alt);
    return bfnd_page_design_image_is_blocked(html_entity_decode($image[1], ENT_QUOTES, 'UTF-8'), (int) ($attachment[1] ?? 0), html_entity_decode($alt[1] ?? '', ENT_QUOTES, 'UTF-8')) ? '' : $content;
}
add_filter('render_block', 'bfnd_filter_nonfinal_image_block', 10, 2);

function bfnd_page_design_walk($node, $section, &$fields, $overrides, $apply, &$serial) {
    if (!$node instanceof DOMElement || bfnd_page_design_skip($node)) { return; }
    // Retired front-end fields keep their old ordinal slots so saved overrides
    // remain attached to the same fields after captions and images are removed.
    if ($node->hasAttribute('data-bfnd-reserved-text')) { $serial['text']++; return; }
    if ($node->hasAttribute('data-bfnd-reserved-image')) { $serial['image']++; return; }
    $tag = strtolower($node->tagName);
    $style = $node->getAttribute('style');
    if ($style && preg_match('/url\([\'\"]?([^\)\'\"]+)[\'\"]?\)/', $style, $background)) {
        $key = $section . '_image_' . ++$serial['image'];
        $fields[$key] = array('type' => 'image', 'label' => '背景圖片', 'default' => $background[1]);
        if ($apply && bfnd_page_design_image_is_blocked($background[1])) {
            $style = preg_replace('/background-image\\s*:[^;]*;?/i', '', $style);
            $node->setAttribute('style', $style);
        }
        if ($apply && (!empty($overrides['image'][$key]) || !empty($overrides['image_url'][$key]))) {
            $image_id = !empty($overrides['image'][$key]) ? absint($overrides['image'][$key]) : 0;
            $url = $image_id ? wp_get_attachment_image_url($image_id, 'full') : esc_url_raw($overrides['image_url'][$key]);
            if ($url && !bfnd_page_design_image_is_blocked($url, $image_id)) { $node->setAttribute('style', str_replace($background[1], esc_url_raw($url), $style)); }
        }
    }
    if ($tag === 'img') {
        $key = $section . '_image_' . ++$serial['image'];
        $fallback = $node->getAttribute('src');
        $fallback_alt = $node->getAttribute('alt');
        $fields[$key] = array('type' => 'image', 'label' => $fallback_alt ?: '版面圖片', 'default' => $fallback);
        preg_match('/\\bwp-image-(\\d+)\\b/', $node->getAttribute('class'), $fallback_attachment);
        if ($apply && bfnd_page_design_image_is_blocked($fallback, (int) ($fallback_attachment[1] ?? 0), $fallback_alt)) {
            if ($node->parentNode) { $node->parentNode->removeChild($node); }
            return;
        }
        if ($apply && (!empty($overrides['image'][$key]) || !empty($overrides['image_url'][$key]))) {
            $id = !empty($overrides['image'][$key]) ? absint($overrides['image'][$key]) : 0;
            $url = $id ? wp_get_attachment_image_url($id, 'full') : esc_url_raw($overrides['image_url'][$key]);
            if ($url && !bfnd_page_design_image_is_blocked($url, $id)) {
                $node->setAttribute('src', $url);
                $alt = get_post_meta($id, '_wp_attachment_image_alt', true);
                if ($alt && !bfnd_page_design_image_is_blocked($url, $id, $alt)) { $node->setAttribute('alt', $alt); }
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
            // An override whose text equals the default (ignoring whitespace) only drops the default's
            // markup, e.g. a migrated "GOOD WOODBETTER LIVING." without the <br>: keep the default HTML.
            $same_text = isset($overrides['text'][$key]) && preg_replace('/\s+/u', '', html_entity_decode(wp_strip_all_tags((string) $overrides['text'][$key]), ENT_QUOTES | ENT_HTML5, 'UTF-8')) === preg_replace('/\s+/u', '', $node->textContent);
            if ($apply && isset($overrides['text'][$key]) && !$same_text) {
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
        if ($child->hasAttribute('data-bfnd-design-ignore')) { continue; }
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
