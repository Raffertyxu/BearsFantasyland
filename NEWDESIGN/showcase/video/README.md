# 宣傳影片

以程式逐格算出的宣傳影片（1920×1080、30fps、H.264、無聲）。成片由 Git LFS 管理。

| 成片 | 腳本 | 內容 |
|---|---|---|
| `out/bears-furniture-30s.mp4` | `reel.html` | 30 秒家具作品片：5 個系列共 17 件作品 |
| `out/bears-brand-story-45s.mp4` | `brand.html` | 45 秒品牌故事片：字卡取自 `/brand-story/` 原文 |
| `out/bears-alba-canvas-30s.mp4` | `alba-canvas.html` + `score.py` | 30 秒生活木作商品廣告片（2.39:1 暗場、有配樂）：晨露圓境・圓萃托盤；分鏡與拍點見 `lifestyle-plan.md` |

商品片使用 `trays/` 內的去背圖（`tray-NN-a.webp`）與外框資料 `trays.json`，由 `python NEWDESIGN/showcase/video/prep_trays.py` 從 `website/assets/lifestyle/` 產生；配樂由 `python NEWDESIGN/showcase/video/score.py` 合成到 `out/alba-canvas-score.wav`（皆只需 numpy、Pillow，無取樣素材）。

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
python NEWDESIGN/showcase/video/score.py
node NEWDESIGN/showcase/video/render.mjs --page alba-canvas.html --out bears-alba-canvas-30s.mp4 --audio out/alba-canvas-score.wav
```

Chrome 預設路徑為 `C:/Program Files/Google/Chrome/Application/chrome.exe`，可用 `--chrome` 指定。
