<?php
if (!defined('ABSPATH')) { exit; }

function bfnd_e($text) { return esc_html((string) $text); }
function bfnd_meta($id, $name) { return get_post_meta($id, '_bfnd_' . $name, true); }
function bfnd_work_by_seed($slug) {
    $statuses = empty($GLOBALS['bfnd_public_content_render']) ? array('publish', 'private') : array('publish');
    $items = get_posts(array('post_type' => 'bf_work', 'post_status' => $statuses, 'meta_key' => '_bfnd_seed', 'meta_value' => $slug, 'numberposts' => 1));
    return $items ? $items[0] : null;
}
function bfnd_image($url, $alt, $class = '', $lazy = true) {
    if (!$url || stripos((string) $url, 'editorial') !== false || stripos((string) $url, 'wooden-cup') !== false || strpos((string) $alt, "\xe7\xa4\xba\xe6\x84\x8f") !== false) { return; }
    echo '<img class="' . esc_attr($class) . '" src="' . esc_url($url) . '" alt="' . esc_attr($alt) . '" ' . ($lazy ? 'loading="lazy"' : 'fetchpriority="high"') . ' decoding="async">';
}
function bfnd_selected_photo($option, $size = 'large') {
    $id = (int) get_option($option);
    if (!$id) { return ''; }
    $source = (string) get_post_meta($id, '_bfnd_source', true);
    $url = wp_get_attachment_image_url($id, $size) ?: '';
    return bfnd_nonfinal_photo($url, $id) ? '' : $url;
}
function bfnd_brand_photo($option, $size, $fallback) {
    return bfnd_selected_photo($option, $size) ?: bfnd_asset($fallback);
}
function bfnd_render_banner_images($key, $legacy_option, $fallback) {
    $ids = get_option('bfnd_banner_slides_' . $key, array());
    if (!is_array($ids) || !$ids) { $ids = array(absint(get_option($legacy_option))); }
    $slides = array();
    foreach (array_unique(array_map('absint', $ids)) as $id) {
        if (!$id || !wp_attachment_is_image($id)) { continue; }
        $url = wp_get_attachment_image_url($id, 'full');
        $image_alt = (string) get_post_meta($id, '_wp_attachment_image_alt', true);
        if (!$url || bfnd_nonfinal_photo($url, $id, $image_alt)) { continue; }
        $slides[] = $url;
    }
    if (!$slides) { $slides[] = bfnd_brand_photo($legacy_option, 'full', $fallback); }
    foreach ($slides as $index => $url) {
        echo '<div class="bfnd-banner-slide' . ($index === 0 ? ' is-active' : '') . '" data-bfnd-banner-slide aria-hidden="' . ($index === 0 ? 'false' : 'true') . '">';
        bfnd_image($url, '', '', $index !== 0);
        echo '</div>';
    }
    return count($slides);
}
function bfnd_render_banner_controls($count, $label) {
    if ($count < 2) { return; }
    echo '<div class="bfnd-banner-controls" data-bfnd-banner-controls data-bfnd-page-design-skip="1" aria-label="' . esc_attr($label . '圖片輪播控制') . '" hidden><button type="button" data-bfnd-banner-prev aria-label="上一張圖片">←</button><span class="bfnd-banner-count" aria-live="off"><span data-bfnd-banner-current>01</span><span aria-hidden="true"> / ' . esc_html(sprintf('%02d', $count)) . '</span></span><button type="button" data-bfnd-banner-next aria-label="下一張圖片">→</button><button type="button" data-bfnd-banner-pause aria-label="暫停輪播">Ⅱ</button></div>';
}
function bfnd_button($label, $url, $light = false) {
    echo '<a class="bf-button' . ($light ? ' bf-button-light' : '') . '" href="' . esc_url($url) . '"><span>' . bfnd_e($label) . '</span><span aria-hidden="true">↗</span></a>';
}
function bfnd_render_header($active) {
    $nav = array('home' => '首頁', 'furniture' => '家具', 'lifestyle' => '生活木作', 'school' => '木作學堂', 'story' => '品牌故事', 'collaboration' => '合作提案');
    $account = function_exists('wc_get_page_id') && wc_get_page_id('myaccount') > 0 ? get_permalink(wc_get_page_id('myaccount')) : bfnd_page_url('service');
    $cart = function_exists('wc_get_cart_url') ? wc_get_cart_url() : bfnd_page_url('collaboration');
    echo '<header class="bf-header bf-new-header"><div class="bf-header-inner"><a class="bf-logo" href="' . esc_url(bfnd_page_url('home')) . '" aria-label="飛熊入夢首頁">';
    echo '<img src="' . esc_url(bfnd_asset('public/logo-transparent.png')) . '" alt="飛熊入夢 Bear’s Fantasyland"></a>';
    echo '<button class="bf-menu-button" type="button" aria-expanded="false" aria-controls="bf-nav">選單 <span aria-hidden="true">☰</span></button>';
    echo '<nav id="bf-nav" class="bf-nav" aria-label="主要導覽">';
    foreach ($nav as $key => $label) {
        if ($key === 'school') {
            $school = bfnd_page_url('school');
            echo '<div class="bf-nav-school"><a' . ($key === $active ? ' aria-current="page"' : '') . ' href="' . esc_url($school) . '">' . bfnd_e($label) . '</a><button class="bf-nav-school-toggle" type="button" aria-label="展開木作學堂課程選單" aria-expanded="false" aria-controls="bf-school-menu">⌄</button><div id="bf-school-menu" class="bf-nav-course-menu"><div><strong>實體課程</strong><small>到工坊上課・實作體驗</small><a href="' . esc_url($school . '#onsite-courses') . '">木工基礎入門班</a><a href="' . esc_url($school . '#onsite-courses') . '">自由創作會員</a><a href="' . esc_url($school . '#onsite-courses') . '">CNC 數位木工</a><a href="' . esc_url($school . '#onsite-courses') . '">磨刀實戰班</a></div><div><strong>線上課程</strong><small>隨時隨地・在家學木作</small><a href="' . esc_url($school . '#online-courses') . '">CNC / VCarve</a><a href="' . esc_url($school . '#online-courses') . '">磨刀技術</a><a href="' . esc_url($school . '#online-courses') . '">更多課程籌備中</a></div><a class="bf-nav-course-all" href="' . esc_url($school . '#onsite-courses') . '">查看所有課程 ↗</a></div></div>';
        } else { echo '<a' . ($key === $active ? ' aria-current="page"' : '') . ' href="' . esc_url(bfnd_page_url($key)) . '">' . bfnd_e($label) . '</a>'; }
    }
    echo '<div class="bf-mobile-secondary"><a href="' . esc_url(bfnd_shop_url()) . '">商品選購</a><a href="' . esc_url(bfnd_page_url('journal')) . '">飛熊日誌</a><a href="' . esc_url(bfnd_page_url('service')) . '">購買與服務</a><a href="' . esc_url($account) . '">會員中心</a><a href="' . esc_url($cart) . '">購物車</a></div>';
    echo '</nav><div class="bf-header-tools"><a href="' . esc_url(bfnd_page_url('furniture') . '#bf-search') . '" aria-label="搜尋家具作品"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.5" cy="10.5" r="6.5"/><path d="m15.5 15.5 5 5"/></svg></a>';
    echo '<a href="' . esc_url($account) . '" aria-label="會員中心"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="7.5" r="3.5"/><path d="M4.5 21c0-4.5 2.7-7 7.5-7s7.5 2.5 7.5 7"/></svg></a><a href="' . esc_url($cart) . '" aria-label="購物車"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 8h14l1 13H4L5 8Z"/><path d="M9 9V6a3 3 0 0 1 6 0v3"/></svg></a></div></div></header>';
}
function bfnd_render_footer() {
    echo '<footer class="bf-footer"><div class="bf-footer-main bf-wrap"><a class="bf-footer-brand" href="' . esc_url(bfnd_page_url('home')) . '"><img src="' . esc_url(bfnd_asset('public/logo-transparent.png')) . '" alt="飛熊入夢 Bear’s Fantasyland"></a>';
    echo '<p class="bf-footer-motto">木，讓生活更美好。</p><nav class="bf-footer-links" aria-label="頁尾導覽"><a href="' . esc_url(bfnd_page_url('furniture')) . '">家具</a><a href="' . esc_url(bfnd_page_url('lifestyle')) . '">生活木作</a><a href="' . esc_url(bfnd_page_url('school')) . '">木作學堂</a><a href="' . esc_url(bfnd_page_url('story')) . '">品牌故事</a><a href="' . esc_url(bfnd_page_url('collaboration')) . '">合作提案</a><a href="' . esc_url(bfnd_page_url('journal')) . '">飛熊日誌</a><a href="' . esc_url(bfnd_page_url('service')) . '">購買與服務</a></nav>';
    echo '<div class="bf-footer-social" role="group" aria-label="社群平台">';
    echo '<a href="https://www.instagram.com/bearloveearth/" target="_blank" rel="noopener noreferrer" aria-label="Instagram（另開新視窗）"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3.5" y="3.5" width="17" height="17" rx="4.5"></rect><circle cx="12" cy="12" r="4"></circle><circle cx="17.7" cy="6.6" r=".8" fill="currentColor" stroke="none"></circle></svg></a>';
    echo '<a href="https://www.facebook.com/iaz2765b" target="_blank" rel="noopener noreferrer" aria-label="Facebook（另開新視窗）"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14.2 21v-8h2.7l.4-3h-3.1V8.1c0-.9.3-1.5 1.6-1.5H17V3.9c-.3 0-1.4-.1-2.6-.1-2.6 0-4.4 1.6-4.4 4.5V10H7v3h3v8z" fill="currentColor" stroke="none"></path></svg></a>';
    echo '<a href="https://www.youtube.com/@%E9%A3%9B%E7%86%8A%E5%85%A5%E5%A4%A2" target="_blank" rel="noopener noreferrer" aria-label="YouTube（另開新視窗）"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 7.2a2.7 2.7 0 0 0-1.9-1.9C17.4 4.8 12 4.8 12 4.8s-5.4 0-7.1.5A2.7 2.7 0 0 0 3 7.2 28 28 0 0 0 2.5 12a28 28 0 0 0 .5 4.8 2.7 2.7 0 0 0 1.9 1.9c1.7.5 7.1.5 7.1.5s5.4 0 7.1-.5a2.7 2.7 0 0 0 1.9-1.9 28 28 0 0 0 .5-4.8 28 28 0 0 0-.5-4.8Z"></path><path d="m10 15.5 5-3.5-5-3.5z" fill="currentColor" stroke="none"></path></svg></a>';
    echo '<a class="bf-footer-line" href="https://line.me/R/ti/p/%40iaz2765b" target="_blank" rel="noopener noreferrer" aria-label="加入飛熊入夢官方 LINE（另開新視窗）"><img src="' . esc_url(bfnd_asset('public/line-brand-icon.png')) . '" alt="" aria-hidden="true"><span>加入官方 LINE</span><span class="bf-social-arrow" aria-hidden="true">↗</span></a></div><a class="bf-footer-maker" href="' . esc_url(bfnd_page_url('story')) . '"><strong>台中 Maker 工藝基地</strong><small>木作設計・木工教育・實木家具</small></a></div>';
    echo '<div class="bf-footer-bottom"><div class="bf-wrap"><small>© ' . esc_html(date_i18n('Y')) . ' 飛熊入夢 Bear’s Fantasyland. All rights reserved.</small><nav aria-label="服務資訊"><a href="' . esc_url(bfnd_shop_url()) . '">商品選購</a><a href="' . esc_url(function_exists('wc_get_cart_url') ? wc_get_cart_url() : bfnd_page_url('furniture')) . '">購物車</a><a href="' . esc_url(function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : bfnd_page_url('service')) . '">會員中心</a><a href="' . esc_url(bfnd_page_url('service')) . '#purchase">作品購買／詢問</a><a href="' . esc_url(bfnd_page_url('service')) . '#shipping-install">運送與安裝</a><a href="' . esc_url(bfnd_page_url('service')) . '#warranty">作品保固</a><a href="' . esc_url(bfnd_page_url('service')) . '#care">保養與修繕</a><a href="' . esc_url(bfnd_page_url('service')) . '#faq">常見問題</a><a href="' . esc_url(bfnd_page_url('collaboration')) . '#inquiry">聯絡我們</a></nav></div></div></footer>';
}
function bfnd_section_head($en, $title, $link = '', $link_label = '') {
    echo '<div class="bf-section-head"><div><span class="bf-kicker">' . bfnd_e($en) . '</span><h2>' . bfnd_e($title) . '</h2></div>';
    if ($link) { echo '<a class="bf-text-link" href="' . esc_url($link) . '">' . bfnd_e($link_label) . ' <span aria-hidden="true">↗</span></a>'; }
    echo '</div>';
}
function bfnd_work_card($post) {
    $id = $post->ID;
    $series = wp_get_post_terms($id, 'bf_series', array('fields' => 'names'));
    echo '<a class="bf-work-card" href="' . esc_url(get_permalink($id)) . '"><div class="bf-work-image">';
    bfnd_image(bfnd_work_image($id, 'large'), get_the_title($id) . '・' . bfnd_meta($id, 'type') . '・' . bfnd_public_material($id));
    echo '</div><div class="bf-card-line"><h3>' . bfnd_e(get_the_title($id)) . '</h3><span aria-hidden="true">↗</span></div><p>' . bfnd_e(bfnd_meta($id, 'english')) . '</p><small>' . bfnd_e($series ? $series[0] : '') . ' / ' . bfnd_e(bfnd_meta($id, 'type')) . '</small></a>';
}
function bfnd_public_material($id) {
    $material = trim((string) bfnd_meta($id, 'material'));
    $material = preg_replace('/[（(][^）)]*待確認[^）)]*[）)]/u', '', $material);
    $material = trim(str_replace('待確認', '', $material), " \t\n\r\0\x0B，,、／/｜|");
    return $material !== '' ? $material : '木材資訊請洽作品諮詢';
}
function bfnd_public_size($id) {
    $size = trim((string) bfnd_meta($id, 'size'));
    if ($size === '') { return ''; }
    if (bfnd_meta($id, 'size_confirmed') !== '1') { return '尺寸請洽作品諮詢'; }
    return '參考尺寸｜' . $size . '，手工製作，實際尺寸依作品為準。';
}
function bfnd_render_home() {
    $hero = bfnd_work_by_seed('muyo');
    echo '<section class="bf-home-hero" data-bfnd-carousel data-bfnd-interval="6000"><div class="bf-home-hero-image" data-bfnd-reserved-image="1">';
    $hero_slides = bfnd_render_banner_images('home', 'bfnd_home_hero_id', 'assets/works/muyo/01.webp');
    echo '</div><div class="bf-home-hero-shade"></div>';
    bfnd_render_banner_controls($hero_slides, '首頁');
    echo '<div class="bf-home-hero-content bf-wrap"><span class="bf-kicker">BEAR’S FANTASYLAND / TAIWAN</span><h1>讓木，<br>成為生活的<br class="bf-mobile-break">一部分。</h1><p>從一件家具，到一堂木工課。<br>我們用傳統工藝 × 數位製造，<br>延續台灣木作的下一個世代。</p><div class="bf-actions">';
    bfnd_button('探索家具作品', bfnd_page_url('furniture'), true); bfnd_button('開始學木工', bfnd_page_url('school'), true);
    echo '</div></div><span class="bf-vertical-note">GOOD WOOD<br>BETTER LIVING.</span><span class="bf-hero-disclosure" data-bfnd-reserved-text="1" hidden aria-hidden="true"></span></section>';
    echo '<div class="bf-values bf-home-values bf-wrap"><div><b aria-hidden="true">♧</b><span>嚴選木材</span><small>來自永續森林・天然・安心</small></div><div><b aria-hidden="true">⚙</b><span>工藝 × 數位製造</span><small>傳統工藝結合 CNC 技術</small></div><div><b aria-hidden="true">⌂</b><span>在地製造</span><small>台中工坊・品質把關</small></div><div><b aria-hidden="true">∞</b><span>耐久設計</span><small>陪伴生活・延長使用年限</small></div><div><b aria-hidden="true">♧</b><span>永續理念</span><small>友善環境・珍惜資源</small></div></div>';
    echo '<section class="bf-split-promos"><a href="' . esc_url(bfnd_page_url('furniture')) . '"><div class="bf-promo-image">';
    bfnd_image(bfnd_asset('assets/works/ridge-table/01.webp'), '稜 RIDGE 實木餐桌');
    echo '</div><div class="bf-promo-copy"><span class="bf-kicker">FURNITURE</span><h2>讓作品，進入生活。</h2><span class="bf-plain-link">探索家具系列 ↗</span></div></a><a href="' . esc_url(bfnd_page_url('school')) . '"><div class="bf-promo-image">';
    $school_promo = bfnd_brand_photo('bfnd_school_hero_id', 'large', 'assets/brand/real-lecture.webp');
    bfnd_image($school_promo, '木作學堂木工工具與實木材料');
    echo '</div><div class="bf-promo-copy"><span class="bf-kicker">WOODWORKING SCHOOL</span><h2>從雙手，開始認識木。</h2><span class="bf-plain-link">了解木作學堂 ↗</span></div></a></section>';
    echo '<section class="bf-section bf-wrap">'; bfnd_section_head('SELECTED WORKS', '作品系列精選', bfnd_page_url('furniture'), '探索更多作品');
    $q = bfnd_work_query(array('posts_per_page' => 3, 'orderby' => 'menu_order', 'order' => 'ASC', 'meta_key' => '_bfnd_featured', 'meta_value' => '1'));
    if (!$q->have_posts()) { $q = bfnd_work_query(array('posts_per_page' => 3, 'orderby' => 'menu_order', 'order' => 'ASC')); }
    echo '<div class="bf-work-grid bf-home-work-grid">'; foreach ($q->posts as $post) { bfnd_work_card($post); }
    $life = get_posts(array('post_type' => 'bf_lifestyle', 'post_status' => 'publish', 'numberposts' => 1));
    if ($life) { bfnd_work_card($life[0]); } echo '</div></section>';
    echo '<section class="bf-home-collab"><div class="bf-wrap bf-home-collab-inner"><div class="bf-home-collab-intro"><h2>合作提案</h2><span class="bf-kicker">COLLABORATION</span><i aria-hidden="true"></i><p>與木一起，<br>創造更好的空間。</p>'; bfnd_button('了解合作提案', bfnd_page_url('collaboration')); echo '</div><div class="bf-home-collab-cards">';
    $cards = array(
        array('企業／空間合作', '辦公・商業・公共空間', 'assets/works/cherry-island-table/01.webp'),
        array('教育／文化合作', '木工教育・工藝體驗', 'assets/brand/real-lecture.webp'),
        array('永續合作', '材料・耐久設計', 'assets/works/muyo/03.webp'),
        array('家具採購', '既有家具系列・專案合作', 'assets/works/muyo/01.webp'),
    );
    foreach ($cards as $card) { echo '<a class="bf-home-collab-card" href="' . esc_url(bfnd_page_url('collaboration')) . '"><div>'; $option = $card[2] === 'assets/brand/real-lecture.webp' ? 'bfnd_real_lecture_id' : ''; $card_image = $option ? (bfnd_selected_photo($option, 'large') ?: bfnd_asset($card[2])) : bfnd_asset($card[2]); bfnd_image($card_image, $card[0]); echo '</div><strong>' . bfnd_e($card[0]) . '</strong><small>' . bfnd_e($card[1]) . '</small></a>'; }
    echo '</div></div></section>';
    $manifesto = bfnd_brand_photo('bfnd_manifesto_id', 'full', 'assets/works/muyo/02.webp');
    echo '<section class="bf-manifesto" style="background-image:url(' . esc_url($manifesto) . ')"><div class="bf-wrap bf-manifesto-inner"><div><h2>木・人・空間・未來</h2><p>從土地出發，為下一個世代設計更好的生活。</p></div><span>A Better<br>Tomorrow.</span>'; bfnd_button('認識飛熊入夢', bfnd_page_url('story'), true); echo '</div></section>';
    bfnd_render_journal_teaser();
}
function bfnd_render_furniture() {
    echo '<section class="bf-page-hero bf-page-hero-photo" data-bfnd-carousel data-bfnd-interval="6000"><div class="bf-page-hero-image" data-bfnd-reserved-image="1">'; $hero_slides = bfnd_render_banner_images('furniture', 'bfnd_home_hero_id', 'assets/works/muyo/01.webp'); echo '</div><div class="bf-page-hero-overlay"></div>'; bfnd_render_banner_controls($hero_slides, '家具作品'); echo '<div class="bf-wrap bf-page-hero-copy"><span class="bf-kicker">FURNITURE / OUR WORKS</span><h1>家具</h1><p>從木材、結構到生活，<br>每件作品都有自己的故事。</p></div></section>';
    echo '<section class="bf-section bf-wrap"><div class="bf-catalog-toolbar"><div class="bf-filter-list" role="group" aria-label="家具分類"><button class="is-active" data-filter="all" type="button">全部</button>';
    $terms = get_terms(array('taxonomy' => 'bf_work_cat', 'hide_empty' => false));
    if (!is_wp_error($terms)) {
        $order = array('tables' => 1, 'seating' => 2, 'storage' => 3, 'islands' => 4);
        usort($terms, function ($a, $b) use ($order) { return ($order[$a->slug] ?? 100) <=> ($order[$b->slug] ?? 100) ?: strcmp($a->name, $b->name); });
        foreach ($terms as $term) { echo '<button data-filter="' . esc_attr($term->slug) . '" type="button">' . bfnd_e($term->name) . '</button>'; }
    }
    echo '<button data-filter="other" type="button">其他</button></div><div class="bf-catalog-controls"><label><span class="bf-visually-hidden">搜尋作品</span><input id="bf-search" type="search" placeholder="搜尋作品、系列或木材"></label><label><span class="bf-visually-hidden">排序</span><select id="bf-sort"><option value="recent">最新作品</option><option value="series">系列</option><option value="material">材質</option></select></label></div></div>';
    $series_terms = get_terms(array('taxonomy' => 'bf_series', 'hide_empty' => true));
    if (!is_wp_error($series_terms) && $series_terms) {
        echo '<div class="bf-collection-filter"><span class="bf-kicker">COLLECTIONS / 依系列瀏覽</span><div class="bf-filter-list" role="group" aria-label="作品系列"><button class="is-active" data-series-filter="all" type="button">全部系列</button>';
        foreach ($series_terms as $term) { echo '<button data-series-filter="' . esc_attr($term->slug) . '" type="button">' . bfnd_e($term->name) . '</button>'; }
        echo '</div></div>';
    }
    echo '<p id="bf-result-count" class="bf-result-count" aria-live="polite"></p><div id="bf-catalog" class="bf-work-grid">';
    $q = bfnd_work_query(array('orderby' => 'menu_order', 'order' => 'ASC'));
    foreach ($q->posts as $post) {
        $series = wp_get_post_terms($post->ID, 'bf_series', array('fields' => 'names'));
        $cat = wp_get_post_terms($post->ID, 'bf_work_cat', array('fields' => 'slugs'));
        $series_slugs = wp_get_post_terms($post->ID, 'bf_series', array('fields' => 'slugs'));
        $material = bfnd_public_material($post->ID);
        $search = implode(' ', array(get_the_title($post), bfnd_meta($post->ID, 'english'), $series ? $series[0] : '', bfnd_meta($post->ID, 'type'), $material));
        echo '<div class="bf-catalog-item" data-category="' . esc_attr($cat ? $cat[0] : 'other') . '" data-search="' . esc_attr(mb_strtolower($search)) . '" data-series="' . esc_attr($series ? $series[0] : '') . '" data-series-filter="' . esc_attr($series_slugs ? $series_slugs[0] : '') . '" data-material="' . esc_attr($material) . '" data-date="' . esc_attr(get_post_time('U', true, $post)) . '">'; bfnd_work_card($post); echo '</div>';
    }
    echo '</div><p id="bf-no-results" class="bf-empty" hidden>沒有符合條件的作品，試試其他關鍵字或分類。</p><div class="bf-catalog-more"><button id="bf-load-more" class="bf-catalog-more-button" type="button" hidden>載入更多作品 <span aria-hidden="true">↓</span></button></div></section>';
    bfnd_render_cta('喜歡這件作品？', '歡迎詢問作品尺寸、木種、製作方式與現有規格。', '作品諮詢', bfnd_page_url('collaboration') . '#inquiry');
}
function bfnd_render_cta($line1, $line2, $label, $url) {
    echo '<section class="bf-cta"><div class="bf-wrap"><span class="bf-kicker">BEAR’S FANTASYLAND</span><h2>' . bfnd_e($line1) . '<br>' . bfnd_e($line2) . '</h2>'; bfnd_button($label, $url); echo '</div></section>';
}

function bfnd_render_work($id) {
    $title = get_the_title($id);
    $image = bfnd_work_image($id, 'full');
    $tagline = bfnd_meta($id, 'tagline') ?: get_the_excerpt($id);
    echo '<section class="bf-detail-hero"><div class="bf-detail-hero-image">'; bfnd_image($image, $title . '・' . bfnd_meta($id, 'type'), '', false); echo '</div><div class="bf-detail-hero-copy bf-wrap"><a class="bf-back" href="' . esc_url(bfnd_page_url('furniture')) . '">← 返回家具作品</a><span class="bf-kicker">FURNITURE / ' . bfnd_e(bfnd_meta($id, 'english')) . '</span><h1>' . bfnd_e($title) . '</h1><p>' . bfnd_e($tagline) . '</p></div></section>';
    $gallery = bfnd_gallery($id);
    $story_image = isset($gallery[1]) ? $gallery[1] : '';
    if (trim(get_post_field('post_content', $id))) {
        echo '<section class="bf-story-grid bf-wrap' . (!$story_image ? ' bf-story-grid-text-only' : '') . '">';
        if ($story_image) { echo '<div class="bf-story-image">'; bfnd_image($story_image, $title . '的作品細節'); echo '</div>'; }
        echo '<div class="bf-story-copy"><span class="bf-index">01 / STORY</span><h2>作品故事</h2><p>' . nl2br(esc_html(get_post_field('post_content', $id))) . '</p></div></section>';
    }
    $series = wp_get_post_terms($id, 'bf_series', array('fields' => 'names'));
    $size = bfnd_public_size($id);
    $size_options = bfnd_work_option_values($id, 'size');
    $specs = array('系列' => $series ? $series[0] : '', '作品類型' => bfnd_meta($id, 'type'), '表面處理' => bfnd_meta($id, 'finish'), '設計製作' => '飛熊入夢 Bear’s Fantasyland');
    echo '<section class="bf-spec-section" data-bf-work-choice-detail data-work-id="' . esc_attr($id) . '"><div class="bf-wrap"><span class="bf-index">02 / SPECIFICATION</span><h2>作品規格</h2><div class="bf-spec-grid">';
    foreach ($specs as $label => $value) { if ($value) { echo '<div><dt>' . bfnd_e($label) . '</dt><dd>' . bfnd_e($value) . '</dd></div>'; } }
    echo '<div><dt>木材／材質</dt><dd>';
    if (!bfnd_render_work_choice_group($id, 'material', '木材／材質')) { echo bfnd_e(bfnd_public_material($id)); }
    echo '</dd></div>';
    if ($size_options) {
        echo '<div><dt>尺寸</dt><dd>';
        bfnd_render_work_choice_group($id, 'size', '尺寸');
        echo '</dd></div>';
    } elseif ($size) { echo '<div><dt>參考尺寸</dt><dd>' . bfnd_e($size) . '</dd></div>'; }
    echo '</div></div></section>';
    $detail_images = array_slice($gallery, 2, 6);
    echo '<section class="bf-section bf-wrap"><div class="bf-section-head"><div><span class="bf-index">03 / DETAILS</span><h2>工藝細節</h2></div></div>';
    if ($detail_images) {
        echo '<div class="bf-detail-gallery">';
        foreach ($detail_images as $src) { echo '<button class="bf-gallery-button" type="button" aria-label="放大作品照片">'; bfnd_image($src, $title . '・作品細節'); echo '</button>'; }
        echo '</div>';
    } else {
        echo '<p>歡迎透過作品諮詢了解製作方式與作品細節。</p>';
    }
    echo '</section>';
    $selected = array_values(array_filter(array_map('absint', (array) bfnd_meta($id, 'related_ids'))));
    $related = $selected ? bfnd_work_query(array('post__in' => $selected, 'orderby' => 'post__in', 'posts_per_page' => 4)) : bfnd_work_query(array('post__not_in' => array($id), 'posts_per_page' => 4, 'orderby' => 'menu_order', 'order' => 'ASC'));
    if ($related->have_posts()) { echo '<section class="bf-section bf-wrap">'; bfnd_section_head('RELATED WORKS', '相關作品', bfnd_page_url('furniture'), '所有作品'); echo '<div class="bf-work-grid">'; foreach ($related->posts as $post) { bfnd_work_card($post); } echo '</div></section>'; }
    $craft = bfnd_meta($id, 'craft');
    $craft_image = wp_get_attachment_image_url((int) bfnd_meta($id, 'craft_image_id'), 'large');
    if ($craft && $craft_image) {
        echo '<section class="bf-craft-section"><div class="bf-wrap"><span class="bf-index">04 / CRAFT</span><h2>製作方式</h2><div class="bf-craft-layout"><div class="bf-craft-photo">'; bfnd_image($craft_image, $title . '的實際製作過程'); echo '</div><div class="bf-craft-content"><h3>以時間，完成一件作品</h3><p>從選材、加工到細節修整，每一道工序都讓材料與設計更貼近生活。</p><ol class="bf-craft-steps">';
        $steps = preg_split('/[、・,，]+/u', $craft);
        foreach ($steps as $i => $step) { $step = trim($step); if ($step) { echo '<li><span>' . sprintf('%02d', $i + 1) . '</span>' . bfnd_e($step) . '</li>'; } }
        echo '</ol></div></div></div></section>';
    }
    echo '<section class="bf-cta"><div class="bf-wrap"><span class="bf-kicker">WORKS INQUIRY</span><h2>喜歡這件作品？</h2><p>歡迎詢問作品尺寸、木種、製作方式與現有規格。</p>';
    if (bfnd_meta($id, 'size_adjustable') === '1') { echo '<p>部分作品可依空間需求調整尺寸，實際製作方式歡迎與我們討論。</p>'; }
    $inquiry_url = add_query_arg('work', $id, bfnd_page_url('collaboration')) . '#inquiry';
    echo '<a class="bf-button" data-bf-work-inquiry data-bf-work-id="' . esc_attr($id) . '" href="' . esc_url($inquiry_url) . '"><span>作品諮詢</span><span aria-hidden="true">↗</span></a></div></section>';
    echo '<dialog id="bf-image-dialog" class="bf-image-dialog" aria-label="作品照片放大檢視"><button type="button" aria-label="關閉照片">關閉 ×</button><img alt="作品照片放大檢視"></dialog>';
}

function bfnd_simple_hero($en, $title, $intro, $image = '') {
    echo '<section class="bf-simple-hero bf-wrap"><div><span class="bf-kicker">' . bfnd_e($en) . '</span><h1>' . bfnd_e($title) . '</h1><p>' . nl2br(bfnd_e($intro)) . '</p></div>';
    if ($image) { echo '<div class="bf-simple-hero-image">'; bfnd_image($image, $title, '', false); echo '</div>'; }
    echo '</section>';
}

function bfnd_lifestyle_icon($kind) {
    $paths = array(
        'table' => '<path d="M12 47c3 10 13 17 28 17s25-7 28-17H12Z"/><path d="M39 47 54 23c2-3 6-3 8-1s2 5 0 8L49 47"/><path d="M25 68h30"/>',
        'home' => '<path d="M23 54h34L50 26H30L23 54Z"/><path d="M40 54v16m-12 0h24"/>',
        'storage' => '<path d="m15 39 25-13 25 13v27L40 76 15 66V39Z"/><path d="m15 39 25 11 25-11M40 50v26"/>',
        'culture' => '<path d="M15 55c7-2 12-7 16-14l7-9 8 1 8 8 7 3 5 12-5 3-6-7-5 3-6 1-9-7-8 12H17l-2-6Z"/><path d="M25 61v9m31-13 2 13M37 32l-1-9 8 4m8 11 4-6m-14 10h1"/>',
    );
    return '<svg viewBox="0 0 80 80" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($paths[$kind] ?? '') . '</svg>';
}

function bfnd_render_commerce_category_section($category, $kicker, $title, $columns = 3) {
    if (!function_exists('wc_get_products')) { return false; }
    $term = is_object($category) ? $category : get_term_by('slug', (string) $category, 'product_cat');
    if (!$term || is_wp_error($term)) { return false; }
    $products = wc_get_products(array('status' => 'publish', 'limit' => 8, 'category' => array($term->slug), 'orderby' => 'menu_order', 'order' => 'ASC'));
    $ids = array();
    foreach ($products as $product) {
        if ($product->is_visible() && get_post_meta($product->get_id(), '_bfnd_course_product', true) !== 'yes') { $ids[] = $product->get_id(); }
    }
    if (!$ids) { return false; }
    echo '<section class="bf-section bf-wrap bf-inline-products" data-bfnd-page-design-skip="1"><div class="bf-tab-head">';
    $category_url = get_term_link($term);
    bfnd_section_head($kicker, $title, is_wp_error($category_url) ? '' : $category_url, '瀏覽分類');
    echo '</div><div class="bf-commerce-content bf-inline-products-grid">' . do_shortcode('[products ids="' . esc_attr(implode(',', $ids)) . '" columns="' . absint($columns) . '" limit="8"]') . '</div></section>';
    return true;
}

function bfnd_render_lifestyle() {
    $hero = bfnd_brand_photo('bfnd_lifestyle_hero_id', 'full', 'assets/lifestyle/tray-01.webp');
    echo '<section class="bf-lifestyle-hero" data-bfnd-carousel data-bfnd-interval="6000"><div class="bf-lifestyle-hero-image" data-bfnd-reserved-image="1">'; $hero_slides = bfnd_render_banner_images('lifestyle', 'bfnd_lifestyle_hero_id', 'assets/lifestyle/tray-01.webp');
    echo '</div><div class="bf-lifestyle-hero-shade"></div>'; bfnd_render_banner_controls($hero_slides, '生活木作'); echo '<div class="bf-wrap bf-lifestyle-hero-copy"><h1>生活木作</h1><span>WOODEN LIVING</span><p>讓木作，走進每一天的生活。<br>從家具，到生活中的每一件小物，<br>我們相信，木頭不只是材料，更是一種陪伴日常的溫度。</p></div><small class="bf-school-disclosure" data-bfnd-reserved-text="1" hidden aria-hidden="true"></small></section>';
    echo '<section class="bf-lifestyle-intro-grid"><div class="bf-lifestyle-intro-copy"><span class="bf-kicker">A LITTLE WOOD, A BETTER DAY</span><h2>生活木作的<br>可能性</h2><p>木作不只存在於大件家具，也能出現在餐桌、書桌、廚房、孩子的房間，甚至是旅途中的風景。我們持續探索木頭在生活中的各種可能，用手作的溫度，連結人與物、日常與自然。</p></div><div class="bf-lifestyle-intro-image">'; bfnd_image(bfnd_asset('assets/lifestyle/tray-01.webp'), '晨露圓境托盤・木款'); echo '<span data-bfnd-reserved-text="1" hidden aria-hidden="true"></span></div></section>';
    echo '<section class="bf-lifestyle-directions bf-wrap"><div class="bf-center-head"><span class="bf-kicker">COMING DIRECTIONS</span><h2>未來將發展的系列方向</h2><p>從生活出發，讓木作走進更多日常場景。</p></div><div class="bf-lifestyle-direction-grid"><article><b aria-hidden="true">' . bfnd_lifestyle_icon('table') . '</b><h3>餐桌器物</h3><p>木盤・餐具・托盤<br>讓飲食時光更溫暖</p></article><article><b aria-hidden="true">' . bfnd_lifestyle_icon('home') . '</b><h3>居家小物</h3><p>燈具・時鐘・掛勾<br>生活裡的小小木作</p></article><article><b aria-hidden="true">' . bfnd_lifestyle_icon('storage') . '</b><h3>收納擺飾</h3><p>收納盒・書架・擺飾<br>有秩序的美感</p></article><article><b aria-hidden="true">' . bfnd_lifestyle_icon('culture') . '</b><h3>文化木作</h3><p>兒童木作・體驗延伸品<br>讓木作走進更多人群</p></article></div></section>';
    echo '<section class="bf-lifestyle-process"><div class="bf-wrap"><div class="bf-center-head"><span class="bf-kicker">OUR MAKING PROCESS</span><h2>製作中的生活木作</h2><p>從一塊木頭，慢慢成為生活的一部分。</p></div><div class="bf-lifestyle-process-grid">';
    $steps = array(
        array('選材', '挑選適合的木材', 'bfnd_selecting_wood_id'),
        array('加工', '結合手作與機械', 'bfnd_process_cnc_id'),
        array('打磨', '讓質感更細緻', 'bfnd_process_sanding_id'),
        array('上油', '呈現木頭的自然之美', 'bfnd_finishing_id'),
    );
    foreach ($steps as $i => $step) { $photo = bfnd_selected_photo($step[2], 'large'); echo '<article>'; if ($photo) { echo '<div>'; bfnd_image($photo, $step[0] . '・木作工序'); echo '</div>'; } else { echo '<span class="bf-index">' . sprintf('%02d', $i + 1) . '</span><span data-bfnd-reserved-image="1" hidden aria-hidden="true"></span>'; } echo '<h3>' . bfnd_e($step[0]) . '</h3><p>' . bfnd_e($step[1]) . '</p></article>'; }
    echo '</div><small class="bf-lifestyle-process-note" data-bfnd-reserved-text="1" hidden aria-hidden="true"></small></div></section>';
    $lifestyle_page = bfnd_page_post('lifestyle');
    $layout_settings = get_option('bfnd_page_design_lifestyle', null);
    $show_works = empty($GLOBALS['bfnd_template_schema_render']) && (is_array($layout_settings) ? !empty($layout_settings['show_works']) : ($lifestyle_page && (has_shortcode($lifestyle_page->post_content, 'bfnd_page')
        ? has_shortcode($lifestyle_page->post_content, 'bfnd_show_works')
        : get_post_meta($lifestyle_page->ID, '_bfnd_show_lifestyle_works', true) === '1')));
    $items = $show_works ? get_posts(array('post_type' => 'bf_lifestyle', 'post_status' => 'publish', 'numberposts' => -1)) : array();
    $has_store_products = bfnd_render_commerce_category_section('lifestyle-woodwork', 'LIFESTYLE SHOP', '生活木作選購', 2);
    if ($items) { echo '<section class="bf-section bf-wrap">'; bfnd_section_head('LIFESTYLE WORKS', '生活木作作品'); echo '<div class="bf-work-grid">'; foreach ($items as $item) { bfnd_work_card($item); } echo '</div></section>'; }
    elseif (!$has_store_products) { echo '<section class="bf-lifestyle-coming bf-wrap"><span aria-hidden="true">♧</span><h2>生活木作系列持續製作中</h2><p>更多作品，正從工坊走向生活。敬請期待。</p></section>'; }
    echo '<section class="bf-lifestyle-cta" style="background-image:linear-gradient(#1c120aa8,#1c120aa8),url(' . esc_url($hero) . ')"><div class="bf-wrap"><h2>想合作開發木製生活用品？</h2><p>歡迎與我們聯繫，一起讓木作走進更多人的日常。</p>'; bfnd_button('合作詢問', bfnd_page_url('collaboration') . '#inquiry', true); echo '</div></section>';
}

function bfnd_render_lifestyle_work($id) {
    $title = get_the_title($id);
    echo '<section class="bf-lifestyle-detail bf-wrap"><a class="bf-back" href="' . esc_url(bfnd_page_url('lifestyle')) . '">← 返回生活木作</a><div class="bf-lifestyle-detail-grid"><div class="bf-lifestyle-main-image">'; bfnd_image(bfnd_work_image($id, 'full'), $title, '', false); echo '</div><div><span class="bf-kicker">LIFESTYLE WORKS</span><h1>' . bfnd_e($title) . '</h1><p class="bf-serif">一器承日常，一圓納天地。</p><p>' . nl2br(esc_html(get_post_field('post_content', $id))) . '</p><p>竹款｜Bamboo Edition<br>木款｜Wood Edition</p>'; bfnd_button('詢問作品', bfnd_page_url('collaboration') . '#inquiry'); echo '</div></div></section>';
    $gallery = bfnd_gallery($id);
    if ($gallery) { echo '<section class="bf-section bf-wrap">'; bfnd_section_head('THE DETAILS', '器物與光影'); echo '<div class="bf-detail-gallery">'; foreach ($gallery as $src) { echo '<button class="bf-gallery-button" type="button" aria-label="放大作品照片">'; bfnd_image($src, $title); echo '</button>'; } echo '</div></section>'; }
    echo '<dialog id="bf-image-dialog" class="bf-image-dialog" aria-label="生活木作照片放大檢視"><button type="button" aria-label="關閉照片">關閉 ×</button><img alt="生活木作照片放大檢視"></dialog>';
}

function bfnd_render_school() {
    $hero = bfnd_brand_photo('bfnd_school_hero_id', 'full', 'assets/brand/real-lecture.webp');
    echo '<section class="bf-school-hero" data-bfnd-carousel data-bfnd-interval="6000"><div class="bf-school-hero-image" data-bfnd-reserved-image="1">';
    $hero_slides = bfnd_render_banner_images('school', 'bfnd_school_hero_id', 'assets/brand/real-lecture.webp');
    echo '</div><div class="bf-school-hero-shade"></div>';
    bfnd_render_banner_controls($hero_slides, '木作學堂');
    echo '<div class="bf-school-hero-content bf-wrap"><span class="bf-kicker">WOODWORKING SCHOOL</span><h1>木作學堂</h1><p>從一堂課開始，<br>用雙手創造屬於自己的作品。</p><div class="bf-actions">';
    bfnd_button('實體課程', '#onsite-courses', true);
    echo '<a class="bf-button bf-button-light" href="#online-courses"><span>線上課程</span><span aria-hidden="true">↗</span></a></div></div><span class="bf-school-disclosure" data-bfnd-reserved-text="1" hidden aria-hidden="true"></span></section>';
    echo '<div class="bf-values bf-wrap"><div><b>01</b><span>專業師資</span><small>來自實務現場的教學</small></div><div><b>02</b><span>完整設備</span><small>手工具、木工機械與 CNC</small></div><div><b>03</b><span>小班教學</span><small>實作為主，安全有保障</small></div><div><b>04</b><span>從興趣到創作</span><small>陪你完成自己的作品</small></div></div>';
    echo '<section class="bf-learning-path bf-wrap" aria-labelledby="bf-learning-path-title"><div class="bf-learning-path-heading"><span class="bf-kicker">LEARNING PATH</span><h2 id="bf-learning-path-title">木作學習路徑</h2></div><ol class="bf-learning-path-steps"><li><span>LEVEL 1</span><strong>基礎入門</strong></li><li><span>LEVEL 2</span><strong>實作養成</strong></li><li><span>LEVEL 3</span><strong>自主製作</strong></li><li><span>36 HOURS</span><strong>完整方案</strong><small>優惠價 NT$ 15,800</small></li></ol></section>';
    echo '<section class="bf-section bf-wrap bf-school-courses" id="onsite-courses"><div class="bf-tab-head">'; bfnd_section_head('LEARN BY MAKING', '實體課程', '#online-courses', '查看線上課程'); echo '</div>';
    $course_statuses = empty($GLOBALS['bfnd_public_content_render']) && current_user_can('edit_posts') ? array('publish', 'private') : 'publish';
    $courses = get_posts(array('post_type' => 'bf_course', 'post_status' => $course_statuses, 'numberposts' => -1, 'orderby' => 'menu_order', 'order' => 'ASC'));
    echo '<div class="bf-course-grid" data-course-group="onsite">'; foreach ($courses as $course) { if (bfnd_meta($course->ID, 'mode') !== 'online' && bfnd_course_track($course->ID) !== 'specialist') { bfnd_course_card($course); } } echo '</div></section>';
    $specialist_courses = array_filter($courses, function ($course) { return bfnd_meta($course->ID, 'mode') !== 'online' && bfnd_course_track($course->ID) === 'specialist'; });
    if ($specialist_courses) {
        echo '<section class="bf-section bf-wrap bf-school-specialist" id="specialist-courses">';
        bfnd_section_head('SPECIALIST SKILLS', '專項技能課程');
        echo '<div class="bf-course-grid">'; foreach ($specialist_courses as $course) { bfnd_course_card($course); } echo '</div></section>';
    }
    echo '<section class="bf-section bf-wrap bf-school-online" id="online-courses">'; bfnd_section_head('LEARN FROM ANYWHERE', '線上課程', '#onsite-courses', '查看實體課程');
    echo '<div class="bf-course-grid" data-course-group="online">'; $online = 0; foreach ($courses as $course) { if (bfnd_meta($course->ID, 'mode') === 'online') { bfnd_course_card($course); $online++; } }
    if (!$online) {
        echo '<article class="bf-course-preview-panel"><span class="bf-kicker">ONLINE COURSES</span><h3>線上課程資訊近期公布</h3><p>正式課綱與開課資訊確認後，將於此頁更新。</p></article>';
    }
    echo '</div></section>';
    $hand_tools = get_term_by('name', '手工具', 'product_cat');
    if ($hand_tools) { bfnd_render_commerce_category_section($hand_tools, 'TOOLS FOR MAKING', '木作學堂・手工具', 3); }
    $school_cta = bfnd_brand_photo('bfnd_home_hero_id', 'full', 'assets/works/muyo/01.webp');
    echo '<section class="bf-school-cta" style="background-image:linear-gradient(90deg,#24180dcc,#24180d22),url(' . esc_url($school_cta) . ')"><div class="bf-wrap"><h2>不知道哪一堂課適合你？</h2><p>歡迎與我們聊聊，找到最適合你的學習路徑。</p><div class="bf-actions">'; bfnd_button('課程選擇指南', '#onsite-courses', true); bfnd_button('聯絡我們', bfnd_page_url('collaboration') . '#inquiry', true); echo '</div></div></section>';
}

function bfnd_course_track($id) {
    $track = bfnd_meta($id, 'track');
    if ($track) { return $track; }
    return in_array(bfnd_meta($id, 'seed'), array('cnc', 'sharpening'), true) ? 'specialist' : '';
}

function bfnd_render_course_list($id, $key, $title, $index) {
    $value = trim((string) bfnd_meta($id, $key));
    if ($value === '') { return; }
    $items = preg_split('/\r\n|\r|\n/u', $value);
    echo '<section class="bf-section bf-wrap bf-course-content-list"><span class="bf-index">' . bfnd_e($index) . '</span><h2>' . bfnd_e($title) . '</h2><ul>';
    foreach ($items as $item) { $item = trim($item); if ($item !== '') { echo '<li>' . bfnd_e($item) . '</li>'; } }
    echo '</ul></section>';
}

function bfnd_course_card($course) {
    $id = $course->ID;
    $duration = trim((string) bfnd_meta($id, 'duration'));
    $level = trim((string) bfnd_meta($id, 'level'));
    $image = bfnd_work_image($id, 'medium_large');
    $track = bfnd_course_track($id);
    $course_label = $track === 'specialist' ? 'SPECIALIST SKILLS' : ($track === 'program' ? 'COMPLETE LEARNING PATH' : 'ON-SITE COURSE');
    if (bfnd_meta($id, 'mode') === 'online') { $course_label = 'ONLINE COURSE'; }
    echo '<article class="bf-course-card"><a class="bf-course-card-main" href="' . esc_url(get_permalink($id)) . '">'; if ($image) { echo '<div class="bf-course-image">'; bfnd_image($image, get_the_title($id) . '課程'); echo '</div>'; } echo '<div class="bf-course-card-body"><span class="bf-kicker">' . bfnd_e($course_label) . '</span><h3>' . bfnd_e(get_the_title($id)) . '</h3><div class="bf-course-meta">';
    if ($duration !== '') { echo '<span>' . bfnd_e($duration) . '</span>'; }
    if ($level !== '') { echo '<span>' . bfnd_e($level) . '</span>'; }
    echo '</div>';
    $price_html = bfnd_course_price_html($id); if ($price_html !== '') { echo '<p class="bf-course-price">' . wp_kses_post($price_html) . '</p>'; }
    $features = trim((string) bfnd_meta($id, 'features'));
    if ($features !== '') { echo '<p class="bf-course-card-plans">' . nl2br(esc_html($features)) . '</p>'; }
    echo '<span class="bf-plain-link">查看課程詳情 ↗</span></div></a>';
    $action = bfnd_course_registration_action($id);
    if ($action) {
        echo '<a class="bf-course-card-action" href="' . esc_url($action['url']) . '">' . bfnd_e($action['label']) . ' ↗</a>';
    } else {
        echo '<a class="bf-course-card-action" href="' . esc_url(add_query_arg('course', $id, bfnd_page_url('collaboration')) . '#inquiry') . '">詢問課程 ↗</a>';
    }
    echo '</article>';
}

function bfnd_render_course($id) {
    bfnd_simple_hero('WOODWORKING COURSE', get_the_title($id), get_the_excerpt($id), bfnd_work_image($id, 'full'));
    echo '<section class="bf-story-grid bf-wrap"><div><span class="bf-index">01 / ABOUT THE COURSE</span><h2>課程介紹</h2><div class="bf-prose">' . apply_filters('the_content', get_post_field('post_content', $id)) . '</div></div><div class="bf-course-facts"><dl><div><dt>課程形式</dt><dd>' . (bfnd_meta($id, 'mode') === 'online' ? '線上課程' : '實體課程') . '</dd></div>';
    $facts = array('課程時數' => bfnd_meta($id, 'duration'), '課程費用' => bfnd_course_price_html($id), '程度' => bfnd_meta($id, 'level'), '適合對象' => bfnd_meta($id, 'audience') ?: bfnd_meta($id, 'level'), '開課梯次' => bfnd_meta($id, 'schedule'));
    foreach ($facts as $label => $value) { if (trim(wp_strip_all_tags((string) $value)) !== '') { echo '<div><dt>' . bfnd_e($label) . '</dt><dd>' . ($label === '課程費用' ? wp_kses_post($value) : nl2br(bfnd_e($value))) . '</dd></div>'; } }
    $action = bfnd_course_registration_action($id);
    if ($action) { bfnd_button($action['label'], $action['url']); }
    else { bfnd_button('詢問課程', add_query_arg('course', $id, bfnd_page_url('collaboration')) . '#inquiry'); }
    echo '</div></section>';
    bfnd_render_course_list($id, 'features', '課程特色', '02 / COURSE FEATURES');
    bfnd_render_course_list($id, 'learning', '學習內容', '03 / LEARNING');
    bfnd_render_course_list($id, 'tools', '使用工具', '04 / TOOLS');
    bfnd_render_course_list($id, 'outcomes', '完成成果', '05 / OUTCOMES');
    bfnd_render_course_list($id, 'notices', '注意事項', '06 / NOTES');
    $posters = bfnd_gallery($id);
    if ($posters) { echo '<section class="bf-section bf-wrap bf-course-poster"><span class="bf-kicker">COURSE INFORMATION</span><h2>課程簡章</h2>'; bfnd_image($posters[0], get_the_title($id) . '課程簡章'); if (count($posters) > 1) { echo '<h3>課程照片</h3><div class="bf-detail-gallery">'; foreach (array_slice($posters, 1, 8) as $photo) { bfnd_image($photo, get_the_title($id) . '課程照片'); } echo '</div>'; } echo '</section>'; }
}

function bfnd_story_row($number, $en, $title, $text, $image, $image_alt, $link = '', $link_label = '', $reverse = false, $reserve_caption = false, $reserve_image = false) {
    echo '<section class="bf-story-row' . ($reverse ? ' bf-story-row-reverse' : '') . (!$image ? ' bf-story-row-text-only' : '') . '">'; if ($image) { echo '<div class="bf-story-row-image">'; bfnd_image($image, $image_alt); if ($reserve_caption) { echo '<span data-bfnd-reserved-text="1" hidden aria-hidden="true"></span>'; } echo '</div>'; } else { if ($reserve_image) { echo '<span data-bfnd-reserved-image="1" hidden aria-hidden="true"></span>'; } if ($reserve_caption) { echo '<span data-bfnd-reserved-text="1" hidden aria-hidden="true"></span>'; } } echo '<div class="bf-story-row-content"><span class="bf-story-number">' . bfnd_e($number) . '</span><span class="bf-kicker">' . bfnd_e($en) . '</span><h2>' . bfnd_e($title) . '</h2><p>' . nl2br(bfnd_e($text)) . '</p>';
    if ($link) { echo '<a class="bf-text-link" href="' . esc_url($link) . '">' . bfnd_e($link_label) . ' ↗</a>'; }
    echo '</div></section>';
}

function bfnd_render_story() {
    $brand_hero = bfnd_brand_photo('bfnd_home_hero_id', 'full', 'assets/works/muyo/01.webp');
    echo '<section class="bf-brand-hero"><div class="bf-brand-hero-copy"><span class="bf-kicker">BRAND STORY / BEAR’S FANTASYLAND</span><h1>飛熊入夢<br><em>木，讓生活更美好。</em></h1><p>從一雙木工的手，<br>到一個新的世代。<br>我們相信，木不只是材料，<br>而是一種更好的生活方式。</p></div><div class="bf-brand-hero-image">'; bfnd_image($brand_hero, '飛熊入夢實木家具', '', false); echo '</div></section>';
    $story_settings = function_exists('bfnd_template_settings') ? bfnd_template_settings('story') : array();
    $video_id = function_exists('bfnd_youtube_video_id') ? bfnd_youtube_video_id($story_settings['youtube_url'] ?? '') : '';
    if ($video_id) {
        $video_url = 'https://www.youtube.com/watch?v=' . rawurlencode($video_id);
        $poster_url = 'https://i.ytimg.com/vi/' . rawurlencode($video_id) . '/hqdefault.jpg';
        echo '<section class="bf-brand-film" data-bfnd-design-ignore aria-labelledby="bf-brand-film-title"><div class="bf-wrap bf-brand-film-inner"><div class="bf-brand-film-copy"><span class="bf-kicker">BRAND FILM</span><h2 id="bf-brand-film-title">品牌影片</h2><p>透過影像，認識飛熊入夢的木作與日常。</p></div><div class="bf-brand-film-frame" data-bf-youtube-id="' . esc_attr($video_id) . '"><img src="' . esc_url($poster_url) . '" alt="飛熊入夢品牌影片封面" loading="lazy"><a class="bf-brand-film-play" data-bf-youtube-play href="' . esc_url($video_url) . '" target="_blank" rel="noopener noreferrer" aria-label="播放飛熊入夢品牌影片"><span class="bf-brand-film-play-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M8 5.5v13l10-6.5z"></path></svg></span><span class="bf-brand-film-play-label">播放品牌影片</span></a></div></div></section>';
    }
    bfnd_story_row('01', 'OUR ORIGIN', '源於熱愛', "飛熊入夢源自對木工的熱愛。我們從傳統木工出發，結合現代設計與數位製造，在手作的溫度與科技的精準之間，尋找屬於這個時代的木作方式。\n\n我們希望，木作不只是被保存的技藝，而能真正走進更多人的生活。", bfnd_asset('assets/brand/real-lecture.webp'), '飛熊入夢木作教學現場', '', '', false, true);
    bfnd_story_row('02', 'FURNITURE', '實木家具', "我們設計並製作實木家具。從木材的選擇、比例、結構到使用方式，回到家具最單純的本質——自然、耐用，並且能長久陪伴生活。\n\n每件作品都能隨著時間與使用，慢慢留下屬於生活的痕跡。", bfnd_asset('assets/works/muyo/02.webp'), '木韻 MUYO 胡桃木桌椅結構細節', bfnd_page_url('furniture'), '探索家具作品', true);
    $wood_ring = bfnd_brand_photo('bfnd_manifesto_id', 'large', 'assets/works/muyo/02.webp');
    echo '<section class="bf-philosophy bf-wrap"><span class="bf-kicker">OUR PHILOSOPHY</span><h2>讓傳承，成為創新的開始。</h2><div class="bf-philosophy-layout"><div class="bf-philosophy-longform">';
    $philosophy = array(
        array('p', '我們從傳統木工走來。曾經，一把鉋刀、一支鑿刀、一雙佈滿木屑的手，就是一位木工師傅最熟悉的日常。那些看似平凡的技藝，藏著的是一代又一代累積下來的經驗。對木材的理解、對尺寸的堅持、對結構的講究，以及對一件作品負責到底的態度。這些，是台灣木工珍貴的底蘊。'),
        array('quote', '傳承，不應該只是把過去原封不動地留下來。'),
        array('p', '如果一門技藝要繼續走下去，它就必須走進新的時代。因此，飛熊入夢選擇從傳統木工出發，走向家具設計、數位製造與當代工藝。我們保留老師傅手中的溫度，也擁抱新世代的設計思維與技術；讓手工具與數位製造相遇，讓傳統榫接與現代設計對話，讓過去累積的經驗，成為下一個創新的起點。'),
        array('quote', '新與舊的交替，不是取代，而是讓彼此成就。'),
        array('p', '我們相信，真正屬於台灣的設計，不需要模仿任何人。因為我們腳下的土地、我們熟悉的木材、我們一路走來的工藝，以及一代代職人留下來的智慧，本身就值得被世界看見。飛熊入夢想做的，不只是一張桌子、一把椅子。我們想讓更多年輕人重新看見木工的價值；讓傳統技藝不只被保存，而是有人願意學、有人願意做、有人願意繼續創造；讓設計師與職人不再站在兩端，而是一起創造屬於這個時代的作品。'),
        array('p', '我們希望有一天，當一件家具從台灣走向世界，人們看見的不只是它的外型。而是能從一道木紋、一處接合、一條曲線之中，看見台灣的工藝、設計與我們對這片土地的自信。'),
        array('quote', '傳統給了我們根，設計讓我們長出新的枝枒。'),
        array('p', '飛熊入夢所做的，是把兩個世代的手，牽在一起。讓老技藝有新的生命，讓新設計有深厚的根。讓「台灣製造」不只代表產地，而是一件值得被珍惜、被認同，也值得我們感到驕傲的事。'),
    );
    foreach ($philosophy as $block) { $tag = $block[0] === 'quote' ? 'blockquote' : 'p'; echo '<' . $tag . '>' . bfnd_e($block[1]) . '</' . $tag . '>'; }
    echo '</div><div class="bf-philosophy-image">'; bfnd_image($wood_ring, '木材年輪與木紋'); echo '</div></div></section>';
    $workshop = bfnd_brand_photo('bfnd_school_hero_id', 'large', 'assets/brand/real-lecture.webp');
    bfnd_story_row('03', 'EDUCATION & MAKING', '木工教育 × 數位製造', "真正值得傳承的，不只有一件完成的作品，還有製作它的方法。從木工基礎、自由創作，到 CNC 數位木工，讓更多人從第一次接觸木材開始，逐步完成自己的作品。", $workshop, '木工教育工坊', bfnd_page_url('school'), '了解木作學堂', false, true);
    $cnc_story = bfnd_selected_photo('bfnd_process_cnc_id', 'large');
    bfnd_story_row('04', 'TAICHUNG MAKER', '台中 Maker 工藝基地', "飛熊入夢扎根台中。這裡結合木工設備、CNC 數位製造、設計、教育與創作，是我們發展課程，也讓創意真正被實現的地方。", $cnc_story, '台中 Maker CNC 數位木工設備', '', '', true, true, true);
    echo '<section class="bf-brand-statement bf-wrap"><span class="bf-kicker">A BETTER LIVING WITH WOOD</span><h2>承一代人的手藝，<br>創下一代人的家具。</h2><p>從台灣出發，做讓我們自己都感到驕傲的設計。</p><div class="bf-actions">'; bfnd_button('探索家具作品', bfnd_page_url('furniture')); bfnd_button('開始學木工', bfnd_page_url('school')); echo '</div></section>';
}

function bfnd_render_collaboration() {
    $hero = bfnd_brand_photo('bfnd_home_hero_id', 'full', 'assets/works/muyo/01.webp');
    echo '<section class="bf-collab-hero" data-bfnd-carousel data-bfnd-interval="6000"><div class="bf-collab-hero-image" data-bfnd-reserved-image="1">'; $hero_slides = bfnd_render_banner_images('collaboration', 'bfnd_home_hero_id', 'assets/works/muyo/01.webp'); echo '</div>'; bfnd_render_banner_controls($hero_slides, '合作提案'); echo '<div class="bf-collab-hero-copy bf-wrap"><span class="bf-kicker">COLLABORATION</span><h1>與木一起，<br>創造更好的空間。</h1><p>飛熊入夢以家具設計為核心，<br>結合木工技藝、數位製造與木工教育，<br>與企業、設計單位、學校及文化組織，<br>共同發展更美好的空間與生活。</p>'; bfnd_button('與我們談合作', '#inquiry'); echo '</div><small class="bf-school-disclosure" data-bfnd-reserved-text="1" hidden aria-hidden="true"></small></section>';
    echo '<section class="bf-collab-possibilities bf-wrap"><div><span class="bf-index">01</span><h2>我們可以怎麼合作？</h2><p>不同的空間，一樣的木作溫度。<br>從家具到教育，與你一起創造更多可能。</p></div><div class="bf-collab-possibilities-grid"><article><b aria-hidden="true">▰</b><h3>家具採購</h3><p>飛熊入夢<br>既有家具系列</p></article><article><b aria-hidden="true">⌂</b><h3>企業／空間合作</h3><p>辦公・商業・公共<br>及教育空間</p></article><article><b aria-hidden="true">♧</b><h3>永續合作</h3><p>材料・耐久設計<br>維修延壽・減少浪費</p></article><article><b aria-hidden="true">◇</b><h3>教育／文化合作</h3><p>木工教育・工藝體驗<br>文化及企業活動</p></article></div></section>';
    echo '<section class="bf-collab-design"><div class="bf-collab-design-image">'; bfnd_image(bfnd_asset('assets/works/ridge-table/01.webp'), '稜 RIDGE 實木餐桌'); echo '</div><div class="bf-collab-design-copy"><span class="bf-kicker">FROM OUR DESIGN TO YOUR SPACE</span><h2>從我們的設計，<br>走進你的空間。</h2><p>我們以飛熊入夢的設計語言與既有作品為基礎，依不同空間需求，在尺寸、木材與使用情境上進行適度調整，讓家具真正融入生活。</p></div><div class="bf-collab-design-image">'; bfnd_image(bfnd_asset('assets/works/muyo/01.webp'), '木韻 MUYO 胡桃木餐桌椅'); echo '</div></section>';
    echo '<section id="process" class="bf-collab-process bf-wrap"><div class="bf-collab-process-head"><span class="bf-index">03</span><h2>合作流程</h2><p>簡單透明，讓合作更順利。</p></div><ol class="bf-collab-process-list">';
    $steps = array(array('需求討論', '了解使用情境、空間與需求'), array('提案報價', '提出合適作品及製作方案'), array('確認細節', '確認材質、尺寸、數量與細節'), array('製作執行', '台中工坊製作與品質管控'), array('完成交付', '依作品安排運送與安裝'));
    foreach ($steps as $i => $step) { echo '<li><b>' . sprintf('%02d', $i + 1) . '</b><strong>' . bfnd_e($step[0]) . '</strong><small>' . bfnd_e($step[1]) . '</small></li>'; }
    echo '</ol></section>';
    echo '<section class="bf-collab-scenarios"><div class="bf-wrap bf-collab-scenarios-inner"><div><span class="bf-index">04</span><h2>合作場景</h2><p>不同的場景，<br>同樣的木作價值。</p><small data-bfnd-reserved-text="1" hidden aria-hidden="true"></small></div><div class="bf-collab-scenarios-grid">';
    $scenarios = array(
        array('企業辦公空間', '會議桌・辦公桌・收納櫃'),
        array('商業空間', '餐桌椅・展示櫃・空間規劃'),
        array('教育場域', '教學桌椅・木作教具'),
        array('公共空間', '圖書館・社區空間・展覽'),
    );
    foreach ($scenarios as $i => $scene) { echo '<article><span data-bfnd-reserved-image="1" hidden aria-hidden="true"></span><span class="bf-index">' . sprintf('%02d', $i + 1) . '</span><h3>' . bfnd_e($scene[0]) . '</h3><p>' . bfnd_e($scene[1]) . '</p></article>'; }
    echo '</div></div></section>';
    echo '<section class="bf-collab-sustain"><span data-bfnd-reserved-image="1" hidden aria-hidden="true"></span><div class="bf-wrap bf-collab-sustain-inner"><div><span class="bf-kicker">05 / SUSTAINABILITY</span><h2>永續設計 × 企業合作</h2><p>從一件家具開始，為下一個世代設計更長久的生活。</p>'; bfnd_button('洽談永續合作', '#inquiry', true); echo '</div><div class="bf-collab-sustain-values"><article><b>♧</b><h3>責任材料</h3><p>建立木材來源與材料紀錄</p></article><article><b>⌂</b><h3>在地製造</h3><p>台中工坊・設計製作</p></article><article><b>∞</b><h3>耐久設計</h3><p>延長家具使用生命週期</p></article><article><b>↻</b><h3>維修再生</h3><p>保養・修繕與重新整理</p></article></div><div class="bf-sdg-goals" aria-label="設計方向呼應的聯合國永續發展目標"><span class="bf-sdg-4"><b>4</b>優質教育</span><span class="bf-sdg-8"><b>8</b>合適的工作與經濟成長</span><span class="bf-sdg-11"><b>11</b>永續城市與社區</span><span class="bf-sdg-12"><b>12</b>負責任的消費與生產</span></div></div></section>';
    echo '<section class="bf-collab-after"><span data-bfnd-reserved-image="1" hidden aria-hidden="true"></span><div class="bf-wrap bf-collab-after-inner"><div><span class="bf-kicker">06 / AFTER SERVICE</span><h2>售後與交付服務</h2><p>一件好的家具，值得被長久使用。我們提供完整的交付與維護服務。</p><a class="bf-text-link" href="' . esc_url(bfnd_page_url('service')) . '">了解服務內容 ↗</a></div><div class="bf-collab-after-grid"><article><b>↗</b><h3>運送與安裝</h3><p>依作品尺寸與地點安排</p></article><article><b>◇</b><h3>作品保固</h3><p>提供售後檢查與保固服務</p></article><article><b>⌘</b><h3>維護與修繕</h3><p>保養・修繕・重新整理</p></article></div></div></section>';
    bfnd_render_journal_teaser();
    bfnd_render_inquiry_form();
}

function bfnd_render_inquiry_form() {
    $work_id = isset($_GET['work']) ? absint($_GET['work']) : 0;
    if (!$work_id || get_post_type($work_id) !== 'bf_work') { $work_id = 0; }
    $selected_material = bfnd_requested_work_option($work_id, 'material');
    $selected_size = bfnd_requested_work_option($work_id, 'size');
    $course_id = isset($_GET['course']) ? absint($_GET['course']) : 0;
    $course_title = $course_id && get_post_type($course_id) === 'bf_course' ? get_the_title($course_id) : '';
    $online_interest = isset($_GET['interest']) && sanitize_key(wp_unslash($_GET['interest'])) === 'online-course';
    echo '<section id="inquiry" class="bf-inquiry-section"><div class="bf-wrap bf-inquiry-grid"><div><span class="bf-kicker">START A CONVERSATION</span><h2>聊聊你的想法。</h2><p>告訴我們你正在尋找什麼，我們會從材質、尺寸與使用情境，與你一起找到合適的木作方式。</p><p class="bf-form-note">表單資料會保存到飛熊入夢後台供回覆使用。請勿填寫身分證、付款資訊或其他敏感資料；如需提供參考圖片，請先於說明欄描述，我們會另行確認傳送方式。</p></div><div class="bf-form-shell">';
    if (isset($_GET['sent']) && $_GET['sent'] === '1') { echo '<div class="bf-success" role="status"><h3>已收到你的詢問。</h3><p>我們會依留下的聯絡方式回覆你。</p></div>'; }
    echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" data-bf-inquiry-form><input type="hidden" name="action" value="bfnd_inquiry">'; wp_nonce_field('bfnd_inquiry', 'bfnd_inquiry_nonce'); echo '<label class="bf-honeypot">網站<input type="text" name="website" autocomplete="off" tabindex="-1"></label>';
    echo '<div class="bf-form-row"><label>姓名 <span aria-hidden="true">*</span><input required maxlength="80" name="name" autocomplete="name"></label><label>聯絡方式 <span aria-hidden="true">*</span><input required maxlength="150" name="contact" placeholder="Email 或電話" autocomplete="email"></label></div>';
    echo '<label>詢問作品<select name="work_id" data-bf-inquiry-work><option value="">一般合作／其他需求</option>';
    $works = bfnd_work_query(array('orderby' => 'title', 'order' => 'ASC'));
    foreach ($works->posts as $post) {
        $material_json = bfnd_work_option_json($post->ID, 'material');
        $size_json = bfnd_work_option_json($post->ID, 'size');
        echo '<option value="' . esc_attr($post->ID) . '" data-material-options="' . esc_attr($material_json) . '" data-size-options="' . esc_attr($size_json) . '"' . selected($work_id, $post->ID, false) . '>' . bfnd_e(get_the_title($post)) . '</option>';
    }
    echo '</select></label><div class="bf-inquiry-option-grid">';
    bfnd_render_inquiry_option_field($work_id, 'material', '選擇木材／材質', $selected_material);
    bfnd_render_inquiry_option_field($work_id, 'size', '選擇尺寸', $selected_size);
    echo '</div><div class="bf-form-row"><label>其他尺寸需求（選填）<input name="dimension" maxlength="150" placeholder="例如希望再加長 10 公分"></label><label>使用空間<input name="space" maxlength="150" placeholder="例如住宅餐廳、商業空間"></label></div>';
    echo '<label>預算／其他需求<input name="budget" maxlength="300" placeholder="可簡述預算範圍或想法"></label><label>補充說明<textarea name="message" rows="5" placeholder="告訴我們你期待的材質、用途與合作方式">' . ($course_title ? esc_textarea('我想詢問課程｜' . $course_title) : ($online_interest ? esc_textarea('我想收到線上課程上架通知。') : '')) . '</textarea></label>';
    echo '<button class="bf-submit" type="submit">送出詢問 <span aria-hidden="true">↗</span></button></form></div></div></section>';
}

function bfnd_journal_query($limit) {
    return new WP_Query(array('post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => $limit));
}
function bfnd_journal_card($post) {
    $terms = get_the_category($post->ID);
    echo '<a class="bf-journal-card" href="' . esc_url(get_permalink($post)) . '"><div class="bf-journal-image">';
    if (has_post_thumbnail($post)) { bfnd_image(get_the_post_thumbnail_url($post, 'large'), get_the_title($post)); }
    echo '</div><div class="bf-journal-meta"><time datetime="' . esc_attr(get_the_date('c', $post)) . '">' . bfnd_e(get_the_date('Y.m.d', $post)) . '</time><span>' . bfnd_e($terms ? $terms[0]->name : '') . '</span></div><h3>' . bfnd_e(get_the_title($post)) . '</h3></a>';
}
function bfnd_render_journal_teaser() {
    $q = bfnd_journal_query(3);
    echo '<section class="bf-section bf-wrap">'; bfnd_section_head('JOURNAL', '飛熊日誌', bfnd_page_url('journal'), '閱讀更多'); echo '<p class="bf-section-intro">記錄木作、設計，以及工坊裡正在發生的事。</p>';
    if ($q->have_posts()) { echo '<div class="bf-journal-grid">'; foreach ($q->posts as $post) { bfnd_journal_card($post); } echo '</div>'; }
    else { echo '<p class="bf-journal-coming-note">文章準備中。</p>'; }
    echo '</section>';
}
function bfnd_render_journal() {
    bfnd_simple_hero('JOURNAL', '飛熊日誌', '記錄木作、設計，以及工坊裡正在發生的事。', bfnd_asset('assets/works/ridge-shelf/01.webp'));
    $q = bfnd_journal_query(-1);
    echo '<section class="bf-section bf-wrap">'; bfnd_section_head('STORIES FROM THE WORKSHOP', '最新文章');
    if ($q->have_posts()) { echo '<div class="bf-journal-grid">'; foreach ($q->posts as $post) { bfnd_journal_card($post); } echo '</div>'; }
    else { echo '<p class="bf-journal-coming-note">文章準備中。</p>'; }
    echo '</section>';
}
function bfnd_render_service() {
    bfnd_simple_hero('PURCHASE & SERVICE', '購買與服務', '讓實木家具安心走進生活，也陪伴你長久使用。');
    echo '<section class="bf-service-section bf-wrap"><div id="purchase"><span class="bf-kicker">01 / WORKS INQUIRY</span><h2>作品購買／詢問</h2><p>飛熊入夢以自有家具作品為主。歡迎詢問作品尺寸、木種、製作方式、現有規格與可購買狀態；實際商品資訊以作品頁及訂單確認內容為準。</p><a class="bf-text-link" href="' . esc_url(bfnd_page_url('furniture')) . '">瀏覽家具作品 ↗</a></div><div id="shipping-install"><span class="bf-kicker">02 / DELIVERY</span><h2>運送與安裝</h2><p>運送及安裝安排依作品尺寸、配送地址、樓層與現場動線確認。費用、方式與交期請於下單前確認，並以訂單記載內容為準。</p></div><div id="warranty"><span class="bf-kicker">03 / WARRANTY</span><h2>作品保固</h2><p>各作品保固範圍與期間依正式保固說明及訂單記載內容為準。若有疑問，請在購買前與我們確認。</p></div><div id="care"><span class="bf-kicker">04 / CARE & REPAIR</span><h2>保養與修繕</h2><p>木材與表面處理方式不同，日常保養方法請依作品說明。若需保養或修繕，請提供作品名稱、購買資訊及狀況照片，我們會先評估可行方式與費用。</p></div><div id="faq"><span class="bf-kicker">05 / FAQ</span><h2>常見問題</h2><details><summary>作品尺寸都是固定的嗎？</summary><p>請先參閱作品頁上的規格。部分作品可依空間需求調整尺寸，實際製作方式需按個別作品確認。</p></details><details><summary>如何確認運送和安裝費用？</summary><p>請提供配送地址、樓層及現場動線，我們會依作品狀況確認可行的配送方式及費用。</p></details><details><summary>可以協助作品保養或修繕嗎？</summary><p>請提供作品資料與目前狀況，我們會先確認作品及可行的處理方式，再回覆費用與安排。</p></details></div></section>';
    bfnd_render_cta('有其他問題？', '讓我們一起討論。', '聯絡我們', bfnd_page_url('collaboration') . '#inquiry');
}

function bfnd_render_commerce() {
    $shop = bfnd_shop_url();
    $account = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : bfnd_page_url('service');
    $cart = function_exists('wc_get_cart_url') ? wc_get_cart_url() : bfnd_page_url('furniture');
    if (is_cart()) { $type = 'cart'; $eyebrow = 'YOUR CART'; $title = '購物車'; $intro = '確認選購的作品與數量，接著完成結帳。'; }
    elseif (is_checkout()) { $type = 'checkout'; $eyebrow = 'CHECKOUT'; $title = '結帳'; $intro = '請確認訂單及配送資料。'; }
    elseif (is_account_page()) { $type = 'account'; $eyebrow = 'MY ACCOUNT'; $title = '會員中心'; $intro = '查看訂單、配送地址與帳號資料。'; }
    elseif (is_product()) { $type = 'product'; $eyebrow = 'SHOP'; $title = get_the_title(); $intro = ''; }
    else { $type = 'shop'; $eyebrow = 'SHOP'; $title = is_product_category() || is_product_tag() ? single_term_title('', false) : '商品選購'; $intro = '探索飛熊入夢的作品與木作商品。'; }
    echo '<section class="bf-commerce-hero"><div class="bf-wrap"><nav class="bf-breadcrumb" aria-label="麵包屑"><a href="' . esc_url(bfnd_page_url('home')) . '">首頁</a><span aria-hidden="true">／</span><span>' . bfnd_e($title) . '</span></nav><span class="bf-kicker">' . bfnd_e($eyebrow) . '</span><h1>' . bfnd_e($title) . '</h1>';
    if ($intro) { echo '<p>' . bfnd_e($intro) . '</p>'; }
    echo '</div></section><section class="bf-commerce-section"><div class="bf-wrap bf-commerce-content bf-commerce-' . esc_attr($type) . '">';
    if ($type === 'cart') { echo do_shortcode('[woocommerce_cart]'); }
    elseif ($type === 'checkout') { echo do_shortcode('[woocommerce_checkout]'); }
    elseif ($type === 'account') { echo do_shortcode('[woocommerce_my_account]'); }
    elseif ($type === 'shop' && is_page(array('woodshop', 'shop'))) {
        $terms = get_terms(array('taxonomy' => 'product_cat', 'hide_empty' => true));
        if (!is_wp_error($terms) && $terms) {
            echo '<nav class="bf-shop-categories" aria-label="商品分類"><a aria-current="page" href="' . esc_url($shop) . '">全部商品</a>';
            foreach ($terms as $term) {
                if ($term->slug === 'uncategorized') { continue; }
                if ($term->name === '手工具') { echo '<span class="bf-shop-category-group">木作學堂</span>'; }
                echo '<a href="' . esc_url(get_term_link($term)) . '">' . esc_html($term->name) . '</a>';
            }
            echo '</nav>';
        }
        echo do_shortcode('[products limit="12" columns="4" paginate="true"]');
    }
    elseif (function_exists('woocommerce_content')) { woocommerce_content(); }
    echo '</div></section><nav class="bf-commerce-next bf-wrap" aria-label="相關頁面"><a href="' . esc_url(bfnd_page_url('furniture')) . '">探索家具作品 <span aria-hidden="true">↗</span></a><a href="' . esc_url($shop) . '">選購商品 <span aria-hidden="true">↗</span></a><a href="' . esc_url($cart) . '">購物車 <span aria-hidden="true">↗</span></a><a href="' . esc_url($account) . '">會員中心 <span aria-hidden="true">↗</span></a><a href="' . esc_url(bfnd_page_url('service')) . '">購買與服務 <span aria-hidden="true">↗</span></a></nav>';
}

function bfnd_render_journal_article() {
    while (have_posts()) {
        the_post();
        echo '<article class="bf-journal-article bf-wrap"><a class="bf-back" href="' . esc_url(bfnd_page_url('journal')) . '">← 返回飛熊日誌</a><span class="bf-kicker">JOURNAL / ' . esc_html(get_the_date('Y.m.d')) . '</span><h1>' . esc_html(get_the_title()) . '</h1>';
        if (has_post_thumbnail()) { echo '<div class="bf-journal-article-image">'; bfnd_image(get_the_post_thumbnail_url(get_the_ID(), 'full'), get_the_title()); echo '</div>'; }
        echo '<div class="bf-journal-article-body">'; the_content(); echo '</div></article>';
    }
    bfnd_render_cta('還想看看更多木作故事？', '從作品、課程與日常持續探索。', '閱讀飛熊日誌', bfnd_page_url('journal'));
}

function bfnd_render_not_found() {
    echo '<section class="bf-not-found bf-wrap"><span class="bf-kicker">404 / PAGE NOT FOUND</span><h1>找不到這個頁面</h1><p>網址可能已調整。從下方入口繼續探索飛熊入夢。</p><div class="bf-actions">';
    bfnd_button('回到首頁', bfnd_page_url('home'));
    bfnd_button('探索家具', bfnd_page_url('furniture'));
    echo '</div></section>';
}
