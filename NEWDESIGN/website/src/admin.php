<?php
if (!defined('ABSPATH')) { exit; }

function bfnd_register_types() {
    $common = array('public' => true, 'show_in_rest' => true, 'has_archive' => true, 'rewrite' => array('slug' => 'works'),
        'supports' => array('title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'page-attributes'));
    register_post_type('bf_work', array_merge($common, array('labels' => array('name' => '家具作品', 'singular_name' => '家具作品', 'add_new_item' => '新增家具作品', 'edit_item' => '編輯家具作品'), 'menu_icon' => 'dashicons-art')));
    register_post_type('bf_lifestyle', array_merge($common, array('labels' => array('name' => '生活木作', 'singular_name' => '生活木作', 'add_new_item' => '新增生活木作'), 'rewrite' => array('slug' => 'lifestyle-works'), 'menu_icon' => 'dashicons-palmtree')));
    register_post_type('bf_course', array_merge($common, array('labels' => array('name' => '木作課程', 'singular_name' => '木作課程', 'add_new_item' => '新增木作課程'), 'rewrite' => array('slug' => 'woodworking-course'), 'menu_icon' => 'dashicons-welcome-learn-more')));
    register_post_type('bf_inquiry', array('labels' => array('name' => '作品、課程與合作詢問', 'singular_name' => '詢問', 'edit_item' => '查看詢問'),
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
    if ($type === 'bf_work') { return array_merge($shared, array('type' => '作品類型', 'material' => '木材／材質', 'material_options' => '可選木材／材質（每行一項，順序即前台顯示順序）', 'size' => '參考尺寸（請填經確認的尺寸）', 'size_options' => '可選尺寸（每行一項；需先勾選尺寸資料已核對才會公開）', 'size_confirmed' => '尺寸資料已核對，可以在網站顯示', 'size_adjustable' => '部分作品可依空間需求調整尺寸（勾選後顯示說明）', 'craft' => '製作方式', 'craft_image_id' => '此件作品的實際製作過程照片 ID（未填即隱藏製作區）', 'finish' => '表面處理', 'featured' => '首頁精選作品（1 顯示，0 不顯示）', 'related_ids' => '相關作品 ID（依序，以逗號分隔）', 'gallery_ids' => '作品圖片；第 1 張主圖、第 2 張 STORY、第 3 張起 DETAILS（可在媒體庫排序）')); }
    if ($type === 'bf_course') { return array_merge($shared, array('track' => '課程軌道：level1 / level2 / level3 / program / specialist / membership', 'mode' => '課程模式：onsite / online', 'features' => '課程特色（每行一項）', 'audience' => '適合對象（每行一項）', 'learning' => '學習內容（每行一項）', 'tools' => '使用工具（每行一項）', 'outcomes' => '完成成果（每行一項）', 'duration' => '課程時數', 'price_label' => '價格標籤（例如：優惠價）', 'price' => '課程顯示價格（尚未綁商品時使用；正式售價以 WooCommerce 商品為準）', 'level' => '程度標籤', 'schedule' => '開課梯次資訊（文字顯示；多梯次請使用 WooCommerce 變化商品）', 'notices' => '注意事項（每行一項）', 'woo_id' => 'WooCommerce 課程／會員方案商品', 'course_access_url' => '線上課程 YouTube 連結（付款後寄給購買者）', 'registration_button' => '報名按鈕文字（選填）', 'gallery_ids' => '課程照片／簡章（第一張作簡章；媒體庫可編輯 ALT）')); }
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
        if ($post->post_type === 'bf_course' && $key === 'course_access_url') {
            echo '<p><label for="bfnd_course_access_url"><strong>' . esc_html($label) . '</strong></label><br>';
            echo '<input style="width:100%;max-width:780px" type="url" id="bfnd_course_access_url" name="bfnd[course_access_url]" value="' . esc_attr((string) $value) . '" placeholder="https://www.youtube.com/watch?v=...">';
            echo '<br><span class="description">僅「線上」課程會在訂單付款完成後寄到訂單 Email，不會公開顯示。若 YouTube 設為「私人」，還需由業主將購買者 Google 帳號加入觀看權限；只寄網址不會自動授權。若設為「不公開」，持有連結者可觀看。</span></p>';
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
        if ($type === 'bf_course' && $key === 'woo_id') { $value = absint($raw_value); }
        if ($type === 'bf_course' && $key === 'course_access_url') { $value = bfnd_sanitize_course_access_url($raw_value); }
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

function bfnd_migrate_banner_carousels() {
    if (get_option('bfnd_banner_carousels_v1')) { return; }
    $defaults = array(
        'home' => array(
            array('/assets/works/muyo/01.webp', '木韻 MUYO 胡桃木餐桌椅'),
            array('/assets/works/muwi/01.webp', '木翼 MUWI 胡桃木長桌'),
            array('/assets/works/musu/01.webp', '木序 MUSU 硬楓木長桌'),
        ),
        'furniture' => array(
            array('/assets/works/muyo/01.webp', '木韻 MUYO 胡桃木餐桌椅'),
            array('/assets/works/ridge-table/01.webp', '稜 RIDGE 實木餐桌'),
            array('/assets/works/muju/01.webp', '木聚 MUJU 栓木餐桌'),
        ),
        'lifestyle' => array(
            array('/assets/lifestyle/tray-01.webp', '晨露圓境托盤・木款'),
            array('/assets/lifestyle/tray-06.webp', '晨露圓境托盤・藍色款'),
            array('/assets/lifestyle/tray-13.webp', '晨露圓境托盤・作品細節'),
        ),
        'school' => array(
            array('/assets/brand/real-lecture.webp', '飛熊入夢木作教學現場'),
            array('/assets/works/warm/01.webp', '暖 WARM 實木圓凳作品'),
            array('/assets/works/muju/01.webp', '木聚 MUJU 栓木餐桌作品'),
        ),
        'collaboration' => array(
            array('/assets/works/muyo/01.webp', '木韻 MUYO 胡桃木餐桌椅作品'),
            array('/assets/works/ridge-table/01.webp', '稜 RIDGE 實木餐桌作品'),
            array('/assets/brand/real-lecture.webp', '飛熊入夢木作教學現場'),
        ),
    );
    $legacy_options = array(
        'home' => 'bfnd_home_hero_id',
        'furniture' => '',
        'lifestyle' => 'bfnd_lifestyle_hero_id',
        'school' => 'bfnd_school_hero_id',
        'collaboration' => 'bfnd_home_hero_id',
    );
    foreach ($defaults as $key => $sources) {
        $option = 'bfnd_banner_slides_' . $key;
        if (is_array(get_option($option, null))) { continue; }
        $ids = array();
        $legacy_id = !empty($legacy_options[$key]) ? absint(get_option($legacy_options[$key])) : 0;
        if ($legacy_id && wp_attachment_is_image($legacy_id)) { $ids[] = $legacy_id; }
        foreach ($sources as $source) {
            $id = bfnd_import_attachment($source[0], 0, $source[1]);
            if ($id && !in_array($id, $ids, true)) { $ids[] = $id; }
        }
        if ($ids) { update_option($option, $ids, false); }
    }
    update_option('bfnd_banner_carousels_v1', 1, false);
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

function bfnd_migrate_course_editor_fields() {
    if (get_option('bfnd_course_editor_fields_v1')) { return; }
    foreach (bfnd_manifest()['courses'] as $course) {
        $id = bfnd_find_seed($course['slug'], 'bf_course');
        if (!$id) { continue; }
        foreach (array('track', 'features', 'audience') as $field) {
            if (!empty($course[$field]) && get_post_meta($id, '_bfnd_' . $field, true) === '') {
                update_post_meta($id, '_bfnd_' . $field, $course[$field]);
            }
        }
        if ($course['slug'] === 'cnc') {
            $duration = trim((string) get_post_meta($id, '_bfnd_duration', true));
            if (in_array(preg_replace('/\s+/u', '', $duration), array('6小時', '依最新公告'), true)) { update_post_meta($id, '_bfnd_duration', ''); }
            $price = trim((string) get_post_meta($id, '_bfnd_price', true));
            if (preg_replace('/[^0-9]/', '', $price) === '8800') { update_post_meta($id, '_bfnd_price', ''); }
        }
        if ($course['slug'] === 'open-studio') {
            $price = trim((string) get_post_meta($id, '_bfnd_price', true));
            if (preg_replace('/[^0-9]/', '', $price) === '1800') { update_post_meta($id, '_bfnd_price', ''); }
        }
    }
    update_option('bfnd_course_editor_fields_v1', 1, false);
}

function bfnd_migrate_course_catalog_v2() {
    if (get_option('bfnd_course_catalog_v2')) { return; }
    $courses = bfnd_manifest()['courses'];
    $slugs = array_column($courses, 'slug');
    $complete = true;
    foreach ($courses as $course) {
        $id = bfnd_find_seed($course['slug'], 'bf_course');
        if (!$id) {
            $matches = get_posts(array('post_type' => 'bf_course', 'post_status' => 'any', 'title' => $course['title'], 'numberposts' => 1));
            if ($matches) {
                $id = $matches[0]->ID;
                update_post_meta($id, '_bfnd_seed', $course['slug']);
            } else {
                bfnd_import_course($course, false);
                $id = bfnd_find_seed($course['slug'], 'bf_course');
            }
        }
        if (!$id) { $complete = false; continue; }
        $fields = array('track', 'features', 'audience', 'learning', 'tools', 'outcomes', 'duration', 'price_label', 'price', 'level', 'notices');
        foreach ($fields as $field) {
            $value = isset($course[$field]) ? trim((string) $course[$field]) : '';
            if ($value === '') { continue; }
            $current = trim((string) get_post_meta($id, '_bfnd_' . $field, true));
            $legacy_membership_plans = $course['slug'] === 'open-studio'
                && $field === 'features'
                && preg_replace('/\s+/u', '', $current) === '4次方案8次方案12次方案';
            $legacy_membership_audience = $course['slug'] === 'open-studio'
                && $field === 'audience'
                && $current === '適合已完成基礎課程或具相應木工能力者';
            $legacy_beginner_level = $course['slug'] === 'beginner'
                && $field === 'level'
                && $current === '初學者適合';
            $legacy_membership_level = $course['slug'] === 'open-studio'
                && $field === 'level'
                && $current === '進階創作';
            if ($current === '' || $legacy_membership_plans || $legacy_membership_audience || $legacy_beginner_level || $legacy_membership_level) {
                update_post_meta($id, '_bfnd_' . $field, $value);
            }
        }
        if (!empty($course['summary']) && get_post_field('post_excerpt', $id) === '從一堂課開始，親手理解木作。') {
            wp_update_post(array('ID' => $id, 'post_excerpt' => $course['summary']));
        }
        if (!empty($course['content']) && get_post_field('post_content', $id) === '課程內容、開課日期與報名方式請以品牌公告為準。') {
            wp_update_post(array('ID' => $id, 'post_content' => $course['content']));
        }
        $order = array_search($course['slug'], $slugs, true);
        if ($order !== false && (int) get_post_field('menu_order', $id) !== $order) {
            wp_update_post(array('ID' => $id, 'menu_order' => $order));
        }
    }
    if ($complete) { update_option('bfnd_course_catalog_v2', 1, false); }
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
            'post_title' => $course['title'], 'post_excerpt' => $course['summary'] ?? '從一堂課開始，親手理解木作。',
            'post_content' => $course['content'] ?? '課程內容、開課日期與報名方式請以品牌公告為準。',
            'menu_order' => array_search($course['slug'], array_column(bfnd_manifest()['courses'], 'slug'), true),
            'meta_input' => array('_bfnd_seed' => $course['slug'], '_bfnd_mode' => $course['mode'],
                '_bfnd_duration' => $course['duration'], '_bfnd_price_label' => $course['price_label'] ?? '',
                '_bfnd_price' => $course['price'], '_bfnd_level' => $course['level'],
                '_bfnd_track' => $course['track'] ?? '', '_bfnd_features' => $course['features'] ?? '',
                '_bfnd_audience' => $course['audience'] ?? '', '_bfnd_learning' => $course['learning'] ?? '',
                '_bfnd_tools' => $course['tools'] ?? '', '_bfnd_outcomes' => $course['outcomes'] ?? '',
                '_bfnd_notices' => $course['notices'] ?? '')));
        if (is_wp_error($id)) { return; }
        update_post_meta($id, '_bfnd_image', $course['image']);
    }
    if (!$with_media) { return; }
    $current = get_post_thumbnail_id($id);
    $current_source = $current ? (string) get_post_meta($current, '_bfnd_source', true) : '';
    $photo = bfnd_import_attachment('/assets/brand/real-lecture.webp', $id, $course['title'] . '・木作教學現場');
    if ($photo && (!$current || stripos($current_source, 'editorial') !== false)) { set_post_thumbnail($id, $photo); }
    if (!get_post_meta($id, '_bfnd_gallery_override', true)) { update_post_meta($id, '_bfnd_gallery_ids', array()); }
}

function bfnd_import_brand_image() {
    $id = bfnd_import_attachment('/assets/works/muyo/01.webp', 0, '木韻 MUYO 胡桃木餐桌椅');
    if ($id) { update_option('bfnd_home_hero_id', $id); }
    $school = bfnd_import_attachment('/assets/brand/real-lecture.webp', 0, '飛熊入夢木作教學現場');
    if ($school) { update_option('bfnd_school_hero_id', $school); }
    $lecture = bfnd_import_attachment('/assets/brand/real-lecture.webp', 0, '飛熊入夢・木工教育實際授課照片');
    if ($lecture) { update_option('bfnd_real_lecture_id', $lecture); }
    $manifesto = bfnd_import_attachment('/assets/works/muyo/02.webp', 0, '木韻 MUYO 胡桃木桌面細節');
    if ($manifesto) { update_option('bfnd_manifesto_id', $manifesto); }
    $lifestyle = bfnd_import_attachment('/assets/lifestyle/tray-01.webp', 0, '晨露圓境托盤・木款');
    if ($lifestyle) { update_option('bfnd_lifestyle_hero_id', $lifestyle); }
}

function bfnd_inquiry_action() {
    if (!isset($_POST['bfnd_inquiry_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['bfnd_inquiry_nonce'])), 'bfnd_inquiry')) { wp_die('表單已逾時，請返回重新送出。'); }
    if (!empty($_POST['website'])) { wp_safe_redirect(bfnd_page_url('collaboration')); exit; }
    if (!empty($_FILES['reference']['name'])) { wp_die('表單目前不接受附件。請移除附件後重新送出。', '附件未送出', array('response' => 400)); }
    $name = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));
    $contact = sanitize_text_field(wp_unslash($_POST['contact'] ?? ''));
    if (!$name || !$contact) { wp_die('請填寫姓名與聯絡方式。'); }
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
