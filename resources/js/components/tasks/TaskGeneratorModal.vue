<template>
    <Teleport to="body">
        <div class="modal-overlay" @mousedown.self="$emit('close')">
            <div class="modal-backdrop" />
            <div class="modal-content liquid-glass liquid-glass-card" style="max-width: 36rem;">
                <!-- Header -->
                <div class="flex items-center gap-2 mb-4">
                    <SparklesIcon class="w-5 h-5 text-primary-400" />
                    <h3 class="text-lg font-semibold text-surface-100">Generar tareas con IA</h3>
                </div>

                <!-- Step 1: Prompt -->
                <div v-if="step === 'input'">
                    <label class="form-label">Describe tu proyecto o lo que necesitas hacer</label>
                    <textarea
                        v-model="prompt"
                        class="form-input"
                        rows="4"
                        placeholder="Ej: Crear una landing page con hero, sección de features, pricing y formulario de contacto..."
                        ref="promptInput"
                    />

                    <div v-if="error" class="form-error mt-2">{{ error }}</div>

                    <div class="flex justify-end gap-3 mt-4">
                        <button type="button" class="btn-secondary" @click="$emit('close')">
                            Cancelar
                        </button>
                        <button
                            class="btn-primary flex items-center gap-2"
                            :disabled="!prompt.trim() || suggesting"
                            @click="handleSuggest"
                        >
                            <template v-if="suggesting">
                                <div class="animate-spin w-4 h-4 border-2 border-white/30 border-t-white rounded-full" />
                                Generando...
                            </template>
                            <template v-else>
                                <SparklesIcon class="w-4 h-4" />
                                Generar tareas
                            </template>
                        </button>
                    </div>
                </div>

                <!-- Step 2: Review -->
                <div v-else>
                    <!-- Select all toggle -->
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-sm text-surface-400">
                            {{ selectedCount }} de {{ suggestions.length }} seleccionadas
                        </span>
                        <button
                            type="button"
                            class="text-xs text-primary-400 hover:text-primary-300 transition-colors"
                            @click="toggleAll"
                        >
                            {{ allSelected ? 'Deseleccionar todo' : 'Seleccionar todo' }}
                        </button>
                    </div>

                    <!-- Task list -->
                    <ul class="space-y-2 max-h-64 overflow-y-auto pr-1 glass-scroll">
                        <li
                            v-for="(task, i) in suggestions"
                            :key="i"
                            class="flex items-start gap-3 p-3 rounded-lg border transition-colors cursor-pointer"
                            :class="task.selected
                                ? 'bg-primary-500/5 border-primary-500/20'
                                : 'bg-white/[0.02] border-white/[0.06] hover:border-white/10'"
                            @click="task.selected = !task.selected"
                        >
                            <input
                                type="checkbox"
                                :checked="task.selected"
                                class="mt-0.5 accent-primary-500"
                                @click.stop
                                @change="task.selected = !task.selected"
                            />
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-surface-100">{{ task.title }}</p>
                                <p v-if="task.description" class="text-xs text-surface-500 mt-0.5 line-clamp-2">
                                    {{ task.description }}
                                </p>
                            </div>
                            <span
                                class="task-priority shrink-0"
                                :class="`task-priority-${task.priority}`"
                            >
                                {{ priorityLabels[task.priority] }}
                            </span>
                        </li>
                    </ul>

                    <!-- Column selector -->
                    <div class="mt-4">
                        <label class="form-label">Columna destino</label>
                        <CustomSelect
                            v-model="targetColumnId"
                            :options="columnOptions"
                            placeholder="Seleccionar columna..."
                        />
                    </div>

                    <div v-if="error" class="form-error mt-2">{{ error }}</div>

                    <!-- Actions -->
                    <div class="flex justify-between mt-4">
                        <button
                            type="button"
                            class="text-sm text-surface-400 hover:text-surface-200 transition-colors"
                            @click="step = 'input'"
                        >
                            &larr; Volver
                        </button>
                        <div class="flex gap-3">
                            <button type="button" class="btn-secondary" @click="$emit('close')">
                                Cancelar
                            </button>
                            <button
                                class="btn-primary flex items-center gap-2"
                                :disabled="!selectedCount || !targetColumnId || confirming"
                                @click="handleConfirm"
                            >
                                <template v-if="confirming">
                                    <div class="animate-spin w-4 h-4 border-2 border-white/30 border-t-white rounded-full" />
                                    Creando...
                                </template>
                                <template v-else>
                                    Crear {{ selectedCount }} tarea{{ selectedCount !== 1 ? 's' : '' }}
                                </template>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { SparklesIcon } from '@heroicons/vue/24/outline'
import { useSuggestTasks, useConfirmGeneratedTasks } from '@/composables/useTasks'
import CustomSelect from '@/components/ui/CustomSelect.vue'

const props = defineProps({
    boardId: { type: Number, required: true },
    columns: { type: Array, required: true },
})

const emit = defineEmits(['close', 'saved'])

const priorityLabels = { low: 'Baja', medium: 'Media', high: 'Alta' }

const step = ref('input')
const prompt = ref('')
const suggestions = ref([])
const targetColumnId = ref(props.columns[0]?.id ?? null)
const error = ref('')
const suggesting = ref(false)
const confirming = ref(false)
const promptInput = ref(null)

const suggestMutation = useSuggestTasks()
const confirmMutation = useConfirmGeneratedTasks()

const columnOptions = computed(() =>
    props.columns.map(c => ({ value: c.id, label: c.name }))
)

const selectedCount = computed(() =>
    suggestions.value.filter(t => t.selected).length
)

const allSelected = computed(() =>
    suggestions.value.length > 0 && suggestions.value.every(t => t.selected)
)

function toggleAll() {
    const newVal = !allSelected.value
    suggestions.value.forEach(t => { t.selected = newVal })
}

onMounted(() => {
    promptInput.value?.focus()
})

async function handleSuggest() {
    error.value = ''
    suggesting.value = true
    try {
        const res = await suggestMutation.mutateAsync(prompt.value)
        suggestions.value = (res.data ?? []).map(t => ({ ...t, selected: true }))
        step.value = 'review'
    } catch (e) {
        error.value = e.response?.data?.message ?? 'Error al generar tareas. Inténtalo de nuevo.'
    } finally {
        suggesting.value = false
    }
}

async function handleConfirm() {
    error.value = ''
    confirming.value = true
    try {
        const selected = suggestions.value
            .filter(t => t.selected)
            .map(({ title, description, priority }) => ({ title, description, priority }))

        await confirmMutation.mutateAsync({
            columnId: targetColumnId.value,
            tasks: selected,
            boardId: props.boardId,
        })
        emit('saved')
    } catch (e) {
        error.value = e.response?.data?.message ?? 'Error al crear las tareas.'
    } finally {
        confirming.value = false
    }
}
</script>
