<?php
if (!defined('ABSPATH')) { exit; }

// Dashboard widget: one place that says where each part of the site is edited, with
// how to add / edit / delete and direct links. Uses the native dashboard widget API.
function bfnd_admin_guide_rows() {
    $u = function ($path) { return admin_url($path); };
    return array(
        array('家具作品', '家具頁的作品卡與作品內頁（名稱、照片、規格、木材選項、尺寸選項）',
            '左側「家具作品」→ 新增／點作品名稱修改／滑鼠移到作品上按「移至回收桶」。照片用「精選圖片」與作品圖集；木材選項一行一個。',
            array('所有作品' => $u('edit.php?post_type=bf_work'), '新增作品' => $u('post-new.php?post_type=bf_work'), '家具分類' => $u('edit-tags.php?taxonomy=bf_work_cat&post_type=bf_work'), '作品系列' => $u('edit-tags.php?taxonomy=bf_series&post_type=bf_work'))),
        array('生活木作', '生活木作頁的作品',
            '左側「生活木作」→ 新增／修改／移至回收桶。',
            array('所有生活木作' => $u('edit.php?post_type=bf_lifestyle'), '新增' => $u('post-new.php?post_type=bf_lifestyle'))),
        array('木作課程', '木作學堂頁的課程卡、課程內頁、頁首「木作學堂」下拉選單',
            '左側「木作課程」→ 新增／修改／移至回收桶。發佈後會自動出現在學堂頁與頁首下拉。要能購買：先到「商品」建立課程商品並勾選「木作課程／會員方案商品」，再回課程頁的「WooCommerce 課程／會員方案商品」選它。',
            array('所有課程' => $u('edit.php?post_type=bf_course'), '新增課程' => $u('post-new.php?post_type=bf_course'), '課程商品（梯次、價格、名額）' => $u('edit.php?post_type=product'))),
        array('商品', '商品選購頁、商品頁、價格、木種（可變商品的「屬性／變化」）、庫存',
            '左側「商品」→ 新增／修改／移至回收桶。要讓客人選木種：商品資料選「可變商品」→「屬性」加「木種」並勾「用於變化」→「變化」填各木種價格。',
            array('所有商品' => $u('edit.php?post_type=product'), '新增商品' => $u('post-new.php?post_type=product'), '商品分類' => $u('edit-tags.php?taxonomy=product_cat&post_type=product'), '訂單' => $u('admin.php?page=wc-orders'))),
        array('飛熊日誌', '日誌文章（首頁、合作頁會自動顯示最新 3 篇）與日誌頁頁首',
            '左側「飛熊日誌」→ 新增日誌文章（記得設定精選圖片與分類）／修改／移至回收桶。日誌頁最上方的標題與主圖在「飛熊日誌 → 日誌頁面設定」。',
            array('所有日誌文章' => $u('edit.php'), '新增日誌文章' => $u('post-new.php'), '分類' => $u('edit-tags.php?taxonomy=category'), '日誌頁面設定' => $u('edit.php?page=bfnd-layout-journal'))),
        array('各頁固定文字與圖片', '首頁、家具、生活木作、木作學堂、品牌故事、合作提案、購買與服務頁上的標題、段落、圖片、區塊顯示',
            '左側「網站版面」→ 選頁面 → 直接改欄位 → 儲存；「恢復預設」可回到原始內容。首頁等頁面的輪播圖在「網站版面 → Banner 輪播」。',
            array('網站版面' => $u('admin.php?page=bfnd-layout-overview'), 'Banner 輪播' => $u('admin.php?page=bfnd-banner-manager'))),
        array('頁首與頁尾', '頁首選單、手機選單、頁尾連結、政策連結、頁尾標語、社群與 LINE 網址',
            '連結：「外觀 → 選單」選「飛熊入夢｜…」的選單，拖曳排序、新增、移除後按「儲存選單」。文字與網址：「外觀 → 自訂 → 飛熊入夢頁首頁尾」，留空就用預設內容。',
            array('選單' => $u('nav-menus.php'), '頁尾文字與社群網址' => $u('customize.php?autofocus[section]=bf_header_footer'))),
        array('詢問表單', '作品、商品、課程、合作詢問（客人從網站送出的表單）',
            '左側「作品、課程與合作詢問」查看；處理完可移至回收桶。新詢問會寄信到網站管理員信箱。',
            array('查看詢問' => $u('edit.php?post_type=bf_inquiry'))),
        array('圖片與檔案', '所有上傳的照片',
            '左側「媒體」→ 新增媒體檔案上傳；點圖片可改替代文字。刪除前確認沒有頁面在用。',
            array('媒體庫' => $u('upload.php'))),
    );
}

add_action('wp_dashboard_setup', function () {
    if (!current_user_can('edit_posts')) { return; }
    wp_add_dashboard_widget('bfnd_admin_guide', '飛熊入夢後台操作指南（要改什麼、去哪裡改）', 'bfnd_render_admin_guide', null, null, 'normal', 'high');
});

function bfnd_render_admin_guide() {
    echo '<style>.bfnd-guide table{width:100%;border-collapse:collapse}.bfnd-guide th,.bfnd-guide td{text-align:left;vertical-align:top;padding:10px 8px;border-top:1px solid #dcdcde}.bfnd-guide th{width:22%;font-weight:600}.bfnd-guide small{display:block;color:#646970;margin-top:3px;font-weight:400}.bfnd-guide .bfnd-guide-links{margin-top:6px;display:flex;flex-wrap:wrap;gap:6px}</style>';
    echo '<div class="bfnd-guide"><p>先找到想改的東西，再點右邊的按鈕直接過去。<strong>新增</strong>按「新增」，<strong>修改</strong>點名稱，<strong>刪除</strong>把滑鼠移到該列按「移至回收桶」（回收桶裡還能還原）。</p><table>';
    foreach (bfnd_admin_guide_rows() as $row) {
        echo '<tr><th>' . esc_html($row[0]) . '<small>' . esc_html($row[1]) . '</small></th><td>' . esc_html($row[2]) . '<div class="bfnd-guide-links">';
        foreach ($row[3] as $label => $url) { echo '<a class="button" href="' . esc_url($url) . '">' . esc_html($label) . '</a>'; }
        echo '</div></td></tr>';
    }
    echo '</table></div>';
}
