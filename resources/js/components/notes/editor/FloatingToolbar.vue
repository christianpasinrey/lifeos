<template>
    <Teleport to="body">
        <div
            v-if="visible"
            class="floating-toolbar liquid-glass"
            :style="{ top: position.top + 'px', left: position.left + 'px' }"
        >
            <button v-for="btn in buttons" :key="btn.label" class="toolbar-btn" @mousedown.prevent="btn.action" :title="btn.label">
                {{ btn.icon }}
            </button>
        </div>
    </Teleport>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'

const visible = ref(false)
const position = ref({ top: 0, left: 0 })

function wrap(before, after) {
    document.execCommand('insertText', false, `${before}${window.getSelection().toString()}${after || before}`)
    visible.value = false
}

const buttons = [
    { label: 'Bold', icon: 'B', action: () => wrap('**') },
    { label: 'Italic', icon: 'I', action: () => wrap('*') },
    { label: 'Strike', icon: 'S', action: () => wrap('~~') },
    { label: 'Code', icon: '<>', action: () => wrap('`') },
    { label: 'Highlight', icon: '==', action: () => wrap('==') },
    { label: 'Link', icon: '🔗', action: () => { const t = window.getSelection().toString(); document.execCommand('insertText', false, `[${t}](url)`); visible.value = false } },
]

function onSelectionChange() {
    const sel = window.getSelection()
    if (!sel.rangeCount || sel.isCollapsed) {
        visible.value = false
        return
    }

    // Check if selection is inside our editor
    const editor = sel.anchorNode?.closest?.('.markdown-editor') || sel.anchorNode?.parentElement?.closest?.('.markdown-editor')
    if (!editor) {
        visible.value = false
        return
    }

    const range = sel.getRangeAt(0)
    const rect = range.getBoundingClientRect()

    position.value = {
        top: rect.top - 40 + window.scrollY,
        left: rect.left + rect.width / 2 - 100,
    }
    visible.value = true
}

onMounted(() => document.addEventListener('selectionchange', onSelectionChange))
onUnmounted(() => document.removeEventListener('selectionchange', onSelectionChange))
</script>
