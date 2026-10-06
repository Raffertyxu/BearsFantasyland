"""Build the installable WordPress theme ZIP."""
from pathlib import Path
from os import getpid
from zipfile import ZipFile, ZIP_DEFLATED

root = Path(__file__).resolve().parent
theme = root / "bears-fantasyland"
dist = root / "dist"
dist.mkdir(exist_ok=True)
archive = dist / "bears-fantasyland.zip"
temporary = dist / f".bears-fantasyland-{getpid()}.tmp.zip"

with ZipFile(temporary, "w", ZIP_DEFLATED, compresslevel=8) as package:
    for path in sorted(theme.rglob("*")):
        # README.md documents internals for developers; keep it out of the public theme folder.
        if path.is_file() and path.relative_to(theme).as_posix() != "README.md":
            package.write(path, arcname=path.relative_to(root).as_posix())

temporary.replace(archive)

print(f"{archive} ({archive.stat().st_size / 1048576:.2f} MiB)")
