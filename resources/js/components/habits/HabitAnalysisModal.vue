<template>
    <Teleport to="body">
        <div class="modal-overlay">
            <div class="modal-backdrop" @click="handleClose" />
            <div class="modal-content liquid-glass liquid-glass-panel" style="--glass-radius: 20px; max-height: 85vh; overflow-y: auto; max-width: 600px;">

                <!-- Step 1: Goals input -->
                <template v-if="step === 'goals'">
                    <h2 class="section-title mb-2">Analizar hábitos con IA</h2>
                    <p class="text-sm text-surface-400 mb-4">
                        Cuéntanos tus propósitos y objetivos. La IA analizará tus hábitos actuales y te sugerirá un plan personalizado.
                    </p>
                    <textarea
                        v-model="goals"
                        rows="4"
                        class="form-input w-full text-sm"
                        placeholder="Ej: Quiero ser más productivo, hacer ejercicio 4 veces por semana, meditar a diario..."
                        :disabled="analyzing"
                    />
                    <div class="flex justify-end gap-3 mt-4">
                        <button @click="handleClose" class="btn-secondary text-sm">Cancelar</button>
                        <button
                            @click="runAnalysis"
                            class="btn-primary text-sm"
                            :disabled="!goals.trim() || analyzing"
                        >
                            <template v-if="analyzing">
                                <span class="inline-block w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin mr-2" />
                                Analizando...
                            </template>
                            <template v-else>Analizar</template>
                        </button>
                    </div>
                </template>

                <!-- Loading -->
                <div v-else-if="step === 'loading'" class="text-center py-12">
                    <div class="inline-block w-8 h-8 border-2 border-primary-500 border-t-transparent rounded-full animate-spin mb-4" />
                    <p class="text-sm text-surface-300">Analizando tus hábitos con IA...</p>
                    <p class="text-xs text-surface-500 mt-1">Esto puede tardar unos segundos</p>
                </div>

                <!-- Error -->
                <div v-else-if="step === 'error'" class="text-center py-12">
                    <p class="text-sm text-danger-400 mb-4">{{ errorMsg }}</p>
                    <div class="flex justify-center gap-3">
                        <button @click="step = 'goals'" class="btn-secondary text-sm">Volver</button>
                        <button @click="handleClose" class="btn-secondary text-sm">Cerrar</button>
                    </div>
                </div>

                <!-- Step 2: Results + suggested habits -->
                <template v-else-if="step === 'results'">
                    <div class="flex items-center justify-between mb-5">
                        <h2 class="section-title">Análisis de hábitos</h2>
                        <div
                            class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold"
                            :class="scoreClass"
                        >
                            {{ data.score }}
                        </div>
                    </div>

                    <!-- Summary -->
                    <p class="text-sm text-surface-200 mb-5 leading-relaxed">{{ data.summary }}</p>

                    <!-- Strengths -->
                    <div v-if="data.strengths?.length" class="mb-5">
                        <h3 class="text-xs font-semibold text-accent-400 uppercase tracking-wider mb-2">Puntos fuertes</h3>
                        <ul class="space-y-1.5">
                            <li v-for="s in data.strengths" :key="s" class="flex items-start gap-2 text-sm text-surface-200">
                                <span class="text-accent-400 shrink-0 mt-0.5">+</span>
                                {{ s }}
                            </li>
                        </ul>
                    </div>

                    <!-- Risks -->
                    <div v-if="data.risks?.length" class="mb-5">
                        <h3 class="text-xs font-semibold text-danger-400 uppercase tracking-wider mb-2">En riesgo</h3>
                        <div class="space-y-2">
                            <div
                                v-for="r in data.risks"
                                :key="r.habit"
                                class="p-3 rounded-lg bg-danger-500/5 border border-danger-500/10"
                            >
                                <div class="text-sm font-medium text-surface-200">{{ r.habit }}</div>
                                <div class="text-xs text-surface-400 mt-0.5">{{ r.issue }}</div>
                                <div class="text-xs text-primary-400 mt-1">{{ r.suggestion }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Weak days -->
                    <div v-if="data.weak_days?.length" class="mb-5">
                        <h3 class="text-xs font-semibold text-yellow-400 uppercase tracking-wider mb-2">Días a mejorar</h3>
                        <div class="space-y-1.5">
                            <div v-for="d in data.weak_days" :key="d.day" class="flex items-center gap-3 text-sm">
                                <span class="text-surface-300 w-20 shrink-0">{{ d.day }}</span>
                                <div class="flex-1">
                                    <div class="progress-track">
                                        <div
                                            class="h-full rounded-full bg-yellow-500/60"
                                            :style="{ width: d.rate + '%' }"
                                        />
                                    </div>
                                </div>
                                <span class="text-xs text-surface-400 w-10 text-right">{{ d.rate }}%</span>
                            </div>
                            <div v-for="d in data.weak_days" :key="'tip-'+d.day" class="text-xs text-surface-400 pl-1 mt-1">
                                {{ d.day }}: {{ d.tip }}
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

                    <!-- Suggested habits -->
                    <div v-if="data.suggested_habits?.length" class="mb-5">
                        <h3 class="text-xs font-semibold text-primary-400 uppercase tracking-wider mb-3">Plan de hábitos sugerido</h3>
                        <p class="text-xs text-surface-400 mb-3">Selecciona los hábitos que quieras añadir:</p>
                        <div class="space-y-2">
                            <label
                                v-for="(habit, i) in data.suggested_habits"
                                :key="i"
                                class="flex items-start gap-3 p-3 rounded-lg border transition-colors cursor-pointer"
                                :class="selectedHabits[i]
                                    ? 'border-primary-500/30 bg-primary-500/5'
                                    : 'border-white/[0.06] hover:border-white/[0.12]'"
                            >
                                <input
                                    type="checkbox"
                                    v-model="selectedHabits[i]"
                                    class="mt-1 shrink-0 accent-primary-500"
                                />
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="w-3 h-3 rounded-full shrink-0"
                                            :style="{ backgroundColor: habit.color || '#6366f1' }"
                                        />
                                        <span class="text-sm font-medium text-surface-200">{{ habit.name }}</span>
                                        <span v-if="habit.replaces" class="text-xs px-1.5 py-0.5 rounded bg-yellow-500/10 text-yellow-400">
                                            Reemplaza: {{ habit.replaces }}
                                        </span>
                                        <span v-else class="text-xs px-1.5 py-0.5 rounded bg-accent-500/10 text-accent-400">
                                            Nuevo
                                        </span>
                                    </div>
                                    <p class="text-xs text-surface-400 mt-1">{{ habit.description }}</p>
                                    <div class="flex items-center gap-3 mt-1.5 text-xs text-surface-500">
                                        <span>{{ frequencyLabel(habit) }}</span>
                                        <span>{{ routineLabel(habit.routine) }}</span>
                                        <span v-if="habit.type === 'numeric'">{{ habit.target_value }} {{ habit.unit }}</span>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-between pt-2">
                        <button @click="step = 'goals'" class="btn-secondary text-sm">Volver</button>
                        <div class="flex gap-3">
                            <button @click="handleClose" class="btn-secondary text-sm">Cerrar</button>
                            <button
                                v-if="data.suggested_habits?.length && selectedCount > 0"
                                @click="applySelected"
                                class="btn-primary text-sm"
                                :disabled="applying"
                            >
                                <template v-if="applying">
                                    <span class="inline-block w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin mr-2" />
                                    Aplicando...
                                </template>
                                <template v-else>
                                    Aplicar {{ selectedCount }} hábito{{ selectedCount !== 1 ? 's' : '' }}
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
                        <p class="text-sm text-surface-400 mb-6">Tus nuevos hábitos ya aparecen en tu lista.</p>
                        <button @click="handleClose" class="btn-primary text-sm">Cerrar</button>
                    </div>
                </template>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { useAnalyzeHabits, useApplySuggestions } from '@/composables/useHabits'

const emit = defineEmits(['close'])

const step = ref('goals')
const goals = ref('')
const data = ref(null)
const errorMsg = ref(null)
const selectedHabits = reactive({})
const appliedMsg = ref('')

const { mutateAsync: analyze, isPending: analyzing } = useAnalyzeHabits()
const { mutateAsync: applySuggestions, isPending: applying } = useApplySuggestions()

const scoreClass = computed(() => {
    const s = data.value?.score ?? 0
    if (s >= 80) return 'bg-accent-500/20 text-accent-400'
    if (s >= 50) return 'bg-yellow-500/20 text-yellow-400'
    return 'bg-danger-500/20 text-danger-400'
})

const selectedCount = computed(() => {
    return Object.values(selectedHabits).filter(Boolean).length
})

function frequencyLabel(habit) {
    const map = { daily: 'Diario', weekly: 'Semanal', custom: 'Personalizado' }
    return map[habit.frequency] || habit.frequency
}

function routineLabel(routine) {
    const map = { morning: 'Mañana', afternoon: 'Tarde', evening: 'Noche', anytime: 'Cualquier momento' }
    return map[routine] || routine
}

async function runAnalysis() {
    step.value = 'loading'
    errorMsg.value = null
    try {
        const result = await analyze(goals.value)
        data.value = result
        // Pre-select all suggested habits
        if (result.suggested_habits?.length) {
            result.suggested_habits.forEach((_, i) => {
                selectedHabits[i] = true
            })
        }
        step.value = 'results'
    } catch (e) {
        errorMsg.value = e.response?.data?.error || e.response?.data?.message || 'Error al analizar los hábitos.'
        step.value = 'error'
    }
}

async function applySelected() {
    const habitsToApply = data.value.suggested_habits
        .filter((_, i) => selectedHabits[i])
        .map(h => ({
            name: h.name,
            type: h.type,
            unit: h.unit || null,
            target_value: h.target_value || null,
            frequency: h.frequency,
            target_days: h.target_days || null,
            routine: h.routine || 'anytime',
            color: h.color || null,
            replaces: h.replaces || null,
        }))

    try {
        const result = await applySuggestions(habitsToApply)
        appliedMsg.value = result.message
        step.value = 'applied'
    } catch (e) {
        errorMsg.value = e.response?.data?.message || 'Error al aplicar los hábitos.'
        step.value = 'error'
    }
}

function handleClose() {
    emit('close')
}
</script>
