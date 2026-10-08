<?php
if (!defined('ABSPATH')) { exit; }

/** Return the configured, public choices for a furniture work or a lifestyle work (lifestyle: 款式 via material only, never size). */
function bfnd_work_option_values($work_id, $kind) {
    if (!in_array($kind, array('material', 'size'), true)) { return array(); }
    $post_type = function_exists('get_post_type') ? get_post_type($work_id) : 'bf_work';
    if (!in_array($post_type, array('bf_work', 'bf_lifestyle'), true)) { return array(); }
    if ($kind === 'size' && $post_type === 'bf_lifestyle') { return array(); }
    if ($kind === 'size' && get_post_meta($work_id, '_bfnd_size_confirmed', true) !== '1') { return array(); }

    $raw = get_post_meta($work_id, '_bfnd_' . $kind . '_options', true);
    if (is_array($raw)) { $raw = implode("\n", $raw); }
    $normalized = bfnd_normalize_work_option_text($raw);
    return $normalized === '' ? array() : explode("\n", $normalized);
}

/** Keep the admin list and public values in the same normalized format. */
function bfnd_normalize_work_option_text($raw) {
    if (is_array($raw)) { $raw = implode("\n", $raw); }
    $lines = preg_split('/\r\n|\r|\n/', (string) $raw);
    $options = array();
    foreach ($lines as $line) {
        $line = trim((string) $line);
        $line = function_exists('sanitize_text_field') ? sanitize_text_field($line) : trim(strip_tags($line));
        if ($line === '') { continue; }
        if (function_exists('mb_strlen') && mb_strlen($line) > 120) { $line = mb_substr($line, 0, 120); }
        elseif (strlen($line) > 360) { $line = substr($line, 0, 360); }
        if (!in_array($line, $options, true)) { $options[] = $line; }
        if (count($options) >= 40) { break; }
    }
    return implode("\n", $options);
}

function bfnd_requested_work_option($work_id, $kind) {
    $param = $kind === 'material' ? 'material_choice' : 'size_choice';
    if (!isset($_GET[$param]) || !is_scalar($_GET[$param])) { return ''; }
    $raw = function_exists('wp_unslash') ? wp_unslash($_GET[$param]) : $_GET[$param];
    $value = function_exists('sanitize_text_field')
        ? sanitize_text_field($raw)
        : trim(strip_tags((string) $raw));
    return in_array($value, bfnd_work_option_values($work_id, $kind), true) ? $value : '';
}

function bfnd_work_option_json($work_id, $kind) {
    $options = bfnd_work_option_values($work_id, $kind);
    $json = function_exists('wp_json_encode') ? wp_json_encode($options) : json_encode($options);
    return is_string($json) ? $json : '[]';
}

function bfnd_render_work_choice_group($work_id, $kind, $label) {
    $options = bfnd_work_option_values($work_id, $kind);
    if (!$options) { return false; }

    $group_id = 'bf-work-' . absint($work_id) . '-' . $kind;
    $field_name = 'bfnd_work_' . $kind . '_choice';
    $feedback_id = $group_id . '-feedback';
    echo '<fieldset class="bf-work-choice-group" data-bf-work-choice-group="' . esc_attr($kind) . '" aria-describedby="' . esc_attr($feedback_id) . '">';
    echo '<legend class="bf-visually-hidden">' . esc_html($label) . '，請選擇一項</legend><div class="bf-work-choice-list">';
    foreach ($options as $index => $option) {
        $input_id = $group_id . '-' . ($index + 1);
        echo '<label class="bf-work-choice" for="' . esc_attr($input_id) . '"><input id="' . esc_attr($input_id) . '" type="radio" name="' . esc_attr($field_name) . '" value="' . esc_attr($option) . '" required><span>' . esc_html($option) . '</span></label>';
    }
    echo '</div><p class="bf-work-choice-feedback" id="' . esc_attr($feedback_id) . '" data-bf-work-choice-feedback aria-live="polite">選擇後會一併帶入作品詢價。</p></fieldset>';
    return true;
}

function bfnd_render_inquiry_option_field($work_id, $kind, $label, $selected_value = '') {
    $options = bfnd_work_option_values($work_id, $kind);
    $field_id = 'bf-inquiry-' . $kind . '-choice';
    $field_name = $kind === 'material' ? 'material_choice' : 'size_choice';
    $hidden = $options ? '' : ' hidden';
    $disabled = $options ? '' : ' disabled';
    $selected_value = in_array($selected_value, $options, true) ? $selected_value : '';

    echo '<label class="bf-inquiry-option-field" data-bf-inquiry-option-field="' . esc_attr($kind) . '"' . $hidden . '><span>' . esc_html($label) . '</span>';
    echo '<select id="' . esc_attr($field_id) . '" name="' . esc_attr($field_name) . '" data-bf-inquiry-option-select="' . esc_attr($kind) . '" data-selected="' . esc_attr($selected_value) . '" aria-describedby="' . esc_attr($field_id . '-feedback') . '"' . ($options ? ' required' : $disabled) . '>';
    echo '<option value="">請選擇' . esc_html(preg_replace('/^選擇/u', '', $label)) . '</option>';
    foreach ($options as $option) {
        echo '<option value="' . esc_attr($option) . '"' . ($option === $selected_value ? ' selected' : '') . '>' . esc_html($option) . '</option>';
    }
    echo '</select><small id="' . esc_attr($field_id . '-feedback') . '" data-bf-inquiry-option-feedback aria-live="polite">請選擇此作品已設定的選項。</small></label>';
}

/** Published, purchasable, non-course shop product linked to a work, or false. */
function bfnd_get_work_shop_product($work_id) {
    if (!function_exists('wc_get_product')) { return false; }
    if (function_exists('get_post_type') && !in_array(get_post_type($work_id), array('bf_work', 'bf_lifestyle'), true)) { return false; }
    $product_id = absint(get_post_meta($work_id, '_bfnd_shop_product_id', true));
    $product = $product_id ? wc_get_product($product_id) : false;
    if (!$product || $product->get_status() !== 'publish' || !$product->is_purchasable()) { return false; }
    if (get_post_meta($product_id, '_bfnd_course_product', true) === 'yes') { return false; }
    return $product;
}

function bfnd_work_shop_product_field($current_id, $label) {
    echo '<p><label for="bfnd_shop_product_id"><strong>' . esc_html($label) . '</strong></label><br>';
    if (!function_exists('wc_get_product')) {
        echo '<select id="bfnd_shop_product_id" disabled style="width:100%;max-width:780px"><option>請先啟用 WooCommerce</option></select>';
        echo '<input type="hidden" name="bfnd[shop_product_id]" value="' . esc_attr($current_id ?: '') . '"></p>';
        return;
    }
    $ids = get_posts(array(
        'post_type' => 'product', 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids', 'orderby' => 'title', 'order' => 'ASC',
        'meta_query' => array(array('key' => '_bfnd_course_product', 'compare' => 'NOT EXISTS')),
    ));
    echo '<select id="bfnd_shop_product_id" name="bfnd[shop_product_id]" style="width:100%;max-width:780px"><option value="">不連結商品（只顯示詢問）</option>';
    if ($current_id && !in_array($current_id, array_map('intval', $ids), true)) {
        $current = wc_get_product($current_id);
        echo '<option value="' . esc_attr($current_id) . '" selected>目前連結：' . esc_html($current ? $current->get_name() : '已不存在的商品') . '（未發布或不可購買，前台不會顯示按鈕）</option>';
    }
    foreach ($ids as $id) {
        $product = wc_get_product($id);
        if (!$product) { continue; }
        $price = wp_strip_all_tags($product->get_price_html());
        echo '<option value="' . esc_attr($id) . '" ' . selected($current_id, $id, false) . '>' . esc_html($product->get_name() . ($price !== '' ? '｜' . $price : '')) . '</option>';
    }
    echo '</select><br><span class="description">作品頁會在「詢問此作品」旁顯示「前往選購」，連到這個商品頁。商品需已發布且可購買才會顯示。</span></p>';
}
