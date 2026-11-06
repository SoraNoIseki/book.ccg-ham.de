import axios, { AxiosError, AxiosResponse } from "axios";

// Helpers shared by the PPT and calendar pages.

export function setupAxiosCsrf(): void {
    const token = document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute("content");
    axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";
    if (token) axios.defaults.headers.common["X-CSRF-TOKEN"] = token;
}

/**
 * Turn a failed request into a list of readable messages.
 * Requests with responseType "blob" deliver JSON error bodies as a Blob, so decode it first.
 */
export async function extractErrors(e: unknown, fallback: string): Promise<string[]> {
    const err = e as AxiosError<any>;
    let body = err?.response?.data;

    if (body instanceof Blob) {
        try {
            body = JSON.parse(await body.text());
        } catch {
            body = undefined;
        }
    }

    if (body?.errors) {
        const all: string[] = [];
        for (const k of Object.keys(body.errors)) {
            all.push(...body.errors[k]);
        }
        return all;
    }

    if (body?.message) return [String(body.message)];
    return [fallback];
}

/** Save a blob response as a file, honouring Content-Disposition when present. */
export function downloadBlob(res: AxiosResponse<Blob>, fallbackName: string): void {
    const blob = new Blob([res.data], {
        type: (res.headers["content-type"] as string) || "application/octet-stream",
    });

    const cd = res.headers["content-disposition"] as string | undefined;
    let filename = fallbackName;
    if (cd) {
        const m = cd.match(/filename\*=UTF-8''([^;]+)|filename="?([^"]+)"?/i);
        filename = decodeURIComponent((m?.[1] || m?.[2] || fallbackName) as string);
    }

    const url = URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    a.remove();
    URL.revokeObjectURL(url);
}
