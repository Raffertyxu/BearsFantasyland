# 宣傳影片

以程式逐格算出的宣傳影片（1920×1080、30fps、H.264）。成片由 Git LFS 管理。

| 成片 | 腳本 | 內容 |
|---|---|---|
| `out/bears-furniture-30s-ad.mp4` | `ad30.html` | 30 秒家具廣告（有配樂）：剪點卡 110 BPM 節拍，前奏、鋪陳、pre-drop、DROP 高潮、收尾 logo 與網址 |
| `out/bears-brand-story-45s-ad.mp4` | `brand45.html` | 45 秒品牌故事廣告（原創配樂 `audio/bears-brand-score-45s.wav`）：剪點卡 96 BPM 拍格（`audio/brand-structure.json`），intro 鋼琴、verse 人與手作、build 加速、28.75 重擊閃白＋真空、30.0 DROP 光爆、climax 每拍一刀、40.0 收尾 logo；字卡全取自 `/brand-story/` 原文。另有無聲預覽 `out/bears-brand-story-45s-ad-silent.mp4` |
| `out/bears-furniture-30s.mp4` | `reel.html` | 30 秒家具作品片（無聲舊版，保留做比較）：5 個系列共 17 件作品 |
| `out/bears-brand-story-45s.mp4` | `brand.html` | 45 秒品牌故事片（無聲）：字卡取自 `/brand-story/` 原文 |

- 圖片直接讀取 `NEWDESIGN/website/assets/`，換圖或改字卡後重新算圖即可。
- 只使用實拍作品照、授課照片及課程海報；`editorial` 系列情境生成圖不用於影片。
- `reel.html`、`brand.html` 的剪接點對齊 120 BPM（0.5 秒一拍）。
- `ad30.html` 的剪接點依 `audio/beatmap.json`（110 BPM，第 k 拍在 k × 60/110 秒，影片 0 秒＝曲目 16.333 秒），對齊到最近的畫格。
- `brand45.html` 的剪接點依 `audio/brand-structure.json`（96 BPM，第 k 拍在 k × 0.625 秒，影片 0 秒＝配樂 0 秒），對齊到最近的畫格；片尾只放 logo、品牌標語與服務項目，不放網址。

## 配樂

`audio/inspiring-cinematic-tunetank-409347.mp3`（Tunetank，Pixabay Content License）。授權與 YouTube Content ID 注意事項見 [`audio/LICENSE.md`](audio/LICENSE.md)。

## 預覽

在 `NEWDESIGN/` 啟動任一靜態伺服器後開啟：

- `showcase/video/ad30.html`：即時循環播放；點一下畫面會從頭連同配樂播放
- `showcase/video/reel.html`：即時循環播放
- `showcase/video/brand45.html`：即時循環播放；點一下畫面會從頭連同配樂播放
- `showcase/video/brand.html?t=12.3`、`ad30.html?t=12.3`、`brand45.html?t=12.3`：顯示單一畫格

## 重新算圖

需要 Node 20+、ffmpeg（PATH 中）及 Chrome。

```bash
node NEWDESIGN/showcase/video/render.mjs
node NEWDESIGN/showcase/video/render.mjs --page brand.html --out bears-brand-story-45s.mp4
node NEWDESIGN/showcase/video/render.mjs --page ad30.html --out bears-furniture-30s-ad.mp4 --audio NEWDESIGN/showcase/video/audio/inspiring-cinematic-tunetank-409347.mp3 --audio-start 16.333 --audio-fade-out 2.7
node NEWDESIGN/showcase/video/render.mjs --page brand45.html --out bears-brand-story-45s-ad.mp4 --audio NEWDESIGN/showcase/video/audio/bears-brand-score-45s.wav --audio-start 0 --audio-fade-out 0
```

- `--audio <檔案>`：先算出無聲影片，再混入配樂（AAC 320k、雙聲道、`-shortest`，開頭 0.05 秒淡入防爆音）。路徑可相對於目前目錄或 `render.mjs` 所在資料夾。
- `--audio-start <秒>`：從曲目第幾秒開始。
- `--audio-fade-out <秒>`：片尾淡出長度（從片尾往前算）。
- `--stills 0.8,2.3,5 [--stills-dir <資料夾>]`：只輸出指定時間點的 PNG，不編影片（目前 `ad30.html`、`brand45.html` 支援）。
- Chrome 預設路徑為 `C:/Program Files/Google/Chrome/Application/chrome.exe`，可用 `--chrome` 指定。
