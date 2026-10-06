# 自製功能 vs. WordPress／WooCommerce 原生功能盤點（2026-10-06）

範圍：外掛 `NEWDESIGN/website/`（0.5.36 來源，分支 `claude/product-page-design-2cb8d1`，commit `5d487b3`）與主題 `NEWDESIGN/theme/bears-fantasyland/`（1.3.39 來源）。dist ZIP 與根目錄舊站不在範圍內。
性質：**只讀盤點，沒有修改任何程式、沒有登入正式站、沒有查資料庫**。文中「正式站已完成／未設定」都是引用 `DEPLOYMENT.md`、`HANDOFF.md` 的文件紀錄，不是本次重新驗證的結果。行數用 `wc -l` 計算原始碼。

---

## 一、給業主的總結（不需要懂程式）

目前網站的自製程式約 **7,000 行**（外掛 PHP 約 2,700、主題 PHP 約 870，其餘是 JS／CSS）。其中大約 **3,000～3,300 行可以刪除，或改用 WordPress／WooCommerce 已經內建的功能**。外掛安裝包另有一份 **約 40 MB 的圖片副本**，跟主題裡的圖片重複。

程式少了，需要維護、可能出錯、可能被攻擊的地方就少。分三類說明：

1. **可以直接刪（約 2,500 行＋40 MB）**：當初「把舊網站搬到新版」用的一次性搬家工具、早就不用的舊編輯器程式，以及「萬一主題沒啟用時」才會用到的一整套備用版面。這些功能在正式站都已經用完，或者根本沒有掛上去執行。
2. **改用 WooCommerce 內建功能（約 650 行）**：
   - **作品加入購物車**：另一個還沒上線的版本（1.3.36）重新做了一套「作品頁直接買」。可是商品頁從 1.3.37 開始，已經用 WooCommerce 的「可變商品＋木種」完成選木種、加入購物車和詢問。**建議不要上線那套，改成作品頁放一顆「前往選購」按鈕，連到對應的商品頁。**
   - **課程付款後寄 YouTube 連結**：WooCommerce 內建的「購買備註」可以在顧客付款後，把一段文字放進訂單頁和通知信。現在正式課程商品還沒建立，**是改用內建功能成本最低的時機**。
   - 作品頁的「可選木材／尺寸詢價」：商品頁已經做到同樣的事（選木種後帶入詢問），而且正式站沒有任何作品設定過這些選項。請業主決定還要不要保留。
3. **建議保留，但可以精簡**：「網站版面」後台、Banner 輪播、詢問表單、課程與家具的內容類型。這些是客戶指定的需求，例如「每頁只留一行短代碼」「版面段落和數量照手稿」，WordPress 沒有可以一比一取代的內建功能。硬換的成本比留著高。

**前三優先**

| 順位 | 做什麼 | 預估少掉的程式 | 為什麼先做 |
| --- | --- | --- | --- |
| 1 | 不合併 1.3.36「作品加入購物車」；改成作品連到 Woo 商品頁 | 避免新增約 250 行；之後可再刪作品選項約 460 行 | 這套程式還沒上線，現在決定沒有遷移成本；它是用 1.3.35 改的，硬合併會蓋掉 1.3.37～1.3.39 的商品頁 |
| 2 | 刪除一次性搬家工具、死碼、外掛備用版面與重複圖片 | 約 2,500 行＋40 MB | 不影響前台畫面；可以少掉 4 個會改網站狀態的後台端點、每次開網頁都會跑的 6 段遷移檢查，以及一個公開可讀的 `content.json` |
| 3 | 課程付款與交付改用 Woo 內建功能（購買備註、虛擬商品、隱藏、商品分類） | 約 190 行 | 正式課程商品還沒建立，沒有舊資料要搬；付款寄信流程也還沒驗收，現在改最不浪費 |

---

## 二、子系統總表

等價程度：**完全**＝原生功能可以直接取代；**部分**＝原生可以做大部分，需要接受差異或少量程式；**無**＝原生沒有對應功能。

| 子系統 | 檔案 | 行數 | 原生替代 | 等價程度 | 建議 | 優先 | 風險 |
| --- | --- | --- | --- | --- | --- | --- | --- |
| S1 一次性匯入／上線／還原首頁工具 | `src/admin.php:159-260, 313-326, 405-495`、`data/content.json`、`assets/`（40 MB） | 約 300＋JSON＋40 MB | WordPress 匯入器、手動上傳媒體（工作已完成，不需要替代） | 完全（已不需要） | 刪除 | 2 | 低：要先確認正式站作品都有特色圖片／圖集，見 M2 |
| S2 一次性資料遷移（日誌分類、課程欄位、課程目錄 v2、Banner、頁面 slug、商品分類） | `admin.php:18-38, 262-311, 328-403`、`content.php:35-68`、`store-operations.php:1-61`、主檔 `:27-32, 61-66` | 約 270 | 無（一次性腳本）；完成旗標存在 wp_options | 完全（已不需要） | 刪除 | 2 | 低：先確認 6 個 `bfnd_*_v1/v2` 旗標都已寫入正式站 |
| S3 沒有掛上 hook 的死碼（舊頁面 meta box 編輯器、短代碼轉換頁） | `page-editor.php:202-275`、`shortcode-pages.php:57-139` | 約 160 | — | — | 刪除 | 2 | 極低：Grep 確認沒有任何 `add_action` 指到這些函式；表單還指向不存在的 `bfnd_restore_shortcode_pages` 處理器 |
| S4 外掛備用版面（主題沒啟用時才用） | `src/render.php`(560)、`templates/site.php`(46)、`public/style.css`(495)、`interactions.css`(432)、`site.js`(261)、logo/LINE 圖、主檔 `:19-21, 70-75, 115-119` | 約 1,810 | WordPress 主題機制本身（主題 1.3.39 已有同名、更新的 renderer） | 完全 | 刪除，外掛改成「需要主題」 | 2 | 中低：主題被停用時前台會退回一般樣式；CHANGELOG 要寫清楚。`DEPLOYMENT.md` 記錄外掛備用 CSS 有一個既存溢位，等於沒人維護 |
| S5 自訂內容類型＋meta box（家具作品／課程／生活木作、分類、系列） | `admin.php:4-16, 40-157`、`public/admin.js`（壓成一行，2 KB） | 約 135＋JS | `register_post_type`／taxonomy 本來就是原生；欄位可改用 `register_post_meta`＋區塊編輯器側欄，或 Woo 商品屬性 | 部分 | 保留；拿掉重複的 SEO 欄位（S11）；生活木作是否併進 Woo 見 M7 | 5 | 低 |
| S6 作品木材／尺寸選項（詢價規格） | `src/work-options.php`(81)、`public/work-options.js`(131)、`work-options.css`(188)、`admin.php:42, 103-106, 136, 144, 509-521`、主題 `render.php:239-250, 506-507, 515-524` | 約 460 | WooCommerce 可變商品＋「木種」屬性＋商品頁詢問帶規格（主題 1.3.37～1.3.39 已上線） | 部分（可販售品完全等價；只詢價、不賣的作品沒有原生對應） | 簡化或刪除（請業主決定） | 1（決策）／4（執行） | 中：這是 2026-10-05 會議需求；正式站還沒有作品設定過選項（`HANDOFF.md:135`），所以沒有資料要搬 |
| S6b 1.3.36「作品加入購物車」（另一個工作樹，還沒合併） | `upload-plugin-theme-files-15c122`：新檔 `src/work-commerce.php`(149)、`admin.php` +26、主題 `render.php` +13、CSS +104 | 約 290（新增） | Woo 單一商品頁（1.3.37 起已用作品頁風格）＋原生 `?add-to-cart` | 完全 | **不合併**；改成作品 meta 存商品 ID，按鈕連到商品頁 | 1 | 合併風險高：該分支以 1.3.35 為基底（`DEPLOYMENT.md` 部署前紀錄已警告），會蓋掉 1.3.37～1.3.39 的商品頁 |
| S7 詢問表單與 `bf_inquiry` | `admin.php:10-11, 114-125, 497-537`、主檔 `:45-46`、主題 `render.php:503-528, 651-669` | 約 110 | WordPress 核心沒有表單；替代品是第三方外掛（Contact Form 7／WPForms 等） | 無（核心）／部分（第三方） | 保留並精簡，加上限流 | 6 | 換成大型表單外掛反而增加攻擊面。現有程式有 nonce、honeypot、白名單驗證，**但沒有頻率限制**；未登入的 `admin_post_nopriv` 端點可能被灌垃圾詢問 |
| S8 課程收費與交付（course-commerce） | `src/course-commerce.php`(228)、`admin.php:43, 60-89, 139-140`、主題 `render.php:6-36`（plugin 函式的備援副本） | 約 290 | Woo「虛擬」勾選、目錄可見度「隱藏」、商品分類、**購買備註**、可變商品梯次／庫存、訂單動作重寄通知信 | 部分～大部分 | 改用原生，只保留「課程頁 ↔ 商品」綁定與價格顯示 | 3 | 中：要調整後台操作說明；購買備註寄信的行為要用測試訂單實測（見 §4 查證） |
| S9 商務營運（自取地址、商品分類遷移） | `src/store-operations.php`(151) | 約 90（不含 S2 的 60） | 區塊結帳的「本地取貨」支援多個取貨點，含地址與說明（**只能用區塊結帳**） | 部分 | 先保留（主題用的是經典 `[woocommerce_checkout]`）；刪掉「沒地址就隱藏自取」的過濾器 | 7 | 改成區塊結帳要先確認綠界外掛相容，未查證 |
| S10 版面編輯器（網站版面＋DOM 欄位掃描＋Banner 管理） | `template-admin.php`(384)、`page-editor.php:1-200`、`shortcode-pages.php:1-56`、`public/page-design.js`(28)、`banner-admin.js`(84) | 約 750 | 區塊編輯器 pattern＋`templateLock: "contentOnly"`（只能改文字和圖片，不能動版面）、template parts | 部分 | 短期保留並刪除死碼（S3）、舊相容讀取；長期可評估改成鎖定的區塊版面 | 8 | 高：客戶 2026-09-18 明確要求頁面本文只放一行短代碼、逐區內容不在頁面編輯器操作（`PM-AUDIT.md`）。要改成原生得重寫 8 頁版面並搬移所有已存的文字覆寫。用順序編號對應欄位（`section_1_text_1`）有編號漂移風險，已經出過一次事故（README 輪播回歸） |
| S11 Yoast 整合 | `src/yoast-seo.php`(59)、`public/yoast-analysis.js`(25)、`template-admin.php:123-140, 272-287`、`admin.php:41`（CPT 的 seo_title／seo_description 欄位）、`templates/site.php:14-19` | 約 130 | Yoast 每篇文章自己的 SEO 標題／描述 metabox 與側欄；Yoast 預設輸出 schema；Woo 輸出商品結構化資料 | 部分 | 刪除舊版 `_bfnd_seo_*` 欄位與相容 filter；分析內容注入在短代碼架構下要保留 | 4 | 低：先把舊值一次性複製到 `_yoast_wpseo_*` |
| S12 「示意」圖片過濾 | `content.php:78-120`、`page-editor.php:73-92`、主題 `render.php:42-44`；另有 21 處檢查呼叫 | 約 70 | 不需要程式：把示意圖從媒體庫移到回收桶，或移除它們在內容裡的引用 | 完全（處理資料，不用程式） | 先清理資料，再刪過濾器 | 5 | 中低：`HANDOFF` 記錄舊圖仍在媒體庫，可能有其他內容共用。每次 `the_content`／`render_block` 都跑一次正規表示式 |
| S13 舊網址轉址 | `content.php:70-76` | 7 | 自訂內容類型設 `has_archive => false`、taxonomy 設 `publicly_queryable => false` | 部分（原生是回 404，不是 301） | 保留或簡化 | 9 | 低 |
| S14 主題整套頁首／頁尾／頁面 renderer | 主題 `src/render.php`(754)、`templates/`(73)、`site.js`(298)、CSS 約 1,410 | 約 2,540 | 區塊主題＋template parts＋導覽區塊 | 部分 | 保留（這就是品牌設計本身）；刪掉 `site.js:44-64` 的快取補丁、沒有載入的 `public/admin.js`／`admin.css` 副本 | 9 | 改成區塊主題等於整站重做；不建議 |

---

## 三、逐項分析

### S1 匯入／上線／還原工具（刪除）

- **做什麼**：工具 → 「飛熊入夢素材匯入」。逐步匯入 17 件作品、托盤、課程與照片（`bfnd_import_*`），「發布全部新版頁面並切換首頁」（`bfnd_launch_action`），「還原先前首頁」（`bfnd_restore_action`）。啟用外掛時還會建立私密頁面並寫入種子資料。
- **主要 hook**：`admin_menu → bfnd_admin_menu`（主檔:41）、`admin_post_bfnd_import`／`bfnd_launch`／`bfnd_restore`（主檔:42-44）、`register_activation_hook`（主檔:61-66）。
- **原生**：已經不需要；新版已經上線（2026-09-18）。
- **資安收益**：少掉 3 個會改網站狀態的 `admin_post` 端點（其中一個能切換首頁）、一段 inline `<script>` 自動送出表單（`admin.php:174`）。`data/content.json` 會隨外掛 ZIP 部署，可以從 `/wp-content/plugins/.../data/content.json` 公開讀到。
- **前提**：`bfnd_work_image()`／`bfnd_gallery()`（`content.php:122-150`）沒有特色圖片時，會退回讀 `_bfnd_image`／`_bfnd_gallery` 存的外掛 `assets/` 相對路徑。刪 `assets/` 之前，要先確認正式站每件作品、課程、生活木作都有特色圖片和 `_bfnd_gallery_override=1`。

### S2 一次性遷移（刪除）

- `bfnd_migrate_native_journal_categories`、`bfnd_migrate_course_editor_fields`、`bfnd_migrate_course_catalog_v2`、`bfnd_migrate_banner_carousels`、`bfnd_migrate_page_slugs` 掛在 `init`（主檔:28-32）；`bfnd_migrate_meeting_product_categories` 掛在 `init` 30（`store-operations.php:61`）。
- 每次請求都會先讀一次 option 旗標。旗標已寫入時幾乎沒有成本，但只要有一段沒完成（例如 `bfnd_migrate_page_slugs` 碰到 slug 衝突就不寫旗標），每次請求都會重跑查詢；`course_catalog_v2` 甚至會呼叫 `wp_update_post`。
- `DEPLOYMENT.md` 記錄商品分類與課程遷移在正式站已經完成；其他旗標沒有直接證據，刪除前要先查 wp_options。

### S3 死碼（刪除）

- `page-editor.php:202-275` 的 `bfnd_page_design_meta_boxes`／`_meta_box`／`_save`／`_admin_assets`，和 `shortcode-pages.php` 的 `bfnd_shortcode_migration_page`／`bfnd_migrate_shortcode_pages_action`／`bfnd_build_page_blocks`：Grep 整個外掛與主題都**沒有** `add_action` 指向它們。這是 2026-09-18 改成「網站版面」左側選單前的舊做法。
- `shortcode-pages.php:52-56` 的 `bfnd_hide_editor_data_blocks`（`render_block`，:143）只在舊區塊內容還存在時有用；8 頁都改成單行短代碼後就是空轉。
- `template-admin.php:292-344` 的 `bfnd_install_template_pages_action` 和 `shortcode-pages.php:9-40` 的舊區塊讀取器：總覽頁顯示 8 頁都是「版型已連接」之後就可以移除（`PM-AUDIT.md` 記錄 2026-09-18 轉換 8 成功、0 失敗）。

### S4 外掛備用版面（刪除）

- `bears-fantasyland-newdesign.php:19-21` 只在主題不是 `bears-fantasyland` 時載入 `src/render.php`。`bfnd_enqueue`（:70）和 `bfnd_template_include`（:115）在主題啟用時也直接 return。
- 正式站用的就是自製主題（1.3.39），所以這 1,800 行與 40 MB 圖片在正式站**從來不會執行**，卻每次都要跟主題同步修改（`HANDOFF` 多次記錄「主題與外掛備援 renderer 同步」）。函式比對結果：主題多了商品頁、分類頁、詢問帶規格等 11 個函式，外掛版已經落後。
- 刪除後在外掛標頭加 `Requires Plugins` 的反向檢查不可行（主題不是外掛），改成在 `admin_notices` 提示「需要飛熊入夢主題」即可，約 5 行。

### S5 自訂內容類型與 meta box（保留）

- `register_post_type`／`register_taxonomy`（`admin.php:4-16`，掛 `init`，主檔:27）本來就是 WordPress 原生做法，不算重複造輪子。
- meta box（`add_meta_boxes`，主檔:33；`save_post`，主檔:40）是手寫欄位，有 nonce、權限檢查與 sanitize，品質可以。原生替代是 `register_post_meta(..., show_in_rest)` 配區塊編輯器側欄，但要寫 JS 面板，程式不會比較少。**保留**。
- 圖集管理 `public/admin.js` 是自製的媒體庫選取／拖曳排序。WordPress 核心沒有給 CPT meta 用的圖集欄位（Woo 的商品圖庫只能用在商品）；保留。
- 主題另有一份一模一樣、卻沒有被載入的 `public/admin.js`／`admin.css`（Grep 主題沒有 enqueue），可以刪除。

### S6／S6b 作品的木材／尺寸選項與「可加入購物車」（重點）

**現況**
- `work-options.php` 讓每件家具作品後台逐行填「可選木材」「可選尺寸」。前台變成單選按鈕（`bfnd_render_work_choice_group`，主題 `render.php:244, 248`），點「作品諮詢」時用 `work-options.js` 帶進合作頁表單。送出時 `bfnd_inquiry_action`（`admin.php:509-521`）會再驗證一次是否屬於該作品的選項。**完全不連動售價、庫存、購物車。**
- 商品頁（主題 1.3.37～1.3.39）已經改用 Woo 可變商品，「延展之境櫻桃木桌」有 3 個木種變化，各有價格。「商品諮詢」連結用 `bf_product`／`bf_variation` 把所選木種帶進同一張詢問表單（`render.php:651-669`），正式站已實測預填（`DEPLOYMENT.md` 1.3.39 段）。
- 另一個工作樹 `upload-plugin-theme-files-15c122` 有未提交的 1.3.36／0.5.37：新增 `work-commerce.php`（149 行）、作品後台「購買商品」下拉、商品端「家具作品購買商品」勾選 meta box，作品頁自己列出變化與價格，再用自製表單 POST `add-to-cart`＋`variation_id` 到購物車，並在訂單項目加作品名稱 meta。

**和原生重複的地方**
1. 選木種、顯示各木種價格、缺貨停用、加入購物車：Woo 單一商品頁的可變商品表單**完全**做得到，而且已經套上作品頁風格（1.3.37）。1.3.36 等於在作品頁再做一個簡化版的加購表單，還要自己處理變化屬性、缺貨、價格顯示。
2. 「商品 ↔ 作品」的一對一綁定、`_bfnd_work_product` 勾選、可選清單過濾：都是為了讓作品頁代替商品頁才需要。
3. 訂單項目加「家具作品」名稱：商品名稱本來就會出現在訂單。

**建議（優先 1）**
- **不合併 `work-commerce.php`**。改成 bf_work 只存一個商品 ID（沿用 `_bfnd_woo_id`，或在作品後台填商品 ID），作品頁 CTA 顯示「前往選購」連到 `get_permalink($product_id)`。約 15～20 行，不需要商品端 meta box。
- 作品木材／尺寸選項：
  - 如果「凡是要讓客人選規格的作品都會做成 Woo 商品」，就**刪除 S6 全部**（約 460 行）。詢問改由商品頁帶 `bf_product`／`bf_variation`，`bfnd_inquiry_action` 的選項驗證跟著移除。
  - 如果業主堅持「不賣、只詢價的作品也要能選木種」，WordPress／Woo 沒有對應的原生功能，就保留 S6。但要凍結、不再擴充，也不要讓兩套規格（作品選項 vs. Woo 屬性）同時存在於同一件作品。
- **長期選項（不急）**：把 bf_work 併進 Woo 商品，用目錄可見度、商品分類代替系列／分類，用 upsell 代替相關作品，用商品圖庫代替圖集，主題商品頁已經是作品頁的設計語言。成本是 17 件作品要搬資料、`/works/{slug}/` 要 301 到 `/product/{slug}/`、英文名稱／工法／故事圖這些欄位要找地方放。可以減少的程式是 CPT＋meta box＋作品頁 renderer＋S6 約 900 行，但 SEO 網址遷移的風險高；等業主確定哪些作品要上架販售後再評估。

### S7 詢問表單（保留並精簡）

- hook：`admin_post_nopriv_bfnd_inquiry`／`admin_post_bfnd_inquiry`（主檔:45-46）。資料存成私密的 `bf_inquiry` 文章並寄信給 `admin_email`。
- 核心沒有表單功能。換成 Contact Form 7、WPForms 這類外掛，程式量會從約 110 行變成數萬行第三方程式，資安面積反而變大（未查證個別外掛的漏洞紀錄）。Wordfence 有全站頻率限制，但不是針對這個表單的防灌水機制（未查證設定方式）。
- **建議**：保留。加一個以 IP 為單位、用 transient 記錄的簡單頻率限制（例如同一 IP 10 分鐘最多 3 筆），以及 Email／電話的格式檢查。如果 S6 刪除，也同時刪掉選項驗證。

### S8 課程收費與交付（改用原生，優先 3）

| 自製功能 | 位置 | 原生替代 |
| --- | --- | --- |
| 「木作課程／會員方案商品」勾選，自動設成虛擬商品＋目錄隱藏，取消時還原 | `course-commerce.php:71-104`（`add_meta_boxes_product`、`woocommerce_admin_process_product_object`） | 商品編輯頁原生「虛擬」勾選＋「目錄可見度：隱藏」；用一個商品分類「課程」標記，取代 `_bfnd_course_product` |
| 商店列表隱藏課程商品 | `:106-109`（`woocommerce_product_is_visible`） | 目錄可見度「隱藏」本來就有這個效果 |
| 購物車／結帳提醒 | `:111-122` | 寫在商品簡短說明；或保留（10 行，低風險） |
| 訂單項目寫入課程名稱與 ID | `:124-136` | 商品名稱就是課程名稱；寄信改用購買備註後就不需要課程 ID |
| 付款後寄 YouTube 連結、每筆只寄一次、重寄訂單動作、訂單備註 | `:138-228`（`woocommerce_payment_complete`、`order_status_processing/completed`、`woocommerce_order_actions`） | 商品「進階」分頁的**購買備註**：Woo 原生訂單頁在 processing／completed 狀態才顯示（已查證，見 §4）；訂單通知信也會帶出購買備註（部分查證）；重寄可用訂單動作重寄顧客通知信（未查證選項名稱） |
| 主題內的 `bfnd_get_course_product` 等備援副本 | 主題 `render.php:6-36` | 外掛保留一份即可；主題已經寫明需要外掛 |

- **差異與限制**：購買備註是「每個商品一段文字」。如果一個課程商品有多個梯次變化，所有梯次會看到同一個連結。目前設計本來就是「一課一連結」，影響不大。原生做法沒有「已寄出」逐項記錄，但 Woo 的訂單備註本來就會記錄通知信寄送。
- **時機**：`HANDOFF`／`DEPLOYMENT` 記錄正式課程商品、YouTube 網址、付款與收信都**還沒建立、還沒驗收**，沒有資料要搬，也沒有習慣要改。
- 預估從約 290 行降到約 80 行（課程 CPT 綁商品 ID、讀 Woo 價格、CTA 連結）。資安面：少掉一個自製寄信流程和一個自訂訂單動作端點。

### S9 商務營運（部分保留）

- 商品分類遷移屬於 S2，刪除。
- 自取地址設定頁（`admin_menu`:67、`admin_init`:74）、結帳顯示地址（`woocommerce_after_shipping_rate`:115）、訂單快照（`woocommerce_checkout_order_processed`:131）、訂單頁／Email 顯示（:143、:151）：Woo **區塊結帳**的 Local Pickup 原生支援「名稱、完整地址、說明」（已查證，見 §4），但主題用的是經典 `[woocommerce_checkout]`（主題 `render.php:574`）。經典版的本地取貨沒有地址欄。改成區塊結帳需要先確認綠界外掛支援區塊結帳（未查證），所以**先保留**。
- `bfnd_filter_unconfigured_pickup_rates`（`woocommerce_package_rates`:107）：用程式在「地址沒填」時把自取方式藏起來。原生做法就是**地址沒確定前不要把「本地取貨」加進運送區域**，所以可以刪除（約 8 行）。

### S10 網站版面編輯器（保留、精簡；長期評估）

- 運作方式：`[bfnd_page key="..."]` 呼叫主題 renderer 輸出 HTML → `bfnd_page_design_document()` 用 DOMDocument 掃描 h1～h6、p、span、img、背景圖，依出現順序編號成欄位 → 後台「網站版面」顯示 textarea／媒體選取 → 存在 `bfnd_page_design_{key}` option → 前台輸出時再掃描一次並替換。hook：`admin_menu`、`admin_post_bfnd_save_template`、`admin_post_bfnd_save_banners`、`admin_enqueue_scripts`、`admin_bar_menu`、`page_row_actions`（`template-admin.php:378-384`）、`add_shortcode`（`shortcode-pages.php:141`）。
- **最接近的原生做法**：把 8 頁版面寫成區塊 pattern，外層 Group 設 `templateLock: "contentOnly"`。客戶只能改文字和圖片，不能移動、刪除區塊，設計工具也會隱藏（WordPress 6.1 起，已查證）。Yoast 會直接分析區塊內容，所以 S11 的分析注入可以一起拿掉。
- **為什麼不建議現在換**：
  1. 客戶指定「頁面本文只放一行短代碼，逐區內容不在頁面編輯器操作」（`PM-AUDIT.md` 2026-09-18）；改成原生區塊等於推翻這個需求。
  2. 頁面裡有動態清單（作品、課程、日誌、Woo 商品）和輪播，需要自訂區塊或在 pattern 裡放短代碼。
  3. 8 頁所有已存的覆寫（用順序編號的鍵）都要搬進區塊內容，版面細節容易跑掉。
- **短期精簡**：刪除 S3 死碼和舊 `_bfnd_page_design` post meta 的相容讀取（`page-editor.php:173-178`）。在掃描器加單元測試或快照檢查，防止編號漂移再出事（README 記錄過一次輪播造成的欄位錯位）。
- Banner 輪播：核心沒有輪播區塊，保留。

### S11 Yoast 整合（部分刪除）

- 「網站版面」裡的 SEO 欄位直接寫 Yoast 的 `_yoast_wpseo_title`／`_metadesc`／`_focuskw`（`template-admin.php:272-285`），只是換一個入口，沒有自造 SEO 輸出，可以保留，方便業主。
- **重複的地方**：`bfnd_fields()` 對作品、課程、生活木作都有自己的 `seo_title`／`seo_description` 欄位（`admin.php:41`），和 Yoast 在同一個編輯頁的原生 metabox 重複，還要靠 `wpseo_title`／`wpseo_metadesc` filter 相容（`yoast-seo.php:26-59`）。`templates/site.php:14-19` 在沒有 Yoast 時輸出 description（屬於 S4，一起刪）。
- Yoast 分析注入（`yoast-analysis.js`、`bfnd_yoast_analysis_content`）：短代碼架構存在一天，就需要保留一天。
- 結構化資料：程式裡**沒有**自製的 JSON-LD，商品頁沿用 Woo 和 Yoast 原生輸出（`DEPLOYMENT.md` 1.3.37 記錄 ld+json 仍輸出）。這部分沒有重複。

### S12 「示意」圖片過濾（改成資料清理）

- 4 個全域 filter：`wp_get_attachment_image`（`content.php:93`）、`the_content`（:107）、`woocommerce_short_description`（:108）、`render_block`（`page-editor.php:92`）。另外有 21 處呼叫 `bfnd_nonfinal_photo`，還有寫法相同的副本 `bfnd_page_design_image_is_blocked`（`page-editor.php:73-83`）和主題 `bfnd_image()`（`render.php:43`）。
- 這些程式是用來「讓舊示意圖永遠不出現」，屬於資料問題。原生做法是把那些附件移到回收桶，或替換掉內容裡的引用。好處是每次輸出內容都少跑一次正規表示式，也不會再誤殺檔名或 ALT 剛好含「示意」「editorial」的正式圖片。

---

## 四、本次查證（依全域規則 2）

| 主張 | 結果 | 來源 |
| --- | --- | --- |
| 區塊 pattern `templateLock: "contentOnly"` 只允許編輯文字／媒體，WordPress 6.1 起 | 已查證 | https://developer.wordpress.org/block-editor/how-to-guides/curating-the-editor-experience/block-locking/ 、https://make.wordpress.org/core/2022/10/11/content-locking-features-and-updates/ |
| Woo 區塊版 Local Pickup 支援名稱、完整地址、說明，且只能用 Checkout 區塊 | 已查證 | https://woocommerce.com/document/woocommerce-blocks-local-pickup/ |
| 經典 Local Pickup 是另一套舊設定 | 已查證 | https://woocommerce.com/document/woocommerce-shipping-and-tax/woocommerce-shipping/local-pickup/ |
| 購買備註在訂單頁只於 `completed`、`processing` 狀態顯示（filter `woocommerce_purchase_note_order_statuses`） | 已查證（原始碼） | https://raw.githubusercontent.com/woocommerce/woocommerce/trunk/plugins/woocommerce/templates/order/order-details.php |
| 購買備註會出現在顧客訂單通知信 | **部分查證**：社群支援串說會寄送，沒取得官方程式片段（2026-10-06）。改用前要以測試訂單實測 | https://wordpress.org/support/?p=14079070 |
| 訂單動作可重寄顧客通知信的選項名稱 | **未查證（2026-10-06）** | — |
| 綠界外掛是否支援 Woo 區塊結帳 | **未查證（2026-10-06）** | — |
| 第三方表單外掛的漏洞紀錄、Wordfence 是否能針對單一表單限流 | **未查證（2026-10-06）** | — |
| 1.3.36 用 POST `variation_id` 但沒送屬性欄位，Woo 是否會自動補齊屬性 | **未查證**；不合併就不需要確認 | — |

---

## 五、遷移步驟草案（不執行）

每一步都遵守：先備份（資料庫匯出＋目前版本 ZIP 當回復包）→ 本機 `npm run check`、`php -l` → 打包 → 使用者授權後從後台上傳 → `check_live_routes.py`、`check_live_content.py` → 失敗就用上一版 ZIP 覆蓋回去。

### M1（優先 1）作品連到 Woo 商品，不合併 1.3.36
1. 在 `upload-plugin-theme-files-15c122` 工作樹標記「不合併」，保留分支當參考，不刪除（刪除前要先問使用者）。
2. 在 1.3.39 基底上，`bfnd_fields('bf_work')` 加 `woo_id`（商品 ID 下拉，只列已發布的商品；不加商品端 meta box）。
3. 主題 `bfnd_render_work()` 的 CTA：有 `woo_id`，而且商品可購買、已發布時，顯示「前往選購」連到商品頁，原本的「作品諮詢」保留成次要連結；沒有綁定就維持現狀。
4. 實測：作品頁 → 商品頁 → 選木種 → 加入購物車；有綁定和沒綁定的作品各測一件；RWD 檢查 390／1440px。
5. 請業主決定 S6 的去留：凡是要選規格的作品是否都會建成 Woo 商品。

### M2（優先 2）刪除一次性工具、死碼、備用版面
1. 在正式站唯讀確認：wp_options 有 `bfnd_native_journal_categories_v1`、`bfnd_course_editor_fields_v1`、`bfnd_course_catalog_v2`、`bfnd_banner_carousels_v1`、`bfnd_clean_slugs_v1`、`bfnd_meeting_product_categories_v1`；「網站版面」總覽 8 頁都是「版型已連接」；所有 bf_work／bf_course／bf_lifestyle 都有特色圖片，而且有 `_bfnd_gallery_override`（或圖集 ID）。
2. 刪除 S1、S2、S3 列出的函式、對應 hook 與 `register_activation_hook` 的種子資料（只保留 `bfnd_register_types`＋`flush_rewrite_rules`）。
3. 刪除 S4：`src/render.php`、`templates/`、`public/style.css`、`interactions.css`、`site.js`、外掛 logo 與 LINE 圖、`bfnd_enqueue`、`bfnd_template_include`；`build_package.py` 不再打包 `assets/`、`data/`、`templates/`。
4. 修改 `content.php` 的 `bfnd_work_image()`／`bfnd_gallery()`，移除 `_bfnd_image`／`_bfnd_gallery` 路徑退回邏輯（步驟 1 確認沒有依賴之後才做）。
5. 刪除主題沒有載入的 `public/admin.js`、`admin.css`，以及 `site.js:44-64` 的電話補丁。
6. 驗收：12 個主要路由＋20 個退役路由；作品、課程、生活木作內頁圖片都正常；後台「網站版面」8 頁的欄位數和刪除前一致（逐頁比對欄位清單，避免編號漂移）。

### M3（優先 3）課程改用 Woo 原生
1. 更新 `ADMIN-GUIDE.md`：課程商品 = 虛擬＋目錄隱藏＋商品分類「課程」；線上課程連結寫在商品「進階 → 購買備註」。
2. 改 `bfnd_course_product_is_eligible()` 的條件：已發布＋虛擬＋分類「課程」（取代 `_bfnd_course_product`）。刪除商品端 meta box、`woocommerce_product_is_visible` filter、YouTube 寄信／重寄／訂單項目 meta（`course-commerce.php:71-136` 的大部分和 `:138-228`），以及課程後台的 `course_access_url` 欄位。
3. 主題 `render.php:6-36` 的備援副本刪除，`bfnd_render_commerce_category_section()` 等排除課程的判斷改成看商品分類。
4. 用測試課程商品（私密）＋測試金流模式（如果綠界有提供）下單，確認購買備註出現在訂單頁和顧客通知信；這一步需要使用者授權才能操作正式站。

### M4 SEO 舊欄位併入 Yoast
1. 一次性 WP-CLI 或後台按鈕：對每篇有 `_bfnd_seo_title`／`_bfnd_seo_description`、但 `_yoast_wpseo_*` 是空值的文章複製過去（先備份 postmeta）。
2. 從 `bfnd_fields()` 移除 `seo_title`、`seo_description`；刪除 `yoast-seo.php:23-59` 的相容 filter。
3. 抽查 3 件作品與 2 堂課程的前台 `<title>`、meta description。

### M5 「示意」圖片清理
1. 列出媒體庫中 `_bfnd_source` 或檔名含 editorial／wooden-cup、或 ALT 含「示意」的附件，再查哪些內容引用了它們。
2. 業主確認後把附件移到回收桶（**不永久刪除**），替換引用。
3. 刪除 S12 的 4 個 filter 和所有 `bfnd_nonfinal_photo` 檢查；確認公開頁搜尋不到「示意」字樣。

### M6 小幅精簡（可以跟 M2 一起做）
- 刪除 `bfnd_filter_unconfigured_pickup_rates`，在 `ADMIN-GUIDE.md` 寫明「地址確認前不要把本地取貨加進運送區域」。
- 詢問表單加上 IP 頻率限制。
- `bf_work`／`bf_lifestyle`／`bf_course` 是否改成 `has_archive => false`，或保留現在的 301 轉址，由 SEO 考量決定。

### M7 長期評估（先不排程）
- 生活木作 CPT（目前只有 1 件托盤）併入 Woo 商品分類「生活木作」。
- bf_work 併入 Woo 商品（見 S6 長期選項）。
- 網站版面改成鎖定的區塊 pattern（見 S10）。需要客戶同意修改 2026-09-18 的「一行短代碼」需求，才能啟動。

---

## 六、本次沒有驗證的範圍

- 沒有讀取正式站資料庫或後台：遷移旗標、各作品是否依賴外掛 `assets/` 圖片、示意附件是否被其他內容引用，都還要唯讀確認。
- 沒有執行 `npm run check` 或任何測試（這次沒有改程式）。
- 另一個工作樹只看了 `git diff` 和新檔，沒有執行它的程式。
- 行數是原始碼行數；「可減少」是根據函式範圍估算，實際刪除時會因為共用函式而有 ±10% 誤差。
