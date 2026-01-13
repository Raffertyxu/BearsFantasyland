<?php
if (!defined('ABSPATH')) exit;

add_action('wp_head', 'bf_checkout_styles');

function bf_checkout_styles() {
    if (!is_checkout()) return;
    ?>
<style>
:root{--bfck-brown:#8A6754;--bfck-cream:#F9F7F5;--bfck-text:#333;--bfck-border:#E8E4E0}
.woocommerce-checkout{background:var(--bfck-cream)}
.woocommerce-checkout .woocommerce{max-width:900px;margin:0 auto;padding:50px 24px}
.woocommerce-checkout .entry-title,.woocommerce-checkout .page-title{font-size:32px;font-weight:600;color:var(--bfck-text);text-align:center;margin-bottom:40px}
.woocommerce-checkout .woocommerce-info{background:#fff;border:none;border-left:4px solid var(--bfck-brown);border-radius:12px;padding:18px 24px;margin-bottom:24px;box-shadow:0 2px 10px rgba(0,0,0,0.04)}
.woocommerce-checkout .woocommerce-info::before{display:none !important}
.woocommerce-checkout form.checkout{background:#fff;border-radius:20px;padding:40px;box-shadow:0 4px 30px rgba(0,0,0,0.06)}
.woocommerce-checkout #customer_details,.woocommerce-checkout #order_review_heading,.woocommerce-checkout #order_review{width:100% !important;float:none !important;clear:both !important}
.woocommerce-checkout h3,.woocommerce-checkout #order_review_heading{font-size:22px;font-weight:600;color:var(--bfck-text);margin:0 0 28px;padding-bottom:14px;border-bottom:2px solid var(--bfck-brown)}
.woocommerce-checkout .col2-set{margin-bottom:30px}
.woocommerce-checkout .col2-set .col-1,.woocommerce-checkout .col2-set .col-2{width:100% !important;float:none !important;margin-bottom:30px}
.woocommerce-checkout .form-row{margin-bottom:20px !important;padding:0 !important}
.woocommerce-checkout label{font-size:14px;font-weight:600;color:var(--bfck-text);margin-bottom:8px;display:block}
.woocommerce-checkout label .required{color:var(--bfck-brown)}
.woocommerce-checkout input[type="text"],.woocommerce-checkout input[type="email"],.woocommerce-checkout input[type="tel"],.woocommerce-checkout input[type="password"],.woocommerce-checkout input[type="number"],.woocommerce-checkout textarea,.woocommerce-checkout select{width:100% !important;padding:14px 18px !important;border:2px solid var(--bfck-border) !important;border-radius:10px !important;font-size:15px !important;color:var(--bfck-text) !important;background:#fff !important;box-sizing:border-box !important}
.woocommerce-checkout input:focus,.woocommerce-checkout textarea:focus,.woocommerce-checkout select:focus{outline:none !important;border-color:var(--bfck-brown) !important;box-shadow:0 0 0 4px rgba(138,103,84,0.12) !important}
.woocommerce-checkout .select2-container{width:100% !important}
.woocommerce-checkout .select2-container--default .select2-selection--single{height:auto !important;padding:12px 40px 12px 16px !important;border:2px solid var(--bfck-border) !important;border-radius:10px !important}
.woocommerce-checkout .select2-container--default .select2-selection--single .select2-selection__arrow{height:100% !important;right:12px !important;top:0 !important}
.woocommerce-checkout input[type="checkbox"],.woocommerce-checkout input[type="radio"]{width:18px !important;height:18px !important;accent-color:var(--bfck-brown);margin-right:10px}
.woocommerce-checkout #order_review{margin-top:40px;padding-top:30px;border-top:1px solid var(--bfck-border)}
.woocommerce-checkout table.shop_table{border:none !important;border-radius:14px !important;overflow:hidden;background:var(--bfck-cream);margin-bottom:24px}
.woocommerce-checkout table.shop_table thead th{background:var(--bfck-cream);font-size:13px;font-weight:600;color:#777;padding:16px 20px !important;border:none !important}
.woocommerce-checkout table.shop_table tbody td,.woocommerce-checkout table.shop_table tfoot td,.woocommerce-checkout table.shop_table tfoot th{padding:16px 20px !important;border:none !important;background:#fff}
.woocommerce-checkout table.shop_table .order-total td,.woocommerce-checkout table.shop_table .order-total th{font-size:18px !important;font-weight:700;color:var(--bfck-brown);background:var(--bfck-cream)}
.woocommerce-checkout #payment{background:var(--bfck-cream);border-radius:14px;padding:28px;margin-top:24px}
.woocommerce-checkout #payment ul.payment_methods{list-style:none;padding:0;margin:0 0 20px;border:none !important;background:none !important}
.woocommerce-checkout #payment ul.payment_methods li{padding:18px !important;margin-bottom:10px;background:#fff;border-radius:10px;border:2px solid var(--bfck-border)}
.woocommerce-checkout #payment ul.payment_methods li:hover{border-color:var(--bfck-brown)}
.woocommerce-checkout #payment div.payment_box{background:var(--bfck-cream) !important;padding:14px 18px !important;margin:10px 0 0 26px !important;border-radius:8px}
.woocommerce-checkout #payment div.payment_box::before{display:none !important}
.woocommerce-checkout #place_order{display:block;width:100%;padding:18px 32px;background:linear-gradient(135deg,var(--bfck-brown),#6B4F3F);color:#fff;border:none;border-radius:12px;font-size:18px;font-weight:600;cursor:pointer;margin-top:16px}
.woocommerce-checkout #place_order:hover{box-shadow:0 8px 25px rgba(138,103,84,0.35)}
.woocommerce-checkout .woocommerce-error{background:#FEE2E2;border-left:4px solid #EF4444;border-radius:10px;padding:16px 20px;color:#991B1B;list-style:none;margin-bottom:24px}
@media(max-width:768px){.woocommerce-checkout .woocommerce{padding:30px 16px}.woocommerce-checkout form.checkout{padding:24px 18px}.woocommerce-checkout #payment{padding:20px}.woocommerce-checkout #place_order{font-size:16px;padding:16px 24px}}
</style>
<?php
}
