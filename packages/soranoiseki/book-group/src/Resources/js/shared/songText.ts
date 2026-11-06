/**
 * Normalise punctuation in lyrics: everything after the title and the source line
 * becomes one full-width space between phrases.
 */
export function beautifySongText(text: string): string {
    return text
        .split('\n')
        .map((raw, i) => {
            let line = raw.trim();
            if (i > 1) {
                line = line
                    .replace(/[。；，：“”（）、？！ ,.;:?!]/g, '　')
                    .replace(/　+$/, '')
                    .replace(/　+/g, '　');
            }
            return line;
        })
        .join('\n');
}
