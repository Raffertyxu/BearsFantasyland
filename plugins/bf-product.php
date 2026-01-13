<?php
/**
 * Plugin Name: BF Product - 飛熊商品頁
 * Plugin URI: https://a1.haotaimaker.com/
 * Description: 愛馬仕風格 WooCommerce 單一商品頁，支援圖片輪播、變體選擇、Fly Cart 連動
 * Version: 1.0.0
 * Author: Bear's Fantasyland
 * Text Domain: bf-product
 * Requires Plugins: woocommerce
 */

if (!defined('ABSPATH')) exit;

class BF_Product {

    private static $instance = null;
    private $option_name = 'bf_product_options';

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        
        // 覆蓋商品頁模板
        add_filter('woocommerce_locate_template', array($this, 'override_template'), 10, 3);
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
    }

    public function get_defaults() {
        return array(
            'enabled' => true,
            'show_related' => true,
            'related_count' => 4,
            'show_tabs' => true,
            'show_reviews' => true,
        );
    }

    public function get_options() {
        $options = get_option($this->option_name);
        return empty($options) ? $this->get_defaults() : wp_parse_args($options, $this->get_defaults());
    }

    public function add_admin_menu() {
        add_submenu_page(
            'woocommerce',
            'BF 商品頁設定',
            '📄 BF 商品頁',
            'manage_options',
            'bf-product-settings',
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
            .bf-product-admin{max-width:700px;margin:20px auto;font-family:-apple-system,sans-serif}
            .bf-product-admin h1{color:#8A6754;margin-bottom:30px}
            .bf-product-admin .card{background:#fff;padding:24px;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,0.08);margin-bottom:20px}
            .bf-product-admin label{display:block;margin-bottom:16px}
            .bf-product-admin label span{display:block;font-weight:600;margin-bottom:6px;color:#333}
            .bf-product-admin input[type="number"]{padding:10px 14px;border:1px solid #ddd;border-radius:6px;width:100px}
            .bf-product-admin .checkbox-row{display:flex;align-items:center;gap:10px;padding:12px 16px;background:#f9f7f5;border-radius:8px;margin-bottom:10px}
            .bf-product-admin .checkbox-row input{width:18px;height:18px;accent-color:#8A6754}
            .bf-product-admin .submit-btn{background:linear-gradient(135deg,#8A6754,#6B4F3F);color:#fff;border:none;padding:14px 40px;font-size:16px;border-radius:8px;cursor:pointer}
        </style>
        <div class="bf-product-admin">
            <h1>📄 BF 商品頁設定</h1>
            <form method="post" action="options.php">
                <?php settings_fields($this->option_name); ?>
                <div class="card">
                    <div class="checkbox-row">
                        <input type="checkbox" id="enabled" name="<?php echo $this->option_name; ?>[enabled]" value="1" <?php checked($o['enabled']); ?>>
                        <label for="enabled" style="margin:0">啟用自訂商品頁樣式</label>
                    </div>
                    <div class="checkbox-row">
                        <input type="checkbox" id="show_tabs" name="<?php echo $this->option_name; ?>[show_tabs]" value="1" <?php checked($o['show_tabs']); ?>>
                        <label for="show_tabs" style="margin:0">顯示商品標籤（描述/規格/評論）</label>
                    </div>
                    <div class="checkbox-row">
                        <input type="checkbox" id="show_reviews" name="<?php echo $this->option_name; ?>[show_reviews]" value="1" <?php checked($o['show_reviews']); ?>>
                        <label for="show_reviews" style="margin:0">顯示顧客評論</label>
                    </div>
                    <div class="checkbox-row">
                        <input type="checkbox" id="show_related" name="<?php echo $this->option_name; ?>[show_related]" value="1" <?php checked($o['show_related']); ?>>
                        <label for="show_related" style="margin:0">顯示相關商品</label>
                    </div>
                </div>
                <div class="card">
                    <label><span>相關商品顯示數量</span>
                        <input type="number" name="<?php echo $this->option_name; ?>[related_count]" value="<?php echo esc_attr($o['related_count']); ?>" min="2" max="8">
                    </label>
                </div>
                <button type="submit" class="submit-btn">💾 儲存設定</button>
            </form>
        </div>
        <?php
    }

    /**
     * 載入自訂 CSS
     */
    public function enqueue_styles() {
        if (!is_product()) return;
        
        $o = $this->get_options();
        if (empty($o['enabled'])) return;

        // 內嵌樣式
        add_action('wp_head', array($this, 'output_styles'));
        add_action('wp_footer', array($this, 'output_scripts'));
    }

    /**
     * 覆蓋模板（暫時用 action hook 方式）
     */
    public function override_template($template, $template_name, $template_path) {
        // 未來可以完全覆蓋模板
        return $template;
    }

    /**
     * 輸出 CSS 樣式
     */
    public function output_styles() {
        $o = $this->get_options();
        if (empty($o['enabled'])) return;
        ?>
<style>
/* ========== BF Product Styles ========== */
:root {
    --bfp-brown: #8A6754;
    --bfp-brown-light: #A88B7A;
    --bfp-cream: #F9F7F5;
    --bfp-text: #333333;
    --bfp-text-light: #777777;
    --bfp-border: #E8E4E0;
    --bfp-white: #FFFFFF;
    --bfp-success: #4CAF50;
}

/* Reset WooCommerce defaults */
.woocommerce div.product {
    font-family: 'Noto Sans TC', -apple-system, sans-serif !important;
}

/* Product Container */
.woocommerce div.product div.images,
.woocommerce div.product div.summary {
    float: none !important;
    width: 100% !important;
}

.woocommerce div.product {
    display: grid !important;
    grid-template-columns: 1fr 1fr !important;
    gap: 60px !important;
    max-width: 1400px !important;
    margin: 0 auto !important;
    padding: 60px 40px !important;
}

@media (max-width: 900px) {
    .woocommerce div.product {
        grid-template-columns: 1fr !important;
        gap: 40px !important;
        padding: 30px 20px !important;
    }
}

/* Gallery Styles */
.woocommerce div.product div.images {
    position: relative !important;
}

.woocommerce div.product div.images .woocommerce-product-gallery__wrapper {
    display: flex !important;
    flex-direction: column !important;
    gap: 16px !important;
}

.woocommerce div.product div.images .woocommerce-product-gallery__image:first-child {
    border-radius: 16px !important;
    overflow: hidden !important;
    background: var(--bfp-cream) !important;
}

.woocommerce div.product div.images .woocommerce-product-gallery__image:first-child img {
    width: 100% !important;
    height: auto !important;
    object-fit: cover !important;
}

.woocommerce div.product div.images .flex-control-thumbs {
    display: flex !important;
    gap: 12px !important;
    margin-top: 16px !important;
    padding: 0 !important;
}

.woocommerce div.product div.images .flex-control-thumbs li {
    width: 80px !important;
    height: 80px !important;
    border-radius: 12px !important;
    overflow: hidden !important;
    cursor: pointer !important;
    border: 2px solid transparent !important;
    transition: all 0.3s !important;
    flex-shrink: 0 !important;
}

.woocommerce div.product div.images .flex-control-thumbs li:hover,
.woocommerce div.product div.images .flex-control-thumbs li.flex-active {
    border-color: var(--bfp-brown) !important;
}

.woocommerce div.product div.images .flex-control-thumbs li img {
    width: 100% !important;
    height: 100% !important;
    object-fit: cover !important;
}

/* Summary Styles */
.woocommerce div.product div.summary {
    padding: 20px 0 !important;
}

/* Product Title */
.woocommerce div.product .product_title {
    font-family: 'Noto Serif TC', serif !important;
    font-size: 32px !important;
    font-weight: 600 !important;
    color: var(--bfp-text) !important;
    margin-bottom: 20px !important;
    letter-spacing: 2px !important;
    line-height: 1.3 !important;
}

/* Price */
.woocommerce div.product p.price,
.woocommerce div.product span.price {
    font-size: 28px !important;
    font-weight: 600 !important;
    color: var(--bfp-brown) !important;
    margin-bottom: 24px !important;
    display: block !important;
}

.woocommerce div.product p.price del,
.woocommerce div.product span.price del {
    color: var(--bfp-text-light) !important;
    font-size: 20px !important;
    font-weight: 400 !important;
    margin-right: 12px !important;
}

.woocommerce div.product p.price ins,
.woocommerce div.product span.price ins {
    text-decoration: none !important;
}

/* Short Description */
.woocommerce div.product .woocommerce-product-details__short-description {
    font-size: 15px !important;
    color: var(--bfp-text-light) !important;
    line-height: 1.8 !important;
    margin-bottom: 30px !important;
    padding-bottom: 30px !important;
    border-bottom: 1px solid var(--bfp-border) !important;
}

/* Meta (SKU, Categories) */
.woocommerce div.product .product_meta {
    font-size: 14px !important;
    color: var(--bfp-text-light) !important;
    margin-bottom: 30px !important;
    padding-bottom: 30px !important;
    border-bottom: 1px solid var(--bfp-border) !important;
}

.woocommerce div.product .product_meta > span {
    display: block !important;
    margin-bottom: 8px !important;
}

.woocommerce div.product .product_meta a {
    color: var(--bfp-brown) !important;
    text-decoration: none !important;
}

.woocommerce div.product .product_meta a:hover {
    text-decoration: underline !important;
}

/* Variations */
.woocommerce div.product form.variations_form {
    margin-bottom: 30px !important;
}

.woocommerce div.product .variations {
    margin-bottom: 20px !important;
}

.woocommerce div.product .variations tr {
    display: flex !important;
    flex-direction: column !important;
    gap: 8px !important;
    margin-bottom: 20px !important;
}

.woocommerce div.product .variations th.label {
    font-weight: 600 !important;
    color: var(--bfp-text) !important;
    font-size: 14px !important;
    text-align: left !important;
    padding: 0 !important;
}

.woocommerce div.product .variations td.value {
    padding: 0 !important;
}

.woocommerce div.product .variations select {
    width: 100% !important;
    padding: 14px 18px !important;
    border: 1px solid var(--bfp-border) !important;
    border-radius: 10px !important;
    font-size: 15px !important;
    color: var(--bfp-text) !important;
    background: var(--bfp-white) !important;
    cursor: pointer !important;
    -webkit-appearance: none !important;
    appearance: none !important;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23777' d='M6 8L1 3h10z'/%3E%3C/svg%3E") !important;
    background-repeat: no-repeat !important;
    background-position: right 16px center !important;
}

.woocommerce div.product .variations select:focus {
    outline: none !important;
    border-color: var(--bfp-brown) !important;
}

/* Quantity & Add to Cart */
.woocommerce div.product form.cart {
    display: flex !important;
    gap: 16px !important;
    align-items: stretch !important;
    margin-bottom: 30px !important;
}

.woocommerce div.product form.cart .quantity {
    display: flex !important;
    align-items: center !important;
    border: 1px solid var(--bfp-border) !important;
    border-radius: 10px !important;
    overflow: hidden !important;
}

.woocommerce div.product form.cart .quantity .qty {
    width: 60px !important;
    height: 54px !important;
    text-align: center !important;
    border: none !important;
    font-size: 16px !important;
    font-weight: 600 !important;
    color: var(--bfp-text) !important;
    -moz-appearance: textfield !important;
}

.woocommerce div.product form.cart .quantity .qty::-webkit-outer-spin-button,
.woocommerce div.product form.cart .quantity .qty::-webkit-inner-spin-button {
    -webkit-appearance: none !important;
    margin: 0 !important;
}

.woocommerce div.product form.cart button.single_add_to_cart_button {
    flex: 1 !important;
    padding: 16px 32px !important;
    background: linear-gradient(135deg, var(--bfp-brown) 0%, #6B4F3F 100%) !important;
    color: var(--bfp-white) !important;
    border: none !important;
    border-radius: 10px !important;
    font-size: 16px !important;
    font-weight: 600 !important;
    letter-spacing: 2px !important;
    cursor: pointer !important;
    transition: all 0.3s !important;
}

.woocommerce div.product form.cart button.single_add_to_cart_button:hover {
    transform: translateY(-2px) !important;
    box-shadow: 0 8px 25px rgba(138, 103, 84, 0.4) !important;
}

/* Tabs */
.woocommerce div.product .woocommerce-tabs {
    grid-column: 1 / -1 !important;
    margin-top: 40px !important;
    padding-top: 40px !important;
    border-top: 1px solid var(--bfp-border) !important;
}

.woocommerce div.product .woocommerce-tabs ul.tabs {
    display: flex !important;
    gap: 0 !important;
    padding: 0 !important;
    margin: 0 0 30px !important;
    border-bottom: 1px solid var(--bfp-border) !important;
    list-style: none !important;
}

.woocommerce div.product .woocommerce-tabs ul.tabs::before,
.woocommerce div.product .woocommerce-tabs ul.tabs::after {
    display: none !important;
}

.woocommerce div.product .woocommerce-tabs ul.tabs li {
    margin: 0 !important;
    padding: 0 !important;
    border: none !important;
    background: none !important;
    border-radius: 0 !important;
}

.woocommerce div.product .woocommerce-tabs ul.tabs li a {
    display: block !important;
    padding: 16px 32px !important;
    font-size: 15px !important;
    font-weight: 500 !important;
    color: var(--bfp-text-light) !important;
    text-decoration: none !important;
    border-bottom: 3px solid transparent !important;
    transition: all 0.3s !important;
}

.woocommerce div.product .woocommerce-tabs ul.tabs li.active a,
.woocommerce div.product .woocommerce-tabs ul.tabs li a:hover {
    color: var(--bfp-brown) !important;
    border-bottom-color: var(--bfp-brown) !important;
}

.woocommerce div.product .woocommerce-tabs .panel {
    padding: 20px 0 !important;
}

.woocommerce div.product .woocommerce-tabs .panel h2 {
    display: none !important;
}

/* Related Products */
.woocommerce div.product .related.products {
    grid-column: 1 / -1 !important;
    margin-top: 60px !important;
    padding-top: 60px !important;
    border-top: 1px solid var(--bfp-border) !important;
}

.woocommerce div.product .related.products > h2 {
    font-family: 'Noto Serif TC', serif !important;
    font-size: 24px !important;
    font-weight: 600 !important;
    color: var(--bfp-text) !important;
    text-align: center !important;
    margin-bottom: 40px !important;
    letter-spacing: 4px !important;
}

.woocommerce div.product .related.products ul.products {
    display: grid !important;
    grid-template-columns: repeat(4, 1fr) !important;
    gap: 30px !important;
}

@media (max-width: 1024px) {
    .woocommerce div.product .related.products ul.products {
        grid-template-columns: repeat(3, 1fr) !important;
    }
}

@media (max-width: 768px) {
    .woocommerce div.product .related.products ul.products {
        grid-template-columns: repeat(2, 1fr) !important;
    }
}

.woocommerce div.product .related.products ul.products li.product {
    margin: 0 !important;
    padding: 0 !important;
    background: var(--bfp-white) !important;
    border-radius: 12px !important;
    overflow: hidden !important;
    transition: all 0.4s !important;
}

.woocommerce div.product .related.products ul.products li.product:hover {
    transform: translateY(-8px) !important;
    box-shadow: 0 20px 50px rgba(0,0,0,0.1) !important;
}

.woocommerce div.product .related.products ul.products li.product a img {
    border-radius: 0 !important;
    margin: 0 !important;
}

.woocommerce div.product .related.products ul.products li.product .woocommerce-loop-product__title {
    font-size: 14px !important;
    font-weight: 500 !important;
    color: var(--bfp-text) !important;
    padding: 16px !important;
    margin: 0 !important;
}

.woocommerce div.product .related.products ul.products li.product .price {
    padding: 0 16px 16px !important;
    font-size: 15px !important;
}

.woocommerce div.product .related.products ul.products li.product .button {
    display: none !important;
}

/* Stock Status */
.woocommerce div.product .stock {
    font-size: 14px !important;
    margin-bottom: 20px !important;
}

.woocommerce div.product .stock.in-stock {
    color: var(--bfp-success) !important;
}

.woocommerce div.product .stock.out-of-stock {
    color: #e74c3c !important;
}

/* Breadcrumb */
.woocommerce .woocommerce-breadcrumb {
    padding: 20px 40px !important;
    font-size: 13px !important;
    color: var(--bfp-text-light) !important;
    max-width: 1400px !important;
    margin: 0 auto !important;
}

.woocommerce .woocommerce-breadcrumb a {
    color: var(--bfp-text-light) !important;
    text-decoration: none !important;
}

.woocommerce .woocommerce-breadcrumb a:hover {
    color: var(--bfp-brown) !important;
}

/* Lightbox / Zoom */
.woocommerce div.product div.images .woocommerce-product-gallery__trigger {
    position: absolute !important;
    top: 16px !important;
    right: 16px !important;
    width: 44px !important;
    height: 44px !important;
    background: rgba(255,255,255,0.9) !important;
    border-radius: 50% !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    font-size: 0 !important;
    z-index: 10 !important;
    transition: all 0.3s !important;
}

.woocommerce div.product div.images .woocommerce-product-gallery__trigger::before {
    content: '🔍' !important;
    font-size: 18px !important;
}

.woocommerce div.product div.images .woocommerce-product-gallery__trigger:hover {
    background: var(--bfp-brown) !important;
    transform: scale(1.1) !important;
}

/* Sale Badge */
.woocommerce div.product .onsale {
    position: absolute !important;
    top: 16px !important;
    left: 16px !important;
    background: var(--bfp-brown) !important;
    color: var(--bfp-white) !important;
    font-size: 12px !important;
    font-weight: 600 !important;
    padding: 6px 14px !important;
    border-radius: 20px !important;
    z-index: 10 !important;
    min-width: auto !important;
    min-height: auto !important;
    line-height: 1.4 !important;
}
</style>
        <?php
    }

    /**
     * 輸出 JavaScript
     */
    public function output_scripts() {
        $o = $this->get_options();
        if (empty($o['enabled'])) return;
        ?>
<script>
jQuery(document).ready(function($) {
    // 加入購物車後開啟 Fly Cart
    $('form.cart').on('submit', function(e) {
        // 讓 WooCommerce 正常處理 AJAX
        setTimeout(function() {
            // 觸發 Fly Cart 開啟
            if (typeof bfOpenFlyCart === 'function') {
                bfOpenFlyCart();
            }
        }, 500);
    });

    // 數量按鈕增強
    var $qty = $('form.cart .quantity');
    if ($qty.length && !$qty.find('.bfp-qty-btn').length) {
        var $input = $qty.find('.qty');
        $qty.prepend('<button type="button" class="bfp-qty-btn bfp-qty-minus">−</button>');
        $qty.append('<button type="button" class="bfp-qty-btn bfp-qty-plus">+</button>');

        $qty.on('click', '.bfp-qty-minus', function() {
            var val = parseInt($input.val()) || 1;
            if (val > 1) $input.val(val - 1).trigger('change');
        });

        $qty.on('click', '.bfp-qty-plus', function() {
            var val = parseInt($input.val()) || 1;
            var max = parseInt($input.attr('max')) || 999;
            if (val < max) $input.val(val + 1).trigger('change');
        });
    }

    console.log('BF Product JS Loaded');
});
</script>
<style>
.bfp-qty-btn {
    width: 44px;
    height: 54px;
    background: var(--bfp-cream, #F9F7F5);
    border: none;
    font-size: 20px;
    color: var(--bfp-text, #333);
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
}
.bfp-qty-btn:hover {
    background: var(--bfp-brown, #8A6754);
    color: #fff;
}
</style>
        <?php
    }
}

// Initialize
add_action('plugins_loaded', function() {
    if (class_exists('WooCommerce')) {
        BF_Product::get_instance();
    }
});
