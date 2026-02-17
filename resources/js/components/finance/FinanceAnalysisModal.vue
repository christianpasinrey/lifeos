<template>
    <Teleport to="body">
        <div class="modal-overlay">
            <div class="modal-backdrop" @click="handleClose" />
            <div class="modal-content liquid-glass liquid-glass-panel" style="--glass-radius: 20px; max-height: 85vh; overflow-y: auto; max-width: 640px;">

                <!-- Step 1: Date selection -->
                <template v-if="step === 'dates'">
                    <h2 class="section-title mb-2">Analizar finanzas con IA</h2>
                    <p class="text-sm text-surface-400 mb-4">
                        Selecciona el periodo a analizar. Se enviarán todos los movimientos del rango al agente IA.
                    </p>
                    <div class="flex gap-4 mb-4">
                        <div class="flex-1">
                            <label class="form-label">Desde</label>
                            <input v-model="startDate" type="date" class="form-input w-full" />
                        </div>
                        <div class="flex-1">
                            <label class="form-label">Hasta</label>
                            <input v-model="endDate" type="date" class="form-input w-full" />
                        </div>
                    </div>
                    <div class="flex justify-end gap-3">
                        <button @click="handleClose" class="btn-secondary text-sm">Cancelar</button>
                        <button
                            @click="runAnalysis"
                            class="btn-primary text-sm"
                            :disabled="!startDate || !endDate"
                        >
                            Analizar
                        </button>
                    </div>
                </template>

                <!-- Loading -->
                <div v-else-if="step === 'loading'" class="text-center py-12">
                    <div class="inline-block w-8 h-8 border-2 border-primary-500 border-t-transparent rounded-full animate-spin mb-4" />
                    <p class="text-sm text-surface-300">Analizando tus finanzas con IA...</p>
                    <p class="text-xs text-surface-500 mt-1">Esto puede tardar unos segundos</p>
                </div>

                <!-- Error -->
                <div v-else-if="step === 'error'" class="text-center py-12">
                    <p class="text-sm text-danger-400 mb-4">{{ errorMsg }}</p>
                    <div class="flex justify-center gap-3">
                        <button @click="step = 'dates'" class="btn-secondary text-sm">Volver</button>
                        <button @click="handleClose" class="btn-secondary text-sm">Cerrar</button>
                    </div>
                </div>

                <!-- Step 2: Results -->
                <template v-else-if="step === 'results'">
                    <div class="flex items-center justify-between mb-5">
                        <h2 class="section-title">Análisis financiero</h2>
                        <div
                            class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold"
                            :class="scoreClass"
                        >
                            {{ data.score }}
                        </div>
                    </div>

                    <!-- Summary -->
                    <p class="text-sm text-surface-200 mb-5 leading-relaxed">{{ data.summary }}</p>

                    <!-- Highlights -->
                    <div v-if="data.highlights?.length" class="mb-5">
                        <h3 class="text-xs font-semibold text-primary-400 uppercase tracking-wider mb-2">Puntos clave</h3>
                        <div class="space-y-2">
                            <div
                                v-for="(h, i) in data.highlights"
                                :key="i"
                                class="flex items-start gap-2 text-sm p-2.5 rounded-lg"
                                :class="highlightClass(h.type)"
                            >
                                <span class="shrink-0 mt-0.5">{{ highlightIcon(h.type) }}</span>
                                <span class="text-surface-200">{{ h.text }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Category analysis -->
                    <div v-if="data.category_analysis?.length" class="mb-5">
                        <h3 class="text-xs font-semibold text-primary-400 uppercase tracking-wider mb-2">Por categoría</h3>
                        <div class="space-y-2">
                            <div
                                v-for="(cat, i) in data.category_analysis"
                                :key="i"
                                class="p-3 rounded-lg border border-white/[0.06] bg-white/[0.02]"
                            >
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-sm font-medium text-surface-200">{{ cat.category }}</span>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs" :class="trendColor(cat.trend)">{{ trendIcon(cat.trend) }}</span>
                                        <span class="text-sm text-white font-medium">{{ formatCurrency(cat.amount) }}</span>
                                    </div>
                                </div>
                                <div class="progress-track mb-1.5">
                                    <div
                                        class="h-full rounded-full bg-primary-500/60"
                                        :style="{ width: Math.min(cat.percentage, 100) + '%' }"
                                    />
                                </div>
                                <p class="text-xs text-surface-400">{{ cat.advice }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Recommendations -->
                    <div v-if="data.recommendations?.length" class="mb-5">
                        <h3 class="text-xs font-semibold text-primary-400 uppercase tracking-wider mb-2">Recomendaciones</h3>
                        <ol class="space-y-2">
                            <li
                                v-for="(rec, i) in data.recommendations"
                                :key="i"
                                class="flex items-start gap-2.5 text-sm text-surface-200"
                            >
                                <span class="w-5 h-5 rounded-full bg-primary-500/15 text-primary-400 text-xs font-bold flex items-center justify-center shrink-0 mt-0.5">
                                    {{ i + 1 }}
                                </span>
                                {{ rec }}
                            </li>
                        </ol>
                    </div>

                    <!-- Suggested actions -->
                    <div v-if="data.suggested_actions?.length" class="mb-5">
                        <h3 class="text-xs font-semibold text-primary-400 uppercase tracking-wider mb-3">Acciones sugeridas</h3>
                        <p class="text-xs text-surface-400 mb-3">Selecciona las que quieras aplicar:</p>
                        <div class="space-y-2">
                            <label
                                v-for="(action, i) in data.suggested_actions"
                                :key="i"
                                class="flex items-start gap-3 p-3 rounded-lg border transition-colors cursor-pointer"
                                :class="selectedActions[i]
                                    ? 'border-primary-500/30 bg-primary-500/5'
                                    : 'border-white/[0.06] hover:border-white/[0.12]'"
                            >
                                <input
                                    type="checkbox"
                                    v-model="selectedActions[i]"
                                    class="mt-1 shrink-0 accent-primary-500"
                                />
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-medium text-surface-200">{{ action.title }}</span>
                                        <span
                                            class="text-xs px-1.5 py-0.5 rounded"
                                            :class="action.type === 'habit'
                                                ? 'bg-accent-500/10 text-accent-400'
                                                : 'bg-primary-500/10 text-primary-400'"
                                        >
                                            {{ action.type === 'habit' ? 'Hábito' : 'Tarea' }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-surface-400 mt-1">{{ action.description }}</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-between pt-2">
                        <button @click="step = 'dates'" class="btn-secondary text-sm">Volver</button>
                        <div class="flex gap-3">
                            <button @click="handleClose" class="btn-secondary text-sm">Cerrar</button>
                            <button
                                v-if="data.suggested_actions?.length && selectedCount > 0"
                                @click="applySelected"
                                class="btn-primary text-sm"
                                :disabled="applying"
                            >
                                <template v-if="applying">
                                    <span class="inline-block w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin mr-2" />
                                    Aplicando...
                                </template>
                                <template v-else>
                                    Aplicar {{ selectedCount }} acción{{ selectedCount !== 1 ? 'es' : '' }}
                                </template>
                            </button>
                        </div>
                    </div>
                </template>

                <!-- Applied success -->
                <template v-else-if="step === 'applied'">
                    <div class="text-center py-10">
                        <div class="text-4xl mb-4">&#10003;</div>
                        <h3 class="text-lg font-medium text-surface-200 mb-2">{{ appliedMsg }}</h3>
                        <p class="text-sm text-surface-400 mb-6">Las acciones se han creado correctamente.</p>
                        <button @click="handleClose" class="btn-primary text-sm">Cerrar</button>
                    </div>
                </template>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { useAnalyzeFinances, useApplyFinanceActions } from '@/composables/useFinance'

const props = defineProps({
    initialStartDate: { type: String, default: '' },
    initialEndDate: { type: String, default: '' },
})

const emit = defineEmits(['close'])

const step = ref('dates')
const startDate = ref(props.initialStartDate)
const endDate = ref(props.initialEndDate)
const data = ref(null)
const errorMsg = ref(null)
const selectedActions = reactive({})
const appliedMsg = ref('')

const { mutateAsync: analyze, isPending: analyzing } = useAnalyzeFinances()
const { mutateAsync: applyActions, isPending: applying } = useApplyFinanceActions()

const scoreClass = computed(() => {
    const s = data.value?.score ?? 0
    if (s >= 80) return 'bg-accent-500/20 text-accent-400'
    if (s >= 50) return 'bg-yellow-500/20 text-yellow-400'
    return 'bg-danger-500/20 text-danger-400'
})

const selectedCount = computed(() => {
    return Object.values(selectedActions).filter(Boolean).length
})

function highlightClass(type) {
    if (type === 'positive') return 'bg-accent-500/5 border border-accent-500/10'
    if (type === 'warning') return 'bg-yellow-500/5 border border-yellow-500/10'
    return 'bg-danger-500/5 border border-danger-500/10'
}

function highlightIcon(type) {
    if (type === 'positive') return '+'
    if (type === 'warning') return '!'
    return '-'
}

function trendIcon(trend) {
    if (trend === 'up') return '\u2191'
    if (trend === 'down') return '\u2193'
    return '\u2192'
}

function trendColor(trend) {
    if (trend === 'up') return 'text-danger-400'
    if (trend === 'down') return 'text-accent-400'
    return 'text-surface-400'
}

function formatCurrency(value) {
    return new Intl.NumberFormat('es-ES', {
        style: 'currency',
        currency: 'EUR',
        minimumFractionDigits: 2,
    }).format(Number(value ?? 0))
}

async function runAnalysis() {
    step.value = 'loading'
    errorMsg.value = null
    try {
        const result = await analyze({ startDate: startDate.value, endDate: endDate.value })
        data.value = result
        if (result.suggested_actions?.length) {
            result.suggested_actions.forEach((_, i) => {
                selectedActions[i] = true
            })
        }
        step.value = 'results'
    } catch (e) {
        errorMsg.value = e.response?.data?.error || e.response?.data?.message || 'Error al analizar las finanzas.'
        step.value = 'error'
    }
}

async function applySelected() {
    const actionsToApply = data.value.suggested_actions
        .filter((_, i) => selectedActions[i])
        .map(a => ({
            type: a.type,
            title: a.title,
            description: a.description || null,
        }))

    try {
        const result = await applyActions(actionsToApply)
        appliedMsg.value = result.message
        step.value = 'applied'
    } catch (e) {
        errorMsg.value = e.response?.data?.message || 'Error al aplicar las acciones.'
        step.value = 'error'
    }
}

function handleClose() {
    emit('close')
}
</script>
