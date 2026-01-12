if (!defined('ABSPATH'))
    exit;

// ========================================
// ADMIN PAGE
// ========================================
add_action('admin_menu', 'bf_bestsellers_menu');
function bf_bestsellers_menu()
{
    add_menu_page(
        '熱銷商品',
        '熱銷商品',
        'manage_options',
        'bf-bestsellers',
        'bf_bestsellers_admin_page',
        'dashicons-star-filled',
        58
    );
}

add_action('admin_init', 'bf_bestsellers_settings');
function bf_bestsellers_settings()
{
    register_setting('bf_bestsellers_options', 'bf_bestsellers_enabled');
    register_setting('bf_bestsellers_options', 'bf_bestsellers_title');
    register_setting('bf_bestsellers_options', 'bf_bestsellers_products');
}

function bf_bestsellers_admin_page()
{
    // Handle Save
    if (isset($_POST['bf_bestsellers_save']) && check_admin_referer('bf_bestsellers_nonce')) {
        update_option('bf_bestsellers_enabled', isset($_POST['bf_bestsellers_enabled']) ? 1 : 0);
        update_option('bf_bestsellers_title', sanitize_text_field($_POST['bf_bestsellers_title']));

        $products = [];
        for ($i = 1; $i <= 4; $i++) {
            $pid = isset($_POST['bf_product_' . $i]) ? intval($_POST['bf_product_' . $i]) : 0;
            if ($pid > 0)
                $products[] = $pid;
        }
        update_option('bf_bestsellers_products', implode(',', $products));

        echo '<div class="updated"><p>設定已儲存！</p></div>';
    }

    // Get Options
    $enabled = get_option('bf_bestsellers_enabled', 0);
    $title = get_option('bf_bestsellers_title', '飛熊經典熱銷');
    $products_raw = get_option('bf_bestsellers_products', '');
    $selected_products = $products_raw ? explode(',', $products_raw) : [];

    // Get all WooCommerce products
    $all_products = [];
    if (function_exists('wc_get_products')) {
        $all_products = wc_get_products([
            'status' => 'publish',
            'limit' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ]);
    }

    ?>
    <style>
        .bf-product-select {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
            padding: 12px;
            background: #f9f9f9;
            border-radius: 6px;
            border: 1px solid #E6D9CC;
        }

        .bf-product-select select {
            flex: 1;
            padding: 8px;
        }

        .bf-product-preview {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
            border: 1px solid #ddd;
            background: #fff;
        }

        .bf-product-info {
            flex: 1;
        }

        .bf-product-info strong {
            color: #5A4A42;
        }

        .bf-product-info small {
            color: #888;
        }
    </style>
    <div class="wrap">
        <h1 style="color:#5A4A42;">⭐ 熱銷商品管理器</h1>
        <form method="post">
            <?php wp_nonce_field('bf_bestsellers_nonce'); ?>

            <table class="form-table">
                <tr>
                    <th>啟用熱銷區塊</th>
                    <td>
                        <label>
                            <input type="checkbox" name="bf_bestsellers_enabled" value="1" <?php checked($enabled, 1); ?>>
                            在所有文章/頁面底部顯示熱銷商品
                        </label>
                    </td>
                </tr>
                <tr>
                    <th>區塊標題</th>
                    <td>
                        <input type="text" name="bf_bestsellers_title" value="<?php echo esc_attr($title); ?>"
                            class="regular-text" style="font-size:18px; font-weight:bold;">
                    </td>
                </tr>
            </table>

            <h2 style="margin-top:30px; color:#5A4A42;">選擇 4 個熱銷商品</h2>

            <?php for ($i = 0; $i < 4; $i++): ?>
                <?php
                $current_id = isset($selected_products[$i]) ? intval($selected_products[$i]) : 0;
                $current_product = $current_id ? wc_get_product($current_id) : null;
                $current_image = $current_product ? wp_get_attachment_image_url($current_product->get_image_id(), 'thumbnail') : '';
                ?>
                <div class="bf-product-select">
                    <span style="font-weight:bold; color:#8A6754; width:30px;">#
                        <?php echo $i + 1; ?>
                    </span>
                    <?php if ($current_image): ?>
                        <img src="<?php echo esc_url($current_image); ?>" class="bf-product-preview">
                    <?php else: ?>
                        <div class="bf-product-preview" style="display:flex;align-items:center;justify-content:center;color:#ccc;">?
                        </div>
                    <?php endif; ?>
                    <select name="bf_product_<?php echo $i + 1; ?>">
                        <option value="">-- 選擇商品 --</option>
                        <?php foreach ($all_products as $product): ?>
                            <option value="<?php echo $product->get_id(); ?>" <?php selected($current_id, $product->get_id()); ?>>
                                <?php echo esc_html($product->get_name()); ?> - NT$
                                <?php echo $product->get_price(); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endfor; ?>

            <p class="submit">
                <input type="submit" name="bf_bestsellers_save" class="button-primary" value="儲存設定">
            </p>
        </form>
    </div>
    <?php
}

// ========================================
// SHORTCODE OUTPUT
// ========================================
add_shortcode('bf_bestsellers', 'bf_bestsellers_shortcode');
function bf_bestsellers_shortcode($atts)
{
    return bf_bestsellers_render();
}

// ========================================
// FRONTEND OUTPUT (Auto-append)
// ========================================
add_filter('the_content', 'bf_bestsellers_append_to_content', 98);
function bf_bestsellers_append_to_content($content)
{
    if (!is_singular())
        return $content;

    $enabled = get_option('bf_bestsellers_enabled', 0);
    if (!$enabled)
        return $content;

    return $content . bf_bestsellers_render();
}

function bf_bestsellers_render()
{
    if (!function_exists('wc_get_product'))
        return '';

    $title = get_option('bf_bestsellers_title', '飛熊經典熱銷');
    $products_raw = get_option('bf_bestsellers_products', '');

    if (empty($products_raw))
        return '';

    $product_ids = explode(',', $products_raw);
    $products_html = '';

    foreach ($product_ids as $pid) {
        $product = wc_get_product(intval($pid));
        if (!$product)
            continue;

        $image_url = wp_get_attachment_image_url($product->get_image_id(), 'medium');
        $name = $product->get_name();
        $price = $product->get_price_html();
        $link = get_permalink($pid);

        $products_html .= '
        <a href="' . esc_url($link) . '" class="bf-bestseller__card">
            <div class="bf-bestseller__img-wrap">
                <img src="' . esc_url($image_url) . '" alt="' . esc_attr($name) . '">
            </div>
            <div class="bf-bestseller__info">
                <h3 class="bf-bestseller__name">' . esc_html($name) . '</h3>
                <div class="bf-bestseller__price">' . $price . '</div>
            </div>
        </a>';
    }

    $output = '
    <style>
    .bf-bestsellers-wrap {
        margin: 60px 0 !important;
        font-family: "Noto Sans TC", sans-serif !important;
    }
    .bf-bestsellers__title {
        text-align: center !important;
        font-family: "Noto Serif TC", serif !important;
        font-size: 28px !important;
        color: #5A4A42 !important;
        margin-bottom: 40px !important;
        letter-spacing: 0.2em !important;
    }
    .bf-bestsellers__grid {
        display: grid !important;
        grid-template-columns: repeat(4, 1fr) !important;
        gap: 20px !important;
        max-width: 1200px !important;
        margin: 0 auto !important;
        padding: 0 20px !important;
    }
    .bf-bestseller__card {
        text-decoration: none !important;
        color: inherit !important;
        display: block !important;
        transition: transform 0.3s ease !important;
    }
    .bf-bestseller__card:hover {
        transform: translateY(-5px) !important;
    }
    .bf-bestseller__img-wrap {
        position: relative !important;
        overflow: hidden !important;
        border-radius: 4px !important;
        aspect-ratio: 1/1 !important;
        background: #f5f5f5 !important;
    }
    .bf-bestseller__img-wrap img {
        width: 100% !important;
        height: 100% !important;
        object-fit: cover !important;
        transition: transform 0.4s ease !important;
    }
    .bf-bestseller__card:hover img {
        transform: scale(1.05) !important;
    }
    .bf-bestseller__info {
        padding: 15px 0 !important;
    }
    .bf-bestseller__name {
        font-size: 15px !important;
        font-weight: 500 !important;
        color: #5A4A42 !important;
        margin: 0 0 8px 0 !important;
        line-height: 1.4 !important;
    }
    .bf-bestseller__price {
        font-size: 14px !important;
        color: #8A6754 !important;
    }
    .bf-bestseller__price del {
        color: #aaa !important;
        margin-right: 8px !important;
    }
    @media (max-width: 992px) {
        .bf-bestsellers__grid { grid-template-columns: repeat(2, 1fr) !important; }
    }
    @media (max-width: 576px) {
        .bf-bestsellers__grid { grid-template-columns: 1fr !important; gap: 30px !important; }
        .bf-bestsellers__title { font-size: 22px !important; }
    }
    </style>
    
    <div class="bf-bestsellers-wrap">
        <h2 class="bf-bestsellers__title">' . esc_html($title) . '</h2>
        <div class="bf-bestsellers__grid">' . $products_html . '</div>
    </div>';

    return $output;
}