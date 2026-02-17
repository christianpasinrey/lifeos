<template>
    <svg :viewBox="`0 0 ${width} ${height}`" class="w-full" style="max-height: 160px;">
        <!-- Area fill -->
        <path :d="areaPath" :fill="color + '15'" />
        <!-- Line -->
        <path :d="linePath" fill="none" :stroke="color" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
        <!-- Dots -->
        <circle
            v-for="(point, i) in points"
            :key="i"
            :cx="point.x"
            :cy="point.y"
            r="3"
            :fill="color"
        />
    </svg>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    data: { type: Array, default: () => [] },
    color: { type: String, default: '#6366f1' },
})

const width = 300
const height = 120
const padding = { top: 10, right: 10, bottom: 10, left: 10 }

const points = computed(() => {
    if (!props.data.length) return []
    const values = props.data.map(d => d.value)
    const min = Math.min(...values)
    const max = Math.max(...values)
    const range = max - min || 1
    const w = width - padding.left - padding.right
    const h = height - padding.top - padding.bottom

    return props.data.map((d, i) => ({
        x: padding.left + (i / Math.max(props.data.length - 1, 1)) * w,
        y: padding.top + h - ((d.value - min) / range) * h,
    }))
})

const linePath = computed(() => {
    if (points.value.length < 2) return ''
    return points.value.map((p, i) => `${i === 0 ? 'M' : 'L'}${p.x},${p.y}`).join(' ')
})

const areaPath = computed(() => {
    if (points.value.length < 2) return ''
    const base = height - padding.bottom
    const first = points.value[0]
    const last = points.value[points.value.length - 1]
    return `M${first.x},${base} ${points.value.map(p => `L${p.x},${p.y}`).join(' ')} L${last.x},${base} Z`
})
</script>
