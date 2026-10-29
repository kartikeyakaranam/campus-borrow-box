"""Create a clean shared-hosting ZIP without local data or credentials."""
from pathlib import Path
from zipfile import ZipFile, ZIP_DEFLATED

root = Path(__file__).resolve().parents[1]
output = root.parent / "Borrow_Box_Deployment_Ready.zip"
public_files = [
    ".htaccess", "account.php", "action.php", "add.php", "config.php",
    "dashboard.php", "edit.php", "index.php", "item.php", "partials.php",
    "assets/app.js", "assets/style.css", "uploads/.htaccess", "uploads/.gitkeep",
]
setup_files = {
    "database/schema.sql": "setup/schema.sql",
    "deployment/config.local.example.php": "setup/config.local.example.php",
    "deployment/README.md": "setup/README.md",
}

with ZipFile(output, "w", ZIP_DEFLATED) as archive:
    for name in public_files:
        archive.write(root / name, "htdocs/" + name)
    for source, destination in setup_files.items():
        archive.write(root / source, destination)

with ZipFile(output) as archive:
    if archive.testzip() is not None:
        raise RuntimeError("Deployment ZIP failed the integrity check")
    names = archive.namelist()
    if any("report/" in name or "tests/" in name or "config.local.php" in name for name in names):
        raise RuntimeError("Private files would be included in the deployment ZIP")
print(output)
