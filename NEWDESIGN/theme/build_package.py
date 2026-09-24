"""Build the installable WordPress theme ZIP."""
from pathlib import Path
from zipfile import ZipFile, ZIP_DEFLATED

root = Path(__file__).resolve().parent
theme = root / "bears-fantasyland"
dist = root / "dist"
dist.mkdir(exist_ok=True)
archive = dist / "bears-fantasyland.zip"

with ZipFile(archive, "w", ZIP_DEFLATED, compresslevel=8) as package:
    for path in sorted(theme.rglob("*")):
        if path.is_file():
            package.write(path, arcname=path.relative_to(root).as_posix())

print(f"{archive} ({archive.stat().st_size / 1048576:.2f} MiB)")
