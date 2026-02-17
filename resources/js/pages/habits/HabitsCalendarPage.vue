<template>
    <div class="p-8">
        <div class="habits-header">
            <div>
                <h1 class="page-title">Calendario</h1>
                <p class="page-subtitle">{{ monthLabel }}</p>
            </div>
            <div class="flex items-center gap-2">
                <button @click="prevMonth" class="btn-icon">
                    <ChevronLeftIcon class="w-5 h-5" />
                </button>
                <button @click="goToday" class="btn-secondary text-xs px-3 py-1.5">Hoy</button>
                <button @click="nextMonth" class="btn-icon">
                    <ChevronRightIcon class="w-5 h-5" />
                </button>
            </div>
        </div>

        <div v-if="isLoading" class="text-center py-12 text-surface-400">Cargando...</div>

        <div v-else-if="calData" class="liquid-glass liquid-glass-card p-6">
            <!-- Legend -->
            <div class="flex flex-wrap gap-3 mb-4">
                <div v-for="h in calData.habits" :key="h.id" class="flex items-center gap-1.5 text-xs text-surface-300">
                    <div class="w-2.5 h-2.5 rounded-full" :style="{ backgroundColor: h.color }" />
                    {{ h.name }}
                </div>
            </div>

            <!-- Day headers -->
            <div class="grid grid-cols-7 gap-1 mb-1">
                <div v-for="day in ['Lun','Mar','Mié','Jue','Vie','Sáb','Dom']" :key="day"
                     class="text-center text-xs font-medium text-surface-400 py-1">
                    {{ day }}
                </div>
            </div>

            <!-- Calendar grid -->
            <div class="grid grid-cols-7 gap-1">
                <!-- Empty cells for offset -->
                <div v-for="n in startOffset" :key="'e'+n" class="aspect-square" />

                <!-- Day cells -->
                <div
                    v-for="day in daysInMonth"
                    :key="day"
                    class="aspect-square rounded-lg border border-white/[0.06] p-1 flex flex-col items-center"
                    :class="isToday(day) ? 'border-primary-500/40 bg-primary-500/5' : ''"
                >
                    <span class="text-[10px] text-surface-400 mb-0.5">{{ day }}</span>
                    <div class="flex flex-wrap gap-0.5 justify-center">
                        <div
                            v-for="dot in getDots(day)"
                            :key="dot.habit_id"
                            class="w-1.5 h-1.5 rounded-full"
                            :style="{ backgroundColor: dot.color }"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useHabitsCalendar } from '@/composables/useHabits'
import { ChevronLeftIcon, ChevronRightIcon } from '@heroicons/vue/24/outline'

const currentMonth = ref(new Date().toISOString().slice(0, 7))

const { data: calData, isLoading } = useHabitsCalendar(currentMonth)

const monthLabel = computed(() => {
    const [y, m] = currentMonth.value.split('-')
    const months = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre']
    return `${months[parseInt(m) - 1]} ${y}`
})

const startOffset = computed(() => {
    if (!calData.value) return 0
    const d = new Date(calData.value.start + 'T12:00:00')
    return (d.getDay() + 6) % 7 // Monday = 0
})

const daysInMonth = computed(() => {
    if (!calData.value) return []
    const end = new Date(calData.value.end + 'T12:00:00')
    return Array.from({ length: end.getDate() }, (_, i) => i + 1)
})

const habitColorMap = computed(() => {
    const map = {}
    calData.value?.habits?.forEach(h => { map[h.id] = h.color })
    return map
})

function getDots(day) {
    const dateStr = `${currentMonth.value}-${String(day).padStart(2, '0')}`
    const entries = calData.value?.calendar?.[dateStr] ?? []
    return entries.map(e => ({ ...e, color: habitColorMap.value[e.habit_id] ?? '#6366f1' }))
}

function isToday(day) {
    const today = new Date()
    const dateStr = `${currentMonth.value}-${String(day).padStart(2, '0')}`
    return dateStr === today.toISOString().split('T')[0]
}

function prevMonth() {
    const [y, m] = currentMonth.value.split('-').map(Number)
    const d = new Date(y, m - 2, 1)
    currentMonth.value = d.toISOString().slice(0, 7)
}

function nextMonth() {
    const [y, m] = currentMonth.value.split('-').map(Number)
    const d = new Date(y, m, 1)
    currentMonth.value = d.toISOString().slice(0, 7)
}

function goToday() {
    currentMonth.value = new Date().toISOString().slice(0, 7)
}
</script>
