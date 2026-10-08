# 飛熊入夢官網：接手待辦清單

更新：2026-10-07（Claude，主題 1.3.42／外掛 0.5.40 上線）
語言：與使用者溝通一律用**繁體中文（台灣）**；程式碼與 commit message 用英文。

---

## 0. 先看這裡：目前正式站狀態

| 項目 | 狀態 |
|---|---|
| 正式站 | https://a1.haotaimaker.com/ （WordPress + WooCommerce 11.1.2 + Yoast + Wordfence + 綠界 ECPay） |
| 啟用主題 | 「飛熊入夢」**1.3.45**（`NEWDESIGN/theme/dist/bears-fantasyland-1.3.45.zip`） |
| 啟用外掛 | 「飛熊入夢 NEWDESIGN 官網」**0.5.42**（`NEWDESIGN/website/dist/bears-fantasyland-newdesign-0.5.42.zip`） |
| 與正式站一致的程式碼 | `main` 分支 |
| 回退用 ZIP | 主題 1.3.35～1.3.42、外掛 0.5.36／0.5.38～0.5.40 都在 `dist/`（Git LFS） |
| 綠界 | **正式收款模式**（「啟用測試模式」關閉）。刷卡測試會真的扣款 |

每次上線的完整紀錄、SHA-256 與即時驗收結果：`NEWDESIGN/website/DEPLOYMENT.md` 最上面幾段。

---

## 1. 等使用者回覆或動手的事（優先）

- [x] 合作頁聯絡清單跑版（主題 1.3.44 回歸）：主題 `1.3.45` 2026-10-07 已上線修正。
- [x] （2026-10-07 已重存覆寫並移除 site.js 補丁，主題 1.3.53）合作頁網站版面覆寫錯位一列的根本修法：合作頁網站版面文字覆寫錯位一列（伺服器輸出表單說明取代市話），重新儲存覆寫後才能移除 `site.js` 的補丁。**`site.js` 這段補丁不要刪。**

- [ ] **課程付款測試**
  - 使用者要自己下一筆單：學堂頁 → 入門班 → 選梯次 → 結帳 → 付款選「**綠界超商代碼**」→ 下單 → **不去繳費**。
  - 使用者回報「下好了」之後，到後台確認：
    1. 訂單有「課程／方案」欄位；
    2. 梯次名額從 6 變 5；
    3. 課程報名提示有出現。
  - 確認完把這筆測試訂單取消，名額會回補。
  - 規定：AI 不可代為付款或操作金流。
- [x] **家具頁兩排篩選要不要合併**（使用者選「系列改下拉選單」；已在分支 `claude/batch1-inquiry-shop-link-filter` 做好，主題 1.3.42，**2026-10-07 已上線**）（/furniture/：上排「家具分類」`bf_work_cat`，下排「作品系列」`bf_series`）
  - 建議：保留分類頁籤，系列改成搜尋框旁的下拉選單。等使用者決定。
  - 程式位置：主題 `src/render.php` 約 194–205 行；前端篩選在 `public/site.js` 約 119 行（`data-series-filter`）。
- [ ] **資安（見 `NEWDESIGN/website/SECURITY-AUDIT-2026-10-06.md`）**
  - [ ] 🔴 主機上來源不明的 `wp-content/mu-plugins/customize-controlsse.php`：每次請求都會執行，9/17 起就記錄，至今未查明。需主機權限下載檢視，或請主機商協助。**不可宣稱已確認是惡意或已確認根因**（見 `PM-AUDIT.md`）。使用者可能需要一份給主機商的說明。
  - [x] （外掛 0.5.40，**2026-10-07 已上線**，限流未在正式站實測：每 IP 10 分鐘 5 次、全站每小時 40 次、欄位長度上限）🟠 詢問表單沒有頻率限制與伺服器端長度限制（外掛 `src/admin.php` 約 500 行 `bfnd_inquiry`），可能被灌單。可加 transient 依 IP 限流＋欄位長度上限。
  - [x] 🟠 `xmlrpc.php`：確認未安裝 Jetpack 後，外掛 0.5.43（`src/hardening.php`）整個關閉，**2026-10-07 已上線**，POST 回 403。
  - [ ] 停用但未刪除的外掛：Code Snippets、三個 BF Shop System（其中兩個同名）。檔案仍在主機上，建議確認不用後刪除（刪除前問使用者）。
  - [ ] 飛熊日誌分類新舊混用（10 個，皆 0 篇）與回收桶 8 篇示範／測試文章：請使用者決定保留哪些分類；永久刪除須由使用者自己操作。
  - [ ] 低風險 10 條見報告。分支 `claude/batch2-security-hardening`（主題 1.3.43／外掛 0.5.41，**2026-10-07 已上線**）已修程式能修的 7 條：
    - L1 匯入頁不再因 `run=1` 連結自動送出；L2 詢問表單 `?work=`／`?course=` 只接受已發布內容；L3 前台私密作品／課程改看 `read_private_posts`；
    - L5 主題 ZIP 不含 README.md，外掛 `data/` 加 `.htaccess` 禁止直接讀 `content.json`（上線後要驗證回 403）；
    - L8 一次性搬移改在管理員的後台請求執行（`bfnd_run_pending_migrations`，`admin_init`），訪客請求不再跑；
    - L9 商店經理可儲存「飛熊商務設定」；L10 貨到付款／轉帳／支票訂單不自動寄線上課程連結，需人工「重新寄送」。
    - L4（詢問只給管理員看）：使用者 2026-10-07 同意，外掛 0.5.42，**2026-10-07 已上線**。
    - 未做：L6／L7（主機設定）。
- [ ] **重複造輪子清理（見 `NEWDESIGN/website/NATIVE-REUSE-AUDIT-2026-10-06.md`）**
  1. ✅ 作品連商品頁「前往選購」：外掛 0.5.40／主題 1.3.42，2026-10-07 已上線（目前沒有作品綁商品，前台還看不到按鈕）。**不要合併** `claude/upload-plugin-theme-files-15c122`。
  2. ✅（使用者 2026-10-07 同意；外掛 0.5.42／主題 1.3.44，**2026-10-07 已上線**）刪掉用不到的程式約 1,700 行：
     - 匯入／上線／還原工具、啟用時寫入種子資料、6 段一次性搬移（上線前已確認 6 個完成旗標皆為 1）；
     - 外掛備援 renderer（`src/render.php`、`templates/site.php`、`public/style.css`／`interactions.css`／`site.js`、logo 圖），改成主題沒啟用時在後台顯示提醒；
     - 沒掛 hook 的舊版面 meta box 與短代碼轉換頁；主題沒載入的 `public/admin.js`／`admin.css`。（`site.js` 電話補丁在 1.3.44 誤刪，1.3.45 已放回，見上方 🔴。）
     - 外掛 ZIP 不再打包 `assets/`（40 MB）、`data/`。這兩個資料夾仍留在 repo 給本機 `preview.php` 用。上線前已用登入狀態掃過 34 頁（含私密作品、課程），沒有任何頁面引用外掛的 `assets/`。
  3. ✅（外掛 0.5.42，**2026-10-07 已上線**）線上課程連結改用 Woo「購買備註」：移除課程的「線上課程 YouTube 連結」欄位、付款後自動寄信與「重新寄送」訂單動作（上線前確認 7 堂課都是實體課、都沒填連結）。`ADMIN-GUIDE.md` 已更新。**還沒用測試訂單確認通知信會帶出購買備註**，第一堂線上課上架前要測。

- [x] （2026-10-08 已上線：外掛 0.5.53／主題 1.3.54，首頁、生活木作、木作學堂、購買與服務，位置皆在頁尾前）**各頁「自由內容區」**：網站版面固定區塊只有 RUD（改、隱藏、恢復），沒有新增。方案：在部分頁面最下方、頁尾之前加一個區塊，內容用 WordPress 原生區塊編輯器（該頁本身的內容），沒內容時不顯示，不另造「新增區塊」系統。
  - 建議加：**木作學堂**（開課公告、梯次異動）、**購買與服務**（運費、退換貨、保固、FAQ）、**首頁**（年節休假、展覽、限時活動）。
  - 可加可不加：**生活木作**（新系列介紹）。
  - 跟著設計稿不加：家具作品（新增就是新增作品）、品牌故事、合作提案、飛熊日誌（新增就是發文章）。
  - 待使用者決定：(1) 上述分配與生活木作要不要加；(2) 位置是否都放頁尾前，或學堂頁放在課程列表上方方便放開課公告。
  - 實作時注意：網站版面以區塊順序對應覆寫（`page-editor.php` 的 `section_N`），新增區塊要加在各頁最後、或加 `data-bfnd-design-ignore`，避免後面區塊的覆寫錯位；教學頁（`src/admin-guide.php`）要補一節。
- [x] 留言與商品評價已關閉（外掛 0.5.52，2026-10-07）；要恢復就移除 `src/hardening.php` 最後一段。

## 2. 客戶要提供的資料（目前都是預設值，要換成真的）

- [ ] **商品 1208「延展之境櫻桃木桌」的木種價格**（可變商品、屬性「木種」）。名稱含「櫻桃木」，但也能選其他木種，名稱可能要改。

  | 木種 | 原價 | 特價 | 變化 ID |
  |---|---|---|---|
  | 櫻桃木 | 4,200 | 3,800 | 1689 |
  | 胡桃木 | 5,000 | 4,500 | 1690 |
  | 白橡木 | 3,900 | 3,500 | 1691 |

  改之前的商品資料備份在 `NEWDESIGN/website/qa-screenshots/product-page/product-1208-backup-before-wood-variations.json`。

- [ ] **課程**：正式價格、梯次日期、名額，CNC 的時數與價格，線上課的 YouTube 網址。

  | 課程（課程 ID → 商品 ID） | 目前預設 |
  |---|---|
  | 木工基礎入門班（1418 → 1695） | 11 月梯／12 月梯「上課日期另行通知」，各 NT$3,680、6 名 |
  | 磨刀實戰班（1420 → 1698） | 同上，NT$3,680 |
  | CNC 數位木工（1419 → 1701） | 同上，NT$4,800（價格是猜的） |
  | 自由創作會員（1421 → 1704） | 4 次 4,800／8 次 8,800／12 次 12,000（頁面原價） |

  - 私密課程 1662／1663／1664（36 小時方案、LEVEL 2／3）尚未公開、未綁商品。
- [ ] 商品描述、規格（Woo「屬性」）、圖片：由客戶在後台填。模板會自動顯示「01 / ABOUT」「02 / SPECIFICATION」，沒填就自動隱藏。

- [ ] 17 件作品木材選項目前是預設（胡桃木／櫻桃木／檜木／硬楓木／栓木），8 件作品材質仍寫「木種待確認」，尺寸選項全空：請客戶在作品編輯頁確認。
- [ ] 使用者自行刪除：舊日誌分類 5 個（飛熊誌、熊熊速報、活動速報、工坊公告、最新消息）、回收桶 8 篇、停用外掛 4 個（Code Snippets、BF Shop System ×3）。

- [x] 後台額外 CSS 已清空（2026-10-07，備份 `NEWDESIGN/website/qa-screenshots/custom-css/additional-css-backup-2026-10-07-full.css`）。
- [ ] 首頁輪播原圖只有 1448px 寬，大螢幕模糊：請客戶提供 ≥3840px 原圖。
- [ ] 合作頁「06 / AFTER SERVICE」區與其他區塊左緣不對齊：確認是否刻意出血。

- [ ] 使用者自行刪除空的舊商品分類「椅子」「經典系列」（商品 → 分類）。
- [ ] 使用者自行刪除兩個舊選單「Primary (2)」「主要選單」（外觀 → 選單，皆未使用）。
- [ ] 頁首頁尾已可在後台改（外觀 → 選單、外觀 → 自訂 → 飛熊入夢頁首頁尾）；可告知客戶。

## 3. Claude 可以接著做的小事（做之前先告知使用者）

- [x] （主題 1.3.42，**2026-10-07 已上線**，7 個商品頁已確認不再出現：商品與所有變化都沒填貨號時隱藏整行）商品資訊出現「貨號: 不提供」（可變商品沒填 SKU 時 Woo 的預設行為）。可在 `product.css` 隱藏，或請客戶填貨號。
- [x] 後台「外觀 > 自訂 > 額外 CSS」頁尾社群連結顏色 3 行：使用者 2026-10-07 同意，已在正式站刪除並發布（原內容備份 `NEWDESIGN/website/qa-screenshots/custom-css/additional-css-backup-2026-10-07.css`）。
- [x] 主題與外掛兩份 `render.php`：外掛那份已在 0.5.42 刪除（已上線）。
- [ ] `HANDOFF.md`、`README.md` 中舊的版本敘述可整併。

---

## 4. 分支地圖（全部已推上 GitHub `Raffertyxu/BearsFantasyland`）

| 分支 | 內容 | 處置 |
|---|---|---|
| `main` | = 正式站（主題 1.3.45／外掛 0.5.42） | 從這裡開新分支 |
| `claude/batch1-inquiry-shop-link-filter`、`claude/batch2-security-hardening`、`claude/batch3-cleanup` | 已併入 main | 可刪 |
| `claude/product-page-design-2cb8d1` | 本 session 的工作，已併入 main | 可刪 |
| `claude/website-rwd-large-screens-777491` | 1.3.35 大螢幕等比例縮放，已包含在 main | 可刪 |
| `claude/upload-plugin-theme-files-15c122` | 未上線的 1.3.36／0.5.37「作品加入購物車」（WIP commit） | **不要合併**，見 1.4 |
| `claude/advertisement-video-production-e1ebf9` | 合作 60 秒影片原始檔與成品（WIP commit） | 影片專案，未審查 |
| `claude/new-video-submissions-source-code-f109ee` | 品牌 45 秒、家具 30 秒廣告與配樂（WIP commit） | 影片專案，未審查 |
| `claude/video-planning-458d0c` | Alba 托盤生活木作 30 秒影片企劃與配樂（WIP commit） | 影片專案，未審查 |

大檔（ZIP、mp4、mp3、wav）走 Git LFS。clone 之後要先跑 `git lfs install` 再跑 `git lfs pull`。

---

## 5. 怎麼上線（本 session 實際使用的流程）

使用者**不操作終端機**，上線由 Claude 透過已登入的 WordPress 後台完成。**每一版都要使用者明確授權**，先前的授權不能延用到下一版。

1. 改程式。升版號：
   - 主題：`style.css` 標頭與 `functions.php` 的所有 enqueue 版本字串；
   - 外掛：`bears-fantasyland-newdesign.php` 標頭與 `BFND_VERSION`。

   跳號時要避開其他分支已用過的號碼。
2. `cd NEWDESIGN/website && npm install && npm run check`：JS 語法檢查，以及 CSS 長度必須寫成 `calc(N*var(--bfnd-px))`；可用 `npm run fix:css-units` 自動轉換。接著 `php -l` 檢查改過的 PHP 檔。
3. 打包：
   - 主題：`python NEWDESIGN/theme/build_package.py`，複製成 `dist/bears-fantasyland-X.Y.Z.zip`；
   - 外掛：`python NEWDESIGN/website/scripts/build_package.py`，複製成 `dist/bears-fantasyland-newdesign-X.Y.Z.zip`。

   **`NEWDESIGN/website/dist` 被 .gitignore 排除，要 `git add -f`。** 記下 SHA-256。
4. commit，並 push 到 GitHub。
5. 後台上傳。原因：內建瀏覽器無法選取本機檔案，Chrome 擴充功能的上傳又限 10 MB，而 ZIP 約 40 MB。做法：
   1. 開 `wp-admin/theme-install.php?upload`（外掛是 `plugin-install.php?tab=upload`）；
   2. 在頁面 JS 用 `fetch('https://media.githubusercontent.com/media/Raffertyxu/BearsFantasyland/<branch>/<path>.zip')` 下載，該網址允許跨來源存取；
   3. 用 `crypto.subtle.digest` 比對 SHA-256；
   4. 用 `DataTransfer` 放進 `input[name=themezip]`（外掛是 `pluginzip`），按 `#install-theme-submit`（外掛是 `#install-plugin-submit`）；
   5. 到下一頁，開啟「使用已上傳版本取代…」連結。

   GitHub 下載速度不穩，從 30 KB/s 到數 MB/s 都有，要用背景 promise 輪詢。
6. 用**未登入**的 Playwright 驗證：主題標頭 `Version`、資源 `?ver=`、各主要頁面 HTTP 200、這次修改的功能。結果寫進 `DEPLOYMENT.md` 最上面。

---

## 6. 這次踩到的坑（改程式前必讀）

- **`?product=`、`?variation=` 是 WordPress／Woo 的查詢變數**，加在一般頁面網址上會變成 404。自訂參數一律加前綴，例如 `bf_product`、`bf_variation`。
- **網站版面（外掛 `src/page-editor.php`）依「第幾個區塊、區塊內第幾段文字」套後台覆寫**（`section_N_text_M`）。
  - 區塊如果條件式不輸出，後面的覆寫全部錯位。合作頁「JOURNAL」標題就是這樣來的。
  - 不要讓頁面上的區塊數量變動：沒內容時輸出隱藏的佔位區塊。
  - 0.5.39 起，覆寫文字與預設文字相同時保留預設 HTML，修好了換行被吃掉的問題。
- **外掛 `bfnd_save_meta` 會把沒有送出的欄位存成空值**。改課程或作品的 meta 時，必須送出整份 meta box 表單。本次的做法：解析編輯頁的 `.metabox-base-form` 與 `.metabox-location-*` 表單，用 FormData POST 到 `meta-box-loader` 網址，並比對儲存前後所有 `bfnd[...]` 欄位。
- **WooCommerce 不在可變商品的上層存「虛擬」**，只存在各個變化上。0.5.38 已修正課程商品的判斷。
- 很多檔案是 CRLF 換行。用腳本改檔時要保持原本的換行格式。
- 主題的很多 class 規則會輸給 `body.bfnd-body p` 和 `body.bfnd-body a`（`style.css` 第 7 行）。要改文字顏色，選擇器必須加上 `body.bfnd-body` 前綴。
- 本機 `preview.php` 沒有 WooCommerce。要預覽商品頁，用 `NEWDESIGN/website/scripts/preview_product_harness.php`（用法寫在檔頭）。

## 7. 這次的報告與截圖

- 審查報告：
  - `NEWDESIGN/website/SECURITY-AUDIT-2026-10-06.md`：資安，高 1、中 2、低 10。
  - `NEWDESIGN/website/NATIVE-REUSE-AUDIT-2026-10-06.md`：重複造輪子盤點。
  - `NEWDESIGN/website/CONTRAST-AUDIT-2026-10-06.md`：顏色對比，23 處已在 1.3.40／1.3.41 修好。
- 截圖：`NEWDESIGN/website/qa-screenshots/`，分成 `product-page/`、`course/`、`contrast/` 三個資料夾。
