<?php
/**
 * Plugin Name: BF Header - 飛熊入夢導覽列
 * Plugin URI: https://a1.haotaimaker.com/
 * Description: 愛馬仕風格導覽列，可從後台自訂所有選單內容
 * Version: 1.2.0
 * Author: Bear's Fantasyland
 * Text Domain: bf-header
 */

if (!defined('ABSPATH')) {
    exit;
}

class BF_Header_Plugin
{

    private static $instance = null;
    private $option_name = 'bf_header_options';

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_shortcode('bf_header', array($this, 'render_header'));
        add_action('wp_body_open', array($this, 'auto_display_header'), 1);
    }

    public function enqueue_admin_scripts($hook)
    {
        if ($hook !== 'toplevel_page_bf-header-settings')
            return;
        wp_enqueue_script('jquery-ui-sortable');
        wp_enqueue_script('jquery-ui-accordion');
        wp_enqueue_media();
    }

    public function auto_display_header()
    {
        $options = $this->get_options();
        if (!empty($options['auto_display'])) {
            echo $this->render_header();
        }
    }

    public function add_admin_menu()
    {
        add_menu_page(
            '飛熊導覽列設定',
            '🐻 飛熊導覽列',
            'manage_options',
            'bf-header-settings',
            array($this, 'settings_page'),
            'dashicons-menu',
            30
        );
    }

    public function register_settings()
    {
        register_setting(
            $this->option_name,
            $this->option_name,
            array(
                'type' => 'array',
                'sanitize_callback' => array($this, 'sanitize_options'),
                'default' => $this->get_defaults()
            )
        );
    }

    public function get_defaults()
    {
        return array(
            'logo_text' => '飛熊入夢',
            'logo_en' => "Bear's Fantasyland",
            'logo_image' => '',
            'logo_width' => 150,
            'use_logo_image' => false,
            'announcement' => '免運優惠進行中 — 全館滿 $3,000 享免運服務',
            'show_announcement' => true,
            'auto_display' => true,
            'color_brown' => '#8A6754',
            'color_cream' => '#F5F1EB',
            'color_text' => '#3D3D3D',
            'show_search' => true,
            'show_account' => true,
            'show_cart' => true,
            'show_cart_count' => true,
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

    public function get_options()
    {
        $options = get_option($this->option_name);
        return empty($options) ? $this->get_defaults() : wp_parse_args($options, $this->get_defaults());
    }

    public function sanitize_options($input)
    {
        $sanitized = array();
        $sanitized['logo_text'] = sanitize_text_field($input['logo_text'] ?? '');
        $sanitized['logo_en'] = sanitize_text_field($input['logo_en'] ?? '');
        $sanitized['logo_image'] = esc_url_raw($input['logo_image'] ?? '');
        $sanitized['logo_width'] = absint($input['logo_width'] ?? 150);
        $sanitized['use_logo_image'] = !empty($input['use_logo_image']);
        $sanitized['announcement'] = sanitize_text_field($input['announcement'] ?? '');
        $sanitized['show_announcement'] = !empty($input['show_announcement']);
        $sanitized['auto_display'] = !empty($input['auto_display']);
        $sanitized['color_brown'] = sanitize_hex_color($input['color_brown'] ?? '#8A6754');
        $sanitized['color_cream'] = sanitize_hex_color($input['color_cream'] ?? '#F5F1EB');
        $sanitized['color_text'] = sanitize_hex_color($input['color_text'] ?? '#3D3D3D');
        $sanitized['show_search'] = !empty($input['show_search']);
        $sanitized['show_account'] = !empty($input['show_account']);
        $sanitized['show_cart'] = !empty($input['show_cart']);
        $sanitized['show_cart_count'] = !empty($input['show_cart_count']);

        if (!empty($input['menu_items']) && is_array($input['menu_items'])) {
            $sanitized['menu_items'] = array();
            foreach ($input['menu_items'] as $item) {
                if (empty($item['title'])) continue;
                $menu_item = array(
                    'title' => sanitize_text_field($item['title'] ?? ''),
                    'url' => esc_url_raw($item['url'] ?? ''),
                    'has_submenu' => !empty($item['has_submenu']),
                    'submenu_title' => sanitize_text_field($item['submenu_title'] ?? ''),
                    'submenu_items' => array()
                );
                if (!empty($item['submenu_items']) && is_array($item['submenu_items'])) {
                    foreach ($item['submenu_items'] as $sub) {
                        if (empty($sub['title'])) continue;
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

    public function settings_page()
    {
        $options = $this->get_options();
        $opt = $this->option_name;
        ?>
        <style>
            .bf-admin-wrap{max-width:900px;margin:20px auto;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif}
            .bf-admin-header{background:linear-gradient(135deg,#8A6754 0%,#6B4F3F 100%);color:#fff;padding:30px;border-radius:12px 12px 0 0}
            .bf-admin-header h1{margin:0 0 8px 0;font-size:28px;font-weight:600}
            .bf-admin-header p{margin:0;opacity:0.9;font-size:14px}
            .bf-admin-body{background:#fff;padding:30px;border:1px solid #e0e0e0;border-top:none;border-radius:0 0 12px 12px}
            .bf-section{margin-bottom:35px}
            .bf-section-title{font-size:16px;font-weight:600;color:#333;margin-bottom:20px;padding-bottom:10px;border-bottom:2px solid #8A6754;display:flex;align-items:center;gap:8px}
            .bf-section-title span{font-size:18px}
            .bf-row{display:flex;align-items:center;margin-bottom:15px}
            .bf-row label{width:140px;font-weight:500;color:#555;flex-shrink:0}
            .bf-row input[type="text"],.bf-row input[type="url"]{flex:1;padding:10px 14px;border:1px solid #ddd;border-radius:6px;font-size:14px;transition:border-color 0.2s}
            .bf-row input:focus{outline:none;border-color:#8A6754;box-shadow:0 0 0 3px rgba(138,103,84,0.1)}
            .bf-row input[type="color"]{width:50px;height:40px;border:none;border-radius:6px;cursor:pointer}
            .bf-row .color-preview{margin-left:10px;font-family:monospace;color:#777}
            .bf-checkbox{display:flex;align-items:center;gap:10px;padding:12px 16px;background:#f9f7f5;border-radius:8px;margin-bottom:10px}
            .bf-checkbox input{width:18px;height:18px;accent-color:#8A6754}
            .bf-checkbox label{font-weight:500;color:#444;cursor:pointer}
            .bf-menu-item{background:#faf8f6;border:1px solid #e8e4e0;border-radius:10px;margin-bottom:12px;overflow:hidden}
            .bf-menu-item-header{display:flex;align-items:center;padding:16px 20px;cursor:pointer;transition:background 0.2s}
            .bf-menu-item-header:hover{background:#f5f1eb}
            .bf-menu-item-header .drag-handle{color:#bbb;margin-right:12px;cursor:grab;font-size:16px}
            .bf-menu-item-header .title{font-weight:600;color:#333;flex:1}
            .bf-menu-item-header .toggle-icon{color:#999;transition:transform 0.3s}
            .bf-menu-item.open .toggle-icon{transform:rotate(180deg)}
            .bf-menu-item-body{display:none;padding:20px;border-top:1px solid #e8e4e0;background:#fff}
            .bf-menu-item.open .bf-menu-item-body{display:block}
            .bf-mini-row{display:flex;gap:10px;margin-bottom:12px;align-items:center}
            .bf-mini-row label{width:80px;font-size:13px;color:#666}
            .bf-mini-row input{flex:1;padding:8px 12px;border:1px solid #ddd;border-radius:5px;font-size:13px}
            .bf-submenu-section{margin-top:15px;padding-top:15px;border-top:1px dashed #ddd}
            .bf-submenu-item{display:flex;gap:8px;margin-bottom:8px;align-items:center}
            .bf-submenu-item input{flex:1;padding:8px;border:1px solid #ddd;border-radius:4px;font-size:12px}
            .bf-btn-remove{background:#f5f5f5;border:none;color:#999;cursor:pointer;padding:6px 10px;border-radius:4px;transition:all 0.2s}
            .bf-btn-remove:hover{background:#fee;color:#c00}
            .bf-btn-add{background:#8A6754;color:#fff;border:none;padding:8px 16px;border-radius:6px;cursor:pointer;font-size:13px;transition:background 0.2s}
            .bf-btn-add:hover{background:#6B4F3F}
            .bf-btn-add-sub{background:#f5f1eb;color:#8A6754;border:1px solid #8A6754;padding:6px 12px;border-radius:5px;cursor:pointer;font-size:12px;margin-top:8px}
            .bf-btn-add-sub:hover{background:#8A6754;color:#fff}
            .bf-actions{margin-top:30px;padding-top:20px;border-top:1px solid #eee}
            .bf-submit{background:linear-gradient(135deg,#8A6754 0%,#6B4F3F 100%);color:#fff;border:none;padding:14px 40px;font-size:16px;font-weight:600;border-radius:8px;cursor:pointer;transition:transform 0.2s,box-shadow 0.2s}
            .bf-submit:hover{transform:translateY(-2px);box-shadow:0 4px 12px rgba(138,103,84,0.3)}
            .bf-shortcode-info{background:#f0f8ff;border:1px solid #b8daff;padding:15px 20px;border-radius:8px;margin-bottom:25px}
            .bf-shortcode-info code{background:#fff;padding:4px 10px;border-radius:4px;font-weight:600;color:#0066cc}
        </style>

        <div class="bf-admin-wrap">
            <div class="bf-admin-header">
                <h1>🐻 飛熊入夢導覽列</h1>
                <p>Bear's Fantasyland Header Settings</p>
            </div>
            <div class="bf-admin-body">
                <div class="bf-shortcode-info">💡 使用短代碼 <code>[bf_header]</code> 顯示導覽列，或勾選下方「自動顯示」選項。</div>
                <form method="post" action="options.php" id="bf-settings-form">
                    <?php settings_fields($opt); ?>
                    <div class="bf-section">
                        <div class="bf-section-title"><span>🏷️</span> 基本設定</div>
                        <div class="bf-row"><label>Logo 中文</label><input type="text" name="<?php echo $opt; ?>[logo_text]" value="<?php echo esc_attr($options['logo_text']); ?>"></div>
                        <div class="bf-row"><label>Logo 英文</label><input type="text" name="<?php echo $opt; ?>[logo_en]" value="<?php echo esc_attr($options['logo_en']); ?>"></div>
                        <div class="bf-row"><label>公告文字</label><input type="text" name="<?php echo $opt; ?>[announcement]" value="<?php echo esc_attr($options['announcement']); ?>"></div>
                        <div class="bf-checkbox"><input type="checkbox" id="show_announcement" name="<?php echo $opt; ?>[show_announcement]" value="1" <?php checked($options['show_announcement']); ?>><label for="show_announcement">顯示頂部公告欄</label></div>
                        <div class="bf-checkbox"><input type="checkbox" id="auto_display" name="<?php echo $opt; ?>[auto_display]" value="1" <?php checked($options['auto_display']); ?>><label for="auto_display">在每一頁自動顯示導覽列</label></div>
                    </div>
                    <div class="bf-section">
                        <div class="bf-section-title"><span>🎨</span> 色彩設定</div>
                        <div class="bf-row"><label>主色調</label><input type="color" name="<?php echo $opt; ?>[color_brown]" value="<?php echo esc_attr($options['color_brown']); ?>" onchange="this.nextElementSibling.textContent=this.value"><span class="color-preview"><?php echo esc_html($options['color_brown']); ?></span></div>
                        <div class="bf-row"><label>背景色</label><input type="color" name="<?php echo $opt; ?>[color_cream]" value="<?php echo esc_attr($options['color_cream']); ?>" onchange="this.nextElementSibling.textContent=this.value"><span class="color-preview"><?php echo esc_html($options['color_cream']); ?></span></div>
                        <div class="bf-row"><label>文字色</label><input type="color" name="<?php echo $opt; ?>[color_text]" value="<?php echo esc_attr($options['color_text']); ?>" onchange="this.nextElementSibling.textContent=this.value"><span class="color-preview"><?php echo esc_html($options['color_text']); ?></span></div>
                    </div>
                    <div class="bf-section">
                        <div class="bf-section-title"><span>🖼️</span> Logo 圖片設定</div>
                        <div class="bf-checkbox"><input type="checkbox" id="use_logo_image" name="<?php echo $opt; ?>[use_logo_image]" value="1" <?php checked($options['use_logo_image']); ?>><label for="use_logo_image">使用圖片取代文字 Logo</label></div>
                        <div class="bf-row"><label>Logo 圖片</label><input type="text" id="logo_image_url" name="<?php echo $opt; ?>[logo_image]" value="<?php echo esc_attr($options['logo_image']); ?>" placeholder="請選擇或輸入圖片網址" style="flex:2;"><button type="button" class="bf-btn-add" onclick="bfSelectLogoImage()" style="margin-left:10px;">選擇圖片</button></div>
                        <?php if (!empty($options['logo_image'])): ?><div class="bf-row" style="margin-top:-5px;"><label></label><img src="<?php echo esc_url($options['logo_image']); ?>" style="max-height:60px;border-radius:4px;border:1px solid #ddd;"></div><?php endif; ?>
                        <div class="bf-row"><label>Logo 寬度</label><input type="number" name="<?php echo $opt; ?>[logo_width]" value="<?php echo esc_attr($options['logo_width']); ?>" style="width:100px;" min="50" max="300"> px</div>
                    </div>
                    <div class="bf-section">
                        <div class="bf-section-title"><span>🛒</span> WooCommerce 整合</div>
                        <p style="color:#666;font-size:13px;margin-bottom:15px;">控制右上角的搜尋、帳戶、購物車圖示，會自動連結到 WooCommerce 頁面。</p>
                        <div class="bf-checkbox"><input type="checkbox" id="show_search" name="<?php echo $opt; ?>[show_search]" value="1" <?php checked($options['show_search']); ?>><label for="show_search">🔍 顯示搜尋圖示</label></div>
                        <div class="bf-checkbox"><input type="checkbox" id="show_account" name="<?php echo $opt; ?>[show_account]" value="1" <?php checked($options['show_account']); ?>><label for="show_account">👤 顯示帳戶圖示（連結到「我的帳戶」）</label></div>
                        <div class="bf-checkbox"><input type="checkbox" id="show_cart" name="<?php echo $opt; ?>[show_cart]" value="1" <?php checked($options['show_cart']); ?>><label for="show_cart">🛒 顯示購物車圖示（連結到購物車頁面）</label></div>
                        <div class="bf-checkbox" style="margin-left:30px;"><input type="checkbox" id="show_cart_count" name="<?php echo $opt; ?>[show_cart_count]" value="1" <?php checked($options['show_cart_count']); ?>><label for="show_cart_count">顯示購物車商品數量</label></div>
                    </div>
                    <div class="bf-section">
                        <div class="bf-section-title"><span>🧭</span> 選單項目 <span style="font-size:12px;color:#999;font-weight:400;margin-left:auto;">點擊展開編輯</span></div>
                        <div class="bf-menu-list" id="bf-menu-list">
                            <?php foreach ($options['menu_items'] as $i => $item): ?>
                            <div class="bf-menu-item" data-index="<?php echo $i; ?>">
                                <div class="bf-menu-item-header" onclick="bfToggleMenuItem(this)"><span class="drag-handle">☰</span><span class="title"><?php echo esc_html($item['title']) ?: '（未命名）'; ?></span><span class="toggle-icon">▼</span></div>
                                <div class="bf-menu-item-body">
                                    <div class="bf-mini-row"><label>標題</label><input type="text" name="<?php echo $opt; ?>[menu_items][<?php echo $i; ?>][title]" value="<?php echo esc_attr($item['title']); ?>" oninput="bfUpdateTitle(this)"></div>
                                    <div class="bf-mini-row"><label>連結</label><input type="text" name="<?php echo $opt; ?>[menu_items][<?php echo $i; ?>][url]" value="<?php echo esc_attr($item['url']); ?>" placeholder="/page 或 https://..."></div>
                                    <div class="bf-mini-row"><label></label><label style="width:auto;display:flex;align-items:center;gap:8px;cursor:pointer;"><input type="checkbox" name="<?php echo $opt; ?>[menu_items][<?php echo $i; ?>][has_submenu]" value="1" <?php checked($item['has_submenu']); ?> onchange="bfToggleSubmenu(this)"> 有子選單</label><button type="button" class="bf-btn-remove" onclick="bfRemoveMenuItem(this)" style="margin-left:auto;">刪除此項</button></div>
                                    <?php if ($item['has_submenu']): ?>
                                    <div class="bf-submenu-section">
                                        <div class="bf-mini-row"><label>子選單標題</label><input type="text" name="<?php echo $opt; ?>[menu_items][<?php echo $i; ?>][submenu_title]" value="<?php echo esc_attr($item['submenu_title']); ?>" placeholder="e.g., Furniture Series"></div>
                                        <div class="bf-submenu-list">
                                            <?php foreach ($item['submenu_items'] as $j => $sub): ?>
                                            <div class="bf-submenu-item"><input type="text" name="<?php echo $opt; ?>[menu_items][<?php echo $i; ?>][submenu_items][<?php echo $j; ?>][title]" value="<?php echo esc_attr($sub['title']); ?>" placeholder="子項目標題"><input type="text" name="<?php echo $opt; ?>[menu_items][<?php echo $i; ?>][submenu_items][<?php echo $j; ?>][url]" value="<?php echo esc_attr($sub['url']); ?>" placeholder="/page 或 https://..."><button type="button" class="bf-btn-remove" onclick="this.parentElement.remove()">×</button></div>
                                            <?php endforeach; ?>
                                        </div>
                                        <button type="button" class="bf-btn-add-sub" onclick="bfAddSubItem(this, <?php echo $i; ?>)">+ 新增子項目</button>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="bf-btn-add" onclick="bfAddMenuItem()">+ 新增選單項目</button>
                    </div>
                    <div class="bf-actions"><button type="submit" class="bf-submit">💾 儲存設定</button></div>
                </form>
            </div>
        </div>
        <script>
        var bfMenuIndex = <?php echo count($options['menu_items']); ?>;
        var bfOptName = '<?php echo $opt; ?>';
        function bfToggleMenuItem(header){header.parentElement.classList.toggle('open')}
        function bfUpdateTitle(input){var titleSpan=input.closest('.bf-menu-item').querySelector('.bf-menu-item-header .title');titleSpan.textContent=input.value||'（未命名）'}
        function bfRemoveMenuItem(btn){if(confirm('確定要刪除此選單項目嗎？')){btn.closest('.bf-menu-item').remove()}}
        function bfAddMenuItem(){var html='<div class="bf-menu-item open" data-index="'+bfMenuIndex+'"><div class="bf-menu-item-header" onclick="bfToggleMenuItem(this)"><span class="drag-handle">☰</span><span class="title">（新項目）</span><span class="toggle-icon">▼</span></div><div class="bf-menu-item-body" style="display:block;"><div class="bf-mini-row"><label>標題</label><input type="text" name="'+bfOptName+'[menu_items]['+bfMenuIndex+'][title]" value="" oninput="bfUpdateTitle(this)"></div><div class="bf-mini-row"><label>連結</label><input type="text" name="'+bfOptName+'[menu_items]['+bfMenuIndex+'][url]" value="" placeholder="/page 或 https://..."></div><div class="bf-mini-row"><label></label><label style="width:auto;display:flex;align-items:center;gap:8px;cursor:pointer;"><input type="checkbox" name="'+bfOptName+'[menu_items]['+bfMenuIndex+'][has_submenu]" value="1" onchange="bfToggleSubmenu(this)"> 有子選單</label><button type="button" class="bf-btn-remove" onclick="bfRemoveMenuItem(this)" style="margin-left:auto;">刪除此項</button></div></div></div>';document.getElementById('bf-menu-list').insertAdjacentHTML('beforeend',html);bfMenuIndex++}
        function bfToggleSubmenu(checkbox){var body=checkbox.closest('.bf-menu-item-body');var existing=body.querySelector('.bf-submenu-section');var idx=checkbox.closest('.bf-menu-item').dataset.index;if(checkbox.checked&&!existing){var html='<div class="bf-submenu-section"><div class="bf-mini-row"><label>子選單標題</label><input type="text" name="'+bfOptName+'[menu_items]['+idx+'][submenu_title]" value="" placeholder="e.g., Furniture Series"></div><div class="bf-submenu-list"></div><button type="button" class="bf-btn-add-sub" onclick="bfAddSubItem(this,'+idx+')">+ 新增子項目</button></div>';body.insertAdjacentHTML('beforeend',html)}else if(!checkbox.checked&&existing){existing.remove()}}
        function bfAddSubItem(btn,parentIdx){var list=btn.previousElementSibling;var subIdx=list.children.length;var html='<div class="bf-submenu-item"><input type="text" name="'+bfOptName+'[menu_items]['+parentIdx+'][submenu_items]['+subIdx+'][title]" value="" placeholder="子項目標題"><input type="text" name="'+bfOptName+'[menu_items]['+parentIdx+'][submenu_items]['+subIdx+'][url]" value="" placeholder="/page 或 https://..."><button type="button" class="bf-btn-remove" onclick="this.parentElement.remove()">×</button></div>';list.insertAdjacentHTML('beforeend',html)}
        function bfSelectLogoImage(){var mediaUploader=wp.media({title:'選擇 Logo 圖片',button:{text:'使用此圖片'},multiple:false});mediaUploader.on('select',function(){var attachment=mediaUploader.state().get('selection').first().toJSON();document.getElementById('logo_image_url').value=attachment.url});mediaUploader.open()}
        </script>
        <?php
    }

    public function render_header($atts = array())
    {
        $o = $this->get_options();
        ob_start();
        ?>
<style>
/* ========== Admin Bar Fix ========== */
body.admin-bar .bf-header{top:32px !important}
@media screen and (max-width:782px){body.admin-bar .bf-header{top:46px !important}}
@media screen and (max-width:600px){body.admin-bar .bf-header{top:0 !important}}

/* ========== CSS Isolation with !important ========== */
.bf-header{--bf-brown:<?php echo esc_attr($o['color_brown']); ?>;--bf-cream:<?php echo esc_attr($o['color_cream']); ?>;--bf-text:<?php echo esc_attr($o['color_text']); ?>;--bf-text-light:#7A7A7A;--bf-white:#FFFFFF;position:sticky !important;top:0 !important;z-index:99999 !important;background:var(--bf-white) !important;font-family:'Noto Sans TC',-apple-system,sans-serif !important}
.bf-header,.bf-header *{box-sizing:border-box !important}
.bf-header-announcement{background:var(--bf-brown) !important;color:var(--bf-white) !important;text-align:center !important;padding:12px 20px !important;font-size:12px !important;letter-spacing:1px !important;margin:0 !important}
.bf-header-main{display:flex !important;align-items:center !important;justify-content:space-between !important;padding:0 60px !important;height:80px !important;border-bottom:1px solid rgba(0,0,0,0.08) !important;background:var(--bf-white) !important;margin:0 !important}
.bf-header-logo{text-decoration:none !important;display:block !important}
.bf-header-logo-text{font-family:'Noto Serif TC',serif !important;font-size:22px !important;font-weight:600 !important;letter-spacing:4px !important;color:var(--bf-brown) !important;display:block !important;margin:0 !important;padding:0 !important}
.bf-header-logo-en{font-family:'Playfair Display',serif !important;font-size:9px !important;color:var(--bf-text-light) !important;letter-spacing:2px !important;text-transform:uppercase !important;display:block !important;margin-top:4px !important;padding:0 !important}
.bf-header-nav{display:flex !important;align-items:center !important;height:100% !important;list-style:none !important;margin:0 !important;padding:0 !important}
.bf-header-nav-item{position:relative !important;height:100% !important;display:flex !important;align-items:center !important;margin:0 !important;padding:0 !important}
.bf-header-nav-link{display:flex !important;align-items:center !important;height:100% !important;padding:0 28px !important;text-decoration:none !important;color:var(--bf-text) !important;font-size:13px !important;letter-spacing:1px !important;transition:color 0.3s !important;position:relative !important;background:transparent !important;border:none !important;margin:0 !important}
.bf-header-nav-link::after{content:'' !important;position:absolute !important;bottom:0 !important;left:28px !important;right:28px !important;height:2px !important;background:var(--bf-brown) !important;transform:scaleX(0) !important;transition:transform 0.4s !important}
.bf-header-nav-link:hover{color:var(--bf-brown) !important}
.bf-header-nav-link:hover::after{transform:scaleX(1) !important}
.bf-mega-menu{position:absolute !important;top:100% !important;left:50% !important;transform:translateX(-50%) !important;min-width:400px !important;background:var(--bf-white) !important;box-shadow:0 20px 60px rgba(0,0,0,0.08) !important;opacity:0 !important;visibility:hidden !important;transition:all 0.4s !important;padding:60px 80px !important;z-index:100 !important}
.bf-header-nav-item:hover .bf-mega-menu{opacity:1 !important;visibility:visible !important}
.bf-mega-menu-title{font-family:'Playfair Display',serif !important;font-size:11px !important;color:var(--bf-text-light) !important;text-transform:uppercase !important;letter-spacing:3px !important;margin-bottom:40px !important;display:block !important}
.bf-mega-menu-list{list-style:none !important;margin:0 !important;padding:0 !important}
.bf-mega-menu-list li{margin-bottom:24px !important;padding:0 !important}
.bf-mega-menu-link{text-decoration:none !important;color:var(--bf-text) !important;font-size:14px !important;letter-spacing:0.5px !important;transition:color 0.3s !important;display:inline-block !important;position:relative !important;background:transparent !important;border:none !important;padding:0 !important;margin:0 !important}
.bf-mega-menu-link::after{content:'' !important;position:absolute !important;bottom:-4px !important;left:0 !important;width:100% !important;height:1px !important;background:var(--bf-brown) !important;transform:scaleX(0) !important;transition:transform 0.3s !important}
.bf-mega-menu-link:hover{color:var(--bf-brown) !important}
.bf-mega-menu-link:hover::after{transform:scaleX(1) !important}
.bf-header-actions{display:flex !important;align-items:center !important;gap:30px !important;margin:0 !important;padding:0 !important}
.bf-header-action-btn{display:flex !important;align-items:center !important;justify-content:center !important;background:none !important;border:none !important;cursor:pointer !important;color:var(--bf-text) !important;padding:8px !important;transition:color 0.3s !important;margin:0 !important}
.bf-header-action-btn:hover{color:var(--bf-brown) !important}
.bf-header-action-btn svg{width:22px !important;height:22px !important;stroke-width:1.5 !important}
.bf-header-cart{position:relative !important;text-decoration:none !important}
.bf-cart-count{position:absolute !important;top:-4px !important;right:-4px !important;background:var(--bf-brown) !important;color:var(--bf-white) !important;font-size:10px !important;font-weight:600 !important;min-width:18px !important;height:18px !important;border-radius:50% !important;display:flex !important;align-items:center !important;justify-content:center !important;line-height:1 !important}
.bf-header-mobile-toggle{display:none !important;background:none !important;border:none !important;cursor:pointer !important;padding:10px !important;flex-direction:column !important;gap:6px !important;margin:0 !important}
.bf-header-mobile-toggle span{display:block !important;width:28px !important;height:1px !important;background:var(--bf-text) !important;transition:all 0.3s !important;margin:0 !important;padding:0 !important}
.bf-mobile-menu{position:fixed !important;top:0 !important;right:-100% !important;width:100% !important;max-width:400px !important;height:100vh !important;height:100dvh !important;background:#fff !important;z-index:999999 !important;transition:right 0.4s ease !important;overflow-y:auto !important;box-shadow:-10px 0 30px rgba(0,0,0,0.1) !important;visibility:hidden !important}
.bf-mobile-menu.active{right:0 !important;visibility:visible !important}
.bf-mobile-overlay{position:fixed !important;inset:0 !important;background:rgba(0,0,0,0.4) !important;z-index:999998 !important;opacity:0 !important;visibility:hidden !important;transition:all 0.4s !important}
.bf-mobile-overlay.active{opacity:1 !important;visibility:visible !important}
.bf-mobile-menu-header{display:flex !important;justify-content:space-between !important;align-items:center !important;padding:16px 24px !important;border-bottom:1px solid rgba(0,0,0,0.08) !important;margin:0 !important}
.bf-mobile-menu-title{font-family:'Noto Serif TC',serif !important;font-size:18px !important;letter-spacing:2px !important;color:var(--bf-brown) !important;margin:0 !important;padding:0 !important}
.bf-mobile-menu-close{background:none !important;border:none !important;cursor:pointer !important;padding:8px !important;color:var(--bf-text) !important;margin:0 !important}
.bf-mobile-menu-close svg{width:24px !important;height:24px !important}
.bf-mobile-menu-nav{padding:8px 0 !important;margin:0 !important}
.bf-mobile-menu-link{display:block !important;padding:14px 24px !important;text-decoration:none !important;color:var(--bf-text) !important;font-size:14px !important;letter-spacing:1px !important;border-bottom:1px solid rgba(0,0,0,0.05) !important;transition:all 0.3s !important;background:transparent !important;margin:0 !important}
.bf-mobile-menu-link:hover{background:var(--bf-cream) !important;color:var(--bf-brown) !important}
.bf-mobile-menu-group{border-bottom:1px solid rgba(0,0,0,0.05) !important;margin:0 !important;padding:0 !important}
.bf-mobile-menu-toggle{display:flex !important;justify-content:space-between !important;align-items:center !important;width:100% !important;padding:14px 24px !important;background:none !important;border:none !important;border-radius:0 !important;cursor:pointer !important;font-family:'Noto Sans TC',sans-serif !important;font-size:15px !important;letter-spacing:1px !important;color:var(--bf-text) !important;text-align:left !important;transition:all 0.3s !important;margin:0 !important}
.bf-mobile-menu-toggle:hover{background:var(--bf-cream) !important;color:var(--bf-brown) !important}
.bf-mobile-menu-toggle svg{width:18px !important;height:18px !important;transition:transform 0.3s !important}
.bf-mobile-menu-toggle.active svg{transform:rotate(180deg) !important}
.bf-mobile-menu-sub{max-height:0 !important;overflow:hidden !important;background:var(--bf-cream) !important;transition:max-height 0.4s !important;margin:0 !important;padding:0 !important}
.bf-mobile-menu-sub.active{max-height:600px !important}
.bf-mobile-menu-sub a{display:block !important;padding:16px 30px 16px 50px !important;text-decoration:none !important;color:var(--bf-text-light) !important;font-size:14px !important;transition:all 0.3s !important;background:transparent !important;margin:0 !important}
.bf-mobile-menu-sub a:hover{color:var(--bf-brown) !important;padding-left:56px !important}
@media(max-width:1200px){.bf-header-main{padding:0 40px !important}.bf-header-nav-link{padding:0 18px !important}}
@media(max-width:1024px){.bf-header-nav{display:none !important}.bf-header-mobile-toggle{display:flex !important}.bf-header-main{padding:0 20px !important;height:70px !important}}

/* Search Modal */
.bf-search-modal{position:fixed !important;inset:0 !important;z-index:9999999 !important;opacity:0 !important;visibility:hidden !important;transition:all 0.4s ease !important}
.bf-search-modal.active{opacity:1 !important;visibility:visible !important}
.bf-search-overlay{position:absolute !important;inset:0 !important;background:linear-gradient(135deg,rgba(138,103,84,0.95) 0%,rgba(61,61,61,0.98) 100%) !important;backdrop-filter:blur(8px) !important}
.bf-search-container{position:absolute !important;top:50% !important;left:50% !important;transform:translate(-50%,-50%) scale(0.95) !important;width:90% !important;max-width:580px !important;transition:transform 0.4s cubic-bezier(0.16,1,0.3,1) !important}
.bf-search-modal.active .bf-search-container{transform:translate(-50%,-50%) scale(1) !important}
.bf-search-close{position:absolute !important;top:-60px !important;right:0 !important;width:48px !important;height:48px !important;background:rgba(255,255,255,0.1) !important;border:1px solid rgba(255,255,255,0.2) !important;border-radius:50% !important;color:#fff !important;font-size:24px !important;cursor:pointer !important;display:flex !important;align-items:center !important;justify-content:center !important;transition:all 0.3s !important}
.bf-search-close:hover{background:rgba(255,255,255,0.2) !important;transform:rotate(90deg) !important}
.bf-search-title{text-align:center !important;color:rgba(255,255,255,0.9) !important;font-family:'Noto Serif TC',serif !important;font-size:14px !important;letter-spacing:4px !important;margin-bottom:24px !important;text-transform:uppercase !important}
.bf-search-form{display:flex !important;background:#fff !important;border-radius:60px !important;overflow:hidden !important;box-shadow:0 25px 80px rgba(0,0,0,0.4) !important}
.bf-search-input{flex:1 !important;padding:22px 32px !important;font-size:16px !important;border:none !important;outline:none !important;font-family:'Noto Sans TC',sans-serif !important;letter-spacing:0.5px !important;background:transparent !important}
.bf-search-input::placeholder{color:#aaa !important}
.bf-search-submit{padding:0 28px !important;background:linear-gradient(135deg,#8A6754 0%,#6B4F3F 100%) !important;border:none !important;cursor:pointer !important;transition:all 0.3s !important}
.bf-search-submit:hover{background:linear-gradient(135deg,#9A7764 0%,#7B5F4F 100%) !important}
.bf-search-submit svg{width:22px !important;height:22px !important;stroke:#fff !important}
.bf-search-hint{text-align:center !important;color:rgba(255,255,255,0.5) !important;font-size:12px !important;margin-top:20px !important;letter-spacing:1px !important}
</style>

<header class="bf-header">
<?php if ($o['show_announcement']): ?><div class="bf-header-announcement"><?php echo esc_html($o['announcement']); ?></div><?php endif; ?>
<div class="bf-header-main">
<a href="<?php echo home_url(); ?>" class="bf-header-logo">
<?php if ($o['use_logo_image'] && !empty($o['logo_image'])): ?>
<img src="<?php echo esc_url($o['logo_image']); ?>" alt="<?php echo esc_attr($o['logo_text']); ?>" style="width:<?php echo esc_attr($o['logo_width']); ?>px;height:auto;">
<?php else: ?>
<span class="bf-header-logo-text"><?php echo esc_html($o['logo_text']); ?></span>
<span class="bf-header-logo-en"><?php echo esc_html($o['logo_en']); ?></span>
<?php endif; ?>
</a>
<nav class="bf-header-nav">
<?php foreach ($o['menu_items'] as $item): ?>
<div class="bf-header-nav-item">
<a href="<?php echo esc_url($item['url']); ?>" class="bf-header-nav-link"><?php echo esc_html($item['title']); ?></a>
<?php if ($item['has_submenu'] && !empty($item['submenu_items'])): ?>
<div class="bf-mega-menu">
<span class="bf-mega-menu-title"><?php echo esc_html($item['submenu_title']); ?></span>
<ul class="bf-mega-menu-list">
<?php foreach ($item['submenu_items'] as $sub): ?>
<li><a href="<?php echo esc_url($sub['url']); ?>" class="bf-mega-menu-link"><?php echo esc_html($sub['title']); ?></a></li>
<?php endforeach; ?>
</ul>
</div>
<?php endif; ?>
</div>
<?php endforeach; ?>
</nav>
<div class="bf-header-actions">
<?php if ($o['show_search']): ?><button type="button" class="bf-header-action-btn" aria-label="搜尋" onclick="bfOpenSearch()"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.35-4.35"></path></svg></button><?php endif; ?>
<?php if ($o['show_account']): ?><a href="<?php echo function_exists('wc_get_page_permalink') ? esc_url(wc_get_page_permalink('myaccount')) : home_url('/my-account'); ?>" class="bf-header-action-btn" aria-label="帳戶"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg></a><?php endif; ?>
<?php if ($o['show_cart']): ?>
<button type="button" class="bf-header-action-btn bf-header-cart" aria-label="購物車" onclick="if(typeof bfOpenFlyCart==='function'){bfOpenFlyCart()}else{window.location='<?php echo function_exists('wc_get_cart_url') ? esc_url(wc_get_cart_url()) : home_url('/cart'); ?>'}">
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
<?php if ($o['show_cart_count'] && function_exists('WC') && WC()->cart): $cart_count = WC()->cart->get_cart_contents_count(); if ($cart_count > 0): ?><span class="bf-cart-count"><?php echo $cart_count; ?></span><?php endif; endif; ?>
</button>
<?php endif; ?>
<button class="bf-header-mobile-toggle" aria-label="選單" onclick="bfToggleMobileMenu()"><span></span><span></span><span></span></button>
</div>
</div>
</header>

<div class="bf-mobile-menu" id="bfMobileMenu">
<div class="bf-mobile-menu-header"><span class="bf-mobile-menu-title">選單</span><button class="bf-mobile-menu-close" onclick="bfCloseMobileMenu()" aria-label="關閉"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M18 6 6 18M6 6l12 12"/></svg></button></div>
<nav class="bf-mobile-menu-nav">
<?php foreach ($o['menu_items'] as $item): ?>
<?php if ($item['has_submenu'] && !empty($item['submenu_items'])): ?>
<div class="bf-mobile-menu-group">
<button class="bf-mobile-menu-toggle" onclick="bfToggleSubmenu(this)"><?php echo esc_html($item['title']); ?><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="m6 9 6 6 6-6"/></svg></button>
<div class="bf-mobile-menu-sub"><?php foreach ($item['submenu_items'] as $sub): ?><a href="<?php echo esc_url($sub['url']); ?>"><?php echo esc_html($sub['title']); ?></a><?php endforeach; ?></div>
</div>
<?php else: ?>
<a href="<?php echo esc_url($item['url']); ?>" class="bf-mobile-menu-link"><?php echo esc_html($item['title']); ?></a>
<?php endif; ?>
<?php endforeach; ?>
</nav>
</div>
<div class="bf-mobile-overlay" id="bfMobileOverlay" onclick="bfCloseMobileMenu()"></div>

<div class="bf-search-modal" id="bfSearchModal">
<div class="bf-search-overlay" onclick="bfCloseSearch()"></div>
<div class="bf-search-container">
<button type="button" class="bf-search-close" onclick="bfCloseSearch()" aria-label="關閉">&times;</button>
<p class="bf-search-title">搜尋</p>
<form action="<?php echo home_url(); ?>" method="get" class="bf-search-form">
<input type="text" name="s" id="bfSearchInput" class="bf-search-input" placeholder="輸入關鍵字..." autocomplete="off">
<button type="submit" class="bf-search-submit"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.35-4.35"></path></svg></button>
</form>
<p class="bf-search-hint">按 Enter 搜尋 · 按 Esc 關閉</p>
</div>
</div>

<script>
function bfToggleMobileMenu(){document.getElementById('bfMobileMenu').classList.add('active');document.getElementById('bfMobileOverlay').classList.add('active');document.body.style.overflow='hidden'}
function bfCloseMobileMenu(){document.getElementById('bfMobileMenu').classList.remove('active');document.getElementById('bfMobileOverlay').classList.remove('active');document.body.style.overflow=''}
function bfToggleSubmenu(btn){var sub=btn.nextElementSibling,isActive=btn.classList.contains('active');document.querySelectorAll('.bf-mobile-menu-toggle').forEach(function(b){b.classList.remove('active');b.nextElementSibling.classList.remove('active')});if(!isActive){btn.classList.add('active');sub.classList.add('active')}}
function bfOpenSearch(){document.getElementById('bfSearchModal').classList.add('active');document.body.style.overflow='hidden';setTimeout(function(){document.getElementById('bfSearchInput').focus()},100)}
function bfCloseSearch(){document.getElementById('bfSearchModal').classList.remove('active');document.body.style.overflow=''}
document.addEventListener('keydown',function(e){if(e.key==='Escape')bfCloseSearch()});
</script>
        <?php
        return ob_get_clean();
    }
}

BF_Header_Plugin::get_instance();
