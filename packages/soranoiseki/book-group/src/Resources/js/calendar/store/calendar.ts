import { defineStore } from "pinia";
import axios from "axios";
import type { CalendarDetail, CalendarEvent, CalendarPayload, DesignPayload, DesignSummary, HistoryEntry, ImageSlots, SettingsMap, SettingsSchema, TabId } from "../types";
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
    /** False for calendars of earlier years (read only) */
    editable: boolean;
    /** May create, edit, rename and delete designs */
    canDesign: boolean;
    designs: DesignSummary[];
    /** The design this calendar renders with */
    calendarDesignId: number | null;
    /** The design shown in the design tab (to preview and, for designers, to edit) */
    designId: number | null;
    designName: string;
    isLoadingDesign: boolean;
    /** Saved settings of that design (resolved with defaults) and what the form currently shows */
    settings: SettingsMap;
    draft: SettingsMap;
    settingsSchema: SettingsSchema;
    /** Custom CSS: saved version and what the editor currently shows */
    styles: string;
    draftStyles: string;
    history: HistoryEntry[];
    historyTotal: number;

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
            editable: true,
            canDesign: false,
            designs: [],
            calendarDesignId: null,
            designId: null,
            designName: "",
            isLoadingDesign: false,
            settings: {},
            draft: {},
            settingsSchema: { groups: [], settings: [] },
            styles: "",
            draftStyles: "",
            history: [],
            historyTotal: 0,

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
        /** True when the parameters or the custom CSS differ from what is saved (they are saved together) */
        designDirty: (state): boolean =>
            JSON.stringify(state.draft) !== JSON.stringify(state.settings) || state.draftStyles !== state.styles,

        hasCalendar: (state): boolean => state.calendarId !== null,

        /** Current version = the default (upcoming) year. */
        isCurrent: (state): boolean => state.year === state.defaultYear,

        /** Earlier years: read only */
        isHistory: (state): boolean => !state.editable,

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
            this.editable = data.editable ?? true;
            this.canDesign = !!data.canDesign;
            this.designs = data.designs ?? [];
            this.calendarDesignId = data.design_id ?? null;
            this.settingsSchema = data.settingsSchema ?? this.settingsSchema;
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
                await this.loadDesign(this.calendarDesignId);
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
                await this.loadDesign(this.calendarDesignId);
                this.successMessage = data.message ?? "已创建。";
            } catch (e) {
                this.errors = await extractErrors(e, FALLBACK_ERROR);
            } finally {
                this.isLoading = false;
            }
        },

        /** Show a design in the design tab (the form, its change records and the preview all follow it). */
        applyDesignPayload(data: DesignPayload): void {
            this.designId = data.design.id;
            this.designName = data.design.name;
            this.settings = data.design.settings ?? {};
            this.draft = { ...this.settings };
            this.styles = data.design.styles ?? "";
            this.draftStyles = this.styles;
            this.history = data.design.history ?? [];
            this.historyTotal = data.design.historyTotal ?? 0;
            this.designs = data.designs ?? this.designs;
        },

        async loadDesign(id: number | null): Promise<void> {
            if (!id) return;
            this.isLoadingDesign = true;
            try {
                const { data } = await axios.get<DesignPayload>(`/api/calendar/designs/${id}`);
                this.applyDesignPayload(data);
            } catch (e) {
                this.errors = await extractErrors(e, "无法加载外观。");
            } finally {
                this.isLoadingDesign = false;
            }
        },

        /** Saves the parameters and the custom CSS of the shown design together; the server records the change. */
        async saveDesign(): Promise<void> {
            if (!this.designId) return;
            this.clearMessages();
            this.isImporting = true;
            try {
                const { data } = await axios.put<DesignPayload>(`/api/calendar/designs/${this.designId}/content`, {
                    settings: this.draft,
                    styles: this.draftStyles,
                });
                this.applyDesignPayload(data);
                this.successMessage = data.message ?? "外观已保存。";
            } catch (e) {
                this.errors = await extractErrors(e, "保存外观失败。");
            } finally {
                this.isImporting = false;
            }
        },

        /** Go back to an earlier recorded state. The server applies it and records the restore as a change of its own. */
        async restoreDesign(revisionId: number): Promise<void> {
            if (!this.designId) return;
            this.clearMessages();
            this.isImporting = true;
            try {
                const { data } = await axios.post<DesignPayload>(
                    `/api/calendar/designs/${this.designId}/restore/${revisionId}`,
                );
                this.applyDesignPayload(data);
                this.successMessage = data.message ?? "已恢复。";
            } catch (e) {
                this.errors = await extractErrors(e, "恢复失败。");
            } finally {
                this.isImporting = false;
            }
        },

        /** New design as a copy of the shown one (or of `copyFrom`). Returns true on success. */
        async createDesign(name: string, copyFrom: number | null = this.designId): Promise<boolean> {
            this.clearMessages();
            try {
                const { data } = await axios.post<DesignPayload>("/api/calendar/designs", { name, copy_from: copyFrom });
                this.applyDesignPayload(data);
                this.successMessage = data.message ?? "已创建。";
                return true;
            } catch (e) {
                this.errors = await extractErrors(e, "创建外观失败。");
                return false;
            }
        },

        async renameDesign(name: string): Promise<boolean> {
            if (!this.designId) return false;
            this.clearMessages();
            try {
                const { data } = await axios.patch<DesignPayload>(`/api/calendar/designs/${this.designId}`, { name });
                this.designName = data.design.name;
                this.designs = data.designs;
                this.successMessage = data.message ?? "已重命名。";
                return true;
            } catch (e) {
                this.errors = await extractErrors(e, "重命名失败。");
                return false;
            }
        },

        async deleteDesign(): Promise<void> {
            if (!this.designId) return;
            this.clearMessages();
            try {
                const { data } = await axios.delete<{ designs: DesignSummary[]; message?: string }>(`/api/calendar/designs/${this.designId}`);
                this.designs = data.designs;
                this.successMessage = data.message ?? "已删除。";
                await this.loadDesign(this.calendarDesignId ?? this.designs[0]?.id ?? null);
            } catch (e) {
                this.errors = await extractErrors(e, "删除外观失败。");
            }
        },

        /** Choose which design this calendar renders with. */
        async useDesign(id: number): Promise<void> {
            if (!this.calendarId) return;
            this.clearMessages();
            this.isImporting = true;
            try {
                const { data } = await axios.put<CalendarPayload>(`/api/calendar/${this.calendarId}/use-design`, { design_id: id });
                this.applyPayload(data);
                this.successMessage = data.message ?? "已更换外观。";
            } catch (e) {
                this.errors = await extractErrors(e, "更换外观失败。");
            } finally {
                this.isImporting = false;
            }
        },

        resetDraft(key?: string): void {
            const defaults: SettingsMap = {};
            for (const item of this.settingsSchema.settings) defaults[item.key] = item.default;
            this.draft = key ? { ...this.draft, [key]: defaults[key] } : defaults;
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
