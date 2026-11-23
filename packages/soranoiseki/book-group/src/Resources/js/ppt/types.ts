// All comments in English as requested.

export type TabId =
    | "basic"
    | "pray"
    | "scripture"
    | "preach"
    | "song"
    | "report"
    | "worker";

// Backend splitContentText() returns item keys like item1, item2...
export type ContentMap = Record<string, string>;

// content = { pray: { item1: '...', ... }, scripture: {...}, ... }
export type AllContent = Partial<Record<TabId, ContentMap>>;

export interface PptSettings {
    song_v?: "1" | "2";
    include_video?: boolean;
    force_holy_dinner?: boolean;
}

export interface PowerpointInit {
    date: string;
    defaultDate: string;
    nextWeek: string;
    versions: string[];
    saveEnabled: boolean;
    content: AllContent;
    // Saved basic settings of this date; null when never saved (defaults apply)
    settings?: PptSettings | null;
    infoMessage?: string | null;
}

export interface ApiSuccess {
    success: boolean;
    message?: string;
}
