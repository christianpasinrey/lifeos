<template>
    <Teleport to="body">
        <div class="modal-overlay" @mousedown.self="$emit('close')">
            <div class="modal-backdrop" />
            <div class="modal-content liquid-glass liquid-glass-card">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-surface-100">
                        {{ column ? 'Editar columna' : 'Nueva columna' }}
                    </h3>
                    <button
                        v-if="column"
                        class="text-xs text-danger-400 hover:text-danger-500 transition-colors"
                        @click="handleDelete"
                    >
                        Eliminar
                    </button>
                </div>

                <form @submit.prevent="handleSubmit" class="form-group">
                    <div>
                        <label class="form-label">Nombre</label>
                        <input
                            v-model="form.name"
                            class="form-input"
                            placeholder="Nombre de la columna"
                            required
                            ref="nameInput"
                        />
                    </div>
                    <div>
                        <label class="form-label">Color</label>
                        <div class="flex items-center gap-2">
                            <input
                                v-model="form.color"
                                type="color"
                                class="w-10 h-10 rounded-lg border border-white/10 cursor-pointer bg-transparent"
                            />
                            <span class="text-sm text-surface-400">{{ form.color }}</span>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 mt-2">
                        <button type="button" class="btn-secondary" @click="$emit('close')">
                            Cancelar
                        </button>
                        <button type="submit" class="btn-primary" :disabled="saving">
                            {{ column ? 'Guardar' : 'Crear' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useCreateColumn, useUpdateColumn, useDeleteColumn } from '@/composables/useTasks'

const props = defineProps({
    boardId: { type: Number, required: true },
    column: { type: Object, default: null },
})

const emit = defineEmits(['close', 'saved'])

const form = ref({
    name: props.column?.name ?? '',
    color: props.column?.color ?? '#60a5fa',
})

const saving = ref(false)
const nameInput = ref(null)
const createColumn = useCreateColumn()
const updateColumn = useUpdateColumn()
const deleteColumn = useDeleteColumn()

onMounted(() => {
    nameInput.value?.focus()
})

async function handleSubmit() {
    saving.value = true
    try {
        if (props.column) {
            await updateColumn.mutateAsync({ id: props.column.id, boardId: props.boardId, ...form.value })
        } else {
            await createColumn.mutateAsync({ boardId: props.boardId, ...form.value })
        }
        emit('saved')
    } finally {
        saving.value = false
    }
}

async function handleDelete() {
    if (!confirm('¿Eliminar esta columna y todas sus tareas?')) return
    await deleteColumn.mutateAsync({ id: props.column.id, boardId: props.boardId })
    emit('saved')
}
</script>
