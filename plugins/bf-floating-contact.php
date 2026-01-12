<?php
/**
 * Plugin Name: BF 浮動聯絡按鈕
 * Description: 飛熊入夢 - 浮動 LINE / 電話 / 回到頂部按鈕
 * Version: 1.0.0
 * Author: BEAR'S FANTASYLAND
 */

if (!defined('ABSPATH')) {
    exit;
}

// ========================================
// ADMIN MENU
// ========================================
add_action('admin_menu', 'bf_floating_menu');
function bf_floating_menu() {
    add_menu_page(
        '浮動按鈕',
        '浮動按鈕',
        'manage_options',
        'bf-floating-contact',
        'bf_floating_admin_page',
        'dashicons-phone',
        28
    );
}

// ========================================
// ADMIN PAGE
// ========================================
function bf_floating_admin_page() {
    // Handle Save
    $saved = false;
    if (isset($_POST['bf_floating_save']) && check_admin_referer('bf_floating_nonce')) {
        update_option('bf_floating_enabled', isset($_POST['bf_floating_enabled']) ? 1 : 0);
        update_option('bf_floating_position', sanitize_text_field($_POST['bf_floating_position']));
        
        // LINE
        update_option('bf_floating_line_enabled', isset($_POST['bf_floating_line_enabled']) ? 1 : 0);
        update_option('bf_floating_line_id', sanitize_text_field($_POST['bf_floating_line_id']));
        
        // Phone
        update_option('bf_floating_phone_enabled', isset($_POST['bf_floating_phone_enabled']) ? 1 : 0);
        update_option('bf_floating_phone_number', sanitize_text_field($_POST['bf_floating_phone_number']));
        
        // Email
        update_option('bf_floating_email_enabled', isset($_POST['bf_floating_email_enabled']) ? 1 : 0);
        update_option('bf_floating_email_address', sanitize_email($_POST['bf_floating_email_address']));
        
        // Back to Top
        update_option('bf_floating_top_enabled', isset($_POST['bf_floating_top_enabled']) ? 1 : 0);
        
        // Style
        update_option('bf_floating_color', sanitize_hex_color($_POST['bf_floating_color']));
        update_option('bf_floating_size', intval($_POST['bf_floating_size']));
        
        $saved = true;
    }
    
    // Get Options
    $enabled = get_option('bf_floating_enabled', 1);
    $position = get_option('bf_floating_position', 'right');
    $line_enabled = get_option('bf_floating_line_enabled', 1);
    $line_id = get_option('bf_floating_line_id', '');
    $phone_enabled = get_option('bf_floating_phone_enabled', 0);
    $phone_number = get_option('bf_floating_phone_number', '');
    $email_enabled = get_option('bf_floating_email_enabled', 0);
    $email_address = get_option('bf_floating_email_address', '');
    $top_enabled = get_option('bf_floating_top_enabled', 1);
    $color = get_option('bf_floating_color', '#8A6754');
    $size = get_option('bf_floating_size', 50);
    
    ?>
    <style>
        :root {
            --bf-cream: #F5F1EB;
            --bf-sand: #E6D9CC;
            --bf-taupe: #C4A995;
            --bf-brown: #8A6754;
            --bf-brown-dark: #6d513f;
        }
        .bf-admin-wrap { max-width: 700px; margin-top: 20px; }
        .bf-admin-header {
            background: linear-gradient(135deg, #00B900 0%, #009900 100%);
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
            padding: 12px 15px;
            background: var(--bf-cream);
            border-radius: 6px;
        }
        .bf-field-row label { font-weight: 600; min-width: 100px; }
        .bf-field-row input[type="text"],
        .bf-field-row input[type="email"],
        .bf-field-row input[type="tel"],
        .bf-field-row select {
            flex: 1;
            padding: 8px 12px;
            border: 1px solid var(--bf-taupe);
            border-radius: 4px;
        }
        .bf-field-row input:focus { outline: none; border-color: var(--bf-brown); }
        .bf-toggle-row {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 10px;
        }
        .bf-channel-box {
            background: var(--bf-cream);
            border: 1px solid var(--bf-sand);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
        }
        .bf-channel-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }
        .bf-channel-header .emoji { font-size: 24px; }
        .bf-channel-header h4 { margin: 0; font-size: 16px; }
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
        .bf-preview-buttons {
            display: flex;
            flex-direction: column;
            gap: 10px;
            align-items: flex-end;
            margin-top: 20px;
            padding: 20px;
            background: #f5f5f5;
            border-radius: 8px;
        }
        .bf-preview-btn {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 22px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
    </style>
    
    <div class="wrap bf-admin-wrap">
        <div class="bf-admin-header">
            <h1>📱 浮動聯絡按鈕</h1>
            <p>設定頁面右下角/左下角的浮動聯絡按鈕</p>
        </div>
        
        <div class="bf-admin-body">
            <?php if ($saved): ?>
                <div class="bf-notice-success">✅ 設定已儲存！</div>
            <?php endif; ?>
            
            <form method="post">
                <?php wp_nonce_field('bf_floating_nonce'); ?>
                
                <h3 class="bf-section-title">⚙️ 基本設定</h3>
                
                <div class="bf-toggle-row">
                    <label><input type="checkbox" name="bf_floating_enabled" value="1" <?php checked($enabled, 1); ?>> 啟用浮動按鈕</label>
                </div>
                
                <div class="bf-field-row">
                    <label>顯示位置</label>
                    <select name="bf_floating_position">
                        <option value="right" <?php selected($position, 'right'); ?>>右下角</option>
                        <option value="left" <?php selected($position, 'left'); ?>>左下角</option>
                    </select>
                </div>
                
                <div class="bf-field-row">
                    <label>主題顏色</label>
                    <input type="color" name="bf_floating_color" value="<?php echo esc_attr($color); ?>" style="width: 60px; height: 35px; padding: 0; border: none;">
                    <span style="color: #666; font-size: 13px;"><?php echo esc_html($color); ?></span>
                </div>
                
                <div class="bf-field-row">
                    <label>按鈕大小</label>
                    <input type="range" name="bf_floating_size" value="<?php echo esc_attr($size); ?>" min="40" max="70" style="flex: 1;">
                    <span><?php echo esc_html($size); ?>px</span>
                </div>
                
                <h3 class="bf-section-title">📞 聯絡管道</h3>
                
                <!-- LINE -->
                <div class="bf-channel-box">
                    <div class="bf-channel-header">
                        <span class="emoji">💬</span>
                        <h4>LINE 官方帳號</h4>
                        <label style="margin-left: auto;"><input type="checkbox" name="bf_floating_line_enabled" value="1" <?php checked($line_enabled, 1); ?>> 啟用</label>
                    </div>
                    <div class="bf-field-row" style="background: #fff; margin: 0;">
                        <label>LINE ID</label>
                        <input type="text" name="bf_floating_line_id" value="<?php echo esc_attr($line_id); ?>" placeholder="@yourlineid">
                        <span style="font-size: 12px; color: #888;">例如：@abc123</span>
                    </div>
                </div>
                
                <!-- Phone -->
                <div class="bf-channel-box">
                    <div class="bf-channel-header">
                        <span class="emoji">📞</span>
                        <h4>電話撥打</h4>
                        <label style="margin-left: auto;"><input type="checkbox" name="bf_floating_phone_enabled" value="1" <?php checked($phone_enabled, 1); ?>> 啟用</label>
                    </div>
                    <div class="bf-field-row" style="background: #fff; margin: 0;">
                        <label>電話號碼</label>
                        <input type="tel" name="bf_floating_phone_number" value="<?php echo esc_attr($phone_number); ?>" placeholder="0912-345-678">
                    </div>
                </div>
                
                <!-- Email -->
                <div class="bf-channel-box">
                    <div class="bf-channel-header">
                        <span class="emoji">✉️</span>
                        <h4>Email 聯繫</h4>
                        <label style="margin-left: auto;"><input type="checkbox" name="bf_floating_email_enabled" value="1" <?php checked($email_enabled, 1); ?>> 啟用</label>
                    </div>
                    <div class="bf-field-row" style="background: #fff; margin: 0;">
                        <label>Email</label>
                        <input type="email" name="bf_floating_email_address" value="<?php echo esc_attr($email_address); ?>" placeholder="contact@example.com">
                    </div>
                </div>
                
                <!-- Back to Top -->
                <div class="bf-channel-box">
                    <div class="bf-channel-header">
                        <span class="emoji">⬆️</span>
                        <h4>回到頂部</h4>
                        <label style="margin-left: auto;"><input type="checkbox" name="bf_floating_top_enabled" value="1" <?php checked($top_enabled, 1); ?>> 啟用</label>
                    </div>
                    <p style="margin: 0; font-size: 13px; color: #666;">滾動超過 300px 時顯示</p>
                </div>
                
                <h3 class="bf-section-title">👀 預覽</h3>
                <div class="bf-preview-buttons">
                    <?php if ($line_enabled): ?>
                    <div class="bf-preview-btn" style="background: #00B900;">💬</div>
                    <?php endif; ?>
                    <?php if ($phone_enabled): ?>
                    <div class="bf-preview-btn" style="background: <?php echo esc_attr($color); ?>;">📞</div>
                    <?php endif; ?>
                    <?php if ($email_enabled): ?>
                    <div class="bf-preview-btn" style="background: <?php echo esc_attr($color); ?>;">✉️</div>
                    <?php endif; ?>
                    <?php if ($top_enabled): ?>
                    <div class="bf-preview-btn" style="background: <?php echo esc_attr($color); ?>;">⬆️</div>
                    <?php endif; ?>
                </div>
                
                <button type="submit" name="bf_floating_save" class="bf-submit-btn">💾 儲存設定</button>
            </form>
        </div>
    </div>
    <?php
}

// ========================================
// FRONTEND OUTPUT
// ========================================
add_action('wp_footer', 'bf_floating_render');
function bf_floating_render() {
    $enabled = get_option('bf_floating_enabled', 1);
    if (!$enabled) return;
    
    $position = get_option('bf_floating_position', 'right');
    $line_enabled = get_option('bf_floating_line_enabled', 1);
    $line_id = get_option('bf_floating_line_id', '');
    $phone_enabled = get_option('bf_floating_phone_enabled', 0);
    $phone_number = get_option('bf_floating_phone_number', '');
    $email_enabled = get_option('bf_floating_email_enabled', 0);
    $email_address = get_option('bf_floating_email_address', '');
    $top_enabled = get_option('bf_floating_top_enabled', 1);
    $color = get_option('bf_floating_color', '#8A6754');
    $size = get_option('bf_floating_size', 50);
    
    $has_buttons = ($line_enabled && $line_id) || ($phone_enabled && $phone_number) || ($email_enabled && $email_address) || $top_enabled;
    if (!$has_buttons) return;
    
    $pos_css = $position === 'left' ? 'left: 20px;' : 'right: 20px;';
    
    ?>
    <style>
    .bf-floating-buttons {
        position: fixed;
        bottom: 20px;
        <?php echo $pos_css; ?>
        display: flex;
        flex-direction: column;
        gap: 12px;
        z-index: 9998;
    }
    .bf-floating-btn {
        width: <?php echo $size; ?>px;
        height: <?php echo $size; ?>px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: <?php echo round($size * 0.44); ?>px;
        text-decoration: none;
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        transition: all 0.3s ease;
        cursor: pointer;
        border: none;
    }
    .bf-floating-btn:hover {
        transform: scale(1.1);
        box-shadow: 0 6px 20px rgba(0,0,0,0.25);
    }
    .bf-floating-btn-line { background: #00B900; }
    .bf-floating-btn-phone { background: <?php echo esc_attr($color); ?>; }
    .bf-floating-btn-email { background: <?php echo esc_attr($color); ?>; }
    .bf-floating-btn-top {
        background: <?php echo esc_attr($color); ?>;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
    }
    .bf-floating-btn-top.visible {
        opacity: 1;
        visibility: visible;
    }
    @media (max-width: 768px) {
        .bf-floating-buttons {
            bottom: 15px;
            <?php echo $position === 'left' ? 'left: 15px;' : 'right: 15px;'; ?>
            gap: 10px;
        }
        .bf-floating-btn {
            width: <?php echo max(40, $size - 5); ?>px;
            height: <?php echo max(40, $size - 5); ?>px;
        }
    }
    </style>
    
    <div class="bf-floating-buttons">
        <?php if ($line_enabled && $line_id): ?>
        <a href="https://line.me/R/ti/p/<?php echo esc_attr(ltrim($line_id, '@')); ?>" target="_blank" class="bf-floating-btn bf-floating-btn-line" title="LINE 聯繫">💬</a>
        <?php endif; ?>
        
        <?php if ($phone_enabled && $phone_number): ?>
        <a href="tel:<?php echo esc_attr(preg_replace('/[^0-9+]/', '', $phone_number)); ?>" class="bf-floating-btn bf-floating-btn-phone" title="電話聯繫">📞</a>
        <?php endif; ?>
        
        <?php if ($email_enabled && $email_address): ?>
        <a href="mailto:<?php echo esc_attr($email_address); ?>" class="bf-floating-btn bf-floating-btn-email" title="Email 聯繫">✉️</a>
        <?php endif; ?>
        
        <?php if ($top_enabled): ?>
        <button class="bf-floating-btn bf-floating-btn-top" id="bf-back-to-top" title="回到頂部">⬆️</button>
        <?php endif; ?>
    </div>
    
    <?php if ($top_enabled): ?>
    <script>
    (function() {
        var topBtn = document.getElementById('bf-back-to-top');
        if (!topBtn) return;
        
        window.addEventListener('scroll', function() {
            if (window.scrollY > 300) {
                topBtn.classList.add('visible');
            } else {
                topBtn.classList.remove('visible');
            }
        });
        
        topBtn.addEventListener('click', function() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    })();
    </script>
    <?php endif; ?>
    <?php
}
