<template>
    <div class="outline-panel">
        <div class="px-2 py-1.5 mb-1">
            <span class="text-[0.625rem] font-semibold text-surface-500 uppercase tracking-wider">Esquema</span>
        </div>

        <div v-if="headings.length" class="space-y-0.5">
            <button
                v-for="(h, i) in headings"
                :key="i"
                class="file-item w-full text-left"
                :style="{ paddingLeft: (h.level - 1) * 12 + 8 + 'px' }"
                @click="scrollToHeading(h)"
            >
                <span class="text-sm truncate" :class="h.level === 1 ? 'text-surface-200 font-medium' : 'text-surface-400'">
                    {{ h.text }}
                </span>
            </button>
        </div>

        <div v-else class="px-2 py-4 text-center">
            <p class="text-xs text-surface-600">Sin encabezados</p>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    content: { type: String, default: '' },
})

const headings = computed(() => {
    if (!props.content) return []
    const lines = props.content.split('\n')
    return lines
        .filter(line => /^#{1,6}\s/.test(line))
        .map(line => {
            const match = line.match(/^(#{1,6})\s+(.+)/)
            return match ? { level: match[1].length, text: match[2].trim() } : null
        })
        .filter(Boolean)
})

function scrollToHeading(h) {
    // Find heading element in the editor by text content
    const headingEls = document.querySelectorAll('.markdown-editor h1, .markdown-editor h2, .markdown-editor h3, .markdown-editor h4, .markdown-editor h5, .markdown-editor h6')
    for (const el of headingEls) {
        if (el.textContent.trim() === h.text) {
            el.scrollIntoView({ behavior: 'smooth', block: 'center' })
            break
        }
    }
}
</script>
