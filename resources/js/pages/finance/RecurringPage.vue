<template>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <p class="text-surface-400">Transacciones automáticas programadas</p>
            <button class="btn-add" @click="openForm()">
                <PlusIcon class="w-4 h-4" />
                Nueva recurrente
            </button>
        </div>

        <div v-if="isLoading" class="text-center py-10 text-surface-500">Cargando...</div>
        <div v-else-if="items.length === 0" class="text-center py-10 text-surface-500">
            No tienes transacciones recurrentes
        </div>

        <div v-else class="space-y-4">
            <div
                v-for="item in items"
                :key="item.id"
                class="liquid-glass liquid-glass-card p-6"
            >
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div class="flex items-center gap-4">
                        <div
                            class="rounded-2xl p-3"
                            :class="item.type === 'income' ? 'bg-accent-500/20 text-accent-400' : 'bg-danger-500/20 text-danger-400'"
                        >
                            <component :is="item.type === 'income' ? ArrowUpCircleIcon : ArrowDownCircleIcon" class="w-6 h-6" />
                        </div>
                        <div>
                            <p class="text-base font-medium text-white">{{ item.description }}</p>
                            <p class="text-sm text-surface-500">
                                {{ formatCurrency(item.amount) }} •
                                {{ frequencyLabel(item) }} •
                                {{ item.category?.name ?? 'Sin categoría' }}
                            </p>
                            <p class="text-xs text-surface-500 mt-1">
                                Próxima: {{ item.next_due_date }}
                                <template v-if="item.end_date"> • Hasta: {{ item.end_date }}</template>
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <span
                            class="text-xs px-2 py-0.5 rounded font-medium"
                            :class="item.is_active ? 'bg-accent-500/20 text-accent-400' : 'bg-surface-700 text-surface-400'"
                        >
                            {{ item.is_active ? 'Activa' : 'Pausada' }}
                        </span>
                        <button class="text-xs text-surface-400 hover:text-white transition" @click="openForm(item)">Editar</button>
                        <button class="text-xs text-danger-400 hover:text-danger-500 transition" @click="remove(item)">Eliminar</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form modal -->
        <Teleport to="body">
            <div v-if="showForm" class="modal-overlay" @mousedown.self="showForm = false">
                <div class="modal-backdrop" />
                <div class="modal-content liquid-glass liquid-glass-panel max-w-lg">
                    <h2 class="section-title mb-5">
                        {{ editing ? 'Editar recurrente' : 'Nueva recurrente' }}
                    </h2>
                    <form class="form-group" @submit.prevent="submit">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="form-label">Tipo</label>
                                <CustomSelect v-model="form.type" :options="typeOptions" />
                            </div>
                            <div>
                                <label class="form-label">Importe (EUR)</label>
                                <input v-model="form.amount" type="number" step="0.01" min="0.01" class="form-input" required />
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Descripción</label>
                            <input v-model="form.description" type="text" class="form-input" required />
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="form-label">Frecuencia</label>
                                <CustomSelect v-model="form.frequency" :options="frequencyOptions" />
                            </div>
                            <div>
                                <label class="form-label">Cada (intervalo)</label>
                                <input v-model="form.interval" type="number" min="1" class="form-input" />
                            </div>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="form-label">Fecha inicio</label>
                                <input v-model="form.start_date" type="date" class="form-input" required />
                            </div>
                            <div>
                                <label class="form-label">Fecha fin (opcional)</label>
                                <input v-model="form.end_date" type="date" class="form-input" />
                            </div>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="form-label">Categoría</label>
                                <CustomSelect v-model="form.category_id" :options="categoryOptions" placeholder="Sin categoría" />
                            </div>
                            <div>
                                <label class="form-label">Cuenta</label>
                                <AccountSelector v-model="form.account_id" :includeAll="false" placeholder="Sin cuenta" />
                            </div>
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
import { ArrowUpCircleIcon, ArrowDownCircleIcon, PlusIcon } from '@heroicons/vue/24/outline'
import CustomSelect from '@/components/ui/CustomSelect.vue'
import AccountSelector from './components/AccountSelector.vue'
import { useFinanceCategories } from '@/composables/useFinance'
import {
    useFinanceRecurring,
    useCreateRecurring,
    useUpdateRecurring,
    useDeleteRecurring,
} from '@/composables/useFinanceRecurring'

const { data: recurringData, isLoading } = useFinanceRecurring(false)
const { data: categoriesData } = useFinanceCategories()

const items = computed(() => recurringData.value?.data ?? [])
const categories = computed(() => categoriesData.value?.data ?? [])

const createRecurring = useCreateRecurring()
const updateRecurring = useUpdateRecurring()
const deleteRecurring = useDeleteRecurring()

const showForm = ref(false)
const editing = ref(null)
const error = ref('')

const form = reactive({
    type: 'expense',
    amount: '',
    description: '',
    frequency: 'monthly',
    interval: 1,
    start_date: new Date().toISOString().slice(0, 10),
    end_date: '',
    category_id: '',
    account_id: '',
})

const typeOptions = [
    { value: 'income', label: 'Ingreso' },
    { value: 'expense', label: 'Gasto' },
]

const frequencyOptions = [
    { value: 'daily', label: 'Diaria' },
    { value: 'weekly', label: 'Semanal' },
    { value: 'monthly', label: 'Mensual' },
    { value: 'yearly', label: 'Anual' },
]

const categoryOptions = computed(() => [
    { value: '', label: 'Sin categoría' },
    ...categories.value.map(c => ({ value: String(c.id), label: c.name })),
])

const mutationPending = computed(() =>
    createRecurring.isPending.value || updateRecurring.isPending.value
)

function frequencyLabel(item) {
    const labels = { daily: 'diaria', weekly: 'semanal', monthly: 'mensual', yearly: 'anual' }
    const freq = labels[item.frequency] || item.frequency
    return item.interval > 1 ? `Cada ${item.interval} ${freq}s` : freq.charAt(0).toUpperCase() + freq.slice(1)
}

function openForm(item = null) {
    error.value = ''
    if (item) {
        editing.value = item
        form.type = item.type
        form.amount = item.amount
        form.description = item.description
        form.frequency = item.frequency
        form.interval = item.interval
        form.start_date = item.start_date
        form.end_date = item.end_date || ''
        form.category_id = item.category_id ? String(item.category_id) : ''
        form.account_id = item.account_id ? String(item.account_id) : ''
    } else {
        editing.value = null
        form.type = 'expense'
        form.amount = ''
        form.description = ''
        form.frequency = 'monthly'
        form.interval = 1
        form.start_date = new Date().toISOString().slice(0, 10)
        form.end_date = ''
        form.category_id = ''
        form.account_id = ''
    }
    showForm.value = true
}

async function submit() {
    error.value = ''
    const payload = {
        type: form.type,
        amount: Number(form.amount),
        description: form.description,
        frequency: form.frequency,
        interval: Number(form.interval),
        start_date: form.start_date,
        end_date: form.end_date || null,
        category_id: form.category_id ? Number(form.category_id) : null,
        account_id: form.account_id ? Number(form.account_id) : null,
    }
    try {
        if (editing.value) {
            await updateRecurring.mutateAsync({ id: editing.value.id, data: payload })
        } else {
            await createRecurring.mutateAsync(payload)
        }
        showForm.value = false
    } catch (e) {
        error.value = e.response?.data?.message ?? 'Error al guardar'
    }
}

async function remove(item) {
    if (!confirm(`¿Eliminar "${item.description}"?`)) return
    await deleteRecurring.mutateAsync(item.id)
}

function formatCurrency(value) {
    return new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR', minimumFractionDigits: 2 }).format(Number(value ?? 0))
}
</script>
