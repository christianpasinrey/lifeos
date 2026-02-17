<template>
    <Teleport to="body">
        <div class="fixed inset-0 z-[100]" @click="$emit('close')" />
        <div
            class="fixed z-[101] w-max rounded-xl p-1.5 shadow-xl calendar-quick-menu"
            :style="posStyle"
        >
            <button
                v-for="slot in creatableSlots"
                :key="slot.source"
                class="flex items-center gap-2.5 w-full px-3 py-2 rounded-lg text-sm text-surface-200 hover:bg-white/[0.08] transition-colors whitespace-nowrap"
                @click="$emit('select', slot)"
            >
                <component :is="slot.icon" class="w-4 h-4 flex-shrink-0" :style="{ color: slot.color }" />
                <span>Nuevo {{ slot.label.toLowerCase() }}</span>
            </button>
        </div>
    </Teleport>
</template>

<script setup>
import { computed } from 'vue'
import { useModuleRegistry } from '@/modules/registry'

const props = defineProps({
    anchorRect: { type: Object, required: true },
})

defineEmits(['select', 'close'])

const { calendarSlots } = useModuleRegistry()

const creatableSlots = computed(() =>
    calendarSlots.value.filter(s => s.quickCreateComponent)
)

const posStyle = computed(() => {
    const r = props.anchorRect
    return {
        left: `${r.left}px`,
        top: `${r.bottom + 4}px`,
    }
})
</script>
