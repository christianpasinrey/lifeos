<template>
    <Teleport to="body">
        <div class="modal-overlay" @mousedown.self="$emit('close')">
            <div class="modal-backdrop" />
            <div class="modal-content liquid-glass liquid-glass-card task-modal-content">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-surface-100">Nueva tarea</h3>
                    <button class="btn-icon" @click="$emit('close')">
                        <XMarkIcon class="w-5 h-5" />
                    </button>
                </div>

                <form @submit.prevent="handleSubmit" class="form-group task-modal-form glass-scroll">
                    <div>
                        <label class="form-label">Título</label>
                        <input
                            v-model="form.title"
                            class="form-input"
                            placeholder="¿Qué hay que hacer?"
                            required
                            ref="titleInput"
                        />
                    </div>

                    <div>
                        <label class="form-label">Descripción corta (opcional)</label>
                        <textarea
                            v-model="form.description"
                            class="form-input"
                            rows="2"
                            placeholder="Una línea resumen…"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="form-label">Prioridad</label>
                            <CustomSelect
                                v-model="form.priority"
                                :options="priorityOptions"
                            />
                        </div>
                        <div>
                            <label class="form-label">Fecha límite</label>
                            <input
                                v-model="form.due_date"
                                type="date"
                                class="form-input"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="form-label">Cycle</label>
                        <CycleSelector
                            :board-id="boardId"
                            v-model="form.cycle_id"
                        />
                    </div>

                    <div>
                        <label class="form-label">Etiquetas</label>
                        <TagPicker
                            local
                            :board-id="boardId"
                            :modelValue="selectedTags"
                            @update:modelValue="onTagsChanged"
                        />
                    </div>

                    <div>
                        <label class="form-label">Contenido enriquecido</label>
                        <BodyHtmlEditor
                            v-model="form.body_html"
                            placeholder="Objetivo, contexto, criterios de aceptación…"
                        />
                    </div>

                    <div class="flex justify-end gap-3 mt-2 task-modal-actions">
                        <button type="button" class="btn-secondary" @click="$emit('close')">
                            Cancelar
                        </button>
                        <button type="submit" class="btn-primary" :disabled="saving || !form.title.trim()">
                            {{ saving ? 'Creando…' : 'Crear' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useCreateTask } from '@/composables/useTasks'
import CustomSelect from '@/components/ui/CustomSelect.vue'
import CycleSelector from './CycleSelector.vue'
import TagPicker from './TagPicker.vue'
import BodyHtmlEditor from './BodyHtmlEditor.vue'
import { XMarkIcon } from '@heroicons/vue/24/outline'

const priorityOptions = [
    { value: 'low', label: 'Baja' },
    { value: 'medium', label: 'Media' },
    { value: 'high', label: 'Alta' },
]

const props = defineProps({
    boardId: { type: Number, required: true },
    columnId: { type: Number, required: true },
})

const emit = defineEmits(['close', 'saved'])

const form = ref({
    title: '',
    description: '',
    body_html: '',
    priority: 'medium',
    due_date: '',
    cycle_id: null,
})
const selectedTags = ref([])

const saving = ref(false)
const titleInput = ref(null)
const createTask = useCreateTask()

onMounted(() => {
    titleInput.value?.focus()
})

function onTagsChanged(tags) {
    selectedTags.value = tags
}

async function handleSubmit() {
    saving.value = true
    try {
        const payload = {
            columnId: props.columnId,
            boardId: props.boardId,
            title: form.value.title,
            description: form.value.description || null,
            body_html: form.value.body_html || null,
            priority: form.value.priority,
            due_date: form.value.due_date || null,
        }
        if (form.value.cycle_id) payload.cycle_id = form.value.cycle_id
        if (selectedTags.value.length) {
            payload.tag_ids = selectedTags.value.map(t => t.id)
        }
        await createTask.mutateAsync(payload)
        emit('saved')
    } finally {
        saving.value = false
    }
}
</script>

<style scoped>
.task-modal-content {
    width: min(36rem, calc(100vw - 2rem));
    max-height: calc(100vh - 4rem);
    display: flex;
    flex-direction: column;
}

.task-modal-form {
    flex: 1;
    overflow-y: auto;
    padding-right: 0.5rem;
    margin-right: -0.5rem;
}

.task-modal-actions {
    padding-top: 0.5rem;
}
</style>
