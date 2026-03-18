<template>
    <div class="note-trash-view px-4 py-6">
        <div class="flex items-center gap-3 mb-4">
            <TrashIcon class="w-5 h-5 text-surface-500" />
            <h3 class="text-lg font-semibold text-surface-200">Papelera</h3>
        </div>

        <div v-if="notes?.data?.length" class="space-y-1">
            <div
                v-for="note in notes.data"
                :key="note.id"
                class="flex items-center justify-between px-3 py-2 rounded-lg hover:bg-white/5 transition-colors"
            >
                <div class="min-w-0">
                    <p class="text-sm text-surface-300 truncate">{{ note.title }}</p>
                    <p class="text-xs text-surface-600">Eliminada {{ formatDate(note.deleted_at) }}</p>
                </div>
                <div class="flex items-center gap-1 flex-shrink-0">
                    <button class="btn-icon text-primary-400" @click="restore(note)" title="Restaurar">
                        <ArrowUturnLeftIcon class="w-3.5 h-3.5" />
                    </button>
                </div>
            </div>
        </div>

        <div v-else class="text-center py-12">
            <p class="text-sm text-surface-600">La papelera está vacía</p>
        </div>
    </div>
</template>

<script setup>
import { useTrashNotes, useRestoreNote } from '@/composables/useNotes'
import { TrashIcon, ArrowUturnLeftIcon } from '@heroicons/vue/24/outline'

const { data: notes } = useTrashNotes()
const restoreNote = useRestoreNote()

async function restore(note) {
    await restoreNote.mutateAsync({ id: note.id, slug: note.slug })
}

function formatDate(d) {
    if (!d) return ''
    return new Date(d).toLocaleDateString('es-ES', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' })
}
</script>
