export type TabId = "images" | "events" | "bible";

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
    message?: string;
}
