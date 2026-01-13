<?php
/**
 * BF Shop System - Product Module
 * 單一商品頁樣式
 */

if (!defined('ABSPATH')) exit;

class BF_Module_Product {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
        
        // AJAX 加入購物車
        add_action('wp_ajax_bf_product_add_to_cart', array($this, 'ajax_add_to_cart'));
        add_action('wp_ajax_nopriv_bf_product_add_to_cart', array($this, 'ajax_add_to_cart'));
    }

    public function ajax_add_to_cart() {
        $product_id = intval($_POST['product_id'] ?? 0);
        $quantity = intval($_POST['quantity'] ?? 1);
        $variation_id = intval($_POST['variation_id'] ?? 0);

        if (!$product_id) {
            wp_send_json_error(array('message' => 'Invalid product'));
        }

        if ($variation_id) {
            $cart_item_key = WC()->cart->add_to_cart($product_id, $quantity, $variation_id);
        } else {
            $cart_item_key = WC()->cart->add_to_cart($product_id, $quantity);
        }

        if ($cart_item_key) {
            ob_start();
            woocommerce_mini_cart();
            $mini_cart = ob_get_clean();

            $fragments = apply_filters('woocommerce_add_to_cart_fragments', array(
                'div.widget_shopping_cart_content' => '<div class="widget_shopping_cart_content">' . $mini_cart . '</div>',
            ));

            wp_send_json_success(array(
                'fragments' => $fragments,
                'cart_hash' => WC()->cart->get_cart_hash(),
                'cart_count' => WC()->cart->get_cart_contents_count(),
            ));
        } else {
            wp_send_json_error(array('message' => 'Could not add to cart'));
        }
    }

    public function enqueue_styles() {
        if (!is_product()) return;
        add_action('wp_head', array($this, 'output_styles'));
        add_action('wp_footer', array($this, 'output_scripts'));
    }

    public function output_styles() {
        $options = get_option('bf_shop_system_options', array());
        $show_sticky = isset($options['product_sticky_bar']) ? $options['product_sticky_bar'] : true;
        ?>
<style>
:root{--bfp-brown:#8A6754;--bfp-cream:#F9F7F5;--bfp-text:#333;--bfp-text-light:#777;--bfp-border:#E8E4E0;--bfp-white:#FFF}
.woocommerce div.product{max-width:1200px;margin:0 auto;padding:40px 20px}
.woocommerce div.product div.images,.woocommerce div.product div.summary{max-width:100% !important;overflow:hidden !important}
.woocommerce div.product div.images img{max-width:100% !important;height:auto !important;border-radius:16px}
.woocommerce div.product .product_title{font-family:'Noto Serif TC',serif;font-size:32px;font-weight:600;color:var(--bfp-text);margin-bottom:16px;letter-spacing:2px}
.woocommerce div.product p.price{font-size:28px;font-weight:600;color:var(--bfp-brown);margin-bottom:24px}
.woocommerce div.product .woocommerce-product-details__short-description{font-size:15px;line-height:1.8;color:var(--bfp-text-light);margin-bottom:30px}
.woocommerce div.product form.cart{display:flex;flex-wrap:wrap;gap:16px;align-items:flex-end;margin-bottom:30px;padding:30px;background:var(--bfp-cream);border-radius:16px}
.woocommerce div.product form.cart .quantity{display:flex;align-items:center;border:1px solid var(--bfp-border);border-radius:10px;overflow:hidden}
.woocommerce div.product form.cart .qty{width:60px;height:54px;text-align:center;border:none;font-size:16px;font-weight:600}
.woocommerce div.product form.cart button[type="submit"]{flex:1;min-width:200px;padding:16px 32px;background:linear-gradient(135deg,var(--bfp-brown),#6B4F3F);color:var(--bfp-white);border:none;border-radius:10px;font-size:16px;font-weight:600;letter-spacing:2px;cursor:pointer;transition:all 0.3s}
.woocommerce div.product form.cart button[type="submit"]:hover{transform:translateY(-2px);box-shadow:0 8px 25px rgba(138,103,84,0.4)}
.woocommerce-tabs{margin-top:60px;border:none !important}
.woocommerce-tabs ul.tabs{border:none !important;padding:0 !important;display:flex;gap:10px;margin-bottom:30px}
.woocommerce-tabs ul.tabs li{background:none !important;border:none !important;padding:0 !important;margin:0 !important;border-radius:0 !important}
.woocommerce-tabs ul.tabs li a{padding:14px 28px !important;background:var(--bfp-cream) !important;border-radius:8px !important;font-weight:500;color:var(--bfp-text) !important;transition:all 0.3s}
.woocommerce-tabs ul.tabs li.active a{background:var(--bfp-brown) !important;color:var(--bfp-white) !important}
.woocommerce-tabs .panel{padding:30px;background:var(--bfp-cream);border-radius:16px}
@media(max-width:768px){.woocommerce div.product .product_title{font-size:24px}.woocommerce div.product p.price{font-size:22px}.woocommerce div.product form.cart{padding:20px}.woocommerce div.product form.cart button[type="submit"]{width:100%}}
<?php if ($show_sticky): ?>
.bfp-sticky-bar{position:fixed;bottom:-100px;left:0;right:0;background:var(--bfp-white);box-shadow:0 -4px 20px rgba(0,0,0,0.1);padding:12px 20px;transition:bottom 0.4s;z-index:99998;display:flex;align-items:center;gap:16px}
.bfp-sticky-bar.visible{bottom:0}
.bfp-sticky-bar-img{width:50px;height:50px;border-radius:8px;object-fit:cover}
.bfp-sticky-bar-info{flex:1;min-width:0}
.bfp-sticky-bar-name{font-size:14px;font-weight:600;color:var(--bfp-text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.bfp-sticky-bar-price{font-size:14px;color:var(--bfp-brown);font-weight:600}
.bfp-sticky-bar-actions{display:flex;gap:10px;align-items:center}
.bfp-sticky-bar-qty{display:flex;align-items:center;border:1px solid var(--bfp-border);border-radius:6px;overflow:hidden}
.bfp-sticky-bar-qty button{width:32px;height:36px;background:var(--bfp-cream);border:none;font-size:16px;cursor:pointer}
.bfp-sticky-bar-qty button:hover{background:var(--bfp-brown);color:var(--bfp-white)}
.bfp-sticky-bar-qty span{width:36px;text-align:center;font-weight:600}
.bfp-sticky-bar-btn{padding:10px 24px;background:linear-gradient(135deg,var(--bfp-brown),#6B4F3F);color:var(--bfp-white);border:none;border-radius:8px;font-weight:600;cursor:pointer;transition:all 0.3s}
.bfp-sticky-bar-btn:hover{transform:translateY(-2px);box-shadow:0 4px 15px rgba(138,103,84,0.4)}
@media(max-width:600px){.bfp-sticky-bar-img,.bfp-sticky-bar-info{display:none}.bfp-sticky-bar{justify-content:center}}
<?php endif; ?>
</style>
        <?php
    }

    public function output_scripts() {
        global $product;
        if (!is_product() || !$product) return;
        $options = get_option('bf_shop_system_options', array());
        $show_sticky = isset($options['product_sticky_bar']) ? $options['product_sticky_bar'] : true;
        ?>
<script>
jQuery(document).ready(function($) {
    // AJAX 加入購物車
    $('form.cart').on('submit', function(e) {
        e.preventDefault();
        var $form = $(this);
        var $button = $form.find('button[type="submit"]');
        var originalText = $button.text();
        $button.prop('disabled', true).text('加入中...');
        var productId = $form.find('input[name="product_id"]').val() || $form.find('button[name="add-to-cart"]').val();
        var quantity = $form.find('input[name="quantity"]').val() || 1;
        var variationId = $form.find('input[name="variation_id"]').val() || 0;
        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {action: 'bf_product_add_to_cart', product_id: productId, quantity: quantity, variation_id: variationId},
            success: function(response) {
                $button.prop('disabled', false).text('已加入！');
                setTimeout(function() { $button.text(originalText); }, 1500);
                $(document.body).trigger('added_to_cart', [response.fragments, response.cart_hash]);
                if (typeof bfOpenFlyCart === 'function') { bfOpenFlyCart(); }
            },
            error: function() { $form.off('submit').submit(); }
        });
    });
    // 數量按鈕
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
});
</script>
<style>.bfp-qty-btn{width:44px;height:54px;background:var(--bfp-cream,#F9F7F5);border:none;font-size:20px;color:var(--bfp-text,#333);cursor:pointer;transition:all 0.2s;display:flex;align-items:center;justify-content:center}.bfp-qty-btn:hover{background:var(--bfp-brown,#8A6754);color:#fff}</style>
<?php if ($show_sticky): ?>
<div class="bfp-sticky-bar" id="bfpStickyBar">
    <img src="<?php echo esc_url(wp_get_attachment_image_url($product->get_image_id(), 'thumbnail')); ?>" class="bfp-sticky-bar-img" alt="">
    <div class="bfp-sticky-bar-info">
        <div class="bfp-sticky-bar-name"><?php echo esc_html($product->get_name()); ?></div>
        <div class="bfp-sticky-bar-price"><?php echo $product->get_price_html(); ?></div>
    </div>
    <div class="bfp-sticky-bar-actions">
        <div class="bfp-sticky-bar-qty">
            <button type="button" onclick="bfpStickyQty(-1)">−</button>
            <span id="bfpStickyQtyVal">1</span>
            <button type="button" onclick="bfpStickyQty(1)">+</button>
        </div>
        <button class="bfp-sticky-bar-btn" onclick="bfpStickyAddToCart()">加入購物車</button>
    </div>
</div>
<script>
var bfpStickyQty = 1;
function bfpStickyQty(d) { bfpStickyQty = Math.max(1, bfpStickyQty + d); document.getElementById('bfpStickyQtyVal').textContent = bfpStickyQty; }
function bfpStickyAddToCart() {
    jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', {
        action: 'bf_product_add_to_cart',
        product_id: <?php echo $product->get_id(); ?>,
        quantity: bfpStickyQty
    }, function(r) {
        if (r.success) {
            jQuery(document.body).trigger('added_to_cart', [r.data.fragments, r.data.cart_hash]);
            if (typeof bfOpenFlyCart === 'function') bfOpenFlyCart();
        }
    });
}
(function() {
    var bar = document.getElementById('bfpStickyBar');
    var form = document.querySelector('form.cart');
    if (!form || !bar) return;
    window.addEventListener('scroll', function() {
        var rect = form.getBoundingClientRect();
        bar.classList.toggle('visible', rect.bottom < 0);
    });
})();
</script>
<?php endif; ?>
        <?php
    }
}

// Initialize
BF_Module_Product::get_instance();
