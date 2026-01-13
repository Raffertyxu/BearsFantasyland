<?php
/**
 * Plugin Name: BF Code Snippets - 代碼片段管理器
 * Plugin URI: https://a1.haotaimaker.com/
 * Description: 在後台編寫 HTML、CSS、JavaScript，儲存後使用短代碼 [bf_code id="xxx"] 插入任何地方
 * Version: 1.0.0
 * Author: Bear's Fantasyland
 * Text Domain: bf-code-snippets
 */

if (!defined('ABSPATH')) exit;

class BF_Code_Snippets {

    private static $instance = null;
    private $option_name = 'bf_code_snippets';

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        add_action('wp_ajax_bf_save_snippet', array($this, 'ajax_save_snippet'));
        add_action('wp_ajax_bf_delete_snippet', array($this, 'ajax_delete_snippet'));
        add_shortcode('bf_code', array($this, 'render_shortcode'));
        
        // 全域 CSS/JS 輸出
        add_action('wp_head', array($this, 'output_global_css'));
        add_action('wp_footer', array($this, 'output_global_js'));
    }

    /**
     * 取得所有片段
     */
    public function get_snippets() {
        return get_option($this->option_name, array());
    }

    /**
     * 儲存片段
     */
    public function save_snippet($id, $data) {
        $snippets = $this->get_snippets();
        $snippets[$id] = $data;
        update_option($this->option_name, $snippets);
    }

    /**
     * 刪除片段
     */
    public function delete_snippet($id) {
        $snippets = $this->get_snippets();
        unset($snippets[$id]);
        update_option($this->option_name, $snippets);
    }

    /**
     * 新增管理選單
     */
    public function add_admin_menu() {
        add_menu_page(
            'BF 代碼片段',
            '📝 代碼片段',
            'manage_options',
            'bf-code-snippets',
            array($this, 'render_admin_page'),
            'dashicons-editor-code',
            80
        );
    }

    /**
     * 管理頁面腳本
     */
    public function admin_scripts($hook) {
        if ($hook !== 'toplevel_page_bf-code-snippets') return;
        ?>
        <style>
            /* ========== BF Code Snippets Admin ========== */
            .bfcs-wrap {
                max-width: 1200px;
                margin: 20px auto;
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            }
            
            .bfcs-header {
                background: linear-gradient(135deg, #2c3e50 0%, #1a252f 100%);
                color: #fff;
                padding: 30px 40px;
                border-radius: 16px 16px 0 0;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            
            .bfcs-header h1 {
                margin: 0;
                font-size: 26px;
                font-weight: 600;
            }
            
            .bfcs-header p {
                margin: 8px 0 0;
                opacity: 0.8;
                font-size: 14px;
            }
            
            .bfcs-add-btn {
                padding: 12px 28px;
                background: #3498db;
                color: #fff;
                border: none;
                border-radius: 8px;
                font-size: 14px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s;
            }
            
            .bfcs-add-btn:hover {
                background: #2980b9;
                transform: translateY(-2px);
            }
            
            .bfcs-content {
                background: #fff;
                border-radius: 0 0 16px 16px;
                box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            }
            
            /* 片段列表 */
            .bfcs-list {
                padding: 30px;
            }
            
            .bfcs-empty {
                text-align: center;
                padding: 60px 20px;
                color: #999;
            }
            
            .bfcs-empty-icon {
                font-size: 60px;
                margin-bottom: 16px;
            }
            
            .bfcs-snippet {
                background: #f8f9fa;
                border-radius: 12px;
                padding: 20px 24px;
                margin-bottom: 16px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                border: 1px solid #eee;
                transition: all 0.3s;
            }
            
            .bfcs-snippet:hover {
                border-color: #3498db;
                box-shadow: 0 4px 12px rgba(52, 152, 219, 0.1);
            }
            
            .bfcs-snippet-info {
                flex: 1;
            }
            
            .bfcs-snippet-name {
                font-size: 16px;
                font-weight: 600;
                color: #333;
                margin-bottom: 6px;
            }
            
            .bfcs-snippet-meta {
                display: flex;
                gap: 16px;
                font-size: 13px;
                color: #888;
            }
            
            .bfcs-snippet-shortcode {
                background: #e8f4fc;
                padding: 4px 12px;
                border-radius: 4px;
                font-family: 'Monaco', 'Consolas', monospace;
                font-size: 12px;
                color: #2980b9;
                cursor: pointer;
            }
            
            .bfcs-snippet-shortcode:hover {
                background: #d4ebf8;
            }
            
            .bfcs-snippet-actions {
                display: flex;
                gap: 10px;
            }
            
            .bfcs-btn {
                padding: 8px 16px;
                border: none;
                border-radius: 6px;
                font-size: 13px;
                font-weight: 500;
                cursor: pointer;
                transition: all 0.2s;
            }
            
            .bfcs-btn-edit {
                background: #3498db;
                color: #fff;
            }
            
            .bfcs-btn-edit:hover {
                background: #2980b9;
            }
            
            .bfcs-btn-delete {
                background: #fff;
                color: #e74c3c;
                border: 1px solid #e74c3c;
            }
            
            .bfcs-btn-delete:hover {
                background: #e74c3c;
                color: #fff;
            }
            
            /* 編輯器 Modal */
            .bfcs-modal-overlay {
                position: fixed;
                inset: 0;
                background: rgba(0,0,0,0.6);
                z-index: 99998;
                display: none;
                align-items: center;
                justify-content: center;
            }
            
            .bfcs-modal-overlay.active {
                display: flex;
            }
            
            .bfcs-modal {
                background: #fff;
                width: 95%;
                max-width: 1000px;
                max-height: 90vh;
                border-radius: 16px;
                overflow: hidden;
                display: flex;
                flex-direction: column;
            }
            
            .bfcs-modal-header {
                padding: 20px 30px;
                background: #f8f9fa;
                border-bottom: 1px solid #eee;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            
            .bfcs-modal-title {
                font-size: 18px;
                font-weight: 600;
                color: #333;
                margin: 0;
            }
            
            .bfcs-modal-close {
                background: none;
                border: none;
                font-size: 24px;
                cursor: pointer;
                color: #888;
                padding: 4px 10px;
            }
            
            .bfcs-modal-close:hover {
                color: #333;
            }
            
            .bfcs-modal-body {
                padding: 30px;
                overflow-y: auto;
                flex: 1;
            }
            
            .bfcs-form-row {
                margin-bottom: 24px;
            }
            
            .bfcs-form-row:last-child {
                margin-bottom: 0;
            }
            
            .bfcs-form-label {
                display: block;
                font-weight: 600;
                color: #333;
                margin-bottom: 8px;
                font-size: 14px;
            }
            
            .bfcs-form-label small {
                font-weight: normal;
                color: #888;
                margin-left: 8px;
            }
            
            .bfcs-input {
                width: 100%;
                padding: 12px 16px;
                border: 1px solid #ddd;
                border-radius: 8px;
                font-size: 15px;
                box-sizing: border-box;
            }
            
            .bfcs-input:focus {
                outline: none;
                border-color: #3498db;
            }
            
            .bfcs-textarea {
                width: 100%;
                min-height: 200px;
                padding: 16px;
                border: 1px solid #ddd;
                border-radius: 8px;
                font-family: 'Monaco', 'Consolas', 'Courier New', monospace;
                font-size: 13px;
                line-height: 1.5;
                resize: vertical;
                box-sizing: border-box;
                background: #1e1e1e;
                color: #d4d4d4;
            }
            
            .bfcs-textarea:focus {
                outline: none;
                border-color: #3498db;
            }
            
            .bfcs-checkbox-row {
                display: flex;
                align-items: center;
                gap: 10px;
                padding: 12px 16px;
                background: #f8f9fa;
                border-radius: 8px;
            }
            
            .bfcs-checkbox-row input {
                width: 18px;
                height: 18px;
                accent-color: #3498db;
            }
            
            .bfcs-modal-footer {
                padding: 20px 30px;
                background: #f8f9fa;
                border-top: 1px solid #eee;
                display: flex;
                justify-content: flex-end;
                gap: 12px;
            }
            
            .bfcs-btn-cancel {
                padding: 12px 28px;
                background: #fff;
                color: #666;
                border: 1px solid #ddd;
                border-radius: 8px;
                font-size: 14px;
                cursor: pointer;
            }
            
            .bfcs-btn-save {
                padding: 12px 28px;
                background: #27ae60;
                color: #fff;
                border: none;
                border-radius: 8px;
                font-size: 14px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s;
            }
            
            .bfcs-btn-save:hover {
                background: #219a52;
            }
            
            /* Tabs */
            .bfcs-tabs {
                display: flex;
                gap: 4px;
                margin-bottom: 16px;
            }
            
            .bfcs-tab {
                padding: 10px 20px;
                background: #e9ecef;
                border: none;
                border-radius: 8px 8px 0 0;
                font-size: 13px;
                font-weight: 500;
                color: #666;
                cursor: pointer;
            }
            
            .bfcs-tab.active {
                background: #1e1e1e;
                color: #fff;
            }
            
            .bfcs-tab-content {
                display: none;
            }
            
            .bfcs-tab-content.active {
                display: block;
            }
            
            .bfcs-toast {
                position: fixed;
                bottom: 30px;
                right: 30px;
                padding: 16px 24px;
                background: #27ae60;
                color: #fff;
                border-radius: 8px;
                font-weight: 500;
                box-shadow: 0 4px 15px rgba(0,0,0,0.2);
                transform: translateY(100px);
                opacity: 0;
                transition: all 0.3s;
                z-index: 99999;
            }
            
            .bfcs-toast.show {
                transform: translateY(0);
                opacity: 1;
            }
        </style>
        <?php
    }

    /**
     * 渲染管理頁面
     */
    public function render_admin_page() {
        $snippets = $this->get_snippets();
        ?>
        <div class="bfcs-wrap">
            <div class="bfcs-header">
                <div>
                    <h1>📝 代碼片段管理器</h1>
                    <p>建立 HTML、CSS、JavaScript 片段，使用短代碼插入任何地方</p>
                </div>
                <button class="bfcs-add-btn" onclick="bfcsOpenEditor()">+ 新增片段</button>
            </div>
            
            <div class="bfcs-content">
                <div class="bfcs-list">
                    <?php if (empty($snippets)): ?>
                    <div class="bfcs-empty">
                        <div class="bfcs-empty-icon">📋</div>
                        <p>還沒有任何代碼片段</p>
                        <p style="font-size:13px;margin-top:8px;">點擊上方「新增片段」開始建立</p>
                    </div>
                    <?php else: ?>
                        <?php foreach ($snippets as $id => $snippet): ?>
                        <div class="bfcs-snippet" data-id="<?php echo esc_attr($id); ?>">
                            <div class="bfcs-snippet-info">
                                <div class="bfcs-snippet-name"><?php echo esc_html($snippet['name']); ?></div>
                                <div class="bfcs-snippet-meta">
                                    <span class="bfcs-snippet-shortcode" onclick="bfcsCopyShortcode('<?php echo esc_attr($id); ?>')" title="點擊複製">[bf_code id="<?php echo esc_attr($id); ?>"]</span>
                                    <?php if (!empty($snippet['global_css'])): ?>
                                    <span>🎨 全域 CSS</span>
                                    <?php endif; ?>
                                    <?php if (!empty($snippet['global_js'])): ?>
                                    <span>⚡ 全域 JS</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="bfcs-snippet-actions">
                                <button class="bfcs-btn bfcs-btn-edit" onclick="bfcsEditSnippet('<?php echo esc_attr($id); ?>')">編輯</button>
                                <button class="bfcs-btn bfcs-btn-delete" onclick="bfcsDeleteSnippet('<?php echo esc_attr($id); ?>')">刪除</button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- 編輯器 Modal -->
        <div class="bfcs-modal-overlay" id="bfcsModal">
            <div class="bfcs-modal">
                <div class="bfcs-modal-header">
                    <h3 class="bfcs-modal-title" id="bfcsModalTitle">新增代碼片段</h3>
                    <button class="bfcs-modal-close" onclick="bfcsCloseEditor()">&times;</button>
                </div>
                <div class="bfcs-modal-body">
                    <input type="hidden" id="bfcsId" value="">
                    
                    <div class="bfcs-form-row">
                        <label class="bfcs-form-label">片段名稱</label>
                        <input type="text" class="bfcs-input" id="bfcsName" placeholder="例如：首頁輪播、自訂按鈕">
                    </div>
                    
                    <div class="bfcs-form-row">
                        <div class="bfcs-tabs">
                            <button class="bfcs-tab active" data-target="tab-html">HTML</button>
                            <button class="bfcs-tab" data-target="tab-css">CSS</button>
                            <button class="bfcs-tab" data-target="tab-js">JavaScript</button>
                        </div>
                        
                        <div id="tab-html" class="bfcs-tab-content active">
                            <textarea class="bfcs-textarea" id="bfcsHtml" placeholder="<!-- 在這裡寫 HTML -->"></textarea>
                        </div>
                        
                        <div id="tab-css" class="bfcs-tab-content">
                            <textarea class="bfcs-textarea" id="bfcsCss" placeholder="/* 在這裡寫 CSS */"></textarea>
                        </div>
                        
                        <div id="tab-js" class="bfcs-tab-content">
                            <textarea class="bfcs-textarea" id="bfcsJs" placeholder="// 在這裡寫 JavaScript"></textarea>
                        </div>
                    </div>
                    
                    <div class="bfcs-form-row">
                        <label class="bfcs-form-label">進階選項</label>
                        <div class="bfcs-checkbox-row">
                            <input type="checkbox" id="bfcsGlobalCss">
                            <label for="bfcsGlobalCss">全域輸出 CSS（自動載入到所有頁面的 &lt;head&gt;）</label>
                        </div>
                        <div class="bfcs-checkbox-row" style="margin-top:8px;">
                            <input type="checkbox" id="bfcsGlobalJs">
                            <label for="bfcsGlobalJs">全域輸出 JavaScript（自動載入到所有頁面的 &lt;footer&gt;）</label>
                        </div>
                    </div>
                </div>
                <div class="bfcs-modal-footer">
                    <button class="bfcs-btn-cancel" onclick="bfcsCloseEditor()">取消</button>
                    <button class="bfcs-btn-save" onclick="bfcsSaveSnippet()">💾 儲存片段</button>
                </div>
            </div>
        </div>
        
        <div class="bfcs-toast" id="bfcsToast"></div>
        
        <script>
        var bfcsSnippets = <?php echo json_encode($snippets); ?>;
        var bfcsAjaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';
        
        // Tab 切換
        document.querySelectorAll('.bfcs-tab').forEach(function(tab) {
            tab.addEventListener('click', function() {
                document.querySelectorAll('.bfcs-tab').forEach(function(t) { t.classList.remove('active'); });
                document.querySelectorAll('.bfcs-tab-content').forEach(function(c) { c.classList.remove('active'); });
                this.classList.add('active');
                document.getElementById(this.getAttribute('data-target')).classList.add('active');
            });
        });
        
        function bfcsOpenEditor(id) {
            document.getElementById('bfcsModal').classList.add('active');
            if (id && bfcsSnippets[id]) {
                var s = bfcsSnippets[id];
                document.getElementById('bfcsModalTitle').textContent = '編輯代碼片段';
                document.getElementById('bfcsId').value = id;
                document.getElementById('bfcsName').value = s.name || '';
                document.getElementById('bfcsHtml').value = s.html || '';
                document.getElementById('bfcsCss').value = s.css || '';
                document.getElementById('bfcsJs').value = s.js || '';
                document.getElementById('bfcsGlobalCss').checked = s.global_css || false;
                document.getElementById('bfcsGlobalJs').checked = s.global_js || false;
            } else {
                document.getElementById('bfcsModalTitle').textContent = '新增代碼片段';
                document.getElementById('bfcsId').value = '';
                document.getElementById('bfcsName').value = '';
                document.getElementById('bfcsHtml').value = '';
                document.getElementById('bfcsCss').value = '';
                document.getElementById('bfcsJs').value = '';
                document.getElementById('bfcsGlobalCss').checked = false;
                document.getElementById('bfcsGlobalJs').checked = false;
            }
        }
        
        function bfcsEditSnippet(id) {
            bfcsOpenEditor(id);
        }
        
        function bfcsCloseEditor() {
            document.getElementById('bfcsModal').classList.remove('active');
        }
        
        function bfcsSaveSnippet() {
            var id = document.getElementById('bfcsId').value || 'snippet_' + Date.now();
            var data = {
                action: 'bf_save_snippet',
                id: id,
                name: document.getElementById('bfcsName').value,
                html: document.getElementById('bfcsHtml').value,
                css: document.getElementById('bfcsCss').value,
                js: document.getElementById('bfcsJs').value,
                global_css: document.getElementById('bfcsGlobalCss').checked ? 1 : 0,
                global_js: document.getElementById('bfcsGlobalJs').checked ? 1 : 0
            };
            
            fetch(bfcsAjaxUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams(data)
            })
            .then(function(r) { return r.json(); })
            .then(function(r) {
                if (r.success) {
                    bfcsShowToast('✓ 片段已儲存');
                    setTimeout(function() { location.reload(); }, 800);
                }
            });
        }
        
        function bfcsDeleteSnippet(id) {
            if (!confirm('確定要刪除這個代碼片段嗎？')) return;
            
            fetch(bfcsAjaxUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ action: 'bf_delete_snippet', id: id })
            })
            .then(function(r) { return r.json(); })
            .then(function(r) {
                if (r.success) {
                    bfcsShowToast('✓ 片段已刪除');
                    setTimeout(function() { location.reload(); }, 800);
                }
            });
        }
        
        function bfcsCopyShortcode(id) {
            var text = '[bf_code id="' + id + '"]';
            navigator.clipboard.writeText(text).then(function() {
                bfcsShowToast('✓ 短代碼已複製');
            });
        }
        
        function bfcsShowToast(msg) {
            var t = document.getElementById('bfcsToast');
            t.textContent = msg;
            t.classList.add('show');
            setTimeout(function() { t.classList.remove('show'); }, 2000);
        }
        </script>
        <?php
    }

    /**
     * AJAX：儲存片段
     */
    public function ajax_save_snippet() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error();
        }
        
        $id = sanitize_text_field($_POST['id']);
        $data = array(
            'name' => sanitize_text_field($_POST['name']),
            'html' => wp_unslash($_POST['html']),
            'css' => wp_unslash($_POST['css']),
            'js' => wp_unslash($_POST['js']),
            'global_css' => !empty($_POST['global_css']),
            'global_js' => !empty($_POST['global_js']),
            'updated' => current_time('mysql'),
        );
        
        $this->save_snippet($id, $data);
        wp_send_json_success(array('id' => $id));
    }

    /**
     * AJAX：刪除片段
     */
    public function ajax_delete_snippet() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error();
        }
        
        $id = sanitize_text_field($_POST['id']);
        $this->delete_snippet($id);
        wp_send_json_success();
    }

    /**
     * 短代碼輸出
     */
    public function render_shortcode($atts) {
        $atts = shortcode_atts(array('id' => ''), $atts);
        if (empty($atts['id'])) return '';
        
        $snippets = $this->get_snippets();
        if (!isset($snippets[$atts['id']])) return '';
        
        $snippet = $snippets[$atts['id']];
        $output = '';
        
        // CSS (inline)
        if (!empty($snippet['css']) && empty($snippet['global_css'])) {
            $output .= '<style>' . $snippet['css'] . '</style>';
        }
        
        // HTML
        if (!empty($snippet['html'])) {
            $output .= $snippet['html'];
        }
        
        // JavaScript (inline)
        if (!empty($snippet['js']) && empty($snippet['global_js'])) {
            $output .= '<script>' . $snippet['js'] . '</script>';
        }
        
        return $output;
    }

    /**
     * 全域 CSS 輸出
     */
    public function output_global_css() {
        $snippets = $this->get_snippets();
        $css = '';
        
        foreach ($snippets as $snippet) {
            if (!empty($snippet['global_css']) && !empty($snippet['css'])) {
                $css .= "/* " . esc_html($snippet['name']) . " */\n";
                $css .= $snippet['css'] . "\n\n";
            }
        }
        
        if ($css) {
            echo "<style id=\"bf-code-snippets-global-css\">\n" . $css . "</style>\n";
        }
    }

    /**
     * 全域 JavaScript 輸出
     */
    public function output_global_js() {
        $snippets = $this->get_snippets();
        $js = '';
        
        foreach ($snippets as $snippet) {
            if (!empty($snippet['global_js']) && !empty($snippet['js'])) {
                $js .= "/* " . esc_html($snippet['name']) . " */\n";
                $js .= $snippet['js'] . "\n\n";
            }
        }
        
        if ($js) {
            echo "<script id=\"bf-code-snippets-global-js\">\n" . $js . "</script>\n";
        }
    }
}

// Initialize
BF_Code_Snippets::get_instance();
