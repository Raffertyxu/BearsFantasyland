# 飛熊入夢官網：接手待辦清單

更新：2026-10-07（Claude，主題 1.3.42／外掛 0.5.40 上線）
語言：與使用者溝通一律用**繁體中文（台灣）**；程式碼與 commit message 用英文。

---

## 0. 先看這裡：目前正式站狀態

| 項目 | 狀態 |
|---|---|
| 正式站 | https://a1.haotaimaker.com/ （WordPress + WooCommerce 11.1.2 + Yoast + Wordfence + 綠界 ECPay） |
| 啟用主題 | 「飛熊入夢」**1.3.42**（`NEWDESIGN/theme/dist/bears-fantasyland-1.3.42.zip`） |
| 啟用外掛 | 「飛熊入夢 NEWDESIGN 官網」**0.5.40**（`NEWDESIGN/website/dist/bears-fantasyland-newdesign-0.5.40.zip`） |
| 與正式站一致的程式碼 | `claude/batch1-inquiry-shop-link-filter` 分支（**尚未併入 `main`**） |
| 回退用 ZIP | 主題 1.3.35～1.3.41、外掛 0.5.36／0.5.38／0.5.39 都在 `dist/`（Git LFS） |
| 綠界 | **正式收款模式**（「啟用測試模式」關閉）。刷卡測試會真的扣款 |

每次上線的完整紀錄、SHA-256 與即時驗收結果：`NEWDESIGN/website/DEPLOYMENT.md` 最上面幾段。

---

## 1. 等使用者回覆或動手的事（優先）

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
  - [ ] 🟠 `xmlrpc.php` 開啟。先確認 Jetpack 是否在用，再用 Wordfence 關閉 XML-RPC 驗證。改設定前要問使用者。
  - [ ] 低風險 10 條見報告。分支 `claude/batch2-security-hardening`（主題 1.3.43／外掛 0.5.41，**待上線**）已修程式能修的 7 條：
    - L1 匯入頁不再因 `run=1` 連結自動送出；L2 詢問表單 `?work=`／`?course=` 只接受已發布內容；L3 前台私密作品／課程改看 `read_private_posts`；
    - L5 主題 ZIP 不含 README.md，外掛 `data/` 加 `.htaccess` 禁止直接讀 `content.json`（上線後要驗證回 403）；
    - L8 一次性搬移改在管理員的後台請求執行（`bfnd_run_pending_migrations`，`admin_init`），訪客請求不再跑；
    - L9 商店經理可儲存「飛熊商務設定」；L10 貨到付款／轉帳／支票訂單不自動寄線上課程連結，需人工「重新寄送」。
    - 未做：L4（詢問改成只有管理員可看，會影響編輯／商店經理帳號，要先問使用者）、L6／L7（主機設定）。
- [ ] **重複造輪子清理（見 `NEWDESIGN/website/NATIVE-REUSE-AUDIT-2026-10-06.md`）**：估計可刪或改用原生功能 3,000 行以上。要分批做、每批都驗證，開始前先問使用者。
  1. ✅ 外掛 0.5.40／主題 1.3.42，**2026-10-07 已上線**（目前沒有作品綁商品，前台還看不到按鈕）：作品編輯頁「對應的商店商品」下拉選單，作品頁底部顯示「前往選購」。**不要合併** `claude/upload-plugin-theme-files-15c122`（1.3.36／0.5.37「作品加入購物車」）。它建在 1.3.35 上，合併會蓋掉 1.3.37～1.3.41 的商品頁。改成作品頁存一個 Woo 商品 ID，放「前往選購」連到商品頁（約 20 行）。
  2. 刪掉用不到的程式（約 2,500 行）：
     - 已用完的匯入／遷移工具；
     - 主題啟用時根本不載入的外掛備援 renderer（外掛 `src/render.php`）；
     - 沒掛上 hook 的舊程式。

     刪之前先唯讀確認：6 個遷移完成標記都已寫入、每件作品與課程都有特色圖片、網站版面 8 頁都已連接。
  3. 線上課程付款後寄 YouTube 連結，改用 Woo 內建的「購買備註」。改之前要用測試訂單確認通知信會帶出備註。

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

## 3. Claude 可以接著做的小事（做之前先告知使用者）

- [x] （主題 1.3.42，**2026-10-07 已上線**，7 個商品頁已確認不再出現：商品與所有變化都沒填貨號時隱藏整行）商品資訊出現「貨號: 不提供」（可變商品沒填 SKU 時 Woo 的預設行為）。可在 `product.css` 隱藏，或請客戶填貨號。
- [ ] 後台「外觀 > 自訂 > 額外 CSS」第 3–5 行是 LINE 按鈕顏色的舊規則。主題已用更高特異性蓋過，可在使用者同意後刪除。
- [ ] 主題 `src/render.php` 與外掛 `src/render.php` 是兩份不同步的副本。主題啟用時只用主題那份。
- [ ] `HANDOFF.md`、`README.md` 中舊的版本敘述可整併。

---

## 4. 分支地圖（全部已推上 GitHub `Raffertyxu/BearsFantasyland`）

| 分支 | 內容 | 處置 |
|---|---|---|
| `main` | 主題 1.3.41／外掛 0.5.39（落後正式站一版） | 從這裡開新分支 |
| `claude/batch1-inquiry-shop-link-filter` | = 正式站（主題 1.3.42／外掛 0.5.40） | 待併入 main |
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
