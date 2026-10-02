# 舊網址及舊頁清理紀錄

日期：2026-10-02  
正式站：<https://a1.haotaimaker.com/>

## 完成項目

- 正式站 WordPress 外掛「飛熊入夢 NEWDESIGN 官網」已由 `0.5.5` 更新至 `0.5.7`，後台確認新版本仍為啟用狀態。
- 使用者選擇將 12 個舊版草稿頁移至回收桶，保留可還原性；未永久刪除或清空回收桶。
- 外掛移除清單中的 19 條舊版相容轉址，並移除額外的 `/newdesign-preview/`，共 20 條。這些舊路徑現在預期回應 404，不再導向新版頁。
- 保留 15 個正式發布頁、唯一仍在草稿狀態的「退款和退貨政策」，以及 WooCommerce 購物／帳戶頁面。

## 移入回收桶的 WordPress 頁面

| ID | 頁面 | 舊 slug |
| ---: | --- | --- |
| 13 | 首頁 | 首頁 |
| 1344 | 優質工具 | tools |
| 1346 | 寫信給我們 | contact |
| 1226 | 常見問題 | faq |
| 1354 | 最新消息 | news |
| 1337 | 木師介紹 | masters |
| 1357 | 熊熊速報 | bearnews |
| 341 | 聯絡我們 | 聯絡我們 |
| 1204 | 訂製與運送 | custom-delivery |
| 1297 | 關於我們 | about |
| 1349 | 飛熊入夢 YouTube 頻道 | youtube |
| 1240 | 飛熊造木所 | school |

## 已停用的 20 條舊網址

```text
/newdesign-preview/
/newdesign-furniture/
/newdesign-lifestyle/
/newdesign-school/
/newdesign-story/
/newdesign-collaboration/
/newdesign-journal/
/newdesign-service/
/tools/
/contact/
/faq/
/news/
/masters/
/bearnews/
/%e8%81%af%e7%b5%a1%e6%88%91%e5%80%91/
/custom-delivery/
/about/
/youtube/
/school/
/%e9%a6%96%e9%a0%81/
```

## 即時驗證

- 執行 `python scripts/check_live_routes.py`：12 個目前主要路由均 HTTP 200；以上 20 個舊路徑均 HTTP 404，無失敗項目。
- 後台頁面清單：全部 16（15 已發布、1 草稿）及回收桶 12；回收桶內可見上述 12 個頁面。
- 外掛清單顯示 `0.5.7` 並有「停用」操作，確認目前啟用。
- PHP 語法檢查：`php -l src/content.php`、`php -l bears-fantasyland-newdesign.php` 通過；路由檢查 Python 原始碼可編譯。

## 範圍與注意事項

- 回收桶是可還原的軟刪除；頁面及相關修訂並未從資料庫永久清除。清空回收桶需要另行確認。
- 本次只處理上述舊頁草稿與相容轉址。沒有刪除訂單、會員、媒體、商品或其他內容類型，也沒有執行資料庫最佳化／清空。
- 20 個舊路徑現在回 404，外部書籤或搜尋結果若仍指向這些路徑會失效；這符合使用者確認的清理範圍。
- 使用者指出 Yoast SEO 分數及可讀性欄尚未優化。本次沒有改動正式頁 SEO 標題、中繼描述、焦點關鍵字或正文；需另外逐頁盤點及調整。
- 先前 [`LIVE-LINK-CONTENT-AUDIT-2026-10-02.md`](LIVE-LINK-CONTENT-AUDIT-2026-10-02.md) 記錄的 19 條 HTTP 301 為清理前快照，不再代表目前正式站狀態。
