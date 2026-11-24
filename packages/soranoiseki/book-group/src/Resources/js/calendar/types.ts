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
    /** Small and medium copies for the page (the big print file is only for the PDF) */
    thumb?: string | null;
    preview?: string | null;
}

/** Part of a monthly photo that shows in the header (centre as fractions of the photo, zoom 1..3) */
export interface ImageCrop {
    x: number;
    y: number;
    zoom: number;
}

/** What the crop editor needs to draw the header like the PDF (the verse is only an approximation of the theme) */
export interface HeaderPreview {
    /** height / width of the header */
    ratio: number;
    showOverlay: boolean;
    /** 1 rem = remRatio x the page width */
    remRatio: number;
    verse: {
        color: string;
        size: number;
        sourceSize: number;
        /** top, right, bottom, left in rem */
        padding: [number, number, number, number];
        lineHeight: number;
        sourceGap: number;
        gradient: boolean;
    };
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

/** A theme = template + stylesheet, provided as files by the developer */
export interface ThemeInfo {
    key: string;
    name: string;
    description: string;
}

export interface DesignSummary {
    id: number;
    name: string;
    /** Display name of its theme */
    theme: string;
    /** Years of the calendars that use this design */
    used_by: number[];
}

/** The design that is being viewed or edited, with its latest change records */
export interface DesignDetail {
    id: number;
    name: string;
    /** Key and display name of its theme (fixed when the design is created) */
    theme: string;
    theme_name: string;
    /** The parameters this design's theme offers */
    settingsSchema: SettingsSchema;
    /** Saved parameter values */
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
    /** Chosen crop per month slot ("01".."12"); months without an entry are centred */
    crops: Record<string, ImageCrop>;
    headerPreview: HeaderPreview | null;
    holidayCount: number;
    /** False for calendars of earlier years: they can be looked at and previewed, but not changed */
    editable: boolean;
    /** The design this calendar renders with */
    design_id: number | null;
    designs: DesignSummary[];
    themes: ThemeInfo[];
    /** Whether the user may create, edit, rename and delete designs */
    canDesign: boolean;
    message?: string;
}
