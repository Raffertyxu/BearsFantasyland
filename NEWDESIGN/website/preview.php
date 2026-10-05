<?php
/** Local visual preview only. Production reads all content from WordPress. */
if (isset($_GET['preview_zoom']) && $_GET['preview_zoom'] === '25') {
    $frame_query = array_intersect_key($_GET, array_flip(array('page', 'slug', 'work', 'material_choice', 'size_choice', 'preview_options')));
    $frame_query['preview_theme'] = '1';
    $frame_src = 'preview.php?' . http_build_query($frame_query);
    ?><!doctype html><html lang="zh-Hant"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>飛熊入夢｜25% 縮放預覽</title><style>html,body{width:100%;height:100%;margin:0;overflow:hidden}iframe{display:block;width:400vw;height:400vh;border:0;transform:scale(.25);transform-origin:top left}</style></head><body><iframe title="25% 縮放網站預覽" src="<?php echo htmlspecialchars($frame_src, ENT_QUOTES, 'UTF-8'); ?>"></iframe></body></html><?php
    exit;
}
error_reporting(E_ALL);
ini_set('display_errors', '1');
define('ABSPATH', __DIR__ . '/');
define('BFND_DIR', __DIR__ . '/');
define('BFND_URL', '/');
$preview_asset_map = array(
    'theme-style' => __DIR__ . '/../theme/bears-fantasyland/public/style.css',
    'theme-interactions' => __DIR__ . '/../theme/bears-fantasyland/public/interactions.css',
);
if (isset($_GET['preview_asset'], $preview_asset_map[$_GET['preview_asset']])) {
    header('Content-Type: text/css; charset=utf-8');
    readfile($preview_asset_map[$_GET['preview_asset']]);
    exit;
}
$preview_data = json_decode(file_get_contents(__DIR__ . '/data/content.json'), true);
$preview_posts = array();
$id = 1;
foreach ($preview_data['works'] as $item) {
    $preview_posts[$id] = (object) array('ID' => $id, 'post_type' => 'bf_work', 'post_title' => $item['title'], 'post_content' => $item['story'], 'post_excerpt' => $item['tagline'], 'data' => $item);
    $id++;
}
$preview_posts[$id] = (object) array('ID' => $id, 'post_type' => 'bf_lifestyle', 'post_title' => '晨露圓境托盤', 'post_content' => '當晨曦化為曲線，自然紋理凝聚成器。竹的細緻清雅、木的溫潤厚實，各自保留獨有的生命紋理。', 'post_excerpt' => '一器承日常，一圓納天地。', 'data' => array('slug' => 'alba-canvas', 'english' => 'Alba Canvas', 'image' => $preview_data['lifestyle']['gallery'][0], 'gallery' => $preview_data['lifestyle']['gallery']));
$id++;
foreach ($preview_data['courses'] as $item) {
    $content = match ($item['slug']) {
        'cnc' => '最新課程時數、內容、費用與開課資訊，請聯絡我們確認。',
        'open-studio' => '會員方案與費用將另行公告，請聯絡我們確認最新資訊。',
        default => '課程內容、開課日期與報名方式請以品牌公告為準。',
    };
    $preview_posts[$id] = (object) array('ID' => $id, 'post_type' => 'bf_course', 'post_title' => $item['title'], 'post_content' => $content, 'post_excerpt' => '從一堂課開始，親手理解木作。', 'data' => $item);
    $id++;
}
function esc_html($s) { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); }
function esc_attr($s) { return esc_html($s); }
function esc_url($s) { return esc_html($s); }
function esc_textarea($s) { return esc_html($s); }
function absint($value) { return abs((int) $value); }
function bfnd_asset($s) { return '/' . ltrim($s, '/'); }
function bfft_asset($s) { return '/' . ltrim($s, '/'); }
function bfnd_page_url($s) { return '/preview.php?page=' . rawurlencode($s); }
function bfnd_shop_url() { return bfnd_page_url('shop'); }
function get_post_meta($id, $key, $single = true) { global $preview_posts; $p = $preview_posts[$id] ?? null; if (!$p) return ''; $k = substr($key, 6); return $p->data[$k] ?? ''; }
function get_post_type($id) { global $preview_posts; return $preview_posts[$id]->post_type ?? ''; }
function get_the_title($post) { global $preview_posts; return is_object($post) ? $post->post_title : ($preview_posts[$post]->post_title ?? ''); }
function get_the_excerpt($post) { global $preview_posts; return is_object($post) ? $post->post_excerpt : ($preview_posts[$post]->post_excerpt ?? ''); }
function get_post_field($field, $id) { global $preview_posts; return $preview_posts[$id]->$field ?? ''; }
function get_permalink($post) { global $preview_posts; $p = is_object($post) ? $post : ($preview_posts[$post] ?? null); return $p ? '/preview.php?page=' . match($p->post_type) {'bf_work'=>'work','bf_lifestyle'=>'lifestyle-work','bf_course'=>'course'} . '&slug=' . rawurlencode($p->data['slug']) : '#'; }
function get_posts($args) { global $preview_posts; $items = array_values(array_filter($preview_posts, fn($p) => $p->post_type === ($args['post_type'] ?? ''))); if (isset($args['meta_key'])) $items = array_values(array_filter($items, fn($p) => ($p->data[substr($args['meta_key'], 6)] ?? '') === ($args['meta_value'] ?? ''))); if (isset($args['numberposts']) && $args['numberposts'] > 0) $items = array_slice($items, 0, $args['numberposts']); return $items; }
class WP_Query { public array $posts; function __construct($args = array()) { global $preview_posts; $items = array_values(array_filter($preview_posts, fn($p) => $p->post_type === ($args['post_type'] ?? ''))); if (($args['post_type'] ?? '') === 'post') $items = array(); if (isset($args['post__not_in'])) $items = array_values(array_filter($items, fn($p) => !in_array($p->ID, $args['post__not_in']))); if (isset($args['posts_per_page']) && $args['posts_per_page'] > 0) $items = array_slice($items, 0, $args['posts_per_page']); $this->posts = $items; } function have_posts() { return count($this->posts) > 0; } }
function bfnd_work_query($args = array()) { return new WP_Query(array_merge(array('post_type' => 'bf_work'), $args)); }
function bfnd_work_image($id, $size = 'large') { return get_post_meta($id, '_bfnd_image'); }
function bfnd_gallery($id) { return get_post_meta($id, '_bfnd_gallery'); }
function wp_get_post_terms($id, $tax, $args = array()) { global $preview_posts; $d = $preview_posts[$id]->data ?? array(); if ($tax === 'bf_series') return array($d['series'] ?? ''); if ($tax === 'bf_work_cat') return array($d['category'] ?? ''); return array(); }
function get_post_time($format, $gmt, $post) { return 1000000000 + $post->ID; }
function current_user_can($cap) { return true; }
function get_option($key) { return null; }
function wp_get_attachment_image_url($id, $size) { return false; }
function date_i18n($format) { return date($format); }
function admin_url($path = '') { return '#local-preview'; }
function wp_nonce_field($a, $b) { echo '<input type="hidden" name="' . esc_attr($b) . '" value="preview">'; }
function selected($a, $b, $echo = false) { return $a == $b ? ' selected' : ''; }
function add_query_arg($key, $value, $url) { return $url . (str_contains($url, '?') ? '&' : '?') . rawurlencode($key) . '=' . rawurlencode((string) $value); }
function apply_filters($filter, $text) { return '<p>' . esc_html($text) . '</p>'; }
function has_post_thumbnail($post) { return false; }
function get_the_post_thumbnail_url($post, $size) { return ''; }
function get_the_date($format, $post) { return date($format); }
function get_terms($args = array()) { return array(); }
function bfnd_page_post($key) { return null; }
function has_shortcode($content, $tag) { return false; }
function get_term_by($field, $value, $taxonomy) { return false; }
function is_wp_error($value) { return false; }
function wp_kses_post($value) { return (string) $value; }
function wp_strip_all_tags($value) { return strip_tags((string) $value); }
function bfnd_course_registration_action($course_id) { return false; }
function bfnd_course_price_html($course_id) {
    $price = get_post_meta($course_id, '_bfnd_price', true);
    $label = trim((string) get_post_meta($course_id, '_bfnd_price_label', true));
    return $price !== '' ? ($label !== '' ? esc_html($label) . ' ' : '') . 'NT$ ' . esc_html(number_format((int) $price)) : '';
}
require_once __DIR__ . '/src/work-options.php';
$preview_theme = isset($_GET['preview_theme']) && $_GET['preview_theme'] === '1';
if ($preview_theme) { require_once __DIR__ . '/../theme/bears-fantasyland/src/render.php'; }
else { require_once __DIR__ . '/src/render.php'; }
$page = $_GET['page'] ?? 'home';
$post = null;
if (in_array($page, array('work', 'lifestyle-work', 'course'))) {
    foreach ($preview_posts as $candidate) if (($candidate->data['slug'] ?? '') === ($_GET['slug'] ?? '')) $post = $candidate;
}
if (isset($_GET['preview_options']) && $_GET['preview_options'] === '1') {
    // Local-only UI fixture with generic labels, not production specifications.
    $fixture_work_id = $page === 'work' && $post ? $post->ID : absint($_GET['work'] ?? 0);
    if ($fixture_work_id && isset($preview_posts[$fixture_work_id]) && $preview_posts[$fixture_work_id]->post_type === 'bf_work') {
        $fixture_work = $preview_posts[$fixture_work_id];
        $fixture_work->data['material_options'] = "材質選項示範 A\n材質選項示範 B";
        $fixture_work->data['size_options'] = "尺寸選項示範 A\n尺寸選項示範 B";
        $fixture_work->data['size_confirmed'] = '1';
    }
}
?><!doctype html><html lang="zh-Hant"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>飛熊入夢 NEWDESIGN 預覽</title><link rel="stylesheet" href="<?php echo esc_url($preview_theme ? '/preview.php?preview_asset=theme-style' : '/public/style.css'); ?>"><link rel="stylesheet" href="<?php echo esc_url($preview_theme ? '/preview.php?preview_asset=theme-interactions' : '/public/interactions.css'); ?>"><link rel="stylesheet" href="/public/work-options.css"></head><body class="bfnd-body"><?php bfnd_render_header($page); ?><main id="bf-main"><?php
if ($post && $page === 'work') bfnd_render_work($post->ID);
elseif ($post && $page === 'lifestyle-work') bfnd_render_lifestyle_work($post->ID);
elseif ($post && $page === 'course') bfnd_render_course($post->ID);
else {
    switch ($page) {
        case 'furniture': bfnd_render_furniture(); break;
        case 'lifestyle': bfnd_render_lifestyle(); break;
        case 'school': bfnd_render_school(); break;
        case 'story': bfnd_render_story(); break;
        case 'collaboration': bfnd_render_collaboration(); break;
        case 'journal': bfnd_render_journal(); break;
        case 'service': bfnd_render_service(); break;
        default: bfnd_render_home();
    }
}
?></main><?php bfnd_render_footer(); ?><script src="/public/site.js"></script><script src="/public/work-options.js"></script></body></html>
