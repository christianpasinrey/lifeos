<template>
    <div class="space-y-2">
        <div v-for="(selected, index) in modelValue" :key="index" class="flex items-center gap-2">
            <select
                :value="selected.tax_id"
                class="form-input flex-1 text-sm"
                @change="updateTax(index, $event.target.value)"
            >
                <option value="">Seleccionar impuesto...</option>
                <option v-for="tax in taxes" :key="tax.id" :value="tax.id">
                    {{ tax.name }} ({{ tax.type === 'applicable' ? '+' : '-' }})
                </option>
            </select>
            <select
                :value="selected.tax_rate_id"
                class="form-input flex-1 text-sm"
                @change="updateRate(index, $event.target.value)"
            >
                <option value="">Tasa...</option>
                <option v-for="rate in getRatesForTax(selected.tax_id)" :key="rate.id" :value="rate.id">
                    {{ rate.name }} ({{ rate.rate }}%)
                </option>
            </select>
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

const props = defineProps({
    modelValue: { type: Array, default: () => [] },
})

const emit = defineEmits(['update:modelValue'])

const { data: taxesData } = useFinanceTaxes()
const taxes = computed(() => taxesData.value?.data ?? [])

function getRatesForTax(taxId) {
    const tax = taxes.value.find(t => t.id === Number(taxId))
    return tax?.rates ?? []
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
