<?php
/**
 * Plugin Name: BF Fly Cart - 飛熊購物車抽屜
 * Plugin URI: https://a1.haotaimaker.com/
 * Description: 愛馬仕風格 AJAX 購物車抽屜，支援免運進度條、優惠券、加購推薦
 * Version: 1.0.1
 * Author: Bear's Fantasyland
 * Text Domain: bf-fly-cart
 * Requires Plugins: woocommerce
 */

if (!defined('ABSPATH')) exit;

class BF_Fly_Cart {

    private static $instance = null;
    private $free_shipping_min = 3000; // 免運門檻

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        // 前端輸出抽屜 HTML
        add_action('wp_footer', array($this, 'render_cart_drawer'));
        
        // AJAX 端點
        add_action('wp_ajax_bf_cart_get', array($this, 'ajax_get_cart'));
        add_action('wp_ajax_nopriv_bf_cart_get', array($this, 'ajax_get_cart'));
        
        add_action('wp_ajax_bf_cart_update_qty', array($this, 'ajax_update_qty'));
        add_action('wp_ajax_nopriv_bf_cart_update_qty', array($this, 'ajax_update_qty'));
        
        add_action('wp_ajax_bf_cart_remove', array($this, 'ajax_remove_item'));
        add_action('wp_ajax_nopriv_bf_cart_remove', array($this, 'ajax_remove_item'));
        
        add_action('wp_ajax_bf_cart_coupon', array($this, 'ajax_apply_coupon'));
        add_action('wp_ajax_nopriv_bf_cart_coupon', array($this, 'ajax_apply_coupon'));

        // 加入購物車後觸發更新
        add_filter('woocommerce_add_to_cart_fragments', array($this, 'cart_fragments'));
    }

    /**
     * 取得購物車資料
     */
    public function ajax_get_cart() {
        wp_send_json_success($this->get_cart_data());
    }

    /**
     * 更新商品數量
     */
    public function ajax_update_qty() {
        $cart_item_key = sanitize_text_field($_POST['cart_item_key'] ?? '');
        $qty = intval($_POST['qty'] ?? 1);
        
        if ($cart_item_key && $qty > 0) {
            WC()->cart->set_quantity($cart_item_key, $qty);
        }
        
        wp_send_json_success($this->get_cart_data());
    }

    /**
     * 移除商品
     */
    public function ajax_remove_item() {
        $cart_item_key = sanitize_text_field($_POST['cart_item_key'] ?? '');
        
        if ($cart_item_key) {
            WC()->cart->remove_cart_item($cart_item_key);
        }
        
        wp_send_json_success($this->get_cart_data());
    }

    /**
     * 套用優惠券
     */
    public function ajax_apply_coupon() {
        $coupon_code = sanitize_text_field($_POST['coupon_code'] ?? '');
        $result = array('success' => false, 'message' => '');
        
        if ($coupon_code) {
            if (WC()->cart->apply_coupon($coupon_code)) {
                $result['success'] = true;
                $result['message'] = '優惠券已套用！';
            } else {
                $result['message'] = '優惠券無效或已過期';
            }
        }
        
        $result['cart'] = $this->get_cart_data();
        wp_send_json($result);
    }

    /**
     * 購物車資料（JSON 格式）
     */
    private function get_cart_data() {
        $cart = WC()->cart;
        $items = array();
        
        foreach ($cart->get_cart() as $key => $item) {
            $product = $item['data'];
            $items[] = array(
                'key' => $key,
                'id' => $item['product_id'],
                'name' => $product->get_name(),
                'price' => $product->get_price(),
                'price_html' => wc_price($product->get_price()),
                'qty' => $item['quantity'],
                'subtotal' => wc_price($item['line_subtotal']),
                'image' => wp_get_attachment_image_url($product->get_image_id(), 'thumbnail') ?: wc_placeholder_img_src('thumbnail'),
                'permalink' => $product->get_permalink(),
            );
        }
        
        $subtotal = $cart->get_subtotal();
        $total = $cart->get_total('edit');
        $discount = $cart->get_discount_total();
        
        return array(
            'items' => $items,
            'count' => $cart->get_cart_contents_count(),
            'subtotal' => wc_price($subtotal),
            'discount' => $discount > 0 ? wc_price($discount) : null,
            'total' => wc_price($total),
            'total_raw' => floatval($total),
            'free_shipping_min' => $this->free_shipping_min,
            'free_shipping_progress' => min(100, ($subtotal / $this->free_shipping_min) * 100),
            'free_shipping_remaining' => max(0, $this->free_shipping_min - $subtotal),
            'coupons' => $cart->get_applied_coupons(),
            'cart_url' => wc_get_cart_url(),
            'checkout_url' => wc_get_checkout_url(),
        );
    }

    /**
     * WooCommerce Fragments（AJAX 更新購物車數量）
     */
    public function cart_fragments($fragments) {
        $count = WC()->cart->get_cart_contents_count();
        $fragments['.bf-cart-count'] = '<span class="bf-cart-count">' . ($count > 0 ? $count : '') . '</span>';
        $fragments['bf_fly_cart_data'] = json_encode($this->get_cart_data());
        return $fragments;
    }

    /**
     * 渲染購物車抽屜 HTML + CSS + JS
     */
    public function render_cart_drawer() {
        if (!function_exists('WC')) return;
        ?>
<style>
/* ========== BF Fly Cart Styles (with !important) ========== */
:root {
    --bfc-brown: #8A6754;
    --bfc-brown-light: #A88B7A;
    --bfc-cream: #F9F7F5;
    --bfc-text: #333333;
    --bfc-text-light: #777777;
    --bfc-border: #E8E4E0;
    --bfc-white: #FFFFFF;
    --bfc-success: #4CAF50;
    --bfc-error: #E74C3C;
}

#bf-fly-cart-overlay {
    position: fixed !important;
    inset: 0 !important;
    background: rgba(0,0,0,0.5) !important;
    z-index: 999998 !important;
    opacity: 0 !important;
    visibility: hidden !important;
    transition: all 0.4s ease !important;
    backdrop-filter: blur(2px) !important;
}

#bf-fly-cart-overlay.active {
    opacity: 1 !important;
    visibility: visible !important;
}

#bf-fly-cart {
    position: fixed !important;
    top: 0 !important;
    right: -450px !important;
    width: 420px !important;
    max-width: 100% !important;
    height: 100vh !important;
    height: 100dvh !important;
    background: var(--bfc-white) !important;
    z-index: 999999 !important;
    transition: right 0.4s cubic-bezier(0.16, 1, 0.3, 1) !important;
    display: flex !important;
    flex-direction: column !important;
    box-shadow: -10px 0 40px rgba(0,0,0,0.15) !important;
    font-family: 'Noto Sans TC', -apple-system, sans-serif !important;
}

#bf-fly-cart.active {
    right: 0 !important;
}

/* Header */
#bf-fly-cart .bfc-header {
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    padding: 20px 24px !important;
    border-bottom: 1px solid var(--bfc-border) !important;
    flex-shrink: 0 !important;
    background: var(--bfc-white) !important;
}

#bf-fly-cart .bfc-title {
    font-family: 'Noto Serif TC', serif !important;
    font-size: 18px !important;
    font-weight: 600 !important;
    color: var(--bfc-text) !important;
    letter-spacing: 2px !important;
    margin: 0 !important;
}

#bf-fly-cart .bfc-count {
    background: var(--bfc-brown) !important;
    color: var(--bfc-white) !important;
    font-size: 12px !important;
    font-weight: 600 !important;
    padding: 2px 10px !important;
    border-radius: 20px !important;
    margin-left: 10px !important;
}

#bf-fly-cart .bfc-close {
    background: none !important;
    border: none !important;
    cursor: pointer !important;
    padding: 8px !important;
    color: var(--bfc-text-light) !important;
    transition: color 0.3s !important;
}

#bf-fly-cart .bfc-close:hover {
    color: var(--bfc-text) !important;
}

#bf-fly-cart .bfc-close svg {
    width: 24px !important;
    height: 24px !important;
}

/* Free Shipping Progress */
#bf-fly-cart .bfc-shipping-progress {
    padding: 16px 24px !important;
    background: var(--bfc-cream) !important;
    border-bottom: 1px solid var(--bfc-border) !important;
    flex-shrink: 0 !important;
}

#bf-fly-cart .bfc-shipping-text {
    font-size: 13px !important;
    color: var(--bfc-text) !important;
    margin-bottom: 10px !important;
    display: flex !important;
    align-items: center !important;
    gap: 8px !important;
}

#bf-fly-cart .bfc-shipping-text .icon {
    font-size: 16px !important;
}

#bf-fly-cart .bfc-shipping-text .highlight {
    color: var(--bfc-brown) !important;
    font-weight: 600 !important;
}

#bf-fly-cart .bfc-progress-bar {
    height: 6px !important;
    background: var(--bfc-border) !important;
    border-radius: 3px !important;
    overflow: hidden !important;
}

#bf-fly-cart .bfc-progress-fill {
    height: 100% !important;
    background: linear-gradient(90deg, var(--bfc-brown) 0%, var(--bfc-brown-light) 100%) !important;
    border-radius: 3px !important;
    transition: width 0.5s ease !important;
}

#bf-fly-cart .bfc-shipping-success {
    color: var(--bfc-success) !important;
    font-weight: 600 !important;
}

/* Cart Items */
#bf-fly-cart .bfc-items {
    flex: 1 !important;
    overflow-y: auto !important;
    padding: 16px 24px !important;
    background: var(--bfc-white) !important;
}

#bf-fly-cart .bfc-item {
    display: flex !important;
    gap: 16px !important;
    padding: 16px 0 !important;
    border-bottom: 1px solid var(--bfc-border) !important;
    animation: bfcSlideIn 0.3s ease !important;
    background: transparent !important;
}

@keyframes bfcSlideIn {
    from { opacity: 0; transform: translateX(20px); }
    to { opacity: 1; transform: translateX(0); }
}

#bf-fly-cart .bfc-item.removing {
    opacity: 0 !important;
    transform: translateX(50px) !important;
    transition: all 0.3s ease !important;
}

#bf-fly-cart .bfc-item-image {
    width: 80px !important;
    height: 80px !important;
    border-radius: 8px !important;
    overflow: hidden !important;
    flex-shrink: 0 !important;
    background: var(--bfc-cream) !important;
}

#bf-fly-cart .bfc-item-image img {
    width: 100% !important;
    height: 100% !important;
    object-fit: cover !important;
}

#bf-fly-cart .bfc-item-info {
    flex: 1 !important;
    display: flex !important;
    flex-direction: column !important;
    justify-content: space-between !important;
}

#bf-fly-cart .bfc-item-name {
    font-size: 14px !important;
    font-weight: 500 !important;
    color: var(--bfc-text) !important;
    text-decoration: none !important;
    display: -webkit-box !important;
    -webkit-line-clamp: 2 !important;
    -webkit-box-orient: vertical !important;
    overflow: hidden !important;
    line-height: 1.4 !important;
}

#bf-fly-cart .bfc-item-name:hover {
    color: var(--bfc-brown) !important;
}

#bf-fly-cart .bfc-item-price {
    font-size: 14px !important;
    color: var(--bfc-brown) !important;
    font-weight: 600 !important;
}

#bf-fly-cart .bfc-item-actions {
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    margin-top: 8px !important;
}

#bf-fly-cart .bfc-qty-control {
    display: flex !important;
    align-items: center !important;
    border: 1px solid var(--bfc-border) !important;
    border-radius: 6px !important;
    overflow: hidden !important;
}

#bf-fly-cart .bfc-qty-btn {
    width: 32px !important;
    height: 32px !important;
    background: var(--bfc-cream) !important;
    border: none !important;
    cursor: pointer !important;
    font-size: 16px !important;
    color: var(--bfc-text) !important;
    transition: all 0.2s !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    line-height: 1 !important;
    padding: 0 !important;
}

#bf-fly-cart .bfc-qty-btn:hover {
    background: var(--bfc-brown) !important;
    color: var(--bfc-white) !important;
}

#bf-fly-cart .bfc-qty-btn:disabled {
    opacity: 0.5 !important;
    cursor: not-allowed !important;
}

#bf-fly-cart .bfc-qty-value {
    width: 40px !important;
    text-align: center !important;
    font-size: 14px !important;
    font-weight: 500 !important;
    background: var(--bfc-white) !important;
    border: none !important;
    color: var(--bfc-text) !important;
    line-height: 32px !important;
}

#bf-fly-cart .bfc-remove-btn {
    background: none !important;
    border: none !important;
    cursor: pointer !important;
    color: var(--bfc-text-light) !important;
    font-size: 12px !important;
    padding: 4px 8px !important;
    transition: color 0.2s !important;
}

#bf-fly-cart .bfc-remove-btn:hover {
    color: var(--bfc-error) !important;
}

/* Empty Cart */
#bf-fly-cart .bfc-empty {
    text-align: center !important;
    padding: 60px 30px !important;
}

#bf-fly-cart .bfc-empty-icon {
    font-size: 64px !important;
    margin-bottom: 20px !important;
    opacity: 0.3 !important;
}

#bf-fly-cart .bfc-empty-title {
    font-family: 'Noto Serif TC', serif !important;
    font-size: 18px !important;
    color: var(--bfc-text) !important;
    margin-bottom: 10px !important;
}

#bf-fly-cart .bfc-empty-text {
    font-size: 14px !important;
    color: var(--bfc-text-light) !important;
    margin-bottom: 24px !important;
}

#bf-fly-cart .bfc-empty-btn {
    display: inline-block !important;
    background: var(--bfc-brown) !important;
    color: var(--bfc-white) !important;
    padding: 12px 32px !important;
    border-radius: 30px !important;
    text-decoration: none !important;
    font-size: 14px !important;
    font-weight: 500 !important;
    transition: all 0.3s !important;
}

#bf-fly-cart .bfc-empty-btn:hover {
    background: #6B4F3F !important;
    transform: translateY(-2px) !important;
    color: var(--bfc-white) !important;
}

/* Coupon */
#bf-fly-cart .bfc-coupon {
    padding: 16px 24px !important;
    border-top: 1px solid var(--bfc-border) !important;
    flex-shrink: 0 !important;
    background: var(--bfc-white) !important;
}

#bf-fly-cart .bfc-coupon-form {
    display: flex !important;
    gap: 10px !important;
}

#bf-fly-cart .bfc-coupon-input {
    flex: 1 !important;
    padding: 12px 16px !important;
    border: 1px solid var(--bfc-border) !important;
    border-radius: 8px !important;
    font-size: 14px !important;
    transition: border-color 0.2s !important;
    background: var(--bfc-white) !important;
    color: var(--bfc-text) !important;
}

#bf-fly-cart .bfc-coupon-input:focus {
    outline: none !important;
    border-color: var(--bfc-brown) !important;
}

#bf-fly-cart .bfc-coupon-btn {
    padding: 12px 20px !important;
    background: var(--bfc-cream) !important;
    border: 1px solid var(--bfc-border) !important;
    border-radius: 8px !important;
    cursor: pointer !important;
    font-size: 14px !important;
    font-weight: 500 !important;
    color: var(--bfc-text) !important;
    transition: all 0.2s !important;
}

#bf-fly-cart .bfc-coupon-btn:hover {
    background: var(--bfc-brown) !important;
    color: var(--bfc-white) !important;
    border-color: var(--bfc-brown) !important;
}

#bf-fly-cart .bfc-coupon-msg {
    font-size: 12px !important;
    margin-top: 8px !important;
    padding: 8px 12px !important;
    border-radius: 6px !important;
}

#bf-fly-cart .bfc-coupon-msg.success {
    background: #E8F5E9 !important;
    color: var(--bfc-success) !important;
}

#bf-fly-cart .bfc-coupon-msg.error {
    background: #FFEBEE !important;
    color: var(--bfc-error) !important;
}

#bf-fly-cart .bfc-applied-coupons {
    display: flex !important;
    flex-wrap: wrap !important;
    gap: 8px !important;
    margin-top: 10px !important;
}

#bf-fly-cart .bfc-coupon-tag {
    background: var(--bfc-cream) !important;
    padding: 4px 12px !important;
    border-radius: 20px !important;
    font-size: 12px !important;
    color: var(--bfc-brown) !important;
    display: flex !important;
    align-items: center !important;
    gap: 6px !important;
}

/* Footer */
#bf-fly-cart .bfc-footer {
    padding: 20px 24px !important;
    border-top: 1px solid var(--bfc-border) !important;
    background: var(--bfc-white) !important;
    flex-shrink: 0 !important;
}

#bf-fly-cart .bfc-totals {
    margin-bottom: 16px !important;
}

#bf-fly-cart .bfc-total-row {
    display: flex !important;
    justify-content: space-between !important;
    margin-bottom: 8px !important;
    font-size: 14px !important;
    color: var(--bfc-text) !important;
}

#bf-fly-cart .bfc-total-row.discount {
    color: var(--bfc-success) !important;
}

#bf-fly-cart .bfc-total-row.final {
    font-size: 18px !important;
    font-weight: 600 !important;
    color: var(--bfc-text) !important;
    padding-top: 12px !important;
    border-top: 1px dashed var(--bfc-border) !important;
    margin-top: 12px !important;
}

#bf-fly-cart .bfc-buttons {
    display: flex !important;
    gap: 12px !important;
}

#bf-fly-cart .bfc-btn {
    flex: 1 !important;
    padding: 14px 20px !important;
    border-radius: 8px !important;
    font-size: 14px !important;
    font-weight: 600 !important;
    text-align: center !important;
    text-decoration: none !important;
    cursor: pointer !important;
    transition: all 0.3s !important;
    border: none !important;
    display: block !important;
    line-height: 1.4 !important;
}

#bf-fly-cart .bfc-btn-cart {
    background: var(--bfc-cream) !important;
    color: var(--bfc-text) !important;
    border: 1px solid var(--bfc-border) !important;
}

#bf-fly-cart .bfc-btn-cart:hover {
    background: var(--bfc-border) !important;
    color: var(--bfc-text) !important;
}

#bf-fly-cart .bfc-btn-checkout {
    background: linear-gradient(135deg, var(--bfc-brown) 0%, #6B4F3F 100%) !important;
    color: var(--bfc-white) !important;
}

#bf-fly-cart .bfc-btn-checkout:hover {
    transform: translateY(-2px) !important;
    box-shadow: 0 4px 15px rgba(138, 103, 84, 0.4) !important;
    color: var(--bfc-white) !important;
}

/* Loading */
#bf-fly-cart .bfc-loading {
    position: absolute !important;
    inset: 0 !important;
    background: rgba(255,255,255,0.8) !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    z-index: 10 !important;
    opacity: 0 !important;
    visibility: hidden !important;
    transition: all 0.2s !important;
}

#bf-fly-cart .bfc-loading.active {
    opacity: 1 !important;
    visibility: visible !important;
}

#bf-fly-cart .bfc-spinner {
    width: 40px !important;
    height: 40px !important;
    border: 3px solid var(--bfc-border) !important;
    border-top-color: var(--bfc-brown) !important;
    border-radius: 50% !important;
    animation: bfcSpin 0.8s linear infinite !important;
}

@keyframes bfcSpin {
    to { transform: rotate(360deg); }
}

/* Mobile */
@media (max-width: 480px) {
    #bf-fly-cart {
        width: 100% !important;
        right: -100% !important;
    }
}
</style>

<!-- Overlay -->
<div id="bf-fly-cart-overlay" onclick="bfFlyCart.close()"></div>

<!-- Cart Drawer -->
<div id="bf-fly-cart">
    <div class="bfc-loading" id="bfc-loading"><div class="bfc-spinner"></div></div>
    
    <!-- Header -->
    <div class="bfc-header">
        <h3 class="bfc-title">購物車 <span class="bfc-count" id="bfc-count">0</span></h3>
        <button class="bfc-close" onclick="bfFlyCart.close()" aria-label="關閉">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M18 6 6 18M6 6l12 12"/></svg>
        </button>
    </div>
    
    <!-- Free Shipping Progress -->
    <div class="bfc-shipping-progress" id="bfc-shipping">
        <div class="bfc-shipping-text" id="bfc-shipping-text"></div>
        <div class="bfc-progress-bar"><div class="bfc-progress-fill" id="bfc-progress-fill"></div></div>
    </div>
    
    <!-- Items Container -->
    <div class="bfc-items" id="bfc-items"></div>
    
    <!-- Coupon -->
    <div class="bfc-coupon" id="bfc-coupon-section">
        <div class="bfc-coupon-form">
            <input type="text" class="bfc-coupon-input" id="bfc-coupon-input" placeholder="輸入優惠碼">
            <button class="bfc-coupon-btn" onclick="bfFlyCart.applyCoupon()">套用</button>
        </div>
        <div id="bfc-coupon-msg"></div>
        <div class="bfc-applied-coupons" id="bfc-applied-coupons"></div>
    </div>
    
    <!-- Footer -->
    <div class="bfc-footer" id="bfc-footer">
        <div class="bfc-totals" id="bfc-totals"></div>
        <div class="bfc-buttons">
            <a href="<?php echo wc_get_cart_url(); ?>" class="bfc-btn bfc-btn-cart">查看購物車</a>
            <a href="<?php echo wc_get_checkout_url(); ?>" class="bfc-btn bfc-btn-checkout">前往結帳</a>
        </div>
    </div>
</div>

<script>
var bfFlyCart = {
    ajaxUrl: '<?php echo admin_url('admin-ajax.php'); ?>',
    isOpen: false,
    data: null,
    
    init: function() {
        this.refresh();
        // Listen for WooCommerce add to cart
        jQuery(document.body).on('added_to_cart', function(e, fragments) {
            if (fragments && fragments.bf_fly_cart_data) {
                bfFlyCart.data = JSON.parse(fragments.bf_fly_cart_data);
                bfFlyCart.render();
            }
            bfFlyCart.open();
        });
    },
    
    open: function() {
        document.getElementById('bf-fly-cart').classList.add('active');
        document.getElementById('bf-fly-cart-overlay').classList.add('active');
        document.body.style.overflow = 'hidden';
        this.isOpen = true;
        this.refresh();
    },
    
    close: function() {
        document.getElementById('bf-fly-cart').classList.remove('active');
        document.getElementById('bf-fly-cart-overlay').classList.remove('active');
        document.body.style.overflow = '';
        this.isOpen = false;
    },
    
    showLoading: function(show) {
        document.getElementById('bfc-loading').classList.toggle('active', show);
    },
    
    refresh: function() {
        this.showLoading(true);
        jQuery.post(this.ajaxUrl, { action: 'bf_cart_get' }, function(res) {
            bfFlyCart.showLoading(false);
            if (res.success) {
                bfFlyCart.data = res.data;
                bfFlyCart.render();
            }
        });
    },
    
    render: function() {
        var d = this.data;
        if (!d) return;
        
        // Count
        document.getElementById('bfc-count').textContent = d.count || 0;
        
        // Update header cart count too
        var headerCount = document.querySelector('.bf-cart-count');
        if (headerCount) headerCount.textContent = d.count || '';
        
        // Shipping Progress
        var shippingEl = document.getElementById('bfc-shipping');
        var textEl = document.getElementById('bfc-shipping-text');
        var fillEl = document.getElementById('bfc-progress-fill');
        
        if (d.free_shipping_remaining > 0) {
            textEl.innerHTML = '<span class="icon">🚚</span> 再買 <span class="highlight">$' + Math.ceil(d.free_shipping_remaining).toLocaleString() + '</span> 即可享免運！';
            fillEl.style.width = d.free_shipping_progress + '%';
            shippingEl.style.display = 'block';
        } else if (d.count > 0) {
            textEl.innerHTML = '<span class="icon">🎉</span> <span class="bfc-shipping-success">恭喜！您已達免運門檻</span>';
            fillEl.style.width = '100%';
            shippingEl.style.display = 'block';
        } else {
            shippingEl.style.display = 'none';
        }
        
        // Items
        var itemsEl = document.getElementById('bfc-items');
        if (d.items.length === 0) {
            itemsEl.innerHTML = '<div class="bfc-empty"><div class="bfc-empty-icon">🛒</div><div class="bfc-empty-title">購物車是空的</div><div class="bfc-empty-text">快去挑選喜歡的商品吧！</div><a href="<?php echo wc_get_page_permalink('shop'); ?>" class="bfc-empty-btn">開始購物</a></div>';
            document.getElementById('bfc-coupon-section').style.display = 'none';
            document.getElementById('bfc-footer').style.display = 'none';
        } else {
            var html = '';
            d.items.forEach(function(item) {
                html += '<div class="bfc-item" data-key="' + item.key + '">' +
                    '<div class="bfc-item-image"><a href="' + item.permalink + '"><img src="' + item.image + '" alt=""></a></div>' +
                    '<div class="bfc-item-info">' +
                        '<a href="' + item.permalink + '" class="bfc-item-name">' + item.name + '</a>' +
                        '<div class="bfc-item-price">' + item.price_html + '</div>' +
                        '<div class="bfc-item-actions">' +
                            '<div class="bfc-qty-control">' +
                                '<button class="bfc-qty-btn" onclick="bfFlyCart.updateQty(\'' + item.key + '\', ' + (item.qty - 1) + ')"' + (item.qty <= 1 ? ' disabled' : '') + '>−</button>' +
                                '<span class="bfc-qty-value">' + item.qty + '</span>' +
                                '<button class="bfc-qty-btn" onclick="bfFlyCart.updateQty(\'' + item.key + '\', ' + (item.qty + 1) + ')">+</button>' +
                            '</div>' +
                            '<button class="bfc-remove-btn" onclick="bfFlyCart.remove(\'' + item.key + '\')">移除</button>' +
                        '</div>' +
                    '</div>' +
                '</div>';
            });
            itemsEl.innerHTML = html;
            document.getElementById('bfc-coupon-section').style.display = 'block';
            document.getElementById('bfc-footer').style.display = 'block';
        }
        
        // Totals
        var totalsHtml = '<div class="bfc-total-row"><span>小計</span><span>' + d.subtotal + '</span></div>';
        if (d.discount) {
            totalsHtml += '<div class="bfc-total-row discount"><span>折扣</span><span>-' + d.discount + '</span></div>';
        }
        totalsHtml += '<div class="bfc-total-row final"><span>總計</span><span>' + d.total + '</span></div>';
        document.getElementById('bfc-totals').innerHTML = totalsHtml;
        
        // Applied Coupons
        var couponsEl = document.getElementById('bfc-applied-coupons');
        if (d.coupons && d.coupons.length > 0) {
            couponsEl.innerHTML = d.coupons.map(function(c) {
                return '<span class="bfc-coupon-tag">🏷️ ' + c + '</span>';
            }).join('');
        } else {
            couponsEl.innerHTML = '';
        }
    },
    
    updateQty: function(key, qty) {
        if (qty < 1) return;
        this.showLoading(true);
        jQuery.post(this.ajaxUrl, { action: 'bf_cart_update_qty', cart_item_key: key, qty: qty }, function(res) {
            bfFlyCart.showLoading(false);
            if (res.success) {
                bfFlyCart.data = res.data;
                bfFlyCart.render();
            }
        });
    },
    
    remove: function(key) {
        var item = document.querySelector('.bfc-item[data-key="' + key + '"]');
        if (item) item.classList.add('removing');
        
        setTimeout(function() {
            bfFlyCart.showLoading(true);
            jQuery.post(bfFlyCart.ajaxUrl, { action: 'bf_cart_remove', cart_item_key: key }, function(res) {
                bfFlyCart.showLoading(false);
                if (res.success) {
                    bfFlyCart.data = res.data;
                    bfFlyCart.render();
                }
            });
        }, 300);
    },
    
    applyCoupon: function() {
        var code = document.getElementById('bfc-coupon-input').value.trim();
        if (!code) return;
        
        this.showLoading(true);
        jQuery.post(this.ajaxUrl, { action: 'bf_cart_coupon', coupon_code: code }, function(res) {
            bfFlyCart.showLoading(false);
            var msgEl = document.getElementById('bfc-coupon-msg');
            msgEl.textContent = res.message;
            msgEl.className = 'bfc-coupon-msg ' + (res.success ? 'success' : 'error');
            
            if (res.success) {
                document.getElementById('bfc-coupon-input').value = '';
                setTimeout(function() { msgEl.textContent = ''; msgEl.className = ''; }, 3000);
            }
            
            if (res.cart) {
                bfFlyCart.data = res.cart;
                bfFlyCart.render();
            }
        });
    }
};

// Initialize
jQuery(function() {
    bfFlyCart.init();
});

// Global function for Header integration
function bfOpenFlyCart() {
    bfFlyCart.open();
}
</script>
        <?php
    }
}

// Initialize
add_action('plugins_loaded', function() {
    if (class_exists('WooCommerce')) {
        BF_Fly_Cart::get_instance();
    }
});
