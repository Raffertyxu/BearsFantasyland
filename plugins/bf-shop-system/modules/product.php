<?php
if (!defined('ABSPATH')) exit;

add_action('wp_head', 'bf_product_styles');
add_action('wp_footer', 'bf_product_scripts');
add_action('wp_ajax_bf_product_add_to_cart', 'bf_product_ajax_add');
add_action('wp_ajax_nopriv_bf_product_add_to_cart', 'bf_product_ajax_add');

function bf_product_ajax_add() {
    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity']);
    $variation_id = intval($_POST['variation_id']);
    if (!$product_id) wp_send_json_error();
    if ($variation_id) {
        $cart_key = WC()->cart->add_to_cart($product_id, $quantity, $variation_id);
    } else {
        $cart_key = WC()->cart->add_to_cart($product_id, $quantity);
    }
    if ($cart_key) {
        ob_start();
        woocommerce_mini_cart();
        $mini_cart = ob_get_clean();
        $fragments = apply_filters('woocommerce_add_to_cart_fragments', array(
            'div.widget_shopping_cart_content' => '<div class="widget_shopping_cart_content">' . $mini_cart . '</div>',
        ));
        wp_send_json_success(array('fragments' => $fragments, 'cart_hash' => WC()->cart->get_cart_hash()));
    } else {
        wp_send_json_error();
    }
}

function bf_product_styles() {
    if (!is_product()) return;
    $options = get_option('bf_shop_system_options', array());
    $show_sticky = !empty($options['product_sticky_bar']);
    ?>
<style>
:root{--bfp-brown:#8A6754;--bfp-cream:#F9F7F5;--bfp-text:#333;--bfp-border:#E8E4E0}
.woocommerce div.product{max-width:1200px;margin:0 auto;padding:40px 20px}
.woocommerce div.product div.images,.woocommerce div.product div.summary{max-width:100% !important;overflow:hidden !important}
.woocommerce div.product div.images img{max-width:100% !important;height:auto !important;border-radius:16px}
.woocommerce div.product .product_title{font-size:32px;font-weight:600;color:var(--bfp-text);margin-bottom:16px}
.woocommerce div.product p.price{font-size:28px;font-weight:600;color:var(--bfp-brown);margin-bottom:24px}
.woocommerce div.product form.cart{display:flex;flex-wrap:wrap;gap:16px;align-items:flex-end;margin-bottom:30px;padding:30px;background:var(--bfp-cream);border-radius:16px}
.woocommerce div.product form.cart .quantity{display:flex;align-items:center;border:1px solid var(--bfp-border);border-radius:10px;overflow:hidden}
.woocommerce div.product form.cart .qty{width:60px;height:54px;text-align:center;border:none;font-size:16px;font-weight:600}
.woocommerce div.product form.cart button[type="submit"]{flex:1;min-width:200px;padding:16px 32px;background:linear-gradient(135deg,var(--bfp-brown),#6B4F3F);color:#fff;border:none;border-radius:10px;font-size:16px;font-weight:600;cursor:pointer;transition:all 0.3s}
.woocommerce div.product form.cart button[type="submit"]:hover{transform:translateY(-2px);box-shadow:0 8px 25px rgba(138,103,84,0.4)}
.woocommerce-tabs{margin-top:60px}
.woocommerce-tabs ul.tabs{border:none !important;padding:0 !important;display:flex;gap:10px;margin-bottom:30px}
.woocommerce-tabs ul.tabs li{background:none !important;border:none !important;padding:0 !important;margin:0 !important}
.woocommerce-tabs ul.tabs li a{padding:14px 28px !important;background:var(--bfp-cream) !important;border-radius:8px !important;font-weight:500;color:var(--bfp-text) !important}
.woocommerce-tabs ul.tabs li.active a{background:var(--bfp-brown) !important;color:#fff !important}
.woocommerce-tabs .panel{padding:30px;background:var(--bfp-cream);border-radius:16px}
<?php if ($show_sticky): ?>
.bfp-sticky{position:fixed;bottom:-100px;left:0;right:0;background:#fff;box-shadow:0 -4px 20px rgba(0,0,0,0.1);padding:12px 20px;transition:bottom 0.4s;z-index:99998;display:flex;align-items:center;gap:16px}
.bfp-sticky.visible{bottom:0}
.bfp-sticky-img{width:50px;height:50px;border-radius:8px;object-fit:cover}
.bfp-sticky-info{flex:1}
.bfp-sticky-name{font-size:14px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.bfp-sticky-price{font-size:14px;color:var(--bfp-brown);font-weight:600}
.bfp-sticky-btn{padding:10px 24px;background:var(--bfp-brown);color:#fff;border:none;border-radius:8px;font-weight:600;cursor:pointer}
@media(max-width:600px){.bfp-sticky-img,.bfp-sticky-info{display:none}.bfp-sticky{justify-content:center}}
<?php endif; ?>
@media(max-width:768px){.woocommerce div.product .product_title{font-size:24px}.woocommerce div.product p.price{font-size:22px}.woocommerce div.product form.cart{padding:20px}.woocommerce div.product form.cart button[type="submit"]{width:100%}}
</style>
<?php
}

function bf_product_scripts() {
    if (!is_product()) return;
    global $product;
    if (!$product) return;
    $options = get_option('bf_shop_system_options', array());
    $show_sticky = !empty($options['product_sticky_bar']);
    $ajax_url = admin_url('admin-ajax.php');
    ?>
<script>
jQuery(document).ready(function($){
    $('form.cart').on('submit',function(e){
        e.preventDefault();
        var $f=$(this),$b=$f.find('button[type="submit"]'),t=$b.text();
        $b.prop('disabled',true).text('加入中...');
        var pid=$f.find('input[name="product_id"]').val()||$f.find('button[name="add-to-cart"]').val();
        var qty=$f.find('input[name="quantity"]').val()||1;
        var vid=$f.find('input[name="variation_id"]').val()||0;
        $.post('<?php echo $ajax_url; ?>',{action:'bf_product_add_to_cart',product_id:pid,quantity:qty,variation_id:vid},function(r){
            $b.prop('disabled',false).text('已加入');
            setTimeout(function(){$b.text(t)},1500);
            $(document.body).trigger('added_to_cart',[r.data.fragments,r.data.cart_hash]);
            if(typeof bfOpenFlyCart==='function')bfOpenFlyCart();
        }).fail(function(){$f.off('submit').submit()});
    });
    var $qty=$('form.cart .quantity');
    if($qty.length&&!$qty.find('.bfp-qty-btn').length){
        var $i=$qty.find('.qty');
        $qty.prepend('<button type="button" class="bfp-qty-btn" style="width:44px;height:54px;background:#F9F7F5;border:none;font-size:20px;cursor:pointer">-</button>');
        $qty.append('<button type="button" class="bfp-qty-btn" style="width:44px;height:54px;background:#F9F7F5;border:none;font-size:20px;cursor:pointer">+</button>');
        $qty.on('click','.bfp-qty-btn',function(){
            var v=parseInt($i.val())||1;
            if($(this).text()==='-'&&v>1)$i.val(v-1);
            if($(this).text()==='+')$i.val(v+1);
        });
    }
});
</script>
<?php if ($show_sticky): ?>
<div class="bfp-sticky" id="bfpSticky">
    <img src="<?php echo esc_url(wp_get_attachment_image_url($product->get_image_id(), 'thumbnail')); ?>" class="bfp-sticky-img">
    <div class="bfp-sticky-info">
        <div class="bfp-sticky-name"><?php echo esc_html($product->get_name()); ?></div>
        <div class="bfp-sticky-price"><?php echo $product->get_price_html(); ?></div>
    </div>
    <button class="bfp-sticky-btn" onclick="jQuery('form.cart').submit()">加入購物車</button>
</div>
<script>
(function(){
    var bar=document.getElementById('bfpSticky'),form=document.querySelector('form.cart');
    if(!form||!bar)return;
    window.addEventListener('scroll',function(){
        bar.classList.toggle('visible',form.getBoundingClientRect().bottom<0);
    });
})();
</script>
<?php endif; ?>
<?php
}
