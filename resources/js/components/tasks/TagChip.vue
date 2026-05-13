<template>
    <span
        class="tag-chip inline-flex items-center gap-1 rounded-full text-[10.5px] font-medium leading-none transition-colors"
        :class="sizeClasses"
        :style="chipStyle"
        :title="tag.description || tag.name"
    >
        <span class="tag-chip-dot rounded-full" :style="{ background: tag.color }" />
        <span class="truncate max-w-[120px]">{{ tag.name }}</span>
        <button
            v-if="removable"
            type="button"
            class="tag-chip-remove ml-0.5 opacity-60 hover:opacity-100 transition-opacity -mr-0.5"
            @click.stop="$emit('remove', tag)"
            :aria-label="`Eliminar ${tag.name}`"
        >
            <XMarkIcon class="w-3 h-3" />
        </button>
    </span>
</template>

<script setup>
import { computed } from 'vue'
import { XMarkIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    tag: { type: Object, required: true },
    size: { type: String, default: 'sm' }, // 'xs' | 'sm' | 'md'
    removable: { type: Boolean, default: false },
})

defineEmits(['remove'])

const sizeClasses = computed(() => ({
    xs: 'px-1.5 py-0.5 [&_.tag-chip-dot]:w-1.5 [&_.tag-chip-dot]:h-1.5',
    sm: 'px-2 py-1 [&_.tag-chip-dot]:w-2 [&_.tag-chip-dot]:h-2',
    md: 'px-2.5 py-1.5 text-xs [&_.tag-chip-dot]:w-2.5 [&_.tag-chip-dot]:h-2.5',
}[props.size]))

// Tinted background derived from the tag color so the chip stays legible on dark glass.
const chipStyle = computed(() => {
    const c = props.tag.color || '#94a3b8'
    return {
        backgroundColor: `${c}1f`, // ~12% alpha
        color: lighten(c, 0.6),
        border: `1px solid ${c}33`,
    }
})

function lighten(hex, amount) {
    const h = hex.replace('#', '')
    if (h.length !== 6) return hex
    const r = Math.min(255, Math.round(parseInt(h.slice(0, 2), 16) + (255 - parseInt(h.slice(0, 2), 16)) * amount))
    const g = Math.min(255, Math.round(parseInt(h.slice(2, 4), 16) + (255 - parseInt(h.slice(2, 4), 16)) * amount))
    const b = Math.min(255, Math.round(parseInt(h.slice(4, 6), 16) + (255 - parseInt(h.slice(4, 6), 16)) * amount))
    return `rgb(${r},${g},${b})`
}
</script>
