class TextHighlighter {

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Helper to escape special regex characters
    escapeRegExp(string) {
        return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }
    constructor(options = {}) {
        this.highlightClass = options.className || 'highlight';
        this.highlightStyle = options.style || 'background-color: yellow';
    }

    highlight(text, search) {
        if (!search)
            return this.escapeHtml(text);

        const safeText = this.escapeHtml(text);
        const searchWords = search.split(' ').filter(word => word.length > 0);
        const pattern = searchWords
            .map(word => this.escapeRegExp(word))
            .join('|');

        return safeText.replace(
            new RegExp(`(${pattern})`, 'gi'),
            this.highlightClass ?
                `<span class="${this.highlightClass}">$1</span>` :
                `<span style="${this.highlightStyle}">$1</span>`
        );
    }
}
