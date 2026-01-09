<?php
/**
 * Plugin Name: BF Header - 飛熊入夢導覽列
 * Plugin URI: https://a1.haotaimaker.com/
 * Description: 愛馬仕風格導覽列，可從後台自訂所有選單內容
 * Version: 1.0.0
 * Author: Bear's Fantasyland
 * Text Domain: bf-header
 */

if (!defined('ABSPATH')) {
    exit;
}

class BF_Header_Plugin {

    private static $instance = null;
    private $option_name = 'bf_header_options';

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_shortcode('bf_header', array($this, 'render_header'));
        
        // 自動顯示在每一頁（不需要短代碼）
        add_action('wp_body_open', array($this, 'auto_display_header'), 1);
    }

    /**
     * 自動顯示 Header
     */
    public function auto_display_header() {
        $options = $this->get_options();
        if (!empty($options['auto_display'])) {
            echo $this->render_header();
        }
    }

    public function add_admin_menu() {
        add_menu_page(
            '飛熊導覽列設定',
            '飛熊導覽列',
            'manage_options',
            'bf-header-settings',
            array($this, 'settings_page'),
            'dashicons-menu',
            30
        );
    }

    public function register_settings() {
        register_setting($this->option_name, $this->option_name, array($this, 'sanitize_options'));
    }

    public function get_defaults() {
        return array(
            'logo_text' => '飛熊入夢',
            'logo_en' => "Bear's Fantasyland",
            'announcement' => '免運優惠進行中 — 全館滿 $3,000 享免運服務',
            'show_announcement' => true,
            'auto_display' => true,
            'color_brown' => '#8A6754',
            'color_cream' => '#F5F1EB',
            'color_text' => '#3D3D3D',
            'menu_items' => array(
                array('title' => '最新消息', 'url' => '/news', 'has_submenu' => false, 'submenu_title' => '', 'submenu_items' => array()),
                array('title' => '家具', 'url' => '/furniture', 'has_submenu' => true, 'submenu_title' => 'Furniture Series', 'submenu_items' => array(
                    array('title' => 'Ar 系列', 'url' => '/furniture/ar'),
                    array('title' => 'Be 系列', 'url' => '/furniture/be'),
                    array('title' => 'Ch 系列', 'url' => '/furniture/ch'),
                    array('title' => '經典系列', 'url' => '/furniture/classic'),
                )),
                array('title' => '家居用品', 'url' => '/household', 'has_submenu' => true, 'submenu_title' => 'Household Items', 'submenu_items' => array(
                    array('title' => '椅子', 'url' => '/household/chairs'),
                )),
                array('title' => '飛熊造夢所', 'url' => '/lab', 'has_submenu' => true, 'submenu_title' => 'Woodmaking Lab', 'submenu_items' => array(
                    array('title' => '飛熊入夢 YouTube 頻道', 'url' => '/lab/youtube'),
                    array('title' => '木工旅程 | 課程總覽', 'url' => '/lab/courses'),
                    array('title' => '優質工具', 'url' => '/lab/tools'),
                    array('title' => '飛熊學堂', 'url' => '/lab/school'),
                    array('title' => '木師介紹', 'url' => '/lab/masters'),
                    array('title' => '熊熊速報', 'url' => '/lab/news'),
                    array('title' => '常見問題', 'url' => '/lab/faq'),
                    array('title' => '寫信給我們', 'url' => '/lab/contact'),
                )),
                array('title' => '關於我們', 'url' => '/about', 'has_submenu' => false, 'submenu_title' => '', 'submenu_items' => array()),
                array('title' => '鑑賞', 'url' => '/gallery', 'has_submenu' => false, 'submenu_title' => '', 'submenu_items' => array()),
                array('title' => '空間租借', 'url' => '/space-rental', 'has_submenu' => false, 'submenu_title' => '', 'submenu_items' => array()),
                array('title' => '訂製與運送', 'url' => '/custom-delivery', 'has_submenu' => false, 'submenu_title' => '', 'submenu_items' => array()),
            ),
        );
    }

    public function get_options() {
        $options = get_option($this->option_name);
        return empty($options) ? $this->get_defaults() : wp_parse_args($options, $this->get_defaults());
    }

    public function sanitize_options($input) {
        $sanitized = array();
        $sanitized['logo_text'] = sanitize_text_field($input['logo_text'] ?? '');
        $sanitized['logo_en'] = sanitize_text_field($input['logo_en'] ?? '');
        $sanitized['announcement'] = sanitize_text_field($input['announcement'] ?? '');
        $sanitized['show_announcement'] = !empty($input['show_announcement']);
        $sanitized['auto_display'] = !empty($input['auto_display']);
        $sanitized['color_brown'] = sanitize_hex_color($input['color_brown'] ?? '#8A6754');
        $sanitized['color_cream'] = sanitize_hex_color($input['color_cream'] ?? '#F5F1EB');
        $sanitized['color_text'] = sanitize_hex_color($input['color_text'] ?? '#3D3D3D');
        
        if (!empty($input['menu_items']) && is_array($input['menu_items'])) {
            $sanitized['menu_items'] = array();
            foreach ($input['menu_items'] as $item) {
                $menu_item = array(
                    'title' => sanitize_text_field($item['title'] ?? ''),
                    'url' => esc_url_raw($item['url'] ?? ''),
                    'has_submenu' => !empty($item['has_submenu']),
                    'submenu_title' => sanitize_text_field($item['submenu_title'] ?? ''),
                    'submenu_items' => array()
                );
                if (!empty($item['submenu_items']) && is_array($item['submenu_items'])) {
                    foreach ($item['submenu_items'] as $sub) {
                        $menu_item['submenu_items'][] = array(
                            'title' => sanitize_text_field($sub['title'] ?? ''),
                            'url' => esc_url_raw($sub['url'] ?? '')
                        );
                    }
                }
                $sanitized['menu_items'][] = $menu_item;
            }
        }
        return $sanitized;
    }

    public function settings_page() {
        $options = $this->get_options();
        ?>
        <div class="wrap">
            <h1>🐻 飛熊入夢導覽列設定</h1>
            <p class="description">使用短代碼 <code>[bf_header]</code> 顯示導覽列</p>
            
            <form method="post" action="options.php">
                <?php settings_fields($this->option_name); ?>
                
                <h2>基本設定</h2>
                <table class="form-table">
                    <tr><th>Logo 文字</th><td><input type="text" name="<?php echo $this->option_name; ?>[logo_text]" value="<?php echo esc_attr($options['logo_text']); ?>" class="regular-text"></td></tr>
                    <tr><th>Logo 英文</th><td><input type="text" name="<?php echo $this->option_name; ?>[logo_en]" value="<?php echo esc_attr($options['logo_en']); ?>" class="regular-text"></td></tr>
                    <tr><th>公告文字</th><td><input type="text" name="<?php echo $this->option_name; ?>[announcement]" value="<?php echo esc_attr($options['announcement']); ?>" class="large-text"></td></tr>
                    <tr><th>顯示公告</th><td><label><input type="checkbox" name="<?php echo $this->option_name; ?>[show_announcement]" value="1" <?php checked($options['show_announcement']); ?>> 顯示頂部公告欄</label></td></tr>
                    <tr><th>自動顯示</th><td><label><input type="checkbox" name="<?php echo $this->option_name; ?>[auto_display]" value="1" <?php checked($options['auto_display']); ?>> 在每一頁自動顯示導覽列（不需要短代碼）</label></td></tr>
                </table>
                
                <h2>色彩設定</h2>
                <table class="form-table">
                    <tr><th>主色調 (Brown)</th><td><input type="color" name="<?php echo $this->option_name; ?>[color_brown]" value="<?php echo esc_attr($options['color_brown']); ?>"> <code><?php echo esc_html($options['color_brown']); ?></code></td></tr>
                    <tr><th>背景色 (Cream)</th><td><input type="color" name="<?php echo $this->option_name; ?>[color_cream]" value="<?php echo esc_attr($options['color_cream']); ?>"> <code><?php echo esc_html($options['color_cream']); ?></code></td></tr>
                    <tr><th>文字色</th><td><input type="color" name="<?php echo $this->option_name; ?>[color_text]" value="<?php echo esc_attr($options['color_text']); ?>"> <code><?php echo esc_html($options['color_text']); ?></code></td></tr>
                </table>
                
                <h2>選單項目</h2>
                <?php foreach ($options['menu_items'] as $i => $item) : ?>
                <div style="background:#f9f9f9; padding:15px; margin:10px 0; border:1px solid #ddd;">
                    <strong>選單 #<?php echo $i + 1; ?></strong><br><br>
                    標題: <input type="text" name="<?php echo $this->option_name; ?>[menu_items][<?php echo $i; ?>][title]" value="<?php echo esc_attr($item['title']); ?>" style="width:120px;">
                    連結: <input type="text" name="<?php echo $this->option_name; ?>[menu_items][<?php echo $i; ?>][url]" value="<?php echo esc_attr($item['url']); ?>" style="width:150px;">
                    <label><input type="checkbox" name="<?php echo $this->option_name; ?>[menu_items][<?php echo $i; ?>][has_submenu]" value="1" <?php checked($item['has_submenu']); ?>> 有子選單</label>
                    
                    <?php if ($item['has_submenu']) : ?>
                    <br><br>子選單標題: <input type="text" name="<?php echo $this->option_name; ?>[menu_items][<?php echo $i; ?>][submenu_title]" value="<?php echo esc_attr($item['submenu_title']); ?>" style="width:150px;">
                    <br><br>子選單項目:
                    <?php foreach ($item['submenu_items'] as $j => $sub) : ?>
                    <div style="margin:5px 0 5px 20px;">
                        <input type="text" name="<?php echo $this->option_name; ?>[menu_items][<?php echo $i; ?>][submenu_items][<?php echo $j; ?>][title]" value="<?php echo esc_attr($sub['title']); ?>" placeholder="標題" style="width:180px;">
                        <input type="text" name="<?php echo $this->option_name; ?>[menu_items][<?php echo $i; ?>][submenu_items][<?php echo $j; ?>][url]" value="<?php echo esc_attr($sub['url']); ?>" placeholder="連結" style="width:180px;">
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
                
                <?php submit_button('儲存設定'); ?>
            </form>
        </div>
        <?php
    }

    public function render_header($atts = array()) {
        $o = $this->get_options();
        ob_start();
        ?>
<style>
.bf-header{--bf-brown:<?php echo esc_attr($o['color_brown']); ?>;--bf-cream:<?php echo esc_attr($o['color_cream']); ?>;--bf-text:<?php echo esc_attr($o['color_text']); ?>;--bf-text-light:#7A7A7A;--bf-white:#FFFFFF;position:sticky;top:0;z-index:1000;background:var(--bf-white);font-family:'Noto Sans TC',-apple-system,sans-serif}
.bf-header *{box-sizing:border-box;margin:0;padding:0}
.bf-header-announcement{background:var(--bf-brown);color:var(--bf-white);text-align:center;padding:12px 20px;font-size:12px;letter-spacing:1px}
.bf-header-main{display:flex;align-items:center;justify-content:space-between;padding:0 60px;height:80px;border-bottom:1px solid rgba(0,0,0,0.08)}
.bf-header-logo{text-decoration:none}
.bf-header-logo-text{font-family:'Noto Serif TC',serif;font-size:22px;font-weight:600;letter-spacing:4px;color:var(--bf-brown);display:block}
.bf-header-logo-en{font-family:'Playfair Display',serif;font-size:9px;color:var(--bf-text-light);letter-spacing:2px;text-transform:uppercase;display:block;margin-top:4px}
.bf-header-nav{display:flex;align-items:center;height:100%}
.bf-header-nav-item{position:relative;height:100%;display:flex;align-items:center}
.bf-header-nav-link{display:flex;align-items:center;height:100%;padding:0 28px;text-decoration:none;color:var(--bf-text);font-size:13px;letter-spacing:1px;transition:color 0.3s;position:relative}
.bf-header-nav-link::after{content:'';position:absolute;bottom:0;left:28px;right:28px;height:2px;background:var(--bf-brown);transform:scaleX(0);transition:transform 0.4s}
.bf-header-nav-link:hover{color:var(--bf-brown)}
.bf-header-nav-link:hover::after{transform:scaleX(1)}
.bf-mega-menu{position:absolute;top:100%;left:50%;transform:translateX(-50%);min-width:400px;background:var(--bf-white);box-shadow:0 20px 60px rgba(0,0,0,0.08);opacity:0;visibility:hidden;transition:all 0.4s;padding:60px 80px;z-index:100}
.bf-header-nav-item:hover .bf-mega-menu{opacity:1;visibility:visible}
.bf-mega-menu-title{font-family:'Playfair Display',serif;font-size:11px;color:var(--bf-text-light);text-transform:uppercase;letter-spacing:3px;margin-bottom:40px}
.bf-mega-menu-list{list-style:none}
.bf-mega-menu-list li{margin-bottom:24px}
.bf-mega-menu-link{text-decoration:none;color:var(--bf-text);font-size:14px;letter-spacing:0.5px;transition:color 0.3s;display:inline-block;position:relative}
.bf-mega-menu-link::after{content:'';position:absolute;bottom:-4px;left:0;width:100%;height:1px;background:var(--bf-brown);transform:scaleX(0);transition:transform 0.3s}
.bf-mega-menu-link:hover{color:var(--bf-brown)}
.bf-mega-menu-link:hover::after{transform:scaleX(1)}
.bf-header-actions{display:flex;align-items:center;gap:30px}
.bf-header-action-btn{display:flex;align-items:center;justify-content:center;background:none;border:none;cursor:pointer;color:var(--bf-text);padding:8px;transition:color 0.3s}
.bf-header-action-btn:hover{color:var(--bf-brown)}
.bf-header-action-btn svg{width:22px;height:22px;stroke-width:1.5}
.bf-header-mobile-toggle{display:none;background:none;border:none;cursor:pointer;padding:10px;flex-direction:column;gap:6px}
.bf-header-mobile-toggle span{display:block;width:28px;height:1px;background:var(--bf-text);transition:all 0.3s}
.bf-mobile-menu{position:fixed;top:0;right:-100%;width:100%;max-width:400px;height:100vh;background:var(--bf-white);z-index:2000;transition:right 0.4s;overflow-y:auto}
.bf-mobile-menu.active{right:0}
.bf-mobile-overlay{position:fixed;inset:0;background:rgba(0,0,0,0.4);z-index:1999;opacity:0;visibility:hidden;transition:all 0.4s}
.bf-mobile-overlay.active{opacity:1;visibility:visible}
.bf-mobile-menu-header{display:flex;justify-content:space-between;align-items:center;padding:24px 30px;border-bottom:1px solid rgba(0,0,0,0.08)}
.bf-mobile-menu-title{font-family:'Noto Serif TC',serif;font-size:18px;letter-spacing:2px;color:var(--bf-brown)}
.bf-mobile-menu-close{background:none;border:none;cursor:pointer;padding:8px;color:var(--bf-text)}
.bf-mobile-menu-close svg{width:24px;height:24px}
.bf-mobile-menu-nav{padding:20px 0}
.bf-mobile-menu-link{display:block;padding:20px 30px;text-decoration:none;color:var(--bf-text);font-size:15px;letter-spacing:1px;border-bottom:1px solid rgba(0,0,0,0.05);transition:all 0.3s}
.bf-mobile-menu-link:hover{background:var(--bf-cream);color:var(--bf-brown)}
.bf-mobile-menu-group{border-bottom:1px solid rgba(0,0,0,0.05)}
.bf-mobile-menu-toggle{display:flex;justify-content:space-between;align-items:center;width:100%;padding:20px 30px;background:none;border:none;cursor:pointer;font-family:'Noto Sans TC',sans-serif;font-size:15px;letter-spacing:1px;color:var(--bf-text);text-align:left;transition:all 0.3s}
.bf-mobile-menu-toggle:hover{background:var(--bf-cream);color:var(--bf-brown)}
.bf-mobile-menu-toggle svg{width:18px;height:18px;transition:transform 0.3s}
.bf-mobile-menu-toggle.active svg{transform:rotate(180deg)}
.bf-mobile-menu-sub{max-height:0;overflow:hidden;background:var(--bf-cream);transition:max-height 0.4s}
.bf-mobile-menu-sub.active{max-height:600px}
.bf-mobile-menu-sub a{display:block;padding:16px 30px 16px 50px;text-decoration:none;color:var(--bf-text-light);font-size:14px;transition:all 0.3s}
.bf-mobile-menu-sub a:hover{color:var(--bf-brown);padding-left:56px}
@media(max-width:1200px){.bf-header-main{padding:0 40px}.bf-header-nav-link{padding:0 18px}}
@media(max-width:1024px){.bf-header-nav{display:none}.bf-header-mobile-toggle{display:flex}.bf-header-main{padding:0 20px;height:70px}}
</style>

<header class="bf-header">
<?php if ($o['show_announcement']) : ?>
<div class="bf-header-announcement"><?php echo esc_html($o['announcement']); ?></div>
<?php endif; ?>
<div class="bf-header-main">
<a href="<?php echo home_url(); ?>" class="bf-header-logo">
<span class="bf-header-logo-text"><?php echo esc_html($o['logo_text']); ?></span>
<span class="bf-header-logo-en"><?php echo esc_html($o['logo_en']); ?></span>
</a>
<nav class="bf-header-nav">
<?php foreach ($o['menu_items'] as $item) : ?>
<div class="bf-header-nav-item">
<a href="<?php echo esc_url($item['url']); ?>" class="bf-header-nav-link"><?php echo esc_html($item['title']); ?></a>
<?php if ($item['has_submenu'] && !empty($item['submenu_items'])) : ?>
<div class="bf-mega-menu">
<span class="bf-mega-menu-title"><?php echo esc_html($item['submenu_title']); ?></span>
<ul class="bf-mega-menu-list">
<?php foreach ($item['submenu_items'] as $sub) : ?>
<li><a href="<?php echo esc_url($sub['url']); ?>" class="bf-mega-menu-link"><?php echo esc_html($sub['title']); ?></a></li>
<?php endforeach; ?>
</ul>
</div>
<?php endif; ?>
</div>
<?php endforeach; ?>
</nav>
<div class="bf-header-actions">
<button class="bf-header-action-btn" aria-label="搜尋"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.35-4.35"></path></svg></button>
<button class="bf-header-action-btn" aria-label="帳戶"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg></button>
<button class="bf-header-action-btn" aria-label="購物車"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg></button>
<button class="bf-header-mobile-toggle" aria-label="選單" onclick="bfToggleMobileMenu()"><span></span><span></span><span></span></button>
</div>
</div>
</header>

<div class="bf-mobile-menu" id="bfMobileMenu">
<div class="bf-mobile-menu-header">
<span class="bf-mobile-menu-title">選單</span>
<button class="bf-mobile-menu-close" onclick="bfCloseMobileMenu()" aria-label="關閉"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M18 6 6 18M6 6l12 12"/></svg></button>
</div>
<nav class="bf-mobile-menu-nav">
<?php foreach ($o['menu_items'] as $item) : ?>
<?php if ($item['has_submenu'] && !empty($item['submenu_items'])) : ?>
<div class="bf-mobile-menu-group">
<button class="bf-mobile-menu-toggle" onclick="bfToggleSubmenu(this)"><?php echo esc_html($item['title']); ?><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="m6 9 6 6 6-6"/></svg></button>
<div class="bf-mobile-menu-sub">
<?php foreach ($item['submenu_items'] as $sub) : ?>
<a href="<?php echo esc_url($sub['url']); ?>"><?php echo esc_html($sub['title']); ?></a>
<?php endforeach; ?>
</div>
</div>
<?php else : ?>
<a href="<?php echo esc_url($item['url']); ?>" class="bf-mobile-menu-link"><?php echo esc_html($item['title']); ?></a>
<?php endif; ?>
<?php endforeach; ?>
</nav>
</div>
<div class="bf-mobile-overlay" id="bfMobileOverlay" onclick="bfCloseMobileMenu()"></div>

<script>
function bfToggleMobileMenu(){document.getElementById('bfMobileMenu').classList.add('active');document.getElementById('bfMobileOverlay').classList.add('active');document.body.style.overflow='hidden'}
function bfCloseMobileMenu(){document.getElementById('bfMobileMenu').classList.remove('active');document.getElementById('bfMobileOverlay').classList.remove('active');document.body.style.overflow=''}
function bfToggleSubmenu(btn){var sub=btn.nextElementSibling,isActive=btn.classList.contains('active');document.querySelectorAll('.bf-mobile-menu-toggle').forEach(function(b){b.classList.remove('active');b.nextElementSibling.classList.remove('active')});if(!isActive){btn.classList.add('active');sub.classList.add('active')}}
</script>
        <?php
        return ob_get_clean();
    }
}

BF_Header_Plugin::get_instance();
