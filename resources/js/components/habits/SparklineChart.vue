<template>
    <!-- Boolean: 7 circles (filled/hollow) -->
    <svg v-if="type === 'boolean'" :width="width" :height="height" class="sparkline">
        <circle
            v-for="(point, i) in data"
            :key="i"
            :cx="spacing / 2 + i * spacing"
            :cy="height / 2"
            :r="3"
            :fill="point.completed ? color : 'transparent'"
            :stroke="color"
            :stroke-width="1.5"
            :opacity="point.completed ? 1 : 0.3"
        />
    </svg>

    <!-- Numeric: 7 mini bars -->
    <svg v-else :width="width" :height="height" class="sparkline">
        <rect
            v-for="(point, i) in data"
            :key="i"
            :x="i * spacing + 1"
            :y="height - barHeight(point.value)"
            :width="spacing - 2"
            :height="barHeight(point.value)"
            :fill="color"
            :opacity="point.value ? 0.8 : 0.15"
            rx="1"
        />
    </svg>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    data: { type: Array, default: () => [] },
    type: { type: String, default: 'boolean' },
    color: { type: String, default: '#6366f1' },
})

const width = 56
const height = 14
const spacing = computed(() => width / Math.max(props.data.length, 1))

const maxValue = computed(() => {
    const vals = props.data.map(p => p.value || 0)
    return Math.max(...vals, 1)
})

function barHeight(value) {
    if (!value) return 2
    return Math.max(2, (value / maxValue.value) * (height - 2))
}
</script>
