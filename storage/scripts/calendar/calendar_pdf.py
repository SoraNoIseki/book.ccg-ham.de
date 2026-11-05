#!/usr/bin/env python3
"""
Render the church calendar as a PDF (A3, one cover page + 12 month pages).

Usage:  calendar_pdf.py <data.json> <output.pdf>

PHP only collects data (see App CalendarDataBuilder): days, lunar labels, holidays, events,
bible verses and absolute image paths. Everything visual (template, CSS, fonts) lives in this
folder, so the result does not depend on web URLs, Vite or fonts installed on the machine.
"""
import json
import sys
from pathlib import Path

from jinja2 import Environment, FileSystemLoader, select_autoescape
from PIL import Image
from weasyprint import HTML

BASE = Path(__file__).resolve().parent

# A3 portrait in millimetres. All layout numbers below are derived from these.
PAGE_W = 297.0
PAGE_H = 420.0

# The original design was drawn for 3508px width with 1rem = 35.36px.
REM = PAGE_W * 35.36 / 3508  # mm per rem (about 3 mm)

HEADER_H = PAGE_W / 2            # header photo is twice as wide as tall
TITLE_TOP_GAP = 3 * REM          # space between header and month title
TITLE_H = 7 * REM                # month title block
WEEKDAY_H = 5 * REM              # weekday bar
SIDE_PAD = 5 * REM               # left/right padding of the calendar grid
FOOTER_SIDE = 10 * REM           # left/right padding of the footer image
FOOTER_GAP = 2 * REM             # space above the footer
FOOTER_BOTTOM = 4 * REM          # space below the footer


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

    footer_path = data.get("footer") if uri(data.get("footer")) else None
    footer_ratio = image_ratio(footer_path)
    footer_w = PAGE_W - 2 * FOOTER_SIDE
    footer_h = footer_w * footer_ratio if footer_ratio else 0
    footer_block = (FOOTER_GAP + footer_h + FOOTER_BOTTOM) if footer_h else FOOTER_BOTTOM

    title_top = HEADER_H + TITLE_TOP_GAP
    weekday_top = title_top + TITLE_H
    days_top = weekday_top + WEEKDAY_H
    days_h = PAGE_H - footer_block - days_top

    months = data["months"]
    for month in months:
        month["image_uri"] = uri(month.get("image"))
        if not month["image_uri"]:
            print(f"warning: month {month['month']} has no image", file=sys.stderr)
        # row height so that the grid fills the area exactly, whether the month needs 4, 5 or 6 weeks
        month["row_h"] = days_h / max(month["week_count"], 1)

    env = Environment(loader=FileSystemLoader(str(BASE)), autoescape=select_autoescape(["html", "j2"]))
    html = env.get_template("template.html.j2").render(
        year=data["year"],
        weekdays=data["weekdays"],
        months=months,
        cover_uri=uri(data.get("cover")),
        overlay_uri=uri(data.get("overlay")),
        footer_uri=uri(footer_path),
        page_w=PAGE_W,
        page_h=PAGE_H,
        rem=REM,
        header_h=HEADER_H,
        title_top=title_top,
        title_h=TITLE_H,
        weekday_top=weekday_top,
        weekday_h=WEEKDAY_H,
        days_top=days_top,
        days_h=days_h,
        side_pad=SIDE_PAD,
        footer_side=FOOTER_SIDE,
        footer_h=footer_h,
        footer_bottom=FOOTER_BOTTOM,
    )

    # base_url makes the relative font and stylesheet paths in the template resolve inside this folder.
    HTML(string=html, base_url=str(BASE) + "/").write_pdf(out_pdf)
    print(f"PDF generated: {out_pdf}", file=sys.stderr)
    return 0


if __name__ == "__main__":
    sys.exit(main())
