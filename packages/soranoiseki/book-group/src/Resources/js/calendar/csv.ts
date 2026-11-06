// Small RFC 4180 style CSV parser, only used for the upload preview (the server does the real import).

export interface ParsedCsv {
    headers: string[];
    rows: string[][];
}

/** Guess the delimiter from the header line: Excel in a German locale saves with ";". */
function detectDelimiter(headerLine: string): string {
    const candidates = [",", ";", "\t"];
    let best = ",";
    let bestCount = -1;
    for (const c of candidates) {
        const count = headerLine.split(c).length;
        if (count > bestCount) {
            best = c;
            bestCount = count;
        }
    }
    return best;
}

export function parseCsv(input: string): ParsedCsv {
    // Strip UTF-8 BOM written by Excel
    const text = input.replace(/^﻿/, "");
    const delimiter = detectDelimiter(text.split(/\r?\n/, 1)[0] ?? "");

    const rows: string[][] = [];
    let row: string[] = [];
    let field = "";
    let quoted = false;

    for (let i = 0; i < text.length; i++) {
        const ch = text[i];

        if (quoted) {
            if (ch === '"' && text[i + 1] === '"') {
                field += '"';
                i++;
            } else if (ch === '"') {
                quoted = false;
            } else {
                field += ch;
            }
            continue;
        }

        if (ch === '"') {
            quoted = true;
        } else if (ch === delimiter) {
            row.push(field);
            field = "";
        } else if (ch === "\n" || ch === "\r") {
            if (ch === "\r" && text[i + 1] === "\n") i++;
            row.push(field);
            rows.push(row);
            row = [];
            field = "";
        } else {
            field += ch;
        }
    }
    if (field !== "" || row.length) {
        row.push(field);
        rows.push(row);
    }

    // Drop completely empty lines
    const filtered = rows.filter((r) => r.some((c) => c.trim() !== ""));
    const [headers = [], ...body] = filtered;

    return {
        headers: headers.map((h) => h.trim().toLowerCase()),
        rows: body,
    };
}

/** CSV text with BOM so that Excel opens UTF-8 (Chinese) correctly. */
export function buildCsv(headers: string[], rows: string[][]): string {
    const esc = (v: string) => (/[",\n\r;]/.test(v) ? `"${v.replace(/"/g, '""')}"` : v);
    return "﻿" + [headers, ...rows].map((r) => r.map(esc).join(",")).join("\r\n") + "\r\n";
}

export function downloadText(filename: string, content: string): void {
    const url = URL.createObjectURL(new Blob([content], { type: "text/csv;charset=utf-8" }));
    const a = document.createElement("a");
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    a.remove();
    URL.revokeObjectURL(url);
}
