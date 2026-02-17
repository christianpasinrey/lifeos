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

        <!-- Source filters (dynamic from calendarSlots) -->
        <div v-if="calendarSlots.length > 1" class="flex flex-wrap gap-2 mb-4">
            <button
                v-for="slot in calendarSlots"
                :key="slot.source"
                @click="toggleSource(slot.source)"
                class="calendar-source-chip"
                :class="{ active: activeSources.includes(slot.source) }"
                :style="activeSources.includes(slot.source) ? { backgroundColor: slot.color + '20', borderColor: slot.color, color: slot.color } : {}"
            >
                <span class="w-2 h-2 rounded-full inline-block" :style="{ backgroundColor: slot.color }" />
                {{ slot.label }}
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
                :source-states="filteredSourceStates"
                :current-date="currentDate"
                @day-click="handleDayClick"
                @event-click="handleEventClick"
                @quick-create="handleQuickCreateFromCell"
            />
            <WeekView
                v-if="currentView === 'week'"
                :events="filteredEvents"
                :source-states="filteredSourceStates"
                :current-date="currentDate"
                @day-click="handleDayClick"
                @event-click="handleEventClick"
                @quick-create="handleQuickCreateFromCell"
            />
            <DayView
                v-if="currentView === 'day'"
                :events="filteredEvents"
                :source-states="filteredSourceStates"
                :current-date="currentDate"
                @day-click="handleDayClick"
                @event-click="handleEventClick"
                @quick-create="handleQuickCreateFromCell"
            />
        </template>

        <!-- Event Modal (for native calendar events) -->
        <CalendarEventModal
            v-if="showEventModal"
            :event="editingEvent"
            :initial-date="selectedDate"
            @close="showEventModal = false"
            @saved="onEventSaved"
        />

        <!-- Event Detail Panel -->
        <EventDetailPanel
            v-if="selectedEvent"
            :event="selectedEvent"
            @close="selectedEvent = null"
            @edit="openEditEvent"
            @deleted="selectedEvent = null"
        />

        <!-- Day Summary Panel -->
        <DaySummaryPanel
            v-if="showDaySummary && !selectedEvent && !showEventModal && !quickCreateSlot"
            :date="summaryDate"
            :source-states="filteredSourceStates"
            @close="showDaySummary = false"
            @event-click="handleEventClick"
            @quick-create="openQuickMenuFromPanel"
        />

        <!-- Quick Create Menu -->
        <QuickCreateMenu
            v-if="showQuickMenu"
            :position="quickMenuPosition"
            @select="handleQuickMenuSelect"
            @close="showQuickMenu = false"
        />

        <!-- Dynamic Quick Create Panel (Tasks, Finance, etc.) -->
        <component
            v-if="quickCreateSlot"
            :is="quickCreateSlot.quickCreateComponent"
            :date="quickCreateDate"
            @close="quickCreateSlot = null"
            @saved="onQuickCreateSaved"
        />
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useCalendarSources } from '@/composables/useCalendarSources'
import MonthView from '@/components/calendar/MonthView.vue'
import WeekView from '@/components/calendar/WeekView.vue'
import DayView from '@/components/calendar/DayView.vue'
import CalendarEventModal from '@/components/calendar/CalendarEventModal.vue'
import EventDetailPanel from '@/components/calendar/EventDetailPanel.vue'
import DaySummaryPanel from '@/components/calendar/DaySummaryPanel.vue'
import QuickCreateMenu from '@/components/calendar/QuickCreateMenu.vue'
import { PlusIcon, ChevronLeftIcon, ChevronRightIcon } from '@heroicons/vue/24/outline'

const currentDate = ref(new Date())
const currentView = ref('month')
const activeSources = ref([])
const showEventModal = ref(false)
const editingEvent = ref(null)
const selectedEvent = ref(null)
const selectedDate = ref(null)

// Day summary panel state
const showDaySummary = ref(false)
const summaryDate = ref(null)

// Quick create state
const showQuickMenu = ref(false)
const quickMenuPosition = ref({ x: 0, y: 0 })
const quickMenuDate = ref(null)
const quickCreateSlot = ref(null)
const quickCreateDate = ref(null)

const views = [
    { key: 'month', label: 'Mes' },
    { key: 'week', label: 'Semana' },
    { key: 'day', label: 'Día' },
]

const startDate = computed(() => {
    const d = new Date(currentDate.value)
    if (currentView.value === 'month') {
        d.setDate(1)
        d.setDate(d.getDate() - d.getDay() + 1)
        if (d.getDay() === 0) d.setDate(d.getDate() - 6)
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
        d.setMonth(d.getMonth() + 1, 0)
        const remaining = 7 - d.getDay()
        if (remaining < 7) d.setDate(d.getDate() + remaining)
    } else if (currentView.value === 'week') {
        const start = new Date(startDate.value)
        d.setTime(start.getTime())
        d.setDate(d.getDate() + 6)
    }
    return formatDate(d)
})

const { allEvents, isLoading, sourceStates, calendarSlots, invalidateAll } = useCalendarSources(startDate, endDate)

const filteredEvents = computed(() => {
    if (!activeSources.value.length) return allEvents.value
    return allEvents.value.filter(e => activeSources.value.includes(e.source))
})

const filteredSourceStates = computed(() => {
    if (!activeSources.value.length) return sourceStates.value
    return sourceStates.value.filter(s => activeSources.value.includes(s.source))
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
    const dateOnly = dateStr.substring(0, 10)
    const hasEvents = allEvents.value.some(e => e.start.substring(0, 10) === dateOnly)

    if (hasEvents) {
        // Open day summary panel
        summaryDate.value = dateOnly
        showDaySummary.value = true
    } else {
        // Open event creation modal with date
        selectedDate.value = dateStr
        showEventModal.value = true
        editingEvent.value = null
    }
}

function handleQuickCreateFromCell({ dateStr, event }) {
    quickMenuDate.value = dateStr
    quickMenuPosition.value = { x: event.clientX, y: event.clientY }
    showQuickMenu.value = true
}

function openQuickMenuFromPanel({ x, y }) {
    quickMenuPosition.value = { x, y }
    quickMenuDate.value = summaryDate.value
    showQuickMenu.value = true
}

function handleQuickMenuSelect(slot) {
    showQuickMenu.value = false

    if (slot.source === 'calendar') {
        // Use the existing event modal
        selectedDate.value = quickMenuDate.value
        showEventModal.value = true
        editingEvent.value = null
    } else {
        quickCreateSlot.value = slot
        quickCreateDate.value = quickMenuDate.value
    }
}

function handleEventClick(event) {
    selectedEvent.value = event
    showDaySummary.value = false
}

function openEditEvent(event) {
    selectedEvent.value = null
    editingEvent.value = event
    showEventModal.value = true
}

function onEventSaved() {
    showEventModal.value = false
    invalidateAll()
}

function onQuickCreateSaved() {
    quickCreateSlot.value = null
    invalidateAll()
}

function formatDate(d) {
    return d.getFullYear() + '-' +
        String(d.getMonth() + 1).padStart(2, '0') + '-' +
        String(d.getDate()).padStart(2, '0')
}
</script>
