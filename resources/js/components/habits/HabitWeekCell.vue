<template>
    <button
        class="w-10 h-10 rounded-lg border transition-all flex items-center justify-center text-xs font-medium"
        :class="cellClass"
        :disabled="day.is_future || !day.due"
        @click="$emit('toggle')"
    >
        <CheckIcon v-if="day.completed" class="w-4 h-4" />
        <span v-else-if="!day.due" class="text-surface-600">—</span>
    </button>
</template>

<script setup>
import { computed } from 'vue'
import { CheckIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    day: Object,
    color: String,
})

defineEmits(['toggle'])

const cellClass = computed(() => {
    if (props.day.is_future) return 'border-white/[0.04] bg-white/[0.02] text-surface-600 cursor-not-allowed opacity-40'
    if (!props.day.due) return 'border-white/[0.04] bg-transparent text-surface-600 cursor-default'
    if (props.day.completed) return 'border-transparent text-white'
    return 'border-white/[0.08] bg-white/[0.03] text-surface-400 hover:border-white/[0.15] hover:bg-white/[0.06] cursor-pointer'
})
</script>

<style scoped>
button[class*="border-transparent"] {
    background-color: v-bind("color + '30'");
    color: v-bind(color);
}
</style>
