<template>
    <span
        v-if="cycle"
        class="cycle-badge inline-flex items-center gap-1 rounded-md px-1.5 py-0.5 text-[10px] font-medium leading-none"
        :style="badgeStyle"
        :title="title"
    >
        <span class="cycle-badge-icon" :class="statusIconClass">
            <ArrowPathIcon v-if="cycle.status === 'active'" class="w-2.5 h-2.5" />
            <CheckCircleIcon v-else-if="cycle.status === 'completed'" class="w-2.5 h-2.5" />
            <ClockIcon v-else class="w-2.5 h-2.5" />
        </span>
        <span class="truncate max-w-[100px]">{{ cycle.name }}</span>
    </span>
</template>

<script setup>
import { computed } from 'vue'
import { ArrowPathIcon, CheckCircleIcon, ClockIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    cycle: { type: Object, default: null },
})

const badgeStyle = computed(() => {
    if (!props.cycle) return {}
    const c = props.cycle.color || '#6366f1'
    return {
        backgroundColor: `${c}1f`,
        color: `${c}`,
        border: `1px solid ${c}33`,
    }
})

const statusIconClass = computed(() => {
    if (props.cycle?.status === 'active') return 'animate-pulse'
    return ''
})

const title = computed(() => {
    if (!props.cycle) return ''
    const range = [props.cycle.starts_on, props.cycle.ends_on].filter(Boolean).join(' → ')
    return `${props.cycle.name} (${props.cycle.status})${range ? ' ' + range : ''}`
})
</script>
