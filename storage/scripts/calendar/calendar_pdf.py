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
from pathlib import Path
from urllib.parse import unquote, urlparse

from jinja2 import Environment, FileSystemLoader, select_autoescape
from PIL import Image
from weasyprint import CSS, HTML
from weasyprint.urls import URLFetcher

BASE = Path(__file__).resolve().parent

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


def load_schema():
    return json.loads((BASE / "settings_schema.json").read_text(encoding="utf-8"))


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
    stylesheet, so they could not override calendar.css.
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


def uri(path):
    return Path(path).as_uri() if path and Path(path).is_file() else None


def main():
    if len(sys.argv) != 3:
        print("Usage: calendar_pdf.py data.json output.pdf", file=sys.stderr)
        return 1

    data = json.loads(Path(sys.argv[1]).read_text(encoding="utf-8"))
    out_pdf = sys.argv[2]

    schema = load_schema()
    settings = resolve_settings(schema, data.get("settings"))

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
    for month in months:
        month["image_uri"] = uri(month.get("image"))
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
    html = env.get_template("template.html.j2").render(
        year=data["year"],
        weekdays=data["weekdays"],
        months=months,
        cover_uri=uri(data.get("cover")),
        overlay_uri=uri(data.get("overlay")) if settings["show_overlay"] else None,
        settings=settings,
        css_vars=css_variables(schema, settings),
        css_uri=(BASE / "calendar.css").as_uri(),
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

    fetcher = RestrictedFetcher([BASE, data.get("images_dir")])

    # Relative url() in the custom CSS resolves in the calendar's image folder (the document base);
    # the base stylesheet is referenced by absolute URI, so fonts still resolve next to calendar.css.
    base_url = (Path(data["images_dir"]).as_uri() + "/") if data.get("images_dir") else (BASE.as_uri() + "/")
    HTML(string=html, base_url=base_url, url_fetcher=fetcher).write_pdf(out_pdf)
    print(f"PDF generated: {out_pdf}", file=sys.stderr)
    return 0


if __name__ == "__main__":
    sys.exit(main())
