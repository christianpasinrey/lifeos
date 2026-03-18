<template>
    <nav v-if="note" class="flex items-center gap-1 text-xs text-surface-500 min-w-0">
        <button class="hover:text-surface-300 transition-colors flex-shrink-0" @click="$router.push({ name: 'notes' })">
            Notas
        </button>
        <template v-if="note.folder">
            <ChevronRightIcon class="w-3 h-3 flex-shrink-0" />
            <span class="truncate text-surface-400">{{ note.folder.name }}</span>
        </template>
        <ChevronRightIcon class="w-3 h-3 flex-shrink-0" />
        <span class="truncate text-surface-200 font-medium">{{ note.title }}</span>
    </nav>
</template>

<script setup>
import { ChevronRightIcon } from '@heroicons/vue/24/outline'

defineProps({
    note: { type: Object, default: null },
})
</script>
