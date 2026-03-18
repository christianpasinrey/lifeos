<template>
    <div>
        <div class="file-item file-item-folder" @click="expanded = !expanded">
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
            />

            <!-- Notes in this folder -->
            <div
                v-for="note in folderNotes"
                :key="'n-' + note.id"
                class="file-item"
                @click="$emit('select-note', note)"
            >
                <DocumentTextIcon class="w-3.5 h-3.5 text-surface-500 flex-shrink-0" />
                <span class="truncate text-sm text-surface-300">{{ note.title }}</span>
            </div>

            <div v-if="!folder.children?.length && !folderNotes.length" class="px-2 py-1">
                <span class="text-xs text-surface-600 italic">Vacía</span>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useNotes } from '@/composables/useNotes'
import { ChevronRightIcon, FolderIcon, DocumentTextIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    folder: { type: Object, required: true },
})

const emit = defineEmits(['select-note', 'create-note'])

const expanded = ref(false)

const { data: notesData } = useNotes(computed(() => expanded.value ? props.folder.id : null))
const folderNotes = computed(() => {
    if (!expanded.value || !notesData.value) return []
    return notesData.value.data ?? []
})
</script>
