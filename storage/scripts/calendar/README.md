# Calendar PDF generator

`calendar_pdf.py` renders the church calendar (A3, cover + 12 months) from a JSON file.
PHP (`CalendarApiController::pdf` + `CalendarDataBuilder`) only collects the data and image paths;
the layout, CSS and fonts all live in this folder, so the output does not depend on web URLs,
Vite or fonts installed on the machine.

    python calendar_pdf.py data.json output.pdf

| File | Purpose |
| --- | --- |
| `calendar_pdf.py` | reads the JSON, computes the A3 layout, renders with WeasyPrint |
| `template.html.j2` | Jinja2 page template (cover, header photo, weekday bar, day grid, footer) |
| `calendar.css` | styles; sizes are in mm / rem (1rem is about 3mm on A3); tunable values are CSS variables |
| `settings_schema.json` | the design settings (colors, sizes, toggles): defaults, ranges and labels |
| `fonts/` | Noto Sans CJK SC, SIL Open Font License 1.1 (see below) |

## Designs

An appearance is a named **design** (`calendar_design`: name, parameters, custom CSS). There can be several;
each calendar uses exactly one (`calendar.design_id`). A new calendar starts with the design of the closest
earlier calendar. A design is shared, so editing it changes every calendar that uses it.

Permissions:

- `calendar` (年历制作): make calendars and choose which design a calendar uses; preview any design.
- `calendar_design` (年历外观设计): additionally create, edit, rename, delete and restore designs.
  Roles are registered by `php artisan book:update-user-roles`; assign the role to users in the admin page.

Designs are soft-deleted and a design that a calendar still uses cannot be deleted.

## Design parameters

`settings_schema.json` is the single source of truth for everything that can be tuned without touching
CSS: PHP validates input against it (`CalendarSettings`), the Vue "外观" tab is generated from it, and
`calendar_pdf.py` takes its defaults from it. Values missing from a design fall back to the schema defaults.

To add a setting: add an entry to the schema (`"css": true` makes it the CSS variable `--key-name`,
otherwise read it from `settings` in the script or template) and use `var(--key-name)` in `calendar.css`.

## History versions

Calendars of earlier years (year < current year + 1) are read only: no image upload, CSV import, holiday
refresh, change of design, PDF generation or download. They can be looked at and previewed with any design.
Enforced in `CalendarApiController` (`lockedResponse`), not only in the UI.

## Custom CSS

Each design can carry free-form CSS in `calendar_design.styles` (the "高级：自定义 CSS" box on the design tab).
It is inlined into the document after `calendar.css`, so it overrides the base styles and the parameters.
Hooks for selecting things: `.month-N` on each page, and `data-date="YYYY-MM-DD"` on every day cell.

It is user input, so it is guarded twice:

- `CalendarStyles` (PHP) rejects `@import`, web or `file:` addresses, `../` and `<`, with line numbers, and
  limits the size to 20 KB.
- `RestrictedFetcher` (in `calendar_pdf.py`) only lets WeasyPrint read local files inside this folder and the
  calendar's image folder, so `url()` can never reach other files or the network. Relative `url()` in custom
  CSS resolves in the image folder (`url(01.jpg)`).

Problems WeasyPrint finds in it (unknown property, invalid value, with line numbers) are printed as
`CSS_WARNING<TAB>text` lines on stderr; the API returns them to the UI in the `X-Calendar-Warnings` header.

Note: the stylesheet must be inlined, not passed as `write_pdf(stylesheets=...)`. WeasyPrint treats those as
user styles, which rank below the document's own stylesheet and could not override `calendar.css`.

## Change records

Every change of a design (parameters and custom CSS together) is stored as one row of
`calendar_design_revision`: who made it, when, and the full state afterwards. Rows are never updated or deleted.

- `create`: the design was created (copied from another one).
- `save`: saved by a user. Saving something identical to the current content records nothing.
- `restore`: the design was set back to an earlier record; `restored_from_id` names that record.
  A restore is a change like any other, so it can itself be undone by restoring a newer record.
- `initial`: the state a design had before its first record (only when it already had custom content).

The page lists only the latest 10 records (`CalendarDesigns::HISTORY_SHOWN`); all of them stay in the table.

## Fonts

`fonts/` holds Noto Sans SC (SIL Open Font License 1.1), built by `tools/build_fonts.py`:

| File | Purpose |
| --- | --- |
| `NotoSC-Light/Regular/Medium-GB2312.ttf` | the three weights used, reduced to the 6763 GB2312 hanzi plus Latin and punctuation (about 2 MB each) |
| `NotoSC-Regular-Full.ttf` | full static Regular, only a fallback for rare characters (some names); embedded in a PDF only when such a character appears |

Why reduced: WeasyPrint re-subsets every font it embeds, which costs seconds for a full CJK font and would make
the live preview slow. The reduced fonts embed in about 0.1 s.

Why TrueType and not "Noto Sans CJK" (CFF/OTF): reducing the CFF font the usual way produces a PDF whose CJK
text is in the text layer but invisible (glyph shapes lost during WeasyPrint's second subsetting);
`--retain-gids` fixes the glyphs but brings the slowness back. Always check a rendered page, not just the
extracted text, after changing fonts.

Rebuild (needs the variable font, `brew install --cask font-noto-sans-sc`, and fontTools which comes with WeasyPrint):

    python tools/build_fonts.py "NotoSansSC[wght].ttf"

## Environment

Python 3.10+ with `weasyprint` and `jinja2` (Pillow comes with WeasyPrint). WeasyPrint needs Pango
(`libpango-1.0-0`, present in the ddev web image).

ddev:

    ddev exec 'python3 -m venv storage/app/pyvenv-ddev && storage/app/pyvenv-ddev/bin/pip install weasyprint jinja2'

then point `COMMON_PYTHON_PATH` in `.env` to `/var/www/html/storage/app/pyvenv-ddev/bin/python3`.
(`PYTHON_PATH`, used for the PPT generator, can use the same virtual environment.)

## Images

Read from `storage/app/public/images/calendar/<year>/`: `cover`, `01` to `12`, `overlay` (PNG), `footer`
(`.jpg` or `.png`). The calendar page uploads them there; missing months render as a grey placeholder.
