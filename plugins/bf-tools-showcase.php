<?php
/**
 * Plugin Name: BF 優質工具展示 (Premium Tools Showcase)
 * Description: 管理並展示優質木工手工具，支援從商品列表選擇並顯示導購連結。Shortcode: [bf_tools_showcase]
 * Version: 2.0
 * Author: Bear's Fantasyland
 */

if (!defined('ABSPATH')) {
    exit;
}

// -----------------------------------------------------------------------------
// 1. Enqueue Scripts & Styles (Admin & Frontend)
// -----------------------------------------------------------------------------

function bf_tools_admin_assets($hook) {
    if ($hook !== 'toplevel_page_bf-tools-showcase') {
        return;
    }

    wp_enqueue_script('jquery');
    wp_enqueue_script('jquery-ui-sortable');
    wp_enqueue_media();
}
add_action('admin_enqueue_scripts', 'bf_tools_admin_assets');

// -----------------------------------------------------------------------------
// 2. Admin Menu & Page
// -----------------------------------------------------------------------------

function bf_tools_add_admin_menu() {
    add_menu_page(
        '優質工具管理',
        '優質工具',
        'manage_options',
        'bf-tools-showcase',
        'bf_tools_render_admin_page',
        'dashicons-hammer',
        26
    );
}
add_action('admin_menu', 'bf_tools_add_admin_menu');

// AJAX Handler for fetching product image
add_action('wp_ajax_bf_tools_get_product_info', 'bf_tools_get_product_info_ajax');
function bf_tools_get_product_info_ajax() {
    check_ajax_referer('bf_tools_ajax_nonce', 'nonce');
    
    $product_id = intval($_POST['product_id']);
    $product = wc_get_product($product_id);

    if ($product) {
        $image_url = wp_get_attachment_image_url($product->get_image_id(), 'thumbnail');
        $desc = $product->get_short_description() ?: $product->get_name(); // Fallback
        wp_send_json_success([
            'image' => $image_url ?: '',
            'name' => $product->get_name(),
            'desc' => strip_tags($desc)
        ]);
    }
    wp_send_json_error();
}

function bf_tools_render_admin_page() {
    // Save logic
    if (isset($_POST['bf_tools_save_nonce']) && wp_verify_nonce($_POST['bf_tools_save_nonce'], 'bf_tools_save_action')) {
        $tools = [];
        if (!empty($_POST['tools'])) {
            foreach ($_POST['tools'] as $tool) {
                // If product_id is selected, we prioritize it but allow overrides
                $tools[] = [
                    'product_id' => intval($tool['product_id']),
                    'custom_desc' => sanitize_textarea_field($tool['custom_desc']),
                    'btn_text' => sanitize_text_field($tool['btn_text']),
                ];
            }
        }
        update_option('bf_tools_list', $tools);
        echo '<div class="updated"><p>工具列表已更新！</p></div>';
    }

    $tools = get_option('bf_tools_list', []);

    // Get all products for dropdown
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
    <div class="wrap">
        <h1 class="wp-heading-inline">優質工具展示管理 (Premium Tools)</h1>
        <p>Shortcode: <code>[bf_tools_showcase]</code></p>
        <hr class="wp-header-end">

        <form method="post" id="bf-tools-form">
            <?php wp_nonce_field('bf_tools_save_action', 'bf_tools_save_nonce'); ?>
            
            <div id="bf-tools-container" class="bf-tools-sortable">
                <?php foreach ($tools as $index => $tool) : 
                    $pid = intval($tool['product_id']);
                    $current_product = $pid ? wc_get_product($pid) : null;
                    $image_url = $current_product ? wp_get_attachment_image_url($current_product->get_image_id(), 'thumbnail') : '';
                    $name = $current_product ? $current_product->get_name() : '未選擇商品';
                ?>
                    <div class="bf-tool-item">
                        <div class="bf-tool-header">
                            <span class="dashicons dashicons-move handle"></span>
                            <span class="tool-title-preview"><?php echo esc_html($name); ?></span>
                            <span class="dashicons dashicons-trash remove-tool" title="移除"></span>
                        </div>
                        <div class="bf-tool-body">
                            <div class="bf-field-row-split">
                                <!-- Image Preview -->
                                <div style="flex:0 0 100px;">
                                    <div class="bf-image-preview-wrapper">
                                        <?php if($image_url): ?>
                                            <img src="<?php echo esc_url($image_url); ?>" class="bf-image-preview">
                                        <?php else: ?>
                                            <div class="bf-no-img">?</div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <!-- Fields -->
                                <div style="flex:1;">
                                    <div class="bf-field-row">
                                        <label>選擇商品</label>
                                        <select name="tools[<?php echo $index; ?>][product_id]" class="widefat bf-product-selector">
                                            <option value="">-- 請選擇商品 --</option>
                                            <?php foreach ($all_products as $p): ?>
                                                <option value="<?php echo $p->get_id(); ?>" <?php selected($pid, $p->get_id()); ?>>
                                                    <?php echo esc_html($p->get_name()); ?> (ID: <?php echo $p->get_id(); ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="bf-field-row">
                                        <label>自訂描述 (若留空則使用商品簡述)</label>
                                        <textarea name="tools[<?php echo $index; ?>][custom_desc]" class="widefat" rows="2" placeholder="覆寫商品原本的描述..."><?php echo esc_textarea($tool['custom_desc']); ?></textarea>
                                    </div>
                                    <div class="bf-field-row">
                                        <label>按鈕文字</label>
                                        <input type="text" name="tools[<?php echo $index; ?>][btn_text]" class="widefat" value="<?php echo esc_attr($tool['btn_text'] ?: '查看商品'); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div style="margin-top: 20px;">
                <button type="button" class="button button-secondary" id="bf-add-tool">＋ 新增工具</button>
                <button type="submit" class="button button-primary huge-btn">儲存所有變更</button>
            </div>
        </form>
    </div>

    <!-- Hidden Template -->
    <script type="text/html" id="bf-tool-template">
        <div class="bf-tool-item">
            <div class="bf-tool-header">
                <span class="dashicons dashicons-move handle"></span>
                <span class="tool-title-preview">新工具</span>
                <span class="dashicons dashicons-trash remove-tool" title="移除"></span>
            </div>
            <div class="bf-tool-body">
                <div class="bf-field-row-split">
                    <div style="flex:0 0 100px;">
                        <div class="bf-image-preview-wrapper">
                            <div class="bf-no-img">?</div>
                            <img src="" class="bf-image-preview" style="display:none;">
                        </div>
                    </div>
                    <div style="flex:1;">
                        <div class="bf-field-row">
                            <label>選擇商品</label>
                            <select name="tools[{index}][product_id]" class="widefat bf-product-selector">
                                <option value="">-- 請選擇商品 --</option>
                                <?php foreach ($all_products as $p): ?>
                                    <option value="<?php echo $p->get_id(); ?>">
                                        <?php echo esc_html($p->get_name()); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="bf-field-row">
                            <label>自訂描述 (若留空則使用商品簡述)</label>
                            <textarea name="tools[{index}][custom_desc]" class="widefat" rows="2" placeholder="覆寫商品原本的描述..."></textarea>
                        </div>
                        <div class="bf-field-row">
                            <label>按鈕文字</label>
                            <input type="text" name="tools[{index}][btn_text]" class="widefat" value="查看商品">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </script>

    <style>
        .bf-tools-sortable { margin-top: 20px; max-width: 800px; }
        .bf-tool-item { background: #fff; border: 1px solid #ccd0d4; margin-bottom: 15px; box-shadow: 0 1px 1px rgba(0,0,0,.04); }
        .bf-tool-header { background: #fcfcfc; border-bottom: 1px solid #ccd0d4; padding: 10px; display: flex; align-items: center; cursor: move; }
        .handle { color: #aaa; margin-right: 10px; cursor: move; }
        .remove-tool { color: #a00; margin-left: auto; cursor: pointer; }
        .bf-tool-body { padding: 15px; background: #fff; }
        .bf-field-row { margin-bottom: 12px; }
        .bf-field-row label { display: block; font-weight: 600; margin-bottom: 5px; }
        .bf-field-row-split { display: flex; gap: 20px; }
        .bf-image-preview { width: 100px; height: 100px; object-fit: cover; border: 1px solid #ddd; padding: 3px; }
        .bf-no-img { width: 100px; height: 100px; background: #eee; display: flex; align-items: center; justify-content: center; font-size: 24px; color: #aaa; border: 1px dashed #ccc; }
        .huge-btn { padding: 5px 20px !important; font-size: 14px !important; }
    </style>

    <script>
    jQuery(document).ready(function($) {
        var ajaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';
        var nonce = '<?php echo wp_create_nonce('bf_tools_ajax_nonce'); ?>';

        // Sortable
        $('#bf-tools-container').sortable({
            handle: '.handle',
            placeholder: 'ui-state-highlight'
        });

        // Add New Tool
        $('#bf-add-tool').on('click', function() {
            var index = $('#bf-tools-container .bf-tool-item').length;
            var template = $('#bf-tool-template').html().replace(/{index}/g, index);
            $('#bf-tools-container').append(template);
        });

        // Remove Tool
        $(document).on('click', '.remove-tool', function() {
            if (confirm('確定移除？')) {
                $(this).closest('.bf-tool-item').remove();
            }
        });

        // Live Product Preview
        $(document).on('change', '.bf-product-selector', function() {
            var select = $(this);
            var pid = select.val();
            var item = select.closest('.bf-tool-item');
            var imgWrap = item.find('.bf-image-preview-wrapper');
            var titlePreview = item.find('.tool-title-preview');

            if (!pid) {
                imgWrap.html('<div class="bf-no-img">?</div>');
                titlePreview.text('新工具');
                return;
            }

            // Set loading state
            imgWrap.css('opacity', '0.5');

            $.post(ajaxUrl, {
                action: 'bf_tools_get_product_info',
                product_id: pid,
                nonce: nonce
            }, function(res) {
                imgWrap.css('opacity', '1');
                if (res.success) {
                    var data = res.data;
                    titlePreview.text(data.name);
                    
                    if (data.image) {
                        imgWrap.html('<img src="' + data.image + '" class="bf-image-preview">');
                    } else {
                        imgWrap.html('<div class="bf-no-img">No Img</div>');
                    }
                    
                    // Optional: auto-fill desc if empty? 
                    // Let's not auto-fill textarea value to avoid overwriting user custom edits.
                }
            });
        });
    });
    </script>
    <?php
}

// -----------------------------------------------------------------------------
// 3. Frontend Shortcode [bf_tools_showcase]
// -----------------------------------------------------------------------------

function bf_tools_shortcode() {
    $tools = get_option('bf_tools_list', []);

    if (empty($tools)) {
        return '';
    }

    ob_start();
    ?>
    <section class="bf-tools-showcase">
        <div class="bf-tools-container">
            <h2 class="bf-tools-heading" style="text-align:center; font-family:'Noto Serif TC'; font-size:32px; margin-bottom: 50px; color:#333;">精選優質木工手工具</h2>
            <div class="bf-tools-grid">
                <?php foreach ($tools as $tool) : 
                    $pid = intval($tool['product_id']);
                    $product = $pid ? wc_get_product($pid) : null;
                    
                    if (!$product) continue; // Skip deleted products

                    $image_url = wp_get_attachment_image_url($product->get_image_id(), 'medium_large');
                    $name = $product->get_name();
                    // Use custom desc if present, otherwise product short desc
                    $desc = !empty($tool['custom_desc']) ? $tool['custom_desc'] : ($product->get_short_description() ?: '');
                    $desc = wp_trim_words(strip_tags($desc), 20); // Limit length
                    $link = $product->get_permalink();
                    $btn_text = $tool['btn_text'] ?: '查看商品';
                ?>
                    <div class="bf-tool-card">
                        <div class="bf-tool-img-wrap">
                            <img src="<?php echo esc_url($image_url ?: wc_placeholder_img_src()); ?>" alt="<?php echo esc_attr($name); ?>">
                        </div>
                        <div class="bf-tool-content">
                            <h3 class="bf-tool-title"><?php echo esc_html($name); ?></h3>
                            <p class="bf-tool-desc"><?php echo esc_html($desc); ?></p>
                            <a href="<?php echo esc_url($link); ?>" class="bf-tool-btn"><?php echo esc_html($btn_text); ?></a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <style>
        .bf-tools-showcase { padding: 60px 0; background-color: #fff; }
        .bf-tools-container { max-width: 1200px; margin: 0 auto; padding: 0 24px; }
        .bf-tools-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 40px; }
        .bf-tool-card { background: #fff; border: 1px solid #eee; transition: all 0.3s ease; display: flex; flex-direction: column; }
        .bf-tool-card:hover { transform: translateY(-5px); box-shadow: 0 10px 30px rgba(0,0,0,0.08); }
        .bf-tool-img-wrap { width: 100%; aspect-ratio: 4/3; overflow: hidden; background: #f9f9f9; display: flex; align-items: center; justify-content: center; }
        .bf-tool-img-wrap img { width: 100%; height: 100%; object-fit: contain; padding: 20px; transition: transform 0.5s ease; }
        .bf-tool-card:hover .bf-tool-img-wrap img { transform: scale(1.05); }
        .bf-tool-content { padding: 24px; text-align: center; flex: 1; display: flex; flex-direction: column; }
        .bf-tool-title { font-family: 'Noto Serif TC', serif; font-size: 20px; color: #333; margin: 0 0 10px 0 !important; font-weight: 600; }
        .bf-tool-desc { font-family: 'Noto Sans TC', sans-serif; font-size: 14px; color: #777; margin-bottom: 24px !important; line-height: 1.6; flex-grow: 1; }
        .bf-tool-btn { display: inline-block; padding: 10px 24px; border: 1px solid #333; color: #333; text-decoration: none; font-size: 14px; transition: all 0.3s ease; align-self: center; letter-spacing: 0.05em; }
        .bf-tool-btn:hover { background: #333; color: #fff; }
    </style>
    <?php
    return ob_get_clean();
}
add_shortcode('bf_tools_showcase', 'bf_tools_shortcode');
