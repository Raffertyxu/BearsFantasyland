<?php
// Local preview for the WooCommerce single product page (preview.php has no WooCommerce).
// Runs the theme's real bfnd_render_product_page() with WordPress/WooCommerce stubs, feeding it
// WooCommerce markup captured from the live product page, and splices the result into the live
// page shell so the live CSS/JS (plus local style.css / product.css) render it.
//
// Usage (from any folder; <dir> holds the captured pages and receives the output):
//   curl -s "https://a1.haotaimaker.com/product/<product-slug>/" -o <dir>/live-product.html
//   curl -s "https://a1.haotaimaker.com/woodshop/" -o <dir>/live-woodshop.html
//   cp NEWDESIGN/theme/bears-fantasyland/public/{style.css,product.css} <dir>/preview/
//   php NEWDESIGN/website/scripts/preview_product_harness.php current|full NEWDESIGN/theme/bears-fantasyland <dir> <dir>/preview/full.html
// then open preview/full.html in Chrome (or Playwright). "current" = live content, "full" = sample
// description/attributes/reviews (sample copy is marked 預覽用示意文字, never publish it).
// Markers "TABS NOT REMOVED" etc. in the output mean the hook removal regressed.
[$self, $scenario, $theme, $scratch, $out] = $argv;
define('ABSPATH', __DIR__ . '/');
$live = file_get_contents("$scratch/live-product.html");
$shop_html = file_get_contents("$scratch/live-woodshop.html");
function grab($html, $start, $end, $include_end = true) {
    $i = strpos($html, $start); if ($i === false) { fwrite(STDERR, "missing $start\n"); return ''; }
    $j = strpos($html, $end, $i); if ($j === false) { fwrite(STDERR, "missing end $end\n"); return ''; }
    return substr($html, $i, $j - $i + ($include_end ? strlen($end) : 0));
}
$P = array(
    'onsale' => grab($live, '<span class="onsale">', '</span>'),
    'gallery' => grab($live, '<div class="woocommerce-product-gallery', '<div class="summary', false),
    'title' => grab($live, '<h1 class="product_title', '</h1>'),
    'price' => grab($live, '<p class="price">', '</p>'),
    'excerpt' => grab($live, '<div class="woocommerce-product-details__short-description">', '</div>'),
    'cart' => grab($live, '<form class="cart"', '</form>'),
    'meta' => grab($live, '<div class="product_meta">', "</div>"),
    'reviews' => grab($live, '<div id="reviews" class="woocommerce-Reviews">', '<div class="clear"></div>') . "\n</div>",
);
preg_match_all('#<li class="[^"]*\bproduct\b[^"]*">.*?</li>#s', $shop_html, $m);
$cards = array();
foreach ($m[0] as $li) { if (preg_match('/post-(\d+)/', $li, $pid)) { $cards[(int) $pid[1]] = $li; } }

// --- hooks ---
$GLOBALS['hooks'] = array();
function add_action($tag, $cb, $prio = 10) { $GLOBALS['hooks'][$tag][$prio][] = $cb; return true; }
function add_filter($tag, $cb, $prio = 10) { return true; }
function remove_action($tag, $cb, $prio = 10) {
    foreach ($GLOBALS['hooks'][$tag][$prio] ?? array() as $k => $c) { if ($c === $cb) { unset($GLOBALS['hooks'][$tag][$prio][$k]); return true; } }
    return false;
}
function do_action($tag) { $h = $GLOBALS['hooks'][$tag] ?? array(); ksort($h); foreach ($h as $cbs) { foreach ($cbs as $cb) { $cb(); } } }
function apply_filters($tag, $value) { return $tag === 'the_content' ? $value : $value; }
// WooCommerce default single product hooks (content-single-product.php)
foreach (array(5 => 'title', 10 => 'price', 20 => 'excerpt', 30 => 'cart', 40 => 'meta') as $prio => $key) {
    add_action('woocommerce_single_product_summary', 'wcstub_' . $key, $prio);
    eval('function wcstub_' . $key . '() { echo $GLOBALS["P"]["' . $key . '"]; }');
}
function woocommerce_output_product_data_tabs() { echo '<p style="background:red;color:#fff">TABS NOT REMOVED</p>'; }
function woocommerce_upsell_display() { echo '<p style="background:red;color:#fff">UPSELLS NOT REMOVED</p>'; }
function woocommerce_output_related_products() { echo '<p style="background:red;color:#fff">RELATED NOT REMOVED</p>'; }
add_action('woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10);
add_action('woocommerce_after_single_product_summary', 'woocommerce_upsell_display', 15);
add_action('woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20);
function woocommerce_content() {
    echo '<div class="woocommerce-notices-wrapper"></div><div id="product-1208" class="product type-product post-1208 status-publish first instock product_cat-lifestyle-woodwork has-post-thumbnail sale shipping-taxable purchasable product-type-simple">';
    echo $GLOBALS['P']['onsale'] . $GLOBALS['P']['gallery'] . '<div class="summary entry-summary">';
    do_action('woocommerce_single_product_summary');
    echo '</div>';
    do_action('woocommerce_after_single_product_summary');
    echo '</div>';
}

// --- WordPress stubs ---
function esc_html($s) { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); }
function esc_attr($s) { return esc_html($s); }
function esc_url($s) { return esc_html($s); }
function wp_strip_all_tags($s) { return strip_tags((string) $s); }
function absint($n) { return abs((int) $n); }
function post_password_required($id) { return false; }
function has_term() { return true; }
function get_option($k) { return 15; }
function is_wp_error($x) { return false; }
function get_queried_object_id() { return 1208; }
function get_the_title($id) { return '延展之境櫻桃木桌'; }
function get_post_meta($id, $key, $single = false) { return ''; }
function get_term($id, $tax) { return null; }
function get_the_terms($id, $tax) { return array((object) array('slug' => 'lifestyle-woodwork', 'name' => '生活木作')); }
function get_term_link($term) { return 'https://a1.haotaimaker.com/product-category/lifestyle-woodwork/'; }
function bfnd_page_url($key) { $map = array('home' => '', 'service' => 'service/', 'collaboration' => 'collaboration/', 'furniture' => 'furniture/'); return 'https://a1.haotaimaker.com/' . ($map[$key] ?? ''); }
function bfft_asset($p) { return 'https://a1.haotaimaker.com/wp-content/themes/bears-fantasyland/' . $p; }
function wc_reviews_enabled() { return true; }
function comments_open($id) { return $GLOBALS['scenario'] === 'full'; }
function get_post($id) { return (object) array('ID' => $id); }
function setup_postdata($p) {}
function wp_reset_postdata() {}
function comments_template() { echo $GLOBALS['P']['reviews']; }
function wc_attribute_label($name) { return $name; }
function wc_get_product_terms() { return array(); }
function wc_format_dimensions($d) { return implode(' &times; ', array_filter($d)) . ' cm'; }
function wc_format_weight($w) { return $w . ' kg'; }
function wc_get_related_products($id, $limit, $exclude) { return array_keys($GLOBALS['cards']); }
function do_shortcode($code) {
    preg_match('/ids="([^"]*)"/', $code, $ids);
    $html = '<div class="woocommerce columns-4 "><ul class="products columns-4">';
    foreach (explode(',', $ids[1]) as $pid) { $html .= $GLOBALS['cards'][(int) $pid] ?? ''; }
    return $html . '</ul></div>';
}
class StubAttribute {
    function __construct(public string $name, public array $options) {}
    function get_visible() { return true; }
    function is_taxonomy() { return false; }
    function get_name() { return $this->name; }
    function get_options() { return $this->options; }
}
class StubProduct {
    function __construct(public array $data) {}
    function get_id() { return 1208; }
    function get_name() { return '延展之境櫻桃木桌'; }
    function get_description() { return $this->data['description']; }
    function get_attributes() { return $this->data['attributes']; }
    function has_dimensions() { return (bool) $this->data['dimensions']; }
    function get_dimensions($fmt) { return $this->data['dimensions']; }
    function has_weight() { return (bool) $this->data['weight']; }
    function get_weight() { return $this->data['weight']; }
    function get_upsell_ids() { return array(); }
    function is_visible() { return true; }
}
class StubCardProduct { function is_visible() { return true; } }
$GLOBALS['scenario'] = $scenario;
$current = array(
    'description' => '<p><img loading="lazy" decoding="async" class="alignnone size-medium wp-image-1210" src="https://a1.haotaimaker.com/wp-content/uploads/2026/01/%E5%95%86%E5%93%81-00018-2-300x227.jpg" alt="" width="300" height="227" /></p>',
    'attributes' => array(), 'dimensions' => array(), 'weight' => '',
);
// Preview-only sample copy to show how the template carries full content; not product facts.
$full = array(
    'description' => '<p>（預覽用示意文字）以一片櫻桃木延展出桌面與桌腳，轉角以燕尾榫收合，讓結構本身成為裝飾。木紋隨光線由淺粉轉為溫潤的紅褐，會隨使用年月慢慢加深。</p><p>適合作為玄關邊桌、書房小桌或沙發旁的置物桌。</p><figure class="wp-block-image size-large"><img src="https://a1.haotaimaker.com/wp-content/uploads/2026/01/%E5%95%86%E5%93%81-00010.jpg" alt=""><figcaption>示意：整體比例</figcaption></figure><h3>保養方式</h3><ul><li>以微濕軟布擦拭，避免長時間積水。</li><li>每半年可補一次護木油。</li></ul>',
    'attributes' => array(new StubAttribute('木種', array('櫻桃木')), new StubAttribute('表面處理', array('天然植物護木油')), new StubAttribute('結構', array('燕尾榫接')), new StubAttribute('交期', array('現貨・3 個工作天內出貨'))),
    'dimensions' => array('length' => '120', 'width' => '40', 'height' => '75'), 'weight' => '12',
);
function wc_get_product($id) { return $id === 1208 ? new StubProduct($GLOBALS['scenario'] === 'full' ? $GLOBALS['full'] : $GLOBALS['current']) : new StubCardProduct(); }
function is_product() { return true; }

require "$theme/src/render.php";
ob_start();
bfnd_render_product_page('https://a1.haotaimaker.com/woodshop/', 'https://a1.haotaimaker.com/cart/', 'https://a1.haotaimaker.com/my-account/');
$body = ob_get_clean();

$start = strpos($live, '<section class="bf-commerce-hero"');
$end_tag = '</nav>';
$next = strpos($live, '<nav class="bf-commerce-next');
$end = strpos($live, $end_tag, $next) + strlen($end_tag);
$page = substr($live, 0, $start) . $body . substr($live, $end);
$wc = 'https://a1.haotaimaker.com/wp-content/plugins/woocommerce/assets/';
$css = '<link rel="stylesheet" href="' . $wc . 'css/photoswipe/photoswipe.min.css"><link rel="stylesheet" href="' . $wc . 'css/photoswipe/default-skin/default-skin.min.css"><link rel="stylesheet" href="product.css">';
$page = preg_replace('#(<link[^>]+interactions\.css[^>]*>)#', '$1' . $css, $page, 1);
$page = str_replace('"zoom_enabled":"",', '"zoom_enabled":"",', $page);
$page = str_replace('"photoswipe_enabled":""', '"photoswipe_enabled":"1"', $page);
$page = str_replace('"flexslider_enabled":""', '"flexslider_enabled":"1"', $page);
$page = preg_replace('#(<script[^>]+single-product\.min\.js)#', '<script src="' . $wc . 'js/flexslider/jquery.flexslider.min.js"></script><script src="' . $wc . 'js/photoswipe/photoswipe.min.js"></script><script src="' . $wc . 'js/photoswipe/photoswipe-ui-default.min.js"></script>$1', $page, 1);
$page = str_replace('https://a1.haotaimaker.com/wp-content/themes/bears-fantasyland/public/style.css?ver=1.3.35', 'style.css?local-style', $page);
file_put_contents($out, $page);
echo "wrote $out (" . strlen($body) . " bytes of product markup)\n";
