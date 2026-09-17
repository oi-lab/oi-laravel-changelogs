/**
 * The day an entry carries, as the reader's own locale writes it.
 *
 * Intl rather than a date library: a change log shows one date per row and
 * nothing else, and the browser already knows how to write it. Pass a locale
 * if your application pins one.
 */
export function formatChangeLogDate(date: string, locale?: string): string {
    const parsed = new Date(`${date}T00:00:00`);

    if (Number.isNaN(parsed.getTime())) {
        return date;
    }

    return new Intl.DateTimeFormat(locale, { dateStyle: 'long' }).format(parsed);
}
