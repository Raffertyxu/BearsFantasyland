"""Build a lossless content manifest and web-sized copies from NEWDESIGN sources."""
from __future__ import annotations

import json
import re
from pathlib import Path

from openpyxl import load_workbook
from PIL import Image, ImageOps

ROOT = Path(__file__).resolve().parents[2]
OUT = Path(__file__).resolve().parents[1]
PHOTO_ROOT = ROOT / "照片" / "家具"
ASSET_ROOT = OUT / "assets"

# Excel row order is the authority. Folder 2 uses an older "嶼 ISLE" label;
# retain the Excel public name until the client resolves that discrepancy.
WORKS = [
    ("ridge-table", "1稜 RIDGE", "未加logo.png", "tables"),
    ("landscape-table", "2嶼 ISLE", "原木桌.png", "tables"),
    ("muju", "1栓木桌 _木聚", "栓木餐桌椅與乾燥花.png", "tables"),
    ("musu", "2硬楓木長桌", "極簡長桌乾燥花.png", "tables"),
    ("muyo", "3胡桃木餐桌椅", "胡桃曲木餐桌椅.png", "tables"),
    ("muwi", "4胡桃曲木桌", "視覺.png", "tables"),
    ("muki", "5日式風格桌", "折界餐桌日式風格.png", "tables"),
    ("muni", "6 木日 MUNI", "木日 MUNI.png", "tables"),
    ("ripple", "漾 RIPPLE", "漾 RIPPLE｜拼木圓椅.png", "seating"),
    ("warm", "暖 WARM", "暖 WARM｜實木圓凳01.JPG", "seating"),
    ("walnut-island-bar", "胡桃木中島酒櫃", "胡桃木中島酒櫃.png", "islands"),
    ("cherry-island-table", "櫻桃木中島桌", "階梯櫻桃木中島.png", "islands"),
    ("grid", "格櫃｜GRID", "格｜GRID.png", "storage"),
    ("ridge-shelf", "嶺 RIDGE", "嶺 RIDGE｜實木層架 正面01.png", "storage"),
    ("arch", "築｜ARCH", "築｜ARCH ＊1櫃.png", "storage"),
    ("plain", "原 PLAIN", "原 PLAIN｜實木書架正面.png", "storage"),
    ("breeze", "風 BREEZE", "風 BREEZE｜實木百葉收納櫃.png", "storage"),
]


def web_copy(source: Path, target: Path, *, width: int = 1800) -> str:
    target.parent.mkdir(parents=True, exist_ok=True)
    with Image.open(source) as image:
        image = ImageOps.exif_transpose(image)
        if image.width > width:
            image = image.resize((width, round(image.height * width / image.width)), Image.Resampling.LANCZOS)
        if image.mode not in ("RGB", "RGBA"):
            image = image.convert("RGB")
        image.save(target, "WEBP", quality=86, method=6)
    return "/assets/" + target.relative_to(ASSET_ROOT).as_posix()


def find_folder(part: str) -> Path:
    matches = [p for p in PHOTO_ROOT.rglob("*") if p.is_dir() and part in p.name]
    if len(matches) != 1:
        raise RuntimeError(f"Expected one folder for {part}, found {matches}")
    return matches[0]


sheet = load_workbook(
    ROOT / "網頁分層圖示及文字" / "官網作品內頁版型" / "飛熊入夢_官網作品資料總表.xlsx",
    read_only=True, data_only=True,
).worksheets[0]
rows = list(sheet.iter_rows(values_only=True))[1:]
assert len(rows) == len(WORKS), (len(rows), len(WORKS))

works = []
for row, (slug, folder_part, hero_name, category) in zip(rows, WORKS):
    folder = find_folder(folder_part)
    photos = sorted(p for p in folder.iterdir() if p.suffix.lower() in {".jpg", ".jpeg", ".png", ".webp"})
    heroes = [p for p in photos if p.name == hero_name]
    if len(heroes) != 1:
        raise RuntimeError(f"Hero not found: {slug}: {hero_name}")
    photos.remove(heroes[0])
    photos.insert(0, heroes[0])
    if slug == "ridge-table":
        photos = photos[:1]  # The other supplied versions contain text and logo baked into pixels.
    out_dir = ASSET_ROOT / "works" / slug
    for stale in out_dir.glob("*.webp"):
        if int(stale.stem) > len(photos):
            stale.unlink()
    gallery = [web_copy(photo, ASSET_ROOT / "works" / slug / f"{i:02d}.webp") for i, photo in enumerate(photos, 1)]
    works.append({
        "slug": slug, "series": row[0], "title": row[1], "english": row[2],
        "type": row[3], "material": row[4], "size": row[5], "tagline": row[6],
        "story": row[7], "craft": row[8], "custom": row[9], "note": row[10],
        "category": category, "image": gallery[0], "gallery": gallery,
        "source_folder": folder.relative_to(ROOT).as_posix(),
    })

tray_root = ROOT / "照片" / "生活木作" / "晨露圓境托盤_ Alba Canvas"
tray_files = sorted(p for p in tray_root.iterdir() if p.suffix.lower() in {".jpg", ".png"})
tray = [web_copy(p, ASSET_ROOT / "lifestyle" / f"tray-{i:02d}.webp") for i, p in enumerate(tray_files, 1)]
workshop = ROOT / "照片" / "工作照" / "IMG_1712.JPG"
web_copy(workshop, ASSET_ROOT / "brand" / "real-lecture.webp")
poster_root = ROOT / "網頁分層圖示及文字" / "木作學堂頁面" / "課程簡章_海報"
course_catalog = [
    {
        "slug": "beginner", "title": "木工基礎入門班", "duration": "6 小時", "price": "3680",
        "level": "LEVEL 1｜基礎", "track": "level1", "mode": "onsite",
        "summary": "從零開始認識木工，建立安全、量測與基本加工的基礎。",
        "content": "從零開始認識木工，學習工具使用、機具安全、量測、基本加工與組裝，並完成基礎實作作品。",
        "audience": "適合完全沒有經驗、想先體驗木工的人。",
        "learning": "認識木工與工具使用\n機具安全操作\n量測、基本加工與組裝",
        "outcomes": "完成一件基礎實作作品。",
    },
    {
        "slug": "level-2-practical", "title": "木工實作養成", "duration": "12 小時", "price": "6280",
        "level": "LEVEL 2｜進階", "track": "level2", "mode": "onsite",
        "summary": "跟著老師完成較完整的木作，逐步累積獨立實作經驗。",
        "content": "老師帶著你完成一件較完整的木作，從跟著老師做，逐步練習自己判斷並獨立完成。",
        "audience": "適合想多練習、希望更熟悉加工流程的人。",
        "learning": "跟著老師完成較完整的木作\n熟悉木材加工流程\n逐步練習判斷與獨立完成",
        "outcomes": "完成一件較完整的木作，累積實作經驗。",
    },
    {
        "slug": "level-3-independent", "title": "自主製作養成", "duration": "18 小時", "price": "7800",
        "level": "LEVEL 3｜養成", "track": "level3", "mode": "onsite",
        "summary": "從圖面規劃、備料與加工順序到組裝，培養獨立完成作品的能力。",
        "content": "從圖面規劃、備料、加工順序到組裝完成，老師逐步減少介入，讓你練習獨立思考與解決問題，具備獨立創作階段的能力。",
        "audience": "適合想真正把木工學起來，並能獨立完成作品的人。",
        "learning": "圖面規劃與備料\n安排加工順序\n獨立思考與解決製作問題",
        "outcomes": "完成一件自主規劃與製作的作品。",
    },
    {
        "slug": "cnc", "title": "CNC 數位木工", "duration": "", "price": "",
        "level": "專項技能｜有基礎佳", "track": "specialist", "mode": "onsite",
        "audience": "建議具備木工基礎。",
    },
    {
        "slug": "sharpening", "title": "磨刀實戰班", "duration": "6 小時", "price": "3680",
        "level": "專項技能｜工具保養", "track": "specialist", "mode": "onsite",
        "summary": "學習磨刀基礎與進階技巧，讓工具回到順手狀態。",
        "content": "磨刀實戰班為專項技能課程，從認識刀具、建立正確磨刀角度，到實作測試切削表現。",
        "audience": "適合希望保養木工工具並精進磨刀技巧的人。",
    },
    {
        "slug": "open-studio", "title": "自由創作會員", "duration": "彈性時段", "price": "",
        "level": "自由創作｜持續精進", "track": "membership", "mode": "onsite",
        "features": "4 次｜NT$ 4,800\n8 次｜NT$ 8,800\n12 次｜NT$ 12,000",
        "audience": "適合具備基礎能力、想持續創作並挑戰更多作品的人。",
        "learning": "自由創作，不限主題\n每次前 1 小時老師教學\n老師依作品提供技術指導與協助\n使用完整工坊設備",
        "notices": "適合具備木工基礎能力者。",
    },
    {
        "slug": "foundation-pathway", "title": "木工基礎養成方案", "duration": "36 小時", "price_label": "優惠價", "price": "15800",
        "level": "LEVEL 1 + LEVEL 2 + LEVEL 3", "track": "program", "mode": "onsite",
        "summary": "從基礎到獨立製作，完成 LEVEL 1、LEVEL 2 與 LEVEL 3 的完整學習路徑。",
        "content": "完整學習 LEVEL 1 木工基礎入門、LEVEL 2 木工實作養成與 LEVEL 3 自主製作養成，共 36 小時。",
        "audience": "適合希望依循完整學習路徑，從基礎走向獨立製作的人。",
        "learning": "LEVEL 1｜木工基礎入門\nLEVEL 2｜木工實作養成\nLEVEL 3｜自主製作養成",
        "outcomes": "完成基礎、進階實作與自主製作三階段學習。",
    },
]
course_order = ["beginner", "level-2-practical", "level-3-independent", "open-studio", "foundation-pathway", "cnc", "sharpening"]
course_catalog.sort(key=lambda course: course_order.index(course["slug"]))

course_photos = {
    "beginner": "1初階木工精華班.png",
    "cnc": "3ＣＮＣ數位木工.png",
    "sharpening": "2木工磨刀實戰班.png",
    "open-studio": "4.會員自由創作.png",
}
courses = []
for course in course_catalog:
    course = dict(course, image="")
    if course["slug"] in course_photos:
        photo = web_copy(poster_root / course_photos[course["slug"]], ASSET_ROOT / "courses" / f"{course['slug']}.webp", width=1200)
        course["image"] = photo
    courses.append(course)

program_bundles = [{
    "slug": "foundation-pathway",
    "title": "木工基礎養成方案",
    "duration": "36 小時",
    "price_label": "優惠價",
    "price": "15800",
    "includes": ["木工基礎入門班", "木工實作養成", "自主製作養成"],
}]
for i, p in enumerate(sorted((ROOT / "照片" / "最新消息").glob("*")), 1):
    if p.suffix.lower() in {".jpg", ".png"}:
        web_copy(p, ASSET_ROOT / "journal" / f"journal-{i:02d}.webp")

manifest = {
    "works": works,
    "lifestyle": {"title": "晨露圓境托盤", "english": "Alba Canvas", "gallery": tray},
    "courses": courses,
    "program_bundles": program_bundles,
    "learning_path": [
        {"step": "LEVEL 1", "title": "木工基礎入門班", "course": "beginner"},
        {"step": "LEVEL 2", "title": "木工實作養成", "course": "level-2-practical"},
        {"step": "LEVEL 3", "title": "自主製作養成", "course": "level-3-independent"},
        {"step": "自由創作", "title": "木工創作會員", "course": "open-studio"},
    ],
    "source": "NEWDESIGN customer Excel and original photos",
}
(OUT / "data").mkdir(exist_ok=True)
(OUT / "data" / "content.json").write_text(json.dumps(manifest, ensure_ascii=False, indent=2), encoding="utf-8")
print(f"Wrote {len(works)} works, {sum(len(w['gallery']) for w in works)} furniture photos, {len(tray)} tray photos")
