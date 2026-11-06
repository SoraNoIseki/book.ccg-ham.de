/** "11月5日" */
export function fmtDate(iso: string): string {
    return new Date(iso).toLocaleDateString('zh-CN', { month: 'numeric', day: 'numeric' });
}

/** Days from today until the date; negative when it is in the past. */
export function daysUntil(iso: string): number {
    const day = 24 * 3600 * 1000;
    const start = new Date(); start.setHours(0, 0, 0, 0);
    const due = new Date(iso); due.setHours(0, 0, 0, 0);
    return Math.round((due.getTime() - start.getTime()) / day);
}

/** "还有 12 天" / "今天到期" / "已逾期 3 天" */
export function dueText(iso: string): string {
    const d = daysUntil(iso);
    if (d < 0) return `已逾期 ${-d} 天`;
    if (d === 0) return '今天到期';
    return `还有 ${d} 天`;
}
