<?php
if (!defined('ABSPATH')) exit;

add_action('wp_footer', 'bf_fly_cart_render');
add_action('wp_ajax_bf_cart_get', 'bf_fly_cart_ajax_get');
add_action('wp_ajax_nopriv_bf_cart_get', 'bf_fly_cart_ajax_get');
add_action('wp_ajax_bf_cart_update_qty', 'bf_fly_cart_ajax_update');
add_action('wp_ajax_nopriv_bf_cart_update_qty', 'bf_fly_cart_ajax_update');
add_action('wp_ajax_bf_cart_remove', 'bf_fly_cart_ajax_remove');
add_action('wp_ajax_nopriv_bf_cart_remove', 'bf_fly_cart_ajax_remove');
add_action('wp_ajax_bf_cart_coupon', 'bf_fly_cart_ajax_coupon');
add_action('wp_ajax_nopriv_bf_cart_coupon', 'bf_fly_cart_ajax_coupon');
add_filter('woocommerce_add_to_cart_fragments', 'bf_fly_cart_fragments');

function bf_fly_cart_get_data() {
    $options = get_option('bf_shop_system_options', array());
    $free_min = isset($options['fly_cart_free_shipping']) ? intval($options['fly_cart_free_shipping']) : 3000;
    $cart = WC()->cart;
    $items = array();
    foreach ($cart->get_cart() as $key => $item) {
        $product = $item['data'];
        $items[] = array(
            'key' => $key,
            'name' => $product->get_name(),
            'price_html' => wc_price($product->get_price()),
            'qty' => $item['quantity'],
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
        'free_shipping_min' => $free_min,
        'free_shipping_progress' => min(100, ($subtotal / $free_min) * 100),
        'free_shipping_remaining' => max(0, $free_min - $subtotal),
        'coupons' => $cart->get_applied_coupons(),
    );
}

function bf_fly_cart_ajax_get() {
    wp_send_json_success(bf_fly_cart_get_data());
}

function bf_fly_cart_ajax_update() {
    $key = sanitize_text_field($_POST['cart_item_key']);
    $qty = intval($_POST['qty']);
    if ($key && $qty > 0) WC()->cart->set_quantity($key, $qty);
    wp_send_json_success(bf_fly_cart_get_data());
}

function bf_fly_cart_ajax_remove() {
    $key = sanitize_text_field($_POST['cart_item_key']);
    if ($key) WC()->cart->remove_cart_item($key);
    wp_send_json_success(bf_fly_cart_get_data());
}

function bf_fly_cart_ajax_coupon() {
    $code = sanitize_text_field($_POST['coupon_code']);
    $result = array('success' => false, 'message' => '');
    if ($code) {
        if (WC()->cart->apply_coupon($code)) {
            $result['success'] = true;
            $result['message'] = '優惠券已套用';
        } else {
            $result['message'] = '優惠券無效';
        }
    }
    $result['cart'] = bf_fly_cart_get_data();
    wp_send_json($result);
}

function bf_fly_cart_fragments($fragments) {
    $count = WC()->cart->get_cart_contents_count();
    $fragments['.bf-cart-count'] = '<span class="bf-cart-count">' . ($count > 0 ? $count : '') . '</span>';
    $fragments['bf_fly_cart_data'] = json_encode(bf_fly_cart_get_data());
    return $fragments;
}

function bf_fly_cart_render() {
    if (!function_exists('WC')) return;
    $options = get_option('bf_shop_system_options', array());
    $show_coupon = !empty($options['fly_cart_show_coupon']);
    $cart_url = wc_get_cart_url();
    $checkout_url = wc_get_checkout_url();
    $shop_url = wc_get_page_permalink('shop');
    $ajax_url = admin_url('admin-ajax.php');
    ?>
<style>
:root{--bfc-brown:#8A6754;--bfc-cream:#F9F7F5;--bfc-text:#333;--bfc-border:#E8E4E0}
#bf-fly-cart-overlay{position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:999998;opacity:0;visibility:hidden;transition:all 0.4s}
#bf-fly-cart-overlay.active{opacity:1;visibility:visible}
#bf-fly-cart{position:fixed;top:0;right:-450px;width:420px;max-width:100%;height:100vh;background:#fff;z-index:999999;transition:right 0.4s;display:flex;flex-direction:column;box-shadow:-10px 0 40px rgba(0,0,0,0.15)}
#bf-fly-cart.active{right:0}
.bfc-header{display:flex;align-items:center;justify-content:space-between;padding:20px 24px;border-bottom:1px solid var(--bfc-border)}
.bfc-title{font-size:18px;font-weight:600;margin:0}
.bfc-count{background:var(--bfc-brown);color:#fff;font-size:12px;padding:2px 10px;border-radius:20px;margin-left:10px}
.bfc-close{background:none;border:none;cursor:pointer;padding:8px;font-size:24px}
.bfc-shipping{padding:16px 24px;background:var(--bfc-cream);border-bottom:1px solid var(--bfc-border)}
.bfc-shipping-text{font-size:13px;margin-bottom:10px}
.bfc-shipping-text b{color:var(--bfc-brown)}
.bfc-progress{height:6px;background:#ddd;border-radius:3px;overflow:hidden}
.bfc-progress-fill{height:100%;background:var(--bfc-brown);transition:width 0.5s}
.bfc-items{flex:1;overflow-y:auto;padding:16px 24px}
.bfc-item{display:flex;gap:16px;padding:16px 0;border-bottom:1px solid var(--bfc-border)}
.bfc-item-img{width:80px;height:80px;border-radius:8px;object-fit:cover}
.bfc-item-info{flex:1}
.bfc-item-name{font-size:14px;font-weight:500;text-decoration:none;color:var(--bfc-text)}
.bfc-item-price{font-size:14px;color:var(--bfc-brown);font-weight:600;margin-top:4px}
.bfc-item-actions{display:flex;align-items:center;justify-content:space-between;margin-top:8px}
.bfc-qty{display:flex;align-items:center;border:1px solid var(--bfc-border);border-radius:6px}
.bfc-qty button{width:32px;height:32px;background:var(--bfc-cream);border:none;cursor:pointer;font-size:16px}
.bfc-qty span{width:40px;text-align:center;font-weight:500}
.bfc-remove{background:none;border:none;cursor:pointer;color:#999;font-size:12px}
.bfc-empty{text-align:center;padding:60px 30px}
.bfc-empty-icon{font-size:64px;opacity:0.3;margin-bottom:20px}
.bfc-coupon{padding:16px 24px;border-top:1px solid var(--bfc-border)}
.bfc-coupon-form{display:flex;gap:10px}
.bfc-coupon-input{flex:1;padding:12px;border:1px solid var(--bfc-border);border-radius:8px}
.bfc-coupon-btn{padding:12px 20px;background:var(--bfc-cream);border:1px solid var(--bfc-border);border-radius:8px;cursor:pointer}
.bfc-footer{padding:20px 24px;border-top:1px solid var(--bfc-border)}
.bfc-total{display:flex;justify-content:space-between;font-size:18px;font-weight:600;margin-bottom:16px}
.bfc-buttons{display:flex;gap:12px}
.bfc-btn{flex:1;padding:14px;border-radius:8px;text-align:center;text-decoration:none;font-weight:600}
.bfc-btn-cart{background:var(--bfc-cream);color:var(--bfc-text);border:1px solid var(--bfc-border)}
.bfc-btn-checkout{background:var(--bfc-brown);color:#fff;border:none}
@media(max-width:480px){#bf-fly-cart{width:100%;right:-100%}}
</style>
<div id="bf-fly-cart-overlay" onclick="bfFlyCart.close()"></div>
<div id="bf-fly-cart">
    <div class="bfc-header">
        <h3 class="bfc-title">購物車 <span class="bfc-count" id="bfc-count">0</span></h3>
        <button class="bfc-close" onclick="bfFlyCart.close()">&times;</button>
    </div>
    <div class="bfc-shipping" id="bfc-shipping">
        <div class="bfc-shipping-text" id="bfc-shipping-text"></div>
        <div class="bfc-progress"><div class="bfc-progress-fill" id="bfc-progress-fill"></div></div>
    </div>
    <div class="bfc-items" id="bfc-items"></div>
    <?php if ($show_coupon): ?>
    <div class="bfc-coupon" id="bfc-coupon">
        <div class="bfc-coupon-form">
            <input type="text" class="bfc-coupon-input" id="bfc-coupon-input" placeholder="輸入優惠碼">
            <button class="bfc-coupon-btn" onclick="bfFlyCart.applyCoupon()">套用</button>
        </div>
    </div>
    <?php endif; ?>
    <div class="bfc-footer" id="bfc-footer">
        <div class="bfc-total"><span>總計</span><span id="bfc-total"></span></div>
        <div class="bfc-buttons">
            <a href="<?php echo $cart_url; ?>" class="bfc-btn bfc-btn-cart">購物車</a>
            <a href="<?php echo $checkout_url; ?>" class="bfc-btn bfc-btn-checkout">結帳</a>
        </div>
    </div>
</div>
<script>
var bfFlyCart={
    ajaxUrl:'<?php echo $ajax_url; ?>',
    shopUrl:'<?php echo $shop_url; ?>',
    data:null,
    init:function(){
        this.refresh();
        jQuery(document.body).on('added_to_cart',function(e,f){
            if(f&&f.bf_fly_cart_data){bfFlyCart.data=JSON.parse(f.bf_fly_cart_data);bfFlyCart.render()}
            bfFlyCart.open();
        });
    },
    open:function(){
        document.getElementById('bf-fly-cart').classList.add('active');
        document.getElementById('bf-fly-cart-overlay').classList.add('active');
        document.body.style.overflow='hidden';
        this.refresh();
    },
    close:function(){
        document.getElementById('bf-fly-cart').classList.remove('active');
        document.getElementById('bf-fly-cart-overlay').classList.remove('active');
        document.body.style.overflow='';
    },
    refresh:function(){
        jQuery.post(this.ajaxUrl,{action:'bf_cart_get'},function(r){
            if(r.success){bfFlyCart.data=r.data;bfFlyCart.render()}
        });
    },
    render:function(){
        var d=this.data;if(!d)return;
        document.getElementById('bfc-count').textContent=d.count||0;
        var hc=document.querySelector('.bf-cart-count');if(hc)hc.textContent=d.count||'';
        var se=document.getElementById('bfc-shipping'),te=document.getElementById('bfc-shipping-text'),fe=document.getElementById('bfc-progress-fill');
        if(d.free_shipping_remaining>0){
            te.innerHTML='再買 <b>$'+Math.ceil(d.free_shipping_remaining)+'</b> 即可免運';
            fe.style.width=d.free_shipping_progress+'%';se.style.display='block';
        }else if(d.count>0){
            te.innerHTML='<b style="color:#4CAF50">已達免運門檻</b>';fe.style.width='100%';se.style.display='block';
        }else{se.style.display='none'}
        var ie=document.getElementById('bfc-items');
        if(d.items.length===0){
            ie.innerHTML='<div class="bfc-empty"><div class="bfc-empty-icon">🛒</div><div>購物車是空的</div><a href="'+this.shopUrl+'" style="display:inline-block;margin-top:20px;padding:12px 32px;background:var(--bfc-brown);color:#fff;border-radius:30px;text-decoration:none">開始購物</a></div>';
            var cp=document.getElementById('bfc-coupon');if(cp)cp.style.display='none';
            document.getElementById('bfc-footer').style.display='none';
        }else{
            var h='';
            d.items.forEach(function(i){
                h+='<div class="bfc-item"><img src="'+i.image+'" class="bfc-item-img"><div class="bfc-item-info"><a href="'+i.permalink+'" class="bfc-item-name">'+i.name+'</a><div class="bfc-item-price">'+i.price_html+'</div><div class="bfc-item-actions"><div class="bfc-qty"><button onclick="bfFlyCart.updateQty(\''+i.key+'\','+(i.qty-1)+')"'+(i.qty<=1?' disabled':'')+'>-</button><span>'+i.qty+'</span><button onclick="bfFlyCart.updateQty(\''+i.key+'\','+(i.qty+1)+')">+</button></div><button class="bfc-remove" onclick="bfFlyCart.remove(\''+i.key+'\')">移除</button></div></div></div>';
            });
            ie.innerHTML=h;
            var cp=document.getElementById('bfc-coupon');if(cp)cp.style.display='block';
            document.getElementById('bfc-footer').style.display='block';
        }
        document.getElementById('bfc-total').innerHTML=d.total;
    },
    updateQty:function(k,q){if(q<1)return;jQuery.post(this.ajaxUrl,{action:'bf_cart_update_qty',cart_item_key:k,qty:q},function(r){if(r.success){bfFlyCart.data=r.data;bfFlyCart.render()}})},
    remove:function(k){jQuery.post(this.ajaxUrl,{action:'bf_cart_remove',cart_item_key:k},function(r){if(r.success){bfFlyCart.data=r.data;bfFlyCart.render()}})},
    applyCoupon:function(){var c=document.getElementById('bfc-coupon-input').value.trim();if(!c)return;jQuery.post(this.ajaxUrl,{action:'bf_cart_coupon',coupon_code:c},function(r){if(r.cart){bfFlyCart.data=r.cart;bfFlyCart.render()}if(r.success)document.getElementById('bfc-coupon-input').value=''})}
};
jQuery(function(){bfFlyCart.init()});
function bfOpenFlyCart(){bfFlyCart.open()}
</script>
<?php
}
