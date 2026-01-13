<?php
/**
 * Plugin Name: BF Cart - 飛熊購物車頁
 * Plugin URI: https://a1.haotaimaker.com/
 * Description: 頂配高奢愛馬仕風格購物車頁面，AJAX 即時更新、優惠券、免運進度
 * Version: 1.0.0
 * Author: Bear's Fantasyland
 * Text Domain: bf-cart
 * Requires Plugins: woocommerce
 */

if (!defined('ABSPATH')) exit;

class BF_Cart {

    private static $instance = null;
    private $option_name = 'bf_cart_options';

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        
        // 覆蓋購物車頁樣式
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
        
        // AJAX handlers
        add_action('wp_ajax_bf_cart_update', array($this, 'ajax_update_cart'));
        add_action('wp_ajax_nopriv_bf_cart_update', array($this, 'ajax_update_cart'));
        add_action('wp_ajax_bf_cart_remove', array($this, 'ajax_remove_item'));
        add_action('wp_ajax_nopriv_bf_cart_remove', array($this, 'ajax_remove_item'));
    }

    public function get_defaults() {
        return array(
            'enabled' => true,
            'free_shipping_min' => 3000,
            'show_continue_shopping' => true,
        );
    }

    public function get_options() {
        $options = get_option($this->option_name);
        return empty($options) ? $this->get_defaults() : wp_parse_args($options, $this->get_defaults());
    }

    public function add_admin_menu() {
        add_submenu_page(
            'woocommerce',
            'BF 購物車設定',
            '🛒 BF 購物車',
            'manage_options',
            'bf-cart-settings',
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
            .bf-cart-admin{max-width:700px;margin:20px auto;font-family:-apple-system,sans-serif}
            .bf-cart-admin h1{color:#8A6754;margin-bottom:30px}
            .bf-cart-admin .card{background:#fff;padding:24px;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,0.08);margin-bottom:20px}
            .bf-cart-admin label{display:block;margin-bottom:16px}
            .bf-cart-admin label span{display:block;font-weight:600;margin-bottom:6px;color:#333}
            .bf-cart-admin input[type="number"]{padding:10px 14px;border:1px solid #ddd;border-radius:6px;width:120px}
            .bf-cart-admin .checkbox-row{display:flex;align-items:center;gap:10px;padding:12px 16px;background:#f9f7f5;border-radius:8px;margin-bottom:10px}
            .bf-cart-admin .checkbox-row input{width:18px;height:18px;accent-color:#8A6754}
            .bf-cart-admin .submit-btn{background:linear-gradient(135deg,#8A6754,#6B4F3F);color:#fff;border:none;padding:14px 40px;font-size:16px;border-radius:8px;cursor:pointer}
        </style>
        <div class="bf-cart-admin">
            <h1>🛒 BF 購物車設定</h1>
            <form method="post" action="options.php">
                <?php settings_fields($this->option_name); ?>
                <div class="card">
                    <div class="checkbox-row">
                        <input type="checkbox" id="enabled" name="<?php echo $this->option_name; ?>[enabled]" value="1" <?php checked($o['enabled']); ?>>
                        <label for="enabled" style="margin:0">啟用自訂購物車頁樣式</label>
                    </div>
                    <div class="checkbox-row">
                        <input type="checkbox" id="show_continue_shopping" name="<?php echo $this->option_name; ?>[show_continue_shopping]" value="1" <?php checked($o['show_continue_shopping']); ?>>
                        <label for="show_continue_shopping" style="margin:0">顯示「繼續購物」按鈕</label>
                    </div>
                </div>
                <div class="card">
                    <label><span>免運門檻（NT$）</span>
                        <input type="number" name="<?php echo $this->option_name; ?>[free_shipping_min]" value="<?php echo esc_attr($o['free_shipping_min']); ?>" min="0" step="100">
                    </label>
                </div>
                <button type="submit" class="submit-btn">💾 儲存設定</button>
            </form>
        </div>
        <?php
    }

    /**
     * AJAX 更新購物車數量
     */
    public function ajax_update_cart() {
        $cart_item_key = sanitize_text_field($_POST['cart_item_key'] ?? '');
        $quantity = intval($_POST['quantity'] ?? 1);

        if (!$cart_item_key || $quantity < 1) {
            wp_send_json_error();
        }

        WC()->cart->set_quantity($cart_item_key, $quantity, true);

        wp_send_json_success(array(
            'cart_total' => WC()->cart->get_cart_total(),
            'cart_subtotal' => WC()->cart->get_cart_subtotal(),
            'cart_count' => WC()->cart->get_cart_contents_count(),
        ));
    }

    /**
     * AJAX 刪除購物車項目
     */
    public function ajax_remove_item() {
        $cart_item_key = sanitize_text_field($_POST['cart_item_key'] ?? '');

        if (!$cart_item_key) {
            wp_send_json_error();
        }

        WC()->cart->remove_cart_item($cart_item_key);

        wp_send_json_success(array(
            'cart_total' => WC()->cart->get_cart_total(),
            'cart_subtotal' => WC()->cart->get_cart_subtotal(),
            'cart_count' => WC()->cart->get_cart_contents_count(),
            'is_empty' => WC()->cart->is_empty(),
        ));
    }

    /**
     * 載入樣式
     */
    public function enqueue_styles() {
        if (!is_cart()) return;
        
        $o = $this->get_options();
        if (empty($o['enabled'])) return;

        add_action('wp_head', array($this, 'output_styles'));
        add_action('wp_footer', array($this, 'output_scripts'));
    }

    /**
     * 輸出 CSS
     */
    public function output_styles() {
        $o = $this->get_options();
        ?>
<style>
/* ========== BF Cart Styles ========== */
:root {
    --bfc-brown: #8A6754;
    --bfc-brown-light: #A88B7A;
    --bfc-cream: #F9F7F5;
    --bfc-text: #333333;
    --bfc-text-light: #777777;
    --bfc-border: #E8E4E0;
    --bfc-white: #FFFFFF;
    --bfc-success: #4CAF50;
    --bfc-danger: #E74C3C;
}

/* Page Container */
.woocommerce-cart .woocommerce {
    max-width: 1200px;
    margin: 0 auto;
    padding: 60px 20px;
    font-family: 'Noto Sans TC', -apple-system, sans-serif;
}

/* Page Title */
.woocommerce-cart .entry-title,
.woocommerce-cart .page-title {
    font-family: 'Noto Serif TC', serif;
    font-size: 36px;
    font-weight: 600;
    color: var(--bfc-text);
    text-align: center;
    margin-bottom: 50px;
    letter-spacing: 4px;
}

/* Cart Table */
.woocommerce-cart table.shop_table {
    border: none;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0,0,0,0.05);
    background: var(--bfc-white);
}

.woocommerce-cart table.shop_table thead {
    background: var(--bfc-cream);
}

.woocommerce-cart table.shop_table thead th {
    font-size: 13px;
    font-weight: 600;
    color: var(--bfc-text-light);
    text-transform: uppercase;
    letter-spacing: 1px;
    padding: 16px 20px;
    border: none;
}

.woocommerce-cart table.shop_table tbody td {
    padding: 24px 20px;
    border-bottom: 1px solid var(--bfc-border);
    vertical-align: middle;
}

.woocommerce-cart table.shop_table tbody tr:last-child td {
    border-bottom: none;
}

/* Product Thumbnail */
.woocommerce-cart table.shop_table .product-thumbnail {
    width: 100px;
}

.woocommerce-cart table.shop_table .product-thumbnail img {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 12px;
}

/* Product Name */
.woocommerce-cart table.shop_table .product-name a {
    font-size: 16px;
    font-weight: 500;
    color: var(--bfc-text);
    text-decoration: none;
    transition: color 0.3s;
}

.woocommerce-cart table.shop_table .product-name a:hover {
    color: var(--bfc-brown);
}

/* Price */
.woocommerce-cart table.shop_table .product-price,
.woocommerce-cart table.shop_table .product-subtotal {
    font-size: 16px;
    font-weight: 600;
    color: var(--bfc-brown);
}

/* Quantity */
.woocommerce-cart table.shop_table .product-quantity .quantity {
    display: flex;
    align-items: center;
    border: 1px solid var(--bfc-border);
    border-radius: 10px;
    overflow: hidden;
    width: fit-content;
}

.woocommerce-cart table.shop_table .product-quantity .qty {
    width: 50px;
    height: 44px;
    text-align: center;
    border: none;
    font-size: 16px;
    font-weight: 600;
    color: var(--bfc-text);
    -moz-appearance: textfield;
}

.woocommerce-cart table.shop_table .product-quantity .qty::-webkit-outer-spin-button,
.woocommerce-cart table.shop_table .product-quantity .qty::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

/* Remove Button */
.woocommerce-cart table.shop_table .product-remove a {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    background: var(--bfc-cream);
    border-radius: 50%;
    color: var(--bfc-text-light);
    font-size: 20px;
    text-decoration: none;
    transition: all 0.3s;
}

.woocommerce-cart table.shop_table .product-remove a:hover {
    background: var(--bfc-danger);
    color: var(--bfc-white);
}

/* Coupon */
.woocommerce-cart .coupon {
    display: flex;
    gap: 12px;
    align-items: center;
}

.woocommerce-cart .coupon input[type="text"] {
    padding: 14px 18px;
    border: 1px solid var(--bfc-border);
    border-radius: 10px;
    font-size: 15px;
    width: 200px;
}

.woocommerce-cart .coupon input[type="text"]:focus {
    outline: none;
    border-color: var(--bfc-brown);
}

.woocommerce-cart .coupon button,
.woocommerce-cart button[name="update_cart"] {
    padding: 14px 24px;
    background: var(--bfc-cream);
    color: var(--bfc-brown);
    border: 1px solid var(--bfc-brown);
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}

.woocommerce-cart .coupon button:hover,
.woocommerce-cart button[name="update_cart"]:hover {
    background: var(--bfc-brown);
    color: var(--bfc-white);
}

/* Cart Totals */
.woocommerce-cart .cart_totals {
    float: none;
    width: 100%;
    max-width: 450px;
    margin-left: auto;
    margin-top: 40px;
}

.woocommerce-cart .cart_totals h2 {
    font-family: 'Noto Serif TC', serif;
    font-size: 24px;
    font-weight: 600;
    color: var(--bfc-text);
    margin-bottom: 24px;
    letter-spacing: 2px;
}

.woocommerce-cart .cart_totals table {
    border: none;
    background: var(--bfc-cream);
    border-radius: 16px;
    overflow: hidden;
}

.woocommerce-cart .cart_totals table th,
.woocommerce-cart .cart_totals table td {
    padding: 18px 24px;
    border: none;
    border-bottom: 1px solid var(--bfc-border);
}

.woocommerce-cart .cart_totals table tr:last-child th,
.woocommerce-cart .cart_totals table tr:last-child td {
    border-bottom: none;
}

.woocommerce-cart .cart_totals table th {
    font-weight: 500;
    color: var(--bfc-text-light);
}

.woocommerce-cart .cart_totals table td {
    font-weight: 600;
    color: var(--bfc-text);
    text-align: right;
}

.woocommerce-cart .cart_totals .order-total th,
.woocommerce-cart .cart_totals .order-total td {
    font-size: 20px;
    color: var(--bfc-brown);
}

/* Checkout Button */
.woocommerce-cart .wc-proceed-to-checkout {
    padding: 0;
    margin-top: 24px;
}

.woocommerce-cart .wc-proceed-to-checkout a.checkout-button {
    display: block;
    padding: 18px 32px;
    background: linear-gradient(135deg, var(--bfc-brown) 0%, #6B4F3F 100%);
    color: var(--bfc-white);
    text-align: center;
    text-decoration: none;
    font-size: 16px;
    font-weight: 600;
    letter-spacing: 2px;
    border-radius: 12px;
    transition: all 0.3s;
}

.woocommerce-cart .wc-proceed-to-checkout a.checkout-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(138, 103, 84, 0.4);
}

/* Free Shipping Progress */
.bfc-shipping-progress {
    background: linear-gradient(135deg, var(--bfc-cream), #FFF8F0);
    border-radius: 16px;
    padding: 24px;
    margin-bottom: 30px;
    text-align: center;
}

.bfc-shipping-text {
    font-size: 15px;
    color: var(--bfc-text);
    margin-bottom: 12px;
}

.bfc-shipping-text strong {
    color: var(--bfc-brown);
}

.bfc-progress-bar {
    height: 8px;
    background: var(--bfc-border);
    border-radius: 4px;
    overflow: hidden;
}

.bfc-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--bfc-brown), var(--bfc-brown-light));
    border-radius: 4px;
    transition: width 0.5s ease;
}

.bfc-free-shipping {
    color: var(--bfc-success);
    font-weight: 600;
}

/* Empty Cart */
.woocommerce-cart .cart-empty {
    text-align: center;
    padding: 80px 20px;
}

.woocommerce-cart .cart-empty::before {
    content: '🛒';
    font-size: 60px;
    display: block;
    margin-bottom: 20px;
}

.woocommerce-cart .return-to-shop a {
    display: inline-block;
    padding: 16px 40px;
    background: linear-gradient(135deg, var(--bfc-brown), #6B4F3F);
    color: var(--bfc-white);
    text-decoration: none;
    font-weight: 600;
    border-radius: 10px;
    margin-top: 20px;
    transition: all 0.3s;
}

.woocommerce-cart .return-to-shop a:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(138, 103, 84, 0.4);
}

/* Actions Row */
.woocommerce-cart .actions {
    padding: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
    border-top: 1px solid var(--bfc-border);
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .woocommerce-cart .woocommerce {
        padding: 30px 16px;
    }
    
    .woocommerce-cart .entry-title,
    .woocommerce-cart .page-title {
        font-size: 28px;
        margin-bottom: 30px;
    }
    
    .woocommerce-cart table.shop_table,
    .woocommerce-cart table.shop_table thead,
    .woocommerce-cart table.shop_table tbody,
    .woocommerce-cart table.shop_table tr,
    .woocommerce-cart table.shop_table td {
        display: block;
    }
    
    .woocommerce-cart table.shop_table thead {
        display: none;
    }
    
    .woocommerce-cart table.shop_table tbody tr {
        padding: 20px;
        margin-bottom: 16px;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
    
    .woocommerce-cart table.shop_table tbody td {
        padding: 8px 0;
        border: none;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .woocommerce-cart table.shop_table tbody td::before {
        content: attr(data-title);
        font-weight: 600;
        color: var(--bfc-text-light);
        font-size: 13px;
    }
    
    .woocommerce-cart table.shop_table .product-thumbnail {
        width: 100%;
        justify-content: center;
    }
    
    .woocommerce-cart .cart_totals {
        max-width: 100%;
    }
    
    .woocommerce-cart .coupon {
        flex-direction: column;
        width: 100%;
    }
    
    .woocommerce-cart .coupon input[type="text"] {
        width: 100%;
    }
    
    .woocommerce-cart .actions {
        flex-direction: column;
    }
}
</style>
        <?php
    }

    /**
     * 輸出 JavaScript
     */
    public function output_scripts() {
        $o = $this->get_options();
        ?>
<script>
jQuery(document).ready(function($) {
    var ajaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';

    // 添加數量按鈕的函數
    function addQtyButtons() {
        $('.woocommerce-cart .quantity').each(function() {
            var $qty = $(this);
            var $input = $qty.find('.qty');
            
            // 移除舊的按鈕避免重複
            $qty.find('.bfc-qty-btn').remove();
            
            // 添加新按鈕
            $qty.prepend('<button type="button" class="bfc-qty-btn bfc-qty-minus">−</button>');
            $qty.append('<button type="button" class="bfc-qty-btn bfc-qty-plus">+</button>');
        });
    }

    // 頁面載入時添加
    addQtyButtons();

    // WooCommerce 更新購物車後重新添加
    $(document.body).on('updated_cart_totals', function() {
        addQtyButtons();
        console.log('Cart updated, buttons re-added');
    });

    // 數量增減
    $(document).on('click', '.bfc-qty-minus, .bfc-qty-plus', function() {
        var $btn = $(this);
        var $qty = $btn.closest('.quantity');
        var $input = $qty.find('.qty');
        var val = parseInt($input.val()) || 1;
        var min = parseInt($input.attr('min')) || 1;
        var max = parseInt($input.attr('max')) || 999;
        
        if ($btn.hasClass('bfc-qty-minus') && val > min) {
            $input.val(val - 1);
        } else if ($btn.hasClass('bfc-qty-plus') && val < max) {
            $input.val(val + 1);
        }
        
        // 觸發更新
        $input.trigger('change');
    });

    // AJAX 更新數量
    $(document).on('change', '.woocommerce-cart .qty', function() {
        var $input = $(this);
        var $row = $input.closest('tr');
        var cartItemKey = $row.find('.product-remove a').data('cart-item-key') || 
                          $row.find('.remove').attr('data-product_id');
        
        // 觸發原生更新按鈕
        $('button[name="update_cart"]').prop('disabled', false).trigger('click');
    });

    console.log('BF Cart JS Loaded');
});
</script>
<style>
.bfc-qty-btn {
    width: 40px;
    height: 44px;
    background: var(--bfc-cream, #F9F7F5);
    border: none;
    font-size: 18px;
    color: var(--bfc-text, #333);
    cursor: pointer;
    transition: all 0.2s;
}
.bfc-qty-btn:hover {
    background: var(--bfc-brown, #8A6754);
    color: #fff;
}
</style>
        <?php
    }
}

// Initialize
add_action('plugins_loaded', function() {
    if (class_exists('WooCommerce')) {
        BF_Cart::get_instance();
    }
});
