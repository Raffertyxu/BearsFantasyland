<?php
if (!defined('ABSPATH')) { exit; }
$page_type = is_page() ? get_post_meta(get_queried_object_id(), '_bfnd_page', true) : '';
$page_type = $page_type ?: ((function_exists('is_shop') && (is_shop() || (function_exists('is_product') && is_product()))) ? 'furniture' : '');
$title = is_singular() ? get_the_title() : (bfnd_pages()[$page_type][0] ?? '飛熊入夢');
if (bfnd_is_commerce_page()) {
    $title = is_cart() ? '購物車' : (is_checkout() ? '結帳' : (is_account_page() ? '會員中心' : $title));
}
$seo_title = is_singular() ? get_post_meta(get_queried_object_id(), '_bfnd_seo_title', true) : '';
$seo_desc = is_singular() ? get_post_meta(get_queried_object_id(), '_bfnd_seo_description', true) : '';
if (!$seo_title) { $seo_title = $title . '｜飛熊入夢 Bear’s Fantasyland'; }
if (!$seo_desc) { $seo_desc = '飛熊入夢從台灣傳統木工出發，透過實木家具設計、木工教育與數位製造，讓木成為生活的一部分。'; }
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo esc_html($seo_title); ?></title>
<meta name="description" content="<?php echo esc_attr($seo_desc); ?>">
<?php if (is_page() && get_post_status() === 'private') : ?><meta name="robots" content="noindex,nofollow"><?php endif; ?>
<?php wp_head(); ?>
</head>
<body <?php body_class('bfnd-body'); ?>>
<?php wp_body_open(); ?>
<a class="bf-skip" href="#bf-main">跳至主要內容</a>
<?php bfnd_render_header($page_type); ?>
<main id="bf-main">
<?php
if (bfnd_is_commerce_page()) { bfnd_render_commerce(); }
elseif (bfnd_is_journal_article()) { bfnd_render_journal_article(); }
elseif (is_404()) { bfnd_render_not_found(); }
elseif (is_singular('bf_work')) { bfnd_render_work(get_queried_object_id()); }
elseif (is_singular('bf_lifestyle')) { bfnd_render_lifestyle_work(get_queried_object_id()); }
elseif (is_singular('bf_course')) { bfnd_render_course(get_queried_object_id()); }
else {
    $extra = get_post_field('post_content', get_queried_object_id());
    if ($extra && has_shortcode($extra, 'bfnd_page')) { echo apply_filters('the_content', $extra); }
    else {
        if (function_exists('bfnd_page_layout_html')) { echo bfnd_page_layout_html($page_type, get_queried_object_id()); }
        if (trim($extra)) { echo '<section class="bf-page-extra bf-wrap">' . apply_filters('the_content', $extra) . '</section>'; }
    }
}
?>
</main>
<?php bfnd_render_footer(); wp_footer(); ?>
</body></html>
