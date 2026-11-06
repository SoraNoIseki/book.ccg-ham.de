import { defineStore } from "pinia";
import axios from "axios";
import type {
    ActivityEntry, BookDraft, BookEdit, BookInfo, LoanRow, MemberInfo, NewMember, ScanResult, Summary, TabId,
} from "../types";
import { setupAxiosCsrf, extractErrors } from "../../shared/http";

const FALLBACK_ERROR = "有错误发生，请稍后重试。";

let activitySeq = 0;

export type ScanPurpose = "lookup" | "isbn" | "add";

export interface LibraryState {
    summary: Summary | null;
    activeTab: TabId;

    books: BookInfo[];
    booksPage: number;
    booksLastPage: number;
    booksTotal: number;
    search: string;
    filter: "all" | "available" | "borrowed";
    isLoadingBooks: boolean;

    loans: LoanRow[];
    isLoadingLoans: boolean;

    members: MemberInfo[];
    memberSearch: string;
    isLoadingMembers: boolean;

    /** The result sheet: what was scanned / opened */
    result: ScanResult | null;
    /** The borrower picked for lending; stays selected so several books can be lent in a row */
    borrower: MemberInfo | null;
    isBusy: boolean;

    scannerOpen: boolean;
    /** What the scanned code is for: look a book up (lend / return), fill the ISBN field of the open
     *  add form, or start adding the scanned book */
    scanPurpose: ScanPurpose;
    addBookDraft: BookDraft | null;
    /** The book that already has the ISBN typed in the add form */
    addBookExisting: BookInfo | null;
    /** Shown after a book / copy was saved, so adding can go on with the next one */
    addBookDone: { message: string; book: BookInfo } | null;
    isLookingUpIsbn: boolean;

    activity: ActivityEntry[];
    errors: string[];
    successMessage: string | null;
}

export const useLibraryStore = defineStore("library", {
    state: (): LibraryState => ({
        summary: null,
        activeTab: "scan",
        books: [],
        booksPage: 1,
        booksLastPage: 1,
        booksTotal: 0,
        search: "",
        filter: "all",
        isLoadingBooks: false,
        loans: [],
        isLoadingLoans: false,
        members: [],
        memberSearch: "",
        isLoadingMembers: false,
        result: null,
        borrower: null,
        isBusy: false,
        scannerOpen: false,
        scanPurpose: "lookup",
        addBookDraft: null,
        addBookExisting: null,
        addBookDone: null,
        isLookingUpIsbn: false,
        activity: [],
        errors: [],
        successMessage: null,
    }),

    getters: {
        canManage: (s) => !!s.summary?.canManage,
    },

    actions: {
        clearMessages(): void {
            this.errors = [];
            this.successMessage = null;
        },

        async init(): Promise<void> {
            setupAxiosCsrf();
            await Promise.all([this.loadSummary(), this.loadBooks(true)]);
        },

        async loadSummary(): Promise<void> {
            try {
                const { data } = await axios.get<{ success: boolean } & Summary>("/api/library/summary");
                this.summary = data;
            } catch (e) {
                this.errors = await extractErrors(e, FALLBACK_ERROR);
            }
        },

        setActiveTab(tab: TabId): void {
            this.activeTab = tab;
            if (tab === "loans") this.loadLoans();
            if (tab === "members") this.loadMembers();
        },

        // ---- Books ------------------------------------------------------------------------

        /** Load the first page (reset) or the next one (append). */
        async loadBooks(reset = false): Promise<void> {
            if (this.isLoadingBooks) return;
            if (!reset && this.booksPage >= this.booksLastPage) return;

            const page = reset ? 1 : this.booksPage + 1;
            this.isLoadingBooks = true;
            try {
                const { data } = await axios.get("/api/library/books", {
                    params: { search: this.search, filter: this.filter, page },
                });
                this.books = reset ? data.books : [...this.books, ...data.books];
                this.booksPage = data.page;
                this.booksLastPage = data.lastPage;
                this.booksTotal = data.total;
            } catch (e) {
                this.errors = await extractErrors(e, FALLBACK_ERROR);
            } finally {
                this.isLoadingBooks = false;
            }
        },

        /** Keep every list in sync after a loan changed. */
        applyBook(book: BookInfo): void {
            const i = this.books.findIndex((b) => b.id === book.id);
            if (i >= 0) this.books[i] = book;
            if (this.result?.book?.id === book.id) this.result.book = book;
            this.loadSummary();
            if (this.activeTab === "loans") this.loadLoans();
        },

        // ---- Scan / lookup ----

        openScanner(purpose: ScanPurpose = "lookup"): void {
            this.scanPurpose = purpose;
            this.scannerOpen = true;
        },

        async lookup(code: string): Promise<void> {
            code = code.trim();
            if (!code) return;

            this.clearMessages();
            this.isBusy = true;
            try {
                const { data } = await axios.get("/api/library/lookup", { params: { code } });

                if (data.found) {
                    this.result = { code, found: true, book: data.book, copyId: data.copyId, isbn: null };
                } else if (this.canManage && data.isbn) {
                    // Unknown ISBN: managers can add the book right away
                    await this.startAddBook(data.isbn);
                } else {
                    this.result = { code, found: false, book: null, copyId: null, isbn: data.isbn };
                }
            } catch (e) {
                this.errors = await extractErrors(e, FALLBACK_ERROR);
            } finally {
                this.isBusy = false;
            }
        },

        openBook(book: BookInfo): void {
            this.result = { code: book.code, found: true, book, copyId: null, isbn: null };
        },

        closeResult(): void {
            this.result = null;
        },

        async borrow(copyId: number): Promise<boolean> {
            if (!this.borrower) return false;
            this.isBusy = true;
            this.errors = [];
            try {
                const { data } = await axios.post(`/api/library/copies/${copyId}/borrow`, { member_id: this.borrower.id });
                this.applyBook(data.book);
                this.logActivity("borrow", this.describe(data.book, copyId, data.message));
                this.successMessage = data.message;
                return true;
            } catch (e) {
                this.errors = await extractErrors(e, FALLBACK_ERROR);
                return false;
            } finally {
                this.isBusy = false;
            }
        },

        async returnCopy(copyId: number): Promise<boolean> {
            this.isBusy = true;
            this.errors = [];
            try {
                const { data } = await axios.post(`/api/library/copies/${copyId}/return`);
                this.applyBook(data.book);
                this.logActivity("return", this.describe(data.book, copyId, data.message));
                this.successMessage = data.message;
                return true;
            } catch (e) {
                this.errors = await extractErrors(e, FALLBACK_ERROR);
                return false;
            } finally {
                this.isBusy = false;
            }
        },

        async renew(loanId: number): Promise<boolean> {
            this.isBusy = true;
            this.errors = [];
            try {
                const { data } = await axios.post(`/api/library/loans/${loanId}/renew`);
                this.applyBook(data.book);
                this.logActivity("renew", this.describe(data.book, null, data.message));
                this.successMessage = data.message;
                return true;
            } catch (e) {
                this.errors = await extractErrors(e, FALLBACK_ERROR);
                return false;
            } finally {
                this.isBusy = false;
            }
        },

        describe(book: BookInfo, copyId: number | null, message: string): string {
            const copy = copyId ? book.copies.find((c) => c.id === copyId) : null;
            return `《${book.title}》${copy ? ' ' + copy.code : ''} · ${message}`;
        },

        logActivity(kind: ActivityEntry["kind"], text: string): void {
            this.activity.unshift({ id: ++activitySeq, kind, text, at: new Date().toISOString() });
            this.activity = this.activity.slice(0, 20);
        },

        // ---- Loans / members --------------------------------------------------------------

        async loadLoans(): Promise<void> {
            this.isLoadingLoans = true;
            try {
                const { data } = await axios.get("/api/library/loans");
                this.loans = data.loans;
            } catch (e) {
                this.errors = await extractErrors(e, FALLBACK_ERROR);
            } finally {
                this.isLoadingLoans = false;
            }
        },

        async loadMembers(search?: string): Promise<MemberInfo[]> {
            if (search !== undefined) this.memberSearch = search;
            this.isLoadingMembers = true;
            try {
                const { data } = await axios.get("/api/library/members", { params: { search: this.memberSearch } });
                this.members = data.members;
                return data.members;
            } catch (e) {
                this.errors = await extractErrors(e, FALLBACK_ERROR);
                return [];
            } finally {
                this.isLoadingMembers = false;
            }
        },

        /** Create (or find by name) a member and select them as the borrower. */
        async createMember(member: NewMember): Promise<boolean> {
            this.isBusy = true;
            this.errors = [];
            try {
                const { data } = await axios.post("/api/library/members", member);
                this.borrower = data.member;
                return true;
            } catch (e) {
                this.errors = await extractErrors(e, FALLBACK_ERROR);
                return false;
            } finally {
                this.isBusy = false;
            }
        },

        // ---- Add book (managers) ----------------------------------------------------------

        emptyDraft(isbn = ""): BookDraft {
            return { isbn, code: "", title: "", subtitle: "", author: "", publisher: "", category: "", cover_url: "", quantity: 1 };
        },

        closeAddBook(): void {
            this.addBookDraft = null;
            this.addBookExisting = null;
            this.addBookDone = null;
            this.clearMessages();
        },

        /** A code was scanned in "add" mode: start adding that book (or explain why not). */
        async startAddFromScan(code: string): Promise<void> {
            const isbn = code.replace(/[\s-]+/g, "").toUpperCase();
            if (/^(\d{13}|\d{9}[\dX])$/.test(isbn)) {
                await this.startAddBook(isbn);
            } else {
                await this.startAddBook("");
                this.errors = [`扫到的 ${code} 不是 ISBN 条形码。请扫描书背面的 ISBN，或手动填写。`];
            }
        },

        /** Open the add-book form, pre-filled from the ISBN lookup when an ISBN is given. */
        async startAddBook(isbn = ""): Promise<void> {
            this.result = null;
            this.addBookExisting = null;
            const draft = this.emptyDraft(isbn);
            this.addBookDraft = draft;
            if (isbn) await this.fillFromIsbn(draft);
        },

        /** Fill title, author ... from the ISBN (Google Books, cached on the server). */
        async fillFromIsbn(draft: BookDraft): Promise<void> {
            this.isLookingUpIsbn = true;
            this.errors = [];
            try {
                const { data } = await axios.get("/api/library/metadata", { params: { isbn: draft.isbn } });
                draft.isbn = data.isbn;
                this.addBookExisting = data.existing;

                const m = data.metadata;
                if (m) {
                    draft.title = m.title ?? draft.title;
                    draft.subtitle = m.subtitle ?? "";
                    draft.author = m.author ?? "";
                    draft.publisher = m.publisher ?? "";
                    draft.cover_url = m.cover_url ?? "";
                } else if (!data.existing) {
                    this.errors = ["没有在网上找到这本书的资料，请手动填写。"];
                }

            } catch (e) {
                this.errors = await extractErrors(e, FALLBACK_ERROR);
            } finally {
                this.isLookingUpIsbn = false;
            }
        },

        /** Save edited details of an existing book (managers); the detail sheet shows the result. */
        async updateBook(book: BookInfo, fields: BookEdit): Promise<boolean> {
            this.isBusy = true;
            this.errors = [];
            try {
                const { data } = await axios.patch(`/api/library/books/${book.id}`, fields);
                this.applyBook(data.book);
                this.successMessage = data.message;
                return true;
            } catch (e) {
                this.errors = await extractErrors(e, FALLBACK_ERROR);
                return false;
            } finally {
                this.isBusy = false;
            }
        },

        /** The ISBN is already in the library and the manager confirmed: add one more copy. */
        async addCopy(book: BookInfo): Promise<boolean> {
            this.isBusy = true;
            this.errors = [];
            try {
                const { data } = await axios.post(`/api/library/books/${book.id}/copies`);
                this.addBookDraft = null;
                this.addBookExisting = null;
                this.addBookDone = { message: data.message, book: data.book };
                await Promise.all([this.loadBooks(true), this.loadSummary()]);
                return true;
            } catch (e) {
                this.errors = await extractErrors(e, FALLBACK_ERROR);
                return false;
            } finally {
                this.isBusy = false;
            }
        },

        async saveBook(draft: BookDraft): Promise<boolean> {
            this.isBusy = true;
            this.errors = [];
            try {
                const { data } = await axios.post("/api/library/books", draft);
                this.addBookDraft = null;
                this.addBookDone = { message: `已保存《${data.book.title}》，共 ${data.book.copies.length} 册。`, book: data.book };
                await Promise.all([this.loadBooks(true), this.loadSummary()]);
                return true;
            } catch (e) {
                this.errors = await extractErrors(e, FALLBACK_ERROR);
                return false;
            } finally {
                this.isBusy = false;
            }
        },

        async importBooks(file: File): Promise<void> {
            this.clearMessages();
            this.isBusy = true;
            try {
                const form = new FormData();
                form.append("file", file);
                const { data } = await axios.post("/api/library/import/books", form);
                this.successMessage = `已导入 ${data.imported} 本书。`;
                await Promise.all([this.loadBooks(true), this.loadSummary()]);
            } catch (e) {
                this.errors = await extractErrors(e, FALLBACK_ERROR);
            } finally {
                this.isBusy = false;
            }
        },
    },
});
