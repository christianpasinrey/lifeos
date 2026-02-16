<template>
    <div class="overflow-x-auto">
        <div class="inline-grid grid-flow-col gap-1" style="grid-template-rows: repeat(7, 1fr)">
            <div
                v-for="day in calendarDays"
                :key="day.date"
                class="w-3 h-3 rounded-sm transition-colors"
                :style="{ backgroundColor: day.color }"
                :title="day.date + (day.completed ? ' ✓' : '')"
            />
        </div>
        <div class="flex items-center gap-2 mt-3 text-xs text-surface-500">
            <span>Menos</span>
            <div class="flex gap-0.5">
                <div class="w-3 h-3 rounded-sm bg-surface-800" />
                <div class="w-3 h-3 rounded-sm" :style="{ backgroundColor: baseColor + '30' }" />
                <div class="w-3 h-3 rounded-sm" :style="{ backgroundColor: baseColor + '60' }" />
                <div class="w-3 h-3 rounded-sm" :style="{ backgroundColor: baseColor + '90' }" />
                <div class="w-3 h-3 rounded-sm" :style="{ backgroundColor: baseColor }" />
            </div>
            <span>Más</span>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    data: { type: Object, default: () => ({}) },
    color: { type: String, default: '#6366f1' },
})

const baseColor = computed(() => props.color || '#6366f1')

const calendarDays = computed(() => {
    const days = []
    const today = new Date()
    const start = new Date(today)
    start.setDate(start.getDate() - 364)

    // Align to start of week (Monday)
    while (start.getDay() !== 1) {
        start.setDate(start.getDate() - 1)
    }

    const current = new Date(start)
    while (current <= today) {
        const dateStr = current.toISOString().split('T')[0]
        const entry = props.data?.[dateStr]
        const completed = entry?.completed ?? false

        let color = '#1e293b' // surface-800
        if (completed) {
            color = baseColor.value
        }

        days.push({ date: dateStr, completed, color })
        current.setDate(current.getDate() + 1)
    }

    return days
})
</script>
