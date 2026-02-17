<template>
    <div class="space-y-1.5">
        <div
            v-for="event in events"
            :key="event.id"
            class="flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-white/[0.06] cursor-pointer transition-colors"
            @click="$emit('event-click', event)"
        >
            <span class="w-2 h-2 rounded-full flex-shrink-0" :style="{ backgroundColor: event.color }" />
            <span v-if="!event.all_day" class="text-xs text-surface-400 w-10 flex-shrink-0">
                {{ event.start.substring(11, 16) }}
            </span>
            <span v-else class="text-xs text-surface-500 w-10 flex-shrink-0">Día</span>
            <span class="text-sm text-surface-200 truncate">{{ event.title }}</span>
        </div>
    </div>
</template>

<script setup>
defineProps({
    events: { type: Array, required: true },
    date: { type: String, required: true },
})

defineEmits(['event-click'])
</script>
