<?php
if (!defined('ABSPATH')) { exit; }

/**
 * Furniture works can be bought through one WooCommerce variable product.
 * Each variation is one wood species with its own price and stock, so the
 * work page lists the variations and posts the chosen one to the cart.
 */
function bfnd_work_product_is_eligible($product) {
    if (!$product || !is_object($product) || !method_exists($product, 'is_type')) { return false; }
    if (!$product->is_type('variable') || get_post_status($product->get_id()) !== 'publish') { return false; }
    return get_post_meta($product->get_id(), '_bfnd_work_product', true) === 'yes';
}

function bfnd_work_product_is_assigned($product_id, $except_work_id = 0) {
    $args = array(
        'post_type' => 'bf_work', 'post_status' => array('publish', 'private', 'draft', 'pending'), 'numberposts' => 1,
        'meta_key' => '_bfnd_woo_id', 'meta_value' => absint($product_id),
    );
    if ($except_work_id) { $args['post__not_in'] = array(absint($except_work_id)); }
    return (bool) get_posts($args);
}

function bfnd_work_products($work_id = 0) {
    if (!function_exists('wc_get_product')) { return array(); }
    $ids = get_posts(array(
        'post_type' => 'product', 'post_status' => 'publish', 'numberposts' => -1,
        'fields' => 'ids', 'orderby' => 'title', 'order' => 'ASC',
        'meta_key' => '_bfnd_work_product', 'meta_value' => 'yes',
    ));
    $products = array();
    foreach ($ids as $id) {
        $product = wc_get_product($id);
        if (bfnd_work_product_is_eligible($product) && !bfnd_work_product_is_assigned($id, $work_id)) { $products[] = $product; }
    }
    return $products;
}

function bfnd_get_work_product($work_id) {
    if (get_post_type($work_id) !== 'bf_work') { return false; }
    $product_id = absint(get_post_meta($work_id, '_bfnd_woo_id', true));
    if (!$product_id || !function_exists('wc_get_product')) { return false; }
    $product = wc_get_product($product_id);
    return bfnd_work_product_is_eligible($product) ? $product : false;
}

/** Human-readable variation name, e.g. 胡桃木 or 胡桃木／180 cm. */
function bfnd_work_variation_label($variation) {
    $values = array();
    foreach ($variation->get_variation_attributes() as $key => $value) {
        if ($value === '') { continue; }
        $taxonomy = substr($key, 10);
        $term = taxonomy_exists($taxonomy) ? get_term_by('slug', $value, $taxonomy) : false;
        $values[] = $term ? $term->name : $value;
    }
    return $values ? implode('／', $values) : $variation->get_name();
}

/** Purchasable wood choices for one work, in the product's variation order. */
function bfnd_work_purchase_choices($work_id) {
    $product = bfnd_get_work_product($work_id);
    if (!$product || !$product->is_purchasable()) { return array(); }
    $choices = array();
    foreach ($product->get_children() as $variation_id) {
        $variation = wc_get_product($variation_id);
        if (!$variation || !$variation->exists() || $variation->get_status() !== 'publish') { continue; }
        if (!$variation->is_purchasable() || $variation->get_price() === '') { continue; }
        $choices[] = array(
            'id' => $variation->get_id(),
            'label' => bfnd_work_variation_label($variation),
            'price_html' => $variation->get_price_html(),
            'in_stock' => $variation->is_in_stock(),
        );
    }
    // A fully sold-out product falls back to the inquiry flow.
    foreach ($choices as $choice) { if ($choice['in_stock']) { return $choices; } }
    return array();
}

function bfnd_work_purchase_form_id($work_id) {
    return 'bf-work-buy-' . absint($work_id);
}

/** Radio list for the spec table. Returns false when the work is not for sale. */
function bfnd_render_work_purchase_choices($work_id) {
    $choices = bfnd_work_purchase_choices($work_id);
    if (!$choices) { return false; }
    $form_id = bfnd_work_purchase_form_id($work_id);
    $feedback_id = $form_id . '-feedback';
    echo '<fieldset class="bf-work-choice-group bf-work-buy-choices" data-bf-work-buy-choices aria-describedby="' . esc_attr($feedback_id) . '">';
    echo '<legend class="bf-visually-hidden">木材／材質，請選擇一項</legend><div class="bf-work-choice-list">';
    foreach ($choices as $index => $choice) {
        $input_id = $form_id . '-' . ($index + 1);
        $disabled = $choice['in_stock'] ? '' : ' disabled';
        echo '<label class="bf-work-choice' . ($choice['in_stock'] ? '' : ' is-sold-out') . '" for="' . esc_attr($input_id) . '">';
        echo '<input id="' . esc_attr($input_id) . '" type="radio" name="variation_id" form="' . esc_attr($form_id) . '" value="' . esc_attr($choice['id']) . '" required' . $disabled . '>';
        echo '<span>' . esc_html($choice['label']) . '<small class="bf-work-choice-price">' . wp_kses_post($choice['price_html']) . ($choice['in_stock'] ? '' : '・已售完') . '</small></span></label>';
    }
    echo '</div><p class="bf-work-choice-feedback" id="' . esc_attr($feedback_id) . '" aria-live="polite">選擇木種後，按下方「加入購物車」結帳。</p></fieldset>';
    return true;
}

/** Cart form placed in the call-to-action. Returns false when the work is not for sale. */
function bfnd_render_work_purchase_form($work_id) {
    $product = bfnd_get_work_product($work_id);
    if (!$product || !bfnd_work_purchase_choices($work_id)) { return false; }
    echo '<form class="bf-work-buy-form" id="' . esc_attr(bfnd_work_purchase_form_id($work_id)) . '" method="post" action="' . esc_url(wc_get_cart_url()) . '">';
    echo '<input type="hidden" name="add-to-cart" value="' . esc_attr($product->get_id()) . '"><input type="hidden" name="product_id" value="' . esc_attr($product->get_id()) . '"><input type="hidden" name="quantity" value="1">';
    echo '<button class="bf-button" type="submit"><span>加入購物車</span><span aria-hidden="true">→</span></button></form>';
    return true;
}

function bfnd_work_product_meta_box() {
    add_meta_box('bfnd_work_product', '家具作品購買商品', 'bfnd_work_product_meta_box_html', 'product', 'side', 'default');
}
add_action('add_meta_boxes_product', 'bfnd_work_product_meta_box');

function bfnd_work_product_meta_box_html($post) {
    wp_nonce_field('bfnd_save_work_product', 'bfnd_work_product_nonce');
    $enabled = get_post_meta($post->ID, '_bfnd_work_product', true) === 'yes';
    echo '<p><label><input type="checkbox" name="bfnd_work_product" value="yes" ' . checked($enabled, true, false) . '> 將此商品作為家具作品的購買項目</label></p>';
    echo '<p class="description">需為「變化商品」：以「木種」屬性建立各木材的變化，並分別填售價與庫存。勾選並儲存後，到家具作品編輯頁選擇此商品；每個商品只能綁一件作品。</p>';
}

function bfnd_save_work_product($product) {
    if (!isset($_POST['bfnd_work_product_nonce'])
        || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['bfnd_work_product_nonce'])), 'bfnd_save_work_product')
        || !current_user_can('edit_post', $product->get_id())) { return; }
    if (isset($_POST['bfnd_work_product']) && wp_unslash($_POST['bfnd_work_product']) === 'yes' && $product->is_type('variable')) {
        update_post_meta($product->get_id(), '_bfnd_work_product', 'yes');
    } else {
        delete_post_meta($product->get_id(), '_bfnd_work_product');
    }
}
add_action('woocommerce_admin_process_product_object', 'bfnd_save_work_product');

function bfnd_work_order_line_item($item, $cart_item_key, $values, $order) {
    $product_id = !empty($values['product_id']) ? absint($values['product_id']) : 0;
    if (!$product_id || get_post_meta($product_id, '_bfnd_work_product', true) !== 'yes') { return; }
    $works = get_posts(array(
        'post_type' => 'bf_work', 'post_status' => array('publish', 'private'), 'numberposts' => 1,
        'meta_key' => '_bfnd_woo_id', 'meta_value' => $product_id,
    ));
    if ($works) {
        $item->add_meta_data('家具作品', get_the_title($works[0]->ID), true);
        $item->add_meta_data('_bfnd_work_id', (int) $works[0]->ID, true);
    }
}
add_action('woocommerce_checkout_create_order_line_item', 'bfnd_work_order_line_item', 10, 4);
