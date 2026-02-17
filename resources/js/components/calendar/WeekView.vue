<template>
    <div class="calendar-week liquid-glass liquid-glass-card p-0 overflow-hidden">
        <!-- Header with day names -->
        <div class="calendar-week-header">
            <div class="calendar-week-time-gutter" />
            <div
                v-for="day in weekDays"
                :key="day.dateStr"
                class="calendar-week-day-header"
                :class="{ 'is-today': day.isToday }"
            >
                <span class="text-xs text-surface-400">{{ day.dayName }}</span>
                <span class="text-lg font-semibold" :class="day.isToday ? 'text-primary-400' : 'text-surface-200'">
                    {{ day.day }}
                </span>
                <!-- Source dots -->
                <div class="calendar-source-dots mt-0.5">
                    <span
                        v-for="src in getSourceDots(day.dateStr)"
                        :key="src.source"
                        class="w-1.5 h-1.5 rounded-full"
                        :style="{ backgroundColor: src.color }"
                        :title="src.label"
                    />
                </div>
            </div>
        </div>

        <!-- All-day events banner -->
        <div v-if="allDayEvents.length" class="calendar-week-allday">
            <div class="calendar-week-time-gutter text-xs text-surface-500 py-1">Todo el día</div>
            <div
                v-for="day in weekDays"
                :key="'allday-' + day.dateStr"
                class="calendar-week-allday-cell"
            >
                <div
                    v-for="event in getAllDayEventsForDate(day.dateStr)"
                    :key="event.id"
                    class="calendar-event-pill"
                    :style="{ backgroundColor: event.color + '30', borderLeftColor: event.color }"
                    @click="$emit('event-click', event)"
                >
                    <span class="truncate text-xs">{{ event.title }}</span>
                </div>
            </div>
        </div>

        <!-- Time grid -->
        <div class="calendar-week-body">
            <div class="calendar-week-grid">
                <!-- Hour rows -->
                <div v-for="hour in hours" :key="hour" class="calendar-week-row">
                    <div class="calendar-week-time-gutter">
                        <span class="text-xs text-surface-500">{{ String(hour).padStart(2, '0') }}:00</span>
                    </div>
                    <div
                        v-for="day in weekDays"
                        :key="day.dateStr + '-' + hour"
                        class="calendar-week-cell group"
                        @click="$emit('day-click', day.dateStr + 'T' + String(hour).padStart(2, '0') + ':00')"
                    >
                        <button
                            class="calendar-cell-add-inline"
                            @click.stop="$emit('quick-create', { dateStr: day.dateStr, event: $event })"
                        >
                            <svg class="w-3 h-3" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="8" y1="3" x2="8" y2="13" /><line x1="3" y1="8" x2="13" y2="8" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Positioned timed events -->
                <div
                    v-for="event in timedEventsPositioned"
                    :key="event.id"
                    class="calendar-week-event"
                    :style="event.style"
                    @click="$emit('event-click', event.event)"
                >
                    <div class="text-xs font-medium truncate">{{ event.event.title }}</div>
                    <div class="text-xs text-surface-400 truncate">{{ event.time }}</div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    events: { type: Array, default: () => [] },
    sourceStates: { type: Array, default: () => [] },
    currentDate: { type: Date, required: true },
})

defineEmits(['event-click', 'day-click', 'quick-create'])

const dayNames = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom']
const hours = Array.from({ length: 24 }, (_, i) => i)

const weekStart = computed(() => {
    const d = new Date(props.currentDate)
    const day = d.getDay()
    const diff = d.getDate() - day + (day === 0 ? -6 : 1)
    return new Date(d.setDate(diff))
})

const weekDays = computed(() => {
    const today = formatDate(new Date())
    return Array.from({ length: 7 }, (_, i) => {
        const d = new Date(weekStart.value)
        d.setDate(d.getDate() + i)
        const dateStr = formatDate(d)
        return {
            day: d.getDate(),
            dayName: dayNames[i],
            dateStr,
            isToday: dateStr === today,
            index: i,
        }
    })
})

const allDayEvents = computed(() => props.events.filter(e => e.all_day))
const timedEvents = computed(() => props.events.filter(e => !e.all_day))

function getAllDayEventsForDate(dateStr) {
    return allDayEvents.value.filter(e => e.start.substring(0, 10) === dateStr)
}

function getSourceDots(dateStr) {
    return props.sourceStates
        .filter(s => s.events.some(e => e.start.substring(0, 10) === dateStr))
        .map(s => ({ source: s.source, color: s.color, label: s.label }))
}

const timedEventsPositioned = computed(() => {
    return timedEvents.value.map(event => {
        const eventDate = event.start.substring(0, 10)
        const dayIdx = weekDays.value.findIndex(d => d.dateStr === eventDate)
        if (dayIdx < 0) return null

        const timeParts = event.start.substring(11, 16).split(':')
        const startHour = parseInt(timeParts[0]) + parseInt(timeParts[1]) / 60

        let duration = 1
        if (event.end) {
            const endParts = event.end.substring(11, 16).split(':')
            const endHour = parseInt(endParts[0]) + parseInt(endParts[1]) / 60
            duration = Math.max(0.5, endHour - startHour)
        }

        const colWidth = 100 / 7
        const left = `calc(60px + ${dayIdx * colWidth}%)`
        const width = `calc(${colWidth}% - 4px)`
        const top = `${startHour * 60}px`
        const height = `${duration * 60}px`

        return {
            event,
            time: event.start.substring(11, 16),
            style: {
                position: 'absolute',
                left,
                width,
                top,
                height,
                minHeight: '24px',
                backgroundColor: event.color + '30',
                borderLeft: `3px solid ${event.color}`,
                borderRadius: '4px',
                padding: '2px 6px',
                zIndex: 10,
                cursor: 'pointer',
            },
        }
    }).filter(Boolean)
})

function formatDate(d) {
    return d.getFullYear() + '-' +
        String(d.getMonth() + 1).padStart(2, '0') + '-' +
        String(d.getDate()).padStart(2, '0')
}
</script>
