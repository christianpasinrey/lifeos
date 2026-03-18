/**
 * InputHandler — keyboard shortcuts, list continuation, undo/redo.
 */

export class InputHandler {
    constructor(editor, { onSave }) {
        this.editor = editor
        this.onSave = onSave
        this.undoStack = []
        this.redoStack = []
        this.lastSnapshot = ''
    }

    handleKeydown(e) {
        // Ctrl/Cmd shortcuts
        if (e.ctrlKey || e.metaKey) {
            switch (e.key) {
                case 'b': e.preventDefault(); this.wrapSelection('**', '**'); return true
                case 'i': e.preventDefault(); this.wrapSelection('*', '*'); return true
                case 'k': e.preventDefault(); this.insertLink(); return true
                case 's': e.preventDefault(); this.onSave?.(); return true
                case 'z':
                    if (e.shiftKey) { e.preventDefault(); this.redo(); return true }
                    e.preventDefault(); this.undo(); return true
            }
        }

        // Tab indentation in lists
        if (e.key === 'Tab') {
            const line = this.getCurrentLine()
            if (/^(\s*)[-*+]\s|^(\s*)\d+\.\s|^(\s*)- \[/.test(line)) {
                e.preventDefault()
                if (e.shiftKey) {
                    this.outdentLine()
                } else {
                    this.indentLine()
                }
                return true
            }
        }

        // Enter — list continuation
        if (e.key === 'Enter') {
            const line = this.getCurrentLine()

            // Empty list item → exit list
            if (/^(\s*)[-*+]\s*$/.test(line) || /^(\s*)\d+\.\s*$/.test(line) || /^(\s*)- \[[ xX]\]\s*$/.test(line)) {
                e.preventDefault()
                this.replaceCurrentLine('')
                return true
            }

            // Continue bullet list
            const bulletMatch = line.match(/^(\s*)([-*+])\s/)
            if (bulletMatch) {
                e.preventDefault()
                this.insertAtCursor(`\n${bulletMatch[1]}${bulletMatch[2]} `)
                return true
            }

            // Continue ordered list
            const orderedMatch = line.match(/^(\s*)(\d+)\.\s/)
            if (orderedMatch) {
                e.preventDefault()
                const next = parseInt(orderedMatch[2]) + 1
                this.insertAtCursor(`\n${orderedMatch[1]}${next}. `)
                return true
            }

            // Continue checkbox
            const checkMatch = line.match(/^(\s*)- \[[ xX]\]\s/)
            if (checkMatch) {
                e.preventDefault()
                this.insertAtCursor(`\n${checkMatch[1]}- [ ] `)
                return true
            }
        }

        return false
    }

    wrapSelection(before, after) {
        const sel = window.getSelection()
        if (!sel.rangeCount) return

        const range = sel.getRangeAt(0)
        const text = range.toString()

        if (text) {
            document.execCommand('insertText', false, `${before}${text}${after}`)
        } else {
            document.execCommand('insertText', false, `${before}${after}`)
            // Move cursor between markers
            const newRange = sel.getRangeAt(0)
            newRange.setStart(newRange.startContainer, newRange.startOffset - after.length)
            newRange.setEnd(newRange.startContainer, newRange.startOffset)
            sel.removeAllRanges()
            sel.addRange(newRange)
        }
    }

    insertLink() {
        const sel = window.getSelection()
        const text = sel.toString() || 'texto'
        document.execCommand('insertText', false, `[${text}](url)`)
    }

    insertAtCursor(text) {
        document.execCommand('insertText', false, text)
    }

    getCurrentLine() {
        const sel = window.getSelection()
        if (!sel.rangeCount) return ''

        const range = sel.getRangeAt(0)
        const node = range.startContainer
        const text = node.textContent || ''
        const offset = range.startOffset

        // Find line start/end
        const before = text.substring(0, offset)
        const lineStart = before.lastIndexOf('\n') + 1
        const afterOffset = text.indexOf('\n', offset)
        const lineEnd = afterOffset === -1 ? text.length : afterOffset

        return text.substring(lineStart, lineEnd)
    }

    replaceCurrentLine(replacement) {
        const sel = window.getSelection()
        if (!sel.rangeCount) return

        const range = sel.getRangeAt(0)
        const node = range.startContainer
        const text = node.textContent || ''
        const offset = range.startOffset

        const before = text.substring(0, offset)
        const lineStart = before.lastIndexOf('\n') + 1
        const afterOffset = text.indexOf('\n', offset)
        const lineEnd = afterOffset === -1 ? text.length : afterOffset

        const newText = text.substring(0, lineStart) + replacement + text.substring(lineEnd)
        node.textContent = newText

        // Set cursor
        const newRange = document.createRange()
        newRange.setStart(node, lineStart + replacement.length)
        newRange.collapse(true)
        sel.removeAllRanges()
        sel.addRange(newRange)
    }

    indentLine() {
        const line = this.getCurrentLine()
        this.replaceCurrentLine('  ' + line)
    }

    outdentLine() {
        const line = this.getCurrentLine()
        if (line.startsWith('  ')) {
            this.replaceCurrentLine(line.substring(2))
        }
    }

    snapshot(content) {
        if (content === this.lastSnapshot) return
        this.undoStack.push(this.lastSnapshot)
        if (this.undoStack.length > 100) this.undoStack.shift()
        this.redoStack = []
        this.lastSnapshot = content
    }

    undo() {
        if (!this.undoStack.length) return null
        this.redoStack.push(this.lastSnapshot)
        this.lastSnapshot = this.undoStack.pop()
        return this.lastSnapshot
    }

    redo() {
        if (!this.redoStack.length) return null
        this.undoStack.push(this.lastSnapshot)
        this.lastSnapshot = this.redoStack.pop()
        return this.lastSnapshot
    }
}

export default InputHandler
