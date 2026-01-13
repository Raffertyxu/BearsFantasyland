<?php
if (!defined('ABSPATH')) exit;

add_action('wp_head', 'bf_cart_styles');
add_action('wp_footer', 'bf_cart_scripts');

function bf_cart_styles() {
    if (!is_cart()) return;
    ?>
<style>
:root{--bfc-brown:#8A6754;--bfc-cream:#F9F7F5;--bfc-text:#333;--bfc-border:#E8E4E0}
.woocommerce-cart .woocommerce{max-width:1200px;margin:0 auto;padding:60px 20px}
.woocommerce-cart .entry-title,.woocommerce-cart .page-title{font-size:36px;font-weight:600;color:var(--bfc-text);text-align:center;margin-bottom:50px}
.woocommerce-cart table.shop_table{border:none;border-radius:16px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.05);background:#fff}
.woocommerce-cart table.shop_table thead{background:var(--bfc-cream)}
.woocommerce-cart table.shop_table thead th{font-size:13px;font-weight:600;color:#777;padding:16px 20px;border:none}
.woocommerce-cart table.shop_table tbody td{padding:24px 20px;border-bottom:1px solid var(--bfc-border);vertical-align:middle}
.woocommerce-cart table.shop_table .product-thumbnail img{width:80px;height:80px;object-fit:cover;border-radius:12px}
.woocommerce-cart table.shop_table .product-name a{font-size:16px;font-weight:500;color:var(--bfc-text);text-decoration:none}
.woocommerce-cart table.shop_table .product-price,.woocommerce-cart table.shop_table .product-subtotal{font-size:16px;font-weight:600;color:var(--bfc-brown)}
.woocommerce-cart table.shop_table .product-quantity .quantity{display:flex;align-items:center;border:1px solid var(--bfc-border);border-radius:10px;overflow:hidden;width:fit-content}
.woocommerce-cart table.shop_table .product-quantity .qty{width:50px;height:44px;text-align:center;border:none;font-size:16px;font-weight:600}
.woocommerce-cart table.shop_table .product-remove a{display:flex;align-items:center;justify-content:center;width:36px;height:36px;background:var(--bfc-cream);border-radius:50%;color:#999;font-size:20px;text-decoration:none}
.woocommerce-cart table.shop_table .product-remove a:hover{background:#E74C3C;color:#fff}
.woocommerce-cart .coupon{display:flex;gap:12px}
.woocommerce-cart .coupon input[type="text"]{padding:14px 18px;border:1px solid var(--bfc-border);border-radius:10px;width:200px}
.woocommerce-cart .coupon button,.woocommerce-cart button[name="update_cart"]{padding:14px 24px;background:var(--bfc-cream);color:var(--bfc-brown);border:1px solid var(--bfc-brown);border-radius:10px;font-weight:600;cursor:pointer}
.woocommerce-cart .coupon button:hover,.woocommerce-cart button[name="update_cart"]:hover{background:var(--bfc-brown);color:#fff}
.woocommerce-cart .cart_totals{float:none;width:100%;max-width:450px;margin-left:auto;margin-top:40px}
.woocommerce-cart .cart_totals h2{font-size:24px;font-weight:600;margin-bottom:24px}
.woocommerce-cart .cart_totals table{border:none;background:var(--bfc-cream);border-radius:16px;overflow:hidden}
.woocommerce-cart .cart_totals table th,.woocommerce-cart .cart_totals table td{padding:18px 24px;border:none;border-bottom:1px solid var(--bfc-border)}
.woocommerce-cart .cart_totals table tr:last-child th,.woocommerce-cart .cart_totals table tr:last-child td{border-bottom:none}
.woocommerce-cart .cart_totals .order-total th,.woocommerce-cart .cart_totals .order-total td{font-size:20px;color:var(--bfc-brown)}
.woocommerce-cart .wc-proceed-to-checkout{padding:0;margin-top:24px}
.woocommerce-cart .wc-proceed-to-checkout a.checkout-button{display:block;padding:18px 32px;background:var(--bfc-brown);color:#fff;text-align:center;text-decoration:none;font-size:16px;font-weight:600;border-radius:12px}
.woocommerce-cart .wc-proceed-to-checkout a.checkout-button:hover{box-shadow:0 8px 25px rgba(138,103,84,0.4)}
.woocommerce-cart .actions{padding:20px;display:flex;justify-content:space-between;flex-wrap:wrap;gap:16px;border-top:1px solid var(--bfc-border)}
@media(max-width:768px){.woocommerce-cart .woocommerce{padding:30px 16px}.woocommerce-cart table.shop_table,.woocommerce-cart table.shop_table thead,.woocommerce-cart table.shop_table tbody,.woocommerce-cart table.shop_table tr,.woocommerce-cart table.shop_table td{display:block}.woocommerce-cart table.shop_table thead{display:none}.woocommerce-cart table.shop_table tbody tr{padding:20px;margin-bottom:16px;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,0.05)}.woocommerce-cart table.shop_table tbody td{padding:8px 0;border:none;display:flex;justify-content:space-between}.woocommerce-cart .cart_totals{max-width:100%}.woocommerce-cart .coupon{flex-direction:column}.woocommerce-cart .coupon input[type="text"]{width:100%}}
</style>
<?php
}

function bf_cart_scripts() {
    if (!is_cart()) return;
    ?>
<script>
jQuery(document).ready(function($){
    function addBtns(){
        $('.woocommerce-cart .quantity').each(function(){
            var $q=$(this);
            $q.find('.bfc-qty-btn').remove();
            $q.prepend('<button type="button" class="bfc-qty-btn" style="width:40px;height:44px;background:#F9F7F5;border:none;font-size:18px;cursor:pointer">-</button>');
            $q.append('<button type="button" class="bfc-qty-btn" style="width:40px;height:44px;background:#F9F7F5;border:none;font-size:18px;cursor:pointer">+</button>');
        });
    }
    addBtns();
    $(document.body).on('updated_cart_totals',addBtns);
    $(document).on('click','.bfc-qty-btn',function(){
        var $b=$(this),$q=$b.closest('.quantity'),$i=$q.find('.qty');
        var v=parseInt($i.val())||1,min=parseInt($i.attr('min'))||1,max=parseInt($i.attr('max'))||999;
        if($b.text()==='-'&&v>min)$i.val(v-1);
        else if($b.text()==='+'&&v<max)$i.val(v+1);
        $i.trigger('change');
    });
    $(document).on('change','.woocommerce-cart .qty',function(){
        $('button[name="update_cart"]').prop('disabled',false).trigger('click');
    });
});
</script>
<?php
}
