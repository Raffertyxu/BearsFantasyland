<?php
/**
 * Plugin Name: BF Shop System
 * Plugin URI: https://a1.haotaimaker.com/
 * Description: 統一管理商店頁面樣式
 * Version: 1.0.0
 * Author: Bear's Fantasyland
 * Text Domain: bf-shop-system
 * Requires Plugins: woocommerce
 */

if (!defined('ABSPATH')) exit;

// 定義路徑常數
define('BF_SHOP_SYSTEM_PATH', plugin_dir_path(__FILE__));

class BF_Shop_System {

    private static $instance = null;
    private $option_name = 'bf_shop_system_options';

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_head', array($this, 'admin_styles'));
        add_action('plugins_loaded', array($this, 'load_modules'), 20);
    }

    public function get_defaults() {
        return array(
            'fly_cart_enabled' => true,
            'fly_cart_free_shipping' => 3000,
            'fly_cart_show_coupon' => true,
            'shop_enabled' => true,
            'shop_auto_redirect' => false,
            'product_enabled' => true,
            'product_sticky_bar' => true,
            'cart_enabled' => true,
            'checkout_enabled' => true,
        );
    }

    public function get_options() {
        $options = get_option($this->option_name);
        return empty($options) ? $this->get_defaults() : wp_parse_args($options, $this->get_defaults());
    }

    public function add_admin_menu() {
        add_menu_page(
            'BF Shop System',
            'BF Shop System',
            'manage_options',
            'bf-shop-system',
            array($this, 'settings_page'),
            'dashicons-store',
            56
        );
    }

    public function register_settings() {
        register_setting($this->option_name, $this->option_name);
    }

    public function admin_styles() {
        $screen = get_current_screen();
        if ($screen->id !== 'toplevel_page_bf-shop-system') return;
        echo '<style>
        .bf-admin{max-width:800px;margin:20px auto;font-family:-apple-system,sans-serif}
        .bf-admin h1{color:#8A6754;margin-bottom:30px}
        .bf-admin .card{background:#fff;padding:24px;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,0.08);margin-bottom:20px}
        .bf-admin .card h2{margin:0 0 20px;font-size:18px;color:#333;border-bottom:2px solid #8A6754;padding-bottom:10px}
        .bf-admin .field{display:flex;justify-content:space-between;align-items:center;padding:12px 0;border-bottom:1px solid #f0f0f0}
        .bf-admin .field:last-child{border-bottom:none}
        .bf-admin .field label{font-weight:500}
        .bf-admin .field small{display:block;color:#888;font-size:12px;margin-top:4px}
        .bf-admin input[type=checkbox]{width:20px;height:20px;accent-color:#8A6754}
        .bf-admin input[type=number]{padding:8px 12px;border:1px solid #ddd;border-radius:6px;width:100px}
        .bf-admin .submit-btn{background:#8A6754;color:#fff;border:none;padding:14px 40px;font-size:16px;border-radius:8px;cursor:pointer;margin-top:20px}
        .bf-admin .submit-btn:hover{background:#6B4F3F}
        </style>';
    }

    public function settings_page() {
        $o = $this->get_options();
        ?>
        <div class="bf-admin">
            <h1>BF Shop System</h1>
            <form method="post" action="options.php">
                <?php settings_fields($this->option_name); ?>
                
                <div class="card">
                    <h2>Fly Cart</h2>
                    <div class="field">
                        <div><label>啟用</label><small>側邊滑出式購物車</small></div>
                        <input type="checkbox" name="<?php echo $this->option_name; ?>[fly_cart_enabled]" value="1" <?php checked($o['fly_cart_enabled']); ?>>
                    </div>
                    <div class="field">
                        <div><label>免運門檻</label><small>NT$</small></div>
                        <input type="number" name="<?php echo $this->option_name; ?>[fly_cart_free_shipping]" value="<?php echo esc_attr($o['fly_cart_free_shipping']); ?>" min="0" step="100">
                    </div>
                    <div class="field">
                        <div><label>顯示優惠券</label><small>在購物車中顯示優惠券輸入框</small></div>
                        <input type="checkbox" name="<?php echo $this->option_name; ?>[fly_cart_show_coupon]" value="1" <?php checked($o['fly_cart_show_coupon']); ?>>
                    </div>
                </div>
                
                <div class="card">
                    <h2>商品列表</h2>
                    <div class="field">
                        <div><label>啟用</label><small>商品列表頁樣式</small></div>
                        <input type="checkbox" name="<?php echo $this->option_name; ?>[shop_enabled]" value="1" <?php checked($o['shop_enabled']); ?>>
                    </div>
                    <div class="field">
                        <div><label>首頁轉址</label><small>首頁自動轉到商店頁</small></div>
                        <input type="checkbox" name="<?php echo $this->option_name; ?>[shop_auto_redirect]" value="1" <?php checked($o['shop_auto_redirect']); ?>>
                    </div>
                </div>
                
                <div class="card">
                    <h2>商品頁</h2>
                    <div class="field">
                        <div><label>啟用</label><small>單一商品頁樣式</small></div>
                        <input type="checkbox" name="<?php echo $this->option_name; ?>[product_enabled]" value="1" <?php checked($o['product_enabled']); ?>>
                    </div>
                    <div class="field">
                        <div><label>Sticky Bar</label><small>滾動時顯示固定購物車按鈕</small></div>
                        <input type="checkbox" name="<?php echo $this->option_name; ?>[product_sticky_bar]" value="1" <?php checked($o['product_sticky_bar']); ?>>
                    </div>
                </div>
                
                <div class="card">
                    <h2>購物車 / 結帳</h2>
                    <div class="field">
                        <div><label>購物車頁樣式</label><small>美化 WooCommerce 購物車頁</small></div>
                        <input type="checkbox" name="<?php echo $this->option_name; ?>[cart_enabled]" value="1" <?php checked($o['cart_enabled']); ?>>
                    </div>
                    <div class="field">
                        <div><label>結帳頁樣式</label><small>美化 WooCommerce 結帳頁</small></div>
                        <input type="checkbox" name="<?php echo $this->option_name; ?>[checkout_enabled]" value="1" <?php checked($o['checkout_enabled']); ?>>
                    </div>
                </div>
                
                <button type="submit" class="submit-btn">儲存設定</button>
            </form>
        </div>
        <?php
    }

    public function load_modules() {
        if (!class_exists('WooCommerce')) return;
        
        $o = $this->get_options();
        
        if (!empty($o['fly_cart_enabled'])) {
            $file = BF_SHOP_SYSTEM_PATH . 'modules/fly-cart.php';
            if (file_exists($file)) require_once $file;
        }
        if (!empty($o['shop_enabled'])) {
            $file = BF_SHOP_SYSTEM_PATH . 'modules/shop.php';
            if (file_exists($file)) require_once $file;
        }
        if (!empty($o['product_enabled'])) {
            $file = BF_SHOP_SYSTEM_PATH . 'modules/product.php';
            if (file_exists($file)) require_once $file;
        }
        if (!empty($o['cart_enabled'])) {
            $file = BF_SHOP_SYSTEM_PATH . 'modules/cart.php';
            if (file_exists($file)) require_once $file;
        }
        if (!empty($o['checkout_enabled'])) {
            $file = BF_SHOP_SYSTEM_PATH . 'modules/checkout.php';
            if (file_exists($file)) require_once $file;
        }
    }
}

BF_Shop_System::get_instance();
