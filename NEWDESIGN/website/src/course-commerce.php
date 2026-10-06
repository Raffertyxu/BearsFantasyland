<?php
if (!defined('ABSPATH')) { exit; }

/** Only dedicated virtual products can be used to collect course payments. */
function bfnd_course_product_is_eligible($product) {
    if (!$product || !is_object($product) || !method_exists($product, 'is_type')) { return false; }
    if (!in_array($product->get_type(), array('simple', 'variable'), true)) { return false; }
    if (get_post_status($product->get_id()) !== 'publish' || !bfnd_course_product_is_virtual($product)) { return false; }
    return get_post_meta($product->get_id(), '_bfnd_course_product', true) === 'yes';
}

/**
 * WooCommerce never stores "virtual" on a variable product's parent (only on its
 * variations), so a variable course product counts as virtual when every variation is.
 */
function bfnd_course_product_is_virtual($product) {
    if (!$product->is_type('variable')) { return $product->is_virtual(); }
    $children = $product->get_children();
    if (!$children) { return false; }
    foreach ($children as $child_id) {
        $child = wc_get_product($child_id);
        if (!$child || !$child->is_virtual()) { return false; }
    }
    return true;
}

function bfnd_course_product_is_assigned($product_id, $except_course_id = 0) {
    $args = array(
        'post_type' => 'bf_course', 'post_status' => array('publish', 'private', 'draft', 'pending'), 'numberposts' => 1,
        'meta_key' => '_bfnd_woo_id', 'meta_value' => absint($product_id),
    );
    if ($except_course_id) { $args['post__not_in'] = array(absint($except_course_id)); }
    return (bool) get_posts($args);
}

function bfnd_course_products($course_id = 0) {
    if (!function_exists('wc_get_product')) { return array(); }
    $ids = get_posts(array(
        'post_type' => 'product', 'post_status' => 'publish', 'numberposts' => -1,
        'fields' => 'ids', 'orderby' => 'title', 'order' => 'ASC',
        'meta_key' => '_bfnd_course_product', 'meta_value' => 'yes',
    ));
    $products = array();
    foreach ($ids as $id) {
        $product = wc_get_product($id);
        if (bfnd_course_product_is_eligible($product) && !bfnd_course_product_is_assigned($id, $course_id)) { $products[] = $product; }
    }
    return $products;
}

function bfnd_get_course_product($course_id) {
    $product_id = absint(get_post_meta($course_id, '_bfnd_woo_id', true));
    if (!$product_id || !function_exists('wc_get_product')) { return false; }
    $product = wc_get_product($product_id);
    return bfnd_course_product_is_eligible($product) ? $product : false;
}

function bfnd_course_registration_action($course_id) {
    $product = bfnd_get_course_product($course_id);
    if (!$product || !$product->is_purchasable() || !$product->is_in_stock()) { return false; }
    if ($product->is_type('variable')) {
        return array('url' => get_permalink($product->get_id()), 'label' => '選擇梯次／方案');
    }
    return array(
        'url' => add_query_arg('add-to-cart', $product->get_id(), wc_get_cart_url()),
        'label' => trim((string) get_post_meta($course_id, '_bfnd_registration_button', true)) ?: '立即報名',
    );
}

function bfnd_course_price_html($course_id) {
    $linked_id = absint(get_post_meta($course_id, '_bfnd_woo_id', true));
    $product = bfnd_get_course_product($course_id);
    if ($product) {
        $price_html = trim((string) $product->get_price_html());
        if ($price_html !== '') {
            $label = trim((string) get_post_meta($course_id, '_bfnd_price_label', true));
            return ($label !== '' ? esc_html($label) . ' ' : '') . wp_kses_post($price_html);
        }
    }
    if ($linked_id) { return ''; }
    $price = get_post_meta($course_id, '_bfnd_price', true);
    $label = trim((string) get_post_meta($course_id, '_bfnd_price_label', true));
    return $price !== '' ? ($label !== '' ? esc_html($label) . ' ' : '') . 'NT$ ' . esc_html(number_format((int) $price)) : '';
}

function bfnd_course_product_meta_box() {
    add_meta_box('bfnd_course_product', '木作課程／會員方案商品', 'bfnd_course_product_meta_box_html', 'product', 'side', 'default');
}
add_action('add_meta_boxes_product', 'bfnd_course_product_meta_box');

function bfnd_course_product_meta_box_html($post) {
    wp_nonce_field('bfnd_save_course_product', 'bfnd_course_product_nonce');
    $enabled = get_post_meta($post->ID, '_bfnd_course_product', true) === 'yes';
    echo '<p><label><input type="checkbox" name="bfnd_course_product" value="yes" ' . checked($enabled, true, false) . '> 將此商品作為課程報名／會員方案收款項目</label></p>';
    echo '<p class="description">儲存時會設為虛擬商品（不計運費），並從一般商店商品清單隱藏。課程可綁定此商品；變化商品可用不同方案或梯次及各自名額。每個商品只能綁一堂課。</p>';
}

function bfnd_save_course_product($product) {
    if (!isset($_POST['bfnd_course_product_nonce'])
        || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['bfnd_course_product_nonce'])), 'bfnd_save_course_product')
        || !current_user_can('edit_post', $product->get_id())) { return; }
    if (isset($_POST['bfnd_course_product']) && wp_unslash($_POST['bfnd_course_product']) === 'yes'
        && in_array($product->get_type(), array('simple', 'variable'), true)) {
        if (get_post_meta($product->get_id(), '_bfnd_course_previous_visibility', true) === '') {
            update_post_meta($product->get_id(), '_bfnd_course_previous_visibility', $product->get_catalog_visibility());
        }
        update_post_meta($product->get_id(), '_bfnd_course_product', 'yes');
        $product->set_virtual(true);
        if ($product->is_type('variable')) {
            foreach ($product->get_children() as $child_id) {
                $child = wc_get_product($child_id);
                if ($child && !$child->is_virtual()) { $child->set_virtual(true); $child->save(); }
            }
        }
        $product->set_catalog_visibility('hidden');
    } else {
        delete_post_meta($product->get_id(), '_bfnd_course_product');
        $previous_visibility = get_post_meta($product->get_id(), '_bfnd_course_previous_visibility', true);
        if (in_array($previous_visibility, array('visible', 'catalog', 'search', 'hidden'), true)) {
            $product->set_catalog_visibility($previous_visibility);
        }
        delete_post_meta($product->get_id(), '_bfnd_course_previous_visibility');
    }
}
add_action('woocommerce_admin_process_product_object', 'bfnd_save_course_product');

function bfnd_hide_course_products_from_catalog($visible, $product_id) {
    return get_post_meta($product_id, '_bfnd_course_product', true) === 'yes' ? false : $visible;
}
add_filter('woocommerce_product_is_visible', 'bfnd_hide_course_products_from_catalog', 10, 2);

function bfnd_course_cart_notice() {
    if (!function_exists('WC') || !WC()->cart) { return; }
    foreach (WC()->cart->get_cart() as $item) {
        $product_id = !empty($item['product_id']) ? absint($item['product_id']) : 0;
        if ($product_id && get_post_meta($product_id, '_bfnd_course_product', true) === 'yes') {
            echo '<div class="woocommerce-info" role="status">課程報名將以此筆 WooCommerce 訂單及付款紀錄管理。請填寫學員的聯絡資料；若由他人代為付款，請在訂單備註註明學員姓名。</div>';
            return;
        }
    }
}
add_action('woocommerce_before_cart', 'bfnd_course_cart_notice');
add_action('woocommerce_before_checkout_form', 'bfnd_course_cart_notice');

function bfnd_course_order_line_item($item, $cart_item_key, $values, $order) {
    $product_id = !empty($values['product_id']) ? absint($values['product_id']) : 0;
    if (!$product_id || get_post_meta($product_id, '_bfnd_course_product', true) !== 'yes') { return; }
    $courses = get_posts(array(
        'post_type' => 'bf_course', 'post_status' => array('publish', 'private'), 'numberposts' => 1,
        'meta_key' => '_bfnd_woo_id', 'meta_value' => $product_id,
    ));
    if ($courses) {
        $item->add_meta_data('課程／方案', get_the_title($courses[0]->ID), true);
        $item->add_meta_data('_bfnd_course_id', (int) $courses[0]->ID, true);
    }
}
add_action('woocommerce_checkout_create_order_line_item', 'bfnd_course_order_line_item', 10, 4);

/** Accept only HTTPS YouTube watch/share URLs for private course fulfillment. */
function bfnd_sanitize_course_access_url($url) {
    $url = trim((string) $url);
    if ($url === '') { return ''; }
    $url = esc_url_raw($url, array('https'));
    if ($url === '') { return ''; }
    $parts = wp_parse_url($url);
    if (!is_array($parts) || strtolower((string) ($parts['scheme'] ?? '')) !== 'https' || empty($parts['host']) || empty($parts['path'])) { return ''; }
    if (isset($parts['user']) || isset($parts['pass']) || (isset($parts['port']) && (int) $parts['port'] !== 443)) { return ''; }
    $host = strtolower(rtrim((string) $parts['host'], '.'));
    $allowed_hosts = array('youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtu.be');
    return in_array($host, $allowed_hosts, true) ? $url : '';
}

function bfnd_course_access_url($course_id) {
    return bfnd_sanitize_course_access_url(get_post_meta(absint($course_id), '_bfnd_course_access_url', true));
}

function bfnd_send_course_access_for_order($order_id, $force = false) {
    if (!function_exists('wc_get_order')) { return; }
    $order = $order_id instanceof WC_Order ? $order_id : wc_get_order(absint($order_id));
    if (!$order) { return; }
    if (!$order->is_paid()) {
        if ($force) { $order->add_order_note('線上課程連結尚未寄送：訂單尚未完成付款。'); }
        return;
    }

    $recipient = sanitize_email($order->get_billing_email());
    if (!$recipient || !is_email($recipient)) {
        $order->add_order_note('線上課程連結尚未寄送：訂單沒有有效的聯絡 Email。');
        return;
    }

    foreach ($order->get_items('line_item') as $item) {
        if (!($item instanceof WC_Order_Item_Product)) { continue; }
        $course_id = absint($item->get_meta('_bfnd_course_id', true));
        if (!$course_id || get_post_type($course_id) !== 'bf_course') { continue; }
        if (get_post_meta($course_id, '_bfnd_mode', true) !== 'online') { continue; }

        if (!$force && $item->get_meta('_bfnd_course_access_email_sent_at', true)) { continue; }
        $access_url = bfnd_course_access_url($course_id);
        if ($access_url === '') {
            if ($item->get_meta('_bfnd_course_access_email_state', true) !== 'waiting_for_link') {
                $item->update_meta_data('_bfnd_course_access_email_state', 'waiting_for_link');
                $item->save();
                $order->add_order_note('線上課程連結尚未寄送：請先在課程資料補上有效的 YouTube 連結，再使用「重新寄送線上課程連結」訂單動作。');
            }
            continue;
        }

        $course_name = sanitize_text_field(wp_specialchars_decode(get_the_title($course_id), ENT_QUOTES));
        $site_name = sanitize_text_field(wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES));
        $subject = sanitize_text_field(sprintf('[%s] %s 課程連結', $site_name, $course_name));
        $message = "您好，\n\n您購買的線上課程「{$course_name}」已完成付款。\n\n課程連結：{$access_url}\n\n若連結無法開啟，請直接回覆此信聯絡我們。\n\n{$site_name}";
        if (!wp_mail($recipient, $subject, $message)) {
            $order->add_order_note('線上課程連結寄送失敗：WordPress 郵件程序回報失敗，請確認訂單 Email 後使用重新寄送動作。');
            continue;
        }

        $item->update_meta_data('_bfnd_course_access_email_sent_at', current_time('mysql'));
        $item->update_meta_data('_bfnd_course_access_email_state', 'accepted_by_wp_mail');
        $item->save();
        $order->add_order_note('線上課程連結已交給 WordPress 郵件程序寄送至訂單 Email；實際收件仍需由客戶或管理員確認。');
    }
}

function bfnd_course_access_payment_complete($order_id) {
    bfnd_send_course_access_for_order($order_id);
}
add_action('woocommerce_payment_complete', 'bfnd_course_access_payment_complete', 20);
add_action('woocommerce_order_status_processing', 'bfnd_course_access_payment_complete', 20);
add_action('woocommerce_order_status_completed', 'bfnd_course_access_payment_complete', 20);

function bfnd_course_access_order_actions($actions, $order) {
    if (!($order instanceof WC_Order)) { return $actions; }
    foreach ($order->get_items('line_item') as $item) {
        $course_id = absint($item->get_meta('_bfnd_course_id', true));
        if ($course_id && get_post_meta($course_id, '_bfnd_mode', true) === 'online') {
            $actions['bfnd_resend_course_access'] = '重新寄送線上課程連結';
            break;
        }
    }
    return $actions;
}
add_filter('woocommerce_order_actions', 'bfnd_course_access_order_actions', 10, 2);

function bfnd_course_access_resend_order_action($order) {
    if (!($order instanceof WC_Order) || !current_user_can('edit_shop_order', $order->get_id())) { return; }
    bfnd_send_course_access_for_order($order, true);
}
add_action('woocommerce_order_action_bfnd_resend_course_access', 'bfnd_course_access_resend_order_action');
