"""Fail when a front-end stylesheet contains a size that will not scale.

Every length in the front-end CSS must be written as calc(N*var(--bfnd-px))
(see design.md, 2026-10-06). This lint reports, with line numbers:
  * raw px / rem lengths inside declaration values (0px is allowed),
  * new large-screen patch layers (@media (min-width >= 2000px)),
  * a missing --bfnd-px definition.
Fix by running:  python scripts/scale_px_units.py
"""
from __future__ import annotations

import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parent))
from scale_px_units import FRONTEND_CSS, ROOT, ROOT_MARKER, transform  # noqa: E402


def main() -> int:
    problems = 0
    for path in FRONTEND_CSS:
        src = path.read_bytes().decode("utf-8")
        rel = path.relative_to(ROOT).as_posix()
        res = transform(src, lint=True)
        for line, prop, value in res.raw_px:
            problems += 1
            print(f"{rel}:{line}: non-scaling length in '{prop}: {value[:90]}'")
        for line, prelude, _ in res.dropped:
            problems += 1
            print(f"{rel}:{line}: large-screen patch layer '{prelude}' (1920 is the reference; sizes scale automatically)")
        if ROOT_MARKER not in src:
            problems += 1
            print(f"{rel}: missing the --bfnd-px definition")
    if problems:
        print(f"\n{problems} problem(s). Run: python scripts/scale_px_units.py")
        return 1
    print(f"CSS units OK ({len(FRONTEND_CSS)} front-end stylesheets use the --bfnd-px scale).")
    return 0


if __name__ == "__main__":
    sys.exit(main())
