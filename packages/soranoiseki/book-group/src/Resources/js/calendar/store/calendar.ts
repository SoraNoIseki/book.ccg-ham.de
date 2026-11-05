import { defineStore } from "pinia";
import axios from "axios";
import type { CalendarDetail, CalendarEvent, CalendarPayload, ImageSlots, TabId } from "../types";
import { setupAxiosCsrf, extractErrors } from "../../shared/http";

const FALLBACK_ERROR = "有错误发生，请稍后重试。";

export const MONTH_SLOTS = ["01", "02", "03", "04", "05", "06", "07", "08", "09", "10", "11", "12"];
/** Slots required for a complete PDF (overlay and footer are optional). */
export const REQUIRED_SLOTS = ["cover", ...MONTH_SLOTS];

function getQueryParam(key: string): string | null {
    return new URLSearchParams(window.location.search).get(key);
}

function setQueryParam(key: string, value: string): void {
    const url = new URL(window.location.href);
    url.searchParams.set(key, value);
    window.history.replaceState({}, "", url.toString());
}

export interface CalendarState {
    year: number;
    defaultYear: number;
    calendarId: number | null;
    versions: number[];
    events: CalendarEvent[];
    details: CalendarDetail[];
    images: ImageSlots;
    holidayCount: number;

    activeTab: TabId;
    selectedYear: string;
    isLoading: boolean;
    /** Slots currently uploading / deleting */
    busySlots: Record<string, boolean>;
    isImporting: boolean;
    isGenerating: boolean;

    errors: string[];
    successMessage: string | null;
}

export const useCalendarStore = defineStore("calendar", {
    state: (): CalendarState => {
        setupAxiosCsrf();

        return {
            year: 0,
            defaultYear: 0,
            calendarId: null,
            versions: [],
            events: [],
            details: [],
            images: {},
            holidayCount: 0,

            activeTab: (getQueryParam("tab") as TabId | null) || "images",
            selectedYear: "",
            isLoading: false,
            busySlots: {},
            isImporting: false,
            isGenerating: false,

            errors: [],
            successMessage: null,
        };
    },

    getters: {
        hasCalendar: (state): boolean => state.calendarId !== null,

        /** Current version = the default (upcoming) year; everything else is history. */
        isCurrent: (state): boolean => state.year === state.defaultYear,

        versionOptions(state): Array<{ value: string; label: string }> {
            const years = new Set<number>(state.versions);
            if (state.defaultYear) years.add(state.defaultYear);
            if (state.year) years.add(state.year);

            return [...years]
                .sort((a, b) => b - a)
                .map((y) => ({
                    value: String(y),
                    label: y === state.defaultYear ? `${y} 年（当前）` : `${y} 年`,
                }));
        },

        requiredImagesDone(state): number {
            return REQUIRED_SLOTS.filter((s) => state.images[s]?.exists).length;
        },

        monthDetailsDone: (state): number => new Set(state.details.filter((d) => d.bible_text).map((d) => d.month)).size,
    },

    actions: {
        applyPayload(data: CalendarPayload): void {
            this.year = data.year;
            this.defaultYear = data.defaultYear;
            this.calendarId = data.calendar?.id ?? null;
            this.versions = data.versions ?? [];
            this.events = data.events ?? [];
            this.details = data.details ?? [];
            this.images = data.images ?? {};
            this.holidayCount = data.holidayCount ?? 0;
            this.selectedYear = String(data.year);
            setQueryParam("year", String(data.year));
        },

        clearMessages(): void {
            this.errors = [];
            this.successMessage = null;
        },

        setActiveTab(tab: TabId): void {
            this.activeTab = tab;
            setQueryParam("tab", tab);
        },

        /** Load one calendar version (defaults to ?year= from the URL, then to the server default). */
        async fetch(year?: number | string): Promise<void> {
            this.clearMessages();
            this.isLoading = true;
            try {
                const wanted = year ?? getQueryParam("year") ?? "";
                const { data } = await axios.get<CalendarPayload>("/api/calendar", { params: { year: wanted } });
                this.applyPayload(data);
            } catch (e) {
                this.errors = await extractErrors(e, FALLBACK_ERROR);
                this.selectedYear = String(this.year || "");
            } finally {
                this.isLoading = false;
            }
        },

        async create(): Promise<void> {
            this.clearMessages();
            this.isLoading = true;
            try {
                const { data } = await axios.post<CalendarPayload>("/api/calendar", { year: this.year });
                this.applyPayload(data);
                this.successMessage = data.message ?? "已创建。";
            } catch (e) {
                this.errors = await extractErrors(e, FALLBACK_ERROR);
            } finally {
                this.isLoading = false;
            }
        },

        /** Retry fetching the public holidays (the holiday api can be down when a calendar is created). */
        async refreshHolidays(): Promise<void> {
            if (!this.calendarId) return;
            this.clearMessages();
            this.isLoading = true;
            try {
                const { data } = await axios.post<CalendarPayload>(`/api/calendar/${this.calendarId}/holidays`);
                this.applyPayload(data);
                this.successMessage = data.message ?? "节假日已更新。";
            } catch (e) {
                this.errors = await extractErrors(e, FALLBACK_ERROR);
            } finally {
                this.isLoading = false;
            }
        },

        /** `batch` keeps earlier messages and prefixes errors with the file name (bulk upload). */
        async uploadImage(slot: string, file: File, batch = false): Promise<boolean> {
            if (!this.calendarId) return false;
            if (!batch) this.clearMessages();
            this.busySlots[slot] = true;
            try {
                const form = new FormData();
                form.append("file", file);
                const { data } = await axios.post(`/api/calendar/${this.calendarId}/images/${slot}`, form);
                this.images = data.images;
                return true;
            } catch (e) {
                const messages = await extractErrors(e, "图片上传失败。");
                this.errors = batch
                    ? [...this.errors, ...messages.map((m) => `${file.name}：${m}`)]
                    : messages;
                return false;
            } finally {
                this.busySlots[slot] = false;
            }
        },

        async deleteImage(slot: string): Promise<void> {
            if (!this.calendarId) return;
            this.errors = [];
            this.successMessage = null;
            this.busySlots[slot] = true;
            try {
                const { data } = await axios.delete(`/api/calendar/${this.calendarId}/images/${slot}`);
                this.images = data.images;
            } catch (e) {
                this.errors = await extractErrors(e, "删除失败。");
            } finally {
                this.busySlots[slot] = false;
            }
        },

        /** kind: "events" (大事历) or "bible-texts" (每月经文) */
        async importCsv(kind: "events" | "bible-texts", file: File): Promise<boolean> {
            if (!this.calendarId) return false;
            this.clearMessages();
            this.isImporting = true;
            try {
                const form = new FormData();
                form.append("file", file);
                const { data } = await axios.post<CalendarPayload>(`/api/calendar/${this.calendarId}/${kind}`, form);
                this.applyPayload(data);
                this.successMessage = data.message ?? "已导入。";
                return true;
            } catch (e) {
                this.errors = await extractErrors(e, "导入失败，请检查 CSV 文件。");
                return false;
            } finally {
                this.isImporting = false;
            }
        },

        /**
         * Generate the PDF on the server. "preview" opens it in a new tab, "download" saves it.
         * The tab is opened synchronously (before the slow request) so that popup blockers allow it.
         */
        async generatePdf(mode: "preview" | "download"): Promise<void> {
            if (!this.calendarId || this.isGenerating) return;
            this.clearMessages();
            this.isGenerating = true;
            const tab = mode === "preview" ? window.open("", "_blank") : null;
            if (tab) tab.document.title = "正在生成日历...";

            try {
                const res = await axios.post<Blob>(`/api/calendar/${this.calendarId}/pdf`, null, { responseType: "blob" });
                const blob = new Blob([res.data], { type: "application/pdf" });
                const url = URL.createObjectURL(blob);

                if (tab) {
                    tab.location.href = url;
                } else {
                    const a = document.createElement("a");
                    a.href = url;
                    a.download = `CCGH_Calendar_${this.year}.pdf`;
                    document.body.appendChild(a);
                    a.click();
                    a.remove();
                }
                // Keep the URL alive long enough for the new tab to load it
                setTimeout(() => URL.revokeObjectURL(url), 60_000);
                this.successMessage = mode === "preview" ? "日历已生成。" : "日历已生成，正在下载...";
            } catch (e) {
                tab?.close();
                this.errors = await extractErrors(e, "生成 PDF 失败。");
            } finally {
                this.isGenerating = false;
            }
        },
    },
});
