<?php
/**
 * Plugin Name: BF Fly Cart - 飛熊購物車抽屜
 * Plugin URI: https://a1.haotaimaker.com/
 * Description: 愛馬仕風格 AJAX 購物車抽屜，支援免運進度條、優惠券、加購推薦
 * Version: 1.0.0
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
/* ========== BF Fly Cart Styles ========== */
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
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.5);
    z-index: 999998;
    opacity: 0;
    visibility: hidden;
    transition: all 0.4s ease;
    backdrop-filter: blur(2px);
}

#bf-fly-cart-overlay.active {
    opacity: 1;
    visibility: visible;
}

#bf-fly-cart {
    position: fixed;
    top: 0;
    right: -450px;
    width: 420px;
    max-width: 100%;
    height: 100vh;
    height: 100dvh;
    background: var(--bfc-white);
    z-index: 999999;
    transition: right 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    display: flex;
    flex-direction: column;
    box-shadow: -10px 0 40px rgba(0,0,0,0.15);
    font-family: 'Noto Sans TC', -apple-system, sans-serif;
}

#bf-fly-cart.active {
    right: 0;
}

/* Header */
.bfc-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px 24px;
    border-bottom: 1px solid var(--bfc-border);
    flex-shrink: 0;
}

.bfc-title {
    font-family: 'Noto Serif TC', serif;
    font-size: 18px;
    font-weight: 600;
    color: var(--bfc-text);
    letter-spacing: 2px;
    margin: 0;
}

.bfc-count {
    background: var(--bfc-brown);
    color: var(--bfc-white);
    font-size: 12px;
    font-weight: 600;
    padding: 2px 10px;
    border-radius: 20px;
    margin-left: 10px;
}

.bfc-close {
    background: none;
    border: none;
    cursor: pointer;
    padding: 8px;
    color: var(--bfc-text-light);
    transition: color 0.3s;
}

.bfc-close:hover {
    color: var(--bfc-text);
}

.bfc-close svg {
    width: 24px;
    height: 24px;
}

/* Free Shipping Progress */
.bfc-shipping-progress {
    padding: 16px 24px;
    background: var(--bfc-cream);
    border-bottom: 1px solid var(--bfc-border);
    flex-shrink: 0;
}

.bfc-shipping-text {
    font-size: 13px;
    color: var(--bfc-text);
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.bfc-shipping-text .icon {
    font-size: 16px;
}

.bfc-shipping-text .highlight {
    color: var(--bfc-brown);
    font-weight: 600;
}

.bfc-progress-bar {
    height: 6px;
    background: var(--bfc-border);
    border-radius: 3px;
    overflow: hidden;
}

.bfc-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--bfc-brown) 0%, var(--bfc-brown-light) 100%);
    border-radius: 3px;
    transition: width 0.5s ease;
}

.bfc-shipping-success {
    color: var(--bfc-success);
    font-weight: 600;
}

/* Cart Items */
.bfc-items {
    flex: 1;
    overflow-y: auto;
    padding: 16px 24px;
}

.bfc-item {
    display: flex;
    gap: 16px;
    padding: 16px 0;
    border-bottom: 1px solid var(--bfc-border);
    animation: bfcSlideIn 0.3s ease;
}

@keyframes bfcSlideIn {
    from { opacity: 0; transform: translateX(20px); }
    to { opacity: 1; transform: translateX(0); }
}

.bfc-item.removing {
    opacity: 0;
    transform: translateX(50px);
    transition: all 0.3s ease;
}

.bfc-item-image {
    width: 80px;
    height: 80px;
    border-radius: 8px;
    overflow: hidden;
    flex-shrink: 0;
    background: var(--bfc-cream);
}

.bfc-item-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.bfc-item-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.bfc-item-name {
    font-size: 14px;
    font-weight: 500;
    color: var(--bfc-text);
    text-decoration: none;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    line-height: 1.4;
}

.bfc-item-name:hover {
    color: var(--bfc-brown);
}

.bfc-item-price {
    font-size: 14px;
    color: var(--bfc-brown);
    font-weight: 600;
}

.bfc-item-actions {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 8px;
}

.bfc-qty-control {
    display: flex;
    align-items: center;
    border: 1px solid var(--bfc-border);
    border-radius: 6px;
    overflow: hidden;
}

.bfc-qty-btn {
    width: 32px;
    height: 32px;
    background: var(--bfc-cream);
    border: none;
    cursor: pointer;
    font-size: 16px;
    color: var(--bfc-text);
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
}

.bfc-qty-btn:hover {
    background: var(--bfc-brown);
    color: var(--bfc-white);
}

.bfc-qty-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.bfc-qty-value {
    width: 40px;
    text-align: center;
    font-size: 14px;
    font-weight: 500;
    background: var(--bfc-white);
    border: none;
    color: var(--bfc-text);
}

.bfc-remove-btn {
    background: none;
    border: none;
    cursor: pointer;
    color: var(--bfc-text-light);
    font-size: 12px;
    padding: 4px 8px;
    transition: color 0.2s;
}

.bfc-remove-btn:hover {
    color: var(--bfc-error);
}

/* Empty Cart */
.bfc-empty {
    text-align: center;
    padding: 60px 30px;
}

.bfc-empty-icon {
    font-size: 64px;
    margin-bottom: 20px;
    opacity: 0.3;
}

.bfc-empty-title {
    font-family: 'Noto Serif TC', serif;
    font-size: 18px;
    color: var(--bfc-text);
    margin-bottom: 10px;
}

.bfc-empty-text {
    font-size: 14px;
    color: var(--bfc-text-light);
    margin-bottom: 24px;
}

.bfc-empty-btn {
    display: inline-block;
    background: var(--bfc-brown);
    color: var(--bfc-white);
    padding: 12px 32px;
    border-radius: 30px;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.3s;
}

.bfc-empty-btn:hover {
    background: #6B4F3F;
    transform: translateY(-2px);
}

/* Coupon */
.bfc-coupon {
    padding: 16px 24px;
    border-top: 1px solid var(--bfc-border);
    flex-shrink: 0;
}

.bfc-coupon-form {
    display: flex;
    gap: 10px;
}

.bfc-coupon-input {
    flex: 1;
    padding: 12px 16px;
    border: 1px solid var(--bfc-border);
    border-radius: 8px;
    font-size: 14px;
    transition: border-color 0.2s;
}

.bfc-coupon-input:focus {
    outline: none;
    border-color: var(--bfc-brown);
}

.bfc-coupon-btn {
    padding: 12px 20px;
    background: var(--bfc-cream);
    border: 1px solid var(--bfc-border);
    border-radius: 8px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    color: var(--bfc-text);
    transition: all 0.2s;
}

.bfc-coupon-btn:hover {
    background: var(--bfc-brown);
    color: var(--bfc-white);
    border-color: var(--bfc-brown);
}

.bfc-coupon-msg {
    font-size: 12px;
    margin-top: 8px;
    padding: 8px 12px;
    border-radius: 6px;
}

.bfc-coupon-msg.success {
    background: #E8F5E9;
    color: var(--bfc-success);
}

.bfc-coupon-msg.error {
    background: #FFEBEE;
    color: var(--bfc-error);
}

.bfc-applied-coupons {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 10px;
}

.bfc-coupon-tag {
    background: var(--bfc-cream);
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    color: var(--bfc-brown);
    display: flex;
    align-items: center;
    gap: 6px;
}

/* Footer */
.bfc-footer {
    padding: 20px 24px;
    border-top: 1px solid var(--bfc-border);
    background: var(--bfc-white);
    flex-shrink: 0;
}

.bfc-totals {
    margin-bottom: 16px;
}

.bfc-total-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
    font-size: 14px;
}

.bfc-total-row.discount {
    color: var(--bfc-success);
}

.bfc-total-row.final {
    font-size: 18px;
    font-weight: 600;
    color: var(--bfc-text);
    padding-top: 12px;
    border-top: 1px dashed var(--bfc-border);
    margin-top: 12px;
}

.bfc-buttons {
    display: flex;
    gap: 12px;
}

.bfc-btn {
    flex: 1;
    padding: 14px 20px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    text-align: center;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.3s;
    border: none;
}

.bfc-btn-cart {
    background: var(--bfc-cream);
    color: var(--bfc-text);
    border: 1px solid var(--bfc-border);
}

.bfc-btn-cart:hover {
    background: var(--bfc-border);
}

.bfc-btn-checkout {
    background: linear-gradient(135deg, var(--bfc-brown) 0%, #6B4F3F 100%);
    color: var(--bfc-white);
}

.bfc-btn-checkout:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(138, 103, 84, 0.4);
}

/* Loading */
.bfc-loading {
    position: absolute;
    inset: 0;
    background: rgba(255,255,255,0.8);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10;
    opacity: 0;
    visibility: hidden;
    transition: all 0.2s;
}

.bfc-loading.active {
    opacity: 1;
    visibility: visible;
}

.bfc-spinner {
    width: 40px;
    height: 40px;
    border: 3px solid var(--bfc-border);
    border-top-color: var(--bfc-brown);
    border-radius: 50%;
    animation: bfcSpin 0.8s linear infinite;
}

@keyframes bfcSpin {
    to { transform: rotate(360deg); }
}

/* Mobile */
@media (max-width: 480px) {
    #bf-fly-cart {
        width: 100%;
        right: -100%;
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
