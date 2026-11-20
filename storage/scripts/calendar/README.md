# Calendar PDF generator

`calendar_pdf.py` renders the church calendar (A3, cover + 12 months) from a JSON file.
PHP (`CalendarApiController::pdf` + `CalendarDataBuilder`) only collects the data and image paths;
the layout, CSS and fonts all live in this folder, so the output does not depend on web URLs,
Vite or fonts installed on the machine.

    python calendar_pdf.py data.json output.pdf

| File | Purpose |
| --- | --- |
| `calendar_pdf.py` | reads the JSON, computes the A3 layout, renders with WeasyPrint |
| `base.css` | what all themes share: fonts, reset, page, and the structure of the parts (see Themes) |
| `themes/<folder>/` | one theme: `theme.json`, a Jinja2 page template and a stylesheet (see Themes) |
| `fonts/` | Noto Sans CJK SC, SIL Open Font License 1.1 (see below) |

## Themes

A **theme** is a layout: a page template, a base stylesheet and the settings it offers (`settings_schema.json`),
written by the developer and committed to git. Users cannot create or edit themes. They pick one when they create a
design, and **it cannot be changed afterwards** (the design's parameters belong to that theme's schema). They can add
custom CSS on top, see below.

    themes/classic/
        theme.json          {"key": "classic", "name": "经典", "description": "...", "template": "template.html.j2", "css": "calendar.css", "settings": "settings_schema.json"}
        template.html.j2    cover, header photo, weekday bar, day grid, footer
        settings_schema.json  the design settings of this theme (colors, sizes, toggles): defaults, ranges and labels
        calendar.css        sizes are in mm / rem (1rem is about 3mm on A3); tunable values are CSS variables

- `key` (a-z, 0-9, `_`, `-`; stored in `calendar_design.theme`) is what counts, the folder name does not matter.
  Never change the key of a theme that is in use. `name` is shown in the UI.
- `template`, `css` and `settings` are paths relative to the theme folder; they must stay inside `themes/`. So two themes
  can share a template or a schema (`"template": "../classic/template.html.j2"`).
- Every stylesheet starts with `@import "../../base.css";`. `base.css` holds the fonts (so a theme never refers to
  `fonts/` itself), the reset, the `.page` and the structure of its parts: which element is positioned absolutely or is a
  grid (`.header`, `.verse`, `.title`, `.weekdays`, `.days`, `.day-cell`, `.footer`, ...). It has no colors and no
  design sizes, a theme adds those. Changing it changes every theme, so only ever add to it.
- **`classic` is frozen.** It is the original layout and all calendars made so far use it; changing it would change
  old years. Start a new theme (copy the folder, new key) for a new layout.
- Every theme gets the same data in its template (`year`, `weekdays`, `months`, `settings`, the layout numbers, ...), but
  its own parameters: `settings` holds what that theme's schema defines. Only add to the shared data, never rename or
  remove something, or existing themes break. Keep `.month-N` on each month page and `data-date` on each day cell in
  every theme, custom CSS relies on them. Other class names are not guaranteed to be the same between themes.
- The layout numbers that `calendar_pdf.py` derives from settings (`header_ratio`, `title_size`, `title_gap`, `show_footer`,
  `show_overlay`) are read from the settings with these names;
  a theme whose schema does not define them gets the defaults in `LAYOUT_DEFAULTS` (calendar_pdf.py).
- Themes: `classic` (frozen original) and `modern` (bright page, very large day number, lunar date and weekday for
  readability, a gradient behind the verse, and stars and a crescent moon beside the month title, drawn by the template
  as inline SVG in the design's `decor_color`; switch off with `show_decor`). `modern` has its own template (stacked
  weekday, `.red-day` and `.weeks-N` classes, the decoration) and its own schema.
- Geometry that is the same for every theme and computed in `calendar_pdf.py` (page size, header height, title box,
  weekday bar of 5rem, the rows of the day grid) is passed to the template, which positions the parts with it.
- PHP lists the themes (`CalendarThemes`) and validates the key; `calendar_pdf.py` finds the same `theme.json` files and
  falls back to `classic` (with a warning) for an unknown key.

## Designs

An appearance is a named **design** (`calendar_design`: name, theme, parameters, custom CSS). There can be several;
each calendar uses exactly one (`calendar.design_id`). A new calendar starts with the design of the closest
earlier calendar. A design is shared, so editing it changes every calendar that uses it.

Permissions:

- `calendar` (年历制作): make calendars and choose which design a calendar uses; preview any design.
- `calendar_design` (年历外观设计): additionally create, edit, rename, delete and restore designs.
  Roles are registered by `php artisan book:update-user-roles`; assign the role to users in the admin page.

Designs are soft-deleted and a design that a calendar still uses cannot be deleted.

## Design parameters

The `settings_schema.json` of a theme is the single source of truth for everything that can be tuned without touching
CSS: PHP validates input against it (`CalendarSettings`), the Vue "外观" tab is generated from it, and
`calendar_pdf.py` takes its defaults from it. Values missing from a design fall back to the schema defaults.

To add a setting: add an entry to the schema (`"css": true` makes it the CSS variable `--key-name`,
otherwise read it from `settings` in the script or template) and use `var(--key-name)` in the stylesheet of that theme.

## Monthly photo crop

The header shows each monthly photo "cover"-style (it fills the header, centred). On the 图片 tab a user can choose which part
shows: drag the photo and zoom (1 to 3 times). Per month slot `calendar.image_crops` (JSON) holds `{x, y, zoom}`: the centre as
fractions of the photo and the zoom. Months without an entry are centred, so old calendars render as before.

- `CalendarDataBuilder` passes it as `month["image_crop"]`. `calendar_pdf.py` (`crop_box`, `crop_to_header`) cuts the part out with Pillow
  into a temporary file and hands that to the template, so every theme and custom CSS get the cropped photo and need no change.
  The photo is turned upright first (EXIF orientation).
- Only centre and zoom are stored, never pixels: the box is computed from the design's `header_ratio` when the PDF is made, so
  changing the ratio (or the theme) keeps the chosen focus. `crop_box` and `cropGeometry.ts` (browser) use the same arithmetic;
  keep them identical.
- Uploading a new picture or deleting one removes the crop of that month.
- The editor (`CropSheet.vue`) draws overlay and verse from `headerPreview` of the calendar payload. The verse is an approximation
  of the theme stylesheet (colour, size and padding per theme are in `CalendarApiController::headerPreview`); custom CSS is not applied.
  A new theme with a different verse box should get its own numbers there.

## History versions

Calendars of earlier years (year < current year + 1) are read only: no image upload, CSV import, holiday
refresh, change of design, PDF generation or download. They can be looked at and previewed with any design.
Enforced in `CalendarApiController` (`lockedResponse`), not only in the UI.

## Custom CSS

Each design can carry free-form CSS in `calendar_design.styles` (the "高级：自定义 CSS" box on the design tab).
It is inlined into the document after the theme's stylesheet, so it overrides the base styles and the parameters.
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
user styles, which rank below the document's own stylesheet and could not override the theme stylesheet.

## Change records

Every change of a design (theme, parameters and custom CSS together) is stored as one row of
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

Python 3.10+ with the packages in `storage/scripts/requirements.txt` (`weasyprint` and `jinja2` for the calendar,
`python-pptx` and `click` for the PPT generator; Pillow comes with WeasyPrint). WeasyPrint needs Pango
(`libpango-1.0-0`, present in the ddev web image).

ddev:

    ddev exec 'python3 -m venv storage/app/pyvenv-ddev && storage/app/pyvenv-ddev/bin/pip install -r storage/scripts/requirements.txt'

then point `PYTHON_PATH` in `.env` to `/var/www/html/storage/app/pyvenv-ddev/bin/python3`.
The PPT generator uses the same `PYTHON_PATH`, so both scripts share one virtual environment.

## Images

Read from `storage/app/public/images/calendar/<year>/`: `cover`, `01` to `12`, `overlay` (PNG), `footer`
(`.jpg` or `.png`). The calendar page uploads them there; missing months render as a grey placeholder.
