<template>
    <div class="calendar-day">
        <!-- All-day events -->
        <div v-if="allDayEvents.length" class="calendar-day-allday liquid-glass liquid-glass-card">
            <div class="text-xs text-surface-500 mb-2">Todo el día</div>
            <div class="flex flex-wrap gap-2">
                <div
                    v-for="event in allDayEvents"
                    :key="event.id"
                    class="calendar-event-pill"
                    :style="{ backgroundColor: event.color + '30', borderLeftColor: event.color }"
                    @click="$emit('event-click', event)"
                >
                    <span class="truncate text-sm">{{ event.title }}</span>
                </div>
            </div>
        </div>

        <!-- Time grid -->
        <div class="calendar-day-body liquid-glass liquid-glass-card p-0 overflow-hidden">
            <div v-for="hour in hours" :key="hour" class="calendar-day-row">
                <div class="calendar-day-time">
                    <span class="text-xs text-surface-500">{{ String(hour).padStart(2, '0') }}:00</span>
                </div>
                <div
                    class="calendar-day-slot"
                    @click="$emit('day-click', dateStr + 'T' + String(hour).padStart(2, '0') + ':00')"
                >
                    <!-- Events at this hour -->
                    <div
                        v-for="event in getEventsAtHour(hour)"
                        :key="event.id"
                        class="calendar-day-event"
                        :style="{ backgroundColor: event.color + '20', borderLeftColor: event.color }"
                        @click="$emit('event-click', event)"
                    >
                        <div class="flex items-center gap-2">
                            <span
                                class="w-2 h-2 rounded-full flex-shrink-0"
                                :style="{ backgroundColor: event.color }"
                            />
                            <span class="font-medium text-sm text-surface-100">{{ event.title }}</span>
                        </div>
                        <div class="text-xs text-surface-400 mt-0.5">
                            {{ event.start.substring(11, 16) }}
                            <template v-if="event.end"> - {{ event.end.substring(11, 16) }}</template>
                        </div>
                        <div v-if="event.description" class="text-xs text-surface-500 mt-1 line-clamp-2">
                            {{ event.description }}
                        </div>
                        <div class="text-xs text-surface-600 mt-1">{{ event.source }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    events: { type: Array, default: () => [] },
    currentDate: { type: Date, required: true },
})

defineEmits(['event-click', 'day-click'])

const hours = Array.from({ length: 24 }, (_, i) => i)

const dateStr = computed(() => formatDate(props.currentDate))

const dayEvents = computed(() =>
    props.events.filter(e => e.start.substring(0, 10) === dateStr.value)
)

const allDayEvents = computed(() => dayEvents.value.filter(e => e.all_day))
const timedEvents = computed(() => dayEvents.value.filter(e => !e.all_day))

function getEventsAtHour(hour) {
    return timedEvents.value.filter(e => {
        const h = parseInt(e.start.substring(11, 13))
        return h === hour
    })
}

function formatDate(d) {
    return d.getFullYear() + '-' +
        String(d.getMonth() + 1).padStart(2, '0') + '-' +
        String(d.getDate()).padStart(2, '0')
}
</script>
