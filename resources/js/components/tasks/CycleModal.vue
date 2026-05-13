<template>
    <Teleport to="body">
        <div class="modal-backdrop" @click.self="$emit('close')">
            <div class="modal-panel liquid-glass liquid-glass-panel max-w-md w-full">
                <div class="modal-header">
                    <h3 class="modal-title">{{ isEdit ? 'Editar cycle' : 'Nuevo cycle' }}</h3>
                    <button class="btn-close" @click="$emit('close')">
                        <XMarkIcon class="w-5 h-5" />
                    </button>
                </div>

                <div class="modal-body space-y-4">
                    <div>
                        <label class="form-label">Nombre</label>
                        <input v-model="form.name" type="text" class="form-input w-full" placeholder="Sprint 12, v2.0…" />
                    </div>

                    <div>
                        <label class="form-label">Descripción</label>
                        <textarea v-model="form.description" rows="2" class="form-input w-full resize-none" placeholder="Objetivo del cycle…" />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="form-label">Estado</label>
                            <select v-model="form.status" class="form-input w-full">
                                <option value="planned">Planificado</option>
                                <option value="active">Activo</option>
                                <option value="completed">Completado</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Color</label>
                            <input v-model="form.color" type="color" class="form-input w-full h-9" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="form-label">Inicio</label>
                            <input v-model="form.starts_on" type="date" class="form-input w-full" />
                        </div>
                        <div>
                            <label class="form-label">Fin</label>
                            <input v-model="form.ends_on" type="date" class="form-input w-full" />
                        </div>
                    </div>

                    <p v-if="error" class="text-xs text-danger-400">{{ error }}</p>
                </div>

                <div class="modal-footer">
                    <button
                        v-if="isEdit"
                        class="text-xs text-danger-400 hover:text-danger-300 transition-colors mr-auto"
                        @click="onDelete"
                    >
                        Eliminar
                    </button>
                    <button class="btn-secondary" @click="$emit('close')">Cancelar</button>
                    <button class="btn-primary" :disabled="!form.name.trim() || saving" @click="onSave">
                        {{ saving ? 'Guardando…' : (isEdit ? 'Guardar' : 'Crear') }}
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { ref, computed } from 'vue'
import { XMarkIcon } from '@heroicons/vue/24/outline'
import { useCreateCycle, useUpdateCycle, useDeleteCycle } from '@/composables/useCycles'

const props = defineProps({
    boardId: { type: Number, required: true },
    cycle: { type: Object, default: null },
})

const emit = defineEmits(['close', 'saved'])

const isEdit = computed(() => !!props.cycle)
const error = ref('')

const form = ref({
    name: props.cycle?.name ?? '',
    description: props.cycle?.description ?? '',
    status: props.cycle?.status ?? 'planned',
    color: props.cycle?.color ?? '#6366f1',
    starts_on: props.cycle?.starts_on ?? '',
    ends_on: props.cycle?.ends_on ?? '',
})

const create = useCreateCycle()
const update = useUpdateCycle()
const remove = useDeleteCycle()
const saving = ref(false)

async function onSave() {
    error.value = ''
    saving.value = true
    try {
        const payload = { ...form.value, boardId: props.boardId }
        if (!payload.starts_on) payload.starts_on = null
        if (!payload.ends_on) payload.ends_on = null
        if (isEdit.value) {
            await update.mutateAsync({ id: props.cycle.id, ...payload })
        } else {
            await create.mutateAsync(payload)
        }
        emit('saved')
    } catch (e) {
        error.value = e?.response?.data?.message || 'Error guardando el cycle.'
    } finally {
        saving.value = false
    }
}

async function onDelete() {
    if (!confirm('¿Eliminar este cycle? Las tareas asignadas mantendrán su ubicación pero perderán la asignación.')) return
    await remove.mutateAsync({ id: props.cycle.id, boardId: props.boardId })
    emit('saved')
}
</script>
