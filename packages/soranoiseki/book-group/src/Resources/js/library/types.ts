export type TabId = "scan" | "books" | "loans" | "members";

export interface LoanInfo {
    id: number;
    copyId: number;
    member: { id: number; name: string };
    borrowedAt: string;
    dueAt: string;
    overdue: boolean;
    renewals: number;
}

export interface CopyInfo {
    id: number;
    number: number;
    code: string;
    status: "active" | "lost" | "retired";
    loan: LoanInfo | null;
}

export interface BookInfo {
    id: number;
    code: string;
    isbn: string | null;
    title: string;
    subtitle: string | null;
    author: string | null;
    publisher: string | null;
    category: string | null;
    coverUrl: string | null;
    copies: CopyInfo[];
}

export interface LoanRow extends LoanInfo {
    copyCode: string;
    book: { id: number; title: string; author: string | null };
}

export interface MemberInfo {
    id: number;
    name: string;
    email: string | null;
    phone: string | null;
    activeLoans: number;
}

export interface NewMember {
    first_name: string;
    last_name: string;
    email: string;
    phone: string;
}

export interface Summary {
    canManage: boolean;
    loanDays: number;
    books: number;
    copies: number;
    loans: number;
    overdue: number;
}

/** What a scan or manual lookup produced */
export interface ScanResult {
    code: string;
    found: boolean;
    book: BookInfo | null;
    /** The copy whose label was scanned (when the code was a copy label) */
    copyId: number | null;
    /** The code is a valid ISBN (used to offer "add this book") */
    isbn: string | null;
}

export interface BookDraft {
    isbn: string;
    code: string;
    title: string;
    subtitle: string;
    author: string;
    publisher: string;
    category: string;
    cover_url: string;
    quantity: number;
}

export interface ActivityEntry {
    id: number;
    kind: "borrow" | "return" | "renew";
    text: string;
    at: string;
}

/** The fields of a book that can be edited */
export interface BookEdit {
    code: string;
    title: string;
    subtitle: string;
    isbn: string;
    author: string;
    publisher: string;
    category: string;
}
