<?php
/**
 * Plugin Name: BF Shop Master - 飛熊商店總控台
 * Plugin URI: https://a1.haotaimaker.com/
 * Description: 統一管理所有飛熊商店外掛的設定
 * Version: 1.0.0
 * Author: Bear's Fantasyland
 * Text Domain: bf-shop-master
 */

if (!defined('ABSPATH')) exit;

class BF_Shop_Master {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
    }

    public function add_admin_menu() {
        add_menu_page(
            'BF 商店總控台',
            '🐻 商店總控台',
            'manage_options',
            'bf-shop-master',
            array($this, 'render_admin_page'),
            'dashicons-store',
            55
        );
    }

    public function admin_scripts($hook) {
        if ($hook !== 'toplevel_page_bf-shop-master') return;
        ?>
        <style>
            /* ========== BF Shop Master Admin ========== */
            .bfm-wrap {
                max-width: 1000px;
                margin: 20px auto;
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            }
            
            .bfm-header {
                background: linear-gradient(135deg, #8A6754 0%, #6B4F3F 100%);
                color: #fff;
                padding: 30px 40px;
                border-radius: 16px 16px 0 0;
                display: flex;
                align-items: center;
                gap: 20px;
            }
            
            .bfm-header h1 {
                margin: 0;
                font-size: 28px;
                font-weight: 600;
                letter-spacing: 2px;
            }
            
            .bfm-header p {
                margin: 8px 0 0;
                opacity: 0.9;
                font-size: 14px;
            }
            
            .bfm-tabs {
                display: flex;
                background: #f1ede9;
                border-bottom: 1px solid #e0d8d0;
                overflow-x: auto;
            }
            
            .bfm-tab {
                padding: 16px 28px;
                background: none;
                border: none;
                cursor: pointer;
                font-size: 14px;
                font-weight: 500;
                color: #888;
                border-bottom: 3px solid transparent;
                transition: all 0.3s;
                white-space: nowrap;
            }
            
            .bfm-tab:hover {
                color: #8A6754;
                background: rgba(138, 103, 84, 0.05);
            }
            
            .bfm-tab.active {
                color: #8A6754;
                border-bottom-color: #8A6754;
                background: #fff;
            }
            
            .bfm-content {
                background: #fff;
                border-radius: 0 0 16px 16px;
                box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            }
            
            .bfm-panel {
                display: none;
                padding: 30px 40px;
            }
            
            .bfm-panel.active {
                display: block;
            }
            
            .bfm-panel-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 24px;
                padding-bottom: 16px;
                border-bottom: 2px solid #8A6754;
            }
            
            .bfm-panel-title {
                font-size: 20px;
                font-weight: 600;
                color: #333;
                margin: 0;
            }
            
            .bfm-status {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                padding: 6px 14px;
                border-radius: 20px;
                font-size: 12px;
                font-weight: 600;
            }
            
            .bfm-status.active {
                background: #d4edda;
                color: #155724;
            }
            
            .bfm-status.inactive {
                background: #f8d7da;
                color: #721c24;
            }
            
            .bfm-field {
                margin-bottom: 24px;
                padding-bottom: 24px;
                border-bottom: 1px solid #f0ebe6;
            }
            
            .bfm-field:last-child {
                border-bottom: none;
                margin-bottom: 0;
                padding-bottom: 0;
            }
            
            .bfm-field-row {
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            
            .bfm-field-info {
                flex: 1;
            }
            
            .bfm-field-label {
                font-weight: 600;
                color: #333;
                font-size: 15px;
            }
            
            .bfm-field-desc {
                font-size: 13px;
                color: #888;
                margin-top: 4px;
            }
            
            .bfm-toggle {
                position: relative;
                width: 52px;
                height: 28px;
            }
            
            .bfm-toggle input {
                opacity: 0;
                width: 0;
                height: 0;
            }
            
            .bfm-toggle-slider {
                position: absolute;
                cursor: pointer;
                inset: 0;
                background: #ccc;
                border-radius: 28px;
                transition: 0.3s;
            }
            
            .bfm-toggle-slider:before {
                content: '';
                position: absolute;
                height: 22px;
                width: 22px;
                left: 3px;
                bottom: 3px;
                background: #fff;
                border-radius: 50%;
                transition: 0.3s;
            }
            
            .bfm-toggle input:checked + .bfm-toggle-slider {
                background: #8A6754;
            }
            
            .bfm-toggle input:checked + .bfm-toggle-slider:before {
                transform: translateX(24px);
            }
            
            .bfm-input {
                padding: 10px 14px;
                border: 1px solid #ddd;
                border-radius: 8px;
                width: 120px;
                font-size: 14px;
            }
            
            .bfm-input:focus {
                outline: none;
                border-color: #8A6754;
            }
            
            .bfm-select {
                padding: 10px 14px;
                border: 1px solid #ddd;
                border-radius: 8px;
                font-size: 14px;
                min-width: 150px;
            }
            
            .bfm-btn {
                padding: 14px 32px;
                background: linear-gradient(135deg, #8A6754, #6B4F3F);
                color: #fff;
                border: none;
                border-radius: 10px;
                font-size: 15px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s;
            }
            
            .bfm-btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 6px 20px rgba(138, 103, 84, 0.3);
            }
            
            .bfm-actions {
                display: flex;
                justify-content: flex-end;
                padding-top: 20px;
                margin-top: 20px;
                border-top: 1px solid #f0ebe6;
            }
            
            .bfm-notice {
                padding: 16px 20px;
                background: #fff8e6;
                border-left: 4px solid #ffc107;
                border-radius: 8px;
                margin-bottom: 24px;
                font-size: 14px;
                color: #856404;
            }
            
            .bfm-section-title {
                font-size: 16px;
                font-weight: 600;
                color: #666;
                margin: 30px 0 16px;
                padding-bottom: 10px;
                border-bottom: 1px dashed #ddd;
            }
            
            .bfm-section-title:first-child {
                margin-top: 0;
            }
        </style>
        <?php
    }

    public function render_admin_page() {
        // 取得各外掛的設定
        $fly_cart_options = get_option('bf_fly_cart_options', array());
        $shop_options = get_option('bf_shop_options', array('enabled' => true, 'products_per_page' => 12, 'columns' => 4, 'show_filters' => true, 'show_sorting' => true, 'show_quick_view' => true, 'default_orderby' => 'date'));
        $product_options = get_option('bf_product_options', array('enabled' => true, 'show_related' => true, 'related_count' => 4, 'show_tabs' => true, 'show_reviews' => true));
        $cart_options = get_option('bf_cart_options', array('enabled' => true, 'free_shipping_min' => 3000, 'show_continue_shopping' => true));
        $checkout_options = get_option('bf_checkout_options', array('enabled' => true));
        
        // 檢查外掛是否啟用
        $active_plugins = get_option('active_plugins', array());
        $fly_cart_active = in_array('bf-fly-cart.php', array_map('basename', $active_plugins));
        $shop_active = in_array('bf-shop.php', array_map('basename', $active_plugins));
        $product_active = in_array('bf-product.php', array_map('basename', $active_plugins));
        $cart_active = in_array('bf-cart.php', array_map('basename', $active_plugins));
        $checkout_active = in_array('bf-checkout.php', array_map('basename', $active_plugins));
        ?>
        <div class="bfm-wrap">
            <div class="bfm-header">
                <div>
                    <h1>🐻 飛熊商店總控台</h1>
                    <p>統一管理所有商店外掛的設定</p>
                </div>
            </div>
            
            <div class="bfm-tabs">
                <button class="bfm-tab active" data-tab="fly-cart">🛒 Fly Cart</button>
                <button class="bfm-tab" data-tab="shop">📦 商品列表</button>
                <button class="bfm-tab" data-tab="product">🏷️ 商品頁</button>
                <button class="bfm-tab" data-tab="cart">🛍️ 購物車</button>
                <button class="bfm-tab" data-tab="checkout">💳 結帳頁</button>
            </div>
            
            <div class="bfm-content">
                <!-- Fly Cart Panel -->
                <div id="panel-fly-cart" class="bfm-panel active">
                    <div class="bfm-panel-header">
                        <h2 class="bfm-panel-title">🛒 Fly Cart 購物車抽屜</h2>
                        <span class="bfm-status <?php echo $fly_cart_active ? 'active' : 'inactive'; ?>">
                            <?php echo $fly_cart_active ? '✓ 已啟用' : '✗ 未啟用'; ?>
                        </span>
                    </div>
                    
                    <?php if (!$fly_cart_active): ?>
                    <div class="bfm-notice">請先在「外掛」頁面啟用 BF Fly Cart 外掛</div>
                    <?php endif; ?>
                    
                    <form method="post" action="options.php">
                        <?php settings_fields('bf_fly_cart_options'); ?>
                        <input type="hidden" name="bf_fly_cart_options[enabled]" value="1">
                        
                        <div class="bfm-field">
                            <div class="bfm-field-row">
                                <div class="bfm-field-info">
                                    <div class="bfm-field-label">免運門檻</div>
                                    <div class="bfm-field-desc">滿額免運的金額（NT$）</div>
                                </div>
                                <input type="number" class="bfm-input" name="bf_fly_cart_options[free_shipping_min]" 
                                       value="<?php echo esc_attr($fly_cart_options['free_shipping_min'] ?? 3000); ?>" 
                                       min="0" step="100">
                            </div>
                        </div>
                        
                        <div class="bfm-actions">
                            <button type="submit" class="bfm-btn">💾 儲存 Fly Cart 設定</button>
                        </div>
                    </form>
                </div>
                
                <!-- Shop Panel -->
                <div id="panel-shop" class="bfm-panel">
                    <div class="bfm-panel-header">
                        <h2 class="bfm-panel-title">📦 商品列表</h2>
                        <span class="bfm-status <?php echo $shop_active ? 'active' : 'inactive'; ?>">
                            <?php echo $shop_active ? '✓ 已啟用' : '✗ 未啟用'; ?>
                        </span>
                    </div>
                    
                    <?php if (!$shop_active): ?>
                    <div class="bfm-notice">請先在「外掛」頁面啟用 BF Shop 外掛</div>
                    <?php endif; ?>
                    
                    <form method="post" action="options.php">
                        <?php settings_fields('bf_shop_options'); ?>
                        
                        <div class="bfm-section-title">顯示設定</div>
                        
                        <div class="bfm-field">
                            <div class="bfm-field-row">
                                <div class="bfm-field-info">
                                    <div class="bfm-field-label">每頁商品數</div>
                                    <div class="bfm-field-desc">商品列表每頁顯示幾個商品</div>
                                </div>
                                <input type="number" class="bfm-input" name="bf_shop_options[products_per_page]" 
                                       value="<?php echo esc_attr($shop_options['products_per_page'] ?? 12); ?>" 
                                       min="4" max="48">
                            </div>
                        </div>
                        
                        <div class="bfm-field">
                            <div class="bfm-field-row">
                                <div class="bfm-field-info">
                                    <div class="bfm-field-label">每行欄數</div>
                                    <div class="bfm-field-desc">商品卡片每行顯示幾欄</div>
                                </div>
                                <select class="bfm-select" name="bf_shop_options[columns]">
                                    <option value="3" <?php selected($shop_options['columns'] ?? 4, 3); ?>>3 欄</option>
                                    <option value="4" <?php selected($shop_options['columns'] ?? 4, 4); ?>>4 欄</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="bfm-field">
                            <div class="bfm-field-row">
                                <div class="bfm-field-info">
                                    <div class="bfm-field-label">預設排序</div>
                                    <div class="bfm-field-desc">商品列表的預設排序方式</div>
                                </div>
                                <select class="bfm-select" name="bf_shop_options[default_orderby]">
                                    <option value="date" <?php selected($shop_options['default_orderby'] ?? 'date', 'date'); ?>>最新上架</option>
                                    <option value="price" <?php selected($shop_options['default_orderby'] ?? 'date', 'price'); ?>>價格低到高</option>
                                    <option value="price-desc" <?php selected($shop_options['default_orderby'] ?? 'date', 'price-desc'); ?>>價格高到低</option>
                                    <option value="popularity" <?php selected($shop_options['default_orderby'] ?? 'date', 'popularity'); ?>>熱銷商品</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="bfm-section-title">功能開關</div>
                        
                        <div class="bfm-field">
                            <div class="bfm-field-row">
                                <div class="bfm-field-info">
                                    <div class="bfm-field-label">顯示分類篩選</div>
                                    <div class="bfm-field-desc">在商品列表上方顯示分類篩選按鈕</div>
                                </div>
                                <label class="bfm-toggle">
                                    <input type="checkbox" name="bf_shop_options[show_filters]" value="1" <?php checked($shop_options['show_filters'] ?? true); ?>>
                                    <span class="bfm-toggle-slider"></span>
                                </label>
                            </div>
                        </div>
                        
                        <div class="bfm-field">
                            <div class="bfm-field-row">
                                <div class="bfm-field-info">
                                    <div class="bfm-field-label">顯示排序選項</div>
                                    <div class="bfm-field-desc">讓顧客可以選擇排序方式</div>
                                </div>
                                <label class="bfm-toggle">
                                    <input type="checkbox" name="bf_shop_options[show_sorting]" value="1" <?php checked($shop_options['show_sorting'] ?? true); ?>>
                                    <span class="bfm-toggle-slider"></span>
                                </label>
                            </div>
                        </div>
                        
                        <div class="bfm-field">
                            <div class="bfm-field-row">
                                <div class="bfm-field-info">
                                    <div class="bfm-field-label">Quick View 快速預覽</div>
                                    <div class="bfm-field-desc">滑過商品時顯示快速預覽按鈕</div>
                                </div>
                                <label class="bfm-toggle">
                                    <input type="checkbox" name="bf_shop_options[show_quick_view]" value="1" <?php checked($shop_options['show_quick_view'] ?? true); ?>>
                                    <span class="bfm-toggle-slider"></span>
                                </label>
                            </div>
                        </div>
                        
                        <div class="bfm-actions">
                            <button type="submit" class="bfm-btn">💾 儲存商品列表設定</button>
                        </div>
                    </form>
                </div>
                
                <!-- Product Panel -->
                <div id="panel-product" class="bfm-panel">
                    <div class="bfm-panel-header">
                        <h2 class="bfm-panel-title">🏷️ 商品頁</h2>
                        <span class="bfm-status <?php echo $product_active ? 'active' : 'inactive'; ?>">
                            <?php echo $product_active ? '✓ 已啟用' : '✗ 未啟用'; ?>
                        </span>
                    </div>
                    
                    <?php if (!$product_active): ?>
                    <div class="bfm-notice">請先在「外掛」頁面啟用 BF Product 外掛</div>
                    <?php endif; ?>
                    
                    <form method="post" action="options.php">
                        <?php settings_fields('bf_product_options'); ?>
                        
                        <div class="bfm-field">
                            <div class="bfm-field-row">
                                <div class="bfm-field-info">
                                    <div class="bfm-field-label">啟用自訂樣式</div>
                                    <div class="bfm-field-desc">套用愛馬仕風格到單一商品頁</div>
                                </div>
                                <label class="bfm-toggle">
                                    <input type="checkbox" name="bf_product_options[enabled]" value="1" <?php checked($product_options['enabled'] ?? true); ?>>
                                    <span class="bfm-toggle-slider"></span>
                                </label>
                            </div>
                        </div>
                        
                        <div class="bfm-field">
                            <div class="bfm-field-row">
                                <div class="bfm-field-info">
                                    <div class="bfm-field-label">顯示商品標籤</div>
                                    <div class="bfm-field-desc">描述、規格、評論等分頁標籤</div>
                                </div>
                                <label class="bfm-toggle">
                                    <input type="checkbox" name="bf_product_options[show_tabs]" value="1" <?php checked($product_options['show_tabs'] ?? true); ?>>
                                    <span class="bfm-toggle-slider"></span>
                                </label>
                            </div>
                        </div>
                        
                        <div class="bfm-field">
                            <div class="bfm-field-row">
                                <div class="bfm-field-info">
                                    <div class="bfm-field-label">顯示評論</div>
                                    <div class="bfm-field-desc">顯示顧客評論區塊</div>
                                </div>
                                <label class="bfm-toggle">
                                    <input type="checkbox" name="bf_product_options[show_reviews]" value="1" <?php checked($product_options['show_reviews'] ?? true); ?>>
                                    <span class="bfm-toggle-slider"></span>
                                </label>
                            </div>
                        </div>
                        
                        <div class="bfm-field">
                            <div class="bfm-field-row">
                                <div class="bfm-field-info">
                                    <div class="bfm-field-label">顯示相關商品</div>
                                    <div class="bfm-field-desc">在商品頁下方顯示相關商品推薦</div>
                                </div>
                                <label class="bfm-toggle">
                                    <input type="checkbox" name="bf_product_options[show_related]" value="1" <?php checked($product_options['show_related'] ?? true); ?>>
                                    <span class="bfm-toggle-slider"></span>
                                </label>
                            </div>
                        </div>
                        
                        <div class="bfm-field">
                            <div class="bfm-field-row">
                                <div class="bfm-field-info">
                                    <div class="bfm-field-label">相關商品數量</div>
                                    <div class="bfm-field-desc">顯示幾個相關商品</div>
                                </div>
                                <input type="number" class="bfm-input" name="bf_product_options[related_count]" 
                                       value="<?php echo esc_attr($product_options['related_count'] ?? 4); ?>" 
                                       min="2" max="8">
                            </div>
                        </div>
                        
                        <div class="bfm-actions">
                            <button type="submit" class="bfm-btn">💾 儲存商品頁設定</button>
                        </div>
                    </form>
                </div>
                
                <!-- Cart Panel -->
                <div id="panel-cart" class="bfm-panel">
                    <div class="bfm-panel-header">
                        <h2 class="bfm-panel-title">🛍️ 購物車頁</h2>
                        <span class="bfm-status <?php echo $cart_active ? 'active' : 'inactive'; ?>">
                            <?php echo $cart_active ? '✓ 已啟用' : '✗ 未啟用'; ?>
                        </span>
                    </div>
                    
                    <?php if (!$cart_active): ?>
                    <div class="bfm-notice">請先在「外掛」頁面啟用 BF Cart 外掛</div>
                    <?php endif; ?>
                    
                    <form method="post" action="options.php">
                        <?php settings_fields('bf_cart_options'); ?>
                        
                        <div class="bfm-field">
                            <div class="bfm-field-row">
                                <div class="bfm-field-info">
                                    <div class="bfm-field-label">啟用自訂樣式</div>
                                    <div class="bfm-field-desc">套用愛馬仕風格到購物車頁</div>
                                </div>
                                <label class="bfm-toggle">
                                    <input type="checkbox" name="bf_cart_options[enabled]" value="1" <?php checked($cart_options['enabled'] ?? true); ?>>
                                    <span class="bfm-toggle-slider"></span>
                                </label>
                            </div>
                        </div>
                        
                        <div class="bfm-field">
                            <div class="bfm-field-row">
                                <div class="bfm-field-info">
                                    <div class="bfm-field-label">免運門檻</div>
                                    <div class="bfm-field-desc">滿額免運的金額（NT$）</div>
                                </div>
                                <input type="number" class="bfm-input" name="bf_cart_options[free_shipping_min]" 
                                       value="<?php echo esc_attr($cart_options['free_shipping_min'] ?? 3000); ?>" 
                                       min="0" step="100">
                            </div>
                        </div>
                        
                        <div class="bfm-field">
                            <div class="bfm-field-row">
                                <div class="bfm-field-info">
                                    <div class="bfm-field-label">繼續購物按鈕</div>
                                    <div class="bfm-field-desc">顯示「繼續購物」按鈕</div>
                                </div>
                                <label class="bfm-toggle">
                                    <input type="checkbox" name="bf_cart_options[show_continue_shopping]" value="1" <?php checked($cart_options['show_continue_shopping'] ?? true); ?>>
                                    <span class="bfm-toggle-slider"></span>
                                </label>
                            </div>
                        </div>
                        
                        <div class="bfm-actions">
                            <button type="submit" class="bfm-btn">💾 儲存購物車設定</button>
                        </div>
                    </form>
                </div>
                
                <!-- Checkout Panel -->
                <div id="panel-checkout" class="bfm-panel">
                    <div class="bfm-panel-header">
                        <h2 class="bfm-panel-title">💳 結帳頁</h2>
                        <span class="bfm-status <?php echo $checkout_active ? 'active' : 'inactive'; ?>">
                            <?php echo $checkout_active ? '✓ 已啟用' : '✗ 未啟用'; ?>
                        </span>
                    </div>
                    
                    <?php if (!$checkout_active): ?>
                    <div class="bfm-notice">請先在「外掛」頁面啟用 BF Checkout 外掛</div>
                    <?php endif; ?>
                    
                    <form method="post" action="options.php">
                        <?php settings_fields('bf_checkout_options'); ?>
                        
                        <div class="bfm-field">
                            <div class="bfm-field-row">
                                <div class="bfm-field-info">
                                    <div class="bfm-field-label">啟用自訂樣式</div>
                                    <div class="bfm-field-desc">套用愛馬仕風格到結帳頁（純 CSS）</div>
                                </div>
                                <label class="bfm-toggle">
                                    <input type="checkbox" name="bf_checkout_options[enabled]" value="1" <?php checked($checkout_options['enabled'] ?? true); ?>>
                                    <span class="bfm-toggle-slider"></span>
                                </label>
                            </div>
                        </div>
                        
                        <div class="bfm-actions">
                            <button type="submit" class="bfm-btn">💾 儲存結帳頁設定</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var tabs = document.querySelectorAll('.bfm-tab');
            var panels = document.querySelectorAll('.bfm-panel');
            
            tabs.forEach(function(tab) {
                tab.addEventListener('click', function() {
                    var target = this.getAttribute('data-tab');
                    
                    tabs.forEach(function(t) { t.classList.remove('active'); });
                    panels.forEach(function(p) { p.classList.remove('active'); });
                    
                    this.classList.add('active');
                    document.getElementById('panel-' + target).classList.add('active');
                });
            });
        });
        </script>
        <?php
    }
}

// Initialize
BF_Shop_Master::get_instance();
