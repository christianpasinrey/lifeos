<template>
    <div class="p-8">
        <div class="habits-header">
            <div>
                <h1 class="page-title">Vista Semanal</h1>
                <p class="page-subtitle">{{ weekLabel }}</p>
            </div>
            <div class="flex items-center gap-2">
                <button @click="prevWeek" class="btn-icon">
                    <ChevronLeftIcon class="w-5 h-5" />
                </button>
                <button @click="goToday" class="btn-secondary text-xs px-3 py-1.5">Hoy</button>
                <button @click="nextWeek" class="btn-icon">
                    <ChevronRightIcon class="w-5 h-5" />
                </button>
            </div>
        </div>

        <div v-if="isLoading" class="text-center py-12 text-surface-400">Cargando...</div>

        <div v-else-if="weekData" class="liquid-glass liquid-glass-card p-6">
            <!-- Header row -->
            <div class="flex items-center gap-3 mb-4">
                <div class="w-36 shrink-0" />
                <div class="flex gap-1.5">
                    <div
                        v-for="date in weekData.dates"
                        :key="date"
                        class="w-10 text-center text-xs font-medium"
                        :class="isToday(date) ? 'text-primary-400' : 'text-surface-400'"
                    >
                        {{ dayLabel(date) }}
                    </div>
                </div>
            </div>

            <!-- Habit rows -->
            <div class="space-y-2">
                <HabitWeekRow
                    v-for="habit in weekData.habits"
                    :key="habit.id"
                    :habit="habit"
                    @toggle-date="handleToggleDate"
                />
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useHabitsWeek, useToggleHabitDate } from '@/composables/useHabits'
import HabitWeekRow from '@/components/habits/HabitWeekRow.vue'
import { ChevronLeftIcon, ChevronRightIcon } from '@heroicons/vue/24/outline'

const startDate = ref(getMonday(new Date()))

function getMonday(d) {
    const date = new Date(d)
    const day = date.getDay()
    const diff = date.getDate() - day + (day === 0 ? -6 : 1)
    date.setDate(diff)
    return date.toISOString().split('T')[0]
}

const { data: weekData, isLoading } = useHabitsWeek(startDate)
const { mutate: toggleDate } = useToggleHabitDate()

const weekLabel = computed(() => {
    if (!weekData.value) return ''
    const start = new Date(weekData.value.start)
    const end = new Date(weekData.value.end)
    const opts = { day: 'numeric', month: 'short' }
    return `${start.toLocaleDateString('es', opts)} — ${end.toLocaleDateString('es', opts)}`
})

function dayLabel(dateStr) {
    const d = new Date(dateStr + 'T12:00:00')
    const days = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb']
    return days[d.getDay()]
}

function isToday(dateStr) {
    return dateStr === new Date().toISOString().split('T')[0]
}

function prevWeek() {
    const d = new Date(startDate.value)
    d.setDate(d.getDate() - 7)
    startDate.value = d.toISOString().split('T')[0]
}

function nextWeek() {
    const d = new Date(startDate.value)
    d.setDate(d.getDate() + 7)
    startDate.value = d.toISOString().split('T')[0]
}

function goToday() {
    startDate.value = getMonday(new Date())
}

function handleToggleDate(habitId, date) {
    toggleDate({ habitId, date })
}
</script>
