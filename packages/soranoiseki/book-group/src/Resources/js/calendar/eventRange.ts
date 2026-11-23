/**
 * Dates that can show up in the calendar of a year: the year itself plus the days that pad the weeks of
 * January (end of the previous December) and December (start of the next January). Weeks run Monday-Sunday.
 * Same rule as CalendarApiController::eventRange().
 */
const iso = (d: Date): string =>
    `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}-${String(d.getDate()).padStart(2, "0")}`;

export function eventRange(year: number): { min: string; max: string } {
    const first = new Date(year, 0, 1);
    const last = new Date(year, 11, 31);
    const min = new Date(year, 0, 1 - ((first.getDay() + 6) % 7));
    const max = new Date(year, 11, 31 + ((7 - last.getDay()) % 7));
    return { min: iso(min), max: iso(max) };
}

export function inEventRange(year: number, date: string): boolean {
    const { min, max } = eventRange(year);
    return date >= min && date <= max;
}
