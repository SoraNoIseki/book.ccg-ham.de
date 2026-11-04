import { defineStore } from "pinia";
import axios, { AxiosError, AxiosResponse } from "axios";
import type { AllContent, PowerpointInit, TabId, ApiSuccess } from "../types";
import { getQueryParam, setQueryParam } from "../url";

// All comments in English as requested.

export interface PowerpointEndpoints {
    show: string;
    save: string;
    saveDownload: string;
    download: string;
    songs: string;
    song: string;
    taskText: string;
}

export interface PowerpointState {
    // Data from API
    date: string;
    defaultDate: string;
    nextWeek: string;
    versions: string[];
    saveEnabled: boolean;
    content: AllContent;

    // UI state
    selectedDate: string;
    activeTab: TabId;
    isLoading: boolean;
    isSubmitting: boolean;

    // Messages
    errors: string[];
    infoMessage: string | null;
    successMessage: string | null;

    // Config
    endpoints: PowerpointEndpoints;
}

const REQUIRED_FIELDS: Array<[TabId, string]> = [
    ["pray", "item1"],
    ["pray", "item2"],
    ["pray", "item3"],
    ["preach", "item0"],
    ["preach", "item1"],
    ["preach", "item2"],
    ["preach", "item3"],
    ["scripture", "item1"],
    ["scripture", "item2"],
    ["scripture", "item3"],
    ["song", "item1"],
    ["song", "item2"],
    ["song", "item3"],
    ["song", "item4"],
    ["worker", "item1"],
    ["worker", "item2"],
];

function setupAxiosCsrf(): void {
    const token = document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute("content");
    axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";
    if (token) axios.defaults.headers.common["X-CSRF-TOKEN"] = token;
}

function extractErrors(e: unknown): string[] {
    const err = e as AxiosError<any>;
    const resp = err?.response;

    if (resp?.data?.errors) {
        const all: string[] = [];
        for (const k of Object.keys(resp.data.errors)) {
            all.push(...resp.data.errors[k]);
        }
        return all;
    }

    if (resp?.data?.message) return [String(resp.data.message)];
    return ["An error occurred. Please do not refresh the page."];
}

function downloadBlob(res: AxiosResponse<Blob>, fallbackName: string): void {
    const blob = new Blob([res.data], {
        type:
            (res.headers["content-type"] as string) ||
            "application/vnd.openxmlformats-officedocument.presentationml.presentation",
    });

    const cd = res.headers["content-disposition"] as string | undefined;
    let filename = fallbackName;
    if (cd) {
        const m = cd.match(/filename\*=UTF-8''([^;]+)|filename="?([^"]+)"?/i);
        filename = decodeURIComponent(
            (m?.[1] || m?.[2] || fallbackName) as string,
        );
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

export const usePowerpointStore = defineStore("powerpoint", {
    state: (): PowerpointState => {
        setupAxiosCsrf();

        const tabFromUrl = (getQueryParam("tab") as TabId | null) || "pray";
        const vFromUrl = getQueryParam("v") || "";

        return {
            date: "",
            defaultDate: "",
            nextWeek: "",
            versions: [],
            saveEnabled: false,
            content: {},

            selectedDate: vFromUrl,
            activeTab: tabFromUrl,
            isLoading: false,
            isSubmitting: false,

            errors: [],
            infoMessage: null,
            successMessage: null,

            endpoints: {
                show: "/api/powerpoint",
                save: "/api/powerpoint/save",
                saveDownload: "/api/powerpoint/save-download",
                download: "/api/powerpoint/download",
                songs: "/api/powerpoint/songs",
                song: "/api/powerpoint/song",
                taskText: "/api/planer/plans/text",
            },
        };
    },

    getters: {
        /**
         * Options for the version selector.
         * Backend `versions` excludes the current date, so merge it back in.
         * Sorted descending (newest first), unique.
         */
        versionOptions(state): Array<{ value: string; label: string }> {
            const dates = new Set<string>(state.versions);
            if (state.date) dates.add(state.date);
            if (state.defaultDate) dates.add(state.defaultDate);

            return [...dates]
                .sort()
                .reverse()
                .map((d) => ({
                    value: d,
                    label: d === state.defaultDate ? `${d}（本周）` : d,
                }));
        },

        /**
         * All required textareas must be filled before the PPT can be generated.
         * Report is optional.
         */
        canDownload(state): boolean {
            return REQUIRED_FIELDS.every(
                ([tab, item]) => (state.content[tab]?.[item] ?? "").trim() !== "",
            );
        },
    },

    actions: {
        /**
         * Initialize store from API.
         * Reads ?v= from URL if not provided.
         */
        async fetchInit(v?: string): Promise<void> {
            this.errors = [];
            this.infoMessage = null;
            this.successMessage = null;

            const version = v ?? getQueryParam("v") ?? "";
            this.isLoading = true;

            try {
                const { data } = await axios.get<PowerpointInit>(
                    this.endpoints.show,
                    { params: { v: version } },
                );

                this.date = data.date;
                this.defaultDate = data.defaultDate;
                this.nextWeek = data.nextWeek;
                this.versions = data.versions ?? [];
                this.saveEnabled = !!data.saveEnabled;
                this.content = data.content ?? {};
                this.infoMessage = data.infoMessage ?? null;

                // Keep selectedDate in sync for the selector UI
                this.selectedDate = this.date;

                // Persist version and tab in URL (ensures refresh keeps them)
                if (this.date) setQueryParam("v", this.date);
                setQueryParam("tab", this.activeTab);
            } catch (e) {
                this.errors = extractErrors(e);
                // Roll the selector back to the version that is actually shown
                this.selectedDate = this.date;
            } finally {
                this.isLoading = false;
            }
        },

        /**
         * Set a single content item, creating the tab map if needed.
         */
        setField(tab: TabId, item: string, value: string): void {
            this.content[tab] = { ...(this.content[tab] ?? {}), [item]: value };
        },

        /**
         * Replace all items of a tab (used by the report list).
         */
        setTab(tab: TabId, items: string[]): void {
            const map: Record<string, string> = {};
            items.forEach((v, i) => (map[`item${i + 1}`] = v));
            this.content[tab] = map;
        },

        /**
         * Switch active tab and persist it in URL.
         */
        setActiveTab(tab: TabId): void {
            this.activeTab = tab;
            setQueryParam("tab", tab);
        },

        /**
         * Load another version and persist it in URL.
         */
        async loadVersion(date: string): Promise<void> {
            if (!date || this.isLoading) return;
            // fetchInit persists the version in the URL after a successful load
            await this.fetchInit(date);
        },

        /**
         * Build request payload matching Laravel request structure.
         */
        buildPayload(): Record<string, any> {
            return {
                date: this.date,
                pray: this.content.pray ?? {},
                scripture: this.content.scripture ?? {},
                preach: this.content.preach ?? {},
                song: this.content.song ?? {},
                report: this.content.report ?? {},
                worker: this.content.worker ?? {},
            };
        },

        /**
         * Save only (no download).
         */
        async save(): Promise<void> {
            this.isSubmitting = true;
            this.errors = [];
            this.successMessage = null;

            try {
                const { data } = await axios.post<ApiSuccess>(
                    this.endpoints.save,
                    this.buildPayload(),
                );
                this.successMessage = data.message ?? "Saved.";
            } catch (e) {
                this.errors = extractErrors(e);
            } finally {
                this.isSubmitting = false;
            }
        },

        /**
         * Save and download PPTX.
         */
        async saveAndDownload(): Promise<void> {
            this.isSubmitting = true;
            this.errors = [];
            this.successMessage = null;

            try {
                const res = await axios.post<Blob>(
                    this.endpoints.saveDownload,
                    this.buildPayload(),
                    {
                        responseType: "blob",
                    },
                );
                downloadBlob(res, `${this.date}.pptx`);
                this.successMessage = "Saved and downloading...";
            } catch (e) {
                this.errors = extractErrors(e);
            } finally {
                this.isSubmitting = false;
            }
        },

        /**
         * Download only.
         */
        async download(): Promise<void> {
            this.isSubmitting = true;
            this.errors = [];
            this.successMessage = null;

            try {
                const res = await axios.post<Blob>(
                    this.endpoints.download,
                    { date: this.date },
                    { responseType: "blob" },
                );
                downloadBlob(res, `${this.date}.pptx`);
            } catch (e) {
                this.errors = extractErrors(e);
            } finally {
                this.isSubmitting = false;
            }
        },
    },
});
