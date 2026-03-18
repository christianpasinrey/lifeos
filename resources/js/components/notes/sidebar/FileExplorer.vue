<template>
    <div class="file-explorer">
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
            />

            <!-- Root-level notes -->
            <div
                v-for="note in foldersData.data?.root_notes ?? []"
                :key="'n-' + note.id"
                class="file-item"
                @click="$emit('select-note', note)"
            >
                <DocumentTextIcon class="w-3.5 h-3.5 text-surface-500 flex-shrink-0" />
                <span class="truncate text-sm text-surface-300">{{ note.title }}</span>
                <StarIcon v-if="note.is_bookmarked" class="w-3 h-3 text-amber-400 flex-shrink-0 ml-auto" />
            </div>
        </div>

        <div v-else class="px-2 py-8 text-center">
            <div class="animate-spin w-5 h-5 border-2 border-primary-400 border-t-transparent rounded-full mx-auto" />
        </div>
    </div>
</template>

<script setup>
import { useNoteFolders, useCreateFolder } from '@/composables/useNoteFolders'
import FolderNode from './FolderNode.vue'
import { DocumentTextIcon, DocumentPlusIcon, FolderPlusIcon, StarIcon } from '@heroicons/vue/24/outline'

const emit = defineEmits(['select-note', 'create-note'])

const { data: foldersData } = useNoteFolders()
const createFolderMutation = useCreateFolder()

async function createFolder(parentId) {
    const name = prompt('Nombre de la carpeta:')
    if (!name?.trim()) return
    await createFolderMutation.mutateAsync({ name, parent_id: parentId })
}
</script>
