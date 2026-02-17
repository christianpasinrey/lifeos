<template>
    <div class="space-y-1.5">
        <div
            v-for="event in events"
            :key="event.id"
            class="flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-white/[0.06] cursor-pointer transition-colors"
            @click="$emit('event-click', event)"
        >
            <span
                class="w-4 h-4 rounded border flex items-center justify-center flex-shrink-0"
                :class="event.meta?.completed
                    ? 'bg-emerald-500/20 border-emerald-500 text-emerald-400'
                    : 'border-surface-600 text-transparent'"
            >
                <svg v-if="event.meta?.completed" class="w-3 h-3" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="2 6 5 9 10 3" />
                </svg>
            </span>
            <span class="text-sm truncate" :class="event.meta?.completed ? 'text-surface-500 line-through' : 'text-surface-200'">
                {{ event.title }}
            </span>
            <span
                v-if="event.meta?.priority"
                class="ml-auto text-xs px-1.5 py-0.5 rounded font-medium flex-shrink-0"
                :class="priorityClass(event.meta.priority)"
            >
                {{ event.meta.priority }}
            </span>
        </div>
    </div>
</template>

<script setup>
defineProps({
    events: { type: Array, required: true },
    date: { type: String, required: true },
})

defineEmits(['event-click'])

function priorityClass(priority) {
    const map = {
        urgent: 'bg-red-500/20 text-red-400',
        high: 'bg-orange-500/20 text-orange-400',
        medium: 'bg-yellow-500/20 text-yellow-400',
        low: 'bg-blue-500/20 text-blue-400',
    }
    return map[priority] || 'bg-surface-700 text-surface-400'
}
</script>
