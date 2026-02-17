<template>
    <Teleport to="body">
        <div class="modal-overlay" @mousedown.self="$emit('close')">
            <div class="modal-backdrop" />
            <div class="modal-content liquid-glass liquid-glass-card">
                <h3 class="text-lg font-semibold text-surface-100 mb-4">
                    {{ board ? 'Editar tablero' : 'Nuevo tablero' }}
                </h3>

                <form @submit.prevent="handleSubmit" class="form-group">
                    <div>
                        <label class="form-label">Nombre</label>
                        <input
                            v-model="form.name"
                            class="form-input"
                            placeholder="Mi tablero"
                            required
                            ref="nameInput"
                        />
                    </div>
                    <div>
                        <label class="form-label">Descripción (opcional)</label>
                        <textarea
                            v-model="form.description"
                            class="form-input"
                            rows="2"
                            placeholder="Describe el propósito del tablero..."
                        />
                    </div>

                    <div class="flex justify-end gap-3 mt-2">
                        <button type="button" class="btn-secondary" @click="$emit('close')">
                            Cancelar
                        </button>
                        <button type="submit" class="btn-primary" :disabled="saving">
                            {{ board ? 'Guardar' : 'Crear' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useCreateBoard, useUpdateBoard, useDeleteBoard } from '@/composables/useTasks'

const props = defineProps({
    board: { type: Object, default: null },
})

const emit = defineEmits(['close', 'saved'])

const form = ref({
    name: props.board?.name ?? '',
    description: props.board?.description ?? '',
})

const saving = ref(false)
const nameInput = ref(null)
const createBoard = useCreateBoard()
const updateBoard = useUpdateBoard()

onMounted(() => {
    nameInput.value?.focus()
})

async function handleSubmit() {
    saving.value = true
    try {
        if (props.board) {
            await updateBoard.mutateAsync({ id: props.board.id, data: form.value })
        } else {
            await createBoard.mutateAsync(form.value)
        }
        emit('saved')
    } finally {
        saving.value = false
    }
}
</script>
