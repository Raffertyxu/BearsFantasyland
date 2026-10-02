"""Smoke check the client artwork fixes on the deployed public pages."""

import json
import re
from urllib.request import Request, urlopen

BASE = "https://a1.haotaimaker.com"
FORBIDDEN_TEXT = ("Haotai TEST", "待確認", "待補", "暫定", "推估", "Sample", "【示範文章】", chr(0x793A) + chr(0x610F), chr(0x6E2C) + chr(0x8A66))


def assert_no_placeholders(label, text):
    for marker in FORBIDDEN_TEXT:
        assert marker.casefold() not in text.casefold(), f"{label} contains internal placeholder: {marker}"
    assert not re.search(r"\b(?:test|demo|sample)\b", text, re.IGNORECASE), f"{label} contains TEST/DEMO/SAMPLE text"
CASES = {
    "/": ("logo-transparent.png", "飛熊日誌"),
    "/woodworking-school/": ("木作學習路徑", "專項技能課程", "線上課程資訊近期公布"),
    "/furniture/": ("COLLECTIONS / 依系列瀏覽", "搜尋作品、系列或木材"),
    "/lifestyle/": ("生活木作", "晨露圓境托盤"),
    "/brand-story/": ("我們希望有一天", "對這片土地的自信", "飛熊入夢木作教學現場"),
    "/collaboration/": ("需求討論", "提案報價", "完成交付", "bf-sdg-goals", "飛熊日誌"),
    "/journal/": ("飛熊日誌", "最新文章"),
    "/service/": ("作品購買／詢問", "運送與安裝", "作品保固", "保養與修繕", "05 / FAQ"),
}

for path, markers in CASES.items():
    with urlopen(Request(BASE + path, headers={"User-Agent": "BFND content check"}), timeout=25) as response:
        html = response.read().decode("utf-8", errors="replace")
    missing = [marker for marker in markers if marker not in html]
    print(("PASS" if not missing else "FAIL"), path, "missing:", missing)
    if missing:
        raise SystemExit(1)
    assert not re.search(r"(?i)(editorial|wooden-cup)", html), f"{path} references an illustrative image asset"
    assert_no_placeholders(path, html)

journal_counts = {}
for path in ("/", "/collaboration/", "/journal/"):
    with urlopen(Request(BASE + path, headers={"User-Agent": "BFND content check"}), timeout=25) as response:
        html = response.read().decode("utf-8", errors="replace")
    count = html.count('class="bf-journal-card"')
    journal_counts[path] = count
    assert "bf-journal-preview-card" not in html, f"{path} still has hard-coded preview cards"
    print("PASS", path, count, "native post cards; no old preview cards")

assert journal_counts["/"] == journal_counts["/collaboration/"] == min(journal_counts["/journal/"], 3), journal_counts
print("PASS", "latest post teasers match the journal list")

with urlopen(Request(BASE + "/works/ridge-table/", headers={"User-Agent": "BFND content check"}), timeout=25) as response:
    detail = response.read().decode("utf-8", errors="replace")
assert "bf-craft-section" not in detail, "work detail still shows unverified process image"
for marker in ("01 / STORY", "02 / SPECIFICATION", "03 / DETAILS", "RELATED WORKS", "WORKS INQUIRY"):
    assert marker in detail, f"work detail missing {marker}"
positions = [detail.index(marker) for marker in ("01 / STORY", "02 / SPECIFICATION", "03 / DETAILS", "RELATED WORKS")]
assert positions == sorted(positions), "work detail section order changed"
for marker in ("CUSTOM MADE", "重新製作", "依作品照片推估", "木種待確認"):
    assert marker not in detail, f"work detail still contains retired copy: {marker}"
print("PASS", "/works/ridge-table/", "section order and inquiry copy")

with urlopen(Request(BASE + "/service/", headers={"User-Agent": "BFND content check"}), timeout=25) as response:
    service = response.read().decode("utf-8", errors="replace")
for marker in ("CUSTOM PROCESS", "訂製流程", "CUSTOM MADE"):
    assert marker not in service, f"service page still contains retired copy: {marker}"
print("PASS", "/service/", "purchase and after-sales structure")

for path in ("/", "/furniture/", "/lifestyle/", "/woodworking-school/", "/brand-story/", "/collaboration/", "/journal/", "/service/"):
    with urlopen(Request(BASE + path, headers={"User-Agent": "BFND content check"}), timeout=25) as response:
        html = response.read().decode("utf-8", errors="replace")
    assert_no_placeholders(path, html)
print("PASS", "public core-page placeholder scan")

for post_type in ("bf_work", "bf_course", "posts"):
    with urlopen(Request(BASE + f"/wp-json/wp/v2/{post_type}?per_page=100", headers={"User-Agent": "BFND content check"}), timeout=25) as response:
        posts = json.loads(response.read().decode("utf-8", errors="replace"))
    for post in posts:
        content = " ".join((post.get("title", {}).get("rendered", ""), post.get("excerpt", {}).get("rendered", ""), post.get("content", {}).get("rendered", "")))
        assert_no_placeholders(f"{post_type} {post.get('id')}", content)
    print("PASS", "public content records", post_type, len(posts))

with urlopen(Request(BASE + "/wp-json/wc/store/v1/products/categories?per_page=100", headers={"User-Agent": "BFND content check"}), timeout=25) as response:
    categories = json.loads(response.read().decode("utf-8", errors="replace"))
test_categories = {"ar", "be", "ch"}
found_categories = sorted(category.get("slug", "") for category in categories if category.get("slug", "").lower() in test_categories)
assert not found_categories, f"test product categories remain public: {found_categories}"

with urlopen(Request(BASE + "/wp-json/wc/store/v1/products?per_page=100", headers={"User-Agent": "BFND content check"}), timeout=25) as response:
    products = json.loads(response.read().decode("utf-8", errors="replace"))
for product in products:
    content = " ".join((product.get("name", ""), product.get("short_description", ""), product.get("description", "")))
    assert_no_placeholders(f"product {product.get('id')}", content)
print("PASS", "public WooCommerce products", len(products), "and no Ar/Be/Ch test categories")
