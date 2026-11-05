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
| `calendar.css` | styles; sizes are in mm / rem (1rem is about 3mm on A3) |
| `fonts/` | Noto Sans CJK SC (Light, Regular, Medium), SIL Open Font License 1.1 |

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
