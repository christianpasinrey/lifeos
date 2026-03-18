/**
 * Lightweight syntax highlighting for code blocks.
 * Tokenizes keywords, strings, comments, numbers for common languages.
 */

const LANGUAGES = {
    js: {
        keywords: /\b(const|let|var|function|return|if|else|for|while|class|import|export|default|from|new|this|async|await|try|catch|throw|typeof|instanceof|in|of|switch|case|break|continue|null|undefined|true|false)\b/g,
        strings: /(["'`])(?:(?!\1|\\).|\\.)*\1/g,
        comments: /\/\/.*$|\/\*[\s\S]*?\*\//gm,
        numbers: /\b\d+\.?\d*\b/g,
    },
    ts: null, // alias
    php: {
        keywords: /\b(function|return|if|else|elseif|for|foreach|while|class|public|private|protected|static|new|use|namespace|extends|implements|interface|abstract|final|const|echo|array|null|true|false|match|fn|readonly)\b/g,
        strings: /(["'])(?:(?!\1|\\).|\\.)*\1/g,
        comments: /\/\/.*$|\/\*[\s\S]*?\*\/|#.*$/gm,
        numbers: /\b\d+\.?\d*\b/g,
        variables: /\$\w+/g,
    },
    python: {
        keywords: /\b(def|return|if|elif|else|for|while|class|import|from|as|try|except|raise|with|lambda|pass|None|True|False|and|or|not|in|is|yield|async|await|self)\b/g,
        strings: /("""[\s\S]*?"""|'''[\s\S]*?'''|"(?:[^"\\]|\\.)*"|'(?:[^'\\]|\\.)*')/g,
        comments: /#.*$/gm,
        numbers: /\b\d+\.?\d*\b/g,
    },
    css: {
        keywords: /\b(import|media|keyframes|from|to)\b/g,
        strings: /(["'])(?:(?!\1|\\).|\\.)*\1/g,
        comments: /\/\*[\s\S]*?\*\//g,
        numbers: /\b\d+\.?\d*(px|em|rem|%|vh|vw|s|ms)?\b/g,
        properties: /[\w-]+(?=\s*:)/g,
    },
    html: {
        keywords: /\b(doctype|html|head|body|div|span|a|p|h[1-6]|ul|ol|li|table|tr|td|th|form|input|button|script|style|link|meta|img)\b/gi,
        strings: /(["'])(?:(?!\1|\\).|\\.)*\1/g,
        comments: /<!--[\s\S]*?-->/g,
        tags: /<\/?[\w-]+|>/g,
    },
    sql: {
        keywords: /\b(SELECT|FROM|WHERE|INSERT|INTO|VALUES|UPDATE|SET|DELETE|CREATE|TABLE|ALTER|DROP|JOIN|LEFT|RIGHT|INNER|ON|AND|OR|NOT|NULL|IS|IN|AS|ORDER|BY|GROUP|HAVING|LIMIT|COUNT|SUM|AVG|MAX|MIN|DISTINCT|INDEX|PRIMARY|KEY|FOREIGN|REFERENCES|CASCADE|EXISTS|BETWEEN|LIKE|UNION)\b/gi,
        strings: /(["'])(?:(?!\1|\\).|\\.)*\1/g,
        comments: /--.*$/gm,
        numbers: /\b\d+\.?\d*\b/g,
    },
    json: {
        strings: /"(?:[^"\\]|\\.)*"/g,
        numbers: /\b\d+\.?\d*\b/g,
        keywords: /\b(true|false|null)\b/g,
    },
    bash: {
        keywords: /\b(if|then|else|elif|fi|for|while|do|done|case|esac|function|return|exit|echo|export|source|cd|ls|rm|cp|mv|mkdir|cat|grep|sed|awk|curl|sudo)\b/g,
        strings: /(["'])(?:(?!\1|\\).|\\.)*\1/g,
        comments: /#.*$/gm,
        variables: /\$[\w{][\w}]*/g,
    },
    yaml: {
        keywords: /\b(true|false|null|yes|no)\b/g,
        strings: /(["'])(?:(?!\1|\\).|\\.)*\1/g,
        comments: /#.*$/gm,
        numbers: /\b\d+\.?\d*\b/g,
    },
}

// Aliases
LANGUAGES.ts = LANGUAGES.js
LANGUAGES.javascript = LANGUAGES.js
LANGUAGES.typescript = LANGUAGES.js
LANGUAGES.sh = LANGUAGES.bash
LANGUAGES.shell = LANGUAGES.bash
LANGUAGES.yml = LANGUAGES.yaml

function escapeHtml(str) {
    return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
}

export function highlightCode(code, lang) {
    const rules = LANGUAGES[lang?.toLowerCase()]
    if (!rules) return escapeHtml(code)

    // Collect all matches with their positions and types
    const matches = []

    const tokenTypes = ['comments', 'strings', 'keywords', 'numbers', 'variables', 'properties', 'tags']

    for (const tokenType of tokenTypes) {
        const pattern = rules[tokenType]
        if (!pattern) continue

        const regex = new RegExp(pattern.source, pattern.flags)
        let m
        while ((m = regex.exec(code)) !== null) {
            matches.push({
                start: m.index,
                end: m.index + m[0].length,
                text: m[0],
                type: tokenType,
            })
        }
    }

    // Sort by start, prioritize comments and strings (they override keywords inside them)
    const priority = { comments: 0, strings: 1, tags: 2, variables: 3, keywords: 4, properties: 5, numbers: 6 }
    matches.sort((a, b) => a.start - b.start || priority[a.type] - priority[b.type])

    // Build non-overlapping result
    let result = ''
    let pos = 0

    for (const match of matches) {
        if (match.start < pos) continue
        if (match.start > pos) {
            result += escapeHtml(code.slice(pos, match.start))
        }
        const cls = `hl-${match.type.replace(/s$/, '')}`
        result += `<span class="${cls}">${escapeHtml(match.text)}</span>`
        pos = match.end
    }

    if (pos < code.length) {
        result += escapeHtml(code.slice(pos))
    }

    return result
}

export default { highlightCode }
