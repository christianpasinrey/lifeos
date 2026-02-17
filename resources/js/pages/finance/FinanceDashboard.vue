<template>
    <div class="space-y-6">
        <div class="flex flex-wrap items-center gap-3">
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
            <div class="ml-auto flex gap-3">
                <template v-for="action in toolbarActions" :key="action.id || action.label">
                    <component v-if="action.component" :is="action.component" :start-date="range.startDate" :end-date="range.endDate" />
                    <button
                        v-else
                        class="px-4 py-2 rounded-xl text-sm font-medium bg-primary-500/20 text-primary-100 hover:bg-primary-500/30 transition"
                        @click="onSlotAction(action)"
                    >
                        <component v-if="action.icon" :is="action.icon" class="w-4 h-4 inline mr-1" />
                        {{ action.label }}
                    </button>
                </template>
            </div>
        </div>

        <SummaryCards :summary="summary" />

        <!-- Account balances -->
        <div v-if="accounts.length > 0" class="liquid-glass liquid-glass-card p-6">
            <h3 class="text-lg font-semibold text-white mb-4">Cuentas</h3>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="account in accounts"
                    :key="account.id"
                    class="flex items-center gap-3 rounded-xl border border-white/5 bg-white/[0.02] p-4"
                >
                    <div class="w-2 h-10 rounded-full" :style="{ background: account.color }" />
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-white truncate">{{ account.name }}</p>
                        <p class="text-xs text-surface-500">{{ accountTypeLabel(account.type) }}</p>
                    </div>
                    <p
                        class="text-base font-semibold"
                        :class="account.current_balance >= 0 ? 'text-white' : 'text-danger-400'"
                    >
                        {{ formatCurrency(account.current_balance) }}
                    </p>
                </div>
            </div>
        </div>

        <!-- By category -->
        <div v-if="summary.by_category?.length" class="liquid-glass liquid-glass-card p-6">
            <h3 class="text-lg font-semibold text-white mb-4">Por categoría</h3>
            <div class="space-y-3">
                <div
                    v-for="cat in summary.by_category"
                    :key="cat.category"
                    class="flex items-center gap-3"
                >
                    <span class="w-2 h-6 rounded-full shrink-0" :style="{ background: cat.color }" />
                    <span class="flex-1 text-sm text-surface-200">{{ cat.category }}</span>
                    <span class="text-sm text-surface-400">{{ cat.count }} mov.</span>
                    <span class="text-sm font-medium text-white">{{ formatCurrency(cat.total) }}</span>
                </div>
            </div>
        </div>

        <!-- Recent transactions -->
        <div class="liquid-glass liquid-glass-card p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-white">Últimos movimientos</h3>
                <router-link to="/finance/transactions" class="text-sm text-primary-300 hover:text-primary-200">
                    Ver todos
                </router-link>
            </div>
            <div v-if="recentTransactions.length === 0" class="text-center py-6 text-surface-500">
                No hay transacciones en este periodo
            </div>
            <ul v-else class="space-y-3">
                <li
                    v-for="tx in recentTransactions"
                    :key="tx.id"
                    class="flex items-center gap-3 rounded-xl border border-white/5 bg-white/[0.02] p-3"
                >
                    <div
                        class="rounded-xl p-2"
                        :class="tx.type === 'income' ? 'bg-accent-500/20 text-accent-400'
                            : tx.type === 'transfer' ? 'bg-primary-500/20 text-primary-400'
                            : 'bg-danger-500/20 text-danger-400'"
                    >
                        <component :is="txIcon(tx.type)" class="w-5 h-5" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-white truncate">{{ tx.description }}</p>
                        <p class="text-xs text-surface-500">{{ formatDate(tx.date) }}</p>
                    </div>
                    <p
                        class="text-sm font-semibold"
                        :class="tx.type === 'income' ? 'text-accent-400'
                            : tx.type === 'transfer' ? 'text-primary-300'
                            : 'text-danger-400'"
                    >
                        {{ tx.type === 'income' ? '+' : tx.type === 'transfer' ? '' : '-' }}{{ formatCurrency(tx.amount) }}
                    </p>
                </li>
            </ul>
        </div>
    </div>
</template>

<script setup>
import { computed, reactive, ref } from 'vue'
import { useModuleRegistry } from '@/modules/registry'
import { ArrowUpCircleIcon, ArrowDownCircleIcon, ArrowsRightLeftIcon } from '@heroicons/vue/24/outline'
import SummaryCards from './components/SummaryCards.vue'
import { useFinanceSummary, useFinanceTransactions } from '@/composables/useFinance'
import { useFinanceAccounts } from '@/composables/useFinanceAccounts'

const { actionsForSlot } = useModuleRegistry()
const toolbarActions = actionsForSlot('finance-dashboard-toolbar')

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

const summaryFilters = computed(() => ({
    startDate: range.startDate,
    endDate: range.endDate,
}))

const { data: summaryData } = useFinanceSummary(summaryFilters)
const { data: transactionsData } = useFinanceTransactions(summaryFilters)
const { data: accountsData } = useFinanceAccounts()

const summary = computed(() => summaryData.value?.data ?? {
    income: 0,
    expenses: 0,
    balance: 0,
    total_balance: 0,
    accounts: [],
    by_category: [],
    period: { start: range.startDate, end: range.endDate },
})

const accounts = computed(() => accountsData.value?.data ?? [])

const recentTransactions = computed(() => {
    const all = transactionsData.value?.data ?? []
    return all.slice(0, 10)
})

function txIcon(type) {
    if (type === 'income') return ArrowUpCircleIcon
    if (type === 'transfer') return ArrowsRightLeftIcon
    return ArrowDownCircleIcon
}

const accountTypeLabels = {
    bank: 'Banco',
    cash: 'Efectivo',
    credit_card: 'Tarjeta de crédito',
    savings: 'Ahorro',
    investment: 'Inversión',
    other: 'Otro',
}

function accountTypeLabel(type) {
    return accountTypeLabels[type] || type
}

function formatCurrency(value) {
    return new Intl.NumberFormat('es-ES', {
        style: 'currency',
        currency: 'EUR',
        minimumFractionDigits: 2,
    }).format(Number(value ?? 0))
}

function formatDate(date) {
    return new Date(date).toLocaleDateString('es-ES', { day: '2-digit', month: 'short' })
}

</script>
