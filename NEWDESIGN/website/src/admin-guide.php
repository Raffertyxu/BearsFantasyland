<?php
if (!defined('ABSPATH')) { exit; }

// Editor help: a dashboard card grid ("我想要…") and a 使用教學 page with numbered steps per task.
// Native APIs only: wp_add_dashboard_widget and add_dashboard_page.

function bfnd_guide_url($task = '') {
    return admin_url('index.php?page=bfnd-guide') . ($task ? '#' . $task : '');
}

function bfnd_guide_tasks() {
    $u = function ($path) { return admin_url($path); };
    return array(
        'work' => array(
            'icon' => 'dashicons-admin-home', 'title' => '上架或修改家具作品', 'summary' => '家具頁的作品卡與作品內頁。',
            'where' => '左側選單「家具作品」',
            'steps' => array(
                '點左側<b>家具作品</b>。要新增就按上方<b>新增作品</b>；要修改就點作品名稱。',
                '最上方填<b>作品名稱</b>，下面大框填<b>作品故事</b>（作品頁「01 / STORY」那段）。',
                '往下到<b>飛熊入夢作品資料</b>：填<b>英文名稱</b>、<b>一句話介紹</b>、<b>作品類型</b>、<b>木材／材質</b>。<b>可選木材／材質</b>一行一個木種（例如「胡桃木」換行「櫻桃木」），客人就能在作品頁點選並帶進詢問表單。',
                '照片：<b>作品圖片</b>欄按<b>從媒體庫選取照片</b>，第 1 張是主圖、第 2 張是作品故事圖、第 3 張起是工藝細節，可拖曳排序。',
                '尺寸：填<b>參考尺寸</b>；要讓客人選尺寸，在<b>可選尺寸</b>一行一個，並勾<b>尺寸資料已核對，可以在網站顯示</b>。',
                '右側勾選<b>家具分類</b>與<b>作品系列</b>（作品系列就是家具頁的系列下拉）。',
                '按右上角藍色<b>發佈</b>（修改時是<b>更新</b>）。',
            ),
            'tips' => array('刪除：在作品列表把滑鼠移到作品上，按<b>移至回收桶</b>，30 天內可在「回收桶」還原。', '想讓作品可以直接購買：在<b>對應的商店商品</b>選擇商品，作品頁會出現「前往選購」。'),
            'links' => array('新增作品' => $u('post-new.php?post_type=bf_work'), '所有作品' => $u('edit.php?post_type=bf_work'), '家具分類' => $u('edit-tags.php?taxonomy=bf_work_cat&post_type=bf_work'), '作品系列' => $u('edit-tags.php?taxonomy=bf_series&post_type=bf_work')),
        ),
        'journal' => array(
            'icon' => 'dashicons-edit', 'title' => '發一篇飛熊日誌', 'summary' => '日誌文章，最新 3 篇也會出現在首頁與合作頁。',
            'where' => '左側選單「飛熊日誌」',
            'steps' => array(
                '點左側<b>飛熊日誌</b> → <b>新增日誌文章</b>。',
                '填標題，在下方編輯區寫內文（按 <b>+</b> 可以插入圖片）。',
                '右側<b>文章</b>分頁 → <b>分類</b>勾一個（卡片會顯示第一個分類）。',
                '右側<b>精選圖片</b> → 設定封面照片（沒有的話卡片會沒有圖）。',
                '按右上角<b>發佈</b>。',
            ),
            'tips' => array('不想公開：改成<b>草稿</b>，或移至回收桶。', '日誌頁最上方的標題與主圖不在這裡，在<b>飛熊日誌 → 日誌頁面設定</b>。'),
            'links' => array('新增日誌文章' => $u('post-new.php'), '所有日誌文章' => $u('edit.php'), '分類' => $u('edit-tags.php?taxonomy=category'), '日誌頁面設定' => $u('edit.php?page=bfnd-layout-journal')),
        ),
        'product' => array(
            'icon' => 'dashicons-cart', 'title' => '上架商品、改價格、設定木種', 'summary' => '商品選購頁與商品頁。',
            'where' => '左側選單「商品」',
            'steps' => array(
                '點左側<b>商品</b>。新增按<b>新增商品</b>；修改就點商品名稱。',
                '填商品名稱與下方大框的<b>商品介紹</b>（商品頁「01 / ABOUT」）。',
                '往下到<b>商品資料</b>：只有一種規格選<b>簡單商品</b>，在「一般」填<b>原價</b>與<b>特價</b>。',
                '要讓客人選木種：商品資料改<b>可變商品</b> → <b>屬性</b>分頁新增「木種」，值用 <b>|</b> 隔開（例如 胡桃木 | 櫻桃木），勾<b>用於變化</b> → 儲存屬性 → <b>變化</b>分頁按「產生變化」，逐一填價格。',
                '<b>屬性</b>分頁再加「尺寸」「表面處理」等（不要勾用於變化），會顯示在商品頁「02 / SPECIFICATION」。',
                '右側<b>商品圖片</b>放主圖、<b>商品圖庫</b>放其他照片，勾選<b>商品分類</b>，按<b>發佈／更新</b>。',
            ),
            'tips' => array('改價格只要做第 1、3 步（可變商品在「變化」裡逐一改）。', '暫停販售：<b>庫存</b>分頁改成「缺貨」，或把商品改成草稿。'),
            'links' => array('新增商品' => $u('post-new.php?post_type=product'), '所有商品' => $u('edit.php?post_type=product'), '商品分類' => $u('edit-tags.php?taxonomy=product_cat&post_type=product')),
        ),
        'course' => array(
            'icon' => 'dashicons-welcome-learn-more', 'title' => '修改課程、開新梯次與名額', 'summary' => '木作學堂頁、課程頁、頁首「木作學堂」選單。',
            'where' => '左側選單「木作課程」與「商品」',
            'steps' => array(
                '<b>改課程介紹</b>：左側<b>木作課程</b> → 點課程名稱 → 改內容、時數、程度等欄位 → <b>更新</b>。',
                '<b>開新梯次或改價格名額</b>：左側<b>商品</b> → 找名稱有「課程報名」的商品（例如「木工基礎入門班｜課程報名」）→ <b>變化</b>分頁。',
                '新梯次：<b>屬性</b>分頁的「梯次」加一個值（例如 2027 年 1 月梯）→ 儲存屬性 → <b>變化</b>分頁「新增變化」→ 選該梯次，填價格；勾<b>管理庫存</b>，<b>庫存數量</b>就是名額。',
                '按<b>更新</b>。課程頁的按鈕會自動變成「選擇梯次／方案」。',
                '<b>新開一門課</b>：先在<b>木作課程 → 新增</b>建立課程；再到<b>商品 → 新增商品</b>建立課程商品，右側勾<b>將此商品作為課程報名／會員方案收款項目</b>（會自動歸到「木作課程」分類，並且不在商店列出）；最後回課程頁，在「WooCommerce 課程／會員方案商品」選這個商品。',
            ),
            'tips' => array('課程發佈後會自動出現在學堂頁與頁首下拉選單，不用另外改選單。', '梯次額滿時名額歸零，客人就無法再選那個梯次。'),
            'links' => array('所有課程' => $u('edit.php?post_type=bf_course'), '新增課程' => $u('post-new.php?post_type=bf_course'), '課程商品' => $u('edit.php?post_type=product&product_cat=woodworking-courses')),
        ),
        'order' => array(
            'icon' => 'dashicons-clipboard', 'title' => '處理訂單', 'summary' => '客人付款後的訂單、出貨、課程報名。',
            'where' => '左側選單「WooCommerce → 訂單」',
            'steps' => array(
                '點左側 <b>WooCommerce → 訂單</b>，最新的在最上面。',
                '點訂單編號看內容：商品、木種或梯次、客人聯絡資料、付款方式。',
                '出貨或課程確認後，右側<b>訂單狀態</b>改成<b>已完成</b> → <b>更新</b>，客人會收到通知信。',
            ),
            'tips' => array('「保留」通常是 ATM／超商代碼還沒繳費。', '要退款請到綠界後台與訂單頁同步處理。'),
            'links' => array('訂單' => $u('admin.php?page=wc-orders')),
        ),
        'inquiry' => array(
            'icon' => 'dashicons-email-alt', 'title' => '查看客人詢問', 'summary' => '作品、商品、課程、合作詢問表單。',
            'where' => '左側選單「作品、課程與合作詢問」',
            'steps' => array(
                '有新詢問時，網站管理員信箱會收到通知信，點信裡的連結即可。',
                '或點左側<b>作品、課程與合作詢問</b>，點標題看內容（詢問的作品、木種、聯絡方式都在裡面）。',
                '回覆客人後，可把該筆移至回收桶。',
            ),
            'tips' => array('詢問只有管理員看得到。'),
            'links' => array('查看詢問' => $u('edit.php?post_type=bf_inquiry')),
        ),
        'layout' => array(
            'icon' => 'dashicons-layout', 'title' => '修改頁面上的文字與圖片', 'summary' => '首頁、家具、生活木作、學堂、品牌故事、合作提案、購買與服務。',
            'where' => '左側選單「網站版面」',
            'steps' => array(
                '點左側<b>網站版面</b> → 點要改的頁面（例如<b>品牌故事</b>）。',
                '頁面依網站上的順序分成區塊，每個區塊以標題表示（例如「源於熱愛（5 個欄位）」），點開後直接改文字；圖片按<b>從媒體庫選取</b>。',
                '不想顯示某個區塊：勾選該區塊的<b>隱藏整個區塊</b>。',
                '按最下方<b>儲存此頁版面</b>，再按上方<b>查看前台 ↗</b>確認。',
            ),
            'tips' => array('改壞了可以按該欄位旁的<b>恢復預設文字</b>或<b>恢復預設圖</b>。', '作品、課程、日誌、商品這些「一筆一筆」的內容不在這裡改，請用各自的選單。'),
            'links' => array('網站版面' => $u('admin.php?page=bfnd-layout-overview')),
        ),
        'banner' => array(
            'icon' => 'dashicons-images-alt2', 'title' => '更換首頁與各頁輪播圖', 'summary' => '首頁、家具、生活木作、學堂最上方的大圖輪播。',
            'where' => '左側選單「網站版面 → Banner 輪播」',
            'steps' => array(
                '點左側<b>網站版面 → Banner 輪播</b>。',
                '每個頁面一區（首頁主視覺、家具作品、生活木作…）。按<b>從媒體庫選取照片</b>新增，用每張圖下方的 <b>←</b> <b>→</b> 調整順序，按<b>移除</b>拿掉。',
                '按最下方<b>儲存全部 Banner</b>。',
            ),
            'tips' => array('建議圖片寬度至少 2560px，大螢幕才不會模糊。'),
            'links' => array('Banner 輪播' => $u('admin.php?page=bfnd-banner-manager')),
        ),
        'menu' => array(
            'icon' => 'dashicons-menu', 'title' => '修改頁首選單與頁尾', 'summary' => '頁首選單、手機選單、頁尾連結、標語、社群與 LINE 網址。',
            'where' => '左側選單「外觀 → 選單」與「外觀 → 自訂」',
            'steps' => array(
                '<b>改連結</b>：點<b>外觀 → 選單</b> → 上方「選擇要編輯的選單」選名稱開頭是<b>飛熊入夢｜</b>的選單 → 按<b>選擇</b>。',
                '左邊勾選頁面或填「自訂連結」按<b>新增至選單</b>；右邊拖曳調整順序，點項目旁的箭頭可改名或<b>移除</b>。',
                '按<b>儲存選單</b>。',
                '<b>改頁尾文字與社群網址</b>：點<b>外觀 → 自訂 → 飛熊入夢頁首頁尾</b>，填標語、Maker 文字、IG／FB／YouTube／LINE 網址 → <b>發佈</b>。留空就使用原本內容。',
            ),
            'tips' => array('頁首選單裡只要有「木作學堂」，課程下拉選單就會自動出現。'),
            'links' => array('選單' => $u('nav-menus.php'), '頁尾文字與社群網址' => $u('customize.php?autofocus[section]=bf_header_footer')),
        ),
        'media' => array(
            'icon' => 'dashicons-format-image', 'title' => '上傳或管理照片', 'summary' => '所有上傳的圖片與檔案。',
            'where' => '左側選單「媒體」',
            'steps' => array(
                '點左側<b>媒體 → 新增媒體檔案</b>，把照片拖進來。',
                '點照片可填<b>替代文字</b>（描述照片內容，對搜尋與無障礙有幫助）。',
            ),
            'tips' => array('刪除照片前先確認沒有作品、商品或頁面在用，否則那裡會變成空白。'),
            'links' => array('媒體庫' => $u('upload.php'), '上傳照片' => $u('media-new.php')),
        ),
    );
}

function bfnd_guide_styles() {
    return '<style>
.bfnd-tasks{display:grid;grid-template-columns:repeat(auto-fill,minmax(210px,1fr));gap:10px;margin:4px 0 0}
.bfnd-task{border:1px solid #dcdcde;border-radius:6px;padding:12px;background:#fff;display:flex;flex-direction:column;gap:6px}
.bfnd-task h3{margin:0;font-size:14px;display:flex;align-items:center;gap:6px}.bfnd-task h3 .dashicons{color:#775640}
.bfnd-task p{margin:0;color:#646970;font-size:12px;flex:1}.bfnd-task .bfnd-btns{display:flex;gap:6px;flex-wrap:wrap}
.bfnd-guide-page{max-width:980px}.bfnd-guide-toc{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:6px;margin:16px 0 24px;padding:0;list-style:none}
.bfnd-guide-toc a{display:flex;align-items:center;gap:6px;padding:8px 10px;background:#fff;border:1px solid #dcdcde;border-radius:4px;text-decoration:none}
.bfnd-guide-card{background:#fff;border:1px solid #dcdcde;border-radius:6px;padding:20px 24px;margin:0 0 18px;scroll-margin-top:40px}
.bfnd-guide-card h2{margin:0 0 4px;display:flex;align-items:center;gap:8px}.bfnd-guide-card h2 .dashicons{color:#775640}
.bfnd-guide-card .bfnd-where{margin:0 0 14px;color:#646970}.bfnd-guide-card ol{margin:0 0 14px 1.4em}.bfnd-guide-card ol li{margin:0 0 8px;line-height:1.7}
.bfnd-guide-card .bfnd-tip{background:#f6f2ec;border-left:4px solid #775640;padding:8px 12px;margin:0 0 14px}.bfnd-guide-card .bfnd-tip p{margin:4px 0}
</style>';
}

add_action('wp_dashboard_setup', function () {
    if (!current_user_can('edit_posts')) { return; }
    wp_add_dashboard_widget('bfnd_admin_guide', '飛熊入夢｜我想要…', 'bfnd_render_admin_guide', null, null, 'normal', 'high');
});

function bfnd_render_admin_guide() {
    echo bfnd_guide_styles() . '<p style="margin-top:0">點「看教學」會一步一步說明；熟悉之後直接點「前往」。完整教學：<a href="' . esc_url(bfnd_guide_url()) . '">控制台 → 飛熊入夢使用教學</a></p><div class="bfnd-tasks">';
    foreach (bfnd_guide_tasks() as $key => $task) {
        $first = reset($task['links']);
        echo '<div class="bfnd-task"><h3><span class="dashicons ' . esc_attr($task['icon']) . '" aria-hidden="true"></span>' . esc_html($task['title']) . '</h3><p>' . esc_html($task['summary']) . '</p><div class="bfnd-btns"><a class="button button-primary" href="' . esc_url(bfnd_guide_url($key)) . '">看教學</a><a class="button" href="' . esc_url($first) . '">前往</a></div></div>';
    }
    echo '</div>';
}

add_action('admin_menu', function () {
    add_dashboard_page('飛熊入夢使用教學', '飛熊入夢使用教學', 'edit_posts', 'bfnd-guide', 'bfnd_render_guide_page');
});

function bfnd_render_guide_page() {
    if (!current_user_can('edit_posts')) { wp_die('權限不足'); }
    $tasks = bfnd_guide_tasks();
    $kses = array('b' => array());
    echo bfnd_guide_styles() . '<div class="wrap bfnd-guide-page"><h1>飛熊入夢使用教學</h1><p>先在下面找到你想做的事，照著步驟點就好。粗體字是畫面上要點的按鈕或欄位名稱。</p><ul class="bfnd-guide-toc">';
    foreach ($tasks as $key => $task) { echo '<li><a href="#' . esc_attr($key) . '"><span class="dashicons ' . esc_attr($task['icon']) . '" aria-hidden="true"></span>' . esc_html($task['title']) . '</a></li>'; }
    echo '</ul>';
    foreach ($tasks as $key => $task) {
        echo '<section class="bfnd-guide-card" id="' . esc_attr($key) . '"><h2><span class="dashicons ' . esc_attr($task['icon']) . '" aria-hidden="true"></span>' . esc_html($task['title']) . '</h2><p class="bfnd-where">在哪裡：' . esc_html($task['where']) . '　｜　改的是：' . esc_html($task['summary']) . '</p><ol>';
        foreach ($task['steps'] as $step) { echo '<li>' . wp_kses($step, $kses) . '</li>'; }
        echo '</ol>';
        if (!empty($task['tips'])) { echo '<div class="bfnd-tip"><strong>注意</strong>'; foreach ($task['tips'] as $tip) { echo '<p>' . wp_kses($tip, $kses) . '</p>'; } echo '</div>'; }
        echo '<p>'; foreach ($task['links'] as $label => $url) { echo '<a class="button' . ($url === reset($task['links']) ? ' button-primary' : '') . '" href="' . esc_url($url) . '">' . esc_html($label) . '</a> '; }
        echo '<a href="#wpbody-content" style="margin-left:8px">回到目錄 ↑</a></p></section>';
    }
    echo '</div>';
}

// 外觀 → 選單: the 木作學堂 course dropdown is built from 木作課程 posts, not from a menu.
add_action('admin_notices', function () {
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen || $screen->id !== 'nav-menus') { return; }
    echo '<div class="notice notice-info"><p><strong>頁首「木作學堂」的課程下拉選單是自動產生的，不在這裡設定。</strong>它會列出所有已發佈的木作課程（實體／線上分開），新增、改名或下架課程後會自動更新。這裡只要保留一個連到木作學堂頁的項目，下拉就會出現。</p><p><a class="button" href="' . esc_url(admin_url('edit.php?post_type=bf_course')) . '">前往木作課程</a> <a class="button" href="' . esc_url(bfnd_guide_url('menu')) . '">選單教學</a></p></div>';
});
