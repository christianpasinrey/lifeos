/**
 * Custom Markdown Parser — converts markdown string to AST (array of block nodes).
 * Each block node has: { type, content, children (inline nodes), meta }
 */

const BLOCK_PATTERNS = [
    { type: 'heading', regex: /^(#{1,6})\s+(.+)$/, meta: (m) => ({ level: m[1].length, text: m[2] }) },
    { type: 'horizontal_rule', regex: /^(-{3,}|_{3,}|\*{3,})$/ },
    { type: 'checkbox', regex: /^(\s*)- \[([ xX])\]\s+(.+)$/, meta: (m) => ({ indent: m[1].length, checked: m[2] !== ' ', text: m[3] }) },
    { type: 'bullet_list', regex: /^(\s*)[-*+]\s+(.+)$/, meta: (m) => ({ indent: m[1].length, text: m[2] }) },
    { type: 'ordered_list', regex: /^(\s*)(\d+)\.\s+(.+)$/, meta: (m) => ({ indent: m[1].length, number: parseInt(m[2]), text: m[3] }) },
    { type: 'blockquote', regex: /^>\s?(.*)$/, meta: (m) => ({ text: m[1] }) },
]

const INLINE_PATTERNS = [
    { type: 'bold', regex: /\*\*(.+?)\*\*/g },
    { type: 'italic', regex: /(?<!\*)\*(?!\*)(.+?)(?<!\*)\*(?!\*)/g },
    { type: 'strikethrough', regex: /~~(.+?)~~/g },
    { type: 'highlight', regex: /==(.+?)==/g },
    { type: 'inline_code', regex: /`([^`]+)`/g },
    { type: 'embed', regex: /!\[\[([^\]]+)\]\]/g },
    { type: 'wikilink', regex: /\[\[([^\]]+)\]\]/g },
    { type: 'tag', regex: /(?<!\w)#([\w\-]+(?:\/[\w\-]+)*)/g },
    { type: 'image', regex: /!\[([^\]]*)\]\(([^)]+)\)/g },
    { type: 'link', regex: /\[([^\]]+)\]\(([^)]+)\)/g },
    { type: 'math_inline', regex: /\$([^$]+)\$/g },
]

export function parse(markdown) {
    if (!markdown) return []

    const lines = markdown.split('\n')
    const blocks = []
    let i = 0

    while (i < lines.length) {
        const line = lines[i]

        // Code block (fenced)
        if (/^```(\w*)/.test(line)) {
            const lang = line.match(/^```(\w*)/)[1] || ''
            const codeLines = []
            i++
            while (i < lines.length && !lines[i].startsWith('```')) {
                codeLines.push(lines[i])
                i++
            }
            blocks.push({ type: 'code_block', content: codeLines.join('\n'), meta: { lang }, line: blocks.length })
            i++ // skip closing ```
            continue
        }

        // Math block
        if (line.trim() === '$$') {
            const mathLines = []
            i++
            while (i < lines.length && lines[i].trim() !== '$$') {
                mathLines.push(lines[i])
                i++
            }
            blocks.push({ type: 'math_block', content: mathLines.join('\n'), line: blocks.length })
            i++
            continue
        }

        // Callout (> [!TYPE])
        if (/^>\s*\[!(\w+)\]/.test(line)) {
            const calloutMatch = line.match(/^>\s*\[!(\w+)\]\s*(.*)/)
            const calloutType = calloutMatch[1].toLowerCase()
            const title = calloutMatch[2] || calloutType
            const bodyLines = []
            i++
            while (i < lines.length && /^>\s?/.test(lines[i])) {
                bodyLines.push(lines[i].replace(/^>\s?/, ''))
                i++
            }
            blocks.push({
                type: 'callout',
                content: bodyLines.join('\n'),
                meta: { calloutType, title },
                children: parseInline(bodyLines.join('\n')),
                line: blocks.length,
            })
            continue
        }

        // Table
        if (line.includes('|') && i + 1 < lines.length && /^\|?[\s\-:|]+\|/.test(lines[i + 1])) {
            const tableLines = [line]
            i++
            while (i < lines.length && lines[i].includes('|')) {
                tableLines.push(lines[i])
                i++
            }
            const rows = tableLines
                .filter(l => !/^[\s\-:|]+$/.test(l.replace(/\|/g, '').trim()) || l.includes('-'))
                .map(l => l.split('|').map(c => c.trim()).filter(c => c !== ''))

            blocks.push({ type: 'table', content: line, meta: { rows }, line: blocks.length })
            continue
        }

        // Empty line
        if (line.trim() === '') {
            i++
            continue
        }

        // Check block patterns
        let matched = false
        for (const pattern of BLOCK_PATTERNS) {
            const m = line.match(pattern.regex)
            if (m) {
                const meta = pattern.meta ? pattern.meta(m) : {}
                const text = meta.text || m[m.length - 1] || ''
                blocks.push({
                    type: pattern.type,
                    content: line,
                    meta,
                    children: parseInline(text),
                    line: blocks.length,
                })
                matched = true
                break
            }
        }

        if (!matched) {
            // Paragraph
            blocks.push({
                type: 'paragraph',
                content: line,
                children: parseInline(line),
                line: blocks.length,
            })
        }

        i++
    }

    return blocks
}

export function parseInline(text) {
    if (!text) return [{ type: 'text', content: text || '' }]

    const tokens = []
    let remaining = text

    // Simple approach: find all inline patterns, sort by position, build token list
    const matches = []
    for (const pattern of INLINE_PATTERNS) {
        const regex = new RegExp(pattern.regex.source, pattern.regex.flags)
        let m
        while ((m = regex.exec(text)) !== null) {
            matches.push({
                type: pattern.type,
                start: m.index,
                end: m.index + m[0].length,
                full: m[0],
                content: m[1],
                meta: m[2] ? { url: m[2] } : {},
            })
        }
    }

    // Sort by start position, earlier first; on tie, longer match first
    matches.sort((a, b) => a.start - b.start || b.end - a.end)

    // Build non-overlapping token list
    let pos = 0
    for (const match of matches) {
        if (match.start < pos) continue // overlap, skip

        if (match.start > pos) {
            tokens.push({ type: 'text', content: text.slice(pos, match.start) })
        }
        tokens.push({ type: match.type, content: match.content, meta: match.meta })
        pos = match.end
    }

    if (pos < text.length) {
        tokens.push({ type: 'text', content: text.slice(pos) })
    }

    return tokens.length ? tokens : [{ type: 'text', content: text }]
}

export default { parse, parseInline }
