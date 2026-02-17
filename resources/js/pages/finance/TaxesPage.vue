<template>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <p class="text-surface-400">Impuestos y retenciones para tus transacciones y facturas</p>
            <button class="btn-add" @click="openTaxForm()">
                <PlusIcon class="w-4 h-4" />
                Nuevo impuesto
            </button>
        </div>

        <div v-if="isLoading" class="text-center py-10 text-surface-500">Cargando...</div>

        <div v-else class="space-y-4">
            <div
                v-for="tax in taxes"
                :key="tax.id"
                class="liquid-glass liquid-glass-card p-6 space-y-4"
            >
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span
                            class="px-2 py-0.5 rounded text-xs font-medium"
                            :class="tax.type === 'applicable'
                                ? 'bg-accent-500/20 text-accent-400'
                                : 'bg-danger-500/20 text-danger-400'"
                        >
                            {{ tax.type === 'applicable' ? 'Aplicable (+)' : 'Deducible (-)' }}
                        </span>
                        <h3 class="text-lg font-semibold text-white">{{ tax.name }}</h3>
                        <span v-if="!tax.user_id" class="text-xs text-surface-500">(sistema)</span>
                    </div>
                    <div v-if="tax.user_id" class="flex gap-2">
                        <button class="text-xs font-medium text-surface-400 hover:text-white transition" @click="openTaxForm(tax)">Editar</button>
                        <button class="text-xs font-medium text-danger-400 hover:text-danger-500 transition" @click="removeTax(tax)">Eliminar</button>
                    </div>
                </div>

                <!-- Rates -->
                <div class="space-y-2">
                    <div
                        v-for="rate in tax.rates"
                        :key="rate.id"
                        class="flex items-center justify-between rounded-xl border border-white/5 bg-white/[0.02] px-4 py-3"
                    >
                        <div class="flex items-center gap-3">
                            <span class="text-sm font-medium text-white">{{ rate.name }}</span>
                            <span v-if="rate.is_default" class="text-xs text-primary-400">(por defecto)</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-lg font-semibold text-white">{{ rate.rate }}%</span>
                            <div v-if="tax.user_id" class="flex gap-2">
                                <button class="text-xs text-surface-400 hover:text-white transition" @click="openRateForm(tax, rate)">Editar</button>
                                <button class="text-xs text-danger-400 hover:text-danger-500 transition" @click="removeRate(rate)">Eliminar</button>
                            </div>
                        </div>
                    </div>
                </div>

                <button
                    class="text-xs text-primary-300 hover:text-primary-200"
                    @click="openRateForm(tax)"
                >+ Añadir tasa</button>
            </div>
        </div>

        <!-- Tax form modal -->
        <Teleport to="body">
            <div v-if="showTaxForm" class="modal-overlay" @mousedown.self="showTaxForm = false">
                <div class="modal-backdrop" />
                <div class="modal-content liquid-glass liquid-glass-panel max-w-md">
                    <h2 class="section-title mb-5">
                        {{ editingTax ? 'Editar impuesto' : 'Nuevo impuesto' }}
                    </h2>
                    <form class="form-group" @submit.prevent="submitTax">
                        <div>
                            <label class="form-label">Nombre</label>
                            <input v-model="taxForm.name" type="text" class="form-input" required />
                        </div>
                        <div>
                            <label class="form-label">Tipo</label>
                            <CustomSelect v-model="taxForm.type" :options="taxTypeOptions" />
                        </div>
                        <p v-if="taxError" class="form-error">{{ taxError }}</p>
                        <div class="flex justify-end gap-3 mt-2">
                            <button type="button" class="btn-secondary" @click="showTaxForm = false">Cancelar</button>
                            <button class="btn-primary" type="submit">Guardar</button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- Rate form modal -->
        <Teleport to="body">
            <div v-if="showRateForm" class="modal-overlay" @mousedown.self="showRateForm = false">
                <div class="modal-backdrop" />
                <div class="modal-content liquid-glass liquid-glass-panel max-w-md">
                    <h2 class="section-title mb-5">
                        {{ editingRate ? 'Editar tasa' : 'Nueva tasa' }} — {{ rateFormTax?.name }}
                    </h2>
                    <form class="form-group" @submit.prevent="submitRate">
                        <div>
                            <label class="form-label">Nombre</label>
                            <input v-model="rateForm.name" type="text" class="form-input" required />
                        </div>
                        <div>
                            <label class="form-label">Tasa (%)</label>
                            <input v-model="rateForm.rate" type="number" step="0.01" min="0" max="100" class="form-input" required />
                        </div>
                        <p v-if="rateError" class="form-error">{{ rateError }}</p>
                        <div class="flex justify-end gap-3 mt-2">
                            <button type="button" class="btn-secondary" @click="showRateForm = false">Cancelar</button>
                            <button class="btn-primary" type="submit">Guardar</button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script setup>
import { computed, reactive, ref } from 'vue'
import { PlusIcon } from '@heroicons/vue/24/outline'
import CustomSelect from '@/components/ui/CustomSelect.vue'
import {
    useFinanceTaxes,
    useCreateTax,
    useUpdateTax,
    useDeleteTax,
    useCreateTaxRate,
    useUpdateTaxRate,
    useDeleteTaxRate,
} from '@/composables/useFinanceTaxes'

const { data: taxesData, isLoading } = useFinanceTaxes()
const taxes = computed(() => taxesData.value?.data ?? [])

const createTax = useCreateTax()
const updateTax = useUpdateTax()
const deleteTax = useDeleteTax()
const createTaxRate = useCreateTaxRate()
const updateTaxRate = useUpdateTaxRate()
const deleteTaxRate = useDeleteTaxRate()

// Tax form
const showTaxForm = ref(false)
const editingTax = ref(null)
const taxError = ref('')
const taxForm = reactive({ name: '', type: 'applicable' })

const taxTypeOptions = [
    { value: 'applicable', label: 'Aplicable (+)' },
    { value: 'deductible', label: 'Deducible (-)' },
]

function openTaxForm(tax = null) {
    taxError.value = ''
    if (tax) {
        editingTax.value = tax
        taxForm.name = tax.name
        taxForm.type = tax.type
    } else {
        editingTax.value = null
        taxForm.name = ''
        taxForm.type = 'applicable'
    }
    showTaxForm.value = true
}

async function submitTax() {
    taxError.value = ''
    try {
        if (editingTax.value) {
            await updateTax.mutateAsync({ id: editingTax.value.id, data: { ...taxForm } })
        } else {
            await createTax.mutateAsync({ ...taxForm })
        }
        showTaxForm.value = false
    } catch (e) {
        taxError.value = e.response?.data?.message ?? 'Error al guardar'
    }
}

async function removeTax(tax) {
    if (!confirm(`¿Eliminar "${tax.name}" y todas sus tasas?`)) return
    await deleteTax.mutateAsync(tax.id)
}

// Rate form
const showRateForm = ref(false)
const editingRate = ref(null)
const rateFormTax = ref(null)
const rateError = ref('')
const rateForm = reactive({ name: '', rate: '' })

function openRateForm(tax, rate = null) {
    rateError.value = ''
    rateFormTax.value = tax
    if (rate) {
        editingRate.value = rate
        rateForm.name = rate.name
        rateForm.rate = rate.rate
    } else {
        editingRate.value = null
        rateForm.name = ''
        rateForm.rate = ''
    }
    showRateForm.value = true
}

async function submitRate() {
    rateError.value = ''
    try {
        if (editingRate.value) {
            await updateTaxRate.mutateAsync({ id: editingRate.value.id, data: { ...rateForm, rate: Number(rateForm.rate) } })
        } else {
            await createTaxRate.mutateAsync({ taxId: rateFormTax.value.id, data: { ...rateForm, rate: Number(rateForm.rate) } })
        }
        showRateForm.value = false
    } catch (e) {
        rateError.value = e.response?.data?.message ?? 'Error al guardar'
    }
}

async function removeRate(rate) {
    if (!confirm(`¿Eliminar la tasa "${rate.name}"?`)) return
    await deleteTaxRate.mutateAsync(rate.id)
}
</script>
