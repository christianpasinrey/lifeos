<template>
    <div class="backlinks-panel">
        <!-- Linked mentions -->
        <div class="px-2 py-1.5">
            <span class="text-[0.625rem] font-semibold text-surface-500 uppercase tracking-wider">Enlaces entrantes</span>
        </div>

        <div v-if="note?.incomingLinks?.length" class="space-y-0.5 mb-4">
            <div
                v-for="link in note.incomingLinks"
                :key="link.id"
                class="file-item"
                @click="$emit('select-note', link.source)"
            >
                <LinkIcon class="w-3.5 h-3.5 text-primary-400 flex-shrink-0" />
                <div class="min-w-0">
                    <p class="text-sm text-surface-300 truncate">{{ link.source?.title }}</p>
                    <p v-if="link.context" class="text-[0.625rem] text-surface-600 truncate">{{ link.context }}</p>
                </div>
            </div>
        </div>
        <div v-else class="px-2 py-2 mb-4">
            <p class="text-xs text-surface-600">Sin enlaces entrantes</p>
        </div>

        <!-- Outgoing links -->
        <div class="px-2 py-1.5">
            <span class="text-[0.625rem] font-semibold text-surface-500 uppercase tracking-wider">Enlaces salientes</span>
        </div>

        <div v-if="note?.outgoingLinks?.length" class="space-y-0.5">
            <div
                v-for="link in note.outgoingLinks"
                :key="link.id"
                class="file-item"
                @click="link.target && $emit('select-note', link.target)"
            >
                <ArrowTopRightOnSquareIcon class="w-3.5 h-3.5 flex-shrink-0" :class="link.target ? 'text-surface-400' : 'text-danger-400'" />
                <span class="truncate text-sm" :class="link.target ? 'text-surface-300' : 'text-danger-400 line-through'">
                    {{ link.target?.title || link.target_title || 'Sin resolver' }}
                </span>
            </div>
        </div>
        <div v-else class="px-2 py-2">
            <p class="text-xs text-surface-600">Sin enlaces salientes</p>
        </div>
    </div>
</template>

<script setup>
import { LinkIcon, ArrowTopRightOnSquareIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    note: { type: Object, default: null },
})

const emit = defineEmits(['select-note'])
</script>
