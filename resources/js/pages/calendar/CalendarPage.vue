<template>
    <div class="p-8">
        <!-- Header -->
        <div class="calendar-header">
            <div class="flex items-center gap-4">
                <h1 class="page-title">Calendario</h1>
                <div class="flex items-center gap-1">
                    <button @click="prev" class="btn-icon">
                        <ChevronLeftIcon class="w-5 h-5" />
                    </button>
                    <button @click="goToday" class="btn-secondary text-xs px-3 py-1">Hoy</button>
                    <button @click="next" class="btn-icon">
                        <ChevronRightIcon class="w-5 h-5" />
                    </button>
                </div>
                <h2 class="text-lg font-medium text-surface-200">{{ headerLabel }}</h2>
            </div>
            <div class="flex items-center gap-3">
                <div class="calendar-view-switcher">
                    <button
                        v-for="v in views"
                        :key="v.key"
                        @click="currentView = v.key"
                        class="calendar-view-btn"
                        :class="{ active: currentView === v.key }"
                    >
                        {{ v.label }}
                    </button>
                </div>
                <button @click="showEventModal = true; editingEvent = null" class="btn-add">
                    <PlusIcon class="w-4 h-4" />
                    Nuevo evento
                </button>
            </div>
        </div>

        <!-- Source filters -->
        <div v-if="sources.length > 1" class="flex flex-wrap gap-2 mb-4">
            <button
                v-for="src in sources"
                :key="src.slug"
                @click="toggleSource(src.slug)"
                class="calendar-source-chip"
                :class="{ active: activeSources.includes(src.slug) }"
                :style="activeSources.includes(src.slug) ? { backgroundColor: src.color + '20', borderColor: src.color, color: src.color } : {}"
            >
                <span class="w-2 h-2 rounded-full inline-block" :style="{ backgroundColor: src.color }" />
                {{ src.label }}
            </button>
        </div>

        <!-- Loading -->
        <div v-if="isLoading" class="flex justify-center py-20">
            <div class="animate-spin w-8 h-8 border-2 border-primary-400 border-t-transparent rounded-full" />
        </div>

        <!-- Views -->
        <template v-else>
            <MonthView
                v-if="currentView === 'month'"
                :events="filteredEvents"
                :current-date="currentDate"
                @day-click="handleDayClick"
                @event-click="handleEventClick"
            />
            <WeekView
                v-if="currentView === 'week'"
                :events="filteredEvents"
                :current-date="currentDate"
                @day-click="handleDayClick"
                @event-click="handleEventClick"
            />
            <DayView
                v-if="currentView === 'day'"
                :events="filteredEvents"
                :current-date="currentDate"
                @day-click="handleDayClick"
                @event-click="handleEventClick"
            />
        </template>

        <!-- Event Modal -->
        <CalendarEventModal
            v-if="showEventModal"
            :event="editingEvent"
            :initial-date="selectedDate"
            @close="showEventModal = false"
            @saved="showEventModal = false"
        />

        <!-- Event Detail Panel -->
        <EventDetailPanel
            v-if="selectedEvent"
            :event="selectedEvent"
            @close="selectedEvent = null"
            @edit="openEditEvent"
            @deleted="selectedEvent = null"
        />
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useCalendarEvents } from '@/composables/useCalendar'
import MonthView from '@/components/calendar/MonthView.vue'
import WeekView from '@/components/calendar/WeekView.vue'
import DayView from '@/components/calendar/DayView.vue'
import CalendarEventModal from '@/components/calendar/CalendarEventModal.vue'
import EventDetailPanel from '@/components/calendar/EventDetailPanel.vue'
import { PlusIcon, ChevronLeftIcon, ChevronRightIcon } from '@heroicons/vue/24/outline'

const currentDate = ref(new Date())
const currentView = ref('month')
const activeSources = ref([])
const showEventModal = ref(false)
const editingEvent = ref(null)
const selectedEvent = ref(null)
const selectedDate = ref(null)

const views = [
    { key: 'month', label: 'Mes' },
    { key: 'week', label: 'Semana' },
    { key: 'day', label: 'Día' },
]

const startDate = computed(() => {
    const d = new Date(currentDate.value)
    if (currentView.value === 'month') {
        d.setDate(1)
        d.setDate(d.getDate() - d.getDay() + 1) // Monday start
        if (d.getDay() === 0) d.setDate(d.getDate() - 6) // adjust Sunday
    } else if (currentView.value === 'week') {
        const day = d.getDay()
        const diff = d.getDate() - day + (day === 0 ? -6 : 1)
        d.setDate(diff)
    }
    return formatDate(d)
})

const endDate = computed(() => {
    const d = new Date(currentDate.value)
    if (currentView.value === 'month') {
        d.setMonth(d.getMonth() + 1, 0) // last day of month
        const remaining = 7 - d.getDay()
        if (remaining < 7) d.setDate(d.getDate() + remaining)
    } else if (currentView.value === 'week') {
        const start = new Date(startDate.value)
        d.setTime(start.getTime())
        d.setDate(d.getDate() + 6)
    }
    return formatDate(d)
})

const { data: calendarData, isLoading } = useCalendarEvents(startDate, endDate, activeSources)

const events = computed(() => calendarData.value?.data ?? [])
const sources = computed(() => calendarData.value?.sources ?? [])

const filteredEvents = computed(() => {
    if (!activeSources.value.length) return events.value
    return events.value.filter(e => activeSources.value.includes(e.source))
})

const headerLabel = computed(() => {
    const d = currentDate.value
    const months = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre']
    if (currentView.value === 'month') {
        return `${months[d.getMonth()]} ${d.getFullYear()}`
    }
    if (currentView.value === 'week') {
        const start = new Date(startDate.value)
        const end = new Date(endDate.value)
        return `${start.getDate()} - ${end.getDate()} ${months[end.getMonth()]} ${end.getFullYear()}`
    }
    return `${d.getDate()} ${months[d.getMonth()]} ${d.getFullYear()}`
})

function prev() {
    const d = new Date(currentDate.value)
    if (currentView.value === 'month') d.setMonth(d.getMonth() - 1)
    else if (currentView.value === 'week') d.setDate(d.getDate() - 7)
    else d.setDate(d.getDate() - 1)
    currentDate.value = d
}

function next() {
    const d = new Date(currentDate.value)
    if (currentView.value === 'month') d.setMonth(d.getMonth() + 1)
    else if (currentView.value === 'week') d.setDate(d.getDate() + 7)
    else d.setDate(d.getDate() + 1)
    currentDate.value = d
}

function goToday() {
    currentDate.value = new Date()
}

function toggleSource(slug) {
    const idx = activeSources.value.indexOf(slug)
    if (idx >= 0) {
        activeSources.value = activeSources.value.filter(s => s !== slug)
    } else {
        activeSources.value = [...activeSources.value, slug]
    }
}

function handleDayClick(dateStr) {
    selectedDate.value = dateStr
    showEventModal.value = true
    editingEvent.value = null
}

function handleEventClick(event) {
    selectedEvent.value = event
}

function openEditEvent(event) {
    selectedEvent.value = null
    editingEvent.value = event
    showEventModal.value = true
}

function formatDate(d) {
    return d.getFullYear() + '-' +
        String(d.getMonth() + 1).padStart(2, '0') + '-' +
        String(d.getDate()).padStart(2, '0')
}
</script>
