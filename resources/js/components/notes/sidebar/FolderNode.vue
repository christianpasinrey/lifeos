<template>
    <div>
        <div
            class="file-item file-item-folder"
            @click="expanded = !expanded"
            @contextmenu="onFolderContextMenu"
        >
            <ChevronRightIcon class="w-3 h-3 text-surface-600 flex-shrink-0 transition-transform" :class="{ 'rotate-90': expanded }" />
            <FolderIcon class="w-3.5 h-3.5 flex-shrink-0" :class="expanded ? 'text-primary-400' : 'text-surface-500'" />
            <span class="truncate text-sm text-surface-300 flex-1">{{ folder.name }}</span>
            <span v-if="folder.all_notes_count" class="text-[0.625rem] text-surface-600 flex-shrink-0">{{ folder.all_notes_count }}</span>
        </div>

        <div v-if="expanded" class="ml-3 border-l border-white/[0.04] pl-1.5 space-y-0.5">
            <!-- Subfolders -->
            <FolderNode
                v-for="child in folder.children ?? []"
                :key="child.id"
                :folder="child"
                @select-note="$emit('select-note', $event)"
                @create-note="$emit('create-note', $event)"
                @rename-folder="$emit('rename-folder', $event)"
                @delete-folder="$emit('delete-folder', $event)"
                @delete-note="$emit('delete-note', $event)"
            />

            <!-- Notes in this folder -->
            <div
                v-for="note in folderNotes"
                :key="'n-' + note.id"
                class="file-item"
                @click="$emit('select-note', note)"
                @contextmenu="onNoteContextMenu($event, note)"
            >
                <DocumentTextIcon class="w-3.5 h-3.5 text-surface-500 flex-shrink-0" />
                <span class="truncate text-sm text-surface-300">{{ note.title }}</span>
            </div>

            <div v-if="!folder.children?.length && !folderNotes.length" class="px-2 py-1">
                <span class="text-xs text-surface-600 italic">Vacía</span>
            </div>
        </div>

        <ContextMenu ref="contextMenu" />
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useNotes } from '@/composables/useNotes'
import ContextMenu from './ContextMenu.vue'
import {
    ChevronRightIcon, FolderIcon, DocumentTextIcon,
    DocumentPlusIcon, FolderPlusIcon, PencilIcon, TrashIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    folder: { type: Object, required: true },
})

const emit = defineEmits(['select-note', 'create-note', 'rename-folder', 'delete-folder', 'delete-note'])

const expanded = ref(false)
const contextMenu = ref(null)

const { data: notesData } = useNotes(computed(() => expanded.value ? props.folder.id : null))
const folderNotes = computed(() => {
    if (!expanded.value || !notesData.value) return []
    return notesData.value.data ?? []
})

function onFolderContextMenu(e) {
    e.preventDefault()
    e.stopPropagation()
    contextMenu.value.show(e, [
        { id: 'new-note', label: 'Nueva nota aquí', icon: DocumentPlusIcon },
        { id: 'new-subfolder', label: 'Nueva subcarpeta', icon: FolderPlusIcon },
        { id: 'rename', label: 'Renombrar', icon: PencilIcon },
        { id: 'delete', label: 'Eliminar carpeta', icon: TrashIcon, danger: true },
    ], (action) => {
        if (action === 'new-note') {
            expanded.value = true
            emit('create-note', props.folder.id)
        }
        if (action === 'new-subfolder') {
            expanded.value = true
            const name = prompt('Nombre de la subcarpeta:')
            if (name?.trim()) {
                // Use the parent's create-note emit path but we need createFolder
                // This will be handled by FileExplorer via event bubbling
                import('@/composables/useNoteFolders').then(({ useCreateFolder }) => {
                    useCreateFolder().mutateAsync({ name, parent_id: props.folder.id })
                })
            }
        }
        if (action === 'rename') emit('rename-folder', props.folder)
        if (action === 'delete') emit('delete-folder', props.folder)
    })
}

function onNoteContextMenu(e, note) {
    e.preventDefault()
    e.stopPropagation()
    contextMenu.value.show(e, [
        { id: 'open', label: 'Abrir', icon: DocumentTextIcon },
        { id: 'delete', label: 'Mover a papelera', icon: TrashIcon, danger: true },
    ], (action) => {
        if (action === 'open') emit('select-note', note)
        if (action === 'delete') emit('delete-note', note)
    })
}
</script>
