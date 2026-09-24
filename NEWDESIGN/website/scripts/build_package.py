"""Package the WordPress plugin without build scripts or source originals."""
from __future__ import annotations

from pathlib import Path
from shutil import copy2, copytree, rmtree
from zipfile import ZipFile, ZIP_DEFLATED

root = Path(__file__).resolve().parents[1]
dist = root / "dist"
package = dist / "bears-fantasyland-newdesign"
if package.exists():
    rmtree(package)
package.mkdir(parents=True)
for name in ("assets", "data", "public", "src", "templates"):
    copytree(root / name, package / name)
copy2(root / "bears-fantasyland-newdesign.php", package / "bears-fantasyland-newdesign.php")
zip_path = dist / "bears-fantasyland-newdesign.zip"
with ZipFile(zip_path, "w", ZIP_DEFLATED, compresslevel=8) as z:
    for path in sorted(package.rglob("*")):
        if path.is_file():
            z.write(path, arcname=path.relative_to(dist).as_posix())
print(zip_path)
print(f"{zip_path.stat().st_size / 1048576:.2f} MiB")
