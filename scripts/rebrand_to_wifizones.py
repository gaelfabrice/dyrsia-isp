#!/usr/bin/env python3
"""Replace PHPNuxBill / NuxBill branding with wifizones in project sources."""
from __future__ import annotations

import os
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
SKIP_DIRS = {
    "ui/compiled",
    "system/uploads",
    "vendor",
    ".git",
    "node_modules",
}
SKIP_FILES = set()
EXTENSIONS = {
    ".php", ".tpl", ".json", ".html", ".md", ".yml", ".yaml", ".sql", ".txt", ".js", ".css", ".bk",
}

REPLACEMENTS = [
    ("PHPNuxBill", "wifizones"),
    ("PHPNUXBILL", "wifizones"),
    ("PHPnuxBill", "wifizones"),
    ("PHPMixBill", "wifizones"),
    ("NuxBill", "wifizones"),
    ("Nuxbill", "wifizones"),
    ("nuxbill", "wifizones"),
    ("phpnuxbill", "wifizones"),
]


def should_skip(path: Path) -> bool:
    rel = path.relative_to(ROOT).as_posix()
    for part in SKIP_DIRS:
        if rel.startswith(part + "/") or rel == part:
            return True
    return rel in SKIP_FILES


def rebrand_file(path: Path) -> bool:
    try:
        text = path.read_text(encoding="utf-8")
    except (UnicodeDecodeError, OSError):
        return False
    original = text
    for old, new in REPLACEMENTS:
        text = text.replace(old, new)
    if text != original:
        path.write_text(text, encoding="utf-8")
        return True
    return False


def main():
    changed = 0
    for dirpath, dirnames, filenames in os.walk(ROOT):
        dirnames[:] = [
            d for d in dirnames
            if not should_skip(Path(dirpath) / d)
        ]
        for name in filenames:
            path = Path(dirpath) / name
            if should_skip(path):
                continue
            if path.suffix.lower() not in EXTENSIONS and name not in ("Dockerfile", "composer.json"):
                continue
            if rebrand_file(path):
                changed += 1
                print(path.relative_to(ROOT))
    print(f"\nDone. {changed} file(s) updated.")


if __name__ == "__main__":
    main()
