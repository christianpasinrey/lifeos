<template>
    <Teleport to="body">
        <div v-if="show" class="modal-overlay" @mousedown.self="$emit('close')">
            <div class="modal-backdrop" />
            <div class="modal-content liquid-glass liquid-glass-panel max-w-2xl flex flex-col overflow-hidden">
                <h2 class="section-title mb-5 shrink-0">
                    {{ editing ? 'Editar registro' : 'Nuevo registro' }}
                </h2>
                <form class="form-group flex flex-col min-h-0 flex-1" @submit.prevent="submit">
                    <div class="flex-1 overflow-y-auto space-y-4 pr-1 glass-scroll">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="form-label">Tipo</label>
                                <CustomSelect v-model="form.type" :options="typeOptions" />
                            </div>
                            <div>
                                <label class="form-label">Cuenta</label>
                                <AccountSelector v-model="form.account_id" :includeAll="false" placeholder="Sin cuenta" />
                            </div>
                        </div>

                        <!-- Simple mode: amount only -->
                        <div v-if="!lineMode">
                            <label class="form-label">Cantidad (EUR)</label>
                            <input v-model="form.amount" type="number" step="0.01" min="0" class="form-input" required />
                        </div>

                        <div>
                            <label class="form-label">Descripción</label>
                            <input v-model="form.description" type="text" class="form-input" required />
                        </div>
                        <div>
                            <label class="form-label">Notas</label>
                            <textarea v-model="form.notes" rows="2" class="form-input" placeholder="Opcional"></textarea>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="form-label">Categoría</label>
                                <CustomSelect v-model="form.category_id" :options="categoryOptions" placeholder="Sin categoría" />
                            </div>
                            <div>
                                <label class="form-label">Fecha</label>
                                <input v-model="form.date" type="date" class="form-input" required />
                            </div>
                        </div>

                        <!-- Toggle line mode -->
                        <div class="flex items-center gap-3 pt-2 border-t border-white/5">
                            <button
                                type="button"
                                class="text-sm font-medium transition"
                                :class="lineMode ? 'text-primary-300 hover:text-primary-200' : 'text-surface-400 hover:text-surface-300'"
                                @click="lineMode = !lineMode"
                            >
                                {{ lineMode ? 'Modo simple (solo importe)' : 'Modo detallado (líneas + impuestos)' }}
                            </button>
                        </div>

                        <!-- Line items -->
                        <TransactionLineEditor
                            v-if="lineMode"
                            v-model="form.lines"
                        />
                    </div>

                    <p v-if="error" class="form-error mt-2">{{ error }}</p>
                    <div class="flex justify-end gap-3 pt-4 mt-4 border-t border-white/5 shrink-0">
                        <button type="button" class="btn-secondary" @click="$emit('close')">Cancelar</button>
                        <button class="btn-primary" type="submit" :disabled="pending">
                            Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue'
import CustomSelect from '@/components/ui/CustomSelect.vue'
import AccountSelector from './AccountSelector.vue'
import TransactionLineEditor from './TransactionLineEditor.vue'
import { useFinanceCategories, useCreateTransaction, useUpdateTransaction } from '@/composables/useFinance'

const props = defineProps({
    show: { type: Boolean, default: false },
    transaction: { type: Object, default: null },
    defaultDate: { type: String, default: null },
})

const emit = defineEmits(['close', 'saved'])

const editing = computed(() => !!props.transaction)
const lineMode = ref(false)
const error = ref('')

const form = reactive({
    type: 'expense',
    amount: '',
    description: '',
    category_id: '',
    account_id: '',
    date: '',
    notes: '',
    lines: [],
})

const { data: categoriesData } = useFinanceCategories()
const createTransaction = useCreateTransaction()
const updateTransaction = useUpdateTransaction()

const pending = computed(() =>
    createTransaction.isPending.value || updateTransaction.isPending.value
)

const typeOptions = [
    { value: 'income', label: 'Ingreso' },
    { value: 'expense', label: 'Gasto' },
    { value: 'transfer', label: 'Transferencia' },
]

const categoryOptions = computed(() => {
    const cats = categoriesData.value?.data ?? []
    return [
        { value: '', label: 'Sin categoría' },
        ...cats.map(c => ({ value: String(c.id), label: c.name })),
    ]
})

watch(() => props.show, (val) => {
    if (val) {
        resetForm()
    }
})

function resetForm() {
    error.value = ''

    if (props.transaction) {
        const tx = props.transaction
        form.type = tx.type
        form.amount = tx.amount
        form.description = tx.description
        form.category_id = tx.category ? String(tx.category.id) : ''
        form.account_id = tx.account ? String(tx.account.id) : (tx.account_id ? String(tx.account_id) : '')
        form.date = tx.date
        form.notes = tx.notes ?? ''

        if (tx.lines && tx.lines.length > 0) {
            lineMode.value = true
            form.lines = tx.lines.map(l => ({
                concept: l.concept,
                quantity: l.quantity,
                unit_price: l.unit_price,
                taxes: (l.taxes || []).map(t => ({
                    tax_id: String(t.tax_id),
                    tax_rate_id: String(t.tax_rate_id),
                })),
            }))
        } else {
            lineMode.value = false
            form.lines = []
        }
    } else {
        form.type = 'expense'
        form.amount = ''
        form.description = ''
        form.category_id = ''
        form.account_id = ''
        form.date = props.defaultDate || new Date().toISOString().slice(0, 10)
        form.notes = ''
        form.lines = []
        lineMode.value = false
    }
}

async function submit() {
    error.value = ''

    const payload = {
        type: form.type,
        description: form.description,
        notes: form.notes || null,
        category_id: form.category_id ? Number(form.category_id) : null,
        account_id: form.account_id ? Number(form.account_id) : null,
        date: form.date,
    }

    if (lineMode.value && form.lines.length > 0) {
        payload.lines = form.lines.map((l, i) => ({
            concept: l.concept,
            quantity: Number(l.quantity) || 1,
            unit_price: Number(l.unit_price) || 0,
            sort_order: i,
            taxes: (l.taxes || [])
                .filter(t => t.tax_id && t.tax_rate_id)
                .map(t => ({
                    tax_id: Number(t.tax_id),
                    tax_rate_id: Number(t.tax_rate_id),
                })),
        }))
        // amount will be calculated from lines on the backend
        payload.amount = 0
    } else {
        payload.amount = Number(form.amount)
    }

    try {
        if (editing.value) {
            await updateTransaction.mutateAsync({ id: props.transaction.id, data: payload })
        } else {
            await createTransaction.mutateAsync(payload)
        }
        emit('saved')
        emit('close')
    } catch (err) {
        error.value = err.response?.data?.message ?? 'No se pudo guardar el registro.'
    }
}
</script>
