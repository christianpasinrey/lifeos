<template>
    <div class="calendar-month liquid-glass liquid-glass-card p-0 overflow-hidden">
        <!-- Day headers -->
        <div class="calendar-month-header">
            <div v-for="day in dayNames" :key="day" class="calendar-day-header">
                {{ day }}
            </div>
        </div>

        <!-- Weeks -->
        <div class="calendar-month-grid">
            <div
                v-for="(day, idx) in calendarDays"
                :key="idx"
                class="calendar-day-cell"
                :class="{
                    'is-today': day.isToday,
                    'is-other-month': !day.isCurrentMonth,
                }"
                @click="$emit('day-click', day.dateStr)"
            >
                <span class="calendar-day-number" :class="{ 'is-today': day.isToday }">
                    {{ day.day }}
                </span>
                <div class="calendar-day-events">
                    <div
                        v-for="event in day.events.slice(0, 3)"
                        :key="event.id"
                        class="calendar-event-pill"
                        :style="{ backgroundColor: event.color + '30', borderLeftColor: event.color }"
                        @click.stop="$emit('event-click', event)"
                        :title="event.title"
                    >
                        <span class="truncate text-xs">{{ event.title }}</span>
                    </div>
                    <div v-if="day.events.length > 3" class="calendar-event-more">
                        +{{ day.events.length - 3 }} más
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

defineEmits(['day-click', 'event-click'])

const dayNames = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom']

const calendarDays = computed(() => {
    const year = props.currentDate.getFullYear()
    const month = props.currentDate.getMonth()
    const today = new Date()
    const todayStr = formatDate(today)

    // First day of month
    const firstDay = new Date(year, month, 1)
    // Day of week (0=Sun), adjust for Monday start
    let startOffset = firstDay.getDay() - 1
    if (startOffset < 0) startOffset = 6

    // Last day of month
    const lastDay = new Date(year, month + 1, 0)
    const totalDays = lastDay.getDate()

    const days = []

    // Previous month days
    for (let i = startOffset - 1; i >= 0; i--) {
        const d = new Date(year, month, -i)
        const dateStr = formatDate(d)
        days.push({
            day: d.getDate(),
            dateStr,
            isToday: dateStr === todayStr,
            isCurrentMonth: false,
            events: getEventsForDate(dateStr),
        })
    }

    // Current month days
    for (let i = 1; i <= totalDays; i++) {
        const d = new Date(year, month, i)
        const dateStr = formatDate(d)
        days.push({
            day: i,
            dateStr,
            isToday: dateStr === todayStr,
            isCurrentMonth: true,
            events: getEventsForDate(dateStr),
        })
    }

    // Next month days to fill 6 rows
    const remaining = 42 - days.length
    for (let i = 1; i <= remaining; i++) {
        const d = new Date(year, month + 1, i)
        const dateStr = formatDate(d)
        days.push({
            day: i,
            dateStr,
            isToday: dateStr === todayStr,
            isCurrentMonth: false,
            events: getEventsForDate(dateStr),
        })
    }

    return days
})

function getEventsForDate(dateStr) {
    return props.events.filter(e => {
        const eventDate = e.start.substring(0, 10)
        return eventDate === dateStr
    })
}

function formatDate(d) {
    return d.getFullYear() + '-' +
        String(d.getMonth() + 1).padStart(2, '0') + '-' +
        String(d.getDate()).padStart(2, '0')
}
</script>
