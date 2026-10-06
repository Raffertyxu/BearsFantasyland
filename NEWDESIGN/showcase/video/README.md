# 宣傳影片

以程式逐格算出的兩支宣傳影片（1920×1080、30fps、H.264、無聲）。成片由 Git LFS 管理。

| 成片 | 腳本 | 內容 |
|---|---|---|
| `out/bears-furniture-30s.mp4` | `reel.html` | 30 秒家具作品片：5 個系列共 17 件作品 |
| `out/bears-brand-story-45s.mp4` | `brand.html` | 45 秒品牌故事片：字卡取自 `/brand-story/` 原文 |

- 圖片直接讀取 `NEWDESIGN/website/assets/`，換圖或改字卡後重新算圖即可。
- 只使用實拍作品照、授課照片及課程海報；`editorial` 系列情境生成圖不用於影片。
- 剪接點對齊 120 BPM（0.5 秒一拍），方便之後配樂。

## 預覽

在 `NEWDESIGN/` 啟動任一靜態伺服器後開啟：

- `showcase/video/reel.html`：即時循環播放
- `showcase/video/brand.html?t=12.3`：顯示單一畫格

## 重新算圖

需要 Node 20+、ffmpeg（PATH 中）及 Chrome。

```bash
node NEWDESIGN/showcase/video/render.mjs
node NEWDESIGN/showcase/video/render.mjs --page brand.html --out bears-brand-story-45s.mp4
```

Chrome 預設路徑為 `C:/Program Files/Google/Chrome/Application/chrome.exe`，可用 `--chrome` 指定。
