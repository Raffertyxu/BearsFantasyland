# GPT 接手摘要

更新日期：2026-09-30

## 先讀什麼

1. `README.md`：目錄、架構、版本、常用命令。
2. `AGENTS.md`：工作規範與證據界線。
3. 依任務讀 `NEWDESIGN/website/DEPLOYMENT.md`、`SECTION-CRUD-AUDIT.md`、`PM-AUDIT.md`、`design.md`。

## Git / GitHub

- 儲存庫：`https://github.com/Raffertyxu/BearsFantasyland`
- 預設分支：`main`
- 2026-09-24 本次整理提交：`9a1b1c1`（Add latest Bears Fantasyland redesign）
- 2026-09-30 Maker 頁首、會員中心版面、WooCommerce 通知間距、頁尾與法律頁更新已完成；程式、文件、部署 ZIP 及主題回復包已推送至 GitHub `main`（提交 `395dc88`）。另一台電腦執行 `git pull` 及 `git lfs pull` 即可取得。
- 此次加入新版 `NEWDESIGN/` 全部程式與參考素材、根目錄 `.gitignore` / `.gitattributes`；`.DS_Store` 已忽略。
- 19 個部署 ZIP 由 Git LFS 管理，合計約 786 MB。新電腦請先安裝並初始化 Git LFS，再執行 `git lfs pull`，否則 ZIP 可能只看到指標檔。
- 當時已確認 `main` 與遠端同步；接手時仍先執行 `git fetch origin`、`git status --short --branch`、`git log -5 --oneline`，因遠端可能已有新提交。

## 程式版本與已記錄部署狀態

- 本機外掛程式碼：`0.5.3`；本機主題程式碼：`1.2.9`。
- 最新命名部署包：`NEWDESIGN/website/dist/bears-fantasyland-newdesign.zip` 和 `NEWDESIGN/theme/dist/bears-fantasyland.zip`；上一版正式站外掛回復包為 `0.5.2`；主題回復包包含 `1.2.8`、`1.2.7`、`1.2.6`、`1.2.5`、`1.2.4`。
- 2026-09-30 已從外掛與主題共用頁首移除「台中 Maker 工藝基地」連結，以符合 `生活木作版型.png`、`木作學堂頁面-實體跟線上課.png` 的紅叉註記；頁尾 Maker 連結與品牌故事內文保留。後台「網站版面」也已說明單頁 CRUD 不包含共用頁首／頁尾，並連到可編輯品牌故事 Maker 內文的管理頁；後台版面 CSS／JS 快取版本同步為 `0.5.2`。更新 `README.md` 與 `NEWDESIGN/website/SECTION-CRUD-AUDIT.md`。
- 2026-09-30 已在 WordPress 正式站成功覆蓋更新外掛 `0.5.1 → 0.5.2`、主題 `1.2.3 → 1.2.4`。外掛清單確認 0.5.2 已啟用；主題詳細資料確認「飛熊入夢」仍是目前使用中的主題且為 1.2.4。
- 2026-09-30 會員中心桌機版曾將導覽擠到右欄、內容掉到下一列。原因是帳戶區使用 CSS Grid，而 WooCommerce clearfix 的 `::before`／`::after` 被當成 Grid 項目佔位；Maker 頁首修正沒有改會員頁排版規則。已在主題 `1.2.5` 限定隱藏會員 Grid 的兩個 clearfix pseudo-elements，並把樣式快取版本由 `1.2.3` 改為 `1.2.5`。正式站主題覆蓋成功；即時會員頁載入 CSS `?ver=1.2.5`，在 1280px 寬桌機檢查導覽欄為左側 250px、內容位於右欄，兩個 pseudo-elements 均為 `display:none`。完整路由檢查未重跑。
- 2026-09-30 WooCommerce 通知圖示壓到第一個字的原因是通知左右內距只有 `20px`，而圖示絕對定位在 `left:24px`。主題 `1.2.6` 與外掛 `0.5.3` 已同步將通知左側內距調為 `52px`。正式站即時檢查會員訂單頁的「確認電子郵件地址／目前還沒有訂單」及結帳頁的折價券／付款方式提示，載入主題 CSS `?ver=1.2.6`，均為 `padding-left:52px`、圖示 `left:24px`；畫面確認圖示與文字分開。未送出訂單，也未重跑完整路由／內容檢查。
- 2026-09-30 頁尾依使用者提供的設計圖稿重做。主題 `1.2.9` 已在 WordPress 後台覆蓋成功；首頁部署後即時畫面確認兩列頁尾、五項導覽、四個社群圖示、Maker 資訊、版權與指定底列標籤。上一版桌機量測為 120px（上列 82px、深色底列 38px），`1.2.9` 僅補上 761–1100px 及手機寬度顯示品牌標語的規則；本次部署後未重新量測高度或擷取 912px 畫面。兩法律頁最初以草稿建立；發布與 WooCommerce 設定的最新狀態見下方。社群帳號網址未確認，圖示不外連。未重跑完整路由／內容檢查，也未做手機截圖核對。
- 2026-09-30 法律頁發布與即時核對：隱私權政策頁 ID 3（`/privacy-policy/`）及服務條款頁 ID 1615（`/terms-of-service/`）均已發布；WooCommerce「進階 → 頁面設定 → 條款」已指定服務條款 ID 1615。首頁頁尾政策連結、兩法律頁內容，以及結帳頁條款勾選與隱私權連結已在正式站即時確認。結帳當時提示沒有可用付款方式；未送出訂單，也未操作購物車。合作提案詢問表單欄位可見且頁面說明資料存入網站後台，但沒有提交表單。這些前台檢查來自登入中的 WordPress 瀏覽器工作階段；未重跑完整路由／內容檢查腳本。
- 法律頁發布時以官方商業登記及工藝名錄核對公司名稱與品牌關係，內容說明資料用途、權利與服務商類別；實際主機／第三方服務商及地區、精確保存期限、個別付款配送／課程取消與保固細節仍待營運盤點。WooCommerce 帳號／隱私設定與保留期間欄位未更動，法律內容未經法律專業人士審閱。詳見 `NEWDESIGN/website/LEGAL-POLICY-DRAFT.md`；其中舊版草稿保留作歷史參考，不是目前線上全文。
- 正式站即時核對：重新載入首頁後，頁首已無「台中 Maker 工藝基地」連結，頁尾仍保留；品牌故事頁仍有「04 TAICHUNG MAKER／台中 Maker 工藝基地」段落。後台「網站版面」已顯示共用頁首／頁尾不屬於單頁 CRUD 欄位的說明，並提供品牌故事版面管理連結。本次只核對首頁、品牌故事及後台版面總覽，未重新執行完整路由／內容檢查腳本。
- 本次程式、文件、部署 ZIP 及主題回復包已包含於 GitHub `main` 提交 `395dc88`；上述正式站驗證範圍與未檢項目仍以各段紀錄為準。
- `DEPLOYMENT.md` 記錄 2026-09-18 正式站更新：12 個正式路由 HTTP 200、19 個舊網址 HTTP 301；內容檢查與原生日誌 CRUD 流程有通過紀錄。這是截至該日的歷史驗證，不等同今天的線上狀態；接手後若涉及線上站，重新驗證。
- 舊版主題及外掛 ZIP 保留於 `dist/` 作回溯用途。

## 架構速讀

- 新版位置：`NEWDESIGN/website/`（外掛）與 `NEWDESIGN/theme/bears-fantasyland/`（獨立主題）。
- 八個主要頁面使用 `[bfnd_page key="..."]`；固定版型在 WordPress「網站版面」管理。
- 家具、生活木作、木作課程、日誌走 WordPress 原生／自訂內容管理；購物車、結帳、帳戶及訂單走 WooCommerce。
- 固定頁內元件並非每個都有完整新增／排序／刪除能力，細節見 `SECTION-CRUD-AUDIT.md`。
- 設計參考素材在 `NEWDESIGN/照片/`、`NEWDESIGN/網頁分層圖示及文字/`；情境示意不能當作真實產品或製程證據。

## 建議第一輪操作

```powershell
git fetch origin
git status --short --branch
git log -5 --oneline
git lfs install
git lfs pull
Set-Location NEWDESIGN/website
npm run check
```

若要做正式站路由或內容驗證，查看 `NEWDESIGN/website/scripts/check_live_routes.py`、`check_live_content.py` 的參數與設定要求後再執行；不要猜測或輸出帳密、Cookie、API 金鑰。正式站紀錄與資安注意事項見 `DEPLOYMENT.md`、`PM-AUDIT.md`。
