export interface SongRow {
    id: string;
    name: string;
    band: string;
    album: string;
    group_id: string;
    checked: boolean;
    corrected: boolean;
    count: number;
}

export interface UnsavedRow {
    id: number;
    name: string;
    band: string | null;
    album: string | null;
    text: string;
}

export interface SongDetail {
    id: string;
    name: string;
    band: string;
    album: string;
    group_id: string;
    checked: boolean;
    corrected: boolean;
    text: string;
}

export interface SongContentRow {
    id: number;
    text: string;
}
