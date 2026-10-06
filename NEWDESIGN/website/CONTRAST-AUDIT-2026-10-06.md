# 全站文字顏色對比度稽核（正式站）

- 稽核日期：2026-10-06　目標：https://a1.haotaimaker.com/（主題版本 `ver=1.3.39`）
- 性質：**只讀**。沒有改任何檔案、沒有動後台；所有「改後對比」都是在稽核用的瀏覽器分頁裡本機注入 CSS 後重測的結果，正式站沒有被修改。
- 證據分級：本檔所有數字都是 2026-10-06 當天對正式站實際渲染的重新量測（不是引用舊文件）。「建議改色」是已在本機注入驗證的方案，**尚未進入原始碼、ZIP、也未上線**。

## 1. 結論摘要

| 項目 | 數量 |
|---|---|
| 檢查範圍 | 17 個頁面 × 2 種寬度（1440×900、390×844）= 34 個頁面檢視 |
| 量測樣本 | 一般狀態 2,540 筆文字行、hover 狀態 307 筆、輪播逐張 366 筆、placeholder 12 筆 |
| 不合格（依「同一條 CSS 規則」合併） | **23 條**：嚴重（對比 < 2.5）**8 條**、一般 **15 條** |
| 不合格的文字行（未合併，含輪播逐張、手機／桌機重複） | 215 行（嚴重 71、一般 144） |
| 本機注入建議 CSS 後重測 | 全站不合格 **0 條**（第 5 節；手機選單展開時被選單蓋住的內容已排除，那是量測誤報） |

**前五名最嚴重**

1. 頁尾「加入官方 LINE」按鈕 hover：#8a654b 字壓 #087a3a 綠底，**1.05**（使用者回報案例 2，17 頁全站皆有）
2. 木作學堂底部 CTA 副標：muted 灰字壓照片，**1.22**（使用者回報案例 1）
3. 首頁 Hero 右側直排字 GOOD WOOD BETTER LIVING.：白字壓照片亮處，**1.44**
4. Hero 小標 kicker（首頁／家具頁輪播第 2、3 張與手機版）：**1.50**
5. 首頁雙欄 Promo 小標「WOODWORKING SCHOOL」：**2.07**

**三個系統性根因（比個別顏色更重要）**

1. **特異性陷阱**：style.css:7 的 `body.bfnd-body p{color:var(--bf-muted)}` 與 `body.bfnd-body a{color:inherit}`（特異性 0,1,2）會壓過所有單一 class 的規則（0,1,1）。`.bf-school-cta p{color:#fff}`（style.css:77）、`.bf-direct-contact-row>a{color:var(--bf-wood)}`（style.css:186）都因此從未生效——這就是案例 1 看不見的原因。同類風險規則（寫了顏色但會被 `body.bfnd-body p` 蓋掉）還有 `.bf-work-card p`（意圖 wood，實測為 muted）、`.bf-philosophy-longform p`（意圖 ink，實測為 muted）、`.bf-footer-brand p`、`.bf-lifestyle-cta p`（有 `!important` 倖免）等，目前剛好仍合格，但改動時要小心。
2. **後台「額外 CSS」蓋過主題**：正式站 `<head>` 內有 `<style id="wp-custom-css">`（WordPress 外觀 > 自訂 > 額外 CSS，存在資料庫，不在 repo 主題檔），第 3–5 行把頁尾所有 `a` 設成 #493325、hover 設成 #8a654b，且因為最後輸出而贏過 interactions.css 的 LINE 按鈕規則。這是案例 2 的真正來源，**光改主題檔的顏色沒用，要提高主題選擇器特異性或刪除後台那 3 行**。（style.css:141-142 有一份同樣的副本，所以「刪掉後台那 3 行」不會讓其他頁尾圖示變色。）
3. **照片疊層太淡**：Hero、CTA、宣言區都是「照片 + 很淡的漸層」再放白字／淺色字，輪播換到亮照片或手機版文字橫跨整寬時就不合格。需要的是更深的整面疊層，而不是逐段調字色。

## 2. 方法與限制

- 工具：Playwright（`playwright-core` + 本機 Chrome，未登入、DPR 1），逐頁捲動觸發 reveal／lazy image 後，每 750px 量一段視窗。
- 對比算法：把頁面上**所有文字暫時隱藏**（`color:transparent`）後截圖，在每個文字行的矩形框內逐像素取背景，將前景色（含文字透明度、祖先 opacity）與每個像素算 WCAG 2.x 對比，**報告「最差 10%」像素的對比值**。純色背景時等於精確的 WCAG 值；照片／漸層背景時是對最亮（或最暗）處的保守估計。
- 門檻：一般文字 < 4.5、大字（≥24px，或 ≥18.66px 且粗體）< 3 為不合格；< 2.5 列嚴重。
- 狀態：一般狀態、桌機 hover（每個 class 組合各 hover 一次，共 307 筆）、首頁／家具／生活木作／木作學堂／合作提案的 Hero 輪播（暫停後逐張，3 張）、桌機「木作學堂」下拉、手機選單展開與課程子選單、placeholder（12）、select（10）、input 按鈕（2）、麵包屑、頁尾小字。
- 來源規則：以 Chrome DevTools Protocol 的 `CSS.getMatchedStylesForNode`（hover 另用強制 `:hover`）找出實際勝出的 `color` 宣告，再用 Grep 對回 repo。正式站 `style.css`、`interactions.css`（`ver=1.3.39`）與 repo 內容逐行 diff 相同；**後台額外 CSS 不在 repo**。style.css 為單行壓縮檔，所以多數規則只能給「行 7」。
- 稽核過程的插曲：連續快速請求後，主機一度回傳「请稍候… / One moment, please」驗證頁（連 curl 也一樣），我沒有繞過，等幾分鐘自行解除後降速（每頁間隔 6 秒）重跑。之後若用自動化工具高頻檢查正式站，可能再被擋。
- **未涵蓋**：登入後頁面（我的帳號內頁、結帳、訂單）、購物車有商品時、商品變體未選時的停用按鈕（測試商品載入時 `disabled` 元素 0 筆）、WooCommerce 通知訊息、圖片燈箱、404／搜尋結果頁、`:focus-visible` 狀態、圖片內嵌文字、SVG／偽元素文字（如頁尾 LINE 圖示的 `::before`）、非文字對比（WCAG 1.4.11）。這些沒有重新跑過，狀態為「待確認」。

## 3. 不合格清單（嚴重，對比 < 2.5）

| # | 嚴重度 | 頁面（寬度） | 元素文字（前 20 字） | CSS selector | 前景色 | 背景 | 對比值（最差／門檻） | 來源 CSS 檔案:行號 | 建議改色（改後實測對比） | 截圖 |
|---|---|---|---|---|---|---|---|---|---|---|
| 1 | 嚴重 | 全站 17 頁的頁尾（1440；hover） | 加入官方 LINE／↗ | `div.bf-footer-main.bf-wrap > div.bf-footer-social > a.bf-footer-line > span ; div.bf-footer-main.bf-wrap > div.bf-footer-social > a.bf-footer-line > span.bf-social-arrow`<br>（共 34 處） | #8a654b | #087a3a（純色） | **1.04** / 4.5<br>state=hover | 實際勝出：WordPress 後台「外觀 > 自訂 > 額外 CSS」(`<style id="wp-custom-css">` 內嵌於 head，**不在主題檔**) 第 4–5 行 `.bf-footer .bf-footer-main .bf-footer-social a:hover{color:#8a654b}`；主題 style.css:142 有同一條副本。應勝出的 interactions.css:264-270 `.bf-footer-line:hover{background:var(--bf-line-deep);color:var(--bf-white)}` 因特異性相同且額外 CSS 最後輸出而落敗（背景綠色是 interactions.css:266-267 生效、文字色沒生效）。平常（未 hover）狀態同樣被額外 CSS 第 3 行 `color:#493325` 蓋掉 interactions.css:242 的 `--bf-line-ink`，但白底上對比 11.8 合格。 | 在 interactions.css:264 把選擇器加重為 `body.bfnd-body .bf-footer .bf-footer-main .bf-footer-social > a.bf-footer-line:hover{color:var(--bf-white)}`（並一併處理 :234 的常態色 `--bf-line-ink`）。白 #ffffff 於 #087a3a 理論 5.45。根治：刪除後台額外 CSS 第 3–5 行（與 style.css:141-142 重複）。<br>改後實測最差 5.44 | `home-d-hover-007.png` |
| 2 | 嚴重 | /woodworking-school/ (390/1440) | 歡迎與我們聊聊，找到最適合你的學習路徑。 | `main#bf-main > section.bf-school-cta > div.bf-wrap > p`<br>（共 2 處） | #75685e | 圖片（取樣最差 #63422d 均色） | **1.21** / 4.5 | style.css:7 `body.bfnd-body p{color:var(--bf-muted)}`（特異性 0,1,2）勝過 style.css:77 `.bf-school-cta p{color:#fff}`（0,1,1），白字從未生效；背景為 render.php:378 inline `linear-gradient(90deg,#24180dcc,#24180d22)` + 照片（右側漸層只剩 13% 不透明，照片亮處直接露出）。 | `body.bfnd-body .bf-school-cta p{color:var(--bf-white)}`，並於 `.bf-school-cta` 加整面 `::before{background:rgba(36,24,13,.35)}` 疊層（.bf-wrap 設 position:relative）。<br>改後實測最差 9.42 | `school-m-073.png` |
| 3 | 嚴重 | / (1440；slide-01/03、slide-02/03、slide-03/03) | GOOD WOODBETTER LIVI | `main#bf-main > section.bf-home-hero > span.bf-vertical-note`<br>（共 12 處） | #ffffff | 圖片（取樣最差 #d8cdc4 均色） | **1.44** / 4.5 | 文字色繼承 style.css:7 `.bf-home-hero{color:#fff}`；`.bf-vertical-note` 定位於 style.css:7（`right:4%;top:19%`），style.css:19 `max-width:100px`。位置剛好落在 `.bf-home-hero-shade`（style.css:7，右端漸層只剩 `rgba(26,18,13,.08)`）上的照片亮處；`WOODBETTER` 字寬超過 max-width 溢出外框。 | `.bf-vertical-note{background:rgba(26,18,13,.78);padding:12px 10px;width:max-content;max-width:none}`（半透明深色底，不動 hero 構圖）。<br>改後實測最差 12.00 | `home-d-slide-0103.png` |
| 4 | 嚴重 | /furniture/ (390/1440；slide-03/03、slide-01/03)<br>/ (390/1440；slide-02/03、slide-03/03、slide-01/03) | FURNITURE / OUR WORK／BEAR’S FANTASYLAND / | `main#bf-main > section.bf-page-hero.bf-page-hero-photo > div.bf-wrap.bf-page-hero-copy > span.bf-kicker ; main#bf-main > section.bf-home-hero > div.bf-home-hero-content.bf-wrap > span.bf-kicker`<br>（共 10 處） | #e6d7c7 | 圖片（取樣最差 #9b8b79 均色） | **1.50** / 4.5<br>state=slide-03/03 | style.css:7 `.bf-home-hero .bf-kicker,.bf-page-hero .bf-kicker{color:#e6d7c7}`，疊在照片上；輪播第 2、3 張與手機版照片較亮。 | 改 `color:var(--bf-white)`；`.bf-page-hero-overlay`（style.css:7）改 `linear-gradient(90deg,#1d140dcc,#1d140d80 80%)`。<br>改後實測最差 5.74 | `furniture-m-slide-0303.png` |
| 5 | 嚴重 | / (390/1440) | WOODWORKING SCHOOL | `section.bf-split-promos > a > div.bf-promo-copy > span.bf-kicker`<br>（共 2 處） | #e0c7aa | 圖片（取樣最差 #54504f 均色） | **2.06** / 4.5 | style.css:7 `.bf-promo-copy .bf-kicker{color:#e0c7aa}`；底圖 `.bf-promo-image img{filter:brightness(.7)}`（style.css:7）。 | `.bf-promo-copy .bf-kicker{color:var(--bf-white)}` 並 `.bf-promo-image img{filter:brightness(.55)}`。<br>改後實測最差 5.10 | `home-m-069.png` |
| 6 | 嚴重 | / (1440；hover) | 探索家具作品／↗ | `div.bf-home-hero-content.bf-wrap > div.bf-actions > a.bf-button.bf-button-light > span`<br>（共 2 處） | #342b25 | #775640（純色） | **2.10** / 4.5<br>state=hover | 文字色：style.css:7 `.bf-button-light:hover{background:#fff;color:var(--bf-ink)!important}`；背景卻被 style.css:19 `.bf-home-hero .bf-actions .bf-button:first-child{background:var(--bf-wood)}`（特異性 0,4,0）蓋回咖啡色，變成 #342b25 字壓 #775640 底。 | `body.bfnd-body .bf-home-hero .bf-actions .bf-button:first-child:hover{background:var(--bf-white);color:var(--bf-ink)!important}`（墨色於白理論 13.8）。<br>改後實測最差 13.83 | `home-d-hover-006.png` |
| 7 | 嚴重 | /woodworking-school/ (390) | 不知道哪一堂課適合你？ | `main#bf-main > section.bf-school-cta > div.bf-wrap > h2` | #ffffff | 圖片（取樣最差 #624939 均色） | **2.18** / 3（大字） | style.css:76 `.bf-school-cta{color:#fff}` 白字生效，但手機版文字橫跨整寬，render.php:378 inline 漸層右端 `#24180d22` 近乎透明，照片亮處對比不足。 | 同上整面 `::before` 疊層 `rgba(36,24,13,.35)`。<br>改後實測最差 4.22 | `school-m-072.png` |
| 8 | 嚴重 | /brand-story/ (390/1440) | 01／02／03／04 | `main#bf-main > section.bf-story-row > div.bf-story-row-content > span.bf-story-number ; main#bf-main > section.bf-story-row.bf-story-row-reverse > div.bf-story-row-content > span.bf-story-number`<br>（共 8 處） | #b49b83 | #f7f5f1（純色） | **2.42** / 3（大字） | style.css:7 `.bf-story-number{color:#b49b83}`。 | `color:var(--bf-wood)`。<br>改後實測最差 6.04 | `brand-story-d-013.png` |

## 4. 不合格清單（一般）

| # | 嚴重度 | 頁面（寬度） | 元素文字（前 20 字） | CSS selector | 前景色 | 背景 | 對比值（最差／門檻） | 來源 CSS 檔案:行號 | 建議改色（改後實測對比） | 截圖 |
|---|---|---|---|---|---|---|---|---|---|---|
| 9 | 一般 | /works/cherry-island-table/ (390/1440)<br>/product/延展之境櫻桃木桌/ (390/1440)<br>/collaboration/ (390/1440)<br>/woodworking-course/beginner/ (390/1440)<br>/woodworking-course/cnc/ (390/1440) | 02 / SPECIFICATION／04／01／03 | `main#bf-main > section.bf-spec-section > div.bf-wrap > span.bf-index ; main#bf-main > section.bf-spec-section.bf-product-spec > div.bf-wrap > span.bf-index`<br>（共 35 處） | #a38d79 | #eeeae4（純色） | **2.63** / 4.5 | style.css:7 `.bf-index{color:#a38d79}`。 | `.bf-index{color:var(--bf-wood)}`（#775640：於 paper 6.10、warm 5.45、白 6.58）。<br>改後實測最差 5.49 | `work-cherry-d-043.png` |
| 10 | 一般 | / (390/1440；slide-02/03、slide-03/03) | 從一件家具，到一堂木工課。我們用傳統工藝 | `main#bf-main > section.bf-home-hero > div.bf-home-hero-content.bf-wrap > p`<br>（共 4 處） | #ffffff | 圖片（取樣最差 #80756d 均色） | **2.78** / 4.5<br>state=slide-02/03 | style.css:7 `body.bfnd-body .bf-home-hero p{color:#fff}`（白字生效），`.bf-home-hero-shade`（style.css:7）漸層 .72→.43→.08，輪播第 2、3 張照片偏亮。 | `.bf-home-hero-shade{background:linear-gradient(90deg,rgba(26,18,13,.78) 0%,rgba(26,18,13,.62) 50%,rgba(26,18,13,.2) 100%)}`；手機（≤768px）改整面 `rgba(26,18,13,.68)`。<br>改後實測最差 4.93 | `home-d-slide-0203.png` |
| 11 | 一般 | / (1440；slide-02/03) | 成為生活的一部分。 | `section.bf-home-hero > div.bf-home-hero-content.bf-wrap > h1 > span.bf-phrase` | #ffffff | 圖片（取樣最差 #675a52 均色） | **2.99** / 3（大字）<br>state=slide-02/03 | style.css:7 `.bf-home-hero h1…{color:#fff}`，疊層同上。 | 同「首頁 Hero 副標」列：加深 `.bf-home-hero-shade`（style.css:7）。<br>改後實測最差 5.80 | `home-d-slide-0203.png` |
| 12 | 一般 | / (390/1440) | 從土地出發，為下一個世代設計更好的生活。 | `section.bf-manifesto > div.bf-wrap.bf-manifesto-inner > div > p`<br>（共 2 處） | #ffffff | 圖片（取樣最差 #725a4c 均色） | **3.32** / 4.5 | style.css:23 `body.bfnd-body .bf-manifesto p{color:#fff}`（白字生效）；底圖上的遮罩 style.css:19 `.bf-manifesto:before{background:linear-gradient(90deg,#170e0966 0%,#170e094d 55%,#170e0966 100%)}`（僅 30–40% 不透明）。 | `.bf-manifesto:before{background:linear-gradient(90deg,#170e09b3 0%,#170e09a6 55%,#170e09b3 100%)}`。<br>改後實測最差 8.30 | `home-m-070.png` |
| 13 | 一般 | /collaboration/ (1440；hover) | ↗ | `section.bf-collab-hero > div.bf-collab-hero-copy.bf-wrap > a.bf-button > span` | #775640 | 圖片（取樣最差 #ccbeb1 均色） | **3.42** / 4.5<br>state=hover | style.css:7 `.bf-button:hover{background:transparent;color:var(--bf-wood)!important}`；在淺色照片上咖啡字失去襯底。 | `body.bfnd-body .bf-collab-hero .bf-button:hover{background:var(--bf-white)}`（wood 於白理論 6.58）。<br>改後實測最差 6.58 | `collaboration-d-hover-029.png` |
| 14 | 一般 | /collaboration/ (390/1440；slide-01/03、slide-02/03、slide-03/03) | 飛熊入夢以家具設計為核心，結合木工技藝、 | `main#bf-main > section.bf-collab-hero > div.bf-collab-hero-copy.bf-wrap > p`<br>（共 7 處） | #4b3a2b | 圖片（取樣最差 #cfc3b7 均色） | **3.58** / 4.5<br>state=slide-01/03 | style.css:34 `body.bfnd-body .bf-collab-hero p{color:#4b3a2b}`；照片明度隨輪播而變（#b8b1a8–#d3c7ba）。 | `color:var(--bf-ink)`（#342b25）。<br>改後實測最差 4.59 | `collaboration-d-slide-0103.png` |
| 15 | 一般 | / (1440) | A BetterTomorrow. | `main#bf-main > section.bf-manifesto > div.bf-wrap.bf-manifesto-inner > span` | #f4ded0 | 圖片（取樣最差 #7a4b3b 均色） | **4.15** / 4.5 | style.css:19 `.bf-manifesto-inner>span{color:#f4ded0}`，遮罩同上。 | `color:var(--bf-white)` 並加深 `.bf-manifesto:before` 遮罩（同「宣言區小字」列）。<br>改後實測最差 10.86 | `home-d-005.png` |
| 16 | 一般 | /collaboration/ (1440；hover) | 04-24616373 | `div > div.bf-direct-contact > p.bf-direct-contact-row > a` | #8a654b | #f0e9df（純色） | **4.30** / 4.5<br>state=hover | style.css:187 `.bf-direct-contact-row>a:hover{color:#8a654b}`。 | `color:var(--bf-ink)`（#342b25 於 #f0e9df 理論 11.5；與常態 wood 有層次差）。<br>改後實測最差 11.47 | `collaboration-d-hover-030.png` |
| 17 | 一般 | /woodworking-school/ (390/1440)<br>/collaboration/ (390/1440)<br>/woodshop/ (390/1440)<br>/cart/ (390/1440)<br>/works/cherry-island-table/ (390/1440)<br>/product/延展之境櫻桃木桌/ (390/1440)<br>/product-category/手工具/ (390/1440)<br>/my-account/ (390/1440) | 正式課綱與開課資訊確認後，將於此頁更新。／一件好的家具，值得被長久使用。我們提供完／探索飛熊入夢的作品與木作商品。／確認選購的作品與數量，接著完成結帳。 | `section#online-courses > div.bf-course-grid > article.bf-course-preview-panel > p ; section.bf-collab-after > div.bf-wrap.bf-collab-after-inner > div > p`<br>（共 24 處） | #75685e | #eee9e2（純色） | **4.46** / 4.5 | style.css:7 `body.bfnd-body p{color:var(--bf-muted)}`；`--bf-muted:#75685e` 於 warm 底只有 4.46–4.47（差 0.04）。 | 調深 token `--bf-muted`：#75685e → #6c6056（style.css:6 `:root`），於 warm #eee9e2 為 5.05、paper 5.65。一處改動修復全站所有 muted 文字。<br>改後實測最差 5.04 | `school-d-010.png` |
| 18 | 一般 | /woodshop/ (390/1440)<br>/cart/ (390/1440)<br>/product-category/手工具/ (390/1440)<br>/my-account/ (390/1440) | 首頁／／／商品選購／購物車 | `section.bf-commerce-hero > div.bf-wrap > nav.bf-breadcrumb > a ; section.bf-commerce-hero > div.bf-wrap > nav.bf-breadcrumb > span`<br>（共 24 處） | #75685e | #eee9e2（純色） | **4.46** / 4.5 | style.css:45 `.bf-breadcrumb{color:var(--bf-muted)}`（a 繼承，style.css:7 `body.bfnd-body a{color:inherit}`），底 `.bf-commerce-hero{background:#eee9e2}`。 | 同上調深 `--bf-muted`，麵包屑於 #eee9e2 變 5.05。<br>改後實測最差 5.04 | `woodshop-d-034.png` |
| 19 | 一般 | /woodworking-course/beginner/ (390/1440)<br>/woodworking-course/cnc/ (390/1440) | 課程形式／課程時數／課程費用／程度 | `div.bf-course-facts > dl > div > dt`<br>（共 17 處） | #75685e | #eee9e2（純色） | **4.46** / 4.5 | style.css:7 `.bf-course-facts dt{color:var(--bf-muted)}`，底 `.bf-course-facts{background:#eee9e2}`。 | 同上調深 `--bf-muted`。<br>改後實測最差 5.04 | `course-beginner-d-048.png` |
| 20 | 一般 | /collaboration/ (390/1440) | 04-24616373／0921 747 056／bearlovearth@gmail.c／@iaz2765b | `div > div.bf-direct-contact > p.bf-direct-contact-row > a`<br>（共 8 處） | #75685e | #f0e9df（純色） | **4.47** / 4.5 | style.css:7 `body.bfnd-body a{color:inherit}`（0,1,2）勝過 style.css:186 `.bf-direct-contact-row>a{color:var(--bf-wood)}`（0,1,1），設計上的 wood 色從未生效，變成繼承 muted #75685e。 | `body.bfnd-body .bf-direct-contact-row>a{color:var(--bf-wood)}`（wood 於 #f0e9df 理論 5.46）。<br>改後實測最差 5.46 | `collaboration-d-024.png` |
| 21 | 一般 | /collaboration/ (390/1440) | 電話／手機／Email／LINE | `div > div.bf-direct-contact > p.bf-direct-contact-row > span`<br>（共 8 處） | #75685e | #f0e9df（純色） | **4.47** / 4.5 | style.css:185 `.bf-direct-contact-row>span{color:var(--bf-muted)}`，12px、底 #f0e9df（muted 於 warm 底僅 4.47）。 | 調深 token `--bf-muted`：#75685e → #6c6056（style.css:6 `:root`），於 #f0e9df 理論 5.06。<br>改後實測最差 5.05 | `collaboration-d-023.png` |
| 22 | 一般 | / (1440；slide-02/03) | ↗ | `div.bf-home-hero-content.bf-wrap > div.bf-actions > a.bf-button.bf-button-light > span` | #ffffff | #7d756f（純色） | **4.47** / 4.5<br>state=slide-02/03 | style.css:7 `.bf-button{color:#fff!important}` + `.bf-button-light{background:rgba(54,33,20,.3)}`（半透明）。 | 同「首頁 Hero 副標」列：加深 `.bf-home-hero-shade`（style.css:7）。<br>改後實測最差 6.58 | `home-d-slide-0203.png` |
| 23 | 一般 | /collaboration/ (390/1440) | Email 或電話／例如希望再加長 10 公分／例如住宅餐廳、商業空間／可簡述預算範圍或想法 | `form > div.bf-form-row > label > input ; div.bf-form-shell > form > label > input`<br>（共 10 處） | #757575 | #fdfcfb（純色） | **4.49** / 4.5 | 沒有任何 CSS 設定 `::placeholder`，用瀏覽器預設（Chrome #757575）；輸入框底色 style.css:7 `.bf-form-shell input:not([type=file])…{background:#fdfcfb}`。 | 新增 `.bf-form-shell input::placeholder,.bf-form-shell textarea::placeholder{color:var(--bf-muted);opacity:1}`（muted 新值於 #fdfcfb 5.95）。<br>改後實測最差 5.94 | `collaboration-d-026.png` |

欄位說明：「前景色」為與背景合成後的實際色；「背景」純色時給色碼，照片／漸層時標「圖片」並附取樣均色；「對比值」為最差 10% 像素的值（無條件捨去到小數 2 位，所以 4.4996 會顯示 4.49）；同一規則的多處問題已合併，頁面欄列出全部出現頁面與寬度（1440＝桌機、390＝手機）。截圖檔在 `NEWDESIGN/website/qa-screenshots/contrast/`；另附 `footer-line-normal-1440.png`（LINE 按鈕常態，白底合格）與 `footer-line-hover-1440.png`（hover，1.05）對照。

## 5. 建議 CSS 與改後驗證

下列 CSS 以 `<style>` 注入正式站頁面（只在稽核分頁），**全站 34 個頁面檢視 + 輪播 + 選單 + hover 全部重跑，不合格 0 條**（首頁最後一輪微調：直排字底色 .62→.78 並改 `width:max-content`、手機 Hero 遮罩、Promo 底圖亮度）。這是對比驗證，視覺是否符合設計意圖需要設計確認（特別是 Hero 遮罩加深、直排字加底色、Promo 圖變暗）。

```css
:root{--bf-muted:#6c6056}                         /* style.css:6，原 #75685e；warm 底 4.46 → 5.05 */
/* 頁尾 LINE 按鈕（要贏過後台額外 CSS，需加重特異性）→ 建議放 interactions.css */
body.bfnd-body .bf-footer .bf-footer-main .bf-footer-social > a.bf-footer-line{color:var(--bf-line-ink)}
body.bfnd-body .bf-footer .bf-footer-main .bf-footer-social > a.bf-footer-line:hover{color:var(--bf-white)}
body.bfnd-body .bf-index,body.bfnd-body .bf-story-number{color:var(--bf-wood)}
body.bfnd-body .bf-school-cta p{color:var(--bf-white)}
body.bfnd-body .bf-school-cta{position:relative}
body.bfnd-body .bf-school-cta::before{content:'';position:absolute;inset:0;background:rgba(36,24,13,.35)}
body.bfnd-body .bf-school-cta .bf-wrap{position:relative}
body.bfnd-body .bf-vertical-note{background:rgba(26,18,13,.78);padding:12px 10px;width:max-content;max-width:none}
body.bfnd-body .bf-home-hero .bf-kicker,body.bfnd-body .bf-page-hero .bf-kicker,body.bfnd-body .bf-promo-copy .bf-kicker{color:var(--bf-white)}
body.bfnd-body .bf-manifesto:before{background:linear-gradient(90deg,#170e09b3 0%,#170e09a6 55%,#170e09b3 100%)}
body.bfnd-body .bf-manifesto-inner>span{color:var(--bf-white)}
body.bfnd-body .bf-collab-hero p{color:var(--bf-ink)}
body.bfnd-body .bf-collab-hero .bf-button:hover{background:var(--bf-white)}
body.bfnd-body .bf-home-hero .bf-actions .bf-button:first-child:hover{background:var(--bf-white);color:var(--bf-ink)!important}
body.bfnd-body .bf-direct-contact-row>a{color:var(--bf-wood)}
body.bfnd-body .bf-direct-contact-row>a:hover{color:var(--bf-ink)}
body.bfnd-body .bf-form-shell input::placeholder,body.bfnd-body .bf-form-shell textarea::placeholder{color:var(--bf-muted);opacity:1}
body.bfnd-body .bf-home-hero-shade{background:linear-gradient(90deg,rgba(26,18,13,.78) 0%,rgba(26,18,13,.62) 50%,rgba(26,18,13,.2) 100%)}
body.bfnd-body .bf-page-hero-overlay{background:linear-gradient(90deg,#1d140dcc,#1d140d80 80%)}
body.bfnd-body .bf-promo-image img{filter:brightness(.55)}
@media (max-width:768px){body.bfnd-body .bf-home-hero-shade{background:rgba(26,18,13,.68)}}
```

實作順序建議：先處理兩個使用者回報案例（LINE 按鈕＝加重 interactions.css 特異性並評估刪後台額外 CSS 第 3–5 行；學堂 CTA＝`body.bfnd-body` 前綴＋整面疊層），再調 `--bf-muted`（一次修好十多條 4.46 邊緣案例），最後 Hero／宣言區／Promo 的遮罩與 `.bf-index` 改色。改完需照 `AGENTS.md` 流程驗證：`npm run check`、重建主題 ZIP、部署後再跑本稽核（正式站是否生效要以即時渲染確認，不能只看原始碼）。

## 6. 通過但值得留意

- `--bf-muted` #75685e 在 paper #f8f6f2 上只有 4.99（12px 小字、麵包屑、價格刪除線、作品卡副標、頁尾 maker 小字等約 490 筆落在 4.5–5.0），改 token 後都會升到 5.65。
- Hero／Promo 的大標白字在照片上多半 4.3–4.4（例：Promo h2「從雙手，開始認識木。」4.34，大字門檻 3 → 合格），沒有餘裕；Hero 遮罩加深後會一併改善。
- 桌機下拉、手機選單、頁尾連結、導覽列、按鈕常態、價格、表單文字：全部合格（導覽 6.58–13.83、頁尾 9.84–12.93）。
