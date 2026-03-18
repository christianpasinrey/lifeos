<template>
    <div class="note-editor-view" v-if="note">
        <!-- Properties panel -->
        <PropertiesPanel
            v-if="showProperties"
            :properties="note.properties || {}"
            @update="saveProperties"
        />

        <!-- Save indicator -->
        <div class="note-save-indicator">
            <span v-if="saving" class="text-xs text-surface-500">Guardando...</span>
            <span v-else-if="lastSaved" class="text-xs text-surface-600">Guardado</span>
        </div>

        <!-- Editor -->
        <MarkdownEditor
            :content="note.content || ''"
            @save="saveContent"
        />
    </div>

    <!-- New note form -->
    <div v-else-if="isNew" class="note-new-form">
        <div class="max-w-xl mx-auto py-20 px-4">
            <h2 class="text-xl font-semibold text-surface-200 mb-4">Nueva nota</h2>
            <input
                ref="titleInput"
                v-model="newTitle"
                class="form-input text-lg mb-3"
                placeholder="Título de la nota..."
                @keydown.enter="createNote"
                autofocus
            />
            <button class="btn-add" @click="createNote" :disabled="!newTitle.trim()">
                Crear nota
            </button>
        </div>
    </div>

    <!-- Empty state -->
    <div v-else class="flex items-center justify-center h-full">
        <div class="text-center">
            <DocumentTextIcon class="w-12 h-12 text-surface-700 mx-auto mb-3" />
            <p class="text-surface-500 text-sm">Selecciona o crea una nota</p>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useNote, useCreateNote, useUpdateNote } from '@/composables/useNotes'
import MarkdownEditor from '@/components/notes/editor/MarkdownEditor.vue'
import PropertiesPanel from '@/components/notes/PropertiesPanel.vue'
import { DocumentTextIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    slug: { type: String, default: '' },
})

const emit = defineEmits(['noteLoaded'])

const route = useRoute()
const router = useRouter()

const slug = computed(() => props.slug || route.params.slug || '')
const isNew = computed(() => route.query.new === '1')

const { data: noteData } = useNote(slug)
const note = computed(() => noteData.value?.data ?? null)

const updateNote = useUpdateNote()
const createNoteMutation = useCreateNote()

const saving = ref(false)
const lastSaved = ref(false)
const showProperties = ref(false)
const newTitle = ref('')

watch(note, (n) => {
    if (n) emit('noteLoaded', n)
}, { immediate: true })

async function saveContent(content) {
    if (!note.value) return
    saving.value = true
    try {
        await updateNote.mutateAsync({ slug: note.value.slug, content })
        lastSaved.value = true
        setTimeout(() => { lastSaved.value = false }, 2000)
    } finally {
        saving.value = false
    }
}

async function saveProperties(properties) {
    if (!note.value) return
    await updateNote.mutateAsync({ slug: note.value.slug, properties })
}

async function createNote() {
    if (!newTitle.value.trim()) return
    const result = await createNoteMutation.mutateAsync({
        title: newTitle.value,
        folder_id: route.query.folder || null,
    })
    router.push({ name: 'note', params: { slug: result.data.slug } })
}
</script>
