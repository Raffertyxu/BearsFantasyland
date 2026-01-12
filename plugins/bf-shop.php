<?php
/**
 * Plugin Name: BF Shop - 飛熊購物中心
 * Plugin URI: https://a1.haotaimaker.com/
 * Description: 愛馬仕風格 WooCommerce 商品列表頁，支援篩選、排序、Quick View
 * Version: 1.0.0
 * Author: Bear's Fantasyland
 * Text Domain: bf-shop
 * Requires Plugins: woocommerce
 */

if (!defined('ABSPATH')) exit;

class BF_Shop {

    private static $instance = null;
    private $option_name = 'bf_shop_options';

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_shortcode('bf_shop', array($this, 'render_shop'));
        
        // AJAX endpoints
        add_action('wp_ajax_bf_shop_filter', array($this, 'ajax_filter'));
        add_action('wp_ajax_nopriv_bf_shop_filter', array($this, 'ajax_filter'));
        add_action('wp_ajax_bf_quick_view', array($this, 'ajax_quick_view'));
        add_action('wp_ajax_nopriv_bf_quick_view', array($this, 'ajax_quick_view'));
    }

    public function get_defaults() {
        return array(
            'enabled' => true,
            'products_per_page' => 12,
            'columns' => 4,
            'show_filters' => true,
            'show_sorting' => true,
            'show_quick_view' => true,
            'default_orderby' => 'date',
        );
    }

    public function get_options() {
        $options = get_option($this->option_name);
        return empty($options) ? $this->get_defaults() : wp_parse_args($options, $this->get_defaults());
    }

    public function add_admin_menu() {
        add_submenu_page(
            'woocommerce',
            'BF 購物中心設定',
            '🛍️ BF 購物中心',
            'manage_options',
            'bf-shop-settings',
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
            .bf-shop-admin{max-width:700px;margin:20px auto;font-family:-apple-system,sans-serif}
            .bf-shop-admin h1{color:#8A6754;margin-bottom:30px}
            .bf-shop-admin .card{background:#fff;padding:24px;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,0.08);margin-bottom:20px}
            .bf-shop-admin label{display:block;margin-bottom:16px}
            .bf-shop-admin label span{display:block;font-weight:600;margin-bottom:6px;color:#333}
            .bf-shop-admin input[type="number"],.bf-shop-admin select{padding:10px 14px;border:1px solid #ddd;border-radius:6px;width:200px}
            .bf-shop-admin .checkbox-row{display:flex;align-items:center;gap:10px;padding:12px 16px;background:#f9f7f5;border-radius:8px;margin-bottom:10px}
            .bf-shop-admin .checkbox-row input{width:18px;height:18px;accent-color:#8A6754}
            .bf-shop-admin .submit-btn{background:linear-gradient(135deg,#8A6754,#6B4F3F);color:#fff;border:none;padding:14px 40px;font-size:16px;border-radius:8px;cursor:pointer}
            .bf-shop-admin code{background:#f0f8ff;padding:4px 10px;border-radius:4px;color:#0066cc}
        </style>
        <div class="bf-shop-admin">
            <h1>🛍️ BF 購物中心設定</h1>
            <div class="card">
                <p>使用短代碼 <code>[bf_shop]</code> 或 <code>[bf_shop category="家具" limit="8"]</code></p>
            </div>
            <form method="post" action="options.php">
                <?php settings_fields($this->option_name); ?>
                <div class="card">
                    <label><span>每頁顯示商品數</span>
                        <input type="number" name="<?php echo $this->option_name; ?>[products_per_page]" value="<?php echo esc_attr($o['products_per_page']); ?>" min="4" max="48">
                    </label>
                    <label><span>每行欄數</span>
                        <select name="<?php echo $this->option_name; ?>[columns]">
                            <option value="3" <?php selected($o['columns'], 3); ?>>3 欄</option>
                            <option value="4" <?php selected($o['columns'], 4); ?>>4 欄</option>
                        </select>
                    </label>
                    <label><span>預設排序</span>
                        <select name="<?php echo $this->option_name; ?>[default_orderby]">
                            <option value="date" <?php selected($o['default_orderby'], 'date'); ?>>最新上架</option>
                            <option value="price" <?php selected($o['default_orderby'], 'price'); ?>>價格低到高</option>
                            <option value="price-desc" <?php selected($o['default_orderby'], 'price-desc'); ?>>價格高到低</option>
                            <option value="popularity" <?php selected($o['default_orderby'], 'popularity'); ?>>熱銷商品</option>
                        </select>
                    </label>
                </div>
                <div class="card">
                    <div class="checkbox-row">
                        <input type="checkbox" id="show_filters" name="<?php echo $this->option_name; ?>[show_filters]" value="1" <?php checked($o['show_filters']); ?>>
                        <label for="show_filters" style="margin:0">顯示分類篩選器</label>
                    </div>
                    <div class="checkbox-row">
                        <input type="checkbox" id="show_sorting" name="<?php echo $this->option_name; ?>[show_sorting]" value="1" <?php checked($o['show_sorting']); ?>>
                        <label for="show_sorting" style="margin:0">顯示排序選項</label>
                    </div>
                    <div class="checkbox-row">
                        <input type="checkbox" id="show_quick_view" name="<?php echo $this->option_name; ?>[show_quick_view]" value="1" <?php checked($o['show_quick_view']); ?>>
                        <label for="show_quick_view" style="margin:0">啟用 Quick View 快速預覽</label>
                    </div>
                </div>
                <button type="submit" class="submit-btn">💾 儲存設定</button>
            </form>
        </div>
        <?php
    }

    /**
     * AJAX 篩選
     */
    public function ajax_filter() {
        $category = sanitize_text_field($_POST['category'] ?? '');
        $orderby = sanitize_text_field($_POST['orderby'] ?? 'date');
        $paged = intval($_POST['paged'] ?? 1);
        $limit = intval($_POST['limit'] ?? 12);

        $args = array(
            'post_type' => 'product',
            'posts_per_page' => $limit,
            'paged' => $paged,
            'post_status' => 'publish',
        );

        // Category filter
        if ($category) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'product_cat',
                    'field' => 'slug',
                    'terms' => $category,
                )
            );
        }

        // Ordering
        switch ($orderby) {
            case 'price':
                $args['meta_key'] = '_price';
                $args['orderby'] = 'meta_value_num';
                $args['order'] = 'ASC';
                break;
            case 'price-desc':
                $args['meta_key'] = '_price';
                $args['orderby'] = 'meta_value_num';
                $args['order'] = 'DESC';
                break;
            case 'popularity':
                $args['meta_key'] = 'total_sales';
                $args['orderby'] = 'meta_value_num';
                $args['order'] = 'DESC';
                break;
            default:
                $args['orderby'] = 'date';
                $args['order'] = 'DESC';
        }

        $query = new WP_Query($args);
        $html = $this->render_products($query);
        
        wp_send_json_success(array(
            'html' => $html,
            'found' => $query->found_posts,
            'pages' => $query->max_num_pages,
        ));
    }

    /**
     * Quick View AJAX
     */
    public function ajax_quick_view() {
        $product_id = intval($_POST['product_id'] ?? 0);
        if (!$product_id) wp_send_json_error();

        $product = wc_get_product($product_id);
        if (!$product) wp_send_json_error();

        $gallery_ids = $product->get_gallery_image_ids();
        $images = array(wp_get_attachment_image_url($product->get_image_id(), 'large'));
        foreach ($gallery_ids as $id) {
            $images[] = wp_get_attachment_image_url($id, 'large');
        }

        wp_send_json_success(array(
            'id' => $product_id,
            'name' => $product->get_name(),
            'price_html' => $product->get_price_html(),
            'short_description' => $product->get_short_description(),
            'description' => wp_trim_words($product->get_description(), 50),
            'images' => $images,
            'permalink' => $product->get_permalink(),
            'add_to_cart_url' => $product->add_to_cart_url(),
            'is_purchasable' => $product->is_purchasable(),
            'is_in_stock' => $product->is_in_stock(),
            'type' => $product->get_type(),
        ));
    }

    /**
     * 渲染商品卡片
     */
    private function render_products($query) {
        if (!$query->have_posts()) {
            return '<div class="bfs-no-products"><p>找不到符合條件的商品</p></div>';
        }

        $html = '';
        while ($query->have_posts()) {
            $query->the_post();
            $product = wc_get_product(get_the_ID());
            $image = wp_get_attachment_image_url($product->get_image_id(), 'woocommerce_thumbnail') ?: wc_placeholder_img_src();
            
            $html .= '<div class="bfs-product" data-id="' . get_the_ID() . '">';
            $html .= '<div class="bfs-product-image">';
            $html .= '<a href="' . get_permalink() . '"><img src="' . esc_url($image) . '" alt="' . esc_attr($product->get_name()) . '"></a>';
            $html .= '<div class="bfs-product-actions">';
            $html .= '<button class="bfs-quick-view-btn" data-id="' . get_the_ID() . '">快速預覽</button>';
            if ($product->is_purchasable() && $product->is_in_stock()) {
                if ($product->get_type() === 'simple') {
                    $html .= '<button class="bfs-add-cart-btn" data-id="' . get_the_ID() . '">加入購物車</button>';
                } else {
                    $html .= '<a href="' . get_permalink() . '" class="bfs-add-cart-btn">選擇規格</a>';
                }
            }
            $html .= '</div></div>';
            $html .= '<div class="bfs-product-info">';
            $html .= '<a href="' . get_permalink() . '" class="bfs-product-name">' . $product->get_name() . '</a>';
            $html .= '<div class="bfs-product-price">' . $product->get_price_html() . '</div>';
            $html .= '</div></div>';
        }
        wp_reset_postdata();
        return $html;
    }

    /**
     * Shortcode 輸出
     */
    public function render_shop($atts = array()) {
        // 避免在 REST API 請求時執行（Gutenberg 儲存時）
        if (defined('REST_REQUEST') && REST_REQUEST) {
            return '<div class="bf-shop-wrap"><p style="padding:40px;text-align:center;color:#999;">購物中心預覽將在前台顯示</p></div>';
        }

        if (!function_exists('WC') || !class_exists('WooCommerce')) {
            return '<p>請先安裝並啟用 WooCommerce</p>';
        }

        $o = $this->get_options();
        $atts = shortcode_atts(array(
            'category' => '',
            'limit' => $o['products_per_page'],
            'columns' => $o['columns'],
            'orderby' => $o['default_orderby'],
        ), $atts);

        // Get categories for filter
        $categories = get_terms(array(
            'taxonomy' => 'product_cat',
            'hide_empty' => true,
        ));

        // Initial query
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => $atts['limit'],
            'post_status' => 'publish',
        );

        if ($atts['category']) {
            $args['tax_query'] = array(array(
                'taxonomy' => 'product_cat',
                'field' => 'slug',
                'terms' => $atts['category'],
            ));
        }

        switch ($atts['orderby']) {
            case 'price':
                $args['meta_key'] = '_price';
                $args['orderby'] = 'meta_value_num';
                $args['order'] = 'ASC';
                break;
            case 'price-desc':
                $args['meta_key'] = '_price';
                $args['orderby'] = 'meta_value_num';
                $args['order'] = 'DESC';
                break;
            case 'popularity':
                $args['meta_key'] = 'total_sales';
                $args['orderby'] = 'meta_value_num';
                $args['order'] = 'DESC';
                break;
            default:
                $args['orderby'] = 'date';
                $args['order'] = 'DESC';
        }

        $query = new WP_Query($args);

        ob_start();
        ?>
<style>
/* ========== BF Shop Styles ========== */
.bf-shop-wrap {
    --bfs-brown: #8A6754;
    --bfs-brown-light: #A88B7A;
    --bfs-cream: #F9F7F5;
    --bfs-text: #333333;
    --bfs-text-light: #777777;
    --bfs-border: #E8E4E0;
    --bfs-white: #FFFFFF;
    font-family: 'Noto Sans TC', -apple-system, sans-serif;
    max-width: 1400px;
    margin: 0 auto;
    padding: 40px 20px;
}

/* Header */
.bfs-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 40px;
    flex-wrap: wrap;
    gap: 20px;
}

.bfs-title {
    font-family: 'Noto Serif TC', serif;
    font-size: 32px;
    font-weight: 600;
    color: var(--bfs-text);
    letter-spacing: 4px;
    margin: 0;
}

.bfs-count {
    font-size: 14px;
    color: var(--bfs-text-light);
    margin-left: 16px;
}

/* Filters */
.bfs-filters {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 30px;
}

.bfs-filter-btn {
    padding: 10px 24px;
    background: var(--bfs-white);
    border: 1px solid var(--bfs-border);
    border-radius: 30px;
    font-size: 14px;
    color: var(--bfs-text);
    cursor: pointer;
    transition: all 0.3s;
}

.bfs-filter-btn:hover,
.bfs-filter-btn.active {
    background: var(--bfs-brown);
    color: var(--bfs-white);
    border-color: var(--bfs-brown);
}

/* Sorting */
.bfs-sorting {
    display: flex;
    align-items: center;
    gap: 12px;
}

.bfs-sorting label {
    font-size: 14px;
    color: var(--bfs-text-light);
}

.bfs-sorting select {
    padding: 10px 16px;
    border: 1px solid var(--bfs-border);
    border-radius: 8px;
    font-size: 14px;
    color: var(--bfs-text);
    background: var(--bfs-white);
    cursor: pointer;
}

/* Product Grid */
.bfs-grid {
    display: grid;
    grid-template-columns: repeat(<?php echo $atts['columns']; ?>, 1fr);
    gap: 30px;
}

@media (max-width: 1024px) {
    .bfs-grid { grid-template-columns: repeat(3, 1fr); }
}

@media (max-width: 768px) {
    .bfs-grid { grid-template-columns: repeat(2, 1fr); gap: 20px; }
}

@media (max-width: 480px) {
    .bfs-grid { grid-template-columns: 1fr; }
}

/* Product Card */
.bfs-product {
    background: var(--bfs-white);
    border-radius: 12px;
    overflow: hidden;
    transition: transform 0.4s, box-shadow 0.4s;
}

.bfs-product:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 50px rgba(0,0,0,0.1);
}

.bfs-product-image {
    position: relative;
    aspect-ratio: 1;
    overflow: hidden;
    background: var(--bfs-cream);
}

.bfs-product-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.6s;
}

.bfs-product:hover .bfs-product-image img {
    transform: scale(1.08);
}

.bfs-product-actions {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 8px;
    opacity: 0;
    transform: translateY(20px);
    transition: all 0.4s;
}

.bfs-product:hover .bfs-product-actions {
    opacity: 1;
    transform: translateY(0);
}

.bfs-quick-view-btn,
.bfs-add-cart-btn {
    padding: 12px 20px;
    border: none;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    text-align: center;
    text-decoration: none;
}

.bfs-quick-view-btn {
    background: rgba(255,255,255,0.95);
    color: var(--bfs-text);
    backdrop-filter: blur(8px);
}

.bfs-quick-view-btn:hover {
    background: var(--bfs-white);
}

.bfs-add-cart-btn {
    background: var(--bfs-brown);
    color: var(--bfs-white);
}

.bfs-add-cart-btn:hover {
    background: #6B4F3F;
}

.bfs-product-info {
    padding: 20px;
    text-align: center;
}

.bfs-product-name {
    font-size: 15px;
    font-weight: 500;
    color: var(--bfs-text);
    text-decoration: none;
    display: block;
    margin-bottom: 8px;
    line-height: 1.4;
    transition: color 0.3s;
}

.bfs-product-name:hover {
    color: var(--bfs-brown);
}

.bfs-product-price {
    font-size: 16px;
    font-weight: 600;
    color: var(--bfs-brown);
}

.bfs-product-price del {
    color: var(--bfs-text-light);
    font-weight: 400;
    font-size: 14px;
    margin-right: 8px;
}

.bfs-product-price ins {
    text-decoration: none;
}

/* No Products */
.bfs-no-products {
    grid-column: 1 / -1;
    text-align: center;
    padding: 60px 20px;
    color: var(--bfs-text-light);
}

/* Loading */
.bfs-loading {
    position: relative;
    pointer-events: none;
}

.bfs-loading::after {
    content: '';
    position: absolute;
    inset: 0;
    background: rgba(255,255,255,0.8);
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Quick View Modal */
.bfs-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.6);
    z-index: 999998;
    opacity: 0;
    visibility: hidden;
    transition: all 0.4s;
    backdrop-filter: blur(4px);
}

.bfs-modal-overlay.active {
    opacity: 1;
    visibility: visible;
}

.bfs-modal {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%) scale(0.9);
    width: 90%;
    max-width: 900px;
    max-height: 90vh;
    background: var(--bfs-white);
    border-radius: 16px;
    z-index: 999999;
    opacity: 0;
    visibility: hidden;
    transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    overflow: hidden;
    display: flex;
}

.bfs-modal.active {
    opacity: 1;
    visibility: visible;
    transform: translate(-50%, -50%) scale(1);
}

.bfs-modal-close {
    position: absolute;
    top: 16px;
    right: 16px;
    width: 40px;
    height: 40px;
    background: var(--bfs-cream);
    border: none;
    border-radius: 50%;
    cursor: pointer;
    font-size: 20px;
    color: var(--bfs-text);
    z-index: 10;
    transition: all 0.3s;
}

.bfs-modal-close:hover {
    background: var(--bfs-brown);
    color: var(--bfs-white);
}

.bfs-modal-gallery {
    width: 50%;
    background: var(--bfs-cream);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px;
}

.bfs-modal-gallery img {
    max-width: 100%;
    max-height: 400px;
    object-fit: contain;
    border-radius: 8px;
}

.bfs-modal-content {
    width: 50%;
    padding: 40px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.bfs-modal-name {
    font-family: 'Noto Serif TC', serif;
    font-size: 24px;
    font-weight: 600;
    color: var(--bfs-text);
    margin-bottom: 16px;
    line-height: 1.3;
}

.bfs-modal-price {
    font-size: 22px;
    font-weight: 600;
    color: var(--bfs-brown);
    margin-bottom: 20px;
}

.bfs-modal-desc {
    font-size: 14px;
    color: var(--bfs-text-light);
    line-height: 1.8;
    margin-bottom: 30px;
}

.bfs-modal-buttons {
    display: flex;
    gap: 12px;
}

.bfs-modal-btn {
    flex: 1;
    padding: 14px 24px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    text-align: center;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.3s;
    border: none;
}

.bfs-modal-btn-detail {
    background: var(--bfs-cream);
    color: var(--bfs-text);
}

.bfs-modal-btn-detail:hover {
    background: var(--bfs-border);
}

.bfs-modal-btn-cart {
    background: var(--bfs-brown);
    color: var(--bfs-white);
}

.bfs-modal-btn-cart:hover {
    background: #6B4F3F;
}

@media (max-width: 768px) {
    .bfs-modal {
        flex-direction: column;
        max-height: 95vh;
        overflow-y: auto;
    }
    .bfs-modal-gallery,
    .bfs-modal-content {
        width: 100%;
    }
    .bfs-modal-gallery {
        padding: 30px;
    }
    .bfs-modal-content {
        padding: 24px;
    }
}
</style>

<div class="bf-shop-wrap" data-limit="<?php echo esc_attr($atts['limit']); ?>">
    <!-- Header -->
    <div class="bfs-header">
        <h2 class="bfs-title">購物中心<span class="bfs-count" id="bfs-count">(<?php echo $query->found_posts; ?> 件商品)</span></h2>
        <?php if ($o['show_sorting']): ?>
        <div class="bfs-sorting">
            <label>排序：</label>
            <select id="bfs-orderby">
                <option value="date" <?php selected($atts['orderby'], 'date'); ?>>最新上架</option>
                <option value="price" <?php selected($atts['orderby'], 'price'); ?>>價格低到高</option>
                <option value="price-desc" <?php selected($atts['orderby'], 'price-desc'); ?>>價格高到低</option>
                <option value="popularity" <?php selected($atts['orderby'], 'popularity'); ?>>熱銷商品</option>
            </select>
        </div>
        <?php endif; ?>
    </div>

    <!-- Category Filters -->
    <?php if ($o['show_filters'] && !empty($categories)): ?>
    <div class="bfs-filters">
        <button class="bfs-filter-btn active" data-category="">全部商品</button>
        <?php foreach ($categories as $cat): ?>
        <button class="bfs-filter-btn" data-category="<?php echo esc_attr($cat->slug); ?>"><?php echo esc_html($cat->name); ?></button>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Product Grid -->
    <div class="bfs-grid" id="bfs-grid">
        <?php echo $this->render_products($query); ?>
    </div>
</div>

<!-- Quick View Modal -->
<div class="bfs-modal-overlay" id="bfs-modal-overlay"></div>
<div class="bfs-modal" id="bfs-modal">
    <button class="bfs-modal-close" id="bfs-modal-close">&times;</button>
    <div class="bfs-modal-gallery"><img id="bfs-modal-img" src="" alt=""></div>
    <div class="bfs-modal-content">
        <h3 class="bfs-modal-name" id="bfs-modal-name"></h3>
        <div class="bfs-modal-price" id="bfs-modal-price"></div>
        <div class="bfs-modal-desc" id="bfs-modal-desc"></div>
        <div class="bfs-modal-buttons">
            <a href="#" class="bfs-modal-btn bfs-modal-btn-detail" id="bfs-modal-detail">查看詳情</a>
            <button class="bfs-modal-btn bfs-modal-btn-cart" id="bfs-modal-cart">加入購物車</button>
        </div>
    </div>
</div>

<script>
(function($) {
    var ajaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';
    var currentCategory = '';
    var currentOrderby = '<?php echo esc_js($atts['orderby']); ?>';
    var limit = <?php echo intval($atts['limit']); ?>;
    var currentProductId = 0;

    // Filter by category
    $('.bfs-filter-btn').on('click', function() {
        currentCategory = $(this).data('category');
        $('.bfs-filter-btn').removeClass('active');
        $(this).addClass('active');
        loadProducts();
    });

    // Sort
    $('#bfs-orderby').on('change', function() {
        currentOrderby = $(this).val();
        loadProducts();
    });

    // Load products via AJAX
    function loadProducts() {
        var $grid = $('#bfs-grid');
        $grid.addClass('bfs-loading');

        $.post(ajaxUrl, {
            action: 'bf_shop_filter',
            category: currentCategory,
            orderby: currentOrderby,
            limit: limit
        }, function(res) {
            $grid.removeClass('bfs-loading');
            if (res.success) {
                $grid.html(res.data.html);
                $('#bfs-count').text('(' + res.data.found + ' 件商品)');
            }
        });
    }

    // Quick View
    $(document).on('click', '.bfs-quick-view-btn', function(e) {
        e.preventDefault();
        var productId = $(this).data('id');
        currentProductId = productId;

        $.post(ajaxUrl, {
            action: 'bf_quick_view',
            product_id: productId
        }, function(res) {
            if (res.success) {
                var d = res.data;
                $('#bfs-modal-img').attr('src', d.images[0] || '');
                $('#bfs-modal-name').text(d.name);
                $('#bfs-modal-price').html(d.price_html);
                $('#bfs-modal-desc').html(d.short_description || d.description);
                $('#bfs-modal-detail').attr('href', d.permalink);
                
                if (d.type === 'simple' && d.is_purchasable && d.is_in_stock) {
                    $('#bfs-modal-cart').show().data('id', productId);
                } else {
                    $('#bfs-modal-cart').hide();
                }

                $('#bfs-modal, #bfs-modal-overlay').addClass('active');
                $('body').css('overflow', 'hidden');
            }
        });
    });

    // Close modal
    $('#bfs-modal-close, #bfs-modal-overlay').on('click', function() {
        $('#bfs-modal, #bfs-modal-overlay').removeClass('active');
        $('body').css('overflow', '');
    });

    // Add to cart from modal
    $('#bfs-modal-cart').on('click', function() {
        var productId = $(this).data('id');
        addToCart(productId);
    });

    // Add to cart from grid
    $(document).on('click', '.bfs-add-cart-btn[data-id]', function(e) {
        e.preventDefault();
        var productId = $(this).data('id');
        addToCart(productId);
    });

    function addToCart(productId) {
        $.post('<?php echo esc_url(wc_ajax_url()); ?>', {
            'add-to-cart': productId
        }, function() {
            // Trigger WooCommerce event
            $(document.body).trigger('added_to_cart');
            // Close modal
            $('#bfs-modal, #bfs-modal-overlay').removeClass('active');
            $('body').css('overflow', '');
        });
    }

    // Keyboard close
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape') {
            $('#bfs-modal, #bfs-modal-overlay').removeClass('active');
            $('body').css('overflow', '');
        }
    });
})(jQuery);
</script>
        <?php
        return ob_get_clean();
    }
}

// Initialize
add_action('plugins_loaded', function() {
    if (class_exists('WooCommerce')) {
        BF_Shop::get_instance();
    }
});
