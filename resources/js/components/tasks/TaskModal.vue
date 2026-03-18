<template>
    <Teleport to="body">
        <div class="modal-overlay" @mousedown.self="$emit('close')">
            <div class="modal-backdrop" />
            <div class="modal-content liquid-glass liquid-glass-card">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-surface-100">Nueva tarea</h3>
                </div>

                <form @submit.prevent="handleSubmit" class="form-group">
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
                        <label class="form-label">Descripción (opcional)</label>
                        <textarea
                            v-model="form.description"
                            class="form-input"
                            rows="3"
                            placeholder="Detalles adicionales..."
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

                    <div class="flex justify-end gap-3 mt-2">
                        <button type="button" class="btn-secondary" @click="$emit('close')">
                            Cancelar
                        </button>
                        <button type="submit" class="btn-primary" :disabled="saving">
                            Crear
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
    priority: 'medium',
    due_date: '',
})

const saving = ref(false)
const titleInput = ref(null)
const createTask = useCreateTask()

onMounted(() => {
    titleInput.value?.focus()
})

async function handleSubmit() {
    saving.value = true
    try {
        await createTask.mutateAsync({ columnId: props.columnId, boardId: props.boardId, ...form.value })
        emit('saved')
    } finally {
        saving.value = false
    }
}
</script>
