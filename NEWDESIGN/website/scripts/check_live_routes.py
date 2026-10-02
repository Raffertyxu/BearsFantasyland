"""Check that the published pages share the NEWDESIGN layout and clean URLs."""

from urllib.error import HTTPError
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
RETIRED = (
    "/newdesign-preview/",
    "/newdesign-furniture/",
    "/newdesign-lifestyle/",
    "/newdesign-school/",
    "/newdesign-story/",
    "/newdesign-collaboration/",
    "/newdesign-journal/",
    "/newdesign-service/",
    "/tools/",
    "/contact/",
    "/faq/",
    "/news/",
    "/masters/",
    "/bearnews/",
    "/%e8%81%af%e7%b5%a1%e6%88%91%e5%80%91/",
    "/custom-delivery/",
    "/about/",
    "/youtube/",
    "/school/",
    "/%e9%a6%96%e9%a0%81/",
)


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

for path in RETIRED:
    try:
        response = opener.open(Request(BASE + path, headers={"User-Agent": "BFND route check"}), timeout=20)
        good = response.status == 404
        print(("PASS" if good else "FAIL"), path, response.status)
        if not good:
            failures.append(path)
    except HTTPError as error:
        good = error.code == 404
        print(("PASS" if good else "FAIL"), path, error.code)
        if not good:
            failures.append(path)

if failures:
    raise SystemExit("Route check failed: " + ", ".join(failures))
