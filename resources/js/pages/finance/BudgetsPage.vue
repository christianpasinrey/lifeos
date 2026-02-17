<template>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <p class="text-surface-400">Establece límites de gasto por categoría y periodo</p>
            <button class="btn-add" @click="openForm()">
                <PlusIcon class="w-4 h-4" />
                Nuevo presupuesto
            </button>
        </div>

        <!-- Progress overview -->
        <div v-if="progress.length > 0" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div
                v-for="p in progress"
                :key="p.budget_id"
                class="liquid-glass liquid-glass-card p-5 space-y-3"
            >
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-white">{{ p.name }}</h3>
                    <span
                        class="text-xs px-2 py-0.5 rounded font-medium"
                        :class="p.over_budget ? 'bg-danger-500/20 text-danger-400' : 'bg-accent-500/20 text-accent-400'"
                    >
                        {{ p.over_budget ? 'Excedido' : 'En rango' }}
                    </span>
                </div>
                <div class="w-full h-3 rounded-full bg-white/5 overflow-hidden">
                    <div
                        class="h-full rounded-full transition-all duration-500"
                        :class="p.percentage > 100 ? 'bg-danger-400' : p.percentage > 80 ? 'bg-yellow-400' : 'bg-accent-400'"
                        :style="{ width: Math.min(p.percentage, 100) + '%' }"
                    />
                </div>
                <div class="flex justify-between text-xs text-surface-400">
                    <span>{{ formatCurrency(p.spent) }} gastado</span>
                    <span>{{ formatCurrency(p.limit) }} límite</span>
                </div>
                <p class="text-xs text-surface-500">{{ p.period_start }} → {{ p.period_end }}</p>
            </div>
        </div>

        <!-- Budgets list -->
        <div v-if="isLoading" class="text-center py-10 text-surface-500">Cargando...</div>
        <div v-else-if="budgets.length === 0" class="text-center py-10 text-surface-500">
            No tienes presupuestos configurados
        </div>
        <div v-else class="liquid-glass liquid-glass-card p-6">
            <h3 class="text-lg font-semibold text-white mb-4">Todos los presupuestos</h3>
            <ul class="space-y-3">
                <li
                    v-for="b in budgets"
                    :key="b.id"
                    class="flex items-center justify-between rounded-xl border border-white/5 bg-white/[0.02] p-4"
                >
                    <div>
                        <p class="text-sm font-medium text-white">{{ b.name }}</p>
                        <p class="text-xs text-surface-500">
                            {{ formatCurrency(b.amount) }} / {{ periodLabel(b.period) }}
                            <template v-if="b.category"> • {{ b.category.name }}</template>
                        </p>
                    </div>
                    <div class="flex gap-2">
                        <button class="text-xs text-surface-400 hover:text-white transition" @click="openForm(b)">Editar</button>
                        <button class="text-xs text-danger-400 hover:text-danger-500 transition" @click="remove(b)">Eliminar</button>
                    </div>
                </li>
            </ul>
        </div>

        <!-- Form modal -->
        <Teleport to="body">
            <div v-if="showForm" class="modal-overlay" @mousedown.self="showForm = false">
                <div class="modal-backdrop" />
                <div class="modal-content liquid-glass liquid-glass-panel max-w-md">
                    <h2 class="section-title mb-5">
                        {{ editing ? 'Editar presupuesto' : 'Nuevo presupuesto' }}
                    </h2>
                    <form class="form-group" @submit.prevent="submit">
                        <div>
                            <label class="form-label">Nombre</label>
                            <input v-model="form.name" type="text" class="form-input" required />
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="form-label">Límite (EUR)</label>
                                <input v-model="form.amount" type="number" step="0.01" min="0.01" class="form-input" required />
                            </div>
                            <div>
                                <label class="form-label">Periodo</label>
                                <CustomSelect v-model="form.period" :options="periodOptions" />
                            </div>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="form-label">Categoría (opcional)</label>
                                <CustomSelect v-model="form.category_id" :options="categoryOptions" placeholder="Todas" />
                            </div>
                            <div>
                                <label class="form-label">Desde</label>
                                <input v-model="form.start_date" type="date" class="form-input" required />
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Color</label>
                            <div class="flex items-center gap-3">
                                <input v-model="form.color" type="color" class="w-10 h-10 rounded-lg border border-white/10 bg-transparent cursor-pointer" />
                                <span class="text-sm text-surface-400">{{ form.color }}</span>
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
import { PlusIcon } from '@heroicons/vue/24/outline'
import CustomSelect from '@/components/ui/CustomSelect.vue'
import { useFinanceCategories } from '@/composables/useFinance'
import {
    useFinanceBudgets,
    useFinanceBudgetProgress,
    useCreateBudget,
    useUpdateBudget,
    useDeleteBudget,
} from '@/composables/useFinanceBudgets'

const { data: budgetsData, isLoading } = useFinanceBudgets(false)
const { data: progressData } = useFinanceBudgetProgress()
const { data: categoriesData } = useFinanceCategories()

const budgets = computed(() => budgetsData.value?.data ?? [])
const progress = computed(() => progressData.value?.data ?? [])
const categories = computed(() => categoriesData.value?.data ?? [])

const createBudget = useCreateBudget()
const updateBudget = useUpdateBudget()
const deleteBudget = useDeleteBudget()

const showForm = ref(false)
const editing = ref(null)
const error = ref('')

const form = reactive({
    name: '',
    amount: '',
    period: 'monthly',
    start_date: new Date().toISOString().slice(0, 10),
    category_id: '',
    color: '#6366f1',
})

const periodOptions = [
    { value: 'monthly', label: 'Mensual' },
    { value: 'quarterly', label: 'Trimestral' },
    { value: 'yearly', label: 'Anual' },
]

const categoryOptions = computed(() => [
    { value: '', label: 'Todas las categorías' },
    ...categories.value.map(c => ({ value: String(c.id), label: c.name })),
])

function periodLabel(p) {
    return { monthly: 'Mensual', quarterly: 'Trimestral', yearly: 'Anual' }[p] || p
}

const mutationPending = computed(() =>
    createBudget.isPending.value || updateBudget.isPending.value
)

function openForm(budget = null) {
    error.value = ''
    if (budget) {
        editing.value = budget
        form.name = budget.name
        form.amount = budget.amount
        form.period = budget.period
        form.start_date = budget.start_date
        form.category_id = budget.category_id ? String(budget.category_id) : ''
        form.color = budget.color || '#6366f1'
    } else {
        editing.value = null
        form.name = ''
        form.amount = ''
        form.period = 'monthly'
        form.start_date = new Date().toISOString().slice(0, 10)
        form.category_id = ''
        form.color = '#6366f1'
    }
    showForm.value = true
}

async function submit() {
    error.value = ''
    const payload = {
        ...form,
        amount: Number(form.amount),
        category_id: form.category_id ? Number(form.category_id) : null,
    }
    try {
        if (editing.value) {
            await updateBudget.mutateAsync({ id: editing.value.id, data: payload })
        } else {
            await createBudget.mutateAsync(payload)
        }
        showForm.value = false
    } catch (e) {
        error.value = e.response?.data?.message ?? 'Error al guardar'
    }
}

async function remove(budget) {
    if (!confirm(`¿Eliminar "${budget.name}"?`)) return
    await deleteBudget.mutateAsync(budget.id)
}

function formatCurrency(value) {
    return new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR', minimumFractionDigits: 2 }).format(Number(value ?? 0))
}
</script>
