<template>
    <div class="file-explorer" @contextmenu="onRootContextMenu">
        <div class="flex items-center justify-between px-2 py-1.5 mb-1">
            <span class="text-[0.625rem] font-semibold text-surface-500 uppercase tracking-wider">Explorador</span>
            <div class="flex gap-0.5">
                <button class="btn-icon" @click="$emit('create-note', null)" title="Nueva nota">
                    <DocumentPlusIcon class="w-3.5 h-3.5" />
                </button>
                <button class="btn-icon" @click="createFolder(null)" title="Nueva carpeta">
                    <FolderPlusIcon class="w-3.5 h-3.5" />
                </button>
            </div>
        </div>

        <!-- Folder tree -->
        <div v-if="foldersData" class="space-y-0.5">
            <FolderNode
                v-for="folder in foldersData.data?.folders ?? []"
                :key="folder.id"
                :folder="folder"
                @select-note="$emit('select-note', $event)"
                @create-note="$emit('create-note', $event)"
                @rename-folder="renameFolder"
                @delete-folder="deleteFolder"
                @delete-note="deleteNote"
            />

            <!-- Root-level notes -->
            <div
                v-for="note in foldersData.data?.root_notes ?? []"
                :key="'n-' + note.id"
                class="file-item"
                @click="$emit('select-note', note)"
                @contextmenu="onNoteContextMenu($event, note)"
            >
                <DocumentTextIcon class="w-3.5 h-3.5 text-surface-500 flex-shrink-0" />
                <span class="truncate text-sm text-surface-300">{{ note.title }}</span>
                <StarIcon v-if="note.is_bookmarked" class="w-3 h-3 text-amber-400 flex-shrink-0 ml-auto" />
            </div>
        </div>

        <div v-else class="px-2 py-8 text-center">
            <div class="animate-spin w-5 h-5 border-2 border-primary-400 border-t-transparent rounded-full mx-auto" />
        </div>

        <!-- Context Menu -->
        <ContextMenu ref="contextMenu" />
    </div>
</template>

<script setup>
import { ref } from 'vue'
import { useNoteFolders, useCreateFolder, useUpdateFolder, useDeleteFolder } from '@/composables/useNoteFolders'
import { useDeleteNote } from '@/composables/useNotes'
import FolderNode from './FolderNode.vue'
import ContextMenu from './ContextMenu.vue'
import {
    DocumentTextIcon, DocumentPlusIcon, FolderPlusIcon, StarIcon,
    PencilIcon, TrashIcon, FolderIcon,
} from '@heroicons/vue/24/outline'

const emit = defineEmits(['select-note', 'create-note'])

const { data: foldersData } = useNoteFolders()
const createFolderMutation = useCreateFolder()
const updateFolderMutation = useUpdateFolder()
const deleteFolderMutation = useDeleteFolder()
const deleteNoteMutation = useDeleteNote()
const contextMenu = ref(null)

async function createFolder(parentId) {
    const name = prompt('Nombre de la carpeta:')
    if (!name?.trim()) return
    await createFolderMutation.mutateAsync({ name, parent_id: parentId })
}

async function renameFolder(folder) {
    const name = prompt('Nuevo nombre:', folder.name)
    if (!name?.trim() || name === folder.name) return
    await updateFolderMutation.mutateAsync({ id: folder.id, name })
}

async function deleteFolder(folder) {
    if (!confirm(`¿Eliminar carpeta "${folder.name}" y todo su contenido?`)) return
    await deleteFolderMutation.mutateAsync({ id: folder.id })
}

async function deleteNote(note) {
    if (!confirm(`¿Mover "${note.title}" a la papelera?`)) return
    await deleteNoteMutation.mutateAsync({ id: note.id, slug: note.slug })
}

function onRootContextMenu(e) {
    // Only trigger if clicking on empty space (not on a folder/note item)
    if (e.target.closest('.file-item')) return

    contextMenu.value.show(e, [
        { id: 'new-note', label: 'Nueva nota', icon: DocumentPlusIcon },
        { id: 'new-folder', label: 'Nueva carpeta', icon: FolderPlusIcon },
    ], (action) => {
        if (action === 'new-note') emit('create-note', null)
        if (action === 'new-folder') createFolder(null)
    })
}

function onNoteContextMenu(e, note) {
    e.stopPropagation()
    contextMenu.value.show(e, [
        { id: 'open', label: 'Abrir', icon: DocumentTextIcon },
        { id: 'delete', label: 'Mover a papelera', icon: TrashIcon, danger: true },
    ], (action) => {
        if (action === 'open') emit('select-note', note)
        if (action === 'delete') deleteNote(note)
    })
}
</script>
