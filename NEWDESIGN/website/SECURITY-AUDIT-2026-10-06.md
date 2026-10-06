# 資安審查：主題 bears-fantasyland 1.3.39／外掛 NEWDESIGN 0.5.36（2026-10-06）

**總結：高 1／中 2／低 10（另列 4 項資訊性觀察）。** 自製主題與外掛程式碼**沒有找到可直接被匿名者利用的高風險漏洞**（沒有 SQL 注入、沒有可利用的 XSS、沒有未授權的寫入端點、沒有自訂 REST／AJAX、沒有 eval／unserialize／shell）。目前真正的大風險不在這兩包程式，而在主機層一個沒查明的強制外掛檔。
最需要先修的 3 件：
1. **【高｜主機】查明 `wp-content/mu-plugins/customize-controlsse.php`**：2026-09-17 紀錄就已列出，到今天文件仍無結論。它每個請求都會自動執行、一般外掛清單停用不了。由主機檔案管理員下載檢視內容、建立時間與修改時間，比對備份；查明前不要開綠界收款。
2. **【中｜外掛】詢問表單只有 honeypot**：沒有頻率限制、驗證碼，伺服器端也沒限制長度。每送一次就新增一筆後台資料並寄一封信，可被大量灌入。
3. **【中｜主機】XML-RPC 有開**：攻擊者可以用它大量嘗試帳號密碼。站上有 Jetpack，不能直接整個關掉，要先確認 Jetpack 有沒有在用，再用 Wordfence 停用 XML-RPC 驗證或限制來源 IP。（線上課程連結只看訂單狀態就寄出的問題列為 L10：目前沒有貨到付款類付款方式，所以暫時無法被利用。）

> 範圍：只讀原始碼（`NEWDESIGN/theme/bears-fantasyland/`、`NEWDESIGN/website/` 的 `*.php`、`src/`、`public/*.js`、`templates/`、`data/`），沒有修改任何程式；正式站只做匿名 GET（約 40 次），沒有送出表單、登入、POST、掃描或暴力嘗試，也沒有請求 MU-plugin 檔的網址。dist ZIP 與根目錄舊站不在範圍內。正式站狀態截至 2026-10-06 16:15–16:40（台灣時間）。中途一度被主機的機器人挑戰頁擋下，我沒有繞過，等它自動解除後才補查。

## 嚴重度定義

- **高**：可能已經或很容易被取得網站控制權、讀到個資或金流資料。
- **中**：可被匿名者濫用、造成營運損失，或在特定設定下會漏出付費內容。
- **低**：需要已登入的低權限帳號、影響有限，或只是資訊外洩、強化不足。

## 問題表

| # | 嚴重度 | 檔案:行號（或 URL） | 問題 | 如何被利用（一句） | 建議修法 | 屬於 | 證據 |
|---|---|---|---|---|---|---|---|
| H1 | 高（待查證） | `wp-content/mu-plugins/customize-controlsse.php`（紀錄見 `NEWDESIGN/website/DEPLOYMENT.md:416`） | 來源不明、沒有描述的強制使用外掛。名稱像是把核心檔 `customize-controls.php` 加了兩個字母，偽裝成核心檔的惡意程式常這樣命名，但**名稱不能證明它是惡意的**，根因也**未確認**。 | 如果是後門，它每次請求都以完整權限執行，可以竄改內容、新增管理員、讀資料庫或金流設定。 | 主機檔案管理員下載這個檔案（不要用瀏覽器開它的網址，會執行它），檢查內容、ctime/mtime、擁有者；比對主機備份；查看同一時間點前後的 FTP／檔案管理／wp-admin 登入紀錄；確認是誰、為什麼建立。如果是惡意的：移除、輪換所有管理員密碼、資料庫密碼、WordPress salts、綠界金鑰，並逐一檢查管理員帳號清單。 | 主機設定 | 文件紀錄（2026-09-17），本次無法從外部查證 |
| M1 | 中 | `NEWDESIGN/website/src/admin.php:497-536`；表單 `NEWDESIGN/theme/bears-fantasyland/src/render.php:513-526` | 公開詢問表單（`admin_post_nopriv_bfnd_inquiry`）的防濫用只有 honeypot（`website` 欄位，第 499 行）。沒有頻率限制、沒有驗證碼，`maxlength` 只在 HTML 上限制，伺服器端不檢查長度。每次送出都會 `wp_insert_post` 一筆並 `wp_mail` 一封（第 523、533 行）。匿名者的 nonce 是所有訪客共用的，可以直接從頁面抓下來重複使用。 | 機器人先抓一次頁面拿到 nonce，再大量 POST，就能灌爆詢問清單和管理員信箱，也可能讓主機寄信信譽變差。 | 依 IP（或 IP + 聯絡方式）做時間窗限流，例如以 transient 記錄，10 分鐘最多 3 次；伺服器端限制各欄長度（姓名 80、聯絡 150、說明 3000 字）；加一個「表單載入時間」欄位，太快送出就拒絕；改成合併寄信或每小時最多寄 N 封；必要時加 Cloudflare Turnstile。主機層可以替 `/wp-admin/admin-post.php` 設 WAF 限流。 | 外掛 | 程式碼證據 |
| M2 | 中 | `https://a1.haotaimaker.com/xmlrpc.php` | XML-RPC 有啟用：GET 回應 405，內容長度 42 bytes，符合 WordPress 的「只接受 POST」訊息。WordPress 核心預設就開著，不是本專案程式造成的。 | 攻擊者可以用 `system.multicall` 在一次請求裡嘗試大量帳密組合，比走登入頁暴力破解更省力（POST 是否被 Cloudflare 或主機擋下未測）。 | 正式站的 REST 命名空間有 `jetpack/v4`，Jetpack 連線可能要用 XML-RPC，不要直接整個封鎖。先確認 Jetpack 是否真的在用：沒在用就停用 Jetpack，再在主機或 Cloudflare 封鎖 `/xmlrpc.php`；有在用就只放行 Jetpack 的 IP 範圍，或在 Wordfence（已安裝，命名空間 `wordfence/v1`）開啟「停用 XML-RPC 驗證」。另外確認 Wordfence 的登入失敗限制和管理員雙因素驗證都已啟用（本次未查證）。 | 主機設定 | 正式站 GET 實測 |
| L1 | 低 | `NEWDESIGN/website/src/admin.php:174`（`bfnd_import_page`） | 帶 `run=1` 的 GET 開啟匯入頁時，頁面會自動以合法 nonce 送出匯入表單。等於只要點一個連結，就會觸發會寫入資料的動作，CSRF 保護被繞過。 | 誘導已登入的管理員點擊 `wp-admin/tools.php?page=bfnd-import&run=1`，就會自動跑一步素材匯入。影響有限：匯入是冪等的，不會覆蓋既有作品。 | 匯入已完成的話，移除這頁或至少拿掉 `run=1` 自動送出；要連續匯入，改成在前一次 POST 成功後才回傳帶一次性旗標的頁面，例如以 transient 檢查。 | 外掛 | 程式碼證據 |
| L2 | 低 | `NEWDESIGN/theme/bears-fantasyland/src/render.php:504-509`；`NEWDESIGN/website/src/work-options.php:5-14, 66-80` | 詢問表單用 `?course=ID` 預填課程名稱時，只檢查文章類型，不檢查狀態；`?work=ID` 也只檢查類型，就把該作品的材質／尺寸選項輸出到下拉選單。 | 匿名者可以逐一嘗試 ID，讀到草稿或私密課程的標題，以及未發布作品的規格選項。 | 兩處加 `get_post_status($id) === 'publish'`（或 `is_post_publicly_viewable()`）檢查。 | 主題＋外掛 | 程式碼證據 |
| L3 | 低 | `NEWDESIGN/website/src/content.php:154`；`NEWDESIGN/theme/bears-fantasyland/src/render.php:360` | 前台作品和課程列表用 `current_user_can('edit_posts')` 決定要不要顯示私密內容，但「投稿者」「作者」也有 `edit_posts`。 | 如果有發投稿者或作者帳號給外部人，他們能在前台看到所有私密作品和課程。 | 改成檢查 `read_private_posts`。 | 外掛＋主題 | 程式碼證據 |
| L4 | 低 | `NEWDESIGN/website/src/admin.php:10-11` | `bf_inquiry` 沿用預設的 `capability_type => post`，所以所有「編輯」和 WooCommerce「商店經理」都能讀取及修改詢問者的姓名和聯絡方式（個資）。 | 任何編輯或商店經理帳號被盜，就能匯出所有詢問個資。 | 改用專屬 capability（例如 `capability_type => 'bf_inquiry'`、`map_meta_cap => true`），只授權給管理員或指定角色；同時建立保留期限並定期清除舊詢問。 | 外掛 | 程式碼證據 |
| L5 | 低 | `https://a1.haotaimaker.com/wp-content/plugins/bears-fantasyland-newdesign/data/content.json`（200，23,515 bytes）；`.../themes/bears-fantasyland/README.md`（200） | 打包時把匯入用的種子資料一起上傳成公開檔案，內含內部備註（例如「推估尺寸，非原作實測」「木種待確認」）、17 筆本機來源資料夾路徑和課程價格草稿；README 也公開了架構說明。`build_package.py:14` 會整個複製 `data/`。 | 任何人都能讀到內部備註和未公告的資料，不需要登入。 | `content.json` 只在啟用和匯入時使用：匯入完成後改成不打包，或放進 `.htaccess` 禁止直接存取；也可以改成 PHP 陣列檔加 ABSPATH 防護。主題 ZIP 排除 README.md。 | 外掛（打包） | 正式站 GET 實測＋程式碼 |
| L6 | 低 | 正式站回應標頭 | 已有 HSTS、`X-Frame-Options: SAMEORIGIN`、`X-Content-Type-Options: nosniff`；缺少 `Content-Security-Policy`、`Referrer-Policy`、`Permissions-Policy`；`x-powered-by: PHP/8.2.34` 洩漏版本。 | 版本資訊讓攻擊者能對照已知漏洞；缺少 CSP，一旦出現 XSS 就沒有第二道防線。 | 在主機或 Cloudflare 加 `Referrer-Policy: strict-origin-when-cross-origin`、`Permissions-Policy`，CSP 先用 Report-Only 試行；在 php.ini 設 `expose_php = Off`。 | 主機設定 | 正式站 GET 實測 |
| L7 | 低 | `https://a1.haotaimaker.com/readme.html`、`/license.txt`（皆 200） | WordPress 安裝附帶的說明檔公開。 | 方便機器人辨識 CMS 並鎖定目標，資訊價值低。 | 在主機封鎖或刪除（WordPress 核心更新時會重新放回，封鎖比刪除可靠）。 | 主機設定 | 正式站 GET 實測 |
| L8 | 低（可用性） | `NEWDESIGN/website/src/store-operations.php:30-61`；`admin.php:262-311, 352-403`；`content.php:51-68`（都掛在 `init`） | 一次性搬移程式掛在 `init`，匿名請求也會執行。如果條件一直達不到，例如商品標題找不到，完成旗標就永遠不會寫入，每個訪客請求都會重跑搜尋查詢；`bfnd_migrate_banner_carousels` 第一次執行時還會在匿名請求裡複製檔案、產生縮圖。 | 大量請求會放大資料庫負擔，資料也可能在不可預期的時間點被改寫。 | 搬移改成只在 `admin_init` 且 `current_user_can('manage_options')` 時執行，或用版本號選項加 `upgrader_process_complete` 觸發；失敗時也寫入「已嘗試」標記和時間。 | 外掛 | 程式碼證據 |
| L9 | 低 | `NEWDESIGN/website/src/store-operations.php:69-70, 93-96` | 「飛熊商務設定」頁開放給 `manage_woocommerce`，但送出走 `options.php`，預設要 `manage_options`。商店經理看得到頁面、存不進去。這是功能問題，不是漏洞；我列出來是為了避免有人「修」的時候直接放寬成所有人都能存。 | —（不可被利用） | 加 `add_filter('option_page_capability_bfnd_store_settings', fn() => 'manage_woocommerce')`，不要放寬到更低的權限。 | 外掛 | 程式碼證據 |
| L10 | 低（潛在，取決於付款方式設定） | `NEWDESIGN/website/src/course-commerce.php:156-163, 204-209` | 寄出線上課程 YouTube 連結的條件只有 `$order->is_paid()`，同時掛在 `woocommerce_order_status_processing` 上。WooCommerce 判斷「已付款」看的是訂單狀態（處理中／已完成），貨到付款這類方式下單後會直接變成「處理中」。2026-10-06 匿名 GET `/wp-json/wc/store/v1/cart` 回傳的付款方式只有 5 種綠界方式（Credit、WebATM、ATM、CVS、Barcode），**沒有** `cod`／`bacs`／`cheque`，所以目前無法用這招。這只是 Store API 列出的方式，不代表綠界已經核准或交易流程已驗收。 | 如果日後啟用貨到付款（WooCommerce 預設也允許虛擬商品使用），任何人都能下單線上課程，不付錢就立刻收到連結。另外，「不公開」連結拿到後可以轉傳。 | 寄送前再確認 `$order->get_date_paid()` 不是空的，或確認付款方式不在 `cod`／`bacs`／`cheque` 名單內；手動標記改由「重新寄送」動作處理。長期來看，可改成需登入的課程頁，或 YouTube「私人」加指定帳號。 | 外掛＋WooCommerce 設定 | 程式碼證據＋Store API GET |

## 資訊性觀察（不計入條數）

- `NEWDESIGN/website/src/shortcode-pages.php:117-120` 表單送到 `admin_post_bfnd_restore_shortcode_pages`，但沒有任何處理函式註冊；`bfnd_migrate_shortcode_pages_action`（第 123 行）、`page-editor.php` 的 `bfnd_page_design_meta_boxes`／`bfnd_page_design_save`（第 202、238 行）也都沒有掛到 hook。這些是死碼，不會執行，建議清除，免得日後誤接上。
- `NEWDESIGN/website/src/page-editor.php:111-112`：舊版區塊內容裡的 `image_url` 經過 `esc_url_raw` 後替換進 `style` 屬性。`esc_url_raw` 允許 `'`、`(`、`)`、`;`，理論上可以注入 CSS（不能注入 JS，因為用 `setAttribute`，跳不出屬性）。來源只有管理員執行的搬移流程，風險可忽略；之後可改成只接受附件 ID。
- 公開詢問表單的 nonce 對匿名者沒有防偽造效果（所有訪客共用，而且匿名送出本來就沒有偽造身分的問題）。另外，如果頁面快取超過 12–24 小時，正常訪客會遇到「表單已逾時」。
- 線上課程使用「不公開」YouTube 連結：拿到連結就能看，也能轉傳，這是營運層面的取捨，`admin.php:87` 的說明已經提醒。

## 已檢查、未發現問題的面向

**入口點與權限**
- 程式碼裡沒有任何 `wp_ajax_*`、`wp_ajax_nopriv_*`、`register_rest_route`（主題和外掛全文 grep）。`admin_post_nopriv_*` 只有詢問表單一個。
- `admin_post_bfnd_import`／`bfnd_launch`／`bfnd_restore`（`admin.php:201-239`）：`manage_options` 加 `check_admin_referer`，輸入只取整數 `step`。
- `admin_post_bfnd_save_template`（`template-admin.php:230-290`）：`sanitize_key` 白名單頁面 key，加 `edit_pages`、`edit_post`、nonce；文字經 `bfnd_page_design_safe_inline` 的 `wp_kses`，只允許 br/em/strong/b/i/sup/sub/span[class]，長度上限 5000；圖片只收 `wp_attachment_is_image` 的附件 ID；YouTube 網址解析成 11 碼 ID 後重組；SEO 欄位用 `sanitize_text_field` 並截斷長度。
- `admin_post_bfnd_save_banners`（`template-admin.php:76-98`）：`edit_pages` 加 nonce，只收圖片附件 ID，最多 10 張。
- `admin_post_bfnd_install_template_pages`（`template-admin.php:292-344`）：`manage_options` 加 nonce。
- `update_option` 只出現在上述有權限加 nonce 的處理函式，以及 `init` 一次性搬移（L8）裡；搬移的值都是程式內建資料，訪客輸入影響不到。沒有任何讓低權限或匿名者觸發 `update_option` 並帶入自己值的路徑。
- `save_post` 的作品、課程中繼資料（`admin.php:127-148`）：nonce、`DOING_AUTOSAVE`、`edit_post`、文章類型白名單，逐欄用 `sanitize_text_field`／`sanitize_textarea_field`／`absint`；課程連結限 HTTPS 和 YouTube 網域白名單（`course-commerce.php:139-150`）。
- 商品「課程商品」勾選（`course-commerce.php:83-103`）：nonce 加 `edit_post`。訂單「重新寄送」動作（第 224-227 行）檢查 `edit_shop_order`。
- 自取地址設定：走 Settings API 加 `sanitize_textarea_field` 和長度上限；前台與信件輸出用 `esc_html`／`nl2br`（`store-operations.php:76-151`）。

**公開表單**
- 不能寫入任意 meta：詢問處理只寫固定欄位清單（`admin.php:525-531`）；`work_id` 必須是已發布的 `bf_work`；材質／尺寸必須是該作品已設定的選項之一（第 504-521 行）；陣列輸入會被 `sanitize_text_field` 清成空字串。
- 沒有 email header injection：通知信的收件人是 `admin_email`、主旨固定、內文只有後台連結，訪客輸入不會進信件（第 532-533 行）；課程信的收件人經 `sanitize_email` 加 `is_email`，主旨經 `sanitize_text_field`。
- 後台儲存型 XSS：標題含訪客姓名，但已經過 `sanitize_text_field`；匿名者寫入時 WordPress 也會套用 kses 過濾；列表由核心跳脫；詳情 meta box 全部 `esc_html`／`esc_url`（`admin.php:114-125`）。
- 附件上傳：0.2.7 起任何上傳都回 400（第 500 行），不會產生公開檔案。
- 轉址：都用 `wp_safe_redirect` 轉回站內固定頁面，沒有開放式重新導向。
- 「已收到」訊息只比對 `sent === '1'`，不回顯輸入。

**前台輸出**
- 主題 `render.php` 全面使用 `esc_html`／`esc_attr`／`esc_url`／`esc_textarea`；價格經 `wp_kses_post`；`?bf_product`／`?bf_variation` 預填只接受已發布、可見的商品和它自己的變化款（第 658-669 行）；短代碼 `[products ids=…]` 只帶整數。
- 版面覆寫在儲存時和輸出時各跑一次 `wp_kses`，即使 options 被改過也不會輸出 script（`page-editor.php:143-145`）。
- `[bfnd_page]` 短代碼只在 key 與目前頁面一致時才輸出（`shortcode-pages.php:42-50`），投稿者塞進文章不會造成影響。

**危險函式與檔案**
- 沒有 `eval`、`unserialize`、`shell_exec`／`exec`／`system`、`wp_remote_*`、`file_put_contents`、`base64_decode`、`create_function`。
- 唯一的 SQL 用 `$wpdb->prepare`（`admin.php:245`）。
- `include`／`require` 都是固定路徑（`functions.php:32`、`index.php:4,7`、外掛主檔第 14-25 行）。`file_get_contents` 讀固定的 `data/content.json`；`copy()` 的來源是外掛內附素材，路徑來自隨包附帶的 JSON，訪客改不到（`admin.php:241-251`）。
- 所有 PHP 檔都有 `ABSPATH` 防護（外掛主檔在第 9 行；`woocommerce.php` 只 require `index.php`，而後者有防護）。沒有除錯輸出；`preview.php` 會開 `display_errors`，但不在打包清單裡（`build_package.py:14-16`），正式站回 404。

**前端 JS**
- 外掛 `public/*.js`、主題 `public/site.js`／`admin.js` 都沒有 `innerHTML`、`insertAdjacentHTML`、`outerHTML`、`document.write`、`eval`、`new Function`，也沒有 `location` 轉址。DOM 都用 `textContent`／`createElement`／`append` 建立；URL 參數只用 `URL.searchParams` 設定連結，不讀進 DOM；YouTube ID 先用正規式驗證再 `encodeURIComponent`（`site.js:31-34`）；`work-options.js:60` 的 `JSON.parse` 來源是伺服器 `esc_attr` 過的 data 屬性。後台 JS 使用媒體庫回傳的 URL 設定 `img.src`。

**資訊外洩與正式站（匿名 GET）**
- `/wp-json/` 共 21 個命名空間：oembed、wc/*、wordfence、wordfence-login-security、yoast、jetpack、wp/v2、wp-site-health、wp-block-editor、wp-abilities、wccom-site。**沒有本專案的自訂命名空間**。和本專案有關的路由只有 `wp/v2/bf_work`、`bf_lifestyle`、`bf_course`、`bf_work_cat`、`bf_series`（核心自動產生）。
- `bf_inquiry`：`public => false`、`show_in_rest => false`；正式站 `/wp-json/wp/v2/bf_inquiry` 回 404，`/?post_type=bf_inquiry` 只顯示首頁、沒有詢問內容。`/wp-json/wp/v2/bf_course` 匿名只回 4 筆 `publish`，欄位沒有 `meta`，回應裡沒有 youtube／access 字樣；課程連結存在受保護的 `_bfnd_course_access_url`，前台沒有任何地方輸出它。
- oEmbed 不洩漏作者名稱（`author_name` 是 null）。
- `/wp-json/wp/v2/users` 回 401，`/wp-json/wp/v2/users/1` 回 404，`/?author=1` 回 404：使用者列舉已被擋。
- `/wp-content/uploads/`、外掛 `src/`、`/wp-includes/` 都沒有目錄列表（404）；`/wp-content/plugins/`、主題目錄回空白的 200（`index.php`）。
- `/wp-content/debug.log`、`/.git/HEAD`、`/.env` 回 404；`/wp-config.php.bak` 回 403（只代表被規則擋下，不代表檔案存在）。
- 有 HSTS（`max-age=63072000; includeSubDomains`），前面有 Cloudflare，主機端有機器人挑戰頁（連續請求後觸發）。

## 推測與證據的區分

- **有程式碼證據**：M1、L1、L2、L3、L4、L8、L9、L10，以及所有「未發現問題」的程式碼結論（附 檔案:行號）。
- **有正式站 GET 證據**：M2（XML-RPC 回「XML-RPC server accepts POST requests only.」）、L5、L6、L7、L10 的付款方式清單，以及使用者列舉、目錄列表、debug.log、REST 命名空間等結論。
- **推測或待確認**：H1（只有 2026-09-17 文件紀錄，內容和來源都未查證，**不宣稱是惡意，也不宣稱根因已確認**）；M2 的 POST 是否被 Cloudflare 或 Wordfence 擋下未測；L10 日後是否會成立，取決於付款方式設定。

## 未能驗證的範圍

- 連續約 27 次 GET 後，正式站曾短暫回傳主機的機器人挑戰頁（標題 "One moment, please..."）。我沒有繞過，等它自動解除後才補查 REST 和 Store API。readme.html 是否含版本字樣沒有再確認。
- Wordfence 和 Jetpack 是從 REST 命名空間推斷「已安裝並啟用」；它們的設定（雙因素驗證、登入限制、XML-RPC 選項）沒有查。
- 沒有檢查：WordPress 核心、WooCommerce 11.1.2、綠界外掛、Yoast 和其他第三方外掛的版本漏洞；管理員帳號清單、密碼強度、雙因素驗證；登入失敗限制；主機檔案權限、備份、資料庫；Cloudflare WAF 規則。這些需要後台或主機權限。
- 本報告不代表網站已通過資安檢查。H1 查明之前，不能宣稱全站沒有遭入侵。
