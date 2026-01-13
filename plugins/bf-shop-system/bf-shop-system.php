<?php
/**
 * Plugin Name: BF Shop System - 飛熊商店系統
 * Plugin URI: https://a1.haotaimaker.com/
 * Description: 統一管理商店頁面樣式：Fly Cart、商品列表、商品頁、購物車、結帳頁
 * Version: 1.0.0
 * Author: Bear's Fantasyland
 * Text Domain: bf-shop-system
 * Requires Plugins: woocommerce
 */

if (!defined('ABSPATH')) exit;

class BF_Shop_System {

    private static $instance = null;
    private $option_name = 'bf_shop_system_options';
    private $plugin_path;
    private $plugin_url;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        $this->plugin_path = plugin_dir_path(__FILE__);
        $this->plugin_url = plugin_dir_url(__FILE__);

        // 後台設定
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));

        // 載入模組
        add_action('plugins_loaded', array($this, 'load_modules'));
    }

    public function get_defaults() {
        return array(
            // Fly Cart
            'fly_cart_enabled' => true,
            'fly_cart_free_shipping' => 3000,
            'fly_cart_show_coupon' => true,
            
            // Shop 列表
            'shop_enabled' => true,
            'shop_quick_view' => true,
            'shop_auto_redirect' => true,
            
            // Product 商品頁
            'product_enabled' => true,
            'product_sticky_bar' => true,
            
            // Cart 購物車
            'cart_enabled' => true,
            'cart_continue_shopping' => true,
            
            // Checkout 結帳
            'checkout_enabled' => true,
        );
    }

    public function get_options() {
        $options = get_option($this->option_name);
        return empty($options) ? $this->get_defaults() : wp_parse_args($options, $this->get_defaults());
    }

    public function add_admin_menu() {
        add_menu_page(
            'BF 商店系統',
            '🐻 商店系統',
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

    public function admin_scripts($hook) {
        if ($hook !== 'toplevel_page_bf-shop-system') return;
        ?>
        <style>
            .bf-admin-wrap{max-width:900px;margin:20px auto;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif}
            .bf-admin-header{background:linear-gradient(135deg,#8A6754,#6B4F3F);color:#fff;padding:30px 40px;border-radius:16px 16px 0 0}
            .bf-admin-header h1{margin:0;font-size:28px;font-weight:600;letter-spacing:1px}
            .bf-admin-header p{margin:10px 0 0;opacity:0.9;font-size:14px}
            .bf-admin-tabs{display:flex;background:#f1ede9;border-bottom:1px solid #e0d8d0}
            .bf-admin-tab{padding:14px 24px;background:none;border:none;cursor:pointer;font-size:14px;color:#666;transition:all 0.3s;border-bottom:3px solid transparent}
            .bf-admin-tab:hover{color:#8A6754}
            .bf-admin-tab.active{color:#8A6754;border-bottom-color:#8A6754;background:#fff}
            .bf-admin-content{background:#fff;padding:30px 40px;border-radius:0 0 16px 16px;box-shadow:0 4px 20px rgba(0,0,0,0.08)}
            .bf-admin-panel{display:none}
            .bf-admin-panel.active{display:block}
            .bf-field{margin-bottom:24px;padding-bottom:24px;border-bottom:1px solid #f0ebe6}
            .bf-field:last-child{border-bottom:none;margin-bottom:0;padding-bottom:0}
            .bf-field-row{display:flex;justify-content:space-between;align-items:center}
            .bf-field-label{font-weight:600;color:#333}
            .bf-field-desc{font-size:13px;color:#888;margin-top:4px}
            .bf-toggle{position:relative;width:50px;height:28px}
            .bf-toggle input{opacity:0;width:0;height:0}
            .bf-toggle-slider{position:absolute;cursor:pointer;inset:0;background:#ccc;border-radius:28px;transition:0.3s}
            .bf-toggle-slider:before{content:'';position:absolute;height:22px;width:22px;left:3px;bottom:3px;background:#fff;border-radius:50%;transition:0.3s}
            .bf-toggle input:checked+.bf-toggle-slider{background:#8A6754}
            .bf-toggle input:checked+.bf-toggle-slider:before{transform:translateX(22px)}
            .bf-input{padding:10px 14px;border:1px solid #ddd;border-radius:8px;width:120px;font-size:14px}
            .bf-submit{background:linear-gradient(135deg,#8A6754,#6B4F3F);color:#fff;border:none;padding:14px 40px;font-size:16px;border-radius:10px;cursor:pointer;margin-top:20px;transition:all 0.3s}
            .bf-submit:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(138,103,84,0.3)}
            .bf-status{display:inline-flex;align-items:center;gap:6px;padding:4px 10px;border-radius:20px;font-size:12px;font-weight:600}
            .bf-status.on{background:#d4edda;color:#155724}
            .bf-status.off{background:#f8d7da;color:#721c24}
        </style>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var tabs = document.querySelectorAll('.bf-admin-tab');
            var panels = document.querySelectorAll('.bf-admin-panel');
            tabs.forEach(function(tab) {
                tab.addEventListener('click', function() {
                    tabs.forEach(function(t) { t.classList.remove('active'); });
                    panels.forEach(function(p) { p.classList.remove('active'); });
                    tab.classList.add('active');
                    document.getElementById(tab.dataset.tab).classList.add('active');
                });
            });
        });
        </script>
        <?php
    }

    public function settings_page() {
        $o = $this->get_options();
        ?>
        <div class="bf-admin-wrap">
            <div class="bf-admin-header">
                <h1>🐻 BF Shop System</h1>
                <p>統一管理飛熊入夢商店的所有頁面樣式</p>
            </div>
            <div class="bf-admin-tabs">
                <button class="bf-admin-tab active" data-tab="tab-fly-cart">🛒 Fly Cart</button>
                <button class="bf-admin-tab" data-tab="tab-shop">📦 商品列表</button>
                <button class="bf-admin-tab" data-tab="tab-product">🏷️ 商品頁</button>
                <button class="bf-admin-tab" data-tab="tab-cart">🛍️ 購物車</button>
                <button class="bf-admin-tab" data-tab="tab-checkout">💳 結帳頁</button>
            </div>
            <form method="post" action="options.php">
                <?php settings_fields($this->option_name); ?>
                <div class="bf-admin-content">
                    
                    <!-- Fly Cart Tab -->
                    <div id="tab-fly-cart" class="bf-admin-panel active">
                        <div class="bf-field">
                            <div class="bf-field-row">
                                <div>
                                    <div class="bf-field-label">啟用 Fly Cart</div>
                                    <div class="bf-field-desc">側邊滑出式購物車</div>
                                </div>
                                <label class="bf-toggle">
                                    <input type="checkbox" name="<?php echo $this->option_name; ?>[fly_cart_enabled]" value="1" <?php checked($o['fly_cart_enabled']); ?>>
                                    <span class="bf-toggle-slider"></span>
                                </label>
                            </div>
                        </div>
                        <div class="bf-field">
                            <div class="bf-field-row">
                                <div>
                                    <div class="bf-field-label">免運門檻 (NT$)</div>
                                    <div class="bf-field-desc">滿額免運的金額</div>
                                </div>
                                <input type="number" class="bf-input" name="<?php echo $this->option_name; ?>[fly_cart_free_shipping]" value="<?php echo esc_attr($o['fly_cart_free_shipping']); ?>" min="0" step="100">
                            </div>
                        </div>
                        <div class="bf-field">
                            <div class="bf-field-row">
                                <div>
                                    <div class="bf-field-label">顯示優惠券輸入</div>
                                    <div class="bf-field-desc">在 Fly Cart 中顯示優惠券輸入框</div>
                                </div>
                                <label class="bf-toggle">
                                    <input type="checkbox" name="<?php echo $this->option_name; ?>[fly_cart_show_coupon]" value="1" <?php checked($o['fly_cart_show_coupon']); ?>>
                                    <span class="bf-toggle-slider"></span>
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Shop Tab -->
                    <div id="tab-shop" class="bf-admin-panel">
                        <div class="bf-field">
                            <div class="bf-field-row">
                                <div>
                                    <div class="bf-field-label">啟用商品列表樣式</div>
                                    <div class="bf-field-desc">覆蓋 WooCommerce 預設商品列表</div>
                                </div>
                                <label class="bf-toggle">
                                    <input type="checkbox" name="<?php echo $this->option_name; ?>[shop_enabled]" value="1" <?php checked($o['shop_enabled']); ?>>
                                    <span class="bf-toggle-slider"></span>
                                </label>
                            </div>
                        </div>
                        <div class="bf-field">
                            <div class="bf-field-row">
                                <div>
                                    <div class="bf-field-label">Quick View 快速查看</div>
                                    <div class="bf-field-desc">在商品卡片上顯示快速查看按鈕</div>
                                </div>
                                <label class="bf-toggle">
                                    <input type="checkbox" name="<?php echo $this->option_name; ?>[shop_quick_view]" value="1" <?php checked($o['shop_quick_view']); ?>>
                                    <span class="bf-toggle-slider"></span>
                                </label>
                            </div>
                        </div>
                        <div class="bf-field">
                            <div class="bf-field-row">
                                <div>
                                    <div class="bf-field-label">首頁自動轉址</div>
                                    <div class="bf-field-desc">首頁自動轉到商品列表</div>
                                </div>
                                <label class="bf-toggle">
                                    <input type="checkbox" name="<?php echo $this->option_name; ?>[shop_auto_redirect]" value="1" <?php checked($o['shop_auto_redirect']); ?>>
                                    <span class="bf-toggle-slider"></span>
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Product Tab -->
                    <div id="tab-product" class="bf-admin-panel">
                        <div class="bf-field">
                            <div class="bf-field-row">
                                <div>
                                    <div class="bf-field-label">啟用商品頁樣式</div>
                                    <div class="bf-field-desc">愛馬仕風格單一商品頁</div>
                                </div>
                                <label class="bf-toggle">
                                    <input type="checkbox" name="<?php echo $this->option_name; ?>[product_enabled]" value="1" <?php checked($o['product_enabled']); ?>>
                                    <span class="bf-toggle-slider"></span>
                                </label>
                            </div>
                        </div>
                        <div class="bf-field">
                            <div class="bf-field-row">
                                <div>
                                    <div class="bf-field-label">Sticky Add to Cart</div>
                                    <div class="bf-field-desc">滾動時顯示固定購物車按鈕</div>
                                </div>
                                <label class="bf-toggle">
                                    <input type="checkbox" name="<?php echo $this->option_name; ?>[product_sticky_bar]" value="1" <?php checked($o['product_sticky_bar']); ?>>
                                    <span class="bf-toggle-slider"></span>
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Cart Tab -->
                    <div id="tab-cart" class="bf-admin-panel">
                        <div class="bf-field">
                            <div class="bf-field-row">
                                <div>
                                    <div class="bf-field-label">啟用購物車頁樣式</div>
                                    <div class="bf-field-desc">美化 WooCommerce 購物車頁</div>
                                </div>
                                <label class="bf-toggle">
                                    <input type="checkbox" name="<?php echo $this->option_name; ?>[cart_enabled]" value="1" <?php checked($o['cart_enabled']); ?>>
                                    <span class="bf-toggle-slider"></span>
                                </label>
                            </div>
                        </div>
                        <div class="bf-field">
                            <div class="bf-field-row">
                                <div>
                                    <div class="bf-field-label">繼續購物按鈕</div>
                                    <div class="bf-field-desc">顯示「繼續購物」按鈕</div>
                                </div>
                                <label class="bf-toggle">
                                    <input type="checkbox" name="<?php echo $this->option_name; ?>[cart_continue_shopping]" value="1" <?php checked($o['cart_continue_shopping']); ?>>
                                    <span class="bf-toggle-slider"></span>
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Checkout Tab -->
                    <div id="tab-checkout" class="bf-admin-panel">
                        <div class="bf-field">
                            <div class="bf-field-row">
                                <div>
                                    <div class="bf-field-label">啟用結帳頁樣式</div>
                                    <div class="bf-field-desc">美化 WooCommerce 結帳頁（純 CSS）</div>
                                </div>
                                <label class="bf-toggle">
                                    <input type="checkbox" name="<?php echo $this->option_name; ?>[checkout_enabled]" value="1" <?php checked($o['checkout_enabled']); ?>>
                                    <span class="bf-toggle-slider"></span>
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="bf-submit">💾 儲存所有設定</button>
                </div>
            </form>
        </div>
        <?php
    }

    public function load_modules() {
        if (!class_exists('WooCommerce')) return;
        
        $o = $this->get_options();
        
        // 載入各模組
        if (!empty($o['fly_cart_enabled'])) {
            require_once $this->plugin_path . 'modules/fly-cart.php';
        }
        if (!empty($o['shop_enabled'])) {
            require_once $this->plugin_path . 'modules/shop.php';
        }
        if (!empty($o['product_enabled'])) {
            require_once $this->plugin_path . 'modules/product.php';
        }
        if (!empty($o['cart_enabled'])) {
            require_once $this->plugin_path . 'modules/cart.php';
        }
        if (!empty($o['checkout_enabled'])) {
            require_once $this->plugin_path . 'modules/checkout.php';
        }
    }
}

// Initialize
BF_Shop_System::get_instance();
