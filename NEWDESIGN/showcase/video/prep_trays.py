"""把托盤棚拍照的灰白背景校正成純白，供 apple.html 以 multiply 疊在任意淺色底上。

做法：用照片外框一圈（全是背景）擬合二次曲面當打光分布，整張除以它（flat-field），
背景就變成 1.0、托盤與自然陰影保留；再把接近白的雜訊壓成純白。
同時輸出每張托盤的外框（不含淡陰影），讓影片能把不同照片的托盤對齊到同一個圓。

用法：python NEWDESIGN/showcase/video/prep_trays.py
輸出：NEWDESIGN/showcase/video/trays/tray-NN.webp 與 trays.json
"""
import json
from pathlib import Path

import numpy as np
from PIL import Image, ImageDraw, ImageFilter

HERE = Path(__file__).resolve().parent
SRC = HERE.parent.parent / "website" / "assets" / "lifestyle"
OUT = HERE / "trays"
OUT.mkdir(exist_ok=True)
# 白色款與白底太接近，自動偵測抓不到外框，改用目視量測值（照片比例座標）
BOX_OVERRIDE = {"tray-05": [.25, .18, .745, .765]}


def fit_background(img):
    """以外框一圈擬合每個色版的二次曲面，回傳全尺寸背景估計。"""
    h, w, _ = img.shape
    small = np.asarray(Image.fromarray((img * 255).astype(np.uint8)).resize((240, 160), Image.BILINEAR), dtype=np.float64) / 255
    sh, sw, _ = small.shape
    yy, xx = np.mgrid[0:sh, 0:sw]
    x, y = xx / (sw - 1) * 2 - 1, yy / (sh - 1) * 2 - 1
    band = (np.abs(x) > .86) | (np.abs(y) > .8)
    feats = lambda X, Y: np.stack([np.ones_like(X), X, Y, X * X, X * Y, Y * Y], -1)
    A = feats(x[band], y[band])
    YY, XX = np.mgrid[0:h, 0:w]
    Xf, Yf = XX / (w - 1) * 2 - 1, YY / (h - 1) * 2 - 1
    Af = feats(Xf, Yf)
    bg = np.empty_like(img)
    for c in range(3):
        v = small[..., c][band]
        keep = np.ones(v.shape, bool)
        for _ in range(3):  # 排除灰塵、陰影等離群點
            coef, *_ = np.linalg.lstsq(A[keep], v[keep], rcond=None)
            res = v - A @ coef
            keep = np.abs(res) < 2.5 * res[keep].std() + 1e-4
        bg[..., c] = Af @ coef
    return bg


# 去背方式：正圓托盤用外框橢圓（白色、米色邊框也抓得到）；有盤面錯開或側拍的用顏色／明暗判斷再補洞
ELLIPSE = {"tray-01", "tray-02", "tray-05", "tray-06", "tray-07", "tray-09", "tray-12", "tray-13"}


def smooth(e0, e1, x):
    t = np.clip((x - e0) / (e1 - e0), 0, 1)
    return t * t * (3 - 2 * t)


def cutout(name, raw, box):
    """輸出帶透明度的 tray-NN-a.webp，給深色或彩色底的鏡頭用。"""
    h, w, _ = raw.shape
    lum = raw @ np.array([.2126, .7152, .0722])
    sat = raw.max(-1) - raw.min(-1)
    not_bg = (lum < .972) | (sat > .05)  # 校正後背景幾乎是 1.0；把手鏤空處透出的背景也排除
    if name in ELLIPSE:
        x0, y0, x1, y1 = box
        yy, xx = np.mgrid[0:h, 0:w]
        cx, cy = (x0 + x1) / 2 * w, (y0 + y1) / 2 * h
        rx, ry = (x1 - x0) / 2 * w * 1.008, (y1 - y0) / 2 * h * 1.008
        r = np.sqrt(((xx - cx) / rx) ** 2 + ((yy - cy) / ry) ** 2)
        # 白色款與白底無法以明暗區分，整個橢圓都算托盤
        a = smooth(1.0, .992, r) * (1 if name == "tray-05" else not_bg)
    else:
        a = np.maximum(smooth(.06, .16, sat), smooth(.62, .42, lum))
        # 補洞：從四角漫延到的才算背景，被托盤包住的區域補成實心（純白的鏤空除外）
        m = Image.fromarray(np.where(a < .5, 255, 0).astype(np.uint8)).copy()  # copy：fromarray 的影像唯讀，floodfill 會無聲失敗
        for xy in [(0, 0), (w - 1, 0), (0, h - 1), (w - 1, h - 1)]:
            ImageDraw.floodfill(m, xy, 128)
        holes = (np.asarray(m) == 255) & not_bg
        a = np.where(holes, 1.0, a)
    a_img = Image.fromarray((a * 255).astype(np.uint8)).filter(ImageFilter.GaussianBlur(1.2))
    a = np.asarray(a_img, dtype=np.float64)[..., None] / 255
    # 去掉邊緣混到的白底
    col = np.clip((raw - (1 - a)) / np.maximum(a, 1e-3), 0, 1)
    rgba = np.concatenate([col, a], -1)
    Image.fromarray((rgba * 255 + .5).astype(np.uint8), "RGBA").save(OUT / f"{name}-a.webp", quality=92)


meta = {}
for i in range(1, 14):
    name = f"tray-{i:02d}"
    img = np.asarray(Image.open(SRC / f"{name}.webp").convert("RGB"), dtype=np.float64) / 255
    raw = np.clip(img / fit_background(img), 0, 1)
    lum = raw @ np.array([.2126, .7152, .0722])
    k = np.clip((lum - .93) / (.975 - .93), 0, 1)[..., None]  # 接近白的部分平滑推到純白
    k = k * k * (3 - 2 * k)
    flat = raw * (1 - k) + k
    Image.fromarray((flat * 255 + .5).astype(np.uint8)).save(OUT / f"{name}.webp", quality=92)

    # 托盤外框：明顯變暗或帶顏色的像素（淡陰影不算），取 0.3%–99.7% 分位避免灰塵
    sat = flat.max(-1) - flat.min(-1)
    lum = flat @ np.array([.2126, .7152, .0722])
    m = (lum < .72) | (sat > .12)
    ys, xs = np.nonzero(m)
    x0, x1 = np.percentile(xs, [.3, 99.7])
    y0, y1 = np.percentile(ys, [.3, 99.7])
    h, w = m.shape
    box = [round(x0 / w, 4), round(y0 / h, 4), round(x1 / w, 4), round(y1 / h, 4)]
    if name in BOX_OVERRIDE:
        box = BOX_OVERRIDE[name]
    meta[name] = {"w": w, "h": h, "box": box}
    print(name, meta[name]["box"])
    cutout(name, raw, box)

(OUT / "trays.json").write_text(json.dumps(meta, indent=1), encoding="utf-8")
