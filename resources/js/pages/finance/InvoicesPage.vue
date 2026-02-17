<template>
    <div class="space-y-6">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div class="flex flex-wrap gap-2">
                <button
                    v-for="tab in typeTabs"
                    :key="tab.value"
                    class="px-4 py-2 rounded-lg text-sm font-medium transition"
                    :class="typeFilter === tab.value
                        ? 'bg-primary-500/20 text-primary-200'
                        : 'text-surface-400 hover:text-surface-200 hover:bg-white/5'"
                    @click="typeFilter = tab.value"
                >{{ tab.label }}</button>
            </div>
            <div class="flex gap-3">
                <CustomSelect v-model="statusFilter" :options="statusOptions" />
                <button class="btn-add" @click="openForm()">
                    <PlusIcon class="w-4 h-4" />
                    Nueva factura
                </button>
            </div>
        </div>

        <div v-if="isLoading" class="text-center py-10 text-surface-500">Cargando...</div>
        <div v-else-if="invoices.length === 0" class="text-center py-10 text-surface-500">
            No hay facturas con estos filtros
        </div>

        <div v-else class="space-y-4">
            <div
                v-for="inv in invoices"
                :key="inv.id"
                class="liquid-glass liquid-glass-card p-6"
            >
                <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                    <div class="space-y-1">
                        <div class="flex items-center gap-3">
                            <span class="text-lg font-semibold text-white">{{ inv.number }}</span>
                            <span
                                class="text-xs px-2 py-0.5 rounded font-medium"
                                :class="statusClass(inv.status)"
                            >{{ statusLabel(inv.status) }}</span>
                            <span class="text-xs text-surface-500">{{ inv.type === 'issued' ? 'Emitida' : 'Recibida' }}</span>
                        </div>
                        <p class="text-sm text-surface-300">{{ inv.counterpart_name }}</p>
                        <p class="text-xs text-surface-500">
                            Emitida: {{ formatDate(inv.issue_date) }}
                            <template v-if="inv.due_date"> • Vence: {{ formatDate(inv.due_date) }}</template>
                        </p>
                    </div>
                    <div class="text-right space-y-1">
                        <p class="text-xl font-semibold text-white">{{ formatCurrency(inv.total) }}</p>
                        <p v-if="inv.subtotal != inv.total" class="text-xs text-surface-500">
                            Base: {{ formatCurrency(inv.subtotal) }}
                            <template v-if="inv.tax_total > 0"> | +{{ formatCurrency(inv.tax_total) }}</template>
                            <template v-if="inv.retention_total > 0"> | -{{ formatCurrency(inv.retention_total) }}</template>
                        </p>
                        <div class="flex justify-end gap-2 mt-2">
                            <button class="text-xs text-surface-400 hover:text-white transition" @click="openForm(inv)">Editar</button>
                            <button class="text-xs text-danger-400 hover:text-danger-500 transition" @click="remove(inv)">Eliminar</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Invoice form modal -->
        <Teleport to="body">
            <div v-if="showForm" class="modal-overlay" @mousedown.self="showForm = false">
                <div class="modal-backdrop" />
                <div class="modal-content liquid-glass liquid-glass-panel max-w-2xl">
                    <h2 class="section-title mb-5">
                        {{ editing ? 'Editar factura' : 'Nueva factura' }}
                    </h2>
                    <form class="form-group" @submit.prevent="submit">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="form-label">Tipo</label>
                                <CustomSelect v-model="form.type" :options="invoiceTypeOptions" />
                            </div>
                            <div>
                                <label class="form-label">Número (auto si vacío)</label>
                                <input v-model="form.number" type="text" class="form-input" placeholder="Auto" />
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Nombre contraparte</label>
                            <input v-model="form.counterpart_name" type="text" class="form-input" required />
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="form-label">NIF/CIF contraparte</label>
                                <input v-model="form.counterpart_tax_id" type="text" class="form-input" />
                            </div>
                            <div>
                                <label class="form-label">Estado</label>
                                <CustomSelect v-model="form.status" :options="formStatusOptions" />
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Dirección contraparte</label>
                            <textarea v-model="form.counterpart_address" rows="2" class="form-input" placeholder="Opcional"></textarea>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="form-label">Fecha emisión</label>
                                <input v-model="form.issue_date" type="date" class="form-input" required />
                            </div>
                            <div>
                                <label class="form-label">Fecha vencimiento</label>
                                <input v-model="form.due_date" type="date" class="form-input" />
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Notas</label>
                            <textarea v-model="form.notes" rows="2" class="form-input" placeholder="Opcional"></textarea>
                        </div>

                        <!-- Line items -->
                        <div class="pt-4 border-t border-white/5">
                            <label class="form-label mb-3">Líneas de factura</label>
                            <TransactionLineEditor v-model="form.lines" />
                        </div>

                        <p v-if="error" class="form-error">{{ error }}</p>
                        <div class="flex justify-end gap-3 mt-2">
                            <button type="button" class="btn-secondary" @click="showForm = false">Cancelar</button>
                            <button class="btn-primary" type="submit" :disabled="mutationPending">Guardar</button>
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
import TransactionLineEditor from './components/TransactionLineEditor.vue'
import {
    useFinanceInvoices,
    useCreateInvoice,
    useUpdateInvoice,
    useDeleteInvoice,
} from '@/composables/useFinanceInvoices'

const typeFilter = ref('')
const statusFilter = ref('')

const filters = computed(() => ({
    type: typeFilter.value || null,
    status: statusFilter.value || null,
}))

const { data: invoicesData, isLoading } = useFinanceInvoices(filters)
const invoices = computed(() => invoicesData.value?.data ?? [])

const createInvoice = useCreateInvoice()
const updateInvoice = useUpdateInvoice()
const deleteInvoice = useDeleteInvoice()

const showForm = ref(false)
const editing = ref(null)
const error = ref('')

const form = reactive({
    type: 'issued',
    number: '',
    counterpart_name: '',
    counterpart_tax_id: '',
    counterpart_address: '',
    issue_date: new Date().toISOString().slice(0, 10),
    due_date: '',
    status: 'draft',
    notes: '',
    lines: [{ concept: '', quantity: 1, unit_price: '', taxes: [] }],
})

const typeTabs = [
    { value: '', label: 'Todas' },
    { value: 'issued', label: 'Emitidas' },
    { value: 'received', label: 'Recibidas' },
]

const statusOptions = [
    { value: '', label: 'Todos los estados' },
    { value: 'draft', label: 'Borrador' },
    { value: 'sent', label: 'Enviada' },
    { value: 'paid', label: 'Pagada' },
    { value: 'overdue', label: 'Vencida' },
    { value: 'cancelled', label: 'Cancelada' },
]

const invoiceTypeOptions = [
    { value: 'issued', label: 'Emitida' },
    { value: 'received', label: 'Recibida' },
]

const formStatusOptions = [
    { value: 'draft', label: 'Borrador' },
    { value: 'sent', label: 'Enviada' },
    { value: 'paid', label: 'Pagada' },
    { value: 'overdue', label: 'Vencida' },
    { value: 'cancelled', label: 'Cancelada' },
]

const mutationPending = computed(() =>
    createInvoice.isPending.value || updateInvoice.isPending.value
)

function statusClass(status) {
    const map = {
        draft: 'bg-surface-700 text-surface-300',
        sent: 'bg-primary-500/20 text-primary-300',
        paid: 'bg-accent-500/20 text-accent-400',
        overdue: 'bg-danger-500/20 text-danger-400',
        cancelled: 'bg-surface-700 text-surface-500',
    }
    return map[status] || ''
}

function statusLabel(status) {
    const map = { draft: 'Borrador', sent: 'Enviada', paid: 'Pagada', overdue: 'Vencida', cancelled: 'Cancelada' }
    return map[status] || status
}

function openForm(inv = null) {
    error.value = ''
    if (inv) {
        editing.value = inv
        form.type = inv.type
        form.number = inv.number
        form.counterpart_name = inv.counterpart_name
        form.counterpart_tax_id = inv.counterpart_tax_id || ''
        form.counterpart_address = inv.counterpart_address || ''
        form.issue_date = inv.issue_date
        form.due_date = inv.due_date || ''
        form.status = inv.status
        form.notes = inv.notes || ''
        form.lines = (inv.lines || []).map(l => ({
            concept: l.concept,
            quantity: l.quantity,
            unit_price: l.unit_price,
            taxes: (l.taxes || []).map(t => ({ tax_id: String(t.tax_id), tax_rate_id: String(t.tax_rate_id) })),
        }))
        if (form.lines.length === 0) {
            form.lines = [{ concept: '', quantity: 1, unit_price: '', taxes: [] }]
        }
    } else {
        editing.value = null
        form.type = 'issued'
        form.number = ''
        form.counterpart_name = ''
        form.counterpart_tax_id = ''
        form.counterpart_address = ''
        form.issue_date = new Date().toISOString().slice(0, 10)
        form.due_date = ''
        form.status = 'draft'
        form.notes = ''
        form.lines = [{ concept: '', quantity: 1, unit_price: '', taxes: [] }]
    }
    showForm.value = true
}

async function submit() {
    error.value = ''
    const payload = {
        type: form.type,
        number: form.number || null,
        counterpart_name: form.counterpart_name,
        counterpart_tax_id: form.counterpart_tax_id || null,
        counterpart_address: form.counterpart_address || null,
        issue_date: form.issue_date,
        due_date: form.due_date || null,
        status: form.status,
        notes: form.notes || null,
        lines: form.lines.filter(l => l.concept && l.unit_price).map((l, i) => ({
            concept: l.concept,
            quantity: Number(l.quantity) || 1,
            unit_price: Number(l.unit_price) || 0,
            sort_order: i,
            taxes: (l.taxes || []).filter(t => t.tax_id && t.tax_rate_id).map(t => ({
                tax_id: Number(t.tax_id),
                tax_rate_id: Number(t.tax_rate_id),
            })),
        })),
    }
    try {
        if (editing.value) {
            await updateInvoice.mutateAsync({ id: editing.value.id, data: payload })
        } else {
            await createInvoice.mutateAsync(payload)
        }
        showForm.value = false
    } catch (e) {
        error.value = e.response?.data?.message ?? 'Error al guardar'
    }
}

async function remove(inv) {
    if (!confirm(`¿Eliminar factura ${inv.number}?`)) return
    await deleteInvoice.mutateAsync(inv.id)
}

function formatCurrency(value) {
    return new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR', minimumFractionDigits: 2 }).format(Number(value ?? 0))
}

function formatDate(date) {
    return new Date(date).toLocaleDateString('es-ES', { day: '2-digit', month: 'short', year: 'numeric' })
}
</script>
