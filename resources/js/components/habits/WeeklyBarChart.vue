<template>
    <div class="flex items-end gap-2 justify-between" style="height: 100px;">
        <div
            v-for="day in days"
            :key="day.key"
            class="flex-1 flex flex-col items-center gap-1"
        >
            <div class="w-full relative" style="height: 80px;">
                <div
                    class="absolute bottom-0 w-full rounded-t transition-all duration-500"
                    :style="{ height: day.height + '%', backgroundColor: color + (day.value > 60 ? 'cc' : '66') }"
                />
            </div>
            <span class="text-[10px] text-surface-400">{{ day.label }}</span>
            <span class="text-[10px] text-surface-500">{{ day.value }}%</span>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    data: { type: Object, default: () => ({}) },
    color: { type: String, default: '#6366f1' },
})

const dayOrder = [
    { key: 'mon', label: 'Lun' },
    { key: 'tue', label: 'Mar' },
    { key: 'wed', label: 'Mié' },
    { key: 'thu', label: 'Jue' },
    { key: 'fri', label: 'Vie' },
    { key: 'sat', label: 'Sáb' },
    { key: 'sun', label: 'Dom' },
]

const days = computed(() =>
    dayOrder.map(d => ({
        ...d,
        value: props.data[d.key] ?? 0,
        height: Math.max(4, props.data[d.key] ?? 0),
    }))
)
</script>
