<template>
    <div class="space-y-1.5">
        <div
            v-for="event in events"
            :key="event.id"
            class="flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-white/[0.06] transition-colors"
        >
            <span
                class="w-4 h-4 rounded-full flex items-center justify-center flex-shrink-0"
                :class="event.meta?.completed ? 'bg-emerald-500/20' : 'bg-white/[0.06]'"
            >
                <svg v-if="event.meta?.completed" class="w-3 h-3 text-emerald-400" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="2 6 5 9 10 3" />
                </svg>
            </span>
            <span
                class="w-2.5 h-2.5 rounded-full flex-shrink-0"
                :style="{ backgroundColor: event.color }"
            />
            <span class="text-sm text-surface-200 truncate">{{ event.title }}</span>
        </div>
    </div>
</template>

<script setup>
defineProps({
    events: { type: Array, required: true },
    date: { type: String, required: true },
})
</script>
