<template>
    <div class="space-y-4">
        <div v-for="(line, index) in modelValue" :key="index" class="rounded-xl border border-white/5 bg-white/[0.02] p-4 space-y-3">
            <div class="flex items-start justify-between gap-2">
                <span class="text-xs text-surface-500 font-medium">Línea {{ index + 1 }}</span>
                <button
                    type="button"
                    class="text-danger-400 hover:text-danger-300 text-xs"
                    @click="removeLine(index)"
                >&times; Eliminar</button>
            </div>
            <div>
                <label class="form-label">Concepto</label>
                <input
                    :value="line.concept"
                    @input="updateField(index, 'concept', $event.target.value)"
                    type="text"
                    class="form-input"
                    required
                />
            </div>
            <div class="grid gap-3 sm:grid-cols-3">
                <div>
                    <label class="form-label">Cantidad</label>
                    <input
                        :value="line.quantity"
                        @input="updateField(index, 'quantity', $event.target.value)"
                        type="number"
                        step="0.0001"
                        min="0"
                        class="form-input"
                    />
                </div>
                <div>
                    <label class="form-label">Precio unit.</label>
                    <input
                        :value="line.unit_price"
                        @input="updateField(index, 'unit_price', $event.target.value)"
                        type="number"
                        step="0.01"
                        class="form-input"
                        required
                    />
                </div>
                <div>
                    <label class="form-label">Subtotal</label>
                    <input
                        :value="lineSubtotal(line)"
                        type="text"
                        class="form-input bg-white/[0.02] text-surface-400"
                        readonly
                    />
                </div>
            </div>
            <div>
                <label class="form-label">Impuestos</label>
                <TaxSelector
                    :modelValue="line.taxes || []"
                    @update:modelValue="updateField(index, 'taxes', $event)"
                />
            </div>
        </div>

        <button
            type="button"
            class="w-full py-3 rounded-xl border border-dashed border-white/10 text-sm text-primary-300 hover:bg-white/[0.03] transition"
            @click="addLine"
        >+ Añadir línea</button>

        <!-- Totals -->
        <div v-if="modelValue.length > 0" class="rounded-xl border border-white/5 bg-white/[0.02] p-4 space-y-2">
            <div class="flex justify-between text-sm">
                <span class="text-surface-400">Subtotal</span>
                <span class="text-white font-medium">{{ formatCurrency(totals.subtotal) }}</span>
            </div>
            <div v-if="totals.taxTotal > 0" class="flex justify-between text-sm">
                <span class="text-surface-400">Impuestos (+)</span>
                <span class="text-accent-400 font-medium">+{{ formatCurrency(totals.taxTotal) }}</span>
            </div>
            <div v-if="totals.retentionTotal > 0" class="flex justify-between text-sm">
                <span class="text-surface-400">Retenciones (-)</span>
                <span class="text-danger-400 font-medium">-{{ formatCurrency(totals.retentionTotal) }}</span>
            </div>
            <div class="flex justify-between text-base font-semibold border-t border-white/5 pt-2">
                <span class="text-surface-300">Total</span>
                <span class="text-white">{{ formatCurrency(totals.total) }}</span>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import TaxSelector from './TaxSelector.vue'
import { useFinanceTaxes } from '@/composables/useFinanceTaxes'

const props = defineProps({
    modelValue: { type: Array, default: () => [] },
})

const emit = defineEmits(['update:modelValue'])

const { data: taxesData } = useFinanceTaxes()

function addLine() {
    emit('update:modelValue', [
        ...props.modelValue,
        { concept: '', quantity: 1, unit_price: '', taxes: [] },
    ])
}

function removeLine(index) {
    const updated = [...props.modelValue]
    updated.splice(index, 1)
    emit('update:modelValue', updated)
}

function updateField(index, field, value) {
    const updated = [...props.modelValue]
    updated[index] = { ...updated[index], [field]: value }
    emit('update:modelValue', updated)
}

function lineSubtotal(line) {
    const qty = Number(line.quantity) || 0
    const price = Number(line.unit_price) || 0
    return (qty * price).toFixed(2)
}

const totals = computed(() => {
    const taxes = taxesData.value?.data ?? []
    let subtotal = 0
    let taxTotal = 0
    let retentionTotal = 0

    for (const line of props.modelValue) {
        const ls = Number(lineSubtotal(line))
        subtotal += ls

        for (const lt of (line.taxes || [])) {
            if (!lt.tax_id || !lt.tax_rate_id) continue
            const tax = taxes.find(t => t.id === Number(lt.tax_id))
            if (!tax) continue
            const rate = tax.rates?.find(r => r.id === Number(lt.tax_rate_id))
            if (!rate) continue

            const amount = ls * (Number(rate.rate) / 100)
            if (tax.type === 'applicable') {
                taxTotal += amount
            } else {
                retentionTotal += amount
            }
        }
    }

    return {
        subtotal: Math.round(subtotal * 100) / 100,
        taxTotal: Math.round(taxTotal * 100) / 100,
        retentionTotal: Math.round(retentionTotal * 100) / 100,
        total: Math.round((subtotal + taxTotal - retentionTotal) * 100) / 100,
    }
})

function formatCurrency(value) {
    return new Intl.NumberFormat('es-ES', {
        style: 'currency',
        currency: 'EUR',
        minimumFractionDigits: 2,
    }).format(Number(value ?? 0))
}
</script>
