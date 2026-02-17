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
                class="calendar-day-cell group"
                :class="{
                    'is-today': day.isToday,
                    'is-other-month': !day.isCurrentMonth,
                }"
                @click="$emit('day-click', day.dateStr)"
            >
                <div class="flex items-center justify-between">
                    <span class="calendar-day-number" :class="{ 'is-today': day.isToday }">
                        {{ day.day }}
                    </span>
                    <!-- Source dots -->
                    <div class="calendar-source-dots">
                        <span
                            v-for="src in day.sourceDots"
                            :key="src.source"
                            class="w-1.5 h-1.5 rounded-full"
                            :style="{ backgroundColor: src.color }"
                            :title="src.label"
                        />
                    </div>
                </div>
                <div class="calendar-day-events">
                    <div
                        v-for="event in day.events.slice(0, 2)"
                        :key="event.id"
                        class="calendar-event-pill"
                        :style="{ backgroundColor: event.color + '30', borderLeftColor: event.color }"
                        @click.stop="$emit('event-click', event)"
                        :title="event.title"
                    >
                        <span class="truncate text-xs">{{ event.title }}</span>
                    </div>
                    <div v-if="day.events.length > 2" class="calendar-event-more">
                        +{{ day.events.length - 2 }} más
                    </div>
                </div>
                <!-- Quick create button -->
                <button
                    class="calendar-cell-add"
                    @click.stop="toggleQuickMenu(day.dateStr, $event)"
                    title="Crear..."
                >
                    <svg class="w-3.5 h-3.5" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="8" y1="3" x2="8" y2="13" /><line x1="3" y1="8" x2="13" y2="8" />
                    </svg>
                </button>
                <QuickCreateMenu
                    v-if="openMenuDate === day.dateStr"
                    :anchor-rect="menuAnchor"
                    @select="onMenuSelect(day.dateStr, $event)"
                    @close="openMenuDate = null"
                />
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import QuickCreateMenu from './QuickCreateMenu.vue'

const props = defineProps({
    events: { type: Array, default: () => [] },
    sourceStates: { type: Array, default: () => [] },
    currentDate: { type: Date, required: true },
})

const emit = defineEmits(['day-click', 'event-click', 'quick-create'])

const dayNames = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom']
const openMenuDate = ref(null)
const menuAnchor = ref({ left: 0, top: 0, right: 0, bottom: 0 })

function toggleQuickMenu(dateStr, e) {
    if (openMenuDate.value === dateStr) {
        openMenuDate.value = null
        return
    }
    const r = e.currentTarget.getBoundingClientRect()
    menuAnchor.value = { left: r.left, top: r.top, right: r.right, bottom: r.bottom }
    openMenuDate.value = dateStr
}

function onMenuSelect(dateStr, slot) {
    openMenuDate.value = null
    emit('quick-create', { dateStr, slot })
}

const calendarDays = computed(() => {
    const year = props.currentDate.getFullYear()
    const month = props.currentDate.getMonth()
    const today = new Date()
    const todayStr = formatDate(today)

    const firstDay = new Date(year, month, 1)
    let startOffset = firstDay.getDay() - 1
    if (startOffset < 0) startOffset = 6

    const lastDay = new Date(year, month + 1, 0)
    const totalDays = lastDay.getDate()

    const days = []

    for (let i = startOffset - 1; i >= 0; i--) {
        const d = new Date(year, month, -i)
        const dateStr = formatDate(d)
        days.push(buildDay(d.getDate(), dateStr, dateStr === todayStr, false))
    }

    for (let i = 1; i <= totalDays; i++) {
        const d = new Date(year, month, i)
        const dateStr = formatDate(d)
        days.push(buildDay(i, dateStr, dateStr === todayStr, true))
    }

    const remaining = 42 - days.length
    for (let i = 1; i <= remaining; i++) {
        const d = new Date(year, month + 1, i)
        const dateStr = formatDate(d)
        days.push(buildDay(i, dateStr, dateStr === todayStr, false))
    }

    return days
})

function buildDay(day, dateStr, isToday, isCurrentMonth) {
    return {
        day,
        dateStr,
        isToday,
        isCurrentMonth,
        events: getEventsForDate(dateStr),
        sourceDots: getSourceDots(dateStr),
    }
}

function getEventsForDate(dateStr) {
    return props.events.filter(e => e.start.substring(0, 10) === dateStr)
}

function getSourceDots(dateStr) {
    return props.sourceStates
        .filter(s => s.events.some(e => e.start.substring(0, 10) === dateStr))
        .map(s => ({ source: s.source, color: s.color, label: s.label }))
}

function formatDate(d) {
    return d.getFullYear() + '-' +
        String(d.getMonth() + 1).padStart(2, '0') + '-' +
        String(d.getDate()).padStart(2, '0')
}
</script>
