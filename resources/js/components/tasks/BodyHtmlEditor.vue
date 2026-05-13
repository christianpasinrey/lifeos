<template>
    <div class="body-html-editor rounded-xl border border-white/[0.08] bg-white/[0.02] overflow-hidden">
        <!-- Toolbar -->
        <div
            v-if="editor"
            class="body-html-toolbar flex flex-wrap items-center gap-0.5 px-2 py-1.5 border-b border-white/[0.06] bg-black/20"
        >
            <button
                v-for="btn in toolbar"
                :key="btn.key"
                type="button"
                class="toolbar-btn"
                :class="{ 'toolbar-btn-active': btn.isActive?.(editor) }"
                :title="btn.title"
                @click="btn.action(editor)"
            >
                <component :is="btn.icon" class="w-3.5 h-3.5" />
            </button>
            <div class="flex-1" />
            <span class="text-[10px] text-surface-500 px-1">
                {{ wordCount }} {{ wordCount === 1 ? 'palabra' : 'palabras' }}
            </span>
        </div>

        <!-- Editor canvas -->
        <EditorContent :editor="editor" class="body-html-content glass-scroll" />
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue'
import { useEditor, EditorContent } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import Link from '@tiptap/extension-link'
import Placeholder from '@tiptap/extension-placeholder'
import Typography from '@tiptap/extension-typography'
import TaskList from '@tiptap/extension-task-list'
import TaskItem from '@tiptap/extension-task-item'
import {
    Bars3BottomLeftIcon,
    BoldIcon,
    ItalicIcon,
    CodeBracketIcon,
    LinkIcon,
    ListBulletIcon,
    NumberedListIcon,
    H1Icon,
    H2Icon,
    H3Icon,
    CheckIcon,
    ChatBubbleBottomCenterTextIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    modelValue: { type: String, default: '' },
    placeholder: { type: String, default: 'Escribe el contenido enriquecido…' },
})

const emit = defineEmits(['update:modelValue', 'blur'])

const editor = useEditor({
    content: props.modelValue || '',
    extensions: [
        StarterKit.configure({
            codeBlock: { HTMLAttributes: { class: 'body-html-codeblock' } },
            heading: { levels: [1, 2, 3] },
        }),
        Link.configure({
            openOnClick: false,
            HTMLAttributes: {
                class: 'text-primary-400 underline underline-offset-2 hover:text-primary-300',
                rel: 'noopener nofollow ugc',
                target: '_blank',
            },
        }),
        Placeholder.configure({ placeholder: props.placeholder }),
        Typography,
        TaskList,
        TaskItem.configure({ nested: true }),
    ],
    editorProps: {
        attributes: {
            class: 'body-html-prose',
            spellcheck: 'true',
        },
    },
    onUpdate: ({ editor }) => emit('update:modelValue', editor.getHTML()),
    onBlur: () => emit('blur', editor.value?.getHTML() ?? ''),
})

// Keep editor in sync if parent rewrites the value (e.g. switching tasks)
watch(() => props.modelValue, (val) => {
    if (!editor.value) return
    if (val === editor.value.getHTML()) return
    editor.value.commands.setContent(val || '', false)
})

onBeforeUnmount(() => editor.value?.destroy())

const wordCount = computed(() => {
    if (!editor.value) return 0
    const text = editor.value.getText().trim()
    if (!text) return 0
    return text.split(/\s+/).length
})

const toolbar = [
    { key: 'h1', icon: H1Icon, title: 'Título 1', action: e => e.chain().focus().toggleHeading({ level: 1 }).run(), isActive: e => e.isActive('heading', { level: 1 }) },
    { key: 'h2', icon: H2Icon, title: 'Título 2', action: e => e.chain().focus().toggleHeading({ level: 2 }).run(), isActive: e => e.isActive('heading', { level: 2 }) },
    { key: 'h3', icon: H3Icon, title: 'Título 3', action: e => e.chain().focus().toggleHeading({ level: 3 }).run(), isActive: e => e.isActive('heading', { level: 3 }) },
    { key: 'p', icon: Bars3BottomLeftIcon, title: 'Párrafo', action: e => e.chain().focus().setParagraph().run(), isActive: e => e.isActive('paragraph') },
    { key: 'bold', icon: BoldIcon, title: 'Negrita (Ctrl+B)', action: e => e.chain().focus().toggleBold().run(), isActive: e => e.isActive('bold') },
    { key: 'italic', icon: ItalicIcon, title: 'Cursiva (Ctrl+I)', action: e => e.chain().focus().toggleItalic().run(), isActive: e => e.isActive('italic') },
    { key: 'code', icon: CodeBracketIcon, title: 'Código inline', action: e => e.chain().focus().toggleCode().run(), isActive: e => e.isActive('code') },
    { key: 'ul', icon: ListBulletIcon, title: 'Lista', action: e => e.chain().focus().toggleBulletList().run(), isActive: e => e.isActive('bulletList') },
    { key: 'ol', icon: NumberedListIcon, title: 'Lista ordenada', action: e => e.chain().focus().toggleOrderedList().run(), isActive: e => e.isActive('orderedList') },
    { key: 'task', icon: CheckIcon, title: 'Checklist', action: e => e.chain().focus().toggleTaskList().run(), isActive: e => e.isActive('taskList') },
    { key: 'quote', icon: ChatBubbleBottomCenterTextIcon, title: 'Cita', action: e => e.chain().focus().toggleBlockquote().run(), isActive: e => e.isActive('blockquote') },
    { key: 'link', icon: LinkIcon, title: 'Enlace', action: linkAction, isActive: e => e.isActive('link') },
]

function linkAction(e) {
    const prev = e.getAttributes('link').href
    const url = window.prompt('URL del enlace (deja vacío para quitar):', prev || 'https://')
    if (url === null) return
    if (url === '') {
        e.chain().focus().extendMarkRange('link').unsetLink().run()
        return
    }
    e.chain().focus().extendMarkRange('link').setLink({ href: url }).run()
}
</script>

<style scoped>
.toolbar-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.75rem;
    height: 1.75rem;
    border-radius: 0.375rem;
    color: rgb(148 163 184);
    transition: color 0.15s, background-color 0.15s;
}
.toolbar-btn:hover {
    background-color: rgba(255, 255, 255, 0.06);
    color: white;
}
.toolbar-btn-active {
    background-color: rgba(99, 102, 241, 0.15);
    color: rgb(165 180 252);
}

:deep(.body-html-prose) {
    min-height: 140px;
    max-height: 420px;
    overflow-y: auto;
    padding: 0.75rem 1rem;
    font-size: 0.875rem;
    line-height: 1.625;
    color: rgb(226 232 240);
    outline: none;
}

:deep(.body-html-prose p) { margin: 0.375rem 0; }
:deep(.body-html-prose h1) { font-size: 1.125rem; font-weight: 600; margin: 0.75rem 0 0.25rem; color: white; }
:deep(.body-html-prose h2) { font-size: 1rem; font-weight: 600; margin: 0.75rem 0 0.25rem; color: white; }
:deep(.body-html-prose h3) { font-size: 0.875rem; font-weight: 600; margin: 0.625rem 0 0.25rem; color: rgb(226 232 240); }
:deep(.body-html-prose ul) { list-style: disc; padding-left: 1.25rem; margin: 0.375rem 0; }
:deep(.body-html-prose ol) { list-style: decimal; padding-left: 1.25rem; margin: 0.375rem 0; }
:deep(.body-html-prose ul[data-type="taskList"]) { list-style: none; padding-left: 0; }
:deep(.body-html-prose ul[data-type="taskList"] li) { display: flex; align-items: flex-start; gap: 0.5rem; }
:deep(.body-html-prose ul[data-type="taskList"] li > label) { margin-top: 0.25rem; }
:deep(.body-html-prose blockquote) {
    border-left: 2px solid rgba(99, 102, 241, 0.4);
    padding-left: 0.75rem;
    font-style: italic;
    color: rgb(203 213 225);
    margin: 0.5rem 0;
}
:deep(.body-html-prose code) {
    background-color: rgba(255, 255, 255, 0.06);
    color: rgb(165 180 252);
    border-radius: 0.25rem;
    padding: 0.125rem 0.25rem;
    font-size: 12px;
}
:deep(.body-html-prose pre) {
    background-color: rgba(0, 0, 0, 0.4);
    border-radius: 0.375rem;
    padding: 0.75rem;
    margin: 0.5rem 0;
    overflow-x: auto;
}
:deep(.body-html-prose pre code) { background: transparent; color: rgb(226 232 240); padding: 0; font-size: 12px; }
:deep(.body-html-prose a) { color: rgb(96 165 250); text-decoration: underline; text-underline-offset: 2px; }
:deep(.body-html-prose hr) { border-color: rgba(255, 255, 255, 0.1); margin: 0.75rem 0; }

:deep(.body-html-prose .is-editor-empty:first-child::before) {
    content: attr(data-placeholder);
    color: rgb(100 116 139);
    float: left;
    pointer-events: none;
    height: 0;
}
</style>
