"""Smoke check the client artwork fixes on the deployed public pages."""

from urllib.request import Request, urlopen

BASE = "https://a1.haotaimaker.com"
CASES = {
    "/": ("logo-transparent.png", "飛熊日誌"),
    "/woodworking-school/": ("VCarve CNC 設計入門", "木工磨刀技術", "訂閱上架通知"),
    "/brand-story/": ("我們希望有一天", "對這片土地的自信", "職人手工刨木情境示意圖"),
    "/collaboration/": ("需求討論", "提案報價", "完成交付", "bf-sdg-goals", "飛熊日誌"),
    "/journal/": ("飛熊日誌", "最新文章"),
    "/service/": ("custom-process", "訂製流程", "常見問題"),
}

for path, markers in CASES.items():
    with urlopen(Request(BASE + path, headers={"User-Agent": "BFND content check"}), timeout=25) as response:
        html = response.read().decode("utf-8", errors="replace")
    missing = [marker for marker in markers if marker not in html]
    print(("PASS" if not missing else "FAIL"), path, "missing:", missing)
    if missing:
        raise SystemExit(1)

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
print("PASS", "/works/ridge-table/", "unverified process block hidden")
