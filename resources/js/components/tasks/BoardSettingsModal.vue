<template>
    <Teleport to="body">
        <div class="modal-overlay" @mousedown.self="$emit('close')">
            <div class="modal-backdrop" />
            <div class="modal-content liquid-glass liquid-glass-card" style="max-width: 36rem; width: 100%;">
                <!-- Header -->
                <div class="flex items-center justify-between mb-5">
                    <h3 class="text-lg font-semibold text-surface-100">Configuraci&oacute;n del tablero</h3>
                    <button @click="$emit('close')" class="btn-close">
                        <XMarkIcon class="w-5 h-5" />
                    </button>
                </div>

                <div class="space-y-6 max-h-[70vh] overflow-y-auto glass-scroll">
                    <!-- General Section -->
                    <section>
                        <h4 class="text-xs font-semibold text-surface-400 uppercase tracking-wider mb-3">General</h4>
                        <div class="space-y-3">
                            <div>
                                <label class="form-label">Nombre</label>
                                <input v-model="boardName" class="form-input" @blur="saveBoardInfo" />
                            </div>
                            <div>
                                <label class="form-label">Descripci&oacute;n</label>
                                <textarea v-model="boardDesc" class="form-input" rows="2" @blur="saveBoardInfo" />
                            </div>
                        </div>
                    </section>

                    <!-- Custom Fields Section -->
                    <section>
                        <h4 class="text-xs font-semibold text-surface-400 uppercase tracking-wider mb-3">Campos personalizados</h4>

                        <div v-if="fields.length" class="space-y-2">
                            <div v-for="field in fields" :key="field.id"
                                class="flex items-center gap-3 px-3 py-2.5 rounded-xl bg-white/[0.03] border border-white/[0.06] group">
                                <!-- Type icon -->
                                <span class="text-surface-500 text-sm flex-shrink-0 w-5 text-center">{{ typeIcon(field.type) }}</span>
                                <!-- Name -->
                                <span class="text-sm text-surface-200 flex-1 truncate">{{ field.name }}</span>
                                <!-- Type badge -->
                                <span class="text-xs text-surface-500 bg-white/[0.04] px-2 py-0.5 rounded-full">{{ typeLabel(field.type) }}</span>
                                <!-- Required toggle -->
                                <button @click="toggleRequired(field)"
                                    class="text-xs transition-colors"
                                    :class="field.required ? 'text-primary-400' : 'text-surface-600 hover:text-surface-400'"
                                    :title="field.required ? 'Obligatorio' : 'Opcional'">
                                    {{ field.required ? '●' : '○' }}
                                </button>
                                <!-- Edit -->
                                <button @click="startEdit(field)"
                                    class="text-surface-600 hover:text-surface-300 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <PencilIcon class="w-3.5 h-3.5" />
                                </button>
                                <!-- Delete -->
                                <button @click="deleteField(field)"
                                    class="text-surface-600 hover:text-danger-400 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <TrashIcon class="w-3.5 h-3.5" />
                                </button>
                            </div>
                        </div>

                        <p v-else class="text-xs text-surface-600 py-2">No hay campos personalizados definidos.</p>

                        <!-- Edit form (shown when editing a field) -->
                        <div v-if="editingField" class="mt-3 p-3 rounded-xl bg-white/[0.03] border border-white/[0.06] space-y-2">
                            <input v-model="editForm.name" class="form-input text-sm" placeholder="Nombre del campo" />
                            <span class="text-xs text-surface-500">Tipo: {{ typeLabel(editingField.type) }} (no editable)</span>

                            <!-- Options editor for select/multi_select -->
                            <div v-if="['select', 'multi_select'].includes(editingField.type)" class="space-y-1.5">
                                <p class="text-xs text-surface-400">Opciones:</p>
                                <div v-for="(opt, i) in editForm.choices" :key="i" class="flex items-center gap-2">
                                    <input v-model="opt.label" class="form-input text-xs flex-1" placeholder="Opci&oacute;n..." />
                                    <input v-model="opt.color" type="color" class="w-6 h-6 rounded cursor-pointer border-0 bg-transparent" />
                                    <button @click="editForm.choices.splice(i, 1)" class="text-surface-600 hover:text-danger-400">
                                        <XMarkIcon class="w-3.5 h-3.5" />
                                    </button>
                                </div>
                                <button @click="editForm.choices.push({ id: crypto.randomUUID(), label: '', color: '#6366f1' })"
                                    class="text-xs text-surface-500 hover:text-primary-400">+ Opci&oacute;n</button>
                            </div>

                            <div class="flex justify-end gap-2">
                                <button @click="editingField = null" class="btn-secondary text-xs px-2 py-1">Cancelar</button>
                                <button @click="saveEdit" class="btn-primary text-xs px-2 py-1" :disabled="!editForm.name">Guardar</button>
                            </div>
                        </div>

                        <!-- Create new field form -->
                        <div v-if="creatingField" class="mt-3 p-3 rounded-xl bg-white/[0.03] border border-white/[0.06] space-y-2">
                            <input v-model="createForm.name" class="form-input text-sm" placeholder="Nombre del campo" />
                            <CustomSelect v-model="createForm.type" :options="typeOptions" />

                            <div v-if="['select', 'multi_select'].includes(createForm.type)" class="space-y-1.5">
                                <div v-for="(opt, i) in createForm.choices" :key="i" class="flex items-center gap-2">
                                    <input v-model="opt.label" class="form-input text-xs flex-1" placeholder="Opci&oacute;n..." />
                                    <input v-model="opt.color" type="color" class="w-6 h-6 rounded cursor-pointer border-0 bg-transparent" />
                                    <button @click="createForm.choices.splice(i, 1)" class="text-surface-600 hover:text-danger-400">
                                        <XMarkIcon class="w-3.5 h-3.5" />
                                    </button>
                                </div>
                                <button @click="createForm.choices.push({ label: '', color: '#6366f1' })"
                                    class="text-xs text-surface-500 hover:text-primary-400">+ Opci&oacute;n</button>
                            </div>

                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" v-model="createForm.required" class="form-checkbox" />
                                <span class="text-xs text-surface-400">Obligatorio</span>
                            </label>

                            <div class="flex justify-end gap-2">
                                <button @click="creatingField = false" class="btn-secondary text-xs px-2 py-1">Cancelar</button>
                                <button @click="createField" class="btn-primary text-xs px-2 py-1" :disabled="!createForm.name">Crear</button>
                            </div>
                        </div>

                        <button v-if="!creatingField && !editingField" @click="creatingField = true"
                            class="mt-2 flex items-center gap-1.5 text-xs text-surface-500 hover:text-primary-400 transition-colors">
                            <PlusIcon class="w-3.5 h-3.5" />
                            Nuevo campo
                        </button>
                    </section>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useUpdateBoard } from '@/composables/useTasks'
import {
    useCustomFields, useCreateCustomField, useUpdateCustomField, useDeleteCustomField
} from '@/composables/useCustomFields'
import CustomSelect from '@/components/ui/CustomSelect.vue'
import { XMarkIcon, PlusIcon, PencilIcon, TrashIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    board: { type: Object, required: true },
    boardId: { type: Number, required: true },
})

defineEmits(['close'])

// Board info
const boardName = ref(props.board?.name || '')
const boardDesc = ref(props.board?.description || '')
const updateBoard = useUpdateBoard()

async function saveBoardInfo() {
    if (boardName.value === props.board?.name && boardDesc.value === (props.board?.description || '')) return
    await updateBoard.mutateAsync({ id: props.boardId, data: { name: boardName.value, description: boardDesc.value } })
}

// Custom fields
const { data: fieldsData } = useCustomFields(computed(() => props.boardId))
const fields = computed(() => fieldsData.value?.data ?? [])

const createCustomField = useCreateCustomField()
const updateCustomField = useUpdateCustomField()
const deleteCustomFieldMut = useDeleteCustomField()

// Type helpers
const typeOptions = [
    { value: 'text', label: 'Texto' },
    { value: 'number', label: 'N\u00famero' },
    { value: 'date', label: 'Fecha' },
    { value: 'select', label: 'Selecci\u00f3n' },
    { value: 'multi_select', label: 'Multi-selecci\u00f3n' },
    { value: 'checkbox', label: 'Checkbox' },
    { value: 'url', label: 'URL' },
]

function typeIcon(type) {
    return { text: 'Aa', number: '#', date: '\ud83d\udcc5', select: '\u25bc', multi_select: '\u2630', checkbox: '\u2611', url: '\ud83d\udd17' }[type] || '?'
}

function typeLabel(type) {
    return typeOptions.find(t => t.value === type)?.label || type
}

// Toggle required
async function toggleRequired(field) {
    await updateCustomField.mutateAsync({ id: field.id, boardId: props.boardId, required: !field.required })
}

// Edit field
const editingField = ref(null)
const editForm = ref({ name: '', choices: [] })

function startEdit(field) {
    editingField.value = field
    editForm.value = {
        name: field.name,
        choices: field.options?.choices ? JSON.parse(JSON.stringify(field.options.choices)) : [],
    }
    creatingField.value = false
}

async function saveEdit() {
    const data = { id: editingField.value.id, boardId: props.boardId, name: editForm.value.name }
    if (['select', 'multi_select'].includes(editingField.value.type)) {
        data.options = { choices: editForm.value.choices }
    }
    await updateCustomField.mutateAsync(data)
    editingField.value = null
}

// Delete field
async function deleteField(field) {
    if (!confirm(`\u00bfEliminar el campo "${field.name}"? Se perder\u00e1n todos los valores en todas las tareas.`)) return
    await deleteCustomFieldMut.mutateAsync({ id: field.id, boardId: props.boardId })
}

// Create field
const creatingField = ref(false)
const createForm = ref({ name: '', type: 'text', choices: [], required: false })

watch(creatingField, (v) => {
    if (v) {
        createForm.value = { name: '', type: 'text', choices: [], required: false }
        editingField.value = null
    }
})

async function createField() {
    const data = {
        boardId: props.boardId,
        name: createForm.value.name,
        type: createForm.value.type,
        required: createForm.value.required,
    }
    if (['select', 'multi_select'].includes(createForm.value.type)) {
        data.options = {
            choices: createForm.value.choices.map(c => ({
                id: crypto.randomUUID(),
                label: c.label,
                color: c.color || '#6366f1',
            }))
        }
    }
    await createCustomField.mutateAsync(data)
    creatingField.value = false
}
</script>
