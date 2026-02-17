<template>
    <div class="space-y-6">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div class="flex flex-wrap items-center gap-3">
                <button class="btn-add" @click="openForm()">
                    <PlusIcon class="w-4 h-4" />
                    Nuevo registro
                </button>
                <button class="btn-secondary" @click="showImportModal = true">
                    <ArrowUpTrayIcon class="w-4 h-4" />
                    Importar
                </button>
                <template v-for="action in toolbarActions" :key="action.id || action.label">
                    <component v-if="action.component" :is="action.component" :transactions="transactions" />
                    <button
                        v-else
                        class="ai-chip"
                        @click="onSlotAction(action)"
                    >
                        <component v-if="action.icon" :is="action.icon" class="w-4 h-4" />
                        {{ action.label }}
                    </button>
                </template>
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
                    <div>
                        <label class="form-label">Cuenta</label>
                        <AccountSelector v-model="accountFilter" :includeAll="true" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Transaction list -->
        <div class="liquid-glass liquid-glass-card p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-xl font-semibold text-white">Registros</h3>
                    <p class="text-sm text-surface-500">{{ transactions.length }} movimientos</p>
                </div>
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
                            :class="tx.type === 'income' ? 'bg-accent-500/20 text-accent-400'
                                : tx.type === 'transfer' ? 'bg-primary-500/20 text-primary-400'
                                : 'bg-danger-500/20 text-danger-400'"
                        >
                            <component :is="txIcon(tx.type)" class="w-6 h-6" />
                        </div>
                        <div>
                            <p class="text-base font-medium text-white">{{ tx.description }}</p>
                            <p class="text-sm text-surface-500 flex items-center gap-2">
                                <span>{{ formatDate(tx.date) }}</span>
                                <span>•</span>
                                <span :style="categoryStyle(tx.category)">
                                    {{ tx.category?.name ?? 'Sin categoría' }}
                                </span>
                                <template v-if="tx.account">
                                    <span>•</span>
                                    <span class="text-surface-500">{{ tx.account.name }}</span>
                                </template>
                            </p>
                            <p v-if="tx.notes" class="text-xs text-surface-500 mt-1">{{ tx.notes }}</p>
                            <!-- Line items summary -->
                            <p v-if="tx.subtotal" class="text-xs text-surface-500 mt-1">
                                Base: {{ formatCurrency(tx.subtotal) }}
                                <template v-if="tx.tax_total"> | IVA: +{{ formatCurrency(tx.tax_total) }}</template>
                                <template v-if="tx.retention_total"> | Ret: -{{ formatCurrency(tx.retention_total) }}</template>
                            </p>
                            <!-- Row info slots (e.g. attachments) -->
                            <template v-for="slot in rowInfoSlots" :key="slot.id">
                                <component :is="slot.component" :transaction="tx" />
                            </template>
                        </div>
                    </div>
                    <div class="flex flex-col items-start gap-2 text-right md:items-end">
                        <p
                            class="text-lg font-semibold"
                            :class="tx.type === 'income' ? 'text-accent-400'
                                : tx.type === 'transfer' ? 'text-primary-300'
                                : 'text-danger-400'"
                        >
                            {{ tx.type === 'income' ? '+' : tx.type === 'transfer' ? '' : '-' }}{{ formatCurrency(tx.amount) }}
                        </p>
                        <div class="flex gap-2">
                            <!-- Row action slots (e.g. attach button) -->
                            <template v-for="slot in rowActionSlots" :key="slot.id">
                                <component :is="slot.component" :transaction="tx" />
                            </template>
                            <button class="text-xs font-medium text-surface-400 hover:text-white transition" @click="openForm(tx)">Editar</button>
                            <button class="text-xs font-medium text-danger-400 hover:text-danger-500 transition" @click="deleteTx(tx)">Eliminar</button>
                        </div>
                    </div>
                </li>
            </ul>
        </div>

        <TransactionFormModal
            :show="showForm"
            :transaction="editingTx"
            :defaultDate="range.endDate"
            @close="showForm = false; editingTx = null"
            @saved="showForm = false; editingTx = null"
        />

        <!-- Import modal -->
        <Teleport to="body">
            <div v-if="showImportModal" class="modal-overlay" @mousedown.self="closeImportModal">
                <div class="modal-backdrop" />
                <div class="modal-content liquid-glass liquid-glass-panel max-w-3xl">
                    <div class="flex items-center justify-between mb-5">
                        <div>
                            <h2 class="section-title">Importar transacciones</h2>
                            <p class="text-sm text-surface-400 mt-1">Sube un archivo XLS, XLSX o CSV de tu banco</p>
                        </div>
                        <button class="btn-close" @click="closeImportModal">
                            <XMarkIcon class="w-5 h-5" />
                        </button>
                    </div>

                    <!-- Step 1: File upload -->
                    <div v-if="importStep === 'upload'" class="space-y-4">
                        <div
                            class="border-2 border-dashed border-white/10 rounded-2xl p-8 text-center cursor-pointer hover:border-primary-400/40 transition"
                            @click="$refs.importFileInput.click()"
                            @dragover.prevent
                            @drop.prevent="handleImportDrop"
                        >
                            <ArrowUpTrayIcon class="w-10 h-10 mx-auto text-surface-500 mb-3" />
                            <p class="text-sm text-surface-300">
                                Arrastra un archivo aquí o <span class="text-primary-400">haz clic para seleccionar</span>
                            </p>
                            <p class="text-xs text-surface-500 mt-2">Formatos: XLS, XLSX, CSV (máx. 5 MB)</p>
                            <input
                                ref="importFileInput"
                                type="file"
                                accept=".xls,.xlsx,.csv,.txt"
                                class="hidden"
                                @change="handleImportFile"
                            />
                        </div>

                        <div>
                            <label class="form-label">Cuenta destino (opcional)</label>
                            <AccountSelector v-model="importAccountId" :includeAll="true" />
                        </div>
                    </div>

                    <!-- Step 2: Preview -->
                    <div v-else-if="importStep === 'preview'" class="space-y-4">
                        <div class="flex items-center gap-3 text-sm">
                            <span class="px-2.5 py-1 rounded-lg bg-primary-500/20 text-primary-300 font-medium">
                                {{ importFormatLabel }}
                            </span>
                            <span class="text-surface-400">{{ importPreviewData.total }} filas detectadas</span>
                        </div>

                        <div class="overflow-x-auto max-h-[50vh] rounded-xl border border-white/5">
                            <table class="w-full text-sm">
                                <thead class="bg-white/[0.03] sticky top-0">
                                    <tr>
                                        <th
                                            v-for="header in importPreviewData.headers"
                                            :key="header"
                                            class="px-3 py-2 text-left text-xs font-medium text-surface-400 whitespace-nowrap"
                                        >
                                            {{ header }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="(row, i) in importPreviewData.rows"
                                        :key="i"
                                        class="border-t border-white/5"
                                    >
                                        <td
                                            v-for="header in importPreviewData.headers"
                                            :key="header"
                                            class="px-3 py-2 text-surface-300 whitespace-nowrap"
                                        >
                                            {{ row[header] ?? '' }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="flex items-center justify-between pt-4 border-t border-white/5">
                            <button class="btn-secondary" @click="importStep = 'upload'; importPreviewData = null">
                                Cambiar archivo
                            </button>
                            <button
                                class="btn-primary"
                                :disabled="importTransactions.isPending.value"
                                @click="confirmImport"
                            >
                                {{ importTransactions.isPending.value ? 'Importando...' : `Importar ${importPreviewData.total} transacciones` }}
                            </button>
                        </div>
                    </div>

                    <!-- Step 3: Result -->
                    <div v-else-if="importStep === 'result'" class="space-y-4">
                        <div class="rounded-2xl border border-white/5 bg-white/[0.02] p-6 text-center space-y-3">
                            <CheckCircleIcon class="w-12 h-12 mx-auto text-accent-400" />
                            <p class="text-lg font-medium text-white">Importación completada</p>
                            <div class="flex justify-center gap-6 text-sm">
                                <div>
                                    <p class="text-2xl font-bold text-accent-400">{{ importResult.imported }}</p>
                                    <p class="text-surface-400">importadas</p>
                                </div>
                                <div>
                                    <p class="text-2xl font-bold text-surface-400">{{ importResult.skipped }}</p>
                                    <p class="text-surface-400">omitidas</p>
                                </div>
                            </div>
                            <div v-if="importResult.errors?.length" class="mt-4 text-left">
                                <p class="text-xs font-medium text-danger-400 mb-1">Errores:</p>
                                <ul class="text-xs text-surface-500 space-y-0.5 max-h-32 overflow-y-auto">
                                    <li v-for="(err, i) in importResult.errors" :key="i">{{ err }}</li>
                                </ul>
                            </div>
                        </div>
                        <div class="flex justify-end">
                            <button class="btn-primary" @click="closeImportModal">Cerrar</button>
                        </div>
                    </div>

                    <!-- Loading state -->
                    <div v-if="importPreview.isPending.value" class="text-center py-8 text-surface-500">
                        Analizando archivo...
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script setup>
import { computed, reactive, ref } from 'vue'
import { useModuleRegistry } from '@/modules/registry'
import {
    ArrowDownCircleIcon,
    ArrowUpCircleIcon,
    ArrowUpTrayIcon,
    ArrowsRightLeftIcon,
    CheckCircleIcon,
    PlusIcon,
    XMarkIcon,
} from '@heroicons/vue/24/outline'
import CustomSelect from '@/components/ui/CustomSelect.vue'
import AccountSelector from './components/AccountSelector.vue'
import TransactionFormModal from './components/TransactionFormModal.vue'
import {
    useFinanceTransactions,
    useFinanceCategories,
    useDeleteTransaction,
    useImportPreview,
    useImportTransactions,
} from '@/composables/useFinance'

const { actionsForSlot } = useModuleRegistry()

const toolbarActions = actionsForSlot('transactions-toolbar')
const rowInfoSlots = actionsForSlot('transaction-row-info')
const rowActionSlots = actionsForSlot('transaction-row-actions')

function onSlotAction(action) {
    if (action.emit) {
        window.dispatchEvent(new CustomEvent(action.emit))
    }
}

function formatDateInput(date) {
    return new Date(date).toISOString().slice(0, 10)
}

const range = reactive({
    startDate: formatDateInput(new Date(new Date().getFullYear(), new Date().getMonth(), 1)),
    endDate: formatDateInput(new Date()),
})

const typeFilter = ref('all')
const categoryFilter = ref('all')
const accountFilter = ref('')

const transactionFilters = computed(() => ({
    startDate: range.startDate,
    endDate: range.endDate,
    type: typeFilter.value === 'all' ? null : typeFilter.value,
    categoryId: categoryFilter.value === 'all' ? null : Number(categoryFilter.value),
    accountId: accountFilter.value ? Number(accountFilter.value) : null,
}))

const { data: transactionsData, isLoading: transactionsLoading } = useFinanceTransactions(transactionFilters)
const { data: categoriesData } = useFinanceCategories()

const transactions = computed(() => transactionsData.value?.data ?? [])
const categories = computed(() => categoriesData.value?.data ?? [])

const typeFilterOptions = [
    { value: 'all', label: 'Todos' },
    { value: 'income', label: 'Ingresos' },
    { value: 'expense', label: 'Gastos' },
    { value: 'transfer', label: 'Transferencias' },
]

const categoryFilterOptions = computed(() => [
    { value: 'all', label: 'Todas' },
    ...categories.value.map(cat => ({ value: String(cat.id), label: cat.name })),
])

const deleteTransactionMutation = useDeleteTransaction()

const showForm = ref(false)
const editingTx = ref(null)

function openForm(tx = null) {
    editingTx.value = tx || null
    showForm.value = true
}

async function deleteTx(tx) {
    if (!confirm('¿Eliminar este registro?')) return
    await deleteTransactionMutation.mutateAsync(tx.id)
}

function txIcon(type) {
    if (type === 'income') return ArrowUpCircleIcon
    if (type === 'transfer') return ArrowsRightLeftIcon
    return ArrowDownCircleIcon
}

function categoryStyle(category) {
    if (!category?.color) return { color: '#a5b4fc' }
    return { color: category.color }
}

function formatCurrency(value) {
    return new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR', minimumFractionDigits: 2 }).format(Number(value ?? 0))
}

function formatDate(date) {
    return new Date(date).toLocaleDateString('es-ES', { day: '2-digit', month: 'short' })
}

// --- Import ---
const importPreview = useImportPreview()
const importTransactions = useImportTransactions()
const showImportModal = ref(false)
const importStep = ref('upload')
const importAccountId = ref('')
const importPreviewData = ref(null)
const importTempPath = ref(null)
const importFile = ref(null)
const importResult = ref(null)

const formatLabels = {
    ing_es: 'ING España',
    ing_nl: 'ING Países Bajos',
    generic_es: 'Formato español genérico',
    generic: 'Formato genérico',
    unknown: 'Formato desconocido',
}
const importFormatLabel = computed(() => formatLabels[importPreviewData.value?.format] ?? importPreviewData.value?.format)

function closeImportModal() {
    showImportModal.value = false
    importStep.value = 'upload'
    importPreviewData.value = null
    importTempPath.value = null
    importFile.value = null
    importResult.value = null
    importAccountId.value = ''
}

function handleImportDrop(e) {
    const file = e.dataTransfer.files?.[0]
    if (file) previewFile(file)
}

function handleImportFile(e) {
    const file = e.target.files?.[0]
    if (file) previewFile(file)
    e.target.value = ''
}

async function previewFile(file) {
    importFile.value = file
    try {
        const result = await importPreview.mutateAsync(file)
        importPreviewData.value = result.data
        importTempPath.value = result.temp_path
        importStep.value = 'preview'
    } catch (error) {
        alert(error.response?.data?.message ?? 'Error al analizar el archivo.')
    }
}

async function confirmImport() {
    try {
        const result = await importTransactions.mutateAsync({
            tempPath: importTempPath.value,
            accountId: importAccountId.value || null,
        })
        importResult.value = result.data
        importStep.value = 'result'
    } catch (error) {
        alert(error.response?.data?.message ?? 'Error al importar.')
    }
}
</script>
