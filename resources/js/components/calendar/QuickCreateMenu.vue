<template>
    <Teleport to=".layout-root">
        <div class="fixed inset-0 z-40" @click="$emit('close')" />
        <div
            class="fixed z-50 min-w-[200px] liquid-glass liquid-glass-panel rounded-xl p-1.5"
            :style="menuStyle"
        >
            <button
                v-for="slot in creatableSlots"
                :key="slot.source"
                class="flex items-center gap-2.5 w-full px-3 py-2 rounded-lg text-sm text-surface-200 hover:bg-white/[0.08] transition-colors"
                @click="$emit('select', slot)"
            >
                <component :is="slot.icon" class="w-4 h-4" :style="{ color: slot.color }" />
                <span>Nuevo {{ slot.label.toLowerCase() }}</span>
            </button>
        </div>
    </Teleport>
</template>

<script setup>
import { computed } from 'vue'
import { useModuleRegistry } from '@/modules/registry'

const props = defineProps({
    position: { type: Object, default: () => ({ x: 0, y: 0 }) },
})

defineEmits(['select', 'close'])

const { calendarSlots } = useModuleRegistry()

const creatableSlots = computed(() =>
    calendarSlots.value.filter(s => s.quickCreateComponent)
)

const menuStyle = computed(() => ({
    left: `${props.position.x}px`,
    top: `${props.position.y}px`,
}))
</script>
