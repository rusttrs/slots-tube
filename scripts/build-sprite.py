#!/usr/bin/env python3
"""Build assets/icons/sprite.svg from assets/icons/src/**/*.svg."""

from __future__ import annotations

import re
import xml.etree.ElementTree as ET
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
SRC = ROOT / "assets" / "icons" / "src"
OUT = ROOT / "assets" / "icons" / "sprite.svg"
INDEX = ROOT / "index.html"

NS = {"svg": "http://www.w3.org/2000/svg"}
ET.register_namespace("", "http://www.w3.org/2000/svg")
ET.register_namespace("xlink", "http://www.w3.org/1999/xlink")

# Info icons are forced into a shared 28×28 canvas.
INFO_SIZE = 28.0


def parse_viewbox(svg: ET.Element) -> tuple[float, float, float, float]:
    vb = svg.get("viewBox")
    if vb:
        parts = [float(x) for x in vb.replace(",", " ").split()]
        if len(parts) == 4:
            return parts[0], parts[1], parts[2], parts[3]
    w = float(re.sub(r"[^\d.]", "", svg.get("width", "0") or "0") or 0)
    h = float(re.sub(r"[^\d.]", "", svg.get("height", "0") or "0") or 0)
    return 0.0, 0.0, w or 24.0, h or 24.0


def strip_ns(tag: str) -> str:
    if "}" in tag:
        return tag.rsplit("}", 1)[-1]
    return tag


def serialize_inner(el: ET.Element) -> str:
    """Serialize element children (not the wrapper itself)."""
    parts: list[str] = []
    if el.text and el.text.strip():
        parts.append(el.text)
    for child in list(el):
        parts.append(ET.tostring(child, encoding="unicode"))
    return "".join(parts)


def uniquify_ids(markup: str, prefix: str) -> str:
    ids = set(re.findall(r'\bid="([^"]+)"', markup))
    for old in sorted(ids, key=len, reverse=True):
        new = f"{prefix}-{old}"
        markup = markup.replace(f'id="{old}"', f'id="{new}"')
        markup = markup.replace(f'url(#{old})', f"url(#{new})")
        markup = markup.replace(f'href="#{old}"', f'href="#{new}"')
        markup = markup.replace(f'xlink:href="#{old}"', f'xlink:href="#{new}"')
    return markup


def normalize_info(inner: str, min_x: float, min_y: float, w: float, h: float) -> tuple[str, str]:
    # Fit inside 28×28 without upscaling — matches Figma frames where smaller glyphs
    # sit letterboxed in the icon slot (e.g. 25×25 art in a 28 slot).
    scale = min(INFO_SIZE / w, INFO_SIZE / h, 1.0) if w and h else 1.0
    tx = (INFO_SIZE - w * scale) / 2 - min_x * scale
    ty = (INFO_SIZE - h * scale) / 2 - min_y * scale
    transform = f"translate({tx:.4f} {ty:.4f}) scale({scale:.6f})"
    return f'<g transform="{transform}">{inner}</g>', f"0 0 {INFO_SIZE:g} {INFO_SIZE:g}"


def load_symbol(path: Path) -> tuple[str, str, str]:
    """Return (symbol_id, viewBox, inner_markup)."""
    raw = path.read_text(encoding="utf-8")
    # ElementTree needs a single root; keep xmlns
    tree = ET.fromstring(raw)
    if strip_ns(tree.tag) != "svg":
        raise ValueError(f"Not an svg: {path}")

    min_x, min_y, w, h = parse_viewbox(tree)
    symbol_id = path.stem
    inner = serialize_inner(tree)
    # Drop decorative attrs that don't belong inside symbol content wrappers
    inner = re.sub(r'\s+style="[^"]*"', "", inner)
    inner = uniquify_ids(inner, symbol_id)

    if path.parent.name == "info" or symbol_id.startswith("info-"):
        inner, view_box = normalize_info(inner, min_x, min_y, w, h)
    elif path.parent.name == "toc" or symbol_id.startswith("toc-"):
        # Square 17 canvas for TOC glyphs — never upscale past source size
        size = 17.0
        scale = min(size / w, size / h, 1.0) if w and h else 1.0
        tx = (size - w * scale) / 2 - min_x * scale
        ty = (size - h * scale) / 2 - min_y * scale
        transform = f"translate({tx:.4f} {ty:.4f}) scale({scale:.6f})"
        inner = f'<g transform="{transform}">{inner}</g>'
        view_box = f"0 0 {size:g} {size:g}"
    else:
        view_box = f"{min_x:g} {min_y:g} {w:g} {h:g}"

    return symbol_id, view_box, inner


def collect_sources() -> list[Path]:
    files = sorted(SRC.rglob("*.svg"))
    if not files:
        raise SystemExit(f"No SVG sources under {SRC}")
    return files


def build() -> None:
    symbols: list[str] = []
    for path in collect_sources():
        symbol_id, view_box, inner = load_symbol(path)
        symbols.append(
            f'  <symbol id="{symbol_id}" viewBox="{view_box}" fill="none">\n'
            f"    {inner}\n"
            f"  </symbol>"
        )

    out = (
        '<?xml version="1.0" encoding="UTF-8"?>\n'
        '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" '
        'fill="none" aria-hidden="true">\n'
        + "\n".join(symbols)
        + "\n</svg>\n"
    )
    OUT.parent.mkdir(parents=True, exist_ok=True)
    OUT.write_text(out, encoding="utf-8")
    print(f"Wrote {OUT.relative_to(ROOT)} ({len(symbols)} symbols)")
    sync_inline_sprite(out)


def sync_inline_sprite(sprite_xml: str) -> None:
    """Keep a hidden copy of the sprite in index.html so Chrome works on file://."""
    if not INDEX.exists():
        return

    sprite = re.sub(r"<\?xml[^?]*\?>\s*", "", sprite_xml)
    sprite = re.sub(
        r"<svg\b",
        '<svg class="svg-sprite" aria-hidden="true" focusable="false"',
        sprite,
        count=1,
    )
    html = INDEX.read_text(encoding="utf-8")
    html = re.sub(
        r"\s*(?:<!--.*?TEMP \(local / Chrome file:// only\):.*?-->\s*)?<svg class=\"svg-sprite\"[^>]*>.*?</svg>\s*",
        "\n",
        html,
        count=1,
        flags=re.S,
    )
    note = (
        "<!--\n"
        "      TEMP (local / Chrome file:// only):\n"
        "      Inline SVG sprite so icons render when opening index.html without a server.\n"
        "      Chrome blocks <use href=\"external.svg#id\"> on the file:// protocol.\n"
        "      On production: remove this block and point <use> back to assets/icons/sprite.svg#id\n"
        "      (or serve the external sprite over http(s)). Keep scripts/build-sprite.py in sync.\n"
        "    -->"
    )
    html = re.sub(
        r"(<body[^>]*>)",
        r"\1\n    " + note + "\n    " + sprite.replace("\n", "\n    "),
        html,
        count=1,
    )
    INDEX.write_text(html, encoding="utf-8")
    print(f"Synced inline sprite into {INDEX.relative_to(ROOT)}")


if __name__ == "__main__":
    build()
