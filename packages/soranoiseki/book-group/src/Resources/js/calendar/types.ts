export type TabId = "images" | "events" | "bible" | "design";

export interface CalendarEvent {
    id: number;
    date: string;
    name: string;
    type: string | null;
}

export interface CalendarDetail {
    id: number;
    month: number;
    bible_text: string;
    bible_source: string;
}

export interface ImageInfo {
    exists: boolean;
    url: string | null;
    size: number | null;
}

/** cover | 01..12 | overlay | footer */
export type ImageSlots = Record<string, ImageInfo>;

export interface RevisionRef {
    id: number;
    created_at: string;
    user_name: string | null;
}

/** One recorded change of the design. The CSS itself stays on the server. */
export interface HistoryEntry {
    id: number;
    created_at: string;
    user_name: string | null;
    /** save = saved by a user, restore = went back to an earlier record, create = the design was created, initial = the state before the first record */
    action: "save" | "restore" | "initial" | "create";
    restored_from: RevisionRef | null;
    /** Differences to the design that is current now */
    settings_changed: number;
    styles_changed: boolean;
}

export interface DesignSummary {
    id: number;
    name: string;
    /** Years of the calendars that use this design */
    used_by: number[];
}

/** The design that is being viewed or edited, with its latest change records */
export interface DesignDetail {
    id: number;
    name: string;
    settings: SettingsMap;
    styles: string;
    history: HistoryEntry[];
    /** Number of all recorded changes; only the latest ones are listed */
    historyTotal: number;
}

export interface DesignPayload {
    success: boolean;
    design: DesignDetail;
    designs: DesignSummary[];
    message?: string;
}

export interface CalendarPayload {
    success: boolean;
    year: number;
    defaultYear: number;
    calendar: { id: number; year: number } | null;
    versions: number[];
    events: CalendarEvent[];
    details: CalendarDetail[];
    images: ImageSlots;
    holidayCount: number;
    /** False for calendars of earlier years: they can be looked at and previewed, but not changed */
    editable: boolean;
    /** The design this calendar renders with */
    design_id: number | null;
    designs: DesignSummary[];
    /** Whether the user may create, edit, rename and delete designs */
    canDesign: boolean;
    settingsSchema: SettingsSchema;
    message?: string;
}
