<?php if (!defined('ABSPATH')) { exit; } ?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo esc_html(wp_get_document_title()); ?></title>
<?php wp_head(); ?>
</head>
<body <?php body_class('bfnd-body'); ?>>
<?php wp_body_open(); ?>
<a class="bf-skip" href="#bf-main">跳至主要內容</a>
<?php if (function_exists('bfnd_render_header')) { bfnd_render_header(''); } ?>
<main id="bf-main" class="bf-wrap bfft-fallback">
<?php if (have_posts()) : while (have_posts()) : the_post(); ?>
<article <?php post_class(); ?>>
<h1><?php the_title(); ?></h1>
<?php if (is_singular()) { the_content(); } else { the_excerpt(); } ?>
</article>
<?php endwhile; the_posts_pagination(); else : ?>
<h1>找不到內容</h1>
<p>請返回首頁，探索飛熊入夢的木作作品。</p>
<?php endif; ?>
</main>
<?php if (function_exists('bfnd_render_footer')) { bfnd_render_footer(); } ?>
<?php wp_footer(); ?>
</body>
</html>
