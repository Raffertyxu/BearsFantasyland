<?php
/**
 * Plugin Name: BF 質感聯絡表單 (Contact Form)
 * Description: 飛熊入夢風格的聯絡表單，支援 AJAX 傳送與後台信箱設定。[bf_contact_form]
 * Version: 1.0
 * Author: Bear's Fantasyland
 */

if (!defined('ABSPATH')) {
    exit;
}

// -----------------------------------------------------------------------------
// 0. Custom Post Type (Backend Management)
// -----------------------------------------------------------------------------

add_action('init', 'bf_contact_register_cpt');
function bf_contact_register_cpt() {
    register_post_type('bf_message', [
        'labels' => [
            'name' => '網站留言',
            'singular_name' => '留言',
            'menu_name' => '網站留言 (Inbox)',
            'all_items' => '所有留言',
            'not_found' => '目前沒有留言',
        ],
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_icon' => 'dashicons-email',
        'supports' => ['title', 'editor', 'custom-fields'],
        'capabilities' => [
            'create_posts' => 'do_not_allow', // Disable manual creation
        ],
        'map_meta_cap' => true,
    ]);
}

// Custom Columns for Message List
add_filter('manage_bf_message_posts_columns', 'bf_contact_custom_columns');
function bf_contact_custom_columns($columns) {
    $new_columns = [
        'cb' => $columns['cb'],
        'title' => '主旨 (Subject)',
        'bf_sender' => '寄件人 (Name)',
        'bf_email' => 'Email',
        'date' => '時間'
    ];
    return $new_columns;
}

add_action('manage_bf_message_posts_custom_column', 'bf_contact_custom_column_content', 10, 2);
function bf_contact_custom_column_content($column, $post_id) {
    switch ($column) {
        case 'bf_sender':
            echo esc_html(get_post_meta($post_id, '_bf_name', true));
            break;
        case 'bf_email':
            echo '<a href="mailto:' . esc_attr(get_post_meta($post_id, '_bf_email', true)) . '">' . esc_html(get_post_meta($post_id, '_bf_email', true)) . '</a>';
            break;
    }
}

// -----------------------------------------------------------------------------
// 1. Settings (Recipient Email)
// -----------------------------------------------------------------------------

function bf_contact_add_admin_menu() {
    // Add "Settings" as submenu to the "bf_message" CPT menu
    add_submenu_page(
        'edit.php?post_type=bf_message',
        '聯絡表單設定',
        '表單設定',
        'manage_options',
        'bf-contact-settings',
        'bf_contact_settings_page'
    );
}
add_action('admin_menu', 'bf_contact_add_admin_menu');

function bf_contact_settings_page() {
    if (isset($_POST['bf_contact_save']) && check_admin_referer('bf_contact_nonce')) {
        update_option('bf_contact_email', sanitize_email($_POST['bf_contact_email']));
        // SMTP Settings
        update_option('bf_smtp_enabled', isset($_POST['bf_smtp_enabled']) ? 1 : 0);
        update_option('bf_smtp_host', sanitize_text_field($_POST['bf_smtp_host']));
        update_option('bf_smtp_port', intval($_POST['bf_smtp_port']));
        update_option('bf_smtp_user', sanitize_text_field($_POST['bf_smtp_user']));
        update_option('bf_smtp_pass', sanitize_text_field($_POST['bf_smtp_pass'])); // Note: Consider encryption for production
        update_option('bf_smtp_secure', sanitize_text_field($_POST['bf_smtp_secure']));
        update_option('bf_smtp_from_name', sanitize_text_field($_POST['bf_smtp_from_name']));
        echo '<div class="updated"><p>設定已儲存。</p></div>';
    }
    
    $email = get_option('bf_contact_email', get_option('admin_email'));
    $smtp_enabled = get_option('bf_smtp_enabled', 0);
    $smtp_host = get_option('bf_smtp_host', '');
    $smtp_port = get_option('bf_smtp_port', 587);
    $smtp_user = get_option('bf_smtp_user', '');
    $smtp_pass = get_option('bf_smtp_pass', '');
    $smtp_secure = get_option('bf_smtp_secure', 'tls');
    $smtp_from_name = get_option('bf_smtp_from_name', get_bloginfo('name'));
    ?>
    <div class="wrap">
        <h2>BF 聯絡表單設定</h2>
        <form method="post">
            <?php wp_nonce_field('bf_contact_nonce'); ?>
            
            <h3>📧 基本設定</h3>
            <table class="form-table">
                <tr>
                    <th scope="row">接收信箱 (Recipient Email)</th>
                    <td>
                        <input type="email" name="bf_contact_email" value="<?php echo esc_attr($email); ?>" class="regular-text">
                        <p class="description">訪客提交的表單內容將會寄送到此信箱。</p>
                    </td>
                </tr>
            </table>

            <hr>
            <h3>🔧 SMTP 郵件伺服器設定</h3>
            <p style="color:#666;">如果郵件無法寄出，請啟用 SMTP 並填寫您的郵件伺服器資訊。</p>

            <table class="form-table">
                <tr>
                    <th scope="row">啟用 SMTP</th>
                    <td>
                        <label>
                            <input type="checkbox" name="bf_smtp_enabled" value="1" <?php checked($smtp_enabled, 1); ?>>
                            使用 SMTP 發送郵件
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">SMTP 主機</th>
                    <td>
                        <input type="text" name="bf_smtp_host" value="<?php echo esc_attr($smtp_host); ?>" class="regular-text" placeholder="例如：smtp.gmail.com">
                    </td>
                </tr>
                <tr>
                    <th scope="row">SMTP 連接埠</th>
                    <td>
                        <input type="number" name="bf_smtp_port" value="<?php echo esc_attr($smtp_port); ?>" style="width:100px;">
                        <p class="description">常用埠號：587 (TLS) 或 465 (SSL)</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">SMTP 帳號</th>
                    <td>
                        <input type="text" name="bf_smtp_user" value="<?php echo esc_attr($smtp_user); ?>" class="regular-text" placeholder="例如：your-email@gmail.com">
                    </td>
                </tr>
                <tr>
                    <th scope="row">SMTP 密碼</th>
                    <td>
                        <input type="password" name="bf_smtp_pass" value="<?php echo esc_attr($smtp_pass); ?>" class="regular-text" autocomplete="new-password">
                        <p class="description">Gmail 用戶請使用「應用程式密碼」而非帳號密碼。</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">加密方式</th>
                    <td>
                        <select name="bf_smtp_secure">
                            <option value="tls" <?php selected($smtp_secure, 'tls'); ?>>TLS</option>
                            <option value="ssl" <?php selected($smtp_secure, 'ssl'); ?>>SSL</option>
                            <option value="" <?php selected($smtp_secure, ''); ?>>無</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">寄件人名稱</th>
                    <td>
                        <input type="text" name="bf_smtp_from_name" value="<?php echo esc_attr($smtp_from_name); ?>" class="regular-text" placeholder="例如：飛熊入夢">
                    </td>
                </tr>
            </table>

            <p class="submit">
                <button type="submit" name="bf_contact_save" class="button button-primary">儲存變更</button>
            </p>
        </form>
        <hr>
        <h3>使用方式</h3>
        <p>請在任何頁面貼上短代碼： <code>[bf_contact_form]</code></p>
    </div>
    <?php
}

// -----------------------------------------------------------------------------
// 1.5 PHPMailer SMTP Hook
// -----------------------------------------------------------------------------

add_action('phpmailer_init', 'bf_contact_smtp_config');
function bf_contact_smtp_config($phpmailer) {
    $smtp_enabled = get_option('bf_smtp_enabled', 0);
    
    if (!$smtp_enabled) {
        return; // Use default wp_mail behavior
    }

    $phpmailer->isSMTP();
    $phpmailer->Host       = get_option('bf_smtp_host', '');
    $phpmailer->SMTPAuth   = true;
    $phpmailer->Port       = get_option('bf_smtp_port', 587);
    $phpmailer->Username   = get_option('bf_smtp_user', '');
    $phpmailer->Password   = get_option('bf_smtp_pass', '');
    $phpmailer->SMTPSecure = get_option('bf_smtp_secure', 'tls');
    $phpmailer->From       = get_option('bf_smtp_user', get_option('admin_email'));
    $phpmailer->FromName   = get_option('bf_smtp_from_name', get_bloginfo('name'));
}

// -----------------------------------------------------------------------------
// 2. AJAX Handler
// -----------------------------------------------------------------------------

add_action('wp_ajax_bf_contact_send', 'bf_contact_send_ajax');
add_action('wp_ajax_nopriv_bf_contact_send', 'bf_contact_send_ajax');

function bf_contact_send_ajax() {
    check_ajax_referer('bf_contact_ajax_nonce', 'nonce');

    $name = sanitize_text_field($_POST['bf_name']);
    $email = sanitize_email($_POST['bf_email']);
    $subject = sanitize_text_field($_POST['bf_subject']);
    $message = sanitize_textarea_field($_POST['bf_message']);
    
    // CAPTCHA Validation
    $captcha_ans = intval($_POST['bf_captcha_ans']);
    $captcha_hash = $_POST['bf_captcha_hash'];
    $salt = 'bf_secret_salt_2026';
    
    if (md5($captcha_ans . $salt) !== $captcha_hash) {
        wp_send_json_error(['msg' => '驗證碼錯誤，請重新計算。']);
        return;
    }

    if (empty($name) || empty($email) || empty($message)) {
        wp_send_json_error(['msg' => '請填寫所有必填欄位。']);
    }

    $to = get_option('bf_contact_email', get_option('admin_email'));
    $site_name = get_bloginfo('name');
    
    $headers = [
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . $site_name . ' <' . $to . '>',
        'Reply-To: ' . $name . ' <' . $email . '>'
    ];

    $body = "
        <h3>來自網站的聯絡訊息</h3>
        <p><strong>姓名：</strong> $name</p>
        <p><strong>信箱：</strong> $email</p>
        <p><strong>主旨：</strong> $subject</p>
        <p><strong>訊息內容：</strong><br>" . nl2br($message) . "</p>
    ";

    // Save to Database (Custom Post Type)
    $post_id = wp_insert_post([
        'post_type' => 'bf_message',
        'post_title' => $name . ' - ' . $subject,
        'post_content' => $message,
        'post_status' => 'private', // Use private so it doesn't show up on frontend archives
    ]);

    if ($post_id) {
        update_post_meta($post_id, '_bf_name', $name);
        update_post_meta($post_id, '_bf_email', $email);
        update_post_meta($post_id, '_bf_subject', $subject);
    }

    $sent = wp_mail($to, "[$site_name] 新留言: " . $subject, $body, $headers);

    if ($post_id || $sent) {
        wp_send_json_success(['msg' => '訊息已發送，我們會盡快與您聯繫！']);
    } else {
        wp_send_json_error(['msg' => '發送失敗，請稍後再試或直接來信。']);
    }
}

// -----------------------------------------------------------------------------
// 3. Shortcode & Frontend
// -----------------------------------------------------------------------------

function bf_contact_shortcode() {
    wp_enqueue_script('jquery');
    
    // Generate Math CAPTCHA
    $n1 = rand(1, 9);
    $n2 = rand(1, 9);
    $salt = 'bf_secret_salt_2026';
    $hash = md5(($n1 + $n2) . $salt);
    
    ob_start();
    ?>
    <section class="bf-contact-section">
        <div class="bf-contact-container">
            <div class="bf-contact-header">
                <span class="bf-contact-subtitle">GET IN TOUCH</span>
                <h2 class="bf-contact-title">寫信給我們</h2>
                <p class="bf-contact-desc">
                    無論是家具訂製、空間規劃或是課程諮詢，<br>
                    歡迎留下您的訊息，我們將用心回覆。
                </p>
            </div>

            <form id="bf-contact-form" class="bf-contact-form">
                <div class="bf-form-row">
                    <div class="bf-form-group">
                        <label for="bf_name">您的稱呼 *</label>
                        <input type="text" id="bf_name" name="bf_name" required>
                    </div>
                    <div class="bf-form-group">
                        <label for="bf_email">電子信箱 *</label>
                        <input type="email" id="bf_email" name="bf_email" required>
                    </div>
                </div>
                
                <div class="bf-form-group">
                    <label for="bf_subject">主旨</label>
                    <input type="text" id="bf_subject" name="bf_subject">
                </div>

                <div class="bf-form-group">
                    <label for="bf_message">訊息內容 *</label>
                    <textarea id="bf_message" name="bf_message" rows="5" required></textarea>
                </div>
                
                <!-- CAPTCHA -->
                <div class="bf-form-group" style="max-width: 200px;">
                    <label for="bf_captcha">驗證碼：<?php echo $n1; ?> + <?php echo $n2; ?> = ?</label>
                    <input type="number" id="bf_captcha" name="bf_captcha_ans" required placeholder="請輸入答案">
                    <input type="hidden" name="bf_captcha_hash" value="<?php echo $hash; ?>">
                </div>

                <div class="bf-form-footer">
                    <button type="submit" class="bf-submit-btn" id="bf-submit-btn">送出訊息</button>
                    <div id="bf-form-status"></div>
                </div>
            </form>
        </div>
    </section>

    <style>
        .bf-contact-section {
            padding: 80px 20px;
            background: #fff;
            text-align: center;
        }
        .bf-contact-container {
            max-width: 700px;
            margin: 0 auto;
        }
        .bf-contact-subtitle {
            font-size: 13px;
            letter-spacing: 0.25em;
            color: #8A6754;
            display: block;
            margin-bottom: 15px;
            font-weight: 600;
        }
        .bf-contact-title {
            font-family: 'Noto Serif TC', serif;
            font-size: 32px;
            color: #333;
            margin-bottom: 24px !important;
        }
        .bf-contact-desc {
            font-size: 15px;
            color: #666;
            line-height: 1.8;
            margin-bottom: 50px;
        }
        
        /* Form Styles */
        .bf-contact-form {
            text-align: left;
        }
        .bf-form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .bf-form-group {
            margin-bottom: 24px;
        }
        .bf-form-group label {
            display: block;
            font-size: 13px;
            color: #333;
            margin-bottom: 8px;
            font-weight: 500;
            letter-spacing: 0.05em;
        }
        .bf-form-group input,
        .bf-form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 0; /* Minimalist */
            font-size: 15px;
            transition: all 0.3s;
            background: #f9f9f9;
            font-family: inherit;
        }
        .bf-form-group input:focus,
        .bf-form-group textarea:focus {
            outline: none;
            border-color: #8A6754;
            background: #fff;
        }
        
        /* Remove number spinner */
        .bf-form-group input[type=number]::-webkit-inner-spin-button, 
        .bf-form-group input[type=number]::-webkit-outer-spin-button { 
            -webkit-appearance: none; 
            margin: 0; 
        }
        
        .bf-form-footer {
            text-align: center;
            margin-top: 30px;
        }
        .bf-submit-btn {
            background-color: #333;
            color: #fff;
            border: 1px solid #333;
            padding: 14px 48px;
            font-size: 15px;
            cursor: pointer;
            letter-spacing: 0.1em;
            transition: all 0.3s;
        }
        .bf-submit-btn:hover {
            background-color: #fff;
            color: #333;
        }
        .bf-submit-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        #bf-form-status {
            margin-top: 20px;
            font-size: 14px;
            min-height: 20px;
        }
        .bf-msg-success { color: #2ecc71; }
        .bf-msg-error { color: #e74c3c; }

        @media (max-width: 600px) {
            .bf-form-row { grid-template-columns: 1fr; gap: 0; }
        }
    </style>

    <script>
    jQuery(document).ready(function($) {
        $('#bf-contact-form').on('submit', function(e) {
            e.preventDefault();
            var form = $(this);
            var btn = $('#bf-submit-btn');
            var status = $('#bf-form-status');
            
            // Collect data
            var data = form.serialize(); // Use serialize to include all fields including hidden hash
            data += '&action=bf_contact_send';
            data += '&nonce=<?php echo wp_create_nonce('bf_contact_ajax_nonce'); ?>';

            // Loading state
            btn.prop('disabled', true).text('發送中...');
            status.text('');

            $.post('<?php echo admin_url('admin-ajax.php'); ?>', data, function(res) {
                btn.prop('disabled', false).text('送出訊息');
                
                if (res.success) {
                    status.html('<span class="bf-msg-success">' + res.data.msg + '</span>');
                    form[0].reset();
                } else {
                    status.html('<span class="bf-msg-error">' + (res.data.msg || '發生錯誤') + '</span>');
                }
            }).fail(function() {
                btn.prop('disabled', false).text('送出訊息');
                status.html('<span class="bf-msg-error">網路連線錯誤，請稍後再試。</span>');
            });
        });
    });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('bf_contact_form', 'bf_contact_shortcode');
