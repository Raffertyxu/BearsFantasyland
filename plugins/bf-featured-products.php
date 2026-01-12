    <?php
    /**
     * Plugin Name: BF 精選商品區塊
     * Description: 飛熊入夢 - 含導言、商品網格、全部商品按鈕的精選商品區塊
     * Version: 1.0.0
     * Author: BEAR'S FANTASYLAND
     */

    if (!defined('ABSPATH'))
        exit;

    // ========================================
    // CONSTANTS
    // ========================================
    define('BF_FEATURED_CACHE_KEY', 'bf_featured_products_cache');
    define('BF_FEATURED_CACHE_TIME', HOUR_IN_SECONDS);

    // ========================================
    // ADMIN SCRIPTS & STYLES
    // ========================================
    add_action('admin_enqueue_scripts', 'bf_featured_admin_assets');
    function bf_featured_admin_assets($hook)
    {
        if ($hook !== 'toplevel_page_bf-featured-products')
            return;

        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-ui-sortable');
    }

    add_action('admin_head', 'bf_featured_admin_head');
    function bf_featured_admin_head()
    {
        $screen = get_current_screen();
        if ($screen->id !== 'toplevel_page_bf-featured-products')
            return;

        echo '<style>' . bf_featured_admin_css() . '</style>';
    }

    add_action('admin_footer', 'bf_featured_admin_footer');
    function bf_featured_admin_footer()
    {
        $screen = get_current_screen();
        if ($screen->id !== 'toplevel_page_bf-featured-products')
            return;

        $ajax_url = admin_url('admin-ajax.php');
        $nonce = wp_create_nonce('bf_featured_ajax');
        ?>
        <script>
        jQuery(function($) {
            var bfFeatured = {
                ajaxUrl: '<?php echo esc_js($ajax_url); ?>',
                nonce: '<?php echo esc_js($nonce); ?>'
            };

            // Sortable
            $('.bf-products-list').sortable({
                handle: '.bf-drag-handle',
                placeholder: 'bf-product-item ui-sortable-placeholder',
                update: function() {
                    updateNumbers();
                }
            });

            // AJAX Image Preview
            $(document).on('change', '.bf-product-select select', function() {
                var $item = $(this).closest('.bf-product-item');
                var $preview = $item.find('.bf-product-preview');
                var productId = $(this).val();

                if (!productId) {
                    $preview.html('<span class="bf-no-img">?</span>');
                    return;
                }

                $preview.html('<span class="bf-no-img">⏳</span>');

                $.post(bfFeatured.ajaxUrl, {
                    action: 'bf_featured_get_product_image',
                    product_id: productId,
                    nonce: bfFeatured.nonce
                }, function(res) {
                    if (res.success && res.data.image) {
                        $preview.html('<img src="' + res.data.image + '" alt="">');
                    } else {
                        $preview.html('<span class="bf-no-img">📷</span>');
                    }
                });
            });

            // Add Product
            $('.bf-add-product-btn').on('click', function() {
                var count = $('.bf-product-item').length;
                if (count >= 12) {
                    alert('最多只能選擇 12 個商品');
                    return;
                }
                var template = $('#bf-product-template').html();
                $('.bf-products-list').append(template);
                updateNumbers();
            });

            // Remove Product
            $(document).on('click', '.bf-remove-btn', function() {
                if ($('.bf-product-item').length <= 1) {
                    alert('至少需要保留 1 個商品');
                    return;
                }
                $(this).closest('.bf-product-item').fadeOut(200, function() {
                    $(this).remove();
                    updateNumbers();
                });
            });

            function updateNumbers() {
                $('.bf-product-item').each(function(i) {
                    $(this).find('.bf-product-num').text(i + 1);
                    $(this).find('select').attr('name', 'bf_product_' + (i + 1));
                });
            }
        });
        </script>
        <?php
    }

    function bf_featured_admin_css()
    {
        return '
        :root {
            --bf-cream: #F5F1EB;
            --bf-sand: #E6D9CC;
            --bf-taupe: #C4A995;
            --bf-brown: #8A6754;
            --bf-brown-dark: #6d513f;
            --bf-text: #3D3D3D;
            --bf-text-light: #7A7A7A;
        }
        .bf-admin-wrap {
            max-width: 900px;
            margin-top: 20px;
        }
        .bf-admin-header {
            background: linear-gradient(135deg, #4A6741 0%, #3d5636 100%);
            color: #fff;
            padding: 25px 30px;
            border-radius: 8px 8px 0 0;
            margin: -1px;
        }
        .bf-admin-header h1 {
            margin: 0;
            font-size: 22px;
            font-weight: 600;
        }
        .bf-admin-header p {
            margin: 8px 0 0;
            opacity: 0.85;
            font-size: 13px;
        }
        .bf-admin-body {
            background: #fff;
            padding: 30px;
            border: 1px solid var(--bf-sand);
            border-top: none;
            border-radius: 0 0 8px 8px;
        }
        .bf-field-group {
            margin-bottom: 25px;
        }
        .bf-field-group label {
            display: block;
            font-weight: 600;
            color: var(--bf-text);
            margin-bottom: 8px;
        }
        .bf-field-group input[type="text"],
        .bf-field-group textarea,
        .bf-field-group select {
            width: 100%;
            max-width: 500px;
            padding: 10px 12px;
            border: 1px solid var(--bf-taupe);
            border-radius: 4px;
            font-size: 14px;
        }
        .bf-field-group textarea {
            min-height: 60px;
            resize: vertical;
        }
        .bf-field-group input[type="text"]:focus,
        .bf-field-group textarea:focus,
        .bf-field-group select:focus {
            outline: none;
            border-color: var(--bf-brown);
            box-shadow: 0 0 0 2px rgba(138, 103, 84, 0.15);
        }
        .bf-field-hint {
            font-size: 12px;
            color: var(--bf-text-light);
            margin-top: 5px;
        }
        .bf-section-title {
            font-size: 16px;
            color: var(--bf-brown);
            margin: 30px 0 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--bf-sand);
        }
        .bf-products-list {
            list-style: none;
            padding: 0;
            margin: 0;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }
        .bf-product-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px;
            background: var(--bf-cream);
            border: 1px solid var(--bf-sand);
            border-radius: 6px;
            cursor: grab;
            transition: all 0.2s ease;
        }
        .bf-product-item:hover {
            border-color: var(--bf-brown);
        }
        .bf-product-item.ui-sortable-helper {
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            cursor: grabbing;
        }
        .bf-product-item.ui-sortable-placeholder {
            visibility: visible !important;
            background: rgba(138, 103, 84, 0.1);
            border: 2px dashed var(--bf-brown);
        }
        .bf-drag-handle {
            color: var(--bf-taupe);
            font-size: 16px;
            cursor: grab;
        }
        .bf-product-num {
            width: 24px;
            height: 24px;
            background: var(--bf-brown);
            color: #fff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 600;
            flex-shrink: 0;
        }
        .bf-product-preview {
            width: 50px;
            height: 50px;
            border-radius: 4px;
            overflow: hidden;
            background: #fff;
            border: 1px solid var(--bf-sand);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .bf-product-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .bf-product-preview .bf-no-img {
            color: var(--bf-taupe);
            font-size: 20px;
        }
        .bf-product-select {
            flex: 1;
            min-width: 0;
        }
        .bf-product-select select {
            width: 100%;
            padding: 8px;
            border: 1px solid var(--bf-taupe);
            border-radius: 4px;
            background: #fff;
            font-size: 12px;
        }
        .bf-remove-btn {
            background: none;
            border: none;
            color: var(--bf-text-light);
            cursor: pointer;
            padding: 6px;
            border-radius: 4px;
            transition: all 0.2s;
            flex-shrink: 0;
        }
        .bf-remove-btn:hover {
            background: #fee;
            color: #c00;
        }
        .bf-add-product-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            background: var(--bf-cream);
            border: 2px dashed var(--bf-taupe);
            border-radius: 6px;
            color: var(--bf-brown);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            margin-top: 15px;
        }
        .bf-add-product-btn:hover {
            border-color: var(--bf-brown);
            background: #fff;
        }
        .bf-submit-btn {
            background: var(--bf-brown);
            color: #fff;
            border: none;
            padding: 14px 35px;
            font-size: 15px;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
            margin-top: 25px;
        }
        .bf-submit-btn:hover {
            background: var(--bf-brown-dark);
            transform: translateY(-1px);
        }
        .bf-notice {
            padding: 12px 18px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .bf-notice-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .bf-shortcode-box {
            background: var(--bf-cream);
            padding: 15px 20px;
            border-radius: 6px;
            border: 1px solid var(--bf-sand);
            margin-top: 30px;
        }
        .bf-shortcode-box code {
            background: #fff;
            padding: 8px 15px;
            border-radius: 4px;
            font-size: 14px;
            color: var(--bf-brown);
            border: 1px solid var(--bf-taupe);
            display: inline-block;
            margin-top: 8px;
        }
        .bf-columns-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        @media (max-width: 782px) {
            .bf-products-list { grid-template-columns: 1fr; }
            .bf-columns-2 { grid-template-columns: 1fr; }
        }
        ';
    }

    // ========================================
    // AJAX HANDLER
    // ========================================
    add_action('wp_ajax_bf_featured_get_product_image', 'bf_featured_get_product_image_ajax');
    function bf_featured_get_product_image_ajax()
    {
        check_ajax_referer('bf_featured_ajax', 'nonce');

        $product_id = intval($_POST['product_id']);
        $product = wc_get_product($product_id);

        if ($product) {
            $image_url = wp_get_attachment_image_url($product->get_image_id(), 'thumbnail');
            wp_send_json_success(['image' => $image_url ?: '']);
        }

        wp_send_json_error();
    }

    // ========================================
    // ADMIN MENU
    // ========================================
    add_action('admin_menu', 'bf_featured_menu');
    function bf_featured_menu()
    {
        add_menu_page(
            '精選商品',
            '精選商品',
            'manage_options',
            'bf-featured-products',
            'bf_featured_admin_page',
            'dashicons-grid-view',
            59
        );
    }

    // ========================================
    // ADMIN PAGE
    // ========================================
    function bf_featured_admin_page()
    {
        // Handle Save
        $saved = false;
        if (isset($_POST['bf_featured_save']) && check_admin_referer('bf_featured_nonce')) {
            update_option('bf_featured_subtitle', sanitize_text_field($_POST['bf_featured_subtitle']));
            update_option('bf_featured_title', sanitize_text_field($_POST['bf_featured_title']));
            update_option('bf_featured_btn_text', sanitize_text_field($_POST['bf_featured_btn_text']));
            update_option('bf_featured_btn_url', esc_url_raw($_POST['bf_featured_btn_url']));
            update_option('bf_featured_columns', intval($_POST['bf_featured_columns']));

            $products = [];
            foreach ($_POST as $key => $value) {
                if (strpos($key, 'bf_product_') === 0) {
                    $pid = intval($value);
                    if ($pid > 0)
                        $products[] = $pid;
                }
            }
            update_option('bf_featured_products', implode(',', $products));

            // Clear cache
            delete_transient(BF_FEATURED_CACHE_KEY);

            $saved = true;
        }

        // Get Options
        $subtitle = get_option('bf_featured_subtitle', '40年工藝・在地實木家具');
        $title = get_option('bf_featured_title', '真摯的陪伴，總是細膩無聲的');
        $btn_text = get_option('bf_featured_btn_text', '逛逛更多森手家具 >');
        $btn_url = get_option('bf_featured_btn_url', '/shop/');
        $columns = get_option('bf_featured_columns', 3);
        $products_raw = get_option('bf_featured_products', '');
        $selected_products = $products_raw ? explode(',', $products_raw) : [];

        // Ensure at least 6 slots
        if (empty($selected_products))
            $selected_products = array_fill(0, 6, 0);

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
        <div class="wrap bf-admin-wrap">
            <div class="bf-admin-header">
                <h1>🌲 精選商品區塊管理器</h1>
                <p>設定導言文字、商品網格、全部商品按鈕</p>
            </div>

            <div class="bf-admin-body">
                <?php if ($saved): ?>
                    <div class="bf-notice bf-notice-success">✅ 設定已儲存，快取已清除！</div>
                <?php endif; ?>

                <form method="post">
                    <?php wp_nonce_field('bf_featured_nonce'); ?>

                    <h3 class="bf-section-title">📝 導言區塊</h3>

                    <div class="bf-field-group">
                        <label for="bf_subtitle">副標題 / 小標</label>
                        <input type="text" id="bf_subtitle" name="bf_featured_subtitle"
                            value="<?php echo esc_attr($subtitle); ?>" placeholder="40年工藝・在地實木家具">
                        <p class="bf-field-hint">顯示在主標題上方的小字</p>
                    </div>

                    <div class="bf-field-group">
                        <label for="bf_title">主標題</label>
                        <input type="text" id="bf_title" name="bf_featured_title"
                            value="<?php echo esc_attr($title); ?>" placeholder="真摯的陪伴，總是細膩無聲的">
                    </div>

                    <h3 class="bf-section-title">🔘 底部按鈕</h3>

                    <div class="bf-columns-2">
                        <div class="bf-field-group">
                            <label for="bf_btn_text">按鈕文字</label>
                            <input type="text" id="bf_btn_text" name="bf_featured_btn_text"
                                value="<?php echo esc_attr($btn_text); ?>" placeholder="逛逛更多森手家具 >">
                        </div>

                        <div class="bf-field-group">
                            <label for="bf_btn_url">按鈕連結</label>
                            <input type="text" id="bf_btn_url" name="bf_featured_btn_url"
                                value="<?php echo esc_attr($btn_url); ?>" placeholder="/shop/">
                        </div>
                    </div>

                    <div class="bf-field-group">
                        <label for="bf_columns">每行顯示欄數</label>
                        <select id="bf_columns" name="bf_featured_columns" style="max-width: 150px;">
                            <option value="2" <?php selected($columns, 2); ?>>2 欄</option>
                            <option value="3" <?php selected($columns, 3); ?>>3 欄</option>
                            <option value="4" <?php selected($columns, 4); ?>>4 欄</option>
                        </select>
                    </div>

                    <h3 class="bf-section-title">📦 選擇商品 (拖曳可排序)</h3>

                    <ul class="bf-products-list">
                        <?php
                        $i = 0;
                        foreach ($selected_products as $pid):
                            $i++;
                            $current_id = intval($pid);
                            $current_product = $current_id ? wc_get_product($current_id) : null;
                            $current_image = $current_product ? wp_get_attachment_image_url($current_product->get_image_id(), 'thumbnail') : '';
                            ?>
                            <li class="bf-product-item">
                                <span class="bf-drag-handle">☰</span>
                                <span class="bf-product-num"><?php echo $i; ?></span>
                                <div class="bf-product-preview">
                                    <?php if ($current_image): ?>
                                        <img src="<?php echo esc_url($current_image); ?>" alt="">
                                    <?php else: ?>
                                        <span class="bf-no-img">?</span>
                                    <?php endif; ?>
                                </div>
                                <div class="bf-product-select">
                                    <select name="bf_product_<?php echo $i; ?>">
                                        <option value="">-- 選擇商品 --</option>
                                        <?php foreach ($all_products as $product): ?>
                                            <option value="<?php echo $product->get_id(); ?>" <?php selected($current_id, $product->get_id()); ?>>
                                                <?php echo esc_html($product->get_name()); ?> - NT$<?php echo $product->get_price(); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="button" class="bf-remove-btn" title="移除">✕</button>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <button type="button" class="bf-add-product-btn">+ 新增商品</button>

                    <!-- Template for new items -->
                    <script type="text/html" id="bf-product-template">
                        <li class="bf-product-item">
                            <span class="bf-drag-handle">☰</span>
                            <span class="bf-product-num">0</span>
                            <div class="bf-product-preview">
                                <span class="bf-no-img">?</span>
                            </div>
                            <div class="bf-product-select">
                                <select name="bf_product_new">
                                    <option value="">-- 選擇商品 --</option>
                                    <?php foreach ($all_products as $product): ?>
                                        <option value="<?php echo $product->get_id(); ?>">
                                            <?php echo esc_html($product->get_name()); ?> - NT$<?php echo $product->get_price(); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="button" class="bf-remove-btn" title="移除">✕</button>
                        </li>
                    </script>

                    <div class="bf-shortcode-box">
                        <strong>📋 短代碼</strong><br>
                        <code>[bf_featured_products]</code>
                        <p style="margin: 10px 0 0; color: #666; font-size: 13px;">
                            將此短代碼貼到任何頁面或文章中即可顯示精選商品區塊
                        </p>
                    </div>

                    <button type="submit" name="bf_featured_save" class="bf-submit-btn">💾 儲存設定</button>
                </form>
            </div>
        </div>
        <?php
    }

    // ========================================
    // FRONTEND CSS
    // ========================================
    function bf_featured_frontend_css($columns = 3)
    {
        return '
        .bf-featured-wrap {
            --bf-cream: #F5F1EB;
            --bf-sand: #E6D9CC;
            --bf-taupe: #C4A995;
            --bf-brown: #8A6754;
            --bf-text: #3D3D3D;
            --bf-text-light: #7A7A7A;
            --bf-font-display: "Noto Serif TC", serif;
            --bf-font-body: "Noto Sans TC", sans-serif;

            padding: 80px 20px;
            max-width: 1200px;
            margin: 0 auto;
            font-family: var(--bf-font-body);
        }
        .bf-featured__header {
            text-align: center;
            margin-bottom: 50px;
        }
        .bf-featured__subtitle {
            font-size: 14px;
            color: var(--bf-brown);
            letter-spacing: 0.2em;
            margin-bottom: 15px;
        }
        .bf-featured__title {
            font-family: var(--bf-font-display);
            font-size: 32px;
            color: var(--bf-text);
            font-weight: 600;
            letter-spacing: 0.05em;
            margin: 0;
        }
        .bf-featured__grid {
            display: grid;
            grid-template-columns: repeat(' . $columns . ', 1fr);
            gap: 30px 25px;
            margin-bottom: 50px;
        }
        .bf-featured__card {
            text-decoration: none;
            color: inherit;
            display: block;
            transition: transform 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }
        .bf-featured__card:hover {
            transform: translateY(-6px);
        }
        .bf-featured__img-wrap {
            position: relative;
            overflow: hidden;
            aspect-ratio: 1/1;
            background: #f8f8f8;
            margin-bottom: 15px;
        }
        .bf-featured__img-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }
        .bf-featured__card:hover img {
            transform: scale(1.05);
        }
        .bf-featured__name {
            font-size: 15px;
            font-weight: 500;
            color: var(--bf-text);
            margin: 0 0 8px 0;
            line-height: 1.5;
        }
        .bf-featured__price {
            font-size: 14px;
            color: var(--bf-brown);
        }
        .bf-featured__price del {
            color: var(--bf-taupe);
            margin-right: 8px;
        }
        .bf-featured__footer {
            text-align: center;
        }
        .bf-featured__btn {
            display: inline-block;
            padding: 16px 40px;
            background: var(--bf-brown);
            color: #fff;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            letter-spacing: 0.1em;
            transition: all 0.3s ease;
        }
        .bf-featured__btn:hover {
            background: #6d513f;
            transform: translateY(-2px);
        }
        @media (max-width: 1024px) {
            .bf-featured__grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 600px) {
            .bf-featured__grid { grid-template-columns: 1fr; gap: 40px; }
            .bf-featured__title { font-size: 24px; }
            .bf-featured-wrap { padding: 50px 15px; }
        }
        ';
    }

    // ========================================
    // SHORTCODE
    // ========================================
    add_shortcode('bf_featured_products', 'bf_featured_shortcode');
    function bf_featured_shortcode($atts)
    {
        if (!function_exists('wc_get_product'))
            return '<!-- WooCommerce not active -->';

        // Check cache
        $cached = get_transient(BF_FEATURED_CACHE_KEY);
        if ($cached !== false) {
            return $cached;
        }

        // Generate fresh output
        $output = bf_featured_render();

        // Store in cache
        set_transient(BF_FEATURED_CACHE_KEY, $output, BF_FEATURED_CACHE_TIME);

        return $output;
    }

    function bf_featured_render()
    {
        $subtitle = get_option('bf_featured_subtitle', '40年工藝・在地實木家具');
        $title = get_option('bf_featured_title', '真摯的陪伴，總是細膩無聲的');
        $btn_text = get_option('bf_featured_btn_text', '逛逛更多森手家具 >');
        $btn_url = get_option('bf_featured_btn_url', '/shop/');
        $columns = get_option('bf_featured_columns', 3);
        $products_raw = get_option('bf_featured_products', '');

        if (empty($products_raw))
            return '<!-- No featured products configured -->';

        $product_ids = explode(',', $products_raw);
        $products_html = '';

        foreach ($product_ids as $pid) {
            $product = wc_get_product(intval($pid));
            if (!$product)
                continue;

            $image_url = wp_get_attachment_image_url($product->get_image_id(), 'medium_large');
            $name = $product->get_name();
            $price = $product->get_price_html();
            $link = get_permalink($pid);

            $products_html .= '
            <a href="' . esc_url($link) . '" class="bf-featured__card">
                <div class="bf-featured__img-wrap">
                    <img src="' . esc_url($image_url) . '" alt="' . esc_attr($name) . '" loading="lazy">
                </div>
                <h3 class="bf-featured__name">' . esc_html($name) . '</h3>
                <div class="bf-featured__price">' . $price . '</div>
            </a>';
        }

        if (empty($products_html))
            return '<!-- No valid products -->';

        $btn_html = '';
        if (!empty($btn_text) && !empty($btn_url)) {
            $btn_html = '
            <div class="bf-featured__footer">
                <a href="' . esc_url($btn_url) . '" class="bf-featured__btn">' . esc_html($btn_text) . '</a>
            </div>';
        }

        return '
        <style>' . bf_featured_frontend_css($columns) . '</style>
        <div class="bf-featured-wrap">
            <header class="bf-featured__header">
                <p class="bf-featured__subtitle">' . esc_html($subtitle) . '</p>
                <h2 class="bf-featured__title">' . esc_html($title) . '</h2>
            </header>
            <div class="bf-featured__grid">' . $products_html . '</div>
            ' . $btn_html . '
        </div>';
    }
