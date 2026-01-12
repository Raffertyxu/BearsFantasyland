<?php
/**
 * Plugin Name: BF 最新消息 (News)
 * Description: 飛熊入夢 - 網格化最新消息顯示器，支援 [bf_news count="6"]
 * Version: 1.0.1
 * Author: BEAR'S FANTASYLAND
 */

if (!defined('ABSPATH')) {
    exit;
}

// ========================================
// 1. FRONTEND CSS
// ========================================
function bf_news_css() {
    return "
    <style>
    .bf-news-section {
        --bf-cream: #F9F7F5;
        --bf-brown: #8A6754;
        --bf-text: #333333;
        --bf-text-muted: #666666;
        --bf-font-display: 'Noto Serif TC', serif;
        --bf-font-body: 'Noto Sans TC', sans-serif;
        padding: 60px 0;
        background-color: transparent;
    }

    .bf-news-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
    }

    .bf-news-header {
        text-align: center;
        margin-bottom: 50px;
    }
    .bf-news-header h2 {
        font-family: var(--bf-font-display);
        font-size: 32px;
        color: var(--bf-text);
        margin: 0 0 10px 0;
        letter-spacing: 0.1em;
    }
    .bf-news-header p {
        font-family: var(--bf-font-display);
        font-size: 14px;
        color: var(--bf-brown);
        text-transform: uppercase;
        letter-spacing: 0.3em;
        margin: 0;
    }

    .bf-news-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 30px;
    }

    .bf-news-card {
        background: #fff;
        border-radius: 4px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        transition: transform 0.4s cubic-bezier(0.2, 1, 0.3, 1), box-shadow 0.4s ease;
        text-decoration: none;
        display: flex;
        flex-direction: column;
    }

    .bf-news-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 20px 40px rgba(138, 103, 84, 0.12);
    }

    .bf-news-thumb {
        width: 100%;
        aspect-ratio: 16/10;
        overflow: hidden;
        background: #eee;
    }

    .bf-news-thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.6s ease;
    }

    .bf-news-card:hover .bf-news-thumb img {
        transform: scale(1.05);
    }

    .bf-news-content {
        padding: 24px;
        flex: 1;
        display: flex;
        flex-direction: column;
    }

    .bf-news-meta {
        font-size: 13px;
        color: var(--bf-brown);
        font-weight: 600;
        letter-spacing: 0.1em;
        margin-bottom: 12px;
        text-transform: uppercase;
        display: block;
    }

    .bf-news-title {
        font-family: var(--bf-font-display);
        font-size: 20px;
        line-height: 1.4;
        color: var(--bf-text);
        margin: 0 0 15px 0;
        font-weight: 600;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .bf-news-excerpt {
        font-family: var(--bf-font-body);
        font-size: 14px;
        color: var(--bf-text-muted);
        line-height: 1.8;
        margin-bottom: 20px;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .bf-news-more {
        margin-top: auto;
        font-size: 13px;
        color: var(--bf-text);
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        font-weight: 500;
        transition: color 0.3s ease;
    }

    .bf-news-card:hover .bf-news-more {
        color: var(--bf-brown);
    }

    .bf-news-more svg {
        margin-left: 8px;
        transition: transform 0.3s ease;
    }

    .bf-news-card:hover .bf-news-more svg {
        transform: translateX(4px);
    }

    @media (max-width: 991px) {
        .bf-news-grid { grid-template-columns: repeat(2, 1fr); }
    }

    @media (max-width: 600px) {
        .bf-news-grid { grid-template-columns: 1fr; }
        .bf-news-section { padding: 40px 0; }
    }
    </style>
    ";
}

// ========================================
// 2. SHORTCODE IMPLEMENTATION
// ========================================
add_shortcode('bf_news', 'bf_news_render_shortcode');

function bf_news_render_shortcode($atts) {
    $args = shortcode_atts([
        'count'    => 3,
        'category' => '',
        'columns'  => 3,
        'title'    => '',
        'subtitle' => ''
    ], $atts);

    // Logic for dynamic title
    $display_title = $args['title'];
    $display_subtitle = $args['subtitle'];

    if (empty($display_title)) {
        if (!empty($args['category'])) {
            $term = get_term_by('slug', $args['category'], 'category');
            $display_title = $term ? $term->name : '最新消息';
        } else {
            $display_title = '最新消息';
        }
    }

    if (empty($display_subtitle)) {
        $display_subtitle = 'LATEST NEWS';
    }

    $query_args = [
        'post_type'      => 'post',
        'posts_per_page' => intval($args['count']),
        'post_status'    => 'publish',
        'orderby'        => 'date',
        'order'          => 'DESC'
    ];

    if (!empty($args['category'])) {
        $query_args['category_name'] = sanitize_text_field($args['category']);
    }

    $query = new WP_Query($query_args);

    if (!$query->have_posts()) {
        return '';
    }

    ob_start();
    echo bf_news_css();
    ?>
    <section class="bf-news-section">
        <div class="bf-news-container">
            <header class="bf-news-header">
                <h2><?php echo esc_html($display_title); ?></h2>
                <p><?php echo esc_html($display_subtitle); ?></p>
            </header>

            <div class="bf-news-grid">
                <?php while ($query->have_posts()): $query->the_post(); ?>
                    <a href="<?php the_permalink(); ?>" class="bf-news-card">
                        <div class="bf-news-thumb">
                            <?php if (has_post_thumbnail()): ?>
                                <?php the_post_thumbnail('medium_large'); ?>
                            <?php else: ?>
                                <img src="https://images.unsplash.com/photo-1544450610-ad597caaf521?q=80&w=800&auto=format&fit=crop" alt="Woodworking Feature">
                            <?php endif; ?>
                        </div>
                        <div class="bf-news-content">
                            <span class="bf-news-meta"><?php echo get_the_date('M d, Y'); ?></span>
                            <h3 class="bf-news-title"><?php the_title(); ?></h3>
                            <div class="bf-news-excerpt">
                                <?php echo wp_trim_words(get_the_excerpt(), 25); ?>
                            </div>
                            <span class="bf-news-more">
                                閱讀更多
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                            </span>
                        </div>
                    </a>
                <?php endwhile; wp_reset_postdata(); ?>
            </div>
        </div>
    </section>
    <?php
    return ob_get_clean();
}
