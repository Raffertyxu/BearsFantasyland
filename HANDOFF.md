# GPT 接手摘要

更新日期：2026-09-24

## 先讀什麼

1. `README.md`：目錄、架構、版本、常用命令。
2. `AGENTS.md`：工作規範與證據界線。
3. 依任務讀 `NEWDESIGN/website/DEPLOYMENT.md`、`SECTION-CRUD-AUDIT.md`、`PM-AUDIT.md`、`design.md`。

## Git / GitHub

- 儲存庫：`https://github.com/Raffertyxu/BearsFantasyland`
- 預設分支：`main`
- 2026-09-24 本次整理提交：`9a1b1c1`（Add latest Bears Fantasyland redesign）
- 此次加入新版 `NEWDESIGN/` 全部程式與參考素材、根目錄 `.gitignore` / `.gitattributes`；`.DS_Store` 已忽略。
- 19 個部署 ZIP 由 Git LFS 管理，合計約 786 MB。新電腦請先安裝並初始化 Git LFS，再執行 `git lfs pull`，否則 ZIP 可能只看到指標檔。
- 當時已確認 `main` 與遠端同步；接手時仍先執行 `git fetch origin`、`git status --short --branch`、`git log -5 --oneline`，因遠端可能已有新提交。

## 程式版本與已記錄部署狀態

- 外掛程式碼：`0.5.1`。
- 主題程式碼：`1.2.3`。
- 最新命名部署包：`NEWDESIGN/website/dist/bears-fantasyland-newdesign.zip` 和 `NEWDESIGN/theme/dist/bears-fantasyland.zip`。
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
