"""Convert front-end CSS pixel sizes to the proportional scale unit.

Convention (see design.md, 2026-10-06): every length in a front-end stylesheet is
written as ``calc(N*var(--bfnd-px))``. ``--bfnd-px`` is exactly 1px up to a
1920px-wide viewport, so nothing changes there; above 1920px it grows with the
viewport so the 1920px reference design scales up uniformly.

What this script does to each front-end CSS file (deterministic, idempotent):

1. Removes the old large-screen patch layers: every ``@media`` block whose only
   condition is ``(min-width: N px)`` with N >= 2000 (the 2560/3840/6000 layers),
   plus a comment that directly precedes such a block. ``min-width:1920px``
   blocks are the reference design and are kept.
2. Inserts the ``--bfnd-px`` definition (once) after any leading @charset/@import.
3. Rewrites every ``<number>px`` inside declaration values to
   ``calc(<number>*var(--bfnd-px))`` (``<n>rem`` becomes ``calc(<n*16>*var(--bfnd-px))``). ``0px`` becomes ``0`` where that is
   unambiguous (outside functions, not in custom properties or ``flex``) and is
   left as ``0px`` otherwise. At-rule preludes (@media, @supports, @import ...),
   selectors, comments, strings and ``url()`` are never touched.

Usage:
    python scripts/scale_px_units.py            # convert the default file list in place
    python scripts/scale_px_units.py --check    # exit 1 if a file would change
    python scripts/scale_px_units.py FILE ...   # convert specific files

Admin/editor stylesheets (admin.css, banner-admin.css, block-editor.css,
page-design.css) are intentionally not in the default list.
"""
from __future__ import annotations

import re
import sys
from dataclasses import dataclass, field
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]  # NEWDESIGN/
FRONTEND_CSS = [
    ROOT / "theme/bears-fantasyland/public/style.css",
    ROOT / "theme/bears-fantasyland/public/interactions.css",
    ROOT / "theme/bears-fantasyland/public/product.css",
    ROOT / "website/public/style.css",
    ROOT / "website/public/interactions.css",
    ROOT / "website/public/work-options.css",
]

SCALE_VAR = "--bfnd-px"
DROP_MIN_WIDTH = 2000
ROOT_MARKER = "--bfnd-px:1px"
ROOT_BLOCK = (
    "/* Scale unit (design.md 2026-10-06): 1px up to the 1920px reference design, "
    "proportional above it. Write every size as calc(N*var(--bfnd-px)); never raw px. */\n"
    ":root{--bfnd-px:1px}\n"
    "@media (min-width:1921px){:root{--bfnd-px:max(1px,min(calc(100vw / 1920),calc(100vh / 940)))}}\n"
)

RULE_LIST_AT = {"media", "supports", "container", "layer", "document", "-moz-document", "scope", "starting-style"}
KEYFRAMES_AT = {"keyframes", "-webkit-keyframes", "-moz-keyframes"}
NUMBER_RE = re.compile(r"[+-]?(?:\d+\.?\d*|\.\d+)(?:[eE][+-]?\d+)?")
IDENT_CHAR = re.compile(r"[A-Za-z0-9_\-\u0080-￿]")


class CssError(ValueError):
    pass


@dataclass
class Result:
    text: str
    dropped: list = field(default_factory=list)  # (line, prelude, summary)
    converted: int = 0
    raw_px: list = field(default_factory=list)  # (line, property, value) — lint findings


def line_of(src: str, pos: int) -> int:
    return src.count("\n", 0, pos) + 1


def skip_string(src: str, i: int) -> int:
    quote = src[i]
    i += 1
    while i < len(src):
        c = src[i]
        if c == "\\":
            i += 2
            continue
        if c == quote:
            return i + 1
        if c == "\n":
            return i  # unterminated string; stop at newline like a CSS tokenizer
        i += 1
    return i


def skip_comment(src: str, i: int) -> int:
    end = src.find("*/", i + 2)
    if end < 0:
        raise CssError(f"unterminated comment at line {line_of(src, i)}")
    return end + 2


def scan_until(src: str, i: int, stops: str) -> int:
    """Return index of the first char in `stops` at paren/bracket depth 0."""
    depth = 0
    while i < len(src):
        c = src[i]
        if c in "\"'":
            i = skip_string(src, i)
            continue
        if src.startswith("/*", i):
            i = skip_comment(src, i)
            continue
        if c in "([":
            depth += 1
        elif c in ")]":
            depth = max(0, depth - 1)
        elif depth == 0 and c in stops:
            return i
        i += 1
    return i


def convert_value(value: str, prop: str, base: int, src: str, res: Result, lint: bool) -> str:
    """Rewrite px lengths inside one declaration value."""
    out = []
    i = 0
    funcs: list[str] = []
    prop_l = prop.strip().lower()
    while i < len(value):
        c = value[i]
        if c in "\"'":
            j = skip_string(value, i)
            out.append(value[i:j]); i = j; continue
        if value.startswith("/*", i):
            j = value.find("*/", i + 2); j = len(value) if j < 0 else j + 2
            out.append(value[i:j]); i = j; continue
        if c == "#":
            j = i + 1
            while j < len(value) and IDENT_CHAR.match(value[j]):
                j += 1
            out.append(value[i:j]); i = j; continue
        if c == "(":
            funcs.append(""); out.append(c); i += 1; continue
        if c == ")":
            if funcs:
                funcs.pop()
            out.append(c); i += 1; continue
        starts_ident = c.isalpha() or c == "_" or ord(c) > 127 or (
            c == "-" and i + 1 < len(value) and (value[i + 1].isalpha() or value[i + 1] in "-_")
        )
        if starts_ident:
            j = i
            while j < len(value) and IDENT_CHAR.match(value[j]):
                j += 1
            name = value[i:j]
            if j < len(value) and value[j] == "(":
                if name.lower() == "url":
                    k = j + 1
                    while k < len(value) and value[k] != ")":
                        k = skip_string(value, k) if value[k] in "\"'" else k + 1
                    out.append(value[i:k + 1]); i = k + 1; continue
                funcs.append(name.lower()); out.append(value[i:j + 1]); i = j + 1; continue
            out.append(name); i = j; continue
        m = NUMBER_RE.match(value, i)
        if m and (c.isdigit() or c == "." or (c in "+-" and m.end() > i + 1)):
            j = m.end()
            k = j
            while k < len(value) and (value[k].isalpha() or value[k] == "%"):
                k += 1
            unit = value[j:k]
            number = m.group(0)
            if unit.lower() == "px":
                if float(number) == 0:
                    safe_zero = not funcs and not prop_l.startswith("--") and prop_l != "flex"
                    out.append("0" if (safe_zero and not lint) else value[i:k])
                else:
                    if lint:
                        res.raw_px.append((line_of(src, base), prop.strip(), value.strip()))
                    out.append(f"calc({number}*var({SCALE_VAR}))")
                    res.converted += 1
            elif unit.lower() == "rem" and float(number) != 0:
                # rem does not follow the scale unit; the site never changes the root size,
                # so 1rem is the browser default 16px.
                if lint:
                    res.raw_px.append((line_of(src, base), prop.strip(), value.strip()))
                px = f"{float(number) * 16:.4f}".rstrip("0").rstrip(".")
                out.append(f"calc({px}*var({SCALE_VAR}))")
                res.converted += 1
            else:
                out.append(value[i:k])
            i = k
            continue
        out.append(c)
        i += 1
    return "".join(out)


def parse_declarations(src: str, i: int, out: list, res: Result, lint: bool) -> int:
    """Parse a declaration block body starting after '{'; returns index after '}'."""
    while i < len(src):
        c = src[i]
        if c.isspace() or c == ";":
            out.append(c); i += 1; continue
        if src.startswith("/*", i):
            j = skip_comment(src, i); out.append(src[i:j]); i = j; continue
        if c == "}":
            out.append(c); return i + 1
        j = scan_until(src, i, ":;{}")
        if j >= len(src) or src[j] in ";}":
            out.append(src[i:j]); i = j; continue  # malformed or empty; copy verbatim
        if src[j] == "{":
            raise CssError(f"nested rule not supported at line {line_of(src, i)}")
        prop = src[i:j]
        out.append(src[i:j + 1])
        k = scan_until(src, j + 1, ";}")
        value = src[j + 1:k]
        if prop.strip() == SCALE_VAR:
            out.append(value)
        else:
            out.append(convert_value(value, prop, j + 1, src, res, lint))
        i = k
    raise CssError("unexpected end of file inside declaration block")


def media_should_drop(prelude: str) -> bool:
    norm = re.sub(r"\s+", "", prelude.lower())
    m = re.fullmatch(r"(?:screen(?:and)?)?\(min-width:(\d+(?:\.\d+)?)px\)", norm)
    return bool(m) and float(m.group(1)) >= DROP_MIN_WIDTH


def summarize_block(body: str) -> str:
    selectors = re.findall(r"([^{};]+)\{", re.sub(r"/\*.*?\*/", "", body, flags=re.S))
    names = []
    for s in selectors:
        s = re.sub(r"\s+", " ", s.strip()).replace("body.bfnd-body ", "")
        if s and s not in names:
            names.append(s)
    text = "; ".join(names)
    return text if len(text) <= 260 else text[:257] + "..."


def parse_rule_list(src: str, i: int, out: list, res: Result, lint: bool, top: bool) -> int:
    while i < len(src):
        c = src[i]
        if c.isspace():
            out.append(c); i += 1; continue
        if src.startswith("/*", i):
            j = skip_comment(src, i); out.append(src[i:j]); i = j; continue
        if c == "}":
            if top:
                raise CssError(f"unbalanced '}}' at line {line_of(src, i)}")
            out.append(c); return i + 1
        if c == "@":
            m = re.match(r"@([A-Za-z-]+)", src[i:])
            name = m.group(1).lower() if m else ""
            j = scan_until(src, i, ";{}")
            if j >= len(src) or src[j] in ";}":
                out.append(src[i:j + 1] if j < len(src) and src[j] == ";" else src[i:j])
                i = j + 1 if j < len(src) and src[j] == ";" else j
                continue
            prelude = src[i + 1 + len(name):j]
            if name == "media" and media_should_drop(prelude):
                block: list = []
                end = parse_rule_list(src, j + 1, block, Result(text=""), lint=False, top=False)
                res.dropped.append((line_of(src, i), "@media" + re.sub(r"\s+", " ", prelude).rstrip(), summarize_block("".join(block))))
                # Drop a comment that introduces only this block, and the trailing newline.
                while out and out[-1].isspace():
                    out.pop()
                if out and out[-1].startswith("/*") and out[-1].endswith("*/"):
                    out.pop()
                    while out and out[-1].isspace():
                        out.pop()
                if out:
                    out.append("\n")
                i = end
                while i < len(src) and src[i] in " \t\r\n":
                    i += 1
                continue
            out.append(src[i:j + 1])
            if name in RULE_LIST_AT or name in KEYFRAMES_AT:
                i = parse_rule_list(src, j + 1, out, res, lint, top=False)
            else:
                i = parse_declarations(src, j + 1, out, res, lint)
            continue
        j = scan_until(src, i, "{};")
        if j >= len(src):
            out.append(src[i:]); return j
        if src[j] != "{":
            raise CssError(f"unexpected '{src[j]}' in selector at line {line_of(src, i)}")
        out.append(src[i:j + 1])
        i = parse_declarations(src, j + 1, out, res, lint)
    if not top:
        raise CssError("unexpected end of file inside block")
    return i


def insert_root_block(text: str) -> str:
    if ROOT_MARKER in text:
        return text
    i = 0
    while True:
        m = re.compile(r"\s*(?:/\*.*?\*/\s*)*@(?:charset|import)\b", re.S).match(text, i)
        if not m:
            break
        j = scan_until(text, m.end(), ";")
        i = j + 1
    head, tail = text[:i], text[i:].lstrip("\n")
    sep = "\n" if head and not head.endswith("\n") else ""
    return head + sep + ROOT_BLOCK + tail


def transform(src: str, lint: bool = False) -> Result:
    res = Result(text="")
    out: list = []
    parse_rule_list(src, 0, out, res, lint, top=True)
    text = "".join(out)
    if not lint:
        text = insert_root_block(text)
    res.text = text
    return res


def main(argv: list[str]) -> int:
    check = "--check" in argv
    files = [Path(a) for a in argv if not a.startswith("--")] or FRONTEND_CSS
    changed = 0
    for path in files:
        src = path.read_bytes().decode("utf-8")
        res = transform(src)
        if "\r\n" in src:  # keep the file's CRLF convention for inserted lines
            res.text = res.text.replace("\r\n", "\n").replace("\n", "\r\n")
        rel = path.relative_to(ROOT) if path.is_absolute() and ROOT in path.parents else path
        if res.text == src:
            print(f"unchanged  {rel}")
            continue
        changed += 1
        print(f"{'would change' if check else 'converted'}  {rel}: {res.converted} px lengths, {len(res.dropped)} wide-screen blocks removed")
        for line, prelude, summary in res.dropped:
            print(f"    - line {line}: {prelude} {{ {summary} }}")
        if not check:
            path.write_bytes(res.text.encode("utf-8"))
    return 1 if (check and changed) else 0


if __name__ == "__main__":
    sys.exit(main(sys.argv[1:]))
