/**
 * Markdown Renderer — converts AST blocks to HTML string.
 * In live preview mode, the active line shows raw markdown.
 */

import { highlightCode } from './SyntaxHighlighter.js'

const CALLOUT_ICONS = {
    note: '📝', tip: '💡', important: '❗', warning: '⚠️',
    caution: '🔥', question: '❓', bug: '🐛', example: '📋', quote: '💬',
}

export function renderToHtml(blocks, activeLine = -1) {
    return blocks.map((block, idx) => {
        if (idx === activeLine) {
            return `<div class="md-line md-line-active" data-line="${idx}">${escapeHtml(block.content)}</div>`
        }
        return renderBlock(block, idx)
    }).join('')
}

function renderBlock(block, idx) {
    const lineAttr = `data-line="${idx}"`

    switch (block.type) {
        case 'heading': {
            const tag = `h${block.meta.level}`
            return `<${tag} class="md-heading md-h${block.meta.level}" ${lineAttr}>${renderInline(block.children)}</${tag}>`
        }

        case 'paragraph':
            return `<p class="md-paragraph" ${lineAttr}>${renderInline(block.children)}</p>`

        case 'bullet_list':
            return `<div class="md-list-item" ${lineAttr} style="padding-left:${block.meta.indent * 16 + 8}px"><span class="md-bullet">•</span> ${renderInline(block.children)}</div>`

        case 'ordered_list':
            return `<div class="md-list-item" ${lineAttr} style="padding-left:${block.meta.indent * 16 + 8}px"><span class="md-bullet">${block.meta.number}.</span> ${renderInline(block.children)}</div>`

        case 'checkbox':
            return `<div class="md-checkbox" ${lineAttr} style="padding-left:${block.meta.indent * 16 + 8}px"><span class="md-check ${block.meta.checked ? 'md-checked' : ''}">${block.meta.checked ? '☑' : '☐'}</span> ${renderInline(block.children)}</div>`

        case 'blockquote':
            return `<blockquote class="md-blockquote" ${lineAttr}>${renderInline(block.children)}</blockquote>`

        case 'callout': {
            const icon = CALLOUT_ICONS[block.meta.calloutType] || '📌'
            return `<div class="md-callout md-callout-${block.meta.calloutType}" ${lineAttr}><div class="md-callout-title">${icon} ${escapeHtml(block.meta.title)}</div><div class="md-callout-body">${renderInline(block.children)}</div></div>`
        }

        case 'code_block': {
            const highlighted = block.meta.lang
                ? highlightCode(block.content, block.meta.lang)
                : escapeHtml(block.content)
            const langBadge = block.meta.lang ? `<span class="md-code-lang">${block.meta.lang}</span>` : ''
            return `<pre class="md-code-block" ${lineAttr}>${langBadge}<code>${highlighted}</code></pre>`
        }

        case 'table': {
            const rows = block.meta.rows
            if (!rows.length) return ''
            const header = rows[0]
            const body = rows.slice(2) // skip separator row
            let html = `<table class="md-table" ${lineAttr}><thead><tr>`
            header.forEach(c => { html += `<th>${escapeHtml(c)}</th>` })
            html += '</tr></thead><tbody>'
            body.forEach(row => {
                html += '<tr>'
                row.forEach(c => { html += `<td>${escapeHtml(c)}</td>` })
                html += '</tr>'
            })
            html += '</tbody></table>'
            return html
        }

        case 'horizontal_rule':
            return `<hr class="md-hr" ${lineAttr} />`

        case 'math_block':
            return `<div class="md-math-block" ${lineAttr}>${escapeHtml(block.content)}</div>`

        default:
            return `<div class="md-line" ${lineAttr}>${escapeHtml(block.content)}</div>`
    }
}

function renderInline(tokens) {
    if (!tokens) return ''
    return tokens.map(token => {
        switch (token.type) {
            case 'text': return escapeHtml(token.content)
            case 'bold': return `<strong>${escapeHtml(token.content)}</strong>`
            case 'italic': return `<em>${escapeHtml(token.content)}</em>`
            case 'strikethrough': return `<del>${escapeHtml(token.content)}</del>`
            case 'highlight': return `<mark class="md-highlight">${escapeHtml(token.content)}</mark>`
            case 'inline_code': return `<code class="md-inline-code">${escapeHtml(token.content)}</code>`
            case 'wikilink': {
                const parts = token.content.split('|')
                const target = parts[0]
                const display = parts[1] || target
                return `<a class="md-wikilink" data-target="${escapeHtml(target)}">${escapeHtml(display)}</a>`
            }
            case 'embed': return `<span class="md-embed" data-target="${escapeHtml(token.content)}">📄 ${escapeHtml(token.content)}</span>`
            case 'tag': return `<span class="md-tag">#${escapeHtml(token.content)}</span>`
            case 'link': return `<a class="md-link" href="${escapeHtml(token.meta?.url || '')}" target="_blank">${escapeHtml(token.content)}</a>`
            case 'image': return `<img class="md-image" src="${escapeHtml(token.meta?.url || '')}" alt="${escapeHtml(token.content)}" />`
            case 'math_inline': return `<span class="md-math-inline">${escapeHtml(token.content)}</span>`
            default: return escapeHtml(token.content || '')
        }
    }).join('')
}

function escapeHtml(str) {
    if (!str) return ''
    return str
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
}

export default { renderToHtml }
