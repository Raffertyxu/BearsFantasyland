# 宣傳影片

以程式逐格算出的三支宣傳影片（1920×1080、30fps、H.264）。家具片與品牌故事片無聲；合作提案片附程式合成的原創配樂（AAC 256 kbps、-14 LUFS）。成片由 Git LFS 管理。

| 成片 | 腳本 | 內容 |
|---|---|---|
| `out/bears-furniture-30s.mp4` | `reel.html` | 30 秒家具作品片：5 個系列共 17 件作品 |
| `out/bears-brand-story-45s.mp4` | `brand.html` | 45 秒品牌故事片：字卡取自 `/brand-story/` 原文 |
| `out/bears-collaboration-60s.mp4` | `collab.html` | 60 秒合作提案片（含配樂）：文案取自 `/collaboration/` 原文（合作方式、設計走進空間、合作流程、合作場景、永續、售後、聯絡方式） |

- 圖片直接讀取 `NEWDESIGN/website/assets/`，換圖或改字卡後重新算圖即可。
- 只使用實拍作品照、授課照片及課程海報；`editorial` 系列情境生成圖不用於影片。
- `collab.html` 另加離屏後製（景深模糊、光暈、邊緣色差）、四分割畫格展開接回滿版、「木」字遮罩穿越開場、規格標註線及線條圖示描繪；聯絡資訊取自合作頁，若更換需同步修改片尾。
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
node NEWDESIGN/showcase/video/render.mjs --page collab.html --out bears-collaboration-60s.mp4
```

Chrome 預設路徑為 `C:/Program Files/Google/Chrome/Application/chrome.exe`，可用 `--chrome` 指定。

## 合作提案片配樂

`music-collab.mjs` 以程式合成原創配樂（120 BPM、D 大調；柔和鋼琴、鋪底、貝斯、鼓組、轉場風切及撞擊音），重拍對齊 `collab.html` 時間軸，沒有使用外部音樂素材。改動畫面時間點後，需同步修改腳本內的時間。

先算出無聲影片，再合成配樂並混入（EQ ＋兩段式 loudnorm 至 -14 LUFS、True Peak -1 dB）：

```bash
node NEWDESIGN/showcase/video/music-collab.mjs collab-music.wav
ffmpeg -i collab-music.wav -af "highshelf=f=3500:g=4,equalizer=f=280:t=q:w=1:g=-2.5,loudnorm=I=-14:TP=-1.0:LRA=9:print_format=json" -f null -
```

把上一步印出的 measured 數值填進第二段 `loudnorm=...:measured_I=..:measured_TP=..:measured_LRA=..:measured_thresh=..:offset=..:linear=true`，以 `-map 0:v -map "[a]" -c:v copy -c:a aac -b:a 256k` 混入無聲影片。
