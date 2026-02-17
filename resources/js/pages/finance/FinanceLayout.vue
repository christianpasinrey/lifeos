<template>
    <div class="space-y-6">
        <header>
            <p class="text-sm uppercase tracking-[0.2em] text-primary-300/70">Panel financiero</p>
            <h1 class="text-3xl font-semibold text-white mt-1">Finanzas</h1>
        </header>

        <!-- Sub-navigation -->
        <nav class="flex flex-wrap gap-1 rounded-xl border border-white/5 bg-white/[0.02] p-1">
            <router-link
                v-for="tab in tabs"
                :key="tab.to"
                :to="tab.to"
                class="px-4 py-2 rounded-lg text-sm font-medium transition"
                :class="isActive(tab.to)
                    ? 'bg-primary-500/20 text-primary-200'
                    : 'text-surface-400 hover:text-surface-200 hover:bg-white/5'"
            >
                {{ tab.label }}
            </router-link>
        </nav>

        <router-view />
    </div>
</template>

<script setup>
import { useRoute } from 'vue-router'

const route = useRoute()

const tabs = [
    { to: '/finance', label: 'Resumen' },
    { to: '/finance/transactions', label: 'Transacciones' },
    { to: '/finance/accounts', label: 'Cuentas' },
    { to: '/finance/invoices', label: 'Facturas' },
    { to: '/finance/recurring', label: 'Recurrentes' },
    { to: '/finance/budgets', label: 'Presupuestos' },
    { to: '/finance/categories', label: 'Categorías' },
    { to: '/finance/taxes', label: 'Impuestos' },
]

function isActive(path) {
    if (path === '/finance') {
        return route.path === '/finance'
    }
    return route.path.startsWith(path)
}
</script>
