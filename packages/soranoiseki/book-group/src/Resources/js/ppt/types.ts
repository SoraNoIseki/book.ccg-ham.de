// All comments in English as requested.

export type TabId =
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

export interface PowerpointInit {
    date: string;
    defaultDate: string;
    nextWeek: string;
    versions: string[];
    saveEnabled: boolean;
    content: AllContent;
    infoMessage?: string | null;
}

export interface ApiSuccess {
    success: boolean;
    message?: string;
}
