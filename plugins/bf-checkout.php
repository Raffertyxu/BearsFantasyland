<?php
/**
 * Plugin Name: BF Checkout - 飛熊結帳頁
 * Plugin URI: https://a1.haotaimaker.com/
 * Description: 愛馬仕風格結帳頁面樣式（純 CSS，不影響邏輯）
 * Version: 1.0.0
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
        
        // 只載入樣式，不改變任何邏輯
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
    }

    public function get_defaults() {
        return array(
            'enabled' => true,
        );
    }

    public function get_options() {
        $options = get_option($this->option_name);
        return empty($options) ? $this->get_defaults() : wp_parse_args($options, $this->get_defaults());
    }

    public function add_admin_menu() {
        add_submenu_page(
            'woocommerce',
            'BF 結帳頁設定',
            '💳 BF 結帳頁',
            'manage_options',
            'bf-checkout-settings',
            array($this, 'settings_page')
        );
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
            .bf-checkout-admin .checkbox-row{display:flex;align-items:center;gap:10px;padding:12px 16px;background:#f9f7f5;border-radius:8px;margin-bottom:10px}
            .bf-checkout-admin .checkbox-row input{width:18px;height:18px;accent-color:#8A6754}
            .bf-checkout-admin .submit-btn{background:linear-gradient(135deg,#8A6754,#6B4F3F);color:#fff;border:none;padding:14px 40px;font-size:16px;border-radius:8px;cursor:pointer}
            .bf-checkout-admin .info{background:#fff3cd;padding:16px;border-radius:8px;border-left:4px solid #ffc107;margin-bottom:20px}
        </style>
        <div class="bf-checkout-admin">
            <h1>💳 BF 結帳頁設定</h1>
            <div class="info">
                ⚠️ 此外掛僅修改結帳頁的 CSS 樣式，不會影響任何付款或表單邏輯。
            </div>
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

    /**
     * 載入樣式
     */
    public function enqueue_styles() {
        if (!is_checkout()) return;
        
        $o = $this->get_options();
        if (empty($o['enabled'])) return;

        add_action('wp_head', array($this, 'output_styles'));
    }

    /**
     * 輸出 CSS（純樣式，不影響邏輯）
     */
    public function output_styles() {
        ?>
<style>
/* ========== BF Checkout Styles ========== */
/* 純 CSS 美化，不影響任何邏輯 */

:root {
    --bfck-brown: #8A6754;
    --bfck-brown-light: #A88B7A;
    --bfck-cream: #F9F7F5;
    --bfck-text: #333333;
    --bfck-text-light: #777777;
    --bfck-border: #E8E4E0;
    --bfck-white: #FFFFFF;
    --bfck-success: #4CAF50;
}

/* Page Container */
.woocommerce-checkout {
    font-family: 'Noto Sans TC', -apple-system, sans-serif;
}

.woocommerce-checkout .woocommerce {
    max-width: 1200px;
    margin: 0 auto;
    padding: 60px 20px;
}

/* Page Title */
.woocommerce-checkout .entry-title,
.woocommerce-checkout .page-title {
    font-family: 'Noto Serif TC', serif;
    font-size: 36px;
    font-weight: 600;
    color: var(--bfck-text);
    text-align: center;
    margin-bottom: 50px;
    letter-spacing: 4px;
}

/* Two Column Layout */
.woocommerce-checkout .col2-set {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 40px;
    margin-bottom: 40px;
}

@media (max-width: 768px) {
    .woocommerce-checkout .col2-set {
        grid-template-columns: 1fr;
        gap: 30px;
    }
}

/* Section Headings */
.woocommerce-checkout h3 {
    font-family: 'Noto Serif TC', serif;
    font-size: 22px;
    font-weight: 600;
    color: var(--bfck-text);
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 2px solid var(--bfck-brown);
    letter-spacing: 2px;
}

/* Form Fields */
.woocommerce-checkout .woocommerce-billing-fields__field-wrapper,
.woocommerce-checkout .woocommerce-shipping-fields__field-wrapper,
.woocommerce-checkout .woocommerce-additional-fields__field-wrapper {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
}

.woocommerce-checkout .form-row {
    margin: 0;
    padding: 0;
    flex: 1 1 calc(50% - 8px);
}

.woocommerce-checkout .form-row-wide {
    flex: 1 1 100%;
}

.woocommerce-checkout .form-row-first,
.woocommerce-checkout .form-row-last {
    flex: 1 1 calc(50% - 8px);
}

@media (max-width: 500px) {
    .woocommerce-checkout .form-row,
    .woocommerce-checkout .form-row-first,
    .woocommerce-checkout .form-row-last {
        flex: 1 1 100%;
    }
}

/* Labels */
.woocommerce-checkout label {
    font-size: 14px;
    font-weight: 500;
    color: var(--bfck-text);
    margin-bottom: 8px;
    display: block;
}

.woocommerce-checkout label .required {
    color: var(--bfck-brown);
}

/* Input Fields */
.woocommerce-checkout input[type="text"],
.woocommerce-checkout input[type="email"],
.woocommerce-checkout input[type="tel"],
.woocommerce-checkout input[type="password"],
.woocommerce-checkout input[type="number"],
.woocommerce-checkout textarea,
.woocommerce-checkout select,
.woocommerce-checkout .select2-container--default .select2-selection--single {
    width: 100%;
    padding: 14px 18px;
    border: 1px solid var(--bfck-border);
    border-radius: 10px;
    font-size: 15px;
    color: var(--bfck-text);
    background: var(--bfck-white);
    transition: all 0.3s;
}

.woocommerce-checkout input:focus,
.woocommerce-checkout textarea:focus,
.woocommerce-checkout select:focus {
    outline: none;
    border-color: var(--bfck-brown);
    box-shadow: 0 0 0 3px rgba(138, 103, 84, 0.1);
}

/* Select2 Dropdown */
.woocommerce-checkout .select2-container--default .select2-selection--single {
    height: auto;
    padding: 10px 14px;
}

.woocommerce-checkout .select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 1.6;
    padding: 0;
    color: var(--bfck-text);
}

.woocommerce-checkout .select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 100%;
    right: 12px;
}

/* Textarea */
.woocommerce-checkout textarea {
    min-height: 120px;
    resize: vertical;
}

/* Checkbox & Radio */
.woocommerce-checkout input[type="checkbox"],
.woocommerce-checkout input[type="radio"] {
    width: 18px;
    height: 18px;
    accent-color: var(--bfck-brown);
    margin-right: 8px;
}

/* Order Review Section */
.woocommerce-checkout #order_review_heading {
    font-family: 'Noto Serif TC', serif;
    font-size: 24px;
    font-weight: 600;
    color: var(--bfck-text);
    margin-bottom: 24px;
    letter-spacing: 2px;
}

/* Order Table */
.woocommerce-checkout table.shop_table {
    border: none;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0,0,0,0.05);
    background: var(--bfck-white);
    margin-bottom: 30px;
}

.woocommerce-checkout table.shop_table thead {
    background: var(--bfck-cream);
}

.woocommerce-checkout table.shop_table thead th {
    font-size: 13px;
    font-weight: 600;
    color: var(--bfck-text-light);
    text-transform: uppercase;
    letter-spacing: 1px;
    padding: 16px 20px;
    border: none;
}

.woocommerce-checkout table.shop_table tbody td,
.woocommerce-checkout table.shop_table tfoot td,
.woocommerce-checkout table.shop_table tfoot th {
    padding: 18px 20px;
    border: none;
    border-bottom: 1px solid var(--bfck-border);
}

.woocommerce-checkout table.shop_table tfoot tr:last-child td,
.woocommerce-checkout table.shop_table tfoot tr:last-child th {
    border-bottom: none;
}

.woocommerce-checkout table.shop_table .order-total {
    background: var(--bfck-cream);
}

.woocommerce-checkout table.shop_table .order-total th,
.woocommerce-checkout table.shop_table .order-total td {
    font-size: 18px;
    font-weight: 700;
    color: var(--bfck-brown);
}

/* Payment Methods */
.woocommerce-checkout #payment {
    background: var(--bfck-cream);
    border-radius: 16px;
    padding: 30px;
}

.woocommerce-checkout #payment ul.payment_methods {
    list-style: none;
    padding: 0;
    margin: 0 0 24px;
    border: none;
}

.woocommerce-checkout #payment ul.payment_methods li {
    padding: 16px;
    margin-bottom: 12px;
    background: var(--bfck-white);
    border-radius: 12px;
    border: 1px solid var(--bfck-border);
    transition: all 0.3s;
}

.woocommerce-checkout #payment ul.payment_methods li:hover {
    border-color: var(--bfck-brown);
}

.woocommerce-checkout #payment ul.payment_methods li label {
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    margin: 0;
}

.woocommerce-checkout #payment div.payment_box {
    background: transparent;
    color: var(--bfck-text-light);
    font-size: 14px;
    padding: 12px 0 0 26px;
    margin: 0;
}

.woocommerce-checkout #payment div.payment_box::before {
    display: none;
}

/* Place Order Button */
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
}

.woocommerce-checkout #place_order:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(138, 103, 84, 0.4);
}

/* Privacy Policy */
.woocommerce-checkout .woocommerce-privacy-policy-text {
    font-size: 13px;
    color: var(--bfck-text-light);
    margin-bottom: 20px;
}

.woocommerce-checkout .woocommerce-privacy-policy-text a {
    color: var(--bfck-brown);
}

/* Terms Checkbox */
.woocommerce-checkout .woocommerce-terms-and-conditions-wrapper {
    margin-bottom: 20px;
}

/* Coupon */
.woocommerce-checkout .checkout_coupon {
    background: var(--bfck-cream);
    border: none;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 30px;
}

.woocommerce-checkout .checkout_coupon p {
    display: flex;
    gap: 12px;
    margin: 0;
}

.woocommerce-checkout .checkout_coupon input[type="text"] {
    flex: 1;
}

.woocommerce-checkout .checkout_coupon button {
    padding: 14px 24px;
    background: var(--bfck-brown);
    color: var(--bfck-white);
    border: none;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}

.woocommerce-checkout .checkout_coupon button:hover {
    background: #6B4F3F;
}

/* Login/Coupon Notice */
.woocommerce-checkout .woocommerce-info {
    background: var(--bfck-cream);
    border: none;
    border-left: 4px solid var(--bfck-brown);
    border-radius: 8px;
    padding: 16px 20px;
    color: var(--bfck-text);
    margin-bottom: 20px;
}

.woocommerce-checkout .woocommerce-info a {
    color: var(--bfck-brown);
    font-weight: 600;
}

/* Error Messages */
.woocommerce-checkout .woocommerce-error {
    background: #FEE2E2;
    border: none;
    border-left: 4px solid #EF4444;
    border-radius: 8px;
    padding: 16px 20px;
    color: #991B1B;
    list-style: none;
}

/* Success Messages */
.woocommerce-checkout .woocommerce-message {
    background: #D1FAE5;
    border: none;
    border-left: 4px solid var(--bfck-success);
    border-radius: 8px;
    padding: 16px 20px;
    color: #065F46;
}

/* Create Account Checkbox */
.woocommerce-checkout .create-account {
    background: var(--bfck-cream);
    padding: 20px;
    border-radius: 12px;
    margin-top: 16px;
}

/* Ship to Different Address */
.woocommerce-checkout .ship-to-different-address {
    margin-bottom: 20px;
}

.woocommerce-checkout .ship-to-different-address label {
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .woocommerce-checkout .woocommerce {
        padding: 30px 16px;
    }
    
    .woocommerce-checkout .entry-title,
    .woocommerce-checkout .page-title {
        font-size: 28px;
        margin-bottom: 30px;
    }
    
    .woocommerce-checkout h3 {
        font-size: 20px;
    }
    
    .woocommerce-checkout #payment {
        padding: 20px;
    }
    
    .woocommerce-checkout #place_order {
        font-size: 16px;
        padding: 16px 24px;
    }
    
    .woocommerce-checkout .checkout_coupon p {
        flex-direction: column;
    }
}
</style>
        <?php
    }
}

// Initialize
add_action('plugins_loaded', function() {
    if (class_exists('WooCommerce')) {
        BF_Checkout::get_instance();
    }
});
