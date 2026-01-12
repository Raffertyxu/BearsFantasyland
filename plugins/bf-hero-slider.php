<?php
/**
 * Plugin Name: BF 首頁輪播與類別導覽
 * Description: 飛熊入夢 - Hero Banner 輪播 + 類別快速導覽列（支援文字位置設定）
 * Version: 1.1.0
 * Author: BEAR'S FANTASYLAND
 */

if (!defined('ABSPATH')) {
    exit;
}

// ========================================
// ADMIN MENU
// ========================================
function bf_hero_menu() {
    add_menu_page(
        '首頁輪播',
        '首頁輪播',
        'manage_options',
        'bf-hero-settings',
        'bf_hero_settings_page',
        'dashicons-slides',
        25
    );
}
add_action('admin_menu', 'bf_hero_menu');

// ========================================
// ADMIN SCRIPTS
// ========================================
function bf_hero_admin_scripts($hook) {
    if ($hook !== 'toplevel_page_bf-hero-settings') {
        return;
    }
    wp_enqueue_media();
    wp_enqueue_script('jquery-ui-sortable');
}
add_action('admin_enqueue_scripts', 'bf_hero_admin_scripts');

// ========================================
// REGISTER SETTINGS
// ========================================
function bf_hero_settings_init() {
    register_setting('bf_hero_options', 'bf_hero_banners', ['sanitize_callback' => 'bf_hero_sanitize_banners']);
    register_setting('bf_hero_options', 'bf_hero_autoplay');
    register_setting('bf_hero_options', 'bf_hero_interval');
    register_setting('bf_hero_options', 'bf_hero_show_arrows');
    register_setting('bf_hero_options', 'bf_hero_show_dots');
    register_setting('bf_hero_options', 'bf_hero_categories', ['sanitize_callback' => 'bf_hero_sanitize_categories']);
    register_setting('bf_hero_options', 'bf_hero_show_categories');
}
add_action('admin_init', 'bf_hero_settings_init');

function bf_hero_sanitize_banners($items) {
    if (!is_array($items)) return [];
    
    $sanitized = [];
    foreach ($items as $item) {
        if (!empty($item['image'])) {
            $sanitized[] = [
                'image' => esc_url_raw($item['image']),
                'url' => esc_url_raw($item['url'] ?? ''),
                'title' => sanitize_text_field($item['title'] ?? ''),
                'subtitle' => sanitize_text_field($item['subtitle'] ?? ''),
                'btn_text' => sanitize_text_field($item['btn_text'] ?? ''),
                'text_position' => sanitize_text_field($item['text_position'] ?? 'center'),
            ];
        }
    }
    return $sanitized;
}

function bf_hero_sanitize_categories($items) {
    if (!is_array($items)) return [];
    
    $sanitized = [];
    foreach ($items as $item) {
        if (!empty($item['image']) || !empty($item['title'])) {
            $sanitized[] = [
                'image' => esc_url_raw($item['image'] ?? ''),
                'title' => sanitize_text_field($item['title'] ?? ''),
                'url' => esc_url_raw($item['url'] ?? ''),
            ];
        }
    }
    return $sanitized;
}

// ========================================
// ADMIN PAGE
// ========================================
function bf_hero_settings_page() {
    if (!current_user_can('manage_options')) return;
    
    if (isset($_GET['settings-updated'])) {
        add_settings_error('bf_hero_messages', 'bf_hero_message', '設定已儲存！', 'updated');
    }
    
    $banners = get_option('bf_hero_banners', []);
    $autoplay = get_option('bf_hero_autoplay', '1');
    $interval = get_option('bf_hero_interval', '5');
    $show_arrows = get_option('bf_hero_show_arrows', '1');
    $show_dots = get_option('bf_hero_show_dots', '1');
    $categories = get_option('bf_hero_categories', []);
    $show_categories = get_option('bf_hero_show_categories', '1');
    
    ?>
    <style>
        :root {
            --bf-cream: #F5F1EB;
            --bf-sand: #E6D9CC;
            --bf-taupe: #C4A995;
            --bf-brown: #8A6754;
            --bf-brown-dark: #6d513f;
        }
        .bf-hero-wrap { max-width: 1000px; margin-top: 20px; }
        .bf-hero-header {
            background: linear-gradient(135deg, var(--bf-brown) 0%, var(--bf-brown-dark) 100%);
            color: #fff; padding: 25px 30px; border-radius: 8px 8px 0 0;
        }
        .bf-hero-header h1 { margin: 0; font-size: 22px; }
        .bf-hero-header p { margin: 8px 0 0; opacity: 0.85; font-size: 13px; }
        .bf-hero-body {
            background: #fff; padding: 30px;
            border: 1px solid var(--bf-sand); border-top: none; border-radius: 0 0 8px 8px;
        }
        .bf-section-title {
            font-size: 18px; color: var(--bf-brown); margin: 30px 0 15px;
            padding-bottom: 10px; border-bottom: 2px solid var(--bf-sand);
        }
        .bf-section-title:first-child { margin-top: 0; }
        .bf-item-box {
            background: var(--bf-cream); border: 1px solid var(--bf-sand);
            padding: 20px; margin: 15px 0; border-radius: 6px; cursor: move; position: relative;
        }
        .bf-item-box:hover { border-color: var(--bf-brown); }
        .bf-item-box.ui-sortable-placeholder {
            visibility: visible !important;
            background: rgba(138, 103, 84, 0.1);
            border: 2px dashed var(--bf-brown);
        }
        .bf-drag-handle { position: absolute; left: -10px; top: 50%; transform: translateY(-50%); color: var(--bf-taupe); font-size: 16px; cursor: move; }
        .bf-item-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .bf-item-header h3 { margin: 0; color: var(--bf-brown); font-size: 14px; }
        .bf-item-content { display: flex; gap: 20px; align-items: flex-start; }
        .bf-preview {
            width: 200px; height: 80px; background: #fff; border: 2px dashed #ccc;
            display: flex; align-items: center; justify-content: center; border-radius: 4px;
            overflow: hidden; flex-shrink: 0;
        }
        .bf-preview img { width: 100%; height: 100%; object-fit: cover; }
        .bf-preview-square { width: 80px; height: 80px; }
        .bf-fields { flex: 1; }
        .bf-field { margin-bottom: 12px; }
        .bf-field label { display: block; font-weight: 600; font-size: 12px; margin-bottom: 4px; color: #555; }
        .bf-field input, .bf-field select { width: 100%; padding: 8px 10px; border: 1px solid var(--bf-taupe); border-radius: 4px; }
        .bf-field input:focus, .bf-field select:focus { outline: none; border-color: var(--bf-brown); }
        .bf-upload-btn { margin-top: 5px; }
        .bf-remove-btn { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; cursor: pointer; padding: 5px 12px; border-radius: 4px; }
        .bf-add-btn {
            display: inline-flex; align-items: center; gap: 8px; padding: 12px 20px;
            background: var(--bf-cream); border: 2px dashed var(--bf-taupe); border-radius: 6px;
            color: var(--bf-brown); font-weight: 600; cursor: pointer; margin-top: 10px;
        }
        .bf-add-btn:hover { border-color: var(--bf-brown); background: #fff; }
        .bf-settings-table { margin-top: 20px; }
        .bf-settings-table th { text-align: left; padding: 10px 0; font-weight: 600; width: 150px; }
        .bf-settings-table td { padding: 10px 0; }
        .bf-shortcode-box {
            background: var(--bf-cream); padding: 15px 20px; border-radius: 6px;
            border: 1px solid var(--bf-sand); margin-top: 30px;
        }
        .bf-shortcode-box code {
            background: #fff; padding: 8px 15px; border-radius: 4px; font-size: 14px;
            color: var(--bf-brown); border: 1px solid var(--bf-taupe); display: inline-block; margin: 5px 10px 5px 0;
        }
        .bf-submit-btn {
            background: var(--bf-brown); color: #fff; border: none;
            padding: 14px 35px; font-size: 15px; font-weight: 600;
            border-radius: 6px; cursor: pointer; margin-top: 25px;
        }
        .bf-submit-btn:hover { background: var(--bf-brown-dark); }
        .bf-cat-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; }
        .bf-field-row { display: flex; gap: 10px; }
        .bf-field-row .bf-field { flex: 1; }
        .bf-position-select { max-width: 150px !important; }
        @media (max-width: 900px) { .bf-cat-grid { grid-template-columns: repeat(2, 1fr); } }
    </style>
    
    <div class="wrap bf-hero-wrap">
        <div class="bf-hero-header">
            <h1>🎠 首頁輪播與類別導覽</h1>
            <p>管理 Hero Banner 輪播圖片與類別快速導覽列（支援文字位置設定）</p>
        </div>
        
        <div class="bf-hero-body">
            <?php settings_errors('bf_hero_messages'); ?>
            
            <form action="options.php" method="post" id="bf-hero-form">
                <?php settings_fields('bf_hero_options'); ?>
                
                <h2 class="bf-section-title">🖼️ Hero Banner 輪播</h2>
                <p class="description">建議尺寸：<strong>1920 x 700 像素</strong>。每張 Banner 可設定文字位置（左下/右下/置中）</p>
                
                <div id="bf-banner-items">
                    <?php if (empty($banners)): ?>
                    <?php $banners = [['image' => '', 'title' => '', 'subtitle' => '', 'btn_text' => '', 'url' => '', 'text_position' => 'center']]; ?>
                    <?php endif; ?>
                    
                    <?php foreach ($banners as $index => $item): ?>
                    <div class="bf-item-box bf-banner-box" data-index="<?php echo $index; ?>">
                        <span class="bf-drag-handle">☰</span>
                        <div class="bf-item-header">
                            <h3 class="bf-banner-title">Banner #<?php echo ($index + 1); ?></h3>
                            <button type="button" class="bf-remove-btn bf-remove-banner" <?php echo count($banners) <= 1 ? 'disabled' : ''; ?>>移除</button>
                        </div>
                        <div class="bf-item-content">
                            <div class="bf-preview bf-banner-preview">
                                <?php if (!empty($item['image'])): ?>
                                    <img src="<?php echo esc_url($item['image']); ?>">
                                <?php else: ?>
                                    <span style="color:#999;">尚未選擇圖片</span>
                                <?php endif; ?>
                            </div>
                            <div class="bf-fields">
                                <div class="bf-field">
                                    <label>圖片網址</label>
                                    <input type="url" name="bf_hero_banners[<?php echo $index; ?>][image]" value="<?php echo esc_attr($item['image'] ?? ''); ?>" class="bf-banner-image" placeholder="https://...">
                                    <button type="button" class="button bf-upload-btn bf-upload-banner">📁 從媒體庫選擇</button>
                                </div>
                                <div class="bf-field-row">
                                    <div class="bf-field">
                                        <label>標題文字</label>
                                        <input type="text" name="bf_hero_banners[<?php echo $index; ?>][title]" value="<?php echo esc_attr($item['title'] ?? ''); ?>" class="bf-banner-field-title" placeholder="歡迎來飛熊入夢">
                                    </div>
                                    <div class="bf-field">
                                        <label>📍 文字位置</label>
                                        <select name="bf_hero_banners[<?php echo $index; ?>][text_position]" class="bf-banner-field-position bf-position-select">
                                            <option value="left" <?php selected($item['text_position'] ?? 'center', 'left'); ?>>↙️ 左下角</option>
                                            <option value="center" <?php selected($item['text_position'] ?? 'center', 'center'); ?>>⬇️ 置中</option>
                                            <option value="right" <?php selected($item['text_position'] ?? 'center', 'right'); ?>>↘️ 右下角</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="bf-field">
                                    <label>副標題</label>
                                    <input type="text" name="bf_hero_banners[<?php echo $index; ?>][subtitle]" value="<?php echo esc_attr($item['subtitle'] ?? ''); ?>" class="bf-banner-field-subtitle" placeholder="誠摯邀請您">
                                </div>
                                <div class="bf-field-row">
                                    <div class="bf-field">
                                        <label>按鈕文字</label>
                                        <input type="text" name="bf_hero_banners[<?php echo $index; ?>][btn_text]" value="<?php echo esc_attr($item['btn_text'] ?? ''); ?>" class="bf-banner-field-btn" placeholder="立即選購">
                                    </div>
                                    <div class="bf-field">
                                        <label>連結網址</label>
                                        <input type="url" name="bf_hero_banners[<?php echo $index; ?>][url]" value="<?php echo esc_attr($item['url'] ?? ''); ?>" class="bf-banner-url" placeholder="https://...">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <button type="button" class="bf-add-btn" id="bf-add-banner">➕ 新增 Banner</button>
                
                <table class="bf-settings-table">
                    <tr>
                        <th>自動播放</th>
                        <td><label><input type="checkbox" name="bf_hero_autoplay" value="1" <?php checked($autoplay, '1'); ?>> 啟用</label></td>
                    </tr>
                    <tr>
                        <th>播放間隔</th>
                        <td><input type="number" name="bf_hero_interval" value="<?php echo esc_attr($interval); ?>" min="2" max="30" style="width:80px;"> 秒</td>
                    </tr>
                    <tr>
                        <th>顯示箭頭</th>
                        <td><label><input type="checkbox" name="bf_hero_show_arrows" value="1" <?php checked($show_arrows, '1'); ?>> 顯示</label></td>
                    </tr>
                    <tr>
                        <th>顯示圓點</th>
                        <td><label><input type="checkbox" name="bf_hero_show_dots" value="1" <?php checked($show_dots, '1'); ?>> 顯示</label></td>
                    </tr>
                </table>
                
                <h2 class="bf-section-title">📂 類別快速導覽</h2>
                <table class="bf-settings-table" style="margin-bottom: 20px;">
                    <tr>
                        <th>啟用類別導覽</th>
                        <td><label><input type="checkbox" name="bf_hero_show_categories" value="1" <?php checked($show_categories, '1'); ?>> 顯示</label></td>
                    </tr>
                </table>
                
                <div id="bf-category-items" class="bf-cat-grid">
                    <?php 
                    if (empty($categories)) {
                        $categories = array_fill(0, 6, ['image' => '', 'title' => '', 'url' => '']);
                    }
                    foreach ($categories as $index => $cat): 
                    ?>
                    <div class="bf-item-box bf-cat-box" data-index="<?php echo $index; ?>">
                        <span class="bf-drag-handle">☰</span>
                        <div class="bf-item-header">
                            <h3 class="bf-cat-title">類別 #<?php echo ($index + 1); ?></h3>
                            <button type="button" class="bf-remove-btn bf-remove-cat">✕</button>
                        </div>
                        <div class="bf-item-content" style="flex-direction: column; gap: 10px;">
                            <div class="bf-preview bf-preview-square bf-cat-preview">
                                <?php if (!empty($cat['image'])): ?>
                                    <img src="<?php echo esc_url($cat['image']); ?>">
                                <?php else: ?>
                                    <span style="color:#999;">?</span>
                                <?php endif; ?>
                            </div>
                            <div class="bf-fields" style="width: 100%;">
                                <div class="bf-field">
                                    <input type="url" name="bf_hero_categories[<?php echo $index; ?>][image]" value="<?php echo esc_attr($cat['image'] ?? ''); ?>" class="bf-cat-image" placeholder="圖片網址">
                                    <button type="button" class="button bf-upload-btn bf-upload-cat" style="width:100%; margin-top:5px;">📁 選擇圖片</button>
                                </div>
                                <div class="bf-field">
                                    <input type="text" name="bf_hero_categories[<?php echo $index; ?>][title]" value="<?php echo esc_attr($cat['title'] ?? ''); ?>" class="bf-cat-field-title" placeholder="類別名稱">
                                </div>
                                <div class="bf-field">
                                    <input type="url" name="bf_hero_categories[<?php echo $index; ?>][url]" value="<?php echo esc_attr($cat['url'] ?? ''); ?>" class="bf-cat-url" placeholder="連結網址">
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <button type="button" class="bf-add-btn" id="bf-add-category">➕ 新增類別</button>
                
                <div class="bf-shortcode-box">
                    <strong>📋 短代碼</strong><br>
                    <code>[bf_hero]</code> 完整區塊<br>
                    <code>[bf_hero_banner]</code> 僅 Banner<br>
                    <code>[bf_hero_categories]</code> 僅類別列
                </div>
                
                <button type="submit" class="bf-submit-btn">💾 儲存設定</button>
            </form>
        </div>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        var bannerIndex = <?php echo count($banners); ?>;
        var catIndex = <?php echo count($categories); ?>;
        
        $('#bf-banner-items').sortable({ handle: '.bf-drag-handle', update: function() { updateBannerIndices(); } });
        $('#bf-category-items').sortable({ handle: '.bf-drag-handle', update: function() { updateCategoryIndices(); } });
        
        function updateBannerIndices() {
            $('#bf-banner-items .bf-banner-box').each(function(i) {
                var $box = $(this);
                $box.attr('data-index', i);
                $box.find('.bf-banner-title').text('Banner #' + (i + 1));
                $box.find('.bf-banner-image').attr('name', 'bf_hero_banners[' + i + '][image]');
                $box.find('.bf-banner-url').attr('name', 'bf_hero_banners[' + i + '][url]');
                $box.find('.bf-banner-field-title').attr('name', 'bf_hero_banners[' + i + '][title]');
                $box.find('.bf-banner-field-subtitle').attr('name', 'bf_hero_banners[' + i + '][subtitle]');
                $box.find('.bf-banner-field-btn').attr('name', 'bf_hero_banners[' + i + '][btn_text]');
                $box.find('.bf-banner-field-position').attr('name', 'bf_hero_banners[' + i + '][text_position]');
            });
            $('.bf-remove-banner').prop('disabled', $('.bf-banner-box').length <= 1);
        }
        
        function updateCategoryIndices() {
            $('#bf-category-items .bf-cat-box').each(function(i) {
                var $box = $(this);
                $box.attr('data-index', i);
                $box.find('.bf-cat-title').text('類別 #' + (i + 1));
                $box.find('.bf-cat-image').attr('name', 'bf_hero_categories[' + i + '][image]');
                $box.find('.bf-cat-field-title').attr('name', 'bf_hero_categories[' + i + '][title]');
                $box.find('.bf-cat-url').attr('name', 'bf_hero_categories[' + i + '][url]');
            });
        }
        
        $('#bf-add-banner').on('click', function() {
            var html = `
            <div class="bf-item-box bf-banner-box" data-index="${bannerIndex}">
                <span class="bf-drag-handle">☰</span>
                <div class="bf-item-header">
                    <h3 class="bf-banner-title">Banner #${bannerIndex + 1}</h3>
                    <button type="button" class="bf-remove-btn bf-remove-banner">移除</button>
                </div>
                <div class="bf-item-content">
                    <div class="bf-preview bf-banner-preview"><span style="color:#999;">尚未選擇圖片</span></div>
                    <div class="bf-fields">
                        <div class="bf-field">
                            <label>圖片網址</label>
                            <input type="url" name="bf_hero_banners[${bannerIndex}][image]" class="bf-banner-image" placeholder="https://...">
                            <button type="button" class="button bf-upload-btn bf-upload-banner">📁 從媒體庫選擇</button>
                        </div>
                        <div class="bf-field-row">
                            <div class="bf-field">
                                <label>標題文字</label>
                                <input type="text" name="bf_hero_banners[${bannerIndex}][title]" class="bf-banner-field-title" placeholder="標題">
                            </div>
                            <div class="bf-field">
                                <label>📍 文字位置</label>
                                <select name="bf_hero_banners[${bannerIndex}][text_position]" class="bf-banner-field-position bf-position-select">
                                    <option value="left">↙️ 左下角</option>
                                    <option value="center" selected>⬇️ 置中</option>
                                    <option value="right">↘️ 右下角</option>
                                </select>
                            </div>
                        </div>
                        <div class="bf-field">
                            <label>副標題</label>
                            <input type="text" name="bf_hero_banners[${bannerIndex}][subtitle]" class="bf-banner-field-subtitle" placeholder="副標題">
                        </div>
                        <div class="bf-field-row">
                            <div class="bf-field">
                                <label>按鈕文字</label>
                                <input type="text" name="bf_hero_banners[${bannerIndex}][btn_text]" class="bf-banner-field-btn" placeholder="按鈕">
                            </div>
                            <div class="bf-field">
                                <label>連結網址</label>
                                <input type="url" name="bf_hero_banners[${bannerIndex}][url]" class="bf-banner-url" placeholder="https://...">
                            </div>
                        </div>
                    </div>
                </div>
            </div>`;
            $('#bf-banner-items').append(html);
            bannerIndex++;
            updateBannerIndices();
            bindUploadButtons();
        });
        
        $('#bf-add-category').on('click', function() {
            var html = `
            <div class="bf-item-box bf-cat-box" data-index="${catIndex}">
                <span class="bf-drag-handle">☰</span>
                <div class="bf-item-header">
                    <h3 class="bf-cat-title">類別 #${catIndex + 1}</h3>
                    <button type="button" class="bf-remove-btn bf-remove-cat">✕</button>
                </div>
                <div class="bf-item-content" style="flex-direction: column; gap: 10px;">
                    <div class="bf-preview bf-preview-square bf-cat-preview"><span style="color:#999;">?</span></div>
                    <div class="bf-fields" style="width: 100%;">
                        <div class="bf-field">
                            <input type="url" name="bf_hero_categories[${catIndex}][image]" class="bf-cat-image" placeholder="圖片網址">
                            <button type="button" class="button bf-upload-btn bf-upload-cat" style="width:100%; margin-top:5px;">📁 選擇圖片</button>
                        </div>
                        <div class="bf-field">
                            <input type="text" name="bf_hero_categories[${catIndex}][title]" class="bf-cat-field-title" placeholder="類別名稱">
                        </div>
                        <div class="bf-field">
                            <input type="url" name="bf_hero_categories[${catIndex}][url]" class="bf-cat-url" placeholder="連結網址">
                        </div>
                    </div>
                </div>
            </div>`;
            $('#bf-category-items').append(html);
            catIndex++;
            updateCategoryIndices();
            bindUploadButtons();
        });
        
        $(document).on('click', '.bf-remove-banner', function() {
            if ($('.bf-banner-box').length > 1) {
                $(this).closest('.bf-banner-box').remove();
                updateBannerIndices();
            }
        });
        
        $(document).on('click', '.bf-remove-cat', function() {
            $(this).closest('.bf-cat-box').remove();
            updateCategoryIndices();
        });
        
        function bindUploadButtons() {
            $('.bf-upload-banner').off('click').on('click', function(e) {
                e.preventDefault();
                var $btn = $(this), $input = $btn.siblings('.bf-banner-image');
                var $preview = $btn.closest('.bf-item-content').find('.bf-banner-preview');
                var frame = wp.media({ title: '選擇 Banner 圖片', button: { text: '使用此圖片' }, multiple: false });
                frame.on('select', function() {
                    var url = frame.state().get('selection').first().toJSON().url;
                    $input.val(url);
                    $preview.html('<img src="' + url + '">');
                });
                frame.open();
            });
            
            $('.bf-upload-cat').off('click').on('click', function(e) {
                e.preventDefault();
                var $btn = $(this), $input = $btn.siblings('.bf-cat-image');
                var $preview = $btn.closest('.bf-item-content').find('.bf-cat-preview');
                var frame = wp.media({ title: '選擇類別圖片', button: { text: '使用此圖片' }, multiple: false });
                frame.on('select', function() {
                    var url = frame.state().get('selection').first().toJSON().url;
                    $input.val(url);
                    $preview.html('<img src="' + url + '">');
                });
                frame.open();
            });
        }
        
        $(document).on('change', '.bf-banner-image', function() {
            var url = $(this).val();
            $(this).closest('.bf-item-content').find('.bf-banner-preview').html(url ? '<img src="' + url + '">' : '<span style="color:#999;">尚未選擇圖片</span>');
        });
        
        $(document).on('change', '.bf-cat-image', function() {
            var url = $(this).val();
            $(this).closest('.bf-item-content').find('.bf-cat-preview').html(url ? '<img src="' + url + '">' : '<span style="color:#999;">?</span>');
        });
        
        bindUploadButtons();
    });
    </script>
    <?php
}

// ========================================
// FRONTEND CSS
// ========================================
function bf_hero_frontend_css() {
    return '
    .bf-hero-section {
        --bf-cream: #F5F1EB;
        --bf-sand: #E6D9CC;
        --bf-taupe: #C4A995;
        --bf-brown: #8A6754;
        --bf-text: #3D3D3D;
        --bf-font-display: "Noto Serif TC", serif;
        --bf-font-body: "Noto Sans TC", sans-serif;
        font-family: var(--bf-font-body);
    }
    
    .bf-banner-slider { position: relative; width: 100%; overflow: hidden; aspect-ratio: 1920 / 700; }
    .bf-banner-track { display: flex; transition: transform 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94); height: 100%; }
    .bf-banner-slide { min-width: 100%; height: 100%; position: relative; }
    .bf-banner-slide img { width: 100%; height: 100%; object-fit: cover; }
    .bf-banner-slide > a { display: block; width: 100%; height: 100%; }
    
    /* Text Overlay - Base */
    .bf-banner-overlay {
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        display: flex; flex-direction: column;
        padding: 40px 60px;
        color: #fff;
        text-shadow: 0 2px 10px rgba(0,0,0,0.4);
    }
    
    /* Position Variants */
    .bf-banner-overlay--center {
        align-items: center; justify-content: center; text-align: center;
    }
    .bf-banner-overlay--left {
        align-items: flex-start; justify-content: flex-end; text-align: left;
        padding-bottom: 80px;
    }
    .bf-banner-overlay--right {
        align-items: flex-end; justify-content: flex-end; text-align: right;
        padding-bottom: 80px;
    }
    
    .bf-banner-overlay-title {
        font-family: var(--bf-font-display);
        font-size: clamp(24px, 4vw, 48px);
        font-weight: 600;
        margin-bottom: 12px;
        letter-spacing: 0.1em;
    }
    .bf-banner-overlay-subtitle {
        font-size: clamp(14px, 2vw, 18px);
        margin-bottom: 20px;
        opacity: 0.9;
    }
    .bf-banner-overlay-btn {
        display: inline-block;
        padding: 12px 35px;
        background: var(--bf-brown);
        color: #fff;
        text-decoration: none;
        font-size: 14px;
        letter-spacing: 0.1em;
        transition: all 0.3s;
        border: none;
    }
    .bf-banner-overlay-btn:hover {
        background: #6d513f;
        transform: translateY(-2px);
    }
    
    /* Left position - add decorative line */
    .bf-banner-overlay--left .bf-banner-overlay-subtitle::before {
        content: "";
        display: inline-block;
        width: 30px;
        height: 2px;
        background: currentColor;
        margin-right: 10px;
        vertical-align: middle;
        opacity: 0.6;
    }
    
    /* Arrows */
    .bf-banner-arrow {
        position: absolute; top: 50%; transform: translateY(-50%);
        width: 50px; height: 80px; background: transparent;
        color: #fff; border: none; font-size: 36px;
        cursor: pointer; z-index: 10;
        text-shadow: 0 2px 5px rgba(0,0,0,0.3);
        opacity: 0.8; transition: opacity 0.3s;
    }
    .bf-banner-arrow:hover { opacity: 1; }
    .bf-banner-prev { left: 20px; }
    .bf-banner-next { right: 20px; }
    
    /* Dots */
    .bf-banner-dots {
        position: absolute; bottom: 25px; left: 50%; transform: translateX(-50%);
        display: flex; gap: 12px; z-index: 10;
    }
    .bf-banner-dot {
        width: 12px; height: 12px; border-radius: 50%;
        background: rgba(255,255,255,0.5); border: none;
        cursor: pointer; transition: all 0.3s;
    }
    .bf-banner-dot.active { background: #fff; transform: scale(1.2); }
    
    /* Category Bar */
    .bf-category-bar { background: #fff; padding: 30px 20px; overflow-x: auto; }
    .bf-category-grid { display: flex; gap: 30px; justify-content: center; max-width: 1200px; margin: 0 auto; }
    .bf-category-item { flex: 0 0 auto; text-align: center; text-decoration: none; color: var(--bf-text); transition: transform 0.3s; }
    .bf-category-item:hover { transform: translateY(-5px); }
    .bf-category-thumb { width: 80px; height: 80px; border-radius: 8px; overflow: hidden; margin: 0 auto 10px; background: var(--bf-cream); }
    .bf-category-thumb img { width: 100%; height: 100%; object-fit: cover; }
    .bf-category-name { font-size: 13px; font-weight: 500; white-space: nowrap; }
    
    @media (max-width: 768px) {
        .bf-banner-slider { aspect-ratio: 16/9; }
        .bf-banner-overlay { padding: 30px; }
        .bf-banner-overlay--left, .bf-banner-overlay--right { padding-bottom: 60px; }
        .bf-banner-arrow { width: 35px; font-size: 24px; }
        .bf-category-grid { justify-content: flex-start; padding: 0 10px; }
    }
    ';
}

// ========================================
// SHORTCODES
// ========================================
add_shortcode('bf_hero', 'bf_hero_shortcode');
function bf_hero_shortcode() {
    $show_categories = get_option('bf_hero_show_categories', '1') === '1';
    $output = '<style>' . bf_hero_frontend_css() . '</style>';
    $output .= '<div class="bf-hero-section">';
    $output .= bf_render_banner();
    if ($show_categories) $output .= bf_render_categories();
    $output .= '</div>';
    return $output;
}

add_shortcode('bf_hero_banner', 'bf_hero_banner_shortcode');
function bf_hero_banner_shortcode() {
    return '<style>' . bf_hero_frontend_css() . '</style><div class="bf-hero-section">' . bf_render_banner() . '</div>';
}

add_shortcode('bf_hero_categories', 'bf_hero_categories_shortcode');
function bf_hero_categories_shortcode() {
    return '<style>' . bf_hero_frontend_css() . '</style><div class="bf-hero-section">' . bf_render_categories() . '</div>';
}

function bf_render_banner() {
    $banners = get_option('bf_hero_banners', []);
    $banners = array_filter($banners, fn($b) => !empty($b['image']));
    if (empty($banners)) return '';
    
    $banners = array_values($banners);
    $autoplay = get_option('bf_hero_autoplay', '1') === '1';
    $interval = intval(get_option('bf_hero_interval', '5')) * 1000;
    $show_arrows = get_option('bf_hero_show_arrows', '1') === '1';
    $show_dots = get_option('bf_hero_show_dots', '1') === '1';
    $unique_id = 'bf-banner-' . uniqid();
    
    ob_start();
    ?>
    <div id="<?php echo esc_attr($unique_id); ?>" class="bf-banner-slider">
        <div class="bf-banner-track">
            <?php foreach ($banners as $index => $banner): ?>
            <?php 
                $position = $banner['text_position'] ?? 'center';
                $has_overlay = !empty($banner['title']) || !empty($banner['subtitle']) || !empty($banner['btn_text']);
            ?>
            <div class="bf-banner-slide">
                <?php if (!empty($banner['url']) && empty($banner['btn_text'])): ?>
                    <a href="<?php echo esc_url($banner['url']); ?>">
                <?php endif; ?>
                
                <img src="<?php echo esc_url($banner['image']); ?>" alt="<?php echo esc_attr($banner['title'] ?? ''); ?>">
                
                <?php if ($has_overlay): ?>
                <div class="bf-banner-overlay bf-banner-overlay--<?php echo esc_attr($position); ?>">
                    <?php if (!empty($banner['title'])): ?>
                        <div class="bf-banner-overlay-title"><?php echo esc_html($banner['title']); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($banner['subtitle'])): ?>
                        <div class="bf-banner-overlay-subtitle"><?php echo esc_html($banner['subtitle']); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($banner['btn_text']) && !empty($banner['url'])): ?>
                        <a href="<?php echo esc_url($banner['url']); ?>" class="bf-banner-overlay-btn"><?php echo esc_html($banner['btn_text']); ?></a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($banner['url']) && empty($banner['btn_text'])): ?>
                    </a>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        
        <?php if ($show_arrows && count($banners) > 1): ?>
        <button class="bf-banner-arrow bf-banner-prev">❮</button>
        <button class="bf-banner-arrow bf-banner-next">❯</button>
        <?php endif; ?>
        
        <?php if ($show_dots && count($banners) > 1): ?>
        <div class="bf-banner-dots">
            <?php foreach ($banners as $i => $b): ?>
            <button class="bf-banner-dot<?php echo $i === 0 ? ' active' : ''; ?>" data-index="<?php echo $i; ?>"></button>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <script>
    (function() {
        var c = document.getElementById('<?php echo esc_js($unique_id); ?>');
        if (!c) return;
        var track = c.querySelector('.bf-banner-track');
        var dots = c.querySelectorAll('.bf-banner-dot');
        var prev = c.querySelector('.bf-banner-prev');
        var next = c.querySelector('.bf-banner-next');
        var current = 0, total = <?php echo count($banners); ?>;
        var autoplay = <?php echo $autoplay ? 'true' : 'false'; ?>;
        var interval = <?php echo $interval; ?>;
        var timer;
        
        function go(i) {
            if (i < 0) i = total - 1;
            if (i >= total) i = 0;
            current = i;
            track.style.transform = 'translateX(-' + (current * 100) + '%)';
            dots.forEach(function(d, idx) { d.classList.toggle('active', idx === current); });
        }
        
        function start() { if (autoplay && total > 1) timer = setInterval(function() { go(current + 1); }, interval); }
        function reset() { clearInterval(timer); start(); }
        
        if (prev) prev.addEventListener('click', function() { go(current - 1); reset(); });
        if (next) next.addEventListener('click', function() { go(current + 1); reset(); });
        dots.forEach(function(d) { d.addEventListener('click', function() { go(parseInt(this.dataset.index)); reset(); }); });
        c.addEventListener('mouseenter', function() { clearInterval(timer); });
        c.addEventListener('mouseleave', start);
        start();
    })();
    </script>
    <?php
    return ob_get_clean();
}

function bf_render_categories() {
    $categories = get_option('bf_hero_categories', []);
    $categories = array_filter($categories, fn($c) => !empty($c['title']) || !empty($c['image']));
    if (empty($categories)) return '';
    
    ob_start();
    ?>
    <div class="bf-category-bar">
        <div class="bf-category-grid">
            <?php foreach ($categories as $cat): ?>
            <a href="<?php echo esc_url($cat['url'] ?? '#'); ?>" class="bf-category-item">
                <div class="bf-category-thumb">
                    <?php if (!empty($cat['image'])): ?>
                        <img src="<?php echo esc_url($cat['image']); ?>" alt="<?php echo esc_attr($cat['title'] ?? ''); ?>" loading="lazy">
                    <?php endif; ?>
                </div>
                <div class="bf-category-name"><?php echo esc_html($cat['title'] ?? ''); ?></div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
