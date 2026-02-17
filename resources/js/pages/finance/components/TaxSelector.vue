<template>
    <div class="space-y-2">
        <div v-for="(selected, index) in modelValue" :key="index" class="flex items-center gap-2">
            <CustomSelect
                :modelValue="selected.tax_id"
                @update:modelValue="updateTax(index, $event)"
                :options="taxOptions"
                placeholder="Seleccionar impuesto..."
                class="flex-1 text-sm"
            />
            <CustomSelect
                :modelValue="selected.tax_rate_id"
                @update:modelValue="updateRate(index, $event)"
                :options="getRateOptions(selected.tax_id)"
                placeholder="Tasa..."
                class="flex-1 text-sm"
            />
            <button
                type="button"
                class="text-danger-400 hover:text-danger-300 text-sm px-1"
                @click="removeTax(index)"
            >&times;</button>
        </div>
        <button
            type="button"
            class="text-xs text-primary-300 hover:text-primary-200"
            @click="addTax"
        >+ Añadir impuesto</button>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { useFinanceTaxes } from '@/composables/useFinanceTaxes'
import CustomSelect from '@/components/ui/CustomSelect.vue'

const props = defineProps({
    modelValue: { type: Array, default: () => [] },
})

const emit = defineEmits(['update:modelValue'])

const { data: taxesData } = useFinanceTaxes()
const taxes = computed(() => taxesData.value?.data ?? [])

const taxOptions = computed(() =>
    taxes.value.map(t => ({
        value: t.id,
        label: `${t.name} (${t.type === 'applicable' ? '+' : '-'})`,
    }))
)

function getRatesForTax(taxId) {
    const tax = taxes.value.find(t => t.id === Number(taxId))
    return tax?.rates ?? []
}

function getRateOptions(taxId) {
    return getRatesForTax(taxId).map(r => ({
        value: r.id,
        label: `${r.name} (${r.rate}%)`,
    }))
}

function addTax() {
    emit('update:modelValue', [...props.modelValue, { tax_id: '', tax_rate_id: '' }])
}

function removeTax(index) {
    const updated = [...props.modelValue]
    updated.splice(index, 1)
    emit('update:modelValue', updated)
}

function updateTax(index, taxId) {
    const updated = [...props.modelValue]
    updated[index] = { ...updated[index], tax_id: taxId, tax_rate_id: '' }
    emit('update:modelValue', updated)
}

function updateRate(index, rateId) {
    const updated = [...props.modelValue]
    updated[index] = { ...updated[index], tax_rate_id: rateId }
    emit('update:modelValue', updated)
}
</script>
