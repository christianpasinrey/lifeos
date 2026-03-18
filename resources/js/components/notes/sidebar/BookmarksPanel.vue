<template>
    <div class="bookmarks-panel">
        <div class="px-2 py-1.5 mb-1">
            <span class="text-[0.625rem] font-semibold text-surface-500 uppercase tracking-wider">Favoritos</span>
        </div>

        <div v-if="notes?.data?.length" class="space-y-0.5">
            <div
                v-for="note in notes.data"
                :key="note.id"
                class="file-item"
                @click="$emit('select-note', note)"
            >
                <StarIcon class="w-3.5 h-3.5 text-amber-400 flex-shrink-0" />
                <span class="truncate text-sm text-surface-300">{{ note.title }}</span>
            </div>
        </div>

        <div v-else class="px-2 py-4 text-center">
            <p class="text-xs text-surface-600">Sin notas favoritas</p>
        </div>
    </div>
</template>

<script setup>
import { useBookmarkedNotes } from '@/composables/useNotes'
import { StarIcon } from '@heroicons/vue/24/outline'

const emit = defineEmits(['select-note'])

const { data: notes } = useBookmarkedNotes()
</script>
