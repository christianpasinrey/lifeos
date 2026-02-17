<template>
    <div class="space-y-8">
        <header class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-sm uppercase tracking-[0.2em] text-primary-300/70">Panel financiero</p>
                <h1 class="text-3xl font-semibold text-white mt-1">Finanzas</h1>
                <p class="text-surface-400 mt-2 max-w-2xl">
                    Registra tus ingresos y gastos, organiza etiquetas y mantén un resumen claro de tu flujo de efectivo.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <button class="btn-add" @click="openTransactionForm()">
                    <PlusIcon class="w-4 h-4" />
                    Nuevo registro
                </button>
                <button
                    class="px-4 py-2 rounded-xl border border-white/10 text-sm font-medium text-surface-100 hover:bg-white/10 transition"
                    @click="toggleCategoryForm()"
                >
                    Gestionar categorías
                </button>
                <button
                    v-if="auth.hasModule('ai_coach')"
                    class="px-4 py-2 rounded-xl text-sm font-medium bg-primary-500/20 text-primary-100 hover:bg-primary-500/30 transition"
                    @click="openCoach"
                >
                    Analizar con IA
                </button>
            </div>
        </header>

        <!-- Summary cards -->
        <div class="grid gap-4 md:grid-cols-3">
            <div class="liquid-glass liquid-glass-card p-6">
                <div class="flex items-center gap-3">
                    <ArrowUpCircleIcon class="w-6 h-6 text-accent-400" />
                    <p class="text-sm text-surface-400">Ingresos</p>
                </div>
                <p class="text-3xl font-semibold text-white mt-3">{{ formatCurrency(summary.income) }}</p>
                <p class="text-xs text-surface-500 mt-1">Periodo {{ summary.period.start }} → {{ summary.period.end }}</p>
            </div>
            <div class="liquid-glass liquid-glass-card p-6">
                <div class="flex items-center gap-3">
                    <ArrowDownCircleIcon class="w-6 h-6 text-danger-400" />
                    <p class="text-sm text-surface-400">Gastos</p>
                </div>
                <p class="text-3xl font-semibold text-white mt-3">{{ formatCurrency(summary.expenses) }}</p>
                <p class="text-xs text-surface-500 mt-1">{{ expenseRatioText }}</p>
            </div>
            <div class="liquid-glass liquid-glass-card p-6">
                <div class="flex items-center gap-3">
                    <WalletIcon class="w-6 h-6 text-primary-300" />
                    <p class="text-sm text-surface-400">Balance</p>
                </div>
                <p
                    class="text-3xl font-semibold mt-3"
                    :class="summary.balance >= 0 ? 'text-accent-400' : 'text-danger-400'"
                >
                    {{ formatCurrency(summary.balance) }}
                </p>
                <p class="text-xs text-surface-500 mt-1">
                    {{ summary.balance >= 0 ? 'Superávit' : 'Déficit' }} mensual
                </p>
            </div>
        </div>

        <!-- Filters -->
        <div class="liquid-glass liquid-glass-card p-5 relative z-10">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                    <div>
                        <label class="form-label">Desde</label>
                        <input v-model="range.startDate" type="date" class="form-input" />
                    </div>
                    <div>
                        <label class="form-label">Hasta</label>
                        <input v-model="range.endDate" type="date" class="form-input" />
                    </div>
                </div>
                <div class="flex flex-col gap-3 sm:flex-row">
                    <div>
                        <label class="form-label">Tipo</label>
                        <CustomSelect v-model="typeFilter" :options="typeFilterOptions" />
                    </div>
                    <div>
                        <label class="form-label">Categoría</label>
                        <CustomSelect v-model="categoryFilter" :options="categoryFilterOptions" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Main content -->
        <div class="grid gap-6 lg:grid-cols-3">
            <section class="liquid-glass liquid-glass-card p-6 lg:col-span-2">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-xl font-semibold text-white">Registros</h3>
                        <p class="text-sm text-surface-500">{{ transactions.length }} movimientos en el período</p>
                    </div>
                    <button class="text-sm text-primary-200 hover:text-primary-100" @click="openTransactionForm()">
                        + Añadir uno nuevo
                    </button>
                </div>

                <div v-if="transactionsLoading" class="text-center py-10 text-surface-500">
                    Cargando movimientos...
                </div>
                <div v-else-if="transactions.length === 0" class="text-center py-10 text-surface-500">
                    No hay transacciones con estos filtros
                </div>
                <ul v-else class="space-y-4">
                    <li
                        v-for="tx in transactions"
                        :key="tx.id"
                        class="flex flex-col gap-3 rounded-2xl border border-white/5 bg-white/[0.02] p-4 md:flex-row md:items-center"
                    >
                        <div class="flex items-center gap-4 flex-1">
                            <div
                                class="rounded-2xl p-3 text-white"
                                :class="tx.type === 'income' ? 'bg-accent-500/20 text-accent-400' : 'bg-danger-500/20 text-danger-400'"
                            >
                                <component :is="tx.type === 'income' ? ArrowUpCircleIcon : ArrowDownCircleIcon" class="w-6 h-6" />
                            </div>
                            <div>
                                <p class="text-base font-medium text-white">{{ tx.description }}</p>
                                <p class="text-sm text-surface-500 flex items-center gap-2">
                                    <span>{{ formatDate(tx.date) }}</span>
                                    <span>•</span>
                                    <span :style="categoryStyle(tx.category)">
                                        {{ tx.category?.name ?? 'Sin categoría' }}
                                    </span>
                                </p>
                                <p v-if="tx.notes" class="text-xs text-surface-500 mt-1">{{ tx.notes }}</p>
                            </div>
                        </div>
                        <div class="flex flex-col items-start gap-2 text-right md:items-end">
                            <p
                                class="text-lg font-semibold"
                                :class="tx.type === 'income' ? 'text-accent-400' : 'text-danger-400'"
                            >
                                {{ tx.type === 'income' ? '+' : '-' }}{{ formatCurrency(tx.amount) }}
                            </p>
                            <div class="flex gap-2">
                                <button class="text-xs font-medium text-surface-400 hover:text-white transition" @click="openTransactionForm(tx)">Editar</button>
                                <button class="text-xs font-medium text-danger-400 hover:text-danger-500 transition" @click="deleteTx(tx)">Eliminar</button>
                            </div>
                        </div>
                    </li>
                </ul>
            </section>

            <section class="liquid-glass liquid-glass-card p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-xl font-semibold text-white">Categorías</h3>
                        <p class="text-sm text-surface-500">Etiquetas para ingresos y gastos</p>
                    </div>
                    <span class="text-sm text-surface-500">{{ categories.length }} activas</span>
                </div>

                <div v-if="categoriesLoading" class="text-center py-6 text-surface-500">Cargando...</div>
                <ul v-else class="space-y-3">
                    <li
                        v-for="cat in categories"
                        :key="cat.id"
                        class="flex items-center justify-between rounded-2xl border border-white/5 bg-white/[0.02] px-4 py-3"
                    >
                        <div class="flex items-center gap-3">
                            <span class="w-2 h-8 rounded-full" :style="{ background: cat.color }" />
                            <div>
                                <p class="text-sm font-medium text-white">{{ cat.name }}</p>
                                <p class="text-xs text-surface-500 uppercase tracking-wide">{{ typeLabel(cat.type) }}</p>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <button class="text-xs font-medium text-surface-400 hover:text-white transition" @click="startEditCategory(cat)">Editar</button>
                            <button class="text-xs font-medium text-danger-400 hover:text-danger-500 transition" @click="deleteCategory(cat)">Eliminar</button>
                        </div>
                    </li>
                </ul>

            </section>
        </div>

        <!-- Category modal -->
        <Teleport to="body">
            <div v-if="showCategoryPanel" class="modal-overlay" @mousedown.self="toggleCategoryForm">
                <div class="modal-backdrop" />
                <div class="modal-content liquid-glass liquid-glass-panel max-w-md">
                    <h2 class="section-title mb-5">
                        {{ editingCategory ? 'Editar categoría' : 'Nueva categoría' }}
                    </h2>
                    <form class="form-group" @submit.prevent="submitCategory">
                        <div>
                            <label class="form-label">Nombre</label>
                            <input v-model="categoryForm.name" type="text" class="form-input" required />
                        </div>
                        <div>
                            <label class="form-label">Tipo</label>
                            <CustomSelect v-model="categoryForm.type" :options="categoryTypeOptions" />
                        </div>
                        <div>
                            <label class="form-label">Color</label>
                            <div class="flex items-center gap-3">
                                <input v-model="categoryForm.color" type="color" class="w-10 h-10 rounded-lg border border-white/10 bg-transparent cursor-pointer" />
                                <span class="text-sm text-surface-400">{{ categoryForm.color }}</span>
                            </div>
                        </div>
                        <p v-if="categoryError" class="form-error">{{ categoryError }}</p>
                        <div class="flex justify-end gap-3 mt-2">
                            <button type="button" class="btn-secondary" @click="toggleCategoryForm">Cancelar</button>
                            <button class="btn-primary" type="submit" :disabled="categoryMutationPending">
                                Guardar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- Transaction modal -->
        <Teleport to="body">
            <div v-if="showTransactionForm" class="modal-overlay" @mousedown.self="closeTransactionForm">
                <div class="modal-backdrop" />
                <div class="modal-content liquid-glass liquid-glass-panel max-w-lg">
                    <h2 class="section-title mb-5">
                        {{ editingTransaction ? 'Editar registro' : 'Nuevo registro' }}
                    </h2>
                    <form class="form-group" @submit.prevent="submitTransaction">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="form-label">Tipo</label>
                                <CustomSelect v-model="transactionForm.type" :options="transactionTypeOptions" />
                            </div>
                            <div>
                                <label class="form-label">Cantidad (€)</label>
                                <input v-model="transactionForm.amount" type="number" step="0.01" min="0" class="form-input" required />
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Descripción</label>
                            <input v-model="transactionForm.description" type="text" class="form-input" required />
                        </div>
                        <div>
                            <label class="form-label">Notas</label>
                            <textarea v-model="transactionForm.notes" rows="2" class="form-input" placeholder="Opcional"></textarea>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="form-label">Categoría</label>
                                <CustomSelect v-model="transactionForm.category_id" :options="transactionCategoryOptions" placeholder="Sin categoría" />
                            </div>
                            <div>
                                <label class="form-label">Fecha</label>
                                <input v-model="transactionForm.date" type="date" class="form-input" required />
                            </div>
                        </div>
                        <p v-if="transactionError" class="form-error">{{ transactionError }}</p>
                        <div class="flex justify-end gap-3 mt-2">
                            <button type="button" class="btn-secondary" @click="closeTransactionForm">Cancelar</button>
                            <button class="btn-primary" type="submit" :disabled="transactionMutationPending">
                                Guardar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script setup>
import { computed, reactive, ref } from 'vue'
import { useAuthStore } from '@/stores/auth'
import {
    ArrowDownCircleIcon,
    ArrowUpCircleIcon,
    WalletIcon,
    PlusIcon,
} from '@heroicons/vue/24/outline'
import CustomSelect from '@/components/ui/CustomSelect.vue'
import {
    useFinanceSummary,
    useFinanceTransactions,
    useFinanceCategories,
    useCreateTransaction,
    useUpdateTransaction,
    useDeleteTransaction,
    useCreateCategory,
    useUpdateCategory,
    useDeleteCategory,
} from '@/composables/useFinance'

const auth = useAuthStore()

function formatDateInput(date) {
    const d = new Date(date)
    return d.toISOString().slice(0, 10)
}

function today() {
    return new Date()
}

function startOfMonth(date) {
    return new Date(date.getFullYear(), date.getMonth(), 1)
}

const range = reactive({
    startDate: formatDateInput(startOfMonth(today())),
    endDate: formatDateInput(today()),
})

const typeFilter = ref('all')
const categoryFilter = ref('all')

const summaryFilters = computed(() => ({
    startDate: range.startDate,
    endDate: range.endDate,
}))

const transactionFilters = computed(() => ({
    startDate: range.startDate,
    endDate: range.endDate,
    type: typeFilter.value === 'all' ? null : typeFilter.value,
    categoryId: categoryFilter.value === 'all' ? null : Number(categoryFilter.value),
}))

const { data: summaryData } = useFinanceSummary(summaryFilters)
const { data: transactionsData, isLoading: transactionsLoading } = useFinanceTransactions(transactionFilters)
const { data: categoriesData, isLoading: categoriesLoading } = useFinanceCategories()

const summary = computed(() => summaryData.value?.data ?? {
    income: 0,
    expenses: 0,
    balance: 0,
    period: { start: range.startDate, end: range.endDate },
    by_category: [],
})

const transactions = computed(() => transactionsData.value?.data ?? [])
const categories = computed(() => categoriesData.value?.data ?? [])

const typeFilterOptions = [
    { value: 'all', label: 'Ingresos y gastos' },
    { value: 'income', label: 'Solo ingresos' },
    { value: 'expense', label: 'Solo gastos' },
]

const categoryFilterOptions = computed(() => [
    { value: 'all', label: 'Todas' },
    ...categories.value.map(cat => ({ value: String(cat.id), label: cat.name })),
])

const transactionTypeOptions = [
    { value: 'income', label: 'Ingreso' },
    { value: 'expense', label: 'Gasto' },
]

const categoryTypeOptions = [
    { value: 'both', label: 'Ingresos y gastos' },
    { value: 'income', label: 'Solo ingresos' },
    { value: 'expense', label: 'Solo gastos' },
]

const transactionCategoryOptions = computed(() => [
    { value: '', label: 'Sin categoría' },
    ...categories.value.map(cat => ({ value: String(cat.id), label: cat.name })),
])

const expenseRatioText = computed(() => {
    if (!summary.value.income) return 'Aún no hay ingresos registrados'
    const ratio = (summary.value.expenses / summary.value.income) * 100
    return `Los gastos representan ${ratio.toFixed(1)}% de tus ingresos`
})

const createTransaction = useCreateTransaction()
const updateTransaction = useUpdateTransaction()
const deleteTransactionMutation = useDeleteTransaction()

const createCategory = useCreateCategory()
const updateCategory = useUpdateCategory()
const deleteCategoryMutation = useDeleteCategory()

const showTransactionForm = ref(false)
const editingTransaction = ref(null)
const transactionError = ref('')

const transactionForm = reactive({
    type: 'expense',
    amount: '',
    description: '',
    category_id: '',
    date: range.endDate,
    notes: '',
})

function resetTransactionForm() {
    transactionForm.type = 'expense'
    transactionForm.amount = ''
    transactionForm.description = ''
    transactionForm.category_id = ''
    transactionForm.date = range.endDate
    transactionForm.notes = ''
    editingTransaction.value = null
    transactionError.value = ''
}

function openTransactionForm(tx = null) {
    if (tx) {
        editingTransaction.value = tx
        transactionForm.type = tx.type
        transactionForm.amount = tx.amount
        transactionForm.description = tx.description
        transactionForm.category_id = tx.category ? String(tx.category.id) : ''
        transactionForm.date = tx.date
        transactionForm.notes = tx.notes ?? ''
    } else {
        resetTransactionForm()
    }
    showTransactionForm.value = true
}

function closeTransactionForm() {
    showTransactionForm.value = false
    resetTransactionForm()
}

const transactionMutationPending = computed(() =>
    createTransaction.isPending.value || updateTransaction.isPending.value
)

async function submitTransaction() {
    transactionError.value = ''

    const payload = {
        type: transactionForm.type,
        amount: Number(transactionForm.amount),
        description: transactionForm.description,
        notes: transactionForm.notes || null,
        category_id: transactionForm.category_id ? Number(transactionForm.category_id) : null,
        date: transactionForm.date,
    }

    try {
        if (editingTransaction.value) {
            await updateTransaction.mutateAsync({ id: editingTransaction.value.id, data: payload })
        } else {
            await createTransaction.mutateAsync(payload)
        }
        closeTransactionForm()
    } catch (error) {
        transactionError.value = error.response?.data?.message ?? 'No se pudo guardar el registro.'
    }
}

async function deleteTx(tx) {
    if (!confirm('¿Eliminar este registro?')) return
    await deleteTransactionMutation.mutateAsync(tx.id)
}

const showCategoryPanel = ref(false)
const editingCategory = ref(null)
const categoryError = ref('')

const categoryForm = reactive({
    name: '',
    type: 'both',
    color: '#6366f1',
})

function toggleCategoryForm() {
    showCategoryPanel.value = !showCategoryPanel.value
    if (!showCategoryPanel.value) {
        resetCategoryForm()
    }
}

function resetCategoryForm() {
    categoryForm.name = ''
    categoryForm.type = 'both'
    categoryForm.color = '#6366f1'
    editingCategory.value = null
    categoryError.value = ''
}

function startEditCategory(category) {
    showCategoryPanel.value = true
    editingCategory.value = category
    categoryForm.name = category.name
    categoryForm.type = category.type
    categoryForm.color = category.color
}

const categoryMutationPending = computed(() =>
    createCategory.isPending.value || updateCategory.isPending.value
)

async function submitCategory() {
    categoryError.value = ''
    const payload = { ...categoryForm }

    try {
        if (editingCategory.value) {
            await updateCategory.mutateAsync({ id: editingCategory.value.id, data: payload })
        } else {
            await createCategory.mutateAsync(payload)
        }
        resetCategoryForm()
        showCategoryPanel.value = false
    } catch (error) {
        categoryError.value = error.response?.data?.message ?? 'No se pudo guardar la categoría.'
    }
}

async function deleteCategory(category) {
    if (!confirm('¿Eliminar esta categoría? Las transacciones quedarán sin etiqueta.')) return
    await deleteCategoryMutation.mutateAsync(category.id)
}

function categoryStyle(category) {
    if (!category?.color) return { color: '#a5b4fc' }
    return { color: category.color }
}

function typeLabel(type) {
    if (type === 'income') return 'Ingresos'
    if (type === 'expense') return 'Gastos'
    return 'Ambos'
}

function formatCurrency(value) {
    return new Intl.NumberFormat('es-ES', {
        style: 'currency',
        currency: 'EUR',
        minimumFractionDigits: 2,
    }).format(Number(value ?? 0))
}

function formatDate(date) {
    const d = new Date(date)
    return d.toLocaleDateString('es-ES', { day: '2-digit', month: 'short' })
}

function openCoach() {
    window.dispatchEvent(new CustomEvent('open-coach-chat'))
}
</script>
