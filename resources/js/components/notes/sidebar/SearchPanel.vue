<template>
    <div class="search-panel">
        <div class="px-2 py-1.5">
            <input
                v-model="query"
                class="form-input text-sm"
                placeholder="Buscar en notas..."
                autofocus
            />
        </div>

        <div v-if="data?.data?.length" class="space-y-0.5 mt-1">
            <div
                v-for="note in data.data"
                :key="note.id"
                class="file-item"
                @click="$emit('select-note', note)"
            >
                <DocumentTextIcon class="w-3.5 h-3.5 text-surface-500 flex-shrink-0" />
                <div class="min-w-0">
                    <p class="text-sm text-surface-300 truncate">{{ note.title }}</p>
                    <p v-if="note.folder" class="text-[0.625rem] text-surface-600 truncate">{{ note.folder.name }}</p>
                </div>
            </div>
        </div>

        <div v-else-if="query && !isLoading" class="px-2 py-4 text-center">
            <p class="text-xs text-surface-600">Sin resultados</p>
        </div>
    </div>
</template>

<script setup>
import { useNoteSearch } from '@/composables/useNoteSearch'
import { DocumentTextIcon } from '@heroicons/vue/24/outline'

const emit = defineEmits(['select-note'])

const { query, data, isLoading } = useNoteSearch()
</script>
