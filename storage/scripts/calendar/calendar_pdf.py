#!/usr/bin/env python3
"""
Render the church calendar as a PDF (A3, one cover page + 12 month pages).

Usage:  calendar_pdf.py <data.json> <output.pdf>

PHP only collects data (see App CalendarDataBuilder): days, lunar labels, holidays, events,
bible verses and absolute image paths. Everything visual (template, CSS, fonts) lives in this
folder, so the result does not depend on web URLs, Vite or fonts installed on the machine.
"""
import json
import logging
import re
import sys
import tempfile
from pathlib import Path
from urllib.parse import unquote, urlparse

from jinja2 import Environment, FileSystemLoader, select_autoescape
from PIL import Image, ImageOps
from weasyprint import CSS, HTML
from weasyprint.urls import URLFetcher

BASE = Path(__file__).resolve().parent
THEMES = BASE / "themes"
DEFAULT_THEME = "classic"

# A3 portrait in millimetres. All layout numbers below are derived from these.
PAGE_W = 297.0
PAGE_H = 420.0

# The original design was drawn for 3508px width with 1rem = 35.36px.
REM = PAGE_W * 35.36 / 3508  # mm per rem (about 3 mm)

TITLE_TOP_GAP = 3 * REM          # space between header and month title
WEEKDAY_H = 5 * REM              # weekday bar
SIDE_PAD = 5 * REM               # left/right padding of the calendar grid
FOOTER_SIDE = 10 * REM           # left/right padding of the footer image
FOOTER_GAP = 2 * REM             # space above the footer
FOOTER_BOTTOM = 4 * REM          # space below the footer

# The page geometry below is computed here from these settings. A theme whose schema does not define them gets
# these values, so a theme only needs to offer the settings it really uses.
LAYOUT_DEFAULTS = {"header_ratio": 0.5, "title_size": 2.4, "title_gap": 3, "show_footer": True, "show_overlay": True}


def load_theme(key):
    """Find a theme by the "key" written in its theme.json (the folder name does not matter).

    A theme is a template + a stylesheet + a settings schema. All must lie inside themes/ (they may live in another theme's folder,
    so that two themes can share a template). An unknown key falls back to the default theme.
    Returns (template path relative to BASE, stylesheet path, settings schema path).
    """
    found = {}
    for manifest in sorted(THEMES.glob("*/theme.json")):
        try:
            info = json.loads(manifest.read_text(encoding="utf-8"))
        except ValueError:
            print(f"warning: invalid {manifest}", file=sys.stderr)
            continue
        if isinstance(info, dict) and isinstance(info.get("key"), str):
            found.setdefault(info["key"], (manifest.parent, info))

    if key not in found:
        if key:
            print(f"warning: theme '{key}' not found, using '{DEFAULT_THEME}'", file=sys.stderr)
        key = DEFAULT_THEME
    if key not in found:
        raise SystemExit(f"error: default theme '{DEFAULT_THEME}' is missing in {THEMES}")

    folder, info = found[key]
    template = (folder / info.get("template", "template.html.j2")).resolve()
    css = (folder / info.get("css", "calendar.css")).resolve()
    schema = (folder / info.get("settings", "settings_schema.json")).resolve()
    for path in (template, css, schema):
        if not path.is_file() or THEMES.resolve() not in path.parents:
            raise SystemExit(f"error: theme '{key}' points to an invalid file: {path}")

    return template.relative_to(BASE).as_posix(), css, schema


def load_schema(path):
    return json.loads(Path(path).read_text(encoding="utf-8"))


def coerce(item, value):
    """Return the value if it is valid for this setting, otherwise its default (never trust input)."""
    default = item["default"]
    kind = item["type"]
    try:
        if kind == "color":
            return value if isinstance(value, str) and re.fullmatch(r"#[0-9a-fA-F]{6}", value) else default
        if kind == "number":
            number = float(value)
            return number if item["min"] <= number <= item["max"] else default
        if kind == "select":
            allowed = [o["value"] for o in item["options"]]
            return value if value in allowed else default
        if kind == "toggle":
            return value if isinstance(value, bool) else default
    except (TypeError, ValueError):
        pass
    return default


def resolve_settings(schema, given):
    """Defaults from the schema, overridden by valid values from the calendar's settings."""
    given = given if isinstance(given, dict) else {}
    return {i["key"]: coerce(i, given.get(i["key"], i["default"])) for i in schema["settings"]}


def css_variables(schema, settings):
    """':root' declarations for every setting flagged css=true, e.g. --day-size: 2.4rem."""
    lines = []
    for item in schema["settings"]:
        if not item.get("css"):
            continue
        value = settings[item["key"]]
        if item["type"] == "number":
            value = f"{value:g}{item.get('unit', '')}"
        lines.append(f"--{item['key'].replace('_', '-')}: {value};")
    return "\n        ".join(lines)


class RestrictedFetcher(URLFetcher):
    """Only lets WeasyPrint read local files inside a few folders (and data: URIs).

    Custom CSS is user input: without this, a stylesheet could point url() at a web address or at any
    file on the server. Allowed roots are this folder (template, fonts) and the calendar's image folder.
    """

    def __init__(self, roots):
        super().__init__(allowed_protocols={"file", "data"})
        self.roots = [Path(r).resolve() for r in roots if r]

    def fetch(self, url, headers=None):
        if url.lower().startswith("file:"):
            path = Path(unquote(urlparse(url).path)).resolve()
            if not any(path == root or root in path.parents for root in self.roots):
                raise ValueError(f"access to {path} is not allowed")
        return super().fetch(url, headers)


class WarningCollector(logging.Handler):
    """Collects WeasyPrint's CSS warnings (unknown property, invalid value, ...) as plain strings."""

    def __init__(self):
        super().__init__(level=logging.WARNING)
        self.messages = []

    def emit(self, record):
        self.messages.append(record.getMessage())


def report_css_warnings(css_text, base_dir, fetcher):
    """Parse the user stylesheet once on its own, only to report its warnings (with line numbers).

    The stylesheet is applied by inlining it into the document (see the template), not through
    write_pdf(stylesheets=...): WeasyPrint ranks those as *user* styles, below the document's own
    stylesheet, so they could not override the theme stylesheet.
    """
    collector = WarningCollector()
    logger = logging.getLogger("weasyprint")
    logger.addHandler(collector)
    try:
        CSS(string=css_text, base_url=Path(base_dir).as_uri() + "/" if base_dir else None, url_fetcher=fetcher)
    finally:
        logger.removeHandler(collector)
    # Machine readable lines for the PHP side; one per warning
    for message in collector.messages:
        print("CSS_WARNING\t" + message.replace("\n", " ")[:300], file=sys.stderr)


def image_ratio(path):
    """height / width of an image file, or None."""
    if not path:
        return None
    try:
        with Image.open(path) as img:
            return img.height / img.width
    except Exception as exc:  # unreadable image: render the page without it
        print(f"warning: cannot read image {path}: {exc}", file=sys.stderr)
        return None


def crop_box(width, height, ratio, crop):
    """The part of a width x height photo that shows in a header of height/width = ratio, as (left, top, right, bottom).

    Start from what "cover" shows (the photo scaled until it fills the header, centred), shrink it by the zoom and
    centre it on (x, y), given as fractions of the photo; the box never leaves the photo. The crop editor in the
    browser (cropGeometry.ts) uses the same arithmetic, so the preview matches the PDF.
    """
    if height / width > ratio:  # tall photo: full width, the header cuts off top and bottom
        base_w, base_h = width, width * ratio
    else:  # wide photo: full height
        base_w, base_h = height / ratio, height
    zoom = min(max(float(crop.get("zoom", 1)), 1.0), 4.0)
    box_w, box_h = base_w / zoom, base_h / zoom
    left = min(max(float(crop.get("x", 0.5)) * width - box_w / 2, 0), width - box_w)
    top = min(max(float(crop.get("y", 0.5)) * height - box_h / 2, 0), height - box_h)
    return left, top, left + box_w, top + box_h


def crop_to_header(path, crop, ratio, out_dir, name):
    """Write the chosen part of a photo to out_dir and return that path (the original path if anything goes wrong).

    The photo is turned upright first (EXIF orientation), like the browser and WeasyPrint show it.
    """
    try:
        with Image.open(path) as src:
            is_png = (src.format or "").upper() == "PNG"
            img = ImageOps.exif_transpose(src)
            left, top, right, bottom = crop_box(img.width, img.height, ratio, crop)
            box = (round(left), round(top), max(round(right), round(left) + 1), max(round(bottom), round(top) + 1))
            part = img.crop(box)
            out = Path(out_dir) / (name + (".png" if is_png else ".jpg"))
            if is_png:
                part.save(out, "PNG")
            else:
                part.convert("RGB").save(out, "JPEG", quality=95, subsampling=0)
            return str(out)
    except Exception as exc:  # keep the page: use the photo uncropped
        print(f"warning: cannot crop image {path}: {exc}", file=sys.stderr)
        return path


def uri(path):
    return Path(path).as_uri() if path and Path(path).is_file() else None


def main():
    if len(sys.argv) != 3:
        print("Usage: calendar_pdf.py data.json output.pdf", file=sys.stderr)
        return 1

    data = json.loads(Path(sys.argv[1]).read_text(encoding="utf-8"))
    out_pdf = sys.argv[2]

    template_name, theme_css, schema_path = load_theme(data.get("theme"))
    schema = load_schema(schema_path)
    settings = {**LAYOUT_DEFAULTS, **resolve_settings(schema, data.get("settings"))}

    header_h = PAGE_W * settings["header_ratio"]

    footer_path = data.get("footer") if settings["show_footer"] and uri(data.get("footer")) else None
    footer_ratio = image_ratio(footer_path)
    footer_w = PAGE_W - 2 * FOOTER_SIDE
    footer_h = footer_w * footer_ratio if footer_ratio else 0
    footer_block = (FOOTER_GAP + footer_h + FOOTER_BOTTOM) if footer_h else FOOTER_BOTTOM

    # The title box is margin 1rem + padding 2.3rem + borders + one text line (about 1.5 x font size);
    # the gap to the weekday bar is a setting. Both follow the title size instead of being a fixed height.
    title_h = (3.4 + 1.5 * settings["title_size"] + settings["title_gap"]) * REM
    title_top = header_h + TITLE_TOP_GAP
    weekday_top = title_top + title_h
    days_top = weekday_top + WEEKDAY_H
    days_h = PAGE_H - footer_block - days_top

    months = data["months"]
    crop_dir = tempfile.TemporaryDirectory(prefix="calendar_crop_")
    for month in months:
        image = month.get("image")
        crop = month.get("image_crop")
        if image and crop and Path(image).is_file():
            image = crop_to_header(image, crop, settings["header_ratio"], crop_dir.name, f"month-{month['month']}")
        month["image_uri"] = uri(image)
        if not month["image_uri"]:
            print(f"warning: month {month['month']} has no image", file=sys.stderr)
        # row height so that the grid fills the area exactly, whether the month needs 4, 5 or 6 weeks
        month["row_h"] = days_h / max(month["week_count"], 1)

    custom_css = (data.get("styles") or "").strip()
    if custom_css:
        report_css_warnings(custom_css, data.get("images_dir"), RestrictedFetcher([BASE, data.get("images_dir")]))
        # Inlined into a <style> element: "</" could end it early, so it is neutralised.
        custom_css = custom_css.replace("</", "<\\/")

    env = Environment(loader=FileSystemLoader(str(BASE)), autoescape=select_autoescape(["html", "j2"]))
    html = env.get_template(template_name).render(
        year=data["year"],
        weekdays=data["weekdays"],
        months=months,
        cover_uri=uri(data.get("cover")),
        overlay_uri=uri(data.get("overlay")) if settings["show_overlay"] else None,
        settings=settings,
        css_vars=css_variables(schema, settings),
        css_uri=theme_css.as_uri(),
        custom_css=custom_css,
        footer_uri=uri(footer_path),
        page_w=PAGE_W,
        page_h=PAGE_H,
        rem=REM,
        header_h=header_h,
        title_top=title_top,
        title_h=title_h,
        weekday_top=weekday_top,
        weekday_h=WEEKDAY_H,
        days_top=days_top,
        days_h=days_h,
        side_pad=SIDE_PAD,
        footer_side=FOOTER_SIDE,
        footer_h=footer_h,
        footer_bottom=FOOTER_BOTTOM,
    )

    fetcher = RestrictedFetcher([BASE, data.get("images_dir"), crop_dir.name])

    # Relative url() in the custom CSS resolves in the calendar's image folder (the document base);
    # the theme stylesheet is referenced by absolute URI, so its fonts still resolve relative to it.
    base_url = (Path(data["images_dir"]).as_uri() + "/") if data.get("images_dir") else (BASE.as_uri() + "/")
    HTML(string=html, base_url=base_url, url_fetcher=fetcher).write_pdf(out_pdf)
    crop_dir.cleanup()
    print(f"PDF generated: {out_pdf}", file=sys.stderr)
    return 0


if __name__ == "__main__":
    sys.exit(main())
