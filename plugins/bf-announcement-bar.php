<?php
/**
 * Plugin Name: BF 頂部公告條
 * Description: 飛熊入夢 - 可關閉的頂部公告條，支援多則輪播、連結設定
 * Version: 1.0.0
 * Author: BEAR'S FANTASYLAND
 */

if (!defined('ABSPATH')) {
    exit;
}

// ========================================
// ADMIN MENU
// ========================================
add_action('admin_menu', 'bf_announcement_menu');
function bf_announcement_menu() {
    add_menu_page(
        '公告條',
        '公告條',
        'manage_options',
        'bf-announcement',
        'bf_announcement_admin_page',
        'dashicons-megaphone',
        27
    );
}

// ========================================
// ADMIN SCRIPTS
// ========================================
add_action('admin_enqueue_scripts', 'bf_announcement_admin_scripts');
function bf_announcement_admin_scripts($hook) {
    if ($hook !== 'toplevel_page_bf-announcement') return;
    wp_enqueue_script('jquery-ui-sortable');
    wp_enqueue_script('wp-color-picker');
    wp_enqueue_style('wp-color-picker');
}

// ========================================
// ADMIN PAGE
// ========================================
function bf_announcement_admin_page() {
    // Handle Save
    $saved = false;
    if (isset($_POST['bf_announcement_save']) && check_admin_referer('bf_announcement_nonce')) {
        update_option('bf_announcement_enabled', isset($_POST['bf_announcement_enabled']) ? 1 : 0);
        update_option('bf_announcement_bg_color', sanitize_hex_color($_POST['bf_announcement_bg_color']));
        update_option('bf_announcement_text_color', sanitize_hex_color($_POST['bf_announcement_text_color']));
        update_option('bf_announcement_dismissable', isset($_POST['bf_announcement_dismissable']) ? 1 : 0);
        update_option('bf_announcement_sticky', isset($_POST['bf_announcement_sticky']) ? 1 : 0);
        update_option('bf_announcement_autoplay', isset($_POST['bf_announcement_autoplay']) ? 1 : 0);
        update_option('bf_announcement_interval', intval($_POST['bf_announcement_interval']));
        
        $messages = [];
        if (!empty($_POST['bf_messages']) && is_array($_POST['bf_messages'])) {
            foreach ($_POST['bf_messages'] as $msg) {
                if (!empty($msg['text'])) {
                    $messages[] = [
                        'text' => sanitize_text_field($msg['text']),
                        'url' => esc_url_raw($msg['url'] ?? ''),
                        'icon' => sanitize_text_field($msg['icon'] ?? ''),
                    ];
                }
            }
        }
        update_option('bf_announcement_messages', $messages);
        
        $saved = true;
    }
    
    // Get Options
    $enabled = get_option('bf_announcement_enabled', 1);
    $bg_color = get_option('bf_announcement_bg_color', '#8A6754');
    $text_color = get_option('bf_announcement_text_color', '#FFFFFF');
    $dismissable = get_option('bf_announcement_dismissable', 1);
    $sticky = get_option('bf_announcement_sticky', 0);
    $autoplay = get_option('bf_announcement_autoplay', 1);
    $interval = get_option('bf_announcement_interval', 4);
    $messages = get_option('bf_announcement_messages', []);
    
    if (empty($messages)) {
        $messages = [['text' => '', 'url' => '', 'icon' => '🎉']];
    }
    
    ?>
    <style>
        :root {
            --bf-cream: #F5F1EB;
            --bf-sand: #E6D9CC;
            --bf-taupe: #C4A995;
            --bf-brown: #8A6754;
            --bf-brown-dark: #6d513f;
        }
        .bf-admin-wrap { max-width: 800px; margin-top: 20px; }
        .bf-admin-header {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: #fff;
            padding: 25px 30px;
            border-radius: 8px 8px 0 0;
        }
        .bf-admin-header h1 { margin: 0; font-size: 22px; }
        .bf-admin-header p { margin: 8px 0 0; opacity: 0.85; font-size: 13px; }
        .bf-admin-body {
            background: #fff;
            padding: 30px;
            border: 1px solid var(--bf-sand);
            border-top: none;
            border-radius: 0 0 8px 8px;
        }
        .bf-section-title {
            font-size: 16px;
            color: var(--bf-brown);
            margin: 25px 0 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--bf-sand);
        }
        .bf-section-title:first-child { margin-top: 0; }
        .bf-field-row {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }
        .bf-field-row label { font-weight: 600; min-width: 120px; }
        .bf-msg-box {
            background: var(--bf-cream);
            border: 1px solid var(--bf-sand);
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 10px;
            cursor: move;
        }
        .bf-msg-box:hover { border-color: var(--bf-brown); }
        .bf-msg-box.ui-sortable-placeholder {
            visibility: visible !important;
            background: rgba(138, 103, 84, 0.1);
            border: 2px dashed var(--bf-brown);
        }
        .bf-msg-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .bf-msg-header h4 { margin: 0; font-size: 14px; color: var(--bf-brown); }
        .bf-msg-fields { display: grid; grid-template-columns: 60px 1fr 1fr; gap: 10px; }
        .bf-msg-fields input { padding: 8px 10px; border: 1px solid var(--bf-taupe); border-radius: 4px; }
        .bf-msg-fields input:focus { outline: none; border-color: var(--bf-brown); }
        .bf-remove-msg {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 4px 10px;
            border-radius: 4px;
            cursor: pointer;
        }
        .bf-add-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            background: var(--bf-cream);
            border: 2px dashed var(--bf-taupe);
            border-radius: 6px;
            color: var(--bf-brown);
            font-weight: 600;
            cursor: pointer;
            margin-top: 10px;
        }
        .bf-add-btn:hover { border-color: var(--bf-brown); background: #fff; }
        .bf-submit-btn {
            background: var(--bf-brown);
            color: #fff;
            border: none;
            padding: 14px 35px;
            font-size: 15px;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            margin-top: 20px;
        }
        .bf-submit-btn:hover { background: var(--bf-brown-dark); }
        .bf-notice-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            padding: 12px 18px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .bf-shortcode-box {
            background: var(--bf-cream);
            padding: 15px 20px;
            border-radius: 6px;
            border: 1px solid var(--bf-sand);
            margin-top: 25px;
        }
        .bf-shortcode-box code {
            background: #fff;
            padding: 8px 15px;
            border-radius: 4px;
            font-size: 14px;
            color: var(--bf-brown);
            border: 1px solid var(--bf-taupe);
            display: inline-block;
            margin: 5px 0;
        }
        .bf-preview-bar {
            padding: 12px 20px;
            text-align: center;
            font-size: 14px;
            border-radius: 6px;
            margin-top: 20px;
        }
        .bf-color-field { display: flex; align-items: center; gap: 10px; }
    </style>
    
    <div class="wrap bf-admin-wrap">
        <div class="bf-admin-header">
            <h1>📢 頂部公告條管理器</h1>
            <p>設定網站頂部的公告訊息，支援多則輪播</p>
        </div>
        
        <div class="bf-admin-body">
            <?php if ($saved): ?>
                <div class="bf-notice-success">✅ 設定已儲存！</div>
            <?php endif; ?>
            
            <form method="post">
                <?php wp_nonce_field('bf_announcement_nonce'); ?>
                
                <h3 class="bf-section-title">⚙️ 基本設定</h3>
                
                <div class="bf-field-row">
                    <label>啟用公告條</label>
                    <label><input type="checkbox" name="bf_announcement_enabled" value="1" <?php checked($enabled, 1); ?>> 開啟</label>
                </div>
                
                <div class="bf-field-row">
                    <label>可關閉</label>
                    <label><input type="checkbox" name="bf_announcement_dismissable" value="1" <?php checked($dismissable, 1); ?>> 顯示關閉按鈕</label>
                </div>
                
                <div class="bf-field-row">
                    <label>固定置頂</label>
                    <label><input type="checkbox" name="bf_announcement_sticky" value="1" <?php checked($sticky, 1); ?>> 滾動時固定在頂部</label>
                </div>
                
                <h3 class="bf-section-title">🎨 樣式設定</h3>
                
                <div class="bf-field-row">
                    <label>背景顏色</label>
                    <div class="bf-color-field">
                        <input type="text" name="bf_announcement_bg_color" value="<?php echo esc_attr($bg_color); ?>" class="bf-color-picker" data-default-color="#8A6754">
                    </div>
                </div>
                
                <div class="bf-field-row">
                    <label>文字顏色</label>
                    <div class="bf-color-field">
                        <input type="text" name="bf_announcement_text_color" value="<?php echo esc_attr($text_color); ?>" class="bf-color-picker" data-default-color="#FFFFFF">
                    </div>
                </div>
                
                <div class="bf-preview-bar" style="background: <?php echo esc_attr($bg_color); ?>; color: <?php echo esc_attr($text_color); ?>;">
                    預覽效果：🎉 歡迎光臨飛熊入夢！全館滿 $3000 免運費
                </div>
                
                <h3 class="bf-section-title">💬 公告訊息（可多則輪播）</h3>
                
                <div class="bf-field-row">
                    <label>自動輪播</label>
                    <label><input type="checkbox" name="bf_announcement_autoplay" value="1" <?php checked($autoplay, 1); ?>> 啟用</label>
                    <span style="margin-left: 20px;">間隔</span>
                    <input type="number" name="bf_announcement_interval" value="<?php echo esc_attr($interval); ?>" min="2" max="30" style="width: 60px;"> 秒
                </div>
                
                <div id="bf-messages-list">
                    <?php foreach ($messages as $i => $msg): ?>
                    <div class="bf-msg-box" data-index="<?php echo $i; ?>">
                        <div class="bf-msg-header">
                            <h4>☰ 訊息 #<?php echo ($i + 1); ?></h4>
                            <button type="button" class="bf-remove-msg" <?php echo count($messages) <= 1 ? 'disabled' : ''; ?>>移除</button>
                        </div>
                        <div class="bf-msg-fields">
                            <input type="text" name="bf_messages[<?php echo $i; ?>][icon]" value="<?php echo esc_attr($msg['icon'] ?? '🎉'); ?>" placeholder="🎉" title="圖示 (Emoji)">
                            <input type="text" name="bf_messages[<?php echo $i; ?>][text]" value="<?php echo esc_attr($msg['text'] ?? ''); ?>" placeholder="公告文字內容">
                            <input type="url" name="bf_messages[<?php echo $i; ?>][url]" value="<?php echo esc_attr($msg['url'] ?? ''); ?>" placeholder="連結網址（選填）">
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <button type="button" class="bf-add-btn" id="bf-add-message">➕ 新增訊息</button>
                
                <div class="bf-shortcode-box">
                    <strong>📋 使用方式</strong><br>
                    <p style="margin: 10px 0 5px; color: #666;">方法 1：短代碼（手動放置位置）</p>
                    <code>[bf_announcement]</code>
                    <p style="margin: 15px 0 5px; color: #666;">方法 2：自動顯示於頁面最頂部（推薦）</p>
                    <p style="font-size: 13px; color: #888;">啟用後會自動在 &lt;body&gt; 開頭顯示公告條</p>
                </div>
                
                <button type="submit" name="bf_announcement_save" class="bf-submit-btn">💾 儲存設定</button>
            </form>
        </div>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        // Color Picker
        $('.bf-color-picker').wpColorPicker({
            change: function(event, ui) {
                updatePreview();
            }
        });
        
        function updatePreview() {
            var bg = $('input[name="bf_announcement_bg_color"]').val();
            var text = $('input[name="bf_announcement_text_color"]').val();
            $('.bf-preview-bar').css({ 'background': bg, 'color': text });
        }
        
        var msgIndex = <?php echo count($messages); ?>;
        
        // Sortable
        $('#bf-messages-list').sortable({
            handle: 'h4',
            placeholder: 'bf-msg-box ui-sortable-placeholder',
            update: function() { updateIndices(); }
        });
        
        function updateIndices() {
            $('#bf-messages-list .bf-msg-box').each(function(i) {
                $(this).attr('data-index', i);
                $(this).find('h4').text('☰ 訊息 #' + (i + 1));
                $(this).find('input').each(function() {
                    var name = $(this).attr('name');
                    if (name) {
                        $(this).attr('name', name.replace(/\[\d+\]/, '[' + i + ']'));
                    }
                });
            });
            updateRemoveButtons();
        }
        
        function updateRemoveButtons() {
            var count = $('.bf-msg-box').length;
            $('.bf-remove-msg').prop('disabled', count <= 1);
        }
        
        // Add message
        $('#bf-add-message').on('click', function() {
            var html = `
            <div class="bf-msg-box" data-index="${msgIndex}">
                <div class="bf-msg-header">
                    <h4>☰ 訊息 #${msgIndex + 1}</h4>
                    <button type="button" class="bf-remove-msg">移除</button>
                </div>
                <div class="bf-msg-fields">
                    <input type="text" name="bf_messages[${msgIndex}][icon]" value="🎉" placeholder="🎉" title="圖示 (Emoji)">
                    <input type="text" name="bf_messages[${msgIndex}][text]" placeholder="公告文字內容">
                    <input type="url" name="bf_messages[${msgIndex}][url]" placeholder="連結網址（選填）">
                </div>
            </div>`;
            $('#bf-messages-list').append(html);
            msgIndex++;
            updateIndices();
        });
        
        // Remove message
        $(document).on('click', '.bf-remove-msg', function() {
            if ($('.bf-msg-box').length > 1) {
                $(this).closest('.bf-msg-box').remove();
                updateIndices();
            }
        });
    });
    </script>
    <?php
}

// ========================================
// FRONTEND CSS
// ========================================
function bf_announcement_css($bg, $text) {
    return "
    .bf-announcement-bar {
        background: {$bg};
        color: {$text};
        padding: 12px 20px;
        text-align: center;
        font-size: 14px;
        font-family: 'Noto Sans TC', sans-serif;
        position: relative;
        z-index: 9999;
        overflow: hidden;
    }
    .bf-announcement-bar.bf-sticky {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
    }
    .bf-announcement-bar.bf-sticky + * {
        margin-top: 44px;
    }
    .bf-announcement-track {
        display: flex;
        transition: transform 0.4s ease;
    }
    .bf-announcement-slide {
        min-width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }
    .bf-announcement-slide a {
        color: inherit;
        text-decoration: underline;
        text-underline-offset: 2px;
    }
    .bf-announcement-slide a:hover {
        text-decoration-thickness: 2px;
    }
    .bf-announcement-icon {
        font-size: 16px;
    }
    .bf-announcement-close {
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        background: transparent;
        border: none;
        color: inherit;
        font-size: 18px;
        cursor: pointer;
        opacity: 0.7;
        padding: 5px;
        line-height: 1;
    }
    .bf-announcement-close:hover {
        opacity: 1;
    }
    .bf-announcement-bar.bf-hidden {
        display: none;
    }
    ";
}

// ========================================
// SHORTCODE
// ========================================
add_shortcode('bf_announcement', 'bf_announcement_shortcode');
function bf_announcement_shortcode() {
    return bf_announcement_render();
}

// ========================================
// AUTO DISPLAY (wp_body_open)
// ========================================
add_action('wp_body_open', 'bf_announcement_auto_display');
function bf_announcement_auto_display() {
    $enabled = get_option('bf_announcement_enabled', 1);
    if (!$enabled) return;
    
    echo bf_announcement_render();
}

// ========================================
// RENDER
// ========================================
function bf_announcement_render() {
    $enabled = get_option('bf_announcement_enabled', 1);
    if (!$enabled) return '';
    
    $messages = get_option('bf_announcement_messages', []);
    $messages = array_filter($messages, fn($m) => !empty($m['text']));
    
    if (empty($messages)) return '';
    
    $bg_color = get_option('bf_announcement_bg_color', '#8A6754');
    $text_color = get_option('bf_announcement_text_color', '#FFFFFF');
    $dismissable = get_option('bf_announcement_dismissable', 1);
    $sticky = get_option('bf_announcement_sticky', 0);
    $autoplay = get_option('bf_announcement_autoplay', 1);
    $interval = intval(get_option('bf_announcement_interval', 4)) * 1000;
    
    $unique_id = 'bf-announcement-' . uniqid();
    $sticky_class = $sticky ? ' bf-sticky' : '';
    
    ob_start();
    ?>
    <style><?php echo bf_announcement_css($bg_color, $text_color); ?></style>
    
    <div id="<?php echo esc_attr($unique_id); ?>" class="bf-announcement-bar<?php echo $sticky_class; ?>">
        <div class="bf-announcement-track">
            <?php foreach ($messages as $msg): ?>
            <div class="bf-announcement-slide">
                <?php if (!empty($msg['icon'])): ?>
                    <span class="bf-announcement-icon"><?php echo esc_html($msg['icon']); ?></span>
                <?php endif; ?>
                <?php if (!empty($msg['url'])): ?>
                    <a href="<?php echo esc_url($msg['url']); ?>"><?php echo esc_html($msg['text']); ?></a>
                <?php else: ?>
                    <span><?php echo esc_html($msg['text']); ?></span>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        
        <?php if ($dismissable): ?>
        <button class="bf-announcement-close" aria-label="關閉">✕</button>
        <?php endif; ?>
    </div>
    
    <script>
    (function() {
        var bar = document.getElementById('<?php echo esc_js($unique_id); ?>');
        if (!bar) return;
        
        // Check if dismissed
        var dismissed = sessionStorage.getItem('bf_announcement_dismissed');
        if (dismissed) {
            bar.classList.add('bf-hidden');
            return;
        }
        
        // Close button
        var closeBtn = bar.querySelector('.bf-announcement-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', function() {
                bar.classList.add('bf-hidden');
                sessionStorage.setItem('bf_announcement_dismissed', '1');
            });
        }
        
        // Auto rotate messages
        var track = bar.querySelector('.bf-announcement-track');
        var slides = bar.querySelectorAll('.bf-announcement-slide');
        var total = slides.length;
        
        if (total > 1 && <?php echo $autoplay ? 'true' : 'false'; ?>) {
            var current = 0;
            setInterval(function() {
                current = (current + 1) % total;
                track.style.transform = 'translateX(-' + (current * 100) + '%)';
            }, <?php echo $interval; ?>);
        }
    })();
    </script>
    <?php
    return ob_get_clean();
}
