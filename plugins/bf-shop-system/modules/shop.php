<?php
if (!defined('ABSPATH')) exit;

add_action('wp_head', 'bf_shop_styles');

$options = get_option('bf_shop_system_options', array());
if (!empty($options['shop_auto_redirect'])) {
    add_action('template_redirect', 'bf_shop_redirect');
}

function bf_shop_redirect() {
    if (is_front_page() && function_exists('wc_get_page_permalink')) {
        wp_redirect(wc_get_page_permalink('shop'));
        exit;
    }
}

function bf_shop_styles() {
    if (!is_shop() && !is_product_category() && !is_product_tag()) return;
    ?>
<style>
:root{--bfs-brown:#8A6754;--bfs-cream:#F9F7F5;--bfs-text:#333;--bfs-border:#E8E4E0}
.woocommerce .products{display:grid !important;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:30px;margin:0 !important;padding:40px 20px}
.woocommerce ul.products li.product{margin:0 !important;padding:0 !important;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.05);transition:all 0.3s}
.woocommerce ul.products li.product:hover{transform:translateY(-8px);box-shadow:0 12px 40px rgba(0,0,0,0.12)}
.woocommerce ul.products li.product a img{width:100%;height:300px;object-fit:cover;display:block;transition:transform 0.5s}
.woocommerce ul.products li.product:hover a img{transform:scale(1.05)}
.woocommerce ul.products li.product .woocommerce-loop-product__title{font-size:16px;font-weight:500;color:var(--bfs-text);padding:20px 20px 8px;margin:0}
.woocommerce ul.products li.product .price{padding:0 20px 20px;font-size:16px;font-weight:600;color:var(--bfs-brown)}
.woocommerce ul.products li.product .button{display:block;width:calc(100% - 40px);margin:0 20px 20px;padding:14px;background:var(--bfs-cream);color:var(--bfs-text);border:1px solid var(--bfs-border);border-radius:8px;font-size:14px;font-weight:500;text-align:center;text-decoration:none;transition:all 0.3s}
.woocommerce ul.products li.product .button:hover{background:var(--bfs-brown);color:#fff;border-color:var(--bfs-brown)}
.woocommerce ul.products li.product .onsale{background:var(--bfs-brown);color:#fff;font-size:12px;padding:6px 14px;border-radius:20px;top:16px;right:16px;left:auto}
.woocommerce nav.woocommerce-pagination ul{border:none;display:flex;gap:8px;justify-content:center;padding:40px 0}
.woocommerce nav.woocommerce-pagination ul li{border:none}
.woocommerce nav.woocommerce-pagination ul li a,.woocommerce nav.woocommerce-pagination ul li span{padding:12px 18px;background:#fff;border:1px solid var(--bfs-border);border-radius:8px;color:var(--bfs-text)}
.woocommerce nav.woocommerce-pagination ul li a:hover,.woocommerce nav.woocommerce-pagination ul li span.current{background:var(--bfs-brown);border-color:var(--bfs-brown);color:#fff}
@media(max-width:768px){.woocommerce .products{grid-template-columns:repeat(2,1fr);gap:16px;padding:20px 16px}.woocommerce ul.products li.product a img{height:200px}.woocommerce ul.products li.product .woocommerce-loop-product__title{font-size:14px;padding:12px 12px 6px}.woocommerce ul.products li.product .price{padding:0 12px 12px;font-size:14px}.woocommerce ul.products li.product .button{margin:0 12px 12px;width:calc(100% - 24px);padding:10px}}
</style>
<?php
}
