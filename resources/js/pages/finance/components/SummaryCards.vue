<template>
    <div class="grid gap-4 md:grid-cols-3 xl:grid-cols-4">
        <div class="liquid-glass liquid-glass-card p-6">
            <div class="flex items-center gap-3">
                <ArrowUpCircleIcon class="w-6 h-6 text-accent-400" />
                <p class="text-sm text-surface-400">Ingresos</p>
            </div>
            <p class="text-3xl font-semibold text-white mt-3">{{ formatCurrency(summary.income) }}</p>
            <p class="text-xs text-surface-500 mt-1">{{ summary.period?.start }} → {{ summary.period?.end }}</p>
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
                <p class="text-sm text-surface-400">Balance periodo</p>
            </div>
            <p
                class="text-3xl font-semibold mt-3"
                :class="summary.balance >= 0 ? 'text-accent-400' : 'text-danger-400'"
            >
                {{ formatCurrency(summary.balance) }}
            </p>
            <p class="text-xs text-surface-500 mt-1">
                {{ summary.balance >= 0 ? 'Superávit' : 'Déficit' }} del periodo
            </p>
        </div>
        <div v-if="summary.total_balance !== undefined" class="liquid-glass liquid-glass-card p-6">
            <div class="flex items-center gap-3">
                <BanknotesIcon class="w-6 h-6 text-primary-400" />
                <p class="text-sm text-surface-400">Patrimonio total</p>
            </div>
            <p
                class="text-3xl font-semibold mt-3"
                :class="summary.total_balance >= 0 ? 'text-white' : 'text-danger-400'"
            >
                {{ formatCurrency(summary.total_balance) }}
            </p>
            <p class="text-xs text-surface-500 mt-1">
                {{ (summary.accounts || []).length }} cuenta{{ (summary.accounts || []).length !== 1 ? 's' : '' }} activa{{ (summary.accounts || []).length !== 1 ? 's' : '' }}
            </p>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import {
    ArrowDownCircleIcon,
    ArrowUpCircleIcon,
    WalletIcon,
    BanknotesIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    summary: { type: Object, required: true },
})

const expenseRatioText = computed(() => {
    if (!props.summary.income) return 'Sin ingresos registrados'
    const ratio = (props.summary.expenses / props.summary.income) * 100
    return `${ratio.toFixed(1)}% de ingresos`
})

function formatCurrency(value) {
    return new Intl.NumberFormat('es-ES', {
        style: 'currency',
        currency: 'EUR',
        minimumFractionDigits: 2,
    }).format(Number(value ?? 0))
}
</script>
