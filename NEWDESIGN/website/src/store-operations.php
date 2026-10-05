<?php
if (!defined('ABSPATH')) { exit; }

/** Find a WooCommerce product only when the title match is unambiguous. */
function bfnd_find_product_by_exact_title($title) {
    $posts = get_posts(array(
        'post_type' => 'product',
        'post_status' => array('publish', 'private', 'draft', 'pending'),
        'numberposts' => 30,
        'fields' => 'ids',
        's' => $title,
    ));
    $matches = array();
    foreach ($posts as $product_id) {
        if (get_the_title($product_id) === $title) { $matches[] = absint($product_id); }
    }
    return count($matches) === 1 ? $matches[0] : 0;
}

function bfnd_ensure_product_category($name, $slug) {
    $term = get_term_by('slug', $slug, 'product_cat');
    if (!$term) { $term = get_term_by('name', $name, 'product_cat'); }
    if ($term && !is_wp_error($term)) { return $term; }
    $created = wp_insert_term($name, 'product_cat', array('slug' => $slug));
    if (is_wp_error($created)) { return false; }
    return get_term((int) $created['term_id'], 'product_cat');
}

/** One-time, title-guarded WooCommerce category alignment from the client meeting. */
function bfnd_migrate_meeting_product_categories() {
    if (get_option('bfnd_meeting_product_categories_v1') || !taxonomy_exists('product_cat') || !function_exists('wc_get_product')) { return; }

    $lifestyle = bfnd_ensure_product_category('生活木作', 'lifestyle-woodwork');
    $tools = get_term_by('name', '手工具', 'product_cat');
    if (!$tools) { $tools = bfnd_ensure_product_category('手工具', 'hand-tools'); }
    if (!$lifestyle || !$tools || is_wp_error($lifestyle) || is_wp_error($tools)) { return; }

    $furniture_titles = array('胡桃曲木桌', '延展之境櫻桃木桌');
    $furniture_ids = array();
    foreach ($furniture_titles as $title) {
        $product_id = bfnd_find_product_by_exact_title($title);
        if (!$product_id) { return; }
        $furniture_ids[] = $product_id;
    }
    $tool_product_id = bfnd_find_product_by_exact_title('線鋸');
    if (!$tool_product_id) { return; }

    foreach ($furniture_ids as $product_id) {
        $assigned = wp_set_object_terms($product_id, array((int) $lifestyle->term_id), 'product_cat', true);
        if (is_wp_error($assigned) || $assigned === false) { return; }
        $classic = get_term_by('slug', 'classic', 'product_cat');
        if ($classic) {
            $removed = wp_remove_object_terms($product_id, (int) $classic->term_id, 'product_cat');
            if (is_wp_error($removed) || $removed === false) { return; }
        }
    }
    $assigned_tool = wp_set_object_terms($tool_product_id, array((int) $tools->term_id), 'product_cat', true);
    if (is_wp_error($assigned_tool) || $assigned_tool === false) { return; }
    update_option('bfnd_meeting_product_categories_v1', 1, false);
}
add_action('init', 'bfnd_migrate_meeting_product_categories', 30);

function bfnd_store_settings_menu() {
    if (!function_exists('WC')) { return; }
    add_submenu_page('woocommerce', '飛熊商務設定', '飛熊商務設定', 'manage_woocommerce', 'bfnd-store-settings', 'bfnd_store_settings_page');
}
add_action('admin_menu', 'bfnd_store_settings_menu', 99);

function bfnd_register_store_settings() {
    register_setting('bfnd_store_settings', 'bfnd_pickup_address', array('sanitize_callback' => 'bfnd_sanitize_pickup_address'));
    add_settings_section('bfnd_pickup_section', '自取設定', 'bfnd_pickup_section_description', 'bfnd-store-settings');
    add_settings_field('bfnd_pickup_address', '自取地址與取貨說明', 'bfnd_pickup_address_field', 'bfnd-store-settings', 'bfnd_pickup_section');
}
add_action('admin_init', 'bfnd_register_store_settings');

function bfnd_sanitize_pickup_address($value) {
    $value = sanitize_textarea_field((string) $value);
    if (function_exists('mb_substr')) { return mb_substr($value, 0, 1200); }
    return substr($value, 0, 3600);
}

function bfnd_pickup_section_description() {
    echo '<p>請填入客戶確認後的實際地址與取貨說明。欄位留白時，網站會暫停顯示 WooCommerce「本地取貨」方式；設定地址後，結帳頁、訂單明細與通知信會顯示取貨資訊。</p>';
    echo '<p>配送區域與宅配費用仍在「WooCommerce → 設定 → 運送」設定。要開放自取，請在適用的運送區域加入「本地取貨」方式。</p>';
}

function bfnd_pickup_address_field() {
    printf('<textarea id="bfnd_pickup_address" name="bfnd_pickup_address" rows="4" class="large-text" maxlength="1200">%s</textarea>', esc_textarea((string) get_option('bfnd_pickup_address', '')));
}

function bfnd_store_settings_page() {
    if (!current_user_can('manage_woocommerce')) { return; }
    echo '<div class="wrap"><h1>飛熊商務設定</h1><p>此設定延伸 WooCommerce 現有訂單與運送流程，不會替代綠界付款外掛。</p><form action="options.php" method="post">';
    settings_fields('bfnd_store_settings');
    do_settings_sections('bfnd-store-settings');
    submit_button('儲存自取資訊');
    echo '</form><hr><p><strong>綠界：</strong>付款方式、商店核准狀態與交易結果請在 WooCommerce 綠界設定及結帳流程核對；本頁不保存任何金流密鑰。</p></div>';
}

function bfnd_filter_unconfigured_pickup_rates($rates, $package) {
    if (trim((string) get_option('bfnd_pickup_address', '')) !== '') { return $rates; }
    foreach ($rates as $rate_id => $rate) {
        if (is_object($rate) && method_exists($rate, 'get_method_id') && $rate->get_method_id() === 'local_pickup') { unset($rates[$rate_id]); }
    }
    return $rates;
}
add_filter('woocommerce_package_rates', 'bfnd_filter_unconfigured_pickup_rates', 20, 2);

function bfnd_render_pickup_rate_address($rate) {
    if (!is_object($rate) || !method_exists($rate, 'get_method_id') || $rate->get_method_id() !== 'local_pickup') { return; }
    $address = trim((string) get_option('bfnd_pickup_address', ''));
    if ($address === '') { return; }
    echo '<p class="bfnd-pickup-address"><strong>自取地點：</strong>' . nl2br(esc_html($address)) . '</p>';
}
add_action('woocommerce_after_shipping_rate', 'bfnd_render_pickup_rate_address', 10, 1);

function bfnd_snapshot_pickup_address($order_id) {
    if (!function_exists('wc_get_order')) { return; }
    $order = wc_get_order(absint($order_id));
    if (!$order) { return; }
    foreach ($order->get_items('shipping') as $shipping_item) {
        if ($shipping_item->get_method_id() !== 'local_pickup') { continue; }
        $address = trim((string) get_option('bfnd_pickup_address', ''));
        if ($address === '') { return; }
        $order->update_meta_data('_bfnd_pickup_address_snapshot', $address);
        $order->save();
        $order->add_order_note('本訂單選擇自取。結帳時自取資訊已保存於訂單。');
        return;
    }
}
add_action('woocommerce_checkout_order_processed', 'bfnd_snapshot_pickup_address', 20, 1);

function bfnd_order_pickup_address($order) {
    if (!$order instanceof WC_Order) { return ''; }
    return trim((string) $order->get_meta('_bfnd_pickup_address_snapshot', true));
}

function bfnd_order_details_pickup_address($order) {
    $address = bfnd_order_pickup_address($order);
    if ($address === '') { return; }
    echo '<section class="bfnd-pickup-confirmation"><h2>自取資訊</h2><p>' . nl2br(esc_html($address)) . '</p></section>';
}
add_action('woocommerce_order_details_after_order_table', 'bfnd_order_details_pickup_address', 10, 1);

function bfnd_email_pickup_address($order, $sent_to_admin, $plain_text, $email) {
    $address = bfnd_order_pickup_address($order);
    if ($address === '') { return; }
    if ($plain_text) { echo "\n自取資訊：\n" . $address . "\n"; }
    else { echo '<h2>自取資訊</h2><p>' . nl2br(esc_html($address)) . '</p>'; }
}
add_action('woocommerce_email_after_order_table', 'bfnd_email_pickup_address', 20, 4);
