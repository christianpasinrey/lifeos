<template>
    <div class="markdown-editor-wrapper">
        <div
            ref="editorEl"
            class="markdown-editor glass-scroll"
            contenteditable="true"
            spellcheck="true"
            @input="onInput"
            @keydown="onKeydown"
            @click="onClick"
            @blur="onBlur"
            v-html="renderedHtml"
        />
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, nextTick } from 'vue'
import { parse } from './MarkdownParser.js'
import { renderToHtml } from './MarkdownRenderer.js'
import { InputHandler } from './InputHandler.js'

const props = defineProps({
    content: { type: String, default: '' },
})

const emit = defineEmits(['save'])

const editorEl = ref(null)
const rawContent = ref(props.content)
const activeLine = ref(-1)
let saveTimer = null
let inputHandler = null

const ast = computed(() => parse(rawContent.value))
const renderedHtml = computed(() => renderToHtml(ast.value, activeLine.value))

watch(() => props.content, (val) => {
    if (val !== rawContent.value) {
        rawContent.value = val
    }
})

onMounted(() => {
    inputHandler = new InputHandler(editorEl.value, {
        onSave: () => emit('save', rawContent.value),
    })
    if (props.content) {
        inputHandler.lastSnapshot = props.content
    }
})

function extractText() {
    if (!editorEl.value) return ''
    // Extract plain text from contenteditable, preserving line breaks
    const clone = editorEl.value.cloneNode(true)
    // Replace block elements with newlines
    clone.querySelectorAll('br').forEach(br => br.replaceWith('\n'))
    clone.querySelectorAll('div, p, h1, h2, h3, h4, h5, h6, pre, blockquote, hr, table, .md-list-item, .md-checkbox, .md-callout, .md-line, .md-line-active').forEach(el => {
        el.prepend(document.createTextNode('\n'))
    })
    return clone.textContent?.replace(/^\n/, '') || ''
}

function getActiveLine() {
    const sel = window.getSelection()
    if (!sel.rangeCount || !editorEl.value) return -1

    let node = sel.anchorNode
    while (node && node !== editorEl.value) {
        if (node.nodeType === 1 && node.dataset?.line !== undefined) {
            return parseInt(node.dataset.line)
        }
        node = node.parentNode
    }
    return -1
}

function onInput() {
    const text = extractText()
    rawContent.value = text
    activeLine.value = getActiveLine()

    // Debounced autosave
    clearTimeout(saveTimer)
    saveTimer = setTimeout(() => {
        inputHandler?.snapshot(text)
        emit('save', text)
    }, 2000)
}

function onKeydown(e) {
    if (inputHandler?.handleKeydown(e)) {
        // Handler consumed the event
        return
    }
}

function onClick() {
    activeLine.value = getActiveLine()
}

function onBlur() {
    activeLine.value = -1
    // Save on blur
    clearTimeout(saveTimer)
    if (rawContent.value !== props.content) {
        emit('save', rawContent.value)
    }
}
</script>
