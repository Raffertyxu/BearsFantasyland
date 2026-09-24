<?php
if (!defined('ABSPATH')) { exit; }

/**
 * Template copy belongs to a dedicated admin screen. The public page contains
 * only its shortcode, while options hold the approved template's editable data.
 */
function bfnd_template_settings($key) {
    $saved = get_option('bfnd_page_design_' . $key, null);
    return is_array($saved) ? $saved : array();
}

function bfnd_template_sections($key, $post_id) {
    $sections = array();
    // Dynamic work cards must not renumber the fixed section/field keys.
    $previous = !empty($GLOBALS['bfnd_template_schema_render']);
    $GLOBALS['bfnd_template_schema_render'] = true;
    try {
        $html = bfnd_page_layout_html($key);
    } finally {
        if ($previous) { $GLOBALS['bfnd_template_schema_render'] = true; }
        else { unset($GLOBALS['bfnd_template_schema_render']); }
    }
    bfnd_page_design_document($html, $post_id, false, $sections);
    return $sections;
}

function bfnd_template_menu() {
    add_menu_page('網站版面', '網站版面', 'edit_pages', 'bfnd-layout-overview', 'bfnd_template_dashboard', 'dashicons-layout', 24);
    foreach (bfnd_pages() as $key => $info) {
        add_submenu_page('bfnd-layout-overview', $info[0], $info[0], 'edit_pages', 'bfnd-layout-' . $key, function () use ($key) {
            bfnd_template_editor($key);
        });
    }
}

function bfnd_template_content_links($key) {
    $lists = array(
        'home' => array('bf_work' => '家具作品', 'bf_lifestyle' => '生活木作', 'bf_course' => '木作課程', 'post' => '飛熊日誌'),
        'furniture' => array('bf_work' => '家具作品'),
        'lifestyle' => array('bf_lifestyle' => '生活木作'),
        'school' => array('bf_course' => '木作課程'),
        'collaboration' => array('post' => '飛熊日誌'),
        'journal' => array('post' => '飛熊日誌'),
    );
    if (empty($lists[$key])) { return; }
    echo '<p><strong>逐筆內容：</strong> ';
    foreach ($lists[$key] as $type => $label) {
        $url = $type === 'post' ? admin_url('edit.php') : admin_url('edit.php?post_type=' . $type);
        echo '<a class="button" href="' . esc_url($url) . '">' . esc_html($label) . '（新增／編輯／回收桶）</a> ';
    }
    echo '</p>';
}

function bfnd_template_dashboard() {
    if (!current_user_can('edit_pages')) { wp_die('權限不足'); }
    echo '<div class="wrap bfnd-template-admin"><h1>網站版面</h1><p>從下方選擇頁面，直接修改固定版型的文字、圖片和顯示區塊。公開頁面只載入一行短代碼。家具作品、生活木作、課程及飛熊日誌的新增與刪除，請使用左側各自的內容列表。</p>';
    if (isset($_GET['bfnd_migrated'])) {
        echo '<div class="notice notice-success"><p>已轉換 ' . absint($_GET['bfnd_migrated']) . ' 個頁面。</p></div>';
    }
    if (!empty($_GET['bfnd_failed'])) {
        echo '<div class="notice notice-error"><p>有 ' . absint($_GET['bfnd_failed']) . ' 個頁面未能轉換，原內容仍保留，請檢查後重試。</p></div>';
    }
    echo '<div class="bfnd-template-grid">';
    $pending = 0;
    foreach (bfnd_pages() as $key => $info) {
        $page = bfnd_page_post($key);
        $ready = $page && bfnd_template_page_is_shortcode_only($page, $key) && is_array(get_option('bfnd_page_design_' . $key, null));
        if (!$ready) { $pending++; }
        echo '<div class="bfnd-template-card"><h2>' . esc_html($info[0]) . '</h2><p>' . ($ready ? '版型已連接，可在此管理內容。' : '尚待轉換；原頁內容仍保留。') . '</p><a class="button button-primary" href="' . esc_url(admin_url('admin.php?page=bfnd-layout-' . $key)) . '">編輯版面</a> ';
        if ($page) { echo '<a class="button" href="' . esc_url(get_permalink($page)) . '" target="_blank" rel="noopener noreferrer">查看前台</a>'; }
        echo '</div>';
    }
    echo '</div>';
    if ($pending && current_user_can('manage_options')) {
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="bfnd_install_template_pages">';
        wp_nonce_field('bfnd_install_template_pages');
        submit_button('將現有內容移入版面管理，頁面改為單行短代碼');
        echo '</form>';
    }
    echo '</div>';
}

function bfnd_template_page_is_shortcode_only($page, $key) {
    if (!$page) { return false; }
    $content = trim($page->post_content);
    $blocks = parse_blocks($content);
    return count($blocks) === 1
        && $blocks[0]['blockName'] === 'core/shortcode'
        && trim($blocks[0]['innerHTML']) === '[bfnd_page key="' . $key . '"]';
}

function bfnd_template_editor($key) {
    if (!current_user_can('edit_pages') || !isset(bfnd_pages()[$key])) { wp_die('權限不足'); }
    $page = bfnd_page_post($key);
    if (!$page || !current_user_can('edit_post', $page->ID)) { wp_die('頁面不存在或權限不足'); }
    $saved = bfnd_template_settings($key);
    $sections = bfnd_template_sections($key, $page->ID);
    $info = bfnd_pages()[$key];
    echo '<div class="wrap bfnd-template-admin"><h1>' . esc_html($info[0]) . '｜版面管理</h1>';
    if (isset($_GET['updated'])) { echo '<div class="notice notice-success is-dismissible"><p>內容已儲存。</p></div>'; }
    if (!bfnd_template_page_is_shortcode_only($page, $key)) {
        echo '<div class="notice notice-warning"><p>此頁尚未改為單行短代碼。請先到「網站版面」執行內容轉換。</p></div>';
    }
    echo '<p>在這裡修改手稿固定版型的文字與圖片。圖片使用 WordPress 媒體庫；恢復預設會使用原始版型內容。家具作品、生活木作作品、課程與飛熊日誌的內容請在各自的 WordPress 清單新增、編輯或移到回收桶。</p>';
    bfnd_template_content_links($key);
    echo '<p><a href="' . esc_url(admin_url('admin.php?page=bfnd-layout-overview')) . '">← 返回網站版面</a>　<a href="' . esc_url(get_permalink($page)) . '" target="_blank" rel="noopener noreferrer">查看前台 ↗</a></p>';
    echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="bfnd_save_template"><input type="hidden" name="page_key" value="' . esc_attr($key) . '">';
    wp_nonce_field('bfnd_save_template_' . $key);
    if ($key === 'lifestyle') {
        echo '<p class="bfnd-design-field"><label><input type="checkbox" name="bfnd_show_lifestyle_works" value="1"' . checked(!empty($saved['show_works']), true, false) . '> 顯示已發布的生活木作作品</label><small>未勾選時依客戶手稿顯示「持續製作中」。</small></p>';
    }
    foreach ($sections as $section_key => $section) {
        echo '<details class="bfnd-design-section"><summary>' . esc_html($section['title']) . ' <small>（' . count($section['fields']) . ' 個欄位）</small></summary>';
        echo '<p class="bfnd-design-field"><label><input type="checkbox" name="bfnd_design_hidden[' . esc_attr($section_key) . ']" value="1"' . checked(!empty($saved['hidden'][$section_key]), true, false) . '> 隱藏整個區塊</label></p>';
        foreach ($section['fields'] as $field_key => $field) {
            if ($field['type'] === 'image') {
                $id = !empty($saved['image'][$field_key]) ? absint($saved['image'][$field_key]) : 0;
                $url = $id ? wp_get_attachment_image_url($id, 'medium') : (!empty($saved['image_url'][$field_key]) ? $saved['image_url'][$field_key] : $field['default']);
                echo '<div class="bfnd-design-field bfnd-design-media"><label><strong>' . esc_html($field['label']) . '</strong></label><div class="bfnd-design-image-preview"><img src="' . esc_url($url) . '" data-default="' . esc_url($field['default']) . '" alt=""></div><input type="hidden" name="bfnd_design_image[' . esc_attr($field_key) . ']" value="' . esc_attr($id) . '"><input type="hidden" class="bfnd-image-reset" name="bfnd_design_image_reset[' . esc_attr($field_key) . ']" value="0"><button type="button" class="button bfnd-design-pick">從媒體庫選取</button> <button type="button" class="button bfnd-design-reset">恢復預設圖</button></div>';
            } else {
                $value = isset($saved['text'][$field_key]) ? $saved['text'][$field_key] : $field['default'];
                echo '<p class="bfnd-design-field"><label for="bfnd_design_' . esc_attr($field_key) . '"><strong>' . esc_html($field['label']) . '</strong></label><textarea id="bfnd_design_' . esc_attr($field_key) . '" name="bfnd_design_text[' . esc_attr($field_key) . ']" rows="' . (mb_strlen(wp_strip_all_tags($value)) > 160 ? '5' : '2') . '" data-default="' . esc_attr($field['default']) . '">' . esc_textarea($value) . '</textarea><button type="button" class="button-link bfnd-design-text-reset">恢復預設文字</button></p>';
            }
        }
        echo '</details>';
    }
    submit_button('儲存此頁版面');
    echo '</form>';
    echo '</div>';
}

function bfnd_save_template_action() {
    $key = isset($_POST['page_key']) ? sanitize_key(wp_unslash($_POST['page_key'])) : '';
    if (!isset(bfnd_pages()[$key])) { wp_die('無效頁面'); }
    $page = bfnd_page_post($key);
    if (!$page || !current_user_can('edit_pages') || !current_user_can('edit_post', $page->ID)) { wp_die('權限不足'); }
    check_admin_referer('bfnd_save_template_' . $key);
    if (!bfnd_template_page_is_shortcode_only($page, $key)) { wp_die('請先將頁面轉為單行短代碼，再儲存版面。'); }
    $sections = bfnd_template_sections($key, $page->ID);
    $old = bfnd_template_settings($key);
    $text = isset($_POST['bfnd_design_text']) && is_array($_POST['bfnd_design_text']) ? wp_unslash($_POST['bfnd_design_text']) : array();
    $images = isset($_POST['bfnd_design_image']) && is_array($_POST['bfnd_design_image']) ? wp_unslash($_POST['bfnd_design_image']) : array();
    $resets = isset($_POST['bfnd_design_image_reset']) && is_array($_POST['bfnd_design_image_reset']) ? wp_unslash($_POST['bfnd_design_image_reset']) : array();
    $hidden = isset($_POST['bfnd_design_hidden']) && is_array($_POST['bfnd_design_hidden']) ? wp_unslash($_POST['bfnd_design_hidden']) : array();
    $save = array('text' => array(), 'image' => array(), 'image_url' => array(), 'hidden' => array(), 'show_works' => $key === 'lifestyle' && isset($_POST['bfnd_show_lifestyle_works']) ? 1 : 0);
    foreach ($sections as $section_key => $section) {
        if (!empty($hidden[$section_key])) { $save['hidden'][$section_key] = 1; }
        foreach ($section['fields'] as $field_key => $field) {
            if ($field['type'] === 'text' && isset($text[$field_key]) && is_string($text[$field_key])) {
                $value = bfnd_page_design_safe_inline(mb_substr($text[$field_key], 0, 5000));
                if ($value !== $field['default']) { $save['text'][$field_key] = $value; }
            }
            if ($field['type'] === 'image') {
                $id = isset($images[$field_key]) ? absint($images[$field_key]) : 0;
                if ($id && wp_attachment_is_image($id)) { $save['image'][$field_key] = $id; }
                elseif (empty($resets[$field_key]) && !empty($old['image_url'][$field_key])) {
                    $save['image_url'][$field_key] = esc_url_raw($old['image_url'][$field_key]);
                }
            }
        }
    }
    update_option('bfnd_page_design_' . $key, $save, false);
    wp_safe_redirect(add_query_arg('updated', '1', admin_url('admin.php?page=bfnd-layout-' . $key)));
    exit;
}

function bfnd_install_template_pages_action() {
    if (!current_user_can('manage_options')) { wp_die('權限不足'); }
    check_admin_referer('bfnd_install_template_pages');
    $done = 0;
    $failed = 0;
    foreach (bfnd_pages() as $key => $info) {
        $page = bfnd_page_post($key);
        if (!$page) { $failed++; continue; }
        if (bfnd_template_page_is_shortcode_only($page, $key) && is_array(get_option('bfnd_page_design_' . $key, null))) { continue; }
        $sections = bfnd_template_sections($key, $page->ID);
        $allowed = array();
        foreach ($sections as $section) { $allowed = array_merge($allowed, $section['fields']); }
        $values = bfnd_design_block_values($page->ID);
        if (!is_array($values)) {
            $values = get_post_meta($page->ID, '_bfnd_page_design', true);
            $values = is_array($values) ? $values : array();
        }
        $save = array('text' => array(), 'image' => array(), 'image_url' => array(), 'hidden' => array(), 'show_works' => 0);
        foreach ($allowed as $field_key => $field) {
            if ($field['type'] === 'text' && isset($values['text'][$field_key])) {
                $save['text'][$field_key] = bfnd_page_design_safe_inline($values['text'][$field_key]);
            }
            if ($field['type'] === 'image') {
                if (!empty($values['image'][$field_key]) && wp_attachment_is_image(absint($values['image'][$field_key]))) {
                    $save['image'][$field_key] = absint($values['image'][$field_key]);
                } elseif (!empty($values['image_url'][$field_key])) {
                    $save['image_url'][$field_key] = esc_url_raw($values['image_url'][$field_key]);
                }
            }
        }
        foreach ($sections as $section_key => $section) {
            if (isset($values['_sections_present']) && !in_array($section_key, $values['_sections_present'], true)) {
                $save['hidden'][$section_key] = 1;
            } elseif (!empty($values['hidden'][$section_key])) { $save['hidden'][$section_key] = 1; }
        }
        if ($key === 'lifestyle') {
            $save['show_works'] = has_shortcode($page->post_content, 'bfnd_show_works')
                || (!has_shortcode($page->post_content, 'bfnd_page') && get_post_meta($page->ID, '_bfnd_show_lifestyle_works', true) === '1') ? 1 : 0;
        }
        if (!is_array(get_option('bfnd_page_design_' . $key, null))) {
            update_option('bfnd_page_design_' . $key, $save, false);
        }
        if (!is_array(get_option('bfnd_page_design_' . $key, null))) { $failed++; continue; }
        if (!bfnd_template_page_is_shortcode_only($page, $key)) {
            $content = '<!-- wp:shortcode -->[bfnd_page key="' . $key . '"]<!-- /wp:shortcode -->';
            $result = wp_update_post(wp_slash(array('ID' => $page->ID, 'post_content' => $content)), true);
            if (is_wp_error($result)) { $failed++; continue; }
        }
        $done++;
    }
    wp_safe_redirect(add_query_arg(array('bfnd_migrated' => $done, 'bfnd_failed' => $failed), admin_url('admin.php?page=bfnd-layout-overview')));
    exit;
}

function bfnd_template_admin_assets($hook) {
    if (strpos($hook, 'bfnd-layout-') === false) { return; }
    wp_enqueue_media();
    wp_enqueue_style('bfnd-page-design', bfnd_asset('public/page-design.css'), array(), '0.5.1');
    wp_enqueue_script('bfnd-page-design', bfnd_asset('public/page-design.js'), array('jquery'), '0.5.1', true);
}

function bfnd_template_admin_bar($bar) {
    if (!is_page() || !current_user_can('edit_pages')) { return; }
    $key = get_post_meta(get_queried_object_id(), '_bfnd_page', true);
    if (!isset(bfnd_pages()[$key])) { return; }
    $bar->add_node(array(
        'id' => 'edit',
        'title' => '編輯版面',
        'href' => admin_url('admin.php?page=bfnd-layout-' . $key),
    ));
}

function bfnd_template_page_row_action($actions, $post) {
    $key = get_post_meta($post->ID, '_bfnd_page', true);
    if (isset(bfnd_pages()[$key]) && current_user_can('edit_post', $post->ID)) {
        $actions['bfnd_template'] = '<a href="' . esc_url(admin_url('admin.php?page=bfnd-layout-' . $key)) . '">管理版面內容</a>';
    }
    return $actions;
}

add_action('admin_menu', 'bfnd_template_menu');
add_action('admin_post_bfnd_save_template', 'bfnd_save_template_action');
add_action('admin_post_bfnd_install_template_pages', 'bfnd_install_template_pages_action');
add_action('admin_enqueue_scripts', 'bfnd_template_admin_assets');
add_action('admin_bar_menu', 'bfnd_template_admin_bar', 1000);
add_filter('page_row_actions', 'bfnd_template_page_row_action', 10, 2);
