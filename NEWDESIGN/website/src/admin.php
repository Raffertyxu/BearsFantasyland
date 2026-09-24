<?php
if (!defined('ABSPATH')) { exit; }

function bfnd_register_types() {
    $common = array('public' => true, 'show_in_rest' => true, 'has_archive' => true, 'rewrite' => array('slug' => 'works'),
        'supports' => array('title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'page-attributes'));
    register_post_type('bf_work', array_merge($common, array('labels' => array('name' => '家具作品', 'singular_name' => '家具作品', 'add_new_item' => '新增家具作品', 'edit_item' => '編輯家具作品'), 'menu_icon' => 'dashicons-art')));
    register_post_type('bf_lifestyle', array_merge($common, array('labels' => array('name' => '生活木作', 'singular_name' => '生活木作', 'add_new_item' => '新增生活木作'), 'rewrite' => array('slug' => 'lifestyle-works'), 'menu_icon' => 'dashicons-palmtree')));
    register_post_type('bf_course', array_merge($common, array('labels' => array('name' => '木作課程', 'singular_name' => '木作課程', 'add_new_item' => '新增木作課程'), 'rewrite' => array('slug' => 'woodworking-course'), 'menu_icon' => 'dashicons-welcome-learn-more')));
    register_post_type('bf_inquiry', array('labels' => array('name' => '訂製與合作詢問', 'singular_name' => '詢問', 'edit_item' => '查看詢問'),
        'public' => false, 'show_ui' => true, 'show_in_rest' => false, 'menu_icon' => 'dashicons-email-alt', 'supports' => array('title', 'editor')));
    register_taxonomy('bf_work_cat', 'bf_work', array('label' => '家具分類', 'hierarchical' => true, 'public' => true, 'show_in_rest' => true, 'rewrite' => array('slug' => 'furniture-category')));
    register_taxonomy('bf_series', 'bf_work', array('label' => '作品系列', 'hierarchical' => true, 'public' => true, 'show_in_rest' => true, 'rewrite' => array('slug' => 'furniture-series')));
    // Keep the old taxonomy readable for a one-time transfer into core Categories.
    register_taxonomy('bf_journal_cat', 'post', array('label' => '舊飛熊日誌分類', 'hierarchical' => true, 'public' => false, 'show_ui' => false, 'show_in_rest' => false));
}

function bfnd_migrate_native_journal_categories() {
    if (get_option('bfnd_native_journal_categories_v1')) { return; }
    $legacy = get_terms(array('taxonomy' => 'bf_journal_cat', 'hide_empty' => false));
    if (is_wp_error($legacy)) { return; }
    $map = array();
    foreach ($legacy as $term) {
        $native = term_exists($term->slug, 'category');
        if (!$native) { $native = wp_insert_term($term->name, 'category', array('slug' => $term->slug)); }
        if (!is_wp_error($native)) { $map[$term->term_id] = is_array($native) ? (int) $native['term_id'] : (int) $native; }
    }
    $posts = get_posts(array('post_type' => 'post', 'post_status' => 'any', 'numberposts' => -1,
        'tax_query' => array(array('taxonomy' => 'bf_journal_cat', 'operator' => 'EXISTS'))));
    foreach ($posts as $post) {
        $terms = wp_get_post_terms($post->ID, 'bf_journal_cat', array('fields' => 'ids'));
        if (is_wp_error($terms)) { continue; }
        $ids = array();
        foreach ($terms as $id) { if (isset($map[$id])) { $ids[] = $map[$id]; } }
        if ($ids) { wp_set_post_terms($post->ID, $ids, 'category', true); }
    }
    update_option('bfnd_native_journal_categories_v1', 1, false);
}

function bfnd_fields($type) {
    $shared = array('english' => '英文名稱', 'tagline' => '一句話介紹', 'seo_title' => 'SEO Title', 'seo_description' => 'SEO Description');
    if ($type === 'bf_work') { return array_merge($shared, array('type' => '作品類型', 'material' => '木材／材質', 'size' => '參考尺寸', 'craft' => '製作方式', 'craft_image_id' => '此件作品的實際製作過程照片 ID（未填即隱藏製作區）', 'custom' => '訂製說明', 'finish' => '表面處理', 'featured' => '首頁精選作品（1 顯示，0 不顯示）', 'related_ids' => '相關作品 ID（依序，以逗號分隔）', 'gallery_ids' => '細節照片')); }
    if ($type === 'bf_course') { return array_merge($shared, array('mode' => '課程模式：onsite / online', 'duration' => '課程時數', 'price' => '課程價格（NT$）', 'level' => '適合對象', 'schedule' => '開課資訊', 'woo_id' => 'WooCommerce 商品 ID（選填）', 'gallery_ids' => '其他照片')); }
    if ($type === 'bf_lifestyle') { return array_merge($shared, array('material' => '材質', 'size' => '參考尺寸', 'gallery_ids' => '作品照片')); }
    return array();
}

function bfnd_meta_boxes() {
    foreach (array('bf_work', 'bf_course', 'bf_lifestyle') as $type) {
        add_meta_box('bfnd_details', '飛熊入夢作品資料', 'bfnd_meta_box', $type, 'normal', 'high');
    }
    add_meta_box('bfnd_inquiry_details', '詢問資料', 'bfnd_inquiry_meta_box', 'bf_inquiry', 'normal', 'high');
}

function bfnd_meta_box($post) {
    wp_nonce_field('bfnd_save_meta', 'bfnd_meta_nonce');
    echo '<p>主圖請使用右側「特色圖片」。作品故事與課程內容請編輯上方本文；作品分類、系列可在右側管理。照片可從媒體庫選取，ID 順序就是前台顯示順序。</p>';
    foreach (bfnd_fields($post->post_type) as $key => $label) {
        $value = get_post_meta($post->ID, '_bfnd_' . $key, true);
        if ($key === 'gallery_ids') {
            $ids = is_array($value) ? $value : array();
            echo '<div class="bfnd-gallery-admin"><p><strong>' . esc_html($label) . '</strong>：可從媒體庫選取、拖曳排序或移除。</p><input type="hidden" id="bfnd_gallery_ids" name="bfnd[gallery_ids]" value="' . esc_attr(implode(',', $ids)) . '"><div id="bfnd-gallery-list">';
            foreach ($ids as $id) { $url = wp_get_attachment_image_url((int) $id, 'thumbnail'); if ($url) { echo '<div draggable="true" data-id="' . esc_attr($id) . '"><img src="' . esc_url($url) . '" alt=""><button type="button" class="bfnd-gallery-remove" aria-label="移除照片">×</button></div>'; } }
            echo '</div><button id="bfnd-gallery-pick" class="button" type="button">從媒體庫選取照片</button></div>';
            continue;
        }
        if (is_array($value)) { $value = implode(',', $value); }
        echo '<p><label for="bfnd_' . esc_attr($key) . '"><strong>' . esc_html($label) . '</strong></label><br>';
        echo '<input style="width:100%;max-width:780px" type="text" id="bfnd_' . esc_attr($key) . '" name="bfnd[' . esc_attr($key) . ']" value="' . esc_attr((string) $value) . '"></p>';
    }
}

function bfnd_inquiry_meta_box($post) {
    foreach (array('contact' => '聯絡方式', 'work' => '詢問作品', 'dimension' => '需求尺寸', 'space' => '使用空間', 'budget' => '預算／其他需求', 'attachment' => '參考附件') as $key => $label) {
        $value = get_post_meta($post->ID, '_bfnd_' . $key, true);
        echo '<p><strong>' . esc_html($label) . '</strong>：';
        if ($key === 'attachment' && $value) { echo '<a href="' . esc_url($value) . '" target="_blank" rel="noopener noreferrer">查看附件</a>'; }
        else { echo esc_html((string) $value ?: '—'); }
        echo '</p>';
    }
    $mail_status = get_post_meta($post->ID, '_bfnd_mail_status', true);
    echo '<p><strong>通知郵件</strong>：' . esc_html($mail_status === 'accepted' ? '已交給 WordPress 寄信程序，收件仍需由信箱確認' : ($mail_status === 'failed' ? '寄信程序回報失敗，詢問資料仍已保存' : '未記錄')) . '</p>';
}

function bfnd_save_meta($post_id) {
    if (!isset($_POST['bfnd_meta_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['bfnd_meta_nonce'])), 'bfnd_save_meta')) { return; }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) { return; }
    if (!current_user_can('edit_post', $post_id)) { return; }
    $type = get_post_type($post_id);
    if (!in_array($type, array('bf_work', 'bf_course', 'bf_lifestyle'), true)) { return; }
    $input = isset($_POST['bfnd']) && is_array($_POST['bfnd']) ? wp_unslash($_POST['bfnd']) : array();
    foreach (bfnd_fields($type) as $key => $unused) {
        $value = isset($input[$key]) ? sanitize_text_field($input[$key]) : '';
        if ($key === 'gallery_ids' || $key === 'related_ids') { $value = array_values(array_filter(array_map('absint', explode(',', $value)))); }
        if ($key === 'featured') { $value = $value === '1' ? '1' : '0'; }
        update_post_meta($post_id, '_bfnd_' . $key, $value);
        if ($key === 'gallery_ids') { update_post_meta($post_id, '_bfnd_gallery_override', '1'); }
    }
}

function bfnd_admin_enqueue($hook) {
    if (!in_array($hook, array('post.php', 'post-new.php'), true)) { return; }
    $screen = get_current_screen();
    if (!$screen || !in_array($screen->post_type, array('bf_work', 'bf_course', 'bf_lifestyle'), true)) { return; }
    wp_enqueue_media();
    wp_enqueue_style('bfnd-admin', bfnd_asset('public/admin.css'), array(), '0.1.0');
    wp_enqueue_script('bfnd-admin', bfnd_asset('public/admin.js'), array('jquery'), '0.1.0', true);
}

function bfnd_admin_menu() {
    add_management_page('飛熊入夢素材匯入', '飛熊入夢素材匯入', 'manage_options', 'bfnd-import', 'bfnd_import_page');
}

function bfnd_import_page() {
    if (!current_user_can('manage_options')) { return; }
    $step = isset($_GET['step']) ? max(0, (int) $_GET['step']) : 0;
    $count = count(bfnd_manifest()['works']) + 2 + count(bfnd_manifest()['courses']);
    echo '<div class="wrap"><h1>飛熊入夢 NEWDESIGN 匯入</h1><p>匯入客戶 Excel 的 17 件作品、托盤與原始照片。已存在的作品不覆蓋編輯內容或發布狀態；新增作品先設為「私密」，可在編輯完成後發布。</p>';
    if ($step >= $count) { echo '<div class="notice notice-success"><p>匯入完成。可到「家具作品」「生活木作」「木作課程」檢查內容與照片。</p></div>'; }
    else {
        echo '<p>進度：' . esc_html($step) . ' / ' . esc_html($count) . '</p><form id="bfnd-import-form" method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        echo '<input type="hidden" name="action" value="bfnd_import"><input type="hidden" name="step" value="' . esc_attr($step) . '">';
        wp_nonce_field('bfnd_import_' . $step);
        echo '<button class="button button-primary" type="submit">' . ($step ? '繼續匯入' : '開始匯入') . '</button></form>';
        if (!empty($_GET['run'])) { echo '<script>document.getElementById("bfnd-import-form").submit();</script>'; }
    }
    $ready = bfnd_launch_readiness();
    echo '<hr><h2>發布新版網站</h2>';
    if ($ready) { echo '<p>尚未達到發布條件：</p><ul>'; foreach ($ready as $issue) { echo '<li>' . esc_html($issue) . '</li>'; } echo '</ul>'; }
    else {
        $hero = (int) get_option('bfnd_home_hero_id');
        if ($hero) { echo '<p>首頁情境圖圖床網址：<input readonly style="width:80%" value="' . esc_attr(wp_get_attachment_url($hero)) . '"></p>'; }
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="bfnd_launch">'; wp_nonce_field('bfnd_launch'); echo '<button class="button button-primary" type="submit">發布全部新版頁面並切換現有首頁</button></form>';
    }
    if (!empty($_GET['launched'])) { echo '<div class="notice notice-success"><p>新版頁面已發布並切換首頁。請檢查手機與桌機畫面。</p></div>'; }
    if (get_option('bfnd_previous_home_id')) { echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="bfnd_restore">'; wp_nonce_field('bfnd_restore'); echo '<button class="button" type="submit">還原先前首頁</button></form>'; }
    echo '</div>';
}

function bfnd_launch_readiness() {
    $issues = array();
    foreach (bfnd_pages() as $key => $info) { if (!bfnd_page_post($key)) { $issues[] = '缺少頁面：' . $info[0]; } }
    $data = bfnd_manifest();
    foreach ($data['works'] as $work) { $id = bfnd_find_seed($work['slug'], 'bf_work'); if (!$id || !has_post_thumbnail($id)) { $issues[] = '作品照片未匯入：' . $work['title']; } }
    foreach ($data['courses'] as $course) { $id = bfnd_find_seed($course['slug'], 'bf_course'); if (!$id || !has_post_thumbnail($id)) { $issues[] = '課程照片未匯入：' . $course['title']; } }
    $life = bfnd_find_seed('alba-canvas', 'bf_lifestyle');
    if (!$life || !has_post_thumbnail($life)) { $issues[] = '生活木作照片未匯入'; }
    if (!get_option('bfnd_home_hero_id')) { $issues[] = '首頁情境圖未匯入媒體庫'; }
    return $issues;
}

function bfnd_launch_action() {
    if (!current_user_can('manage_options')) { wp_die('Permission denied'); }
    check_admin_referer('bfnd_launch');
    $issues = bfnd_launch_readiness();
    if ($issues) { wp_die(esc_html(implode('；', $issues))); }
    $home = bfnd_page_post('home');
    if (!get_option('bfnd_previous_home_id')) { update_option('bfnd_previous_home_id', (int) get_option('page_on_front')); }
    foreach (bfnd_pages() as $key => $info) { $page = bfnd_page_post($key); wp_update_post(array('ID' => $page->ID, 'post_status' => 'publish')); }
    foreach (bfnd_manifest()['works'] as $work) { $id = bfnd_find_seed($work['slug'], 'bf_work'); if ($id) { wp_update_post(array('ID' => $id, 'post_status' => 'publish')); } }
    foreach (bfnd_manifest()['courses'] as $course) { $id = bfnd_find_seed($course['slug'], 'bf_course'); if ($id) { wp_update_post(array('ID' => $id, 'post_status' => 'publish')); } }
    $life = bfnd_find_seed('alba-canvas', 'bf_lifestyle'); if ($life) { wp_update_post(array('ID' => $life, 'post_status' => 'publish')); }
    update_option('show_on_front', 'page');
    update_option('page_on_front', $home->ID);
    flush_rewrite_rules();
    wp_safe_redirect(admin_url('tools.php?page=bfnd-import&step=' . (count(bfnd_manifest()['works']) + 2 + count(bfnd_manifest()['courses'])) . '&launched=1'));
    exit;
}

function bfnd_restore_action() {
    if (!current_user_can('manage_options')) { wp_die('Permission denied'); }
    check_admin_referer('bfnd_restore');
    $old = (int) get_option('bfnd_previous_home_id');
    if ($old && get_post_status($old)) { update_option('show_on_front', 'page'); update_option('page_on_front', $old); }
    wp_safe_redirect(admin_url('tools.php?page=bfnd-import'));
    exit;
}

function bfnd_import_action() {
    if (!current_user_can('manage_options')) { wp_die('Permission denied'); }
    $step = isset($_POST['step']) ? max(0, (int) $_POST['step']) : 0;
    check_admin_referer('bfnd_import_' . $step);
    $data = bfnd_manifest();
    if ($step < count($data['works'])) { bfnd_import_work($data['works'][$step]); }
    elseif ($step === count($data['works'])) { bfnd_import_lifestyle($data['lifestyle']); }
    elseif ($step < count($data['works']) + 1 + count($data['courses'])) { bfnd_import_course($data['courses'][$step - count($data['works']) - 1]); }
    elseif ($step === count($data['works']) + 1 + count($data['courses'])) { bfnd_import_brand_image(); }
    wp_safe_redirect(admin_url('tools.php?page=bfnd-import&step=' . ($step + 1) . '&run=1'));
    exit;
}

function bfnd_import_attachment($path, $parent, $alt) {
    global $wpdb;
    $source = BFND_DIR . ltrim($path, '/');
    if (!is_file($source)) { return 0; }
    $existing = $wpdb->get_var($wpdb->prepare("SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='_bfnd_source' AND meta_value=%s LIMIT 1", $path));
    if ($existing) { return (int) $existing; }
    $upload = wp_upload_dir();
    if (!empty($upload['error'])) { return 0; }
    $name = wp_unique_filename($upload['path'], basename($source));
    $target = trailingslashit($upload['path']) . $name;
    if (!copy($source, $target)) { return 0; }
    $type = wp_check_filetype($name);
    $id = wp_insert_attachment(array('post_mime_type' => $type['type'], 'post_title' => pathinfo($name, PATHINFO_FILENAME), 'post_status' => 'inherit', 'post_parent' => $parent), $target, $parent);
    if (is_wp_error($id)) { return 0; }
    require_once ABSPATH . 'wp-admin/includes/image.php';
    wp_update_attachment_metadata($id, wp_generate_attachment_metadata($id, $target));
    update_post_meta($id, '_wp_attachment_image_alt', $alt);
    update_post_meta($id, '_bfnd_source', $path);
    return (int) $id;
}

function bfnd_find_seed($slug, $type) {
    $posts = get_posts(array('post_type' => $type, 'post_status' => 'any', 'meta_key' => '_bfnd_seed', 'meta_value' => $slug, 'numberposts' => 1));
    return $posts ? $posts[0]->ID : 0;
}

function bfnd_seed_content() {
    $data = bfnd_manifest();
    foreach (array('作品', '工藝', '課程', '活動', '品牌') as $name) {
        if (!term_exists($name, 'category')) { wp_insert_term($name, 'category'); }
    }
    foreach ($data['works'] as $work) { bfnd_import_work($work, false); }
    bfnd_import_lifestyle($data['lifestyle'], false);
    foreach ($data['courses'] as $course) { bfnd_import_course($course, false); }
}

function bfnd_import_work($work, $with_media = true) {
    $id = bfnd_find_seed($work['slug'], 'bf_work');
    if (!$id) {
        $id = wp_insert_post(array('post_type' => 'bf_work', 'post_status' => 'private', 'post_name' => $work['slug'],
            'post_title' => $work['title'], 'post_content' => $work['story'], 'post_excerpt' => $work['tagline'],
            'menu_order' => array_search($work['slug'], array_column(bfnd_manifest()['works'], 'slug'), true),
            'meta_input' => array('_bfnd_seed' => $work['slug'])));
        if (is_wp_error($id)) { return; }
        foreach (array('english', 'tagline', 'type', 'material', 'size', 'craft', 'custom') as $field) {
            update_post_meta($id, '_bfnd_' . $field, $work[$field]);
        }
        update_post_meta($id, '_bfnd_image', $work['image']);
        update_post_meta($id, '_bfnd_gallery', $work['gallery']);
        update_post_meta($id, '_bfnd_featured', array_search($work['slug'], array_column(bfnd_manifest()['works'], 'slug'), true) < 4 ? '1' : '0');
        $cats = array('tables' => '桌・茶几', 'seating' => '椅・凳', 'storage' => '櫃體・收納', 'islands' => '中島・生活機能');
        $term = term_exists($cats[$work['category']], 'bf_work_cat');
        if (!$term) { $term = wp_insert_term($cats[$work['category']], 'bf_work_cat', array('slug' => $work['category'])); }
        if (!is_wp_error($term)) { wp_set_post_terms($id, array((int) (is_array($term) ? $term['term_id'] : $term)), 'bf_work_cat'); }
        $series = term_exists($work['series'], 'bf_series');
        if (!$series) { $series = wp_insert_term($work['series'], 'bf_series'); }
        if (!is_wp_error($series)) { wp_set_post_terms($id, array((int) (is_array($series) ? $series['term_id'] : $series)), 'bf_series'); }
    }
    if (!$with_media) { return; }
    $ids = array();
    foreach ($work['gallery'] as $path) {
        $attachment = bfnd_import_attachment($path, $id, $work['title'] . '・' . $work['type'] . '・' . $work['material']);
        if ($attachment) { $ids[] = $attachment; }
    }
    if ($ids) {
        if (!has_post_thumbnail($id)) { set_post_thumbnail($id, $ids[0]); }
        if (!get_post_meta($id, '_bfnd_gallery_override', true)) { update_post_meta($id, '_bfnd_gallery_ids', $ids); }
    }
}

function bfnd_import_lifestyle($life, $with_media = true) {
    $id = bfnd_find_seed('alba-canvas', 'bf_lifestyle');
    if (!$id) {
        $id = wp_insert_post(array('post_type' => 'bf_lifestyle', 'post_status' => 'private', 'post_name' => 'alba-canvas',
            'post_title' => $life['title'], 'post_excerpt' => '一器承日常，一圓納天地。',
            'post_content' => '當晨曦化為曲線，自然紋理凝聚成器。竹的細緻清雅、木的溫潤厚實，各自保留獨有的生命紋理。',
            'meta_input' => array('_bfnd_seed' => 'alba-canvas', '_bfnd_english' => $life['english'])));
        if (is_wp_error($id)) { return; }
        update_post_meta($id, '_bfnd_image', $life['gallery'][0]);
        update_post_meta($id, '_bfnd_gallery', $life['gallery']);
    }
    if (!$with_media) { return; }
    $ids = array();
    foreach ($life['gallery'] as $path) {
        $attachment = bfnd_import_attachment($path, $id, $life['title'] . '・托盤');
        if ($attachment) { $ids[] = $attachment; }
    }
    if ($ids) { if (!has_post_thumbnail($id)) { set_post_thumbnail($id, $ids[0]); } if (!get_post_meta($id, '_bfnd_gallery_override', true)) { update_post_meta($id, '_bfnd_gallery_ids', $ids); } }
}

function bfnd_import_course($course, $with_media = true) {
    $id = bfnd_find_seed($course['slug'], 'bf_course');
    if (!$id) {
        $id = wp_insert_post(array('post_type' => 'bf_course', 'post_status' => 'private', 'post_name' => $course['slug'],
            'post_title' => $course['title'], 'post_excerpt' => '從一堂課開始，親手理解木作。',
            'post_content' => '課程內容、開課日期與報名方式請以品牌公告為準。',
            'meta_input' => array('_bfnd_seed' => $course['slug'], '_bfnd_mode' => $course['mode'],
                '_bfnd_duration' => $course['duration'], '_bfnd_price' => $course['price'], '_bfnd_level' => $course['level'])));
        if (is_wp_error($id)) { return; }
        update_post_meta($id, '_bfnd_image', $course['image']);
    }
    if (!$with_media) { return; }
    $poster = bfnd_import_attachment($course['image'], $id, $course['title'] . '・課程簡章');
    $editorial = '/assets/courses/editorial-' . $course['slug'] . '.png';
    $photo = bfnd_import_attachment($editorial, $id, $course['title'] . '・木作情境示意圖');
    $current = get_post_thumbnail_id($id);
    if ($photo && (!$current || $current === $poster)) { set_post_thumbnail($id, $photo); }
    if ($poster && !get_post_meta($id, '_bfnd_gallery_override', true)) { update_post_meta($id, '_bfnd_gallery_ids', array($poster)); }
}

function bfnd_import_brand_image() {
    $id = bfnd_import_attachment('/assets/brand/home-editorial.png', 0, '木韻 MUYO 胡桃木餐桌椅・情境示意圖');
    if ($id) { update_option('bfnd_home_hero_id', $id); }
    $school = bfnd_import_attachment('/assets/brand/school-editorial.png', 0, '木作學堂・木工實作情境示意圖');
    if ($school) { update_option('bfnd_school_hero_id', $school); }
    $lecture = bfnd_import_attachment('/assets/brand/real-lecture.webp', 0, '飛熊入夢・木工教育實際授課照片');
    if ($lecture) { update_option('bfnd_real_lecture_id', $lecture); }
    $forest = bfnd_import_attachment('/assets/brand/forest-editorial.png', 0, '永續合作・森林情境示意圖');
    if ($forest) { update_option('bfnd_forest_id', $forest); }
    $manifesto = bfnd_import_attachment('/assets/brand/wood-ring-editorial.png', 0, '木・人・空間・未來・年輪情境示意圖');
    if ($manifesto) { update_option('bfnd_manifesto_id', $manifesto); }
    $lifestyle = bfnd_import_attachment('/assets/brand/lifestyle-editorial.png', 0, '生活木作・工作檯木器物情境示意圖');
    if ($lifestyle) { update_option('bfnd_lifestyle_hero_id', $lifestyle); }
    $selecting = bfnd_import_attachment('/assets/brand/selecting-wood-editorial.png', 0, '生活木作・選材情境示意圖');
    if ($selecting) { update_option('bfnd_selecting_wood_id', $selecting); }
    $finishing = bfnd_import_attachment('/assets/brand/finishing-editorial.png', 0, '生活木作・上油情境示意圖');
    if ($finishing) { update_option('bfnd_finishing_id', $finishing); }
    $cnc = bfnd_import_attachment('/assets/courses/editorial-cnc.png', 0, '生活木作・加工情境示意圖');
    if ($cnc) { update_option('bfnd_process_cnc_id', $cnc); }
    $sanding = bfnd_import_attachment('/assets/courses/editorial-beginner.png', 0, '生活木作・打磨情境示意圖');
    if ($sanding) { update_option('bfnd_process_sanding_id', $sanding); }
    foreach (array('office' => '企業辦公', 'cafe' => '商業空間', 'library' => '公共空間') as $key => $label) {
        $photo = bfnd_import_attachment('/assets/brand/collab-' . $key . '-editorial.png', 0, $label . '・合作情境示意圖');
        if ($photo) { update_option('bfnd_collab_' . $key . '_id', $photo); }
    }
}

function bfnd_inquiry_action() {
    if (!isset($_POST['bfnd_inquiry_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['bfnd_inquiry_nonce'])), 'bfnd_inquiry')) { wp_die('表單已逾時，請返回重新送出。'); }
    if (!empty($_POST['website'])) { wp_safe_redirect(bfnd_page_url('collaboration')); exit; }
    if (!empty($_FILES['reference']['name'])) { wp_die('表單目前不接受附件。請移除附件後重新送出。', '附件未送出', array('response' => 400)); }
    $name = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));
    $contact = sanitize_text_field(wp_unslash($_POST['contact'] ?? ''));
    if (!$name || !$contact) { wp_die('請填寫姓名與聯絡方式。'); }
    $work_id = absint($_POST['work_id'] ?? 0);
    $work = $work_id && get_post_type($work_id) === 'bf_work' ? get_the_title($work_id) : '';
    $message = sanitize_textarea_field(wp_unslash($_POST['message'] ?? ''));
    $id = wp_insert_post(array('post_type' => 'bf_inquiry', 'post_status' => 'private', 'post_title' => '詢問｜' . $name . ($work ? '｜' . $work : ''), 'post_content' => $message));
    if (!$id || is_wp_error($id)) { wp_die('送出失敗，請稍後重試。'); }
    $fields = array('contact' => $contact, 'work' => $work);
    foreach (array('dimension', 'space', 'budget') as $key) {
        $fields[$key] = sanitize_text_field(wp_unslash($_POST[$key] ?? ''));
    }
    foreach ($fields as $key => $value) {
        update_post_meta($id, '_bfnd_' . $key, $value);
    }
    $mail_body = "收到新的訂製與合作詢問。\n請登入 WordPress 後台查看：" . admin_url('post.php?post=' . $id . '&action=edit');
    $mail_sent = wp_mail(get_option('admin_email'), '飛熊入夢｜新詢問通知', $mail_body);
    update_post_meta($id, '_bfnd_mail_status', $mail_sent ? 'accepted' : 'failed');
    wp_safe_redirect(add_query_arg('sent', '1', bfnd_page_url('collaboration')));
    exit;
}
