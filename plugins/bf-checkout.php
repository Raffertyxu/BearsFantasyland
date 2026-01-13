<?php
/**
 * Plugin Name: BF Checkout - 飛熊結帳頁
 * Plugin URI: https://a1.haotaimaker.com/
 * Description: 愛馬仕風格結帳頁面樣式（純 CSS，不影響邏輯）
 * Version: 1.0.1
 * Author: Bear's Fantasyland
 * Text Domain: bf-checkout
 * Requires Plugins: woocommerce
 */

if (!defined('ABSPATH')) exit;

class BF_Checkout {

    private static $instance = null;
    private $option_name = 'bf_checkout_options';

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
    }

    public function get_defaults() {
        return array('enabled' => true);
    }

    public function get_options() {
        $options = get_option($this->option_name);
        return empty($options) ? $this->get_defaults() : wp_parse_args($options, $this->get_defaults());
    }

    public function add_admin_menu() {
        add_submenu_page('woocommerce', 'BF 結帳頁設定', '💳 BF 結帳頁', 'manage_options', 'bf-checkout-settings', array($this, 'settings_page'));
    }

    public function register_settings() {
        register_setting($this->option_name, $this->option_name);
    }

    public function settings_page() {
        $o = $this->get_options();
        ?>
        <style>
            .bf-checkout-admin{max-width:700px;margin:20px auto;font-family:-apple-system,sans-serif}
            .bf-checkout-admin h1{color:#8A6754;margin-bottom:30px}
            .bf-checkout-admin .card{background:#fff;padding:24px;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,0.08);margin-bottom:20px}
            .bf-checkout-admin .checkbox-row{display:flex;align-items:center;gap:10px;padding:12px 16px;background:#f9f7f5;border-radius:8px}
            .bf-checkout-admin .checkbox-row input{width:18px;height:18px;accent-color:#8A6754}
            .bf-checkout-admin .submit-btn{background:linear-gradient(135deg,#8A6754,#6B4F3F);color:#fff;border:none;padding:14px 40px;font-size:16px;border-radius:8px;cursor:pointer}
        </style>
        <div class="bf-checkout-admin">
            <h1>💳 BF 結帳頁設定</h1>
            <form method="post" action="options.php">
                <?php settings_fields($this->option_name); ?>
                <div class="card">
                    <div class="checkbox-row">
                        <input type="checkbox" id="enabled" name="<?php echo $this->option_name; ?>[enabled]" value="1" <?php checked($o['enabled']); ?>>
                        <label for="enabled" style="margin:0">啟用自訂結帳頁樣式</label>
                    </div>
                </div>
                <button type="submit" class="submit-btn">💾 儲存設定</button>
            </form>
        </div>
        <?php
    }

    public function enqueue_styles() {
        if (!is_checkout()) return;
        $o = $this->get_options();
        if (empty($o['enabled'])) return;
        add_action('wp_head', array($this, 'output_styles'));
    }

    public function output_styles() {
        ?>
<style>
/* ========== BF Checkout Styles v2 ========== */
:root {
    --bfck-brown: #8A6754;
    --bfck-cream: #F9F7F5;
    --bfck-text: #333333;
    --bfck-text-light: #777777;
    --bfck-border: #E8E4E0;
    --bfck-white: #FFFFFF;
}

/* 整體背景 */
.woocommerce-checkout {
    font-family: 'Noto Sans TC', -apple-system, sans-serif;
    background: var(--bfck-cream);
}

.woocommerce-checkout .woocommerce {
    max-width: 900px;
    margin: 0 auto;
    padding: 50px 24px;
}

/* 標題 */
.woocommerce-checkout .entry-title,
.woocommerce-checkout .page-title {
    font-family: 'Noto Serif TC', serif;
    font-size: 32px;
    font-weight: 600;
    color: var(--bfck-text);
    text-align: center;
    margin-bottom: 40px;
    letter-spacing: 4px;
}

/* 提示訊息 */
.woocommerce-checkout .woocommerce-info {
    background: var(--bfck-white);
    border: none;
    border-left: 4px solid var(--bfck-brown);
    border-radius: 12px;
    padding: 18px 24px 18px 32px;
    margin-bottom: 24px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.04);
}

/* 隱藏原生 icon */
.woocommerce-checkout .woocommerce-info::before {
    display: none !important;
}

.woocommerce-checkout .woocommerce-info a {
    color: var(--bfck-brown);
    font-weight: 600;
}

/* 主表單卡片 */
.woocommerce-checkout form.checkout {
    background: var(--bfck-white);
    border-radius: 20px;
    padding: 40px;
    box-shadow: 0 4px 30px rgba(0,0,0,0.06);
    display: block !important;
}

/* 強制單欄佈局 */
.woocommerce-checkout #customer_details,
.woocommerce-checkout #order_review_heading,
.woocommerce-checkout #order_review {
    width: 100% !important;
    float: none !important;
    clear: both !important;
}

/* 區塊標題 */
.woocommerce-checkout h3,
.woocommerce-checkout #order_review_heading {
    font-family: 'Noto Serif TC', serif;
    font-size: 22px;
    font-weight: 600;
    color: var(--bfck-text);
    margin: 0 0 28px 0;
    padding-bottom: 14px;
    border-bottom: 2px solid var(--bfck-brown);
}

/* 帳單/運送區塊 */
.woocommerce-checkout .col2-set {
    margin-bottom: 30px;
}

.woocommerce-checkout .col2-set .col-1,
.woocommerce-checkout .col2-set .col-2 {
    width: 100% !important;
    float: none !important;
    margin-bottom: 30px;
}

/* 表單列間距 */
.woocommerce-checkout .form-row {
    margin-bottom: 20px !important;
    padding: 0 !important;
}

/* 標籤 */
.woocommerce-checkout label {
    font-size: 14px;
    font-weight: 600;
    color: var(--bfck-text);
    margin-bottom: 8px;
    display: block;
}

.woocommerce-checkout label .required {
    color: var(--bfck-brown);
}

.woocommerce-checkout label .optional {
    color: var(--bfck-text-light);
    font-weight: 400;
    font-size: 12px;
}

/* 輸入框 */
.woocommerce-checkout input[type="text"],
.woocommerce-checkout input[type="email"],
.woocommerce-checkout input[type="tel"],
.woocommerce-checkout input[type="password"],
.woocommerce-checkout input[type="number"],
.woocommerce-checkout textarea,
.woocommerce-checkout select {
    width: 100% !important;
    padding: 14px 18px !important;
    border: 2px solid var(--bfck-border) !important;
    border-radius: 10px !important;
    font-size: 15px !important;
    color: var(--bfck-text) !important;
    background: var(--bfck-white) !important;
    transition: all 0.3s !important;
    box-sizing: border-box !important;
}

.woocommerce-checkout input:focus,
.woocommerce-checkout textarea:focus,
.woocommerce-checkout select:focus {
    outline: none !important;
    border-color: var(--bfck-brown) !important;
    box-shadow: 0 0 0 4px rgba(138, 103, 84, 0.12) !important;
}

/* Select2 */
.woocommerce-checkout .select2-container {
    width: 100% !important;
}

.woocommerce-checkout .select2-container--default .select2-selection--single {
    height: auto !important;
    padding: 12px 40px 12px 16px !important;
    border: 2px solid var(--bfck-border) !important;
    border-radius: 10px !important;
    overflow: visible !important;
}

.woocommerce-checkout .select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 100% !important;
    right: 12px !important;
    top: 0 !important;
}

/* Checkbox/Radio */
.woocommerce-checkout input[type="checkbox"],
.woocommerce-checkout input[type="radio"] {
    width: 18px !important;
    height: 18px !important;
    accent-color: var(--bfck-brown);
    margin-right: 10px;
}

/* 訂單摘要區 */
.woocommerce-checkout #order_review {
    margin-top: 40px;
    padding-top: 30px;
    border-top: 1px solid var(--bfck-border);
}

/* 訂單表格 */
.woocommerce-checkout table.shop_table {
    border: none !important;
    border-radius: 14px !important;
    overflow: hidden;
    background: var(--bfck-cream);
    margin-bottom: 24px;
}

.woocommerce-checkout table.shop_table thead th {
    background: var(--bfck-cream);
    font-size: 13px;
    font-weight: 600;
    color: var(--bfck-text-light);
    padding: 16px 20px !important;
    border: none !important;
}

.woocommerce-checkout table.shop_table tbody td,
.woocommerce-checkout table.shop_table tfoot td,
.woocommerce-checkout table.shop_table tfoot th {
    padding: 16px 20px !important;
    border: none !important;
    background: var(--bfck-white);
}

.woocommerce-checkout table.shop_table .order-total td,
.woocommerce-checkout table.shop_table .order-total th {
    font-size: 18px !important;
    font-weight: 700;
    color: var(--bfck-brown);
    background: var(--bfck-cream);
}

/* 付款方式區 */
.woocommerce-checkout #payment {
    background: var(--bfck-cream);
    border-radius: 14px;
    padding: 28px;
    margin-top: 24px;
}

.woocommerce-checkout #payment ul.payment_methods {
    list-style: none;
    padding: 0;
    margin: 0 0 20px;
    border: none !important;
    background: none !important;
}

.woocommerce-checkout #payment ul.payment_methods li {
    padding: 18px !important;
    margin-bottom: 10px;
    background: var(--bfck-white);
    border-radius: 10px;
    border: 2px solid var(--bfck-border);
}

.woocommerce-checkout #payment ul.payment_methods li:hover {
    border-color: var(--bfck-brown);
}

.woocommerce-checkout #payment div.payment_box {
    background: var(--bfck-cream) !important;
    padding: 14px 18px !important;
    margin: 10px 0 0 26px !important;
    border-radius: 8px;
}

.woocommerce-checkout #payment div.payment_box::before {
    display: none !important;
}

/* 下單按鈕 */
.woocommerce-checkout #place_order {
    display: block;
    width: 100%;
    padding: 18px 32px;
    background: linear-gradient(135deg, var(--bfck-brown) 0%, #6B4F3F 100%);
    color: var(--bfck-white);
    border: none;
    border-radius: 12px;
    font-size: 18px;
    font-weight: 600;
    letter-spacing: 3px;
    cursor: pointer;
    transition: all 0.3s;
    margin-top: 16px;
}

.woocommerce-checkout #place_order:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(138, 103, 84, 0.35);
}

/* 隱私/條款 */
.woocommerce-checkout .woocommerce-privacy-policy-text {
    font-size: 13px;
    color: var(--bfck-text-light);
    margin: 16px 0;
    line-height: 1.7;
}

/* 錯誤訊息 */
.woocommerce-checkout .woocommerce-error {
    background: #FEE2E2;
    border-left: 4px solid #EF4444;
    border-radius: 10px;
    padding: 16px 20px;
    color: #991B1B;
    list-style: none;
    margin-bottom: 24px;
}

/* 手機版 */
@media (max-width: 768px) {
    .woocommerce-checkout .woocommerce {
        padding: 30px 16px;
    }
    
    .woocommerce-checkout form.checkout {
        padding: 24px 18px;
    }
    
    .woocommerce-checkout .entry-title {
        font-size: 26px;
        margin-bottom: 30px;
    }
    
    .woocommerce-checkout #payment {
        padding: 20px;
    }
    
    .woocommerce-checkout #place_order {
        font-size: 16px;
        padding: 16px 24px;
    }
}
</style>
        <?php
    }
}

add_action('plugins_loaded', function() {
    if (class_exists('WooCommerce')) {
        BF_Checkout::get_instance();
    }
});
