"""Check that the published pages share the NEWDESIGN layout and clean URLs."""

from urllib.error import HTTPError
from urllib.parse import urlsplit
from urllib.request import HTTPRedirectHandler, Request, build_opener


BASE = "https://a1.haotaimaker.com"
PAGES = (
    "/",
    "/furniture/",
    "/lifestyle/",
    "/woodworking-school/",
    "/brand-story/",
    "/collaboration/",
    "/journal/",
    "/service/",
    "/woodshop/",
    "/cart/",
    "/my-account/",
    "/product/%e7%b7%9a%e9%8b%b8/",
)
LEGACY = {
    "/newdesign-furniture/": "/furniture/",
    "/newdesign-lifestyle/": "/lifestyle/",
    "/newdesign-school/": "/woodworking-school/",
    "/newdesign-story/": "/brand-story/",
    "/newdesign-collaboration/": "/collaboration/",
    "/newdesign-journal/": "/journal/",
    "/newdesign-service/": "/service/",
    "/tools/": "/woodshop/",
    "/contact/": "/collaboration/#inquiry",
    "/faq/": "/service/#faq",
    "/news/": "/journal/",
    "/masters/": "/brand-story/",
    "/bearnews/": "/journal/",
    "/%e8%81%af%e7%b5%a1%e6%88%91%e5%80%91/": "/collaboration/#inquiry",
    "/custom-delivery/": "/service/",
    "/about/": "/brand-story/",
    "/youtube/": "/woodworking-school/",
    "/school/": "/woodworking-school/",
    "/%e9%a6%96%e9%a0%81/": "/",
}


class NoRedirect(HTTPRedirectHandler):
    def redirect_request(self, request, file, code, msg, headers, newurl):
        return None


opener = build_opener(NoRedirect)
failures = []

for path in PAGES:
    try:
        response = opener.open(Request(BASE + path, headers={"User-Agent": "BFND route check"}), timeout=20)
        html = response.read().decode("utf-8", errors="replace")
        good = response.status == 200 and 'id="bf-main"' in html and 'id="masthead"' not in html
        if path == "/":
            good = good and 'href="' + BASE + '/newdesign-' not in html
        print(("PASS" if good else "FAIL"), path, response.status)
        if not good:
            failures.append(path)
    except HTTPError as error:
        print("FAIL", path, error.code)
        failures.append(path)

for old, new in LEGACY.items():
    try:
        opener.open(Request(BASE + old, headers={"User-Agent": "BFND route check"}), timeout=20)
        print("FAIL", old, "did not redirect")
        failures.append(old)
    except HTTPError as error:
        location = error.headers.get("Location", "")
        actual = urlsplit(location)
        expected = urlsplit(BASE + new)
        good = (error.code == 301 and actual.netloc == expected.netloc
                and actual.path.rstrip("/").lower() == expected.path.rstrip("/").lower()
                and actual.fragment == expected.fragment)
        print(("PASS" if good else "FAIL"), old, error.code, location)
        if not good:
            failures.append(old)

if failures:
    raise SystemExit("Route check failed: " + ", ".join(failures))
