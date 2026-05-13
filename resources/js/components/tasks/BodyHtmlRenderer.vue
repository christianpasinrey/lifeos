<template>
    <div v-if="hasContent" class="body-html-rendered prose-task" v-html="safeHtml" />
    <div v-else class="text-xs text-surface-600 italic">Sin contenido enriquecido.</div>
</template>

<script setup>
import { computed } from 'vue'
import DOMPurify from 'isomorphic-dompurify'

const props = defineProps({
    html: { type: String, default: '' },
})

const hasContent = computed(() => {
    const stripped = (props.html || '').replace(/<[^>]+>/g, '').trim()
    return stripped.length > 0
})

const safeHtml = computed(() =>
    DOMPurify.sanitize(props.html || '', {
        USE_PROFILES: { html: true },
        ALLOWED_TAGS: [
            'h1', 'h2', 'h3', 'h4', 'p', 'br', 'hr',
            'strong', 'em', 'u', 's', 'code', 'pre',
            'a', 'blockquote',
            'ul', 'ol', 'li', 'input', 'label',
        ],
        ALLOWED_ATTR: ['href', 'title', 'rel', 'target', 'class', 'data-type', 'data-checked', 'type', 'checked', 'disabled'],
        ADD_ATTR: ['target', 'rel'],
        FORBID_TAGS: ['script', 'style', 'iframe', 'object', 'embed'],
    })
)
</script>

<style scoped>
.body-html-rendered :deep(p) { margin: 0.375rem 0; font-size: 0.875rem; color: rgb(226 232 240); line-height: 1.625; }
.body-html-rendered :deep(h1) { font-size: 1.125rem; font-weight: 600; margin: 0.75rem 0 0.25rem; color: white; }
.body-html-rendered :deep(h2) { font-size: 1rem; font-weight: 600; margin: 0.75rem 0 0.25rem; color: white; }
.body-html-rendered :deep(h3) { font-size: 0.875rem; font-weight: 600; margin: 0.625rem 0 0.25rem; color: rgb(226 232 240); }
.body-html-rendered :deep(ul) { list-style: disc; padding-left: 1.25rem; margin: 0.375rem 0; font-size: 0.875rem; color: rgb(226 232 240); }
.body-html-rendered :deep(ol) { list-style: decimal; padding-left: 1.25rem; margin: 0.375rem 0; font-size: 0.875rem; color: rgb(226 232 240); }
.body-html-rendered :deep(ul[data-type="taskList"]) { list-style: none; padding-left: 0; }
.body-html-rendered :deep(ul[data-type="taskList"] li) { display: flex; align-items: flex-start; gap: 0.5rem; }
.body-html-rendered :deep(blockquote) {
    border-left: 2px solid rgba(99, 102, 241, 0.4);
    padding-left: 0.75rem;
    font-style: italic;
    color: rgb(203 213 225);
    margin: 0.5rem 0;
    font-size: 0.875rem;
}
.body-html-rendered :deep(code) {
    background-color: rgba(255, 255, 255, 0.06);
    color: rgb(165 180 252);
    border-radius: 0.25rem;
    padding: 0.125rem 0.25rem;
    font-size: 12px;
}
.body-html-rendered :deep(pre) {
    background-color: rgba(0, 0, 0, 0.4);
    border-radius: 0.375rem;
    padding: 0.75rem;
    margin: 0.5rem 0;
    overflow-x: auto;
}
.body-html-rendered :deep(pre code) { background: transparent; color: rgb(226 232 240); padding: 0; font-size: 12px; }
.body-html-rendered :deep(a) { color: rgb(96 165 250); text-decoration: underline; text-underline-offset: 2px; }
.body-html-rendered :deep(a:hover) { color: rgb(147 197 253); }
.body-html-rendered :deep(hr) { border-color: rgba(255, 255, 255, 0.1); margin: 0.75rem 0; }
</style>
