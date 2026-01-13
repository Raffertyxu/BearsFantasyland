<?php
/**
 * BF Shop System - Cart Module
 * 購物車頁面樣式
 */

if (!defined('ABSPATH')) exit;

class BF_Module_Cart {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
    }

    public function enqueue_styles() {
        if (!is_cart()) return;
        add_action('wp_head', array($this, 'output_styles'));
        add_action('wp_footer', array($this, 'output_scripts'));
    }

    public function output_styles() {
        ?>
<style>
:root{--bfc-brown:#8A6754;--bfc-cream:#F9F7F5;--bfc-text:#333;--bfc-text-light:#777;--bfc-border:#E8E4E0;--bfc-white:#FFF;--bfc-danger:#E74C3C}
.woocommerce-cart .woocommerce{max-width:1200px;margin:0 auto;padding:60px 20px;font-family:'Noto Sans TC',-apple-system,sans-serif}
.woocommerce-cart .entry-title,.woocommerce-cart .page-title{font-family:'Noto Serif TC',serif;font-size:36px;font-weight:600;color:var(--bfc-text);text-align:center;margin-bottom:50px;letter-spacing:4px}
.woocommerce-cart table.shop_table{border:none;border-radius:16px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.05);background:var(--bfc-white)}
.woocommerce-cart table.shop_table thead{background:var(--bfc-cream)}
.woocommerce-cart table.shop_table thead th{font-size:13px;font-weight:600;color:var(--bfc-text-light);text-transform:uppercase;letter-spacing:1px;padding:16px 20px;border:none}
.woocommerce-cart table.shop_table tbody td{padding:24px 20px;border-bottom:1px solid var(--bfc-border);vertical-align:middle}
.woocommerce-cart table.shop_table .product-thumbnail img{width:80px;height:80px;object-fit:cover;border-radius:12px}
.woocommerce-cart table.shop_table .product-name a{font-size:16px;font-weight:500;color:var(--bfc-text);text-decoration:none;transition:color 0.3s}
.woocommerce-cart table.shop_table .product-name a:hover{color:var(--bfc-brown)}
.woocommerce-cart table.shop_table .product-price,.woocommerce-cart table.shop_table .product-subtotal{font-size:16px;font-weight:600;color:var(--bfc-brown)}
.woocommerce-cart table.shop_table .product-quantity .quantity{display:flex;align-items:center;border:1px solid var(--bfc-border);border-radius:10px;overflow:hidden;width:fit-content}
.woocommerce-cart table.shop_table .product-quantity .qty{width:50px;height:44px;text-align:center;border:none;font-size:16px;font-weight:600;-moz-appearance:textfield}
.woocommerce-cart table.shop_table .product-quantity .qty::-webkit-outer-spin-button,.woocommerce-cart table.shop_table .product-quantity .qty::-webkit-inner-spin-button{-webkit-appearance:none;margin:0}
.woocommerce-cart table.shop_table .product-remove a{display:flex;align-items:center;justify-content:center;width:36px;height:36px;background:var(--bfc-cream);border-radius:50%;color:var(--bfc-text-light);font-size:20px;text-decoration:none;transition:all 0.3s}
.woocommerce-cart table.shop_table .product-remove a:hover{background:var(--bfc-danger);color:var(--bfc-white)}
.woocommerce-cart .coupon{display:flex;gap:12px;align-items:center}
.woocommerce-cart .coupon input[type="text"]{padding:14px 18px;border:1px solid var(--bfc-border);border-radius:10px;font-size:15px;width:200px}
.woocommerce-cart .coupon input[type="text"]:focus{outline:none;border-color:var(--bfc-brown)}
.woocommerce-cart .coupon button,.woocommerce-cart button[name="update_cart"]{padding:14px 24px;background:var(--bfc-cream);color:var(--bfc-brown);border:1px solid var(--bfc-brown);border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;transition:all 0.3s}
.woocommerce-cart .coupon button:hover,.woocommerce-cart button[name="update_cart"]:hover{background:var(--bfc-brown);color:var(--bfc-white)}
.woocommerce-cart .cart_totals{float:none;width:100%;max-width:450px;margin-left:auto;margin-top:40px}
.woocommerce-cart .cart_totals h2{font-family:'Noto Serif TC',serif;font-size:24px;font-weight:600;color:var(--bfc-text);margin-bottom:24px;letter-spacing:2px}
.woocommerce-cart .cart_totals table{border:none;background:var(--bfc-cream);border-radius:16px;overflow:hidden}
.woocommerce-cart .cart_totals table th,.woocommerce-cart .cart_totals table td{padding:18px 24px;border:none;border-bottom:1px solid var(--bfc-border)}
.woocommerce-cart .cart_totals table tr:last-child th,.woocommerce-cart .cart_totals table tr:last-child td{border-bottom:none}
.woocommerce-cart .cart_totals .order-total th,.woocommerce-cart .cart_totals .order-total td{font-size:20px;color:var(--bfc-brown)}
.woocommerce-cart .wc-proceed-to-checkout{padding:0;margin-top:24px}
.woocommerce-cart .wc-proceed-to-checkout a.checkout-button{display:block;padding:18px 32px;background:linear-gradient(135deg,var(--bfc-brown),#6B4F3F);color:var(--bfc-white);text-align:center;text-decoration:none;font-size:16px;font-weight:600;letter-spacing:2px;border-radius:12px;transition:all 0.3s}
.woocommerce-cart .wc-proceed-to-checkout a.checkout-button:hover{transform:translateY(-2px);box-shadow:0 8px 25px rgba(138,103,84,0.4)}
.woocommerce-cart .actions{padding:20px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:16px;border-top:1px solid var(--bfc-border)}
@media(max-width:768px){.woocommerce-cart .woocommerce{padding:30px 16px}.woocommerce-cart .entry-title{font-size:28px;margin-bottom:30px}.woocommerce-cart table.shop_table,.woocommerce-cart table.shop_table thead,.woocommerce-cart table.shop_table tbody,.woocommerce-cart table.shop_table tr,.woocommerce-cart table.shop_table td{display:block}.woocommerce-cart table.shop_table thead{display:none}.woocommerce-cart table.shop_table tbody tr{padding:20px;margin-bottom:16px;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,0.05)}.woocommerce-cart table.shop_table tbody td{padding:8px 0;border:none;display:flex;justify-content:space-between;align-items:center}.woocommerce-cart .cart_totals{max-width:100%}.woocommerce-cart .coupon{flex-direction:column;width:100%}.woocommerce-cart .coupon input[type="text"]{width:100%}.woocommerce-cart .actions{flex-direction:column}}
</style>
        <?php
    }

    public function output_scripts() {
        ?>
<script>
jQuery(document).ready(function($) {
    function addQtyButtons() {
        $('.woocommerce-cart .quantity').each(function() {
            var $qty = $(this);
            $qty.find('.bfc-qty-btn').remove();
            $qty.prepend('<button type="button" class="bfc-qty-btn bfc-qty-minus">−</button>');
            $qty.append('<button type="button" class="bfc-qty-btn bfc-qty-plus">+</button>');
        });
    }
    addQtyButtons();
    $(document.body).on('updated_cart_totals', function() { addQtyButtons(); });
    $(document).on('click', '.bfc-qty-minus, .bfc-qty-plus', function() {
        var $btn = $(this), $qty = $btn.closest('.quantity'), $input = $qty.find('.qty');
        var val = parseInt($input.val()) || 1;
        var min = parseInt($input.attr('min')) || 1;
        var max = parseInt($input.attr('max')) || 999;
        if ($btn.hasClass('bfc-qty-minus') && val > min) $input.val(val - 1);
        else if ($btn.hasClass('bfc-qty-plus') && val < max) $input.val(val + 1);
        $input.trigger('change');
    });
    $(document).on('change', '.woocommerce-cart .qty', function() {
        $('button[name="update_cart"]').prop('disabled', false).trigger('click');
    });
});
</script>
<style>.bfc-qty-btn{width:40px;height:44px;background:var(--bfc-cream,#F9F7F5);border:none;font-size:18px;color:var(--bfc-text,#333);cursor:pointer;transition:all 0.2s}.bfc-qty-btn:hover{background:var(--bfc-brown,#8A6754);color:#fff}</style>
        <?php
    }
}

// Initialize
BF_Module_Cart::get_instance();
