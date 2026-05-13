<template>
    <Teleport to=".layout-root">
        <Transition name="slide-right">
            <aside class="task-detail-aside" v-if="task">
                <div class="liquid-glass liquid-glass-panel h-full flex flex-col rounded-[20px]">
                    <!-- Header -->
                    <div class="flex items-center justify-between px-5 py-4 border-b border-white/[0.06]">
                        <div class="flex items-center gap-2 flex-1 min-w-0">
                            <div class="w-8 h-8 rounded-lg bg-primary-500/10 flex items-center justify-center flex-shrink-0">
                                <ClipboardDocumentListIcon class="w-4 h-4 text-primary-400" />
                            </div>
                            <!-- Inline editable title -->
                            <input
                                v-model="titleLocal"
                                @blur="saveTitle"
                                @keydown.enter="$event.target.blur()"
                                class="bg-transparent text-base font-semibold text-white border-none outline-none w-full
                                       focus:ring-1 focus:ring-primary-500/30 rounded px-1 -ml-1 transition-shadow"
                            />
                        </div>
                        <button @click="$emit('close')" class="btn-close flex-shrink-0 ml-2">
                            <XMarkIcon class="w-5 h-5" />
                        </button>
                    </div>

                    <!-- Scrollable content -->
                    <div class="flex-1 overflow-y-auto px-5 py-4 space-y-5 glass-scroll">
                        <!-- Metadata -->
                        <div class="grid grid-cols-2 gap-3">
                            <!-- Column selector -->
                            <div>
                                <label class="form-label">Columna</label>
                                <CustomSelect
                                    :modelValue="task.column_id"
                                    :options="columnOptions"
                                    @update:modelValue="moveToColumn"
                                />
                            </div>
                            <!-- Due date -->
                            <div>
                                <label class="form-label">Fecha límite</label>
                                <input
                                    :value="task.due_date || ''"
                                    type="date"
                                    class="form-input"
                                    :class="isOverdue ? 'text-danger-400' : ''"
                                    @change="saveField('due_date', $event.target.value || null)"
                                />
                            </div>
                        </div>

                        <!-- Priority segmented control -->
                        <div>
                            <label class="form-label">Prioridad</label>
                            <div class="flex rounded-lg overflow-hidden border border-white/[0.08]">
                                <button
                                    v-for="p in priorities"
                                    :key="p.value"
                                    @click="saveField('priority', p.value)"
                                    class="flex-1 py-1.5 text-xs font-medium transition-all"
                                    :class="task.priority === p.value
                                        ? p.activeClass
                                        : 'text-surface-500 hover:text-surface-300 bg-white/[0.02]'"
                                >
                                    {{ p.label }}
                                </button>
                            </div>
                        </div>

                        <!-- Description -->
                        <div>
                            <label class="form-label">Descripción</label>
                            <textarea
                                v-model="descLocal"
                                @blur="saveDescription"
                                class="form-input w-full resize-none"
                                rows="2"
                                placeholder="Resumen corto…"
                            />
                        </div>

                        <!-- Tags -->
                        <div>
                            <label class="form-label">Etiquetas</label>
                            <TagPicker
                                :target-type="'task'"
                                :target-id="taskId"
                                :board-id="boardId"
                                :modelValue="task.tags || []"
                                @change="onTagsChanged"
                            />
                        </div>

                        <!-- Cycle -->
                        <div>
                            <label class="form-label">Cycle</label>
                            <CycleSelector
                                :board-id="boardId"
                                :modelValue="task.cycle_id"
                                @change="onCycleChanged"
                            />
                        </div>

                        <!-- Rich body -->
                        <div>
                            <label class="form-label flex items-center justify-between">
                                <span>Contenido enriquecido</span>
                                <button
                                    type="button"
                                    class="text-[10px] text-surface-500 hover:text-surface-300 transition-colors"
                                    @click="bodyEditing = !bodyEditing"
                                >
                                    {{ bodyEditing ? 'Vista previa' : 'Editar' }}
                                </button>
                            </label>
                            <BodyHtmlEditor
                                v-if="bodyEditing"
                                v-model="bodyLocal"
                                @blur="saveBody"
                            />
                            <div
                                v-else
                                class="rounded-xl border border-white/[0.08] bg-white/[0.02] px-4 py-3 min-h-[80px]"
                            >
                                <BodyHtmlRenderer :html="task.body_html || ''" />
                            </div>
                        </div>

                        <!-- Custom Fields -->
                        <div>
                            <h4 class="text-xs font-semibold text-surface-400 uppercase tracking-wider mb-2">Campos personalizados</h4>
                            <div class="space-y-3">
                                <div v-for="field in customFields" :key="field.id">
                                    <label class="form-label">{{ field.name }}{{ field.required ? ' *' : '' }}</label>
                                    <CustomFieldRenderer
                                        :field="field"
                                        :value="getFieldValue(field.id)"
                                        @update="saveCustomField(field.id, $event)"
                                    />
                                </div>
                            </div>
                            <AddCustomFieldInline :board-id="boardId" @created="onFieldCreated" class="mt-2" />
                        </div>

                        <!-- Attachments -->
                        <TaskAttachments :task-id="taskId" :board-id="boardId" :attachments="task.attachments || []" />
                    </div>

                    <!-- Footer -->
                    <div class="px-5 py-3 border-t border-white/[0.06] flex items-center justify-between">
                        <button @click="handleDelete" class="text-xs text-danger-400 hover:text-danger-300 transition-colors">
                            Eliminar tarea
                        </button>
                        <span class="text-xs text-surface-600">
                            Creada {{ formatRelativeDate(task.created_at) }}
                        </span>
                    </div>
                </div>
            </aside>
        </Transition>
    </Teleport>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useTask, useUpdateTask, useDeleteTask, useMoveTask } from '@/composables/useTasks'
import { useCustomFields, useSetFieldValues } from '@/composables/useCustomFields'
import CustomFieldRenderer from './CustomFieldRenderer.vue'
import AddCustomFieldInline from './AddCustomFieldInline.vue'
import TaskAttachments from './TaskAttachments.vue'
import TagPicker from './TagPicker.vue'
import CycleSelector from './CycleSelector.vue'
import BodyHtmlEditor from './BodyHtmlEditor.vue'
import BodyHtmlRenderer from './BodyHtmlRenderer.vue'
import CustomSelect from '@/components/ui/CustomSelect.vue'
import { ClipboardDocumentListIcon, XMarkIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    taskId: { type: Number, required: true },
    boardId: { type: Number, required: true },
    columns: { type: Array, required: true },
})

const emit = defineEmits(['close'])

const { data: taskData } = useTask(computed(() => props.taskId))
const task = computed(() => taskData.value?.data ?? null)

const { data: fieldsData } = useCustomFields(computed(() => props.boardId))
const customFields = computed(() => fieldsData.value?.data ?? [])

const updateTask = useUpdateTask()
const deleteTask = useDeleteTask()
const moveTask = useMoveTask()
const setFieldValues = useSetFieldValues()

// Local state for inline editing
const titleLocal = ref('')
const descLocal = ref('')
const bodyLocal = ref('')
const bodyEditing = ref(false)

watch(task, (t) => {
    if (t) {
        titleLocal.value = t.title
        descLocal.value = t.description || ''
        bodyLocal.value = t.body_html || ''
    }
}, { immediate: true })

const priorities = [
    { value: 'low', label: 'Baja', activeClass: 'bg-surface-500/20 text-surface-300' },
    { value: 'medium', label: 'Media', activeClass: 'bg-amber-500/20 text-amber-400' },
    { value: 'high', label: 'Alta', activeClass: 'bg-danger-500/20 text-danger-400' },
]

const columnOptions = computed(() =>
    props.columns.map(c => ({ value: c.id, label: c.name }))
)

const isOverdue = computed(() => {
    if (!task.value?.due_date) return false
    return new Date(task.value.due_date) < new Date(new Date().toDateString())
})

function getFieldValue(fieldId) {
    const fv = task.value?.custom_field_values?.find(v => v.field_id === fieldId)
    return fv?.value ?? null
}

async function saveTitle() {
    if (titleLocal.value === task.value?.title || !titleLocal.value.trim()) {
        titleLocal.value = task.value?.title || ''
        return
    }
    await updateTask.mutateAsync({ id: props.taskId, boardId: props.boardId, title: titleLocal.value })
}

async function saveDescription() {
    if (descLocal.value === (task.value?.description || '')) return
    await updateTask.mutateAsync({ id: props.taskId, boardId: props.boardId, description: descLocal.value })
}

async function saveBody() {
    if (bodyLocal.value === (task.value?.body_html || '')) return
    await updateTask.mutateAsync({ id: props.taskId, boardId: props.boardId, body_html: bodyLocal.value })
}

function onTagsChanged() {
    // Mutation invalidates queries; nothing else to do.
}

async function onCycleChanged(cycleId) {
    await updateTask.mutateAsync({
        id: props.taskId,
        boardId: props.boardId,
        cycle_id: cycleId ?? 0, // 0 = clear server-side
    })
}

async function saveField(field, value) {
    await updateTask.mutateAsync({ id: props.taskId, boardId: props.boardId, [field]: value })
}

async function moveToColumn(columnId) {
    await moveTask.mutateAsync({
        id: props.taskId,
        boardId: props.boardId,
        column_id: columnId,
        sort_order: 0,
    })
}

async function saveCustomField(fieldId, value) {
    await setFieldValues.mutateAsync({
        taskId: props.taskId,
        boardId: props.boardId,
        values: [{ field_id: fieldId, value }],
    })
}

function onFieldCreated() {
    // Fields query auto-invalidates
}

async function handleDelete() {
    if (!confirm('¿Eliminar esta tarea? Esta acción no se puede deshacer.')) return
    await deleteTask.mutateAsync({ id: props.taskId, boardId: props.boardId })
    emit('close')
}

function formatRelativeDate(dateStr) {
    if (!dateStr) return ''
    const diff = Date.now() - new Date(dateStr).getTime()
    const mins = Math.floor(diff / 60000)
    if (mins < 60) return `hace ${mins}m`
    const hours = Math.floor(mins / 60)
    if (hours < 24) return `hace ${hours}h`
    const days = Math.floor(hours / 24)
    return `hace ${days}d`
}
</script>
