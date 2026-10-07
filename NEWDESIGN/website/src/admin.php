<?php
if (!defined('ABSPATH')) { exit; }

function bfnd_register_types() {
    $common = array('public' => true, 'show_in_rest' => true, 'has_archive' => true, 'rewrite' => array('slug' => 'works'),
        'supports' => array('title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'page-attributes'));
    register_post_type('bf_work', array_merge($common, array('labels' => array('name' => '家具作品', 'singular_name' => '家具作品', 'add_new_item' => '新增家具作品', 'edit_item' => '編輯家具作品'), 'menu_icon' => 'dashicons-art')));
    register_post_type('bf_lifestyle', array_merge($common, array('labels' => array('name' => '生活木作', 'singular_name' => '生活木作', 'add_new_item' => '新增生活木作'), 'rewrite' => array('slug' => 'lifestyle-works'), 'menu_icon' => 'dashicons-palmtree')));
    register_post_type('bf_course', array_merge($common, array('labels' => array('name' => '木作課程', 'singular_name' => '木作課程', 'add_new_item' => '新增木作課程'), 'rewrite' => array('slug' => 'woodworking-course'), 'menu_icon' => 'dashicons-welcome-learn-more')));
    register_post_type('bf_inquiry', array('labels' => array('name' => '作品、課程與合作詢問', 'singular_name' => '詢問', 'edit_item' => '查看詢問'),
        'public' => false, 'show_ui' => true, 'show_in_rest' => false, 'menu_icon' => 'dashicons-email-alt', 'supports' => array('title', 'editor'),
        // Inquiries hold visitors' contact details, so only administrators may read or change them.
        'capabilities' => array_fill_keys(array('edit_post', 'read_post', 'delete_post', 'edit_posts', 'edit_others_posts', 'edit_private_posts', 'edit_published_posts', 'publish_posts', 'read_private_posts', 'delete_posts', 'delete_others_posts', 'delete_private_posts', 'delete_published_posts', 'create_posts'), 'manage_options'),
        'map_meta_cap' => false));
    register_taxonomy('bf_work_cat', 'bf_work', array('label' => '家具分類', 'hierarchical' => true, 'public' => true, 'show_in_rest' => true, 'rewrite' => array('slug' => 'furniture-category')));
    register_taxonomy('bf_series', 'bf_work', array('label' => '作品系列', 'hierarchical' => true, 'public' => true, 'show_in_rest' => true, 'rewrite' => array('slug' => 'furniture-series')));
}

function bfnd_fields($type) {
    $shared = array('english' => '英文名稱', 'tagline' => '一句話介紹', 'seo_title' => 'SEO Title', 'seo_description' => 'SEO Description');
    if ($type === 'bf_work') { return array_merge($shared, array('type' => '作品類型', 'material' => '木材／材質', 'material_options' => '可選木材／材質（每行一項，順序即前台顯示順序）', 'size' => '參考尺寸（請填經確認的尺寸）', 'size_options' => '可選尺寸（每行一項；需先勾選尺寸資料已核對才會公開）', 'size_confirmed' => '尺寸資料已核對，可以在網站顯示', 'size_adjustable' => '部分作品可依空間需求調整尺寸（勾選後顯示說明）', 'craft' => '製作方式', 'craft_image_id' => '此件作品的實際製作過程照片 ID（未填即隱藏製作區）', 'finish' => '表面處理', 'featured' => '首頁精選作品（1 顯示，0 不顯示）', 'related_ids' => '相關作品 ID（依序，以逗號分隔）', 'shop_product_id' => '對應的商店商品（選填；設定後作品頁顯示「前往選購」）', 'gallery_ids' => '作品圖片；第 1 張主圖、第 2 張 STORY、第 3 張起 DETAILS（可在媒體庫排序）')); }
    if ($type === 'bf_course') { return array_merge($shared, array('track' => '課程軌道：level1 / level2 / level3 / program / specialist / membership', 'mode' => '課程模式：onsite / online', 'features' => '課程特色（每行一項）', 'audience' => '適合對象（每行一項）', 'learning' => '學習內容（每行一項）', 'tools' => '使用工具（每行一項）', 'outcomes' => '完成成果（每行一項）', 'duration' => '課程時數', 'price_label' => '價格標籤（例如：優惠價）', 'price' => '課程顯示價格（尚未綁商品時使用；正式售價以 WooCommerce 商品為準）', 'level' => '程度標籤', 'schedule' => '開課梯次資訊（文字顯示；多梯次請使用 WooCommerce 變化商品）', 'notices' => '注意事項（每行一項）', 'woo_id' => 'WooCommerce 課程／會員方案商品', 'registration_button' => '報名按鈕文字（選填）', 'gallery_ids' => '課程照片／簡章（第一張作簡章；媒體庫可編輯 ALT）')); }
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
    echo '<p>主圖請使用右側「特色圖片」。作品故事與課程介紹請編輯上方本文；作品分類、系列可在右側管理。作品圖片、課程照片與簡章可從媒體庫選取排序；附件 ALT 文字在媒體庫編輯。</p>';
    foreach (bfnd_fields($post->post_type) as $key => $label) {
        $value = get_post_meta($post->ID, '_bfnd_' . $key, true);
        if ($post->post_type === 'bf_course' && $key === 'woo_id') {
            $current_product_id = absint($value);
            echo '<div class="bfnd-course-product-field"><label for="bfnd_woo_id"><strong>' . esc_html($label) . '</strong></label><br>';
            if (!function_exists('wc_get_product')) {
                echo '<select id="bfnd_woo_id" name="bfnd[woo_id]" disabled style="width:100%;max-width:780px"><option>請先啟用 WooCommerce</option></select></div>';
            } else {
                echo '<select id="bfnd_woo_id" name="bfnd[woo_id]" style="width:100%;max-width:780px"><option value="">尚未綁定</option>';
                $selectable_ids = array();
                foreach (bfnd_course_products($post->ID) as $product) {
                    $selectable_ids[] = $product->get_id();
                    $type_label = $product->is_type('variable') ? '變化商品（選梯次／方案）' : '單一商品（加入購物車）';
                    $price_label = wp_strip_all_tags($product->get_price_html());
                    $option_label = $product->get_name() . '（' . $type_label . ($price_label !== '' ? '｜' . $price_label : '') . '）';
                    echo '<option value="' . esc_attr($product->get_id()) . '" ' . selected($current_product_id, $product->get_id(), false) . '>' . esc_html($option_label) . '</option>';
                }
                if ($current_product_id && !in_array($current_product_id, $selectable_ids, true)) {
                    $current_product = wc_get_product($current_product_id);
                    $legacy_name = $current_product ? $current_product->get_name() : '已不存在的商品';
                    echo '<option value="' . esc_attr($current_product_id) . '" selected>目前連結：' . esc_html($legacy_name) . '（需改設為虛擬課程商品）</option>';
                }
                echo '</select><p class="description">先到「商品」編輯簡單或變化商品，勾選「木作課程／會員方案商品」並儲存；系統會自動設為虛擬商品及隱藏於一般商店清單。變化商品可設定不同梯次、方案及名額；單一商品採 WooCommerce 售價與庫存。每個商品只能綁一堂課。</p></div>';
            }
            continue;
        }
        if ($post->post_type === 'bf_work' && $key === 'shop_product_id') {
            bfnd_work_shop_product_field(absint($value), $label);
            continue;
        }
        if ($key === 'gallery_ids') {
            $ids = is_array($value) ? $value : array();
            echo '<div class="bfnd-gallery-admin"><p><strong>' . esc_html($label) . '</strong>：可從媒體庫選取、拖曳排序或移除。</p><input type="hidden" id="bfnd_gallery_ids" name="bfnd[gallery_ids]" value="' . esc_attr(implode(',', $ids)) . '"><div id="bfnd-gallery-list">';
            foreach ($ids as $id) { $url = wp_get_attachment_image_url((int) $id, 'thumbnail'); if ($url) { echo '<div draggable="true" data-id="' . esc_attr($id) . '"><img src="' . esc_url($url) . '" alt=""><button type="button" class="bfnd-gallery-remove" aria-label="移除照片">×</button></div>'; } }
            echo '</div><button id="bfnd-gallery-pick" class="button" type="button">從媒體庫選取照片</button></div>';
            continue;
        }
        if ($key === 'size_adjustable' || $key === 'size_confirmed') {
            echo '<p><label><input type="checkbox" name="bfnd[' . esc_attr($key) . ']" value="1" ' . checked((string) $value, '1', false) . '> <strong>' . esc_html($label) . '</strong></label></p>';
            continue;
        }
        if (is_array($value)) { $value = implode(',', $value); }
        echo '<p><label for="bfnd_' . esc_attr($key) . '"><strong>' . esc_html($label) . '</strong></label><br>';
        if (in_array($key, array('features', 'audience', 'learning', 'tools', 'outcomes', 'schedule', 'notices', 'material_options', 'size_options'), true)) {
            echo '<textarea style="width:100%;max-width:780px" rows="4" id="bfnd_' . esc_attr($key) . '" name="bfnd[' . esc_attr($key) . ']">' . esc_textarea((string) $value) . '</textarea></p>';
            if ($key === 'material_options' || $key === 'size_options') {
                echo '<p class="description">每行一個可供客戶選擇的規格；新增、刪除或調整行順序即可管理選項。尺寸選項只會在尺寸核對開關啟用時公開。</p>';
            }
        } else {
            echo '<input style="width:100%;max-width:780px" type="text" id="bfnd_' . esc_attr($key) . '" name="bfnd[' . esc_attr($key) . ']" value="' . esc_attr((string) $value) . '"></p>';
        }
    }
}

function bfnd_inquiry_meta_box($post) {
    foreach (array('contact' => '聯絡方式', 'work' => '詢問作品', 'work_id' => '作品資料 ID', 'material_choice' => '選擇木材／材質', 'size_choice' => '選擇尺寸', 'dimension' => '其他尺寸需求', 'space' => '使用空間', 'budget' => '預算／其他需求', 'attachment' => '參考附件') as $key => $label) {
        $value = get_post_meta($post->ID, '_bfnd_' . $key, true);
        echo '<p><strong>' . esc_html($label) . '</strong>：';
        if ($key === 'attachment' && $value) { echo '<a href="' . esc_url($value) . '" target="_blank" rel="noopener noreferrer">查看附件</a>'; }
        elseif ($key === 'work_id' && absint($value) && get_post_type(absint($value)) === 'bf_work') { $edit_link = get_edit_post_link(absint($value)); echo $edit_link ? '<a href="' . esc_url($edit_link) . '">' . esc_html(get_the_title(absint($value))) . '</a>' : esc_html(get_the_title(absint($value))); }
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
        $raw_value = isset($input[$key]) && is_scalar($input[$key]) ? (string) $input[$key] : '';
        $value = in_array($key, array('features', 'audience', 'learning', 'tools', 'outcomes', 'schedule', 'notices', 'material_options', 'size_options'), true)
            ? sanitize_textarea_field($raw_value)
            : sanitize_text_field($raw_value);
        if (($type === 'bf_course' && $key === 'woo_id') || ($type === 'bf_work' && $key === 'shop_product_id')) { $value = absint($raw_value); }
        if ($key === 'gallery_ids' || $key === 'related_ids') { $value = array_values(array_filter(array_map('absint', explode(',', $value)))); }
        if ($key === 'featured') { $value = $value === '1' ? '1' : '0'; }
        if ($key === 'size_adjustable' || $key === 'size_confirmed') { $value = $value === '1' ? '1' : '0'; }
        if ($key === 'material_options' || $key === 'size_options') { $value = bfnd_normalize_work_option_text($value); }
        update_post_meta($post_id, '_bfnd_' . $key, $value);
        if ($key === 'gallery_ids') { update_post_meta($post_id, '_bfnd_gallery_override', '1'); }
    }
}

function bfnd_admin_enqueue($hook) {
    if (!in_array($hook, array('post.php', 'post-new.php'), true)) { return; }
    $screen = get_current_screen();
    if (!$screen || !in_array($screen->post_type, array('bf_work', 'bf_course', 'bf_lifestyle'), true)) { return; }
    wp_enqueue_media();
    wp_enqueue_style('bfnd-admin', bfnd_asset('public/admin.css'), array(), '0.1.1');
    wp_enqueue_script('bfnd-admin', bfnd_asset('public/admin.js'), array('jquery'), '0.1.1', true);
}

function bfnd_inquiry_limits() {
    return array('name' => 80, 'contact' => 160, 'dimension' => 200, 'space' => 200, 'budget' => 300, 'material_choice' => 200, 'size_choice' => 200, 'message' => 3000);
}

function bfnd_inquiry_check_lengths() {
    foreach (bfnd_inquiry_limits() as $field => $max) {
        $raw = $_POST[$field] ?? '';
        if (!is_scalar($raw)) { continue; }
        if (mb_strlen(wp_unslash((string) $raw), 'UTF-8') > $max) {
            wp_die('部分欄位內容過長，請精簡後重新送出（留言最多 ' . bfnd_inquiry_limits()['message'] . ' 字）。', '內容過長', array('response' => 400, 'back_link' => true));
        }
    }
}

function bfnd_inquiry_client_ip() {
    $ip = isset($_SERVER['HTTP_CF_CONNECTING_IP']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_CF_CONNECTING_IP'])) : '';
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
    }
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : 'unknown';
}

// Per-visitor limit plus a site-wide cap; the cap still bounds a flood if the client IP header is spoofed.
function bfnd_inquiry_check_rate_limit() {
    $buckets = array(
        'bfnd_inq_ip_' . md5(bfnd_inquiry_client_ip()) => array(5, 10 * MINUTE_IN_SECONDS),
        'bfnd_inq_all' => array(40, HOUR_IN_SECONDS),
    );
    foreach ($buckets as $key => $rule) {
        $hits = get_transient($key);
        $hits = is_array($hits) ? $hits : array('count' => 0, 'expires' => time() + $rule[1]);
        if ($hits['count'] >= $rule[0]) {
            wp_die('送出次數過多，請稍後再試，或直接透過 LINE 與我們聯繫。', '請稍後再試', array('response' => 429, 'back_link' => true));
        }
        $hits['count']++;
        set_transient($key, $hits, max(1, $hits['expires'] - time()));
    }
}

function bfnd_inquiry_action() {
    if (!isset($_POST['bfnd_inquiry_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['bfnd_inquiry_nonce'])), 'bfnd_inquiry')) { wp_die('表單已逾時，請返回重新送出。'); }
    if (!empty($_POST['website'])) { wp_safe_redirect(bfnd_page_url('collaboration')); exit; }
    if (!empty($_FILES['reference']['name'])) { wp_die('表單目前不接受附件。請移除附件後重新送出。', '附件未送出', array('response' => 400)); }
    $name = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));
    $contact = sanitize_text_field(wp_unslash($_POST['contact'] ?? ''));
    if (!$name || !$contact) { wp_die('請填寫姓名與聯絡方式。'); }
    bfnd_inquiry_check_lengths();
    bfnd_inquiry_check_rate_limit();
    $work_id = absint($_POST['work_id'] ?? 0);
    if ($work_id && (get_post_type($work_id) !== 'bf_work' || get_post_status($work_id) !== 'publish')) {
        wp_die('作品資料已更新，請返回作品頁重新選擇後送出。', '請重新選擇作品', array('response' => 400));
    }
    $work = $work_id ? get_the_title($work_id) : '';
    $raw_material_choice = $_POST['material_choice'] ?? '';
    $raw_size_choice = $_POST['size_choice'] ?? '';
    $work_choices = array(
        'material_choice' => is_scalar($raw_material_choice) ? sanitize_text_field(wp_unslash((string) $raw_material_choice)) : '',
        'size_choice' => is_scalar($raw_size_choice) ? sanitize_text_field(wp_unslash((string) $raw_size_choice)) : '',
    );
    foreach ($work_choices as $field => $value) {
        $kind = $field === 'material_choice' ? 'material' : 'size';
        $configured = $work_id ? bfnd_work_option_values($work_id, $kind) : array();
        if (($configured && !in_array($value, $configured, true)) || (!$configured && $value !== '')) {
            wp_die('作品規格選項已更新，請返回作品頁重新選擇後送出。', '請重新選擇作品規格', array('response' => 400));
        }
    }
    $message = sanitize_textarea_field(wp_unslash($_POST['message'] ?? ''));
    $id = wp_insert_post(array('post_type' => 'bf_inquiry', 'post_status' => 'private', 'post_title' => '詢問｜' . $name . ($work ? '｜' . $work : ''), 'post_content' => $message));
    if (!$id || is_wp_error($id)) { wp_die('送出失敗，請稍後重試。'); }
    $fields = array('contact' => $contact, 'work' => $work, 'work_id' => $work_id) + $work_choices;
    foreach (array('dimension', 'space', 'budget') as $key) {
        $fields[$key] = sanitize_text_field(wp_unslash($_POST[$key] ?? ''));
    }
    foreach ($fields as $key => $value) {
        update_post_meta($id, '_bfnd_' . $key, $value);
    }
    $mail_body = "收到新的作品、課程與合作詢問。\n請登入 WordPress 後台查看：" . admin_url('post.php?post=' . $id . '&action=edit');
    $mail_sent = wp_mail(get_option('admin_email'), '飛熊入夢｜新詢問通知', $mail_body);
    update_post_meta($id, '_bfnd_mail_status', $mail_sent ? 'accepted' : 'failed');
    wp_safe_redirect(add_query_arg('sent', '1', bfnd_page_url('collaboration')));
    exit;
}


// 飛熊日誌 articles are WordPress's native posts. Name them that in the admin menu and explain
// on the list screen where each part is edited, so editors don't look for articles under 網站版面
// (which only holds the journal page's header text and image).
add_filter('post_type_labels_post', function ($labels) {
    $labels->name = '飛熊日誌';
    $labels->menu_name = '飛熊日誌';
    $labels->all_items = '所有日誌文章';
    $labels->add_new_item = '新增日誌文章';
    $labels->edit_item = '編輯日誌文章';
    $labels->new_item = '新日誌文章';
    $labels->view_item = '檢視日誌文章';
    $labels->search_items = '搜尋日誌文章';
    $labels->not_found = '還沒有日誌文章。';
    return $labels;
});
add_action('admin_notices', function () {
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen || $screen->id !== 'edit-post') { return; }
    echo '<div class="notice notice-info"><p><strong>這裡是飛熊日誌的文章。</strong>按「新增日誌文章」撰寫，按「發佈」後會自動出現在飛熊日誌頁；最新 3 篇也會出現在首頁與合作頁。不想公開就移到回收桶，或改成草稿。</p>'
        . '<p>卡片會使用文章的<strong>精選圖片</strong>、<strong>第一個分類</strong>與發佈日期，請記得設定精選圖片。日誌頁最上方的標題、介紹文字與主圖，在左側 <a href="' . esc_url(bfnd_layout_admin_url('journal')) . '">飛熊日誌 → 日誌頁面設定</a>。</p></div>';
});

// Journal cards show only the first category; tags were unused and confused editors.
add_action('init', function () { unregister_taxonomy_for_object_type('post_tag', 'post'); }, 20);
