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

// The journal page header lives with its articles under 飛熊日誌 (native posts), not under 網站版面,
// so editors have one place for everything journal-related.
function bfnd_layout_admin_url($key) {
    return $key === 'journal' ? admin_url('edit.php?page=bfnd-layout-journal') : admin_url('admin.php?page=bfnd-layout-' . $key);
}

function bfnd_template_menu() {
    add_menu_page('網站版面', '網站版面', 'edit_pages', 'bfnd-layout-overview', 'bfnd_template_dashboard', 'dashicons-layout', 24);
    add_submenu_page('bfnd-layout-overview', 'Banner 輪播', 'Banner 輪播', 'edit_pages', 'bfnd-banner-manager', 'bfnd_banner_manager');
    foreach (bfnd_pages() as $key => $info) {
        $parent = $key === 'journal' ? 'edit.php' : 'bfnd-layout-overview';
        $title = $key === 'journal' ? '日誌頁面設定' : $info[0];
        add_submenu_page($parent, $title, $title, 'edit_pages', 'bfnd-layout-' . $key, function () use ($key) {
            bfnd_template_editor($key);
        });
    }
}

function bfnd_banner_pages() {
    return array(
        'home' => '首頁主視覺',
        'furniture' => '家具作品',
        'lifestyle' => '生活木作',
        'school' => '木作學堂',
        'collaboration' => '合作提案',
    );
}

function bfnd_banner_manager() {
    if (!current_user_can('edit_pages')) { wp_die('權限不足'); }
    echo '<div class="wrap bfnd-banner-admin"><h1>Banner 輪播管理</h1><p>為各頁 Banner 選取多張正式照片並調整順序。前台每 6 秒切換，可手動切換或暫停。每組至少保留 1 張；推薦放 2–5 張，避免同一頁重複照片。</p>';
    if (isset($_GET['updated'])) { echo '<div class="notice notice-success is-dismissible"><p>Banner 圖片與順序已儲存。</p></div>'; }
    echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="bfnd_save_banners">';
    wp_nonce_field('bfnd_save_banners');
    echo '<div class="bfnd-banner-admin-grid">';
    foreach (bfnd_banner_pages() as $key => $label) {
        $ids = get_option('bfnd_banner_slides_' . $key, array());
        if (!is_array($ids)) { $ids = array(); }
        echo '<section class="bfnd-banner-panel" data-bfnd-banner-gallery><div class="bfnd-banner-panel-head"><h2>' . esc_html($label) . '</h2><span>' . count($ids) . ' 張</span></div>';
        echo '<input type="hidden" name="bfnd_banner_slides[' . esc_attr($key) . ']" value="' . esc_attr(implode(',', array_map('absint', $ids))) . '">';
        echo '<div class="bfnd-banner-items">';
        foreach ($ids as $id) {
            $id = absint($id);
            if (!$id || !wp_attachment_is_image($id)) { continue; }
            $url = wp_get_attachment_image_url($id, 'medium');
            $alt = (string) get_post_meta($id, '_wp_attachment_image_alt', true);
            if (!$url || bfnd_nonfinal_photo($url, $id, $alt)) { continue; }
            echo '<article class="bfnd-banner-item" data-id="' . esc_attr($id) . '"><img src="' . esc_url($url) . '" alt=""><div class="bfnd-banner-item-actions"><button type="button" class="button" data-move="-1" aria-label="圖片往前">←</button><button type="button" class="button" data-move="1" aria-label="圖片往後">→</button><button type="button" class="button-link-delete" data-remove>移除</button></div></article>';
        }
        echo '</div><button type="button" class="button button-secondary" data-pick-banner>從媒體庫選取照片</button><p class="description">可以多選；左右按鈕調整順序。每頁獨立管理。</p></section>';
    }
    echo '</div>';
    submit_button('儲存全部 Banner');
    echo '</form></div>';
}

function bfnd_save_banners_action() {
    if (!current_user_can('edit_pages')) { wp_die('權限不足'); }
    check_admin_referer('bfnd_save_banners');
    $posted = isset($_POST['bfnd_banner_slides']) && is_array($_POST['bfnd_banner_slides']) ? wp_unslash($_POST['bfnd_banner_slides']) : array();
    $save = array();
    foreach (bfnd_banner_pages() as $key => $label) {
        $raw = isset($posted[$key]) && is_string($posted[$key]) ? $posted[$key] : '';
        $ids = preg_split('/[\s,]+/', trim($raw), -1, PREG_SPLIT_NO_EMPTY);
        foreach ($ids as $candidate) {
            $id = absint($candidate);
            if (!$id || !wp_attachment_is_image($id)) { continue; }
            $url = wp_get_attachment_image_url($id, 'full');
            $alt = (string) get_post_meta($id, '_wp_attachment_image_alt', true);
            if (!$url || bfnd_nonfinal_photo($url, $id, $alt) || in_array($id, $save[$key] ?? array(), true)) { continue; }
            $save[$key][] = $id;
            if (count($save[$key]) >= 10) { break; }
        }
        if (empty($save[$key])) { wp_die(esc_html($label . '至少需要保留 1 張正式圖片。')); }
    }
    foreach ($save as $key => $ids) { update_option('bfnd_banner_slides_' . $key, $ids, false); }
    wp_safe_redirect(add_query_arg('updated', '1', admin_url('admin.php?page=bfnd-banner-manager')));
    exit;
}

function bfnd_template_shared_layout_notice() {
        echo '<div class="notice notice-info inline"><p><strong>頁首與頁尾不在這裡改。</strong>連結請到<strong>外觀 → 選單</strong>；頁尾標語、社群與 LINE 網址請到<strong>外觀 → 自訂 → 飛熊入夢頁首頁尾</strong>。頁首「木作學堂」的課程下拉會自動列出已發佈的木作課程。</p><p><a class="button" href="' . esc_url(admin_url('nav-menus.php')) . '">選單</a> <a class="button" href="' . esc_url(admin_url('customize.php?autofocus[section]=bf_header_footer')) . '">頁尾文字與社群網址</a> <a class="button" href="' . esc_url(function_exists('bfnd_guide_url') ? bfnd_guide_url('layout') : admin_url('index.php')) . '">網站版面教學</a></p></div>';
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
    if (isset($lists[$key]['post'])) {
        // Journal articles are native WordPress posts; this screen only holds the page's fixed text and images.
        $journal_notes = array(
            'journal' => '這一頁只改日誌頁<strong>最上方的標題、介紹文字與主圖</strong>（也可從左側「飛熊日誌 → 日誌頁面設定」進來）。',
            'home' => '首頁的「飛熊日誌」區塊會自動顯示最新 3 篇文章，不用在這裡編輯文章。',
            'collaboration' => '合作頁的「飛熊日誌」區塊會自動顯示最新 3 篇文章，不用在這裡編輯文章。',
        );
        echo '<div class="notice notice-info inline"><p>' . wp_kses($journal_notes[$key] ?? '', array('strong' => array())) . ' <strong>日誌文章的新增、修改、刪除，請到左側選單「飛熊日誌」。</strong></p><p><a class="button button-primary" href="' . esc_url(admin_url('edit.php')) . '">前往飛熊日誌文章列表</a> <a class="button" href="' . esc_url(admin_url('post-new.php')) . '">新增一篇日誌文章</a></p></div>';
    }
    echo '<p><strong>逐筆內容：</strong> ';
    foreach ($lists[$key] as $type => $label) {
        $url = $type === 'post' ? admin_url('edit.php') : admin_url('edit.php?post_type=' . $type);
        echo '<a class="button" href="' . esc_url($url) . '">' . esc_html($label) . '（新增／編輯／回收桶）</a> ';
    }
    echo '</p>';
}

function bfnd_template_seo_fields($page) {
    $title = get_post_meta($page->ID, '_yoast_wpseo_title', true);
    if ($title === '') { $title = get_post_meta($page->ID, '_bfnd_seo_title', true); }
    $description = get_post_meta($page->ID, '_yoast_wpseo_metadesc', true);
    if ($description === '') { $description = get_post_meta($page->ID, '_bfnd_seo_description', true); }
    $focus_keyphrase = get_post_meta($page->ID, '_yoast_wpseo_focuskw', true);
    $editor_url = admin_url('post.php?post=' . absint($page->ID) . '&action=edit');

    echo '<details class="bfnd-design-section bfnd-seo-settings" open><summary>SEO｜搜尋結果資訊</summary>';
    echo '<p>SEO 資料會存到這一頁，前台標題與摘要由 Yoast 輸出。Yoast 分析會讀取已儲存的版面實際文字，不會只看到短代碼。焦點關鍵字詞是編輯檢查用，不會顯示在搜尋結果。<br><a href="' . esc_url($editor_url) . '" target="_blank" rel="noopener noreferrer">開啟 WordPress 頁面編輯器查看 Yoast 分析 ↗</a></p>';
    if (!defined('WPSEO_VERSION')) {
        echo '<div class="notice notice-warning inline"><p>目前未偵測到 Yoast SEO；欄位可先儲存，但 Yoast 分析與 SEO 標籤需要外掛啟用後才會生效。</p></div>';
    }
    echo '<p class="bfnd-design-field"><label for="bfnd_yoast_title"><strong>SEO 標題</strong></label><input id="bfnd_yoast_title" name="bfnd_yoast_title" type="text" maxlength="200" value="' . esc_attr($title) . '" placeholder="每頁獨立撰寫：主要主題｜飛熊入夢 Bear’s Fantasyland"></p>';
    echo '<p class="bfnd-design-field"><label for="bfnd_yoast_description"><strong>Meta Description</strong></label><textarea id="bfnd_yoast_description" name="bfnd_yoast_description" rows="3" maxlength="350" placeholder="用一至兩句準確說明這頁提供什麼，以及訪客可以做什麼。">' . esc_textarea($description) . '</textarea></p>';
    echo '<p class="bfnd-design-field"><label for="bfnd_yoast_focus_keyphrase"><strong>Yoast 焦點關鍵字詞</strong></label><input id="bfnd_yoast_focus_keyphrase" name="bfnd_yoast_focus_keyphrase" type="text" maxlength="200" value="' . esc_attr($focus_keyphrase) . '" placeholder="填一個最符合此頁搜尋意圖的詞組"></p>';
    echo '<p><small>不要為了讓指示燈變綠而重複堆詞。先寫給客戶看的清楚標題與摘要，再用 Yoast 檢查；紅／橘燈是編輯提示，不是搜尋排名保證。</small></p></details>';
}

function bfnd_template_dashboard() {
    if (!current_user_can('edit_pages')) { wp_die('權限不足'); }
    echo '<div class="wrap bfnd-template-admin"><h1>網站版面</h1><p>從下方選擇頁面，直接修改固定版型的文字、圖片和顯示區塊。公開頁面只載入一行短代碼。家具作品、生活木作、課程及飛熊日誌的新增與刪除，請使用左側各自的內容列表。</p>';
    bfnd_template_shared_layout_notice();
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
        if ($key === 'journal') {
            echo '<div class="bfnd-template-card"><h2>' . esc_html($info[0]) . '</h2><p>日誌頁的標題、介紹與主圖，和日誌文章放在一起：左側「飛熊日誌 → 日誌頁面設定」。</p><a class="button" href="' . esc_url(bfnd_layout_admin_url($key)) . '">前往日誌頁面設定</a></div>';
            continue;
        }
        echo '<div class="bfnd-template-card"><h2>' . esc_html($info[0]) . '</h2><p>' . ($ready ? '版型已連接，可在此管理內容。' : '尚待轉換；原頁內容仍保留。') . '</p><a class="button button-primary" href="' . esc_url(bfnd_layout_admin_url($key)) . '">編輯版面</a> ';
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
    bfnd_template_shared_layout_notice();
    bfnd_template_content_links($key);
    echo '<p>' . ($key === 'journal' ? '<a href="' . esc_url(admin_url('edit.php')) . '">← 返回飛熊日誌文章</a>' : '<a href="' . esc_url(admin_url('admin.php?page=bfnd-layout-overview')) . '">← 返回網站版面</a>') . '　<a href="' . esc_url(get_permalink($page)) . '" target="_blank" rel="noopener noreferrer">查看前台 ↗</a></p>';
    echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="bfnd_save_template"><input type="hidden" name="page_key" value="' . esc_attr($key) . '">';
    wp_nonce_field('bfnd_save_template_' . $key);
    bfnd_template_seo_fields($page);
    if ($key === 'story') {
        $youtube_url = isset($saved['youtube_url']) ? (string) $saved['youtube_url'] : '';
        echo '<details class="bfnd-design-section" open><summary>品牌影片｜YouTube</summary>';
        echo '<p class="bfnd-design-field"><label for="bfnd_story_youtube_url"><strong>YouTube 品牌影片連結</strong></label><input id="bfnd_story_youtube_url" name="bfnd_story_youtube_url" type="url" inputmode="url" autocomplete="url" placeholder="https://www.youtube.com/watch?v=..." value="' . esc_attr($youtube_url) . '"></p>';
        echo '<p class="description">貼上 YouTube 一般影片、Shorts 或 youtu.be 分享連結並儲存。影片會顯示在品牌故事上方；訪客按下播放後才載入播放器。欄位留白時不公開影片區塊，也不會顯示空白或預告內容。</p></details>';
    }
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
                if (bfnd_page_design_image_is_blocked($url, $id)) { $id = 0; $url = $field['default']; }
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
    $youtube_url = isset($old['youtube_url']) ? (string) $old['youtube_url'] : '';
    if ($key === 'story' && isset($_POST['bfnd_story_youtube_url']) && is_string($_POST['bfnd_story_youtube_url'])) {
        $youtube_url = esc_url_raw(trim(wp_unslash($_POST['bfnd_story_youtube_url'])));
        if ($youtube_url !== '' && !bfnd_youtube_video_id($youtube_url)) {
            wp_die('請貼上有效的 YouTube 影片連結（一般影片、Shorts 或 youtu.be 分享網址），或清空欄位儲存。');
        }
        if ($youtube_url !== '') {
            $youtube_url = 'https://www.youtube.com/watch?v=' . rawurlencode(bfnd_youtube_video_id($youtube_url));
        }
    }
    $text = isset($_POST['bfnd_design_text']) && is_array($_POST['bfnd_design_text']) ? wp_unslash($_POST['bfnd_design_text']) : array();
    $images = isset($_POST['bfnd_design_image']) && is_array($_POST['bfnd_design_image']) ? wp_unslash($_POST['bfnd_design_image']) : array();
    $resets = isset($_POST['bfnd_design_image_reset']) && is_array($_POST['bfnd_design_image_reset']) ? wp_unslash($_POST['bfnd_design_image_reset']) : array();
    $hidden = isset($_POST['bfnd_design_hidden']) && is_array($_POST['bfnd_design_hidden']) ? wp_unslash($_POST['bfnd_design_hidden']) : array();
    $save = array('text' => array(), 'image' => array(), 'image_url' => array(), 'hidden' => array(), 'show_works' => $key === 'lifestyle' && isset($_POST['bfnd_show_lifestyle_works']) ? 1 : 0);
    if ($key === 'story') { $save['youtube_url'] = $youtube_url; }
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
    $seo_fields = array(
        'bfnd_yoast_title' => array('_yoast_wpseo_title', 'text', 200),
        'bfnd_yoast_description' => array('_yoast_wpseo_metadesc', 'textarea', 350),
        'bfnd_yoast_focus_keyphrase' => array('_yoast_wpseo_focuskw', 'text', 200),
    );
    foreach ($seo_fields as $request_key => $field) {
        if (!isset($_POST[$request_key]) || !is_string($_POST[$request_key])) { continue; }
        $raw_value = wp_unslash($_POST[$request_key]);
        $meta_value = $field[1] === 'textarea' ? sanitize_textarea_field($raw_value) : sanitize_text_field($raw_value);
        $meta_key = $field[0];
        $meta_value = mb_substr($meta_value, 0, $field[2]);
        if ($meta_value === '') { delete_post_meta($page->ID, $meta_key); }
        else { update_post_meta($page->ID, $meta_key, $meta_value); }
    }
    if (isset($_POST['bfnd_yoast_title']) && is_string($_POST['bfnd_yoast_title'])) { delete_post_meta($page->ID, '_bfnd_seo_title'); }
    if (isset($_POST['bfnd_yoast_description']) && is_string($_POST['bfnd_yoast_description'])) { delete_post_meta($page->ID, '_bfnd_seo_description'); }
    wp_safe_redirect(add_query_arg('updated', '1', bfnd_layout_admin_url($key)));
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
    if (strpos($hook, 'bfnd-banner-manager') !== false) {
        wp_enqueue_media();
        wp_enqueue_style('bfnd-banner-admin', bfnd_asset('public/banner-admin.css'), array(), BFND_VERSION);
        wp_enqueue_script('bfnd-banner-admin', bfnd_asset('public/banner-admin.js'), array('jquery'), BFND_VERSION, true);
        return;
    }
    if (strpos($hook, 'bfnd-layout-') === false) { return; }
    wp_enqueue_media();
    wp_enqueue_style('bfnd-page-design', bfnd_asset('public/page-design.css'), array(), '0.5.2');
    wp_enqueue_script('bfnd-page-design', bfnd_asset('public/page-design.js'), array('jquery'), '0.5.2', true);
}

function bfnd_template_admin_bar($bar) {
    if (!is_page() || !current_user_can('edit_pages')) { return; }
    $key = get_post_meta(get_queried_object_id(), '_bfnd_page', true);
    if (!isset(bfnd_pages()[$key])) { return; }
    $bar->add_node(array(
        'id' => 'edit',
        'title' => '編輯版面',
        'href' => bfnd_layout_admin_url($key),
    ));
}

function bfnd_template_page_row_action($actions, $post) {
    $key = get_post_meta($post->ID, '_bfnd_page', true);
    if (isset(bfnd_pages()[$key]) && current_user_can('edit_post', $post->ID)) {
        $actions['bfnd_template'] = '<a href="' . esc_url(bfnd_layout_admin_url($key)) . '">管理版面內容</a>';
    }
    return $actions;
}

add_action('admin_menu', 'bfnd_template_menu');
add_action('admin_post_bfnd_save_template', 'bfnd_save_template_action');
add_action('admin_post_bfnd_save_banners', 'bfnd_save_banners_action');
add_action('admin_post_bfnd_install_template_pages', 'bfnd_install_template_pages_action');
add_action('admin_enqueue_scripts', 'bfnd_template_admin_assets');
add_action('admin_bar_menu', 'bfnd_template_admin_bar', 1000);
add_filter('page_row_actions', 'bfnd_template_page_row_action', 10, 2);
