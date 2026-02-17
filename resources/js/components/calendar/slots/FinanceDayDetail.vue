<template>
    <div class="space-y-1.5">
        <div
            v-for="event in events"
            :key="event.id"
            class="flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-white/[0.06] transition-colors"
        >
            <span
                class="text-xs font-bold px-1.5 py-0.5 rounded flex-shrink-0"
                :class="event.meta?.type === 'income'
                    ? 'bg-emerald-500/20 text-emerald-400'
                    : 'bg-red-500/20 text-red-400'"
            >
                {{ event.meta?.type === 'income' ? '+' : '-' }}
            </span>
            <span class="text-sm font-medium text-surface-200 flex-shrink-0">
                {{ formatAmount(event.meta?.amount) }}
            </span>
            <span class="text-sm text-surface-400 truncate">{{ event.title }}</span>
            <span
                v-if="event.meta?.category"
                class="ml-auto text-xs text-surface-500 flex-shrink-0"
            >
                {{ event.meta.category }}
            </span>
        </div>

        <div v-if="events.length > 0" class="pt-2 mt-2 border-t border-white/[0.06] flex justify-between text-xs">
            <span class="text-surface-500">Total día</span>
            <span class="font-medium" :class="dayTotal >= 0 ? 'text-emerald-400' : 'text-red-400'">
                {{ dayTotal >= 0 ? '+' : '' }}{{ formatAmount(dayTotal) }}
            </span>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    events: { type: Array, required: true },
    date: { type: String, required: true },
})

const dayTotal = computed(() =>
    props.events.reduce((sum, e) => {
        const amount = e.meta?.amount ?? 0
        return sum + (e.meta?.type === 'income' ? amount : -amount)
    }, 0)
)

function formatAmount(val) {
    return new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(val ?? 0)
}
</script>
