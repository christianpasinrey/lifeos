<template>
    <button
        class="ai-chip"
        :disabled="autoCategorize.isPending.value"
        @click="runAutoCategorize"
    >
        <SparklesIcon class="w-4 h-4" />
        {{ autoCategorize.isPending.value ? 'Analizando...' : 'Auto-clasificar' }}
    </button>

    <Teleport to="body">
        <div v-if="showModal" class="modal-overlay" @mousedown.self="closeModal">
            <div class="modal-backdrop" />
            <div class="modal-content liquid-glass liquid-glass-panel max-w-2xl">
                <div class="flex items-center justify-between mb-5">
                    <div>
                        <h2 class="section-title">Auto-clasificar transacciones</h2>
                        <p class="text-sm text-surface-400 mt-1">Revisa las propuestas y confirma los cambios</p>
                    </div>
                    <button class="btn-close" @click="closeModal">
                        <XMarkIcon class="w-5 h-5" />
                    </button>
                </div>

                <div v-if="proposals.length === 0" class="text-center py-8 text-surface-500">
                    No hay propuestas disponibles.
                </div>

                <ul v-else class="space-y-3 max-h-[60vh] overflow-y-auto pr-1">
                    <li
                        v-for="p in proposals"
                        :key="p.transaction_id"
                        class="rounded-2xl border border-white/5 bg-white/[0.02] p-4"
                    >
                        <div class="flex items-start gap-3">
                            <input v-model="p.selected" type="checkbox" class="mt-1 accent-primary-500" />
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="text-sm font-medium text-white truncate">{{ p.transaction_description }}</p>
                                    <p
                                        class="text-sm font-semibold whitespace-nowrap"
                                        :class="p.transaction_type === 'income' ? 'text-accent-400' : 'text-danger-400'"
                                    >
                                        {{ p.transaction_type === 'income' ? '+' : '-' }}{{ formatCurrency(p.transaction_amount) }}
                                    </p>
                                </div>
                                <p class="text-xs text-surface-500 mt-0.5">{{ p.transaction_date }}</p>
                                <div class="flex items-center gap-2 mt-2">
                                    <ArrowRightIcon class="w-3.5 h-3.5 text-primary-400 shrink-0" />
                                    <span
                                        v-if="p.category_id"
                                        class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-lg text-xs font-medium"
                                        :style="{ background: (p.category_color || '#6366f1') + '22', color: p.category_color || '#6366f1' }"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full" :style="{ background: p.category_color || '#6366f1' }" />
                                        {{ p.category_name }}
                                    </span>
                                    <span
                                        v-else-if="p.new_category"
                                        class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-lg text-xs font-medium border border-dashed border-primary-400/40"
                                        :style="{ background: (p.new_category.color || '#6366f1') + '22', color: p.new_category.color || '#6366f1' }"
                                    >
                                        <PlusIcon class="w-3 h-3" />
                                        {{ p.new_category.name }}
                                        <span class="text-surface-500 font-normal">(nueva)</span>
                                    </span>
                                </div>
                                <p class="text-xs text-surface-500 mt-1 italic">{{ p.reason }}</p>
                            </div>
                        </div>
                    </li>
                </ul>

                <div class="flex items-center justify-between mt-5 pt-4 border-t border-white/5">
                    <div class="flex items-center gap-3">
                        <button class="text-xs text-primary-300 hover:text-primary-200" @click="toggleAll(true)">Seleccionar todo</button>
                        <button class="text-xs text-surface-400 hover:text-surface-300" @click="toggleAll(false)">Deseleccionar</button>
                    </div>
                    <div class="flex gap-3">
                        <button class="btn-secondary" @click="closeModal">Cancelar</button>
                        <button
                            class="btn-primary"
                            :disabled="applyCategories.isPending.value || selectedCount === 0"
                            @click="confirmCategorize"
                        >
                            Aplicar {{ selectedCount }} cambios
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { computed, ref } from 'vue'
import { SparklesIcon, ArrowRightIcon, PlusIcon, XMarkIcon } from '@heroicons/vue/24/outline'
import { useAutoCategorize, useApplyCategories } from '@/composables/useFinance'

const props = defineProps({
    transactions: { type: Array, default: () => [] },
})

const autoCategorize = useAutoCategorize()
const applyCategories = useApplyCategories()

const showModal = ref(false)
const proposals = ref([])

const selectedCount = computed(() => proposals.value.filter(p => p.selected).length)

function formatCurrency(value) {
    return new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR', minimumFractionDigits: 2 }).format(Number(value ?? 0))
}

async function runAutoCategorize() {
    try {
        const uncategorizedIds = props.transactions.filter(tx => !tx.category_id).map(tx => tx.id)
        const result = await autoCategorize.mutateAsync(uncategorizedIds.length > 0 ? uncategorizedIds : null)
        const data = result.data ?? []
        if (data.length === 0) {
            alert('No hay transacciones sin categorizar o el modelo no generó propuestas.')
            return
        }
        proposals.value = data.map(p => ({ ...p, selected: true }))
        showModal.value = true
    } catch (error) {
        alert(error.response?.data?.error ?? error.response?.data?.message ?? 'Error al auto-clasificar.')
    }
}

function closeModal() {
    showModal.value = false
    proposals.value = []
}

function toggleAll(value) {
    proposals.value.forEach(p => { p.selected = value })
}

async function confirmCategorize() {
    const selected = proposals.value.filter(p => p.selected)
    const assignments = selected.map(p => ({
        transaction_id: p.transaction_id,
        category_id: p.category_id || null,
        new_category: p.new_category || null,
    }))
    try {
        await applyCategories.mutateAsync(assignments)
        closeModal()
    } catch (error) {
        alert(error.response?.data?.message ?? 'Error al aplicar categorías.')
    }
}
</script>
