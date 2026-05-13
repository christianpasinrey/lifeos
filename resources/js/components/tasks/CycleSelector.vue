<template>
    <div class="cycle-selector">
        <CustomSelect
            :modelValue="modelValue ?? 0"
            :options="options"
            placeholder="Sin cycle"
            @update:modelValue="onChange"
        />
    </div>
</template>

<script setup>
import { computed } from 'vue'
import CustomSelect from '@/components/ui/CustomSelect.vue'
import { useCycles } from '@/composables/useCycles'

const props = defineProps({
    boardId: { type: Number, required: true },
    modelValue: { type: Number, default: null }, // cycle_id or null
})

const emit = defineEmits(['update:modelValue', 'change'])

const { data: cyclesData } = useCycles(() => props.boardId)

const options = computed(() => {
    const list = cyclesData.value?.data ?? []
    return [
        { value: 0, label: '— Sin cycle —' },
        ...list.map(c => ({
            value: c.id,
            label: `${c.name} · ${c.status}`,
        })),
    ]
})

function onChange(val) {
    const id = val === 0 ? null : Number(val)
    emit('update:modelValue', id)
    emit('change', id)
}
</script>
