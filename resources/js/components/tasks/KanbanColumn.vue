<template>
    <div
        class="kanban-column liquid-glass liquid-glass-card"
        :class="{ 'kanban-column-dragover': drag.dragOverId.value === `col-${column.id}` }"
        :data-column-id="column.id"
        draggable="true"
        @dragstart.self="drag.onDragStart($event, { type: 'column', id: column.id })"
        @dragend="drag.onDragEnd"
        @dragover="onColumnDragOver"
        @dragleave="drag.onDragLeave($event, `col-${column.id}`)"
        @drop="onColumnDrop"
    >
        <!-- Column header -->
        <div class="kanban-column-header">
            <div class="flex items-center gap-2 min-w-0">
                <span
                    class="w-2.5 h-2.5 rounded-full flex-shrink-0"
                    :style="{ backgroundColor: column.color || '#64748b' }"
                />
                <span class="text-sm font-semibold text-surface-200 truncate">{{ column.name }}</span>
                <span class="text-xs text-surface-500">{{ column.tasks?.length ?? 0 }}</span>
            </div>
            <div class="flex items-center gap-1">
                <button class="btn-icon" @click="$emit('editColumn', column)" title="Editar columna">
                    <PencilIcon class="w-3.5 h-3.5" />
                </button>
                <button class="btn-icon" @click="$emit('addTask', column.id)" title="Nueva tarea">
                    <PlusIcon class="w-3.5 h-3.5" />
                </button>
            </div>
        </div>

        <!-- Tasks drop zone -->
        <TransitionGroup
            tag="div"
            name="task-flip"
            class="kanban-tasks glass-scroll"
            @dragover.prevent="onTaskAreaDragOver"
            @dragleave="onTaskAreaDragLeave"
            @drop.stop="onTaskAreaDrop"
        >
            <TaskCard
                v-for="(task, taskIndex) in renderedTasks"
                :key="task.id"
                :task="task"
                :index="taskIndex"
                :column-id="column.id"
                :drag="drag"
                :custom-fields="customFields"
                :cycles="cycles"
                @edit="$emit('editTask', task, column.id)"
                @drop-above="(payload) => onTaskDropAbove(payload, taskIndex)"
                @task-drag-over="onTaskDragOver"
            />

            <!-- Empty state / bottom drop zone -->
            <div
                v-if="!renderedTasks.length"
                key="__empty__"
                class="kanban-empty"
                :class="{ 'kanban-empty-active': taskDropActive }"
            >
                <span class="text-xs text-surface-600">Arrastra aquí</span>
            </div>
        </TransitionGroup>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import TaskCard from './TaskCard.vue'
import { PlusIcon, PencilIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    column: { type: Object, required: true },
    index: { type: Number, required: true }, // position in the parent's rendered list
    boardId: { type: Number, required: true },
    drag: { type: Object, required: true },
    customFields: { type: Array, default: () => [] },
    cycles: { type: Array, default: () => [] },
})

const emit = defineEmits([
    'editColumn', 'addTask', 'editTask',
    'dropTask', 'dropColumn',
    'columnDragOver',
])

const taskDropActive = ref(false)

// Local preview reorder for tasks dragged WITHIN this column.
const previewTaskOrder = ref(null)

const baseTasks = computed(() => props.column.tasks ?? [])

const renderedTasks = computed(() => {
    if (!previewTaskOrder.value) return baseTasks.value
    const byId = new Map(baseTasks.value.map(t => [t.id, t]))
    return previewTaskOrder.value
        .map(id => byId.get(id))
        .filter(Boolean)
})

watch(() => props.drag.dragging.value, (val) => {
    if (!val || val.type !== 'task') previewTaskOrder.value = null
    if (val?.type === 'task' && val.sourceColumnId !== props.column.id) {
        previewTaskOrder.value = null
    }
})

function onTaskDragOver(targetTaskId) {
    const payload = props.drag.dragging.value
    if (!payload || payload.type !== 'task' || payload.sourceColumnId !== props.column.id) return
    const list = baseTasks.value
    const fromIdx = list.findIndex(t => t.id === payload.id)
    const toIdx = list.findIndex(t => t.id === targetTaskId)
    if (fromIdx < 0 || toIdx < 0 || fromIdx === toIdx) {
        previewTaskOrder.value = list.map(t => t.id)
        return
    }
    const order = list.map(t => t.id)
    order.splice(fromIdx, 1)
    order.splice(toIdx, 0, payload.id)
    previewTaskOrder.value = order
}

function onColumnDragOver(e) {
    if (props.drag.dragging.value?.type === 'column') {
        props.drag.onDragOver(e, `col-${props.column.id}`)
        emit('columnDragOver', props.index)
    }
}

function onColumnDrop(e) {
    if (props.drag.dragging.value?.type === 'column') {
        props.drag.onDrop(e, (payload) => {
            emit('dropColumn', payload, props.index)
        })
    }
}

function onTaskAreaDragOver(e) {
    if (props.drag.dragging.value?.type === 'task') {
        e.dataTransfer.dropEffect = 'move'
        taskDropActive.value = true
    }
}

function onTaskAreaDragLeave() {
    taskDropActive.value = false
}

function onTaskAreaDrop(e) {
    taskDropActive.value = false
    if (props.drag.dragging.value?.type !== 'task') return

    // If we have a preview order, use it. Otherwise drop at end.
    const payload = props.drag.dragging.value
    const targetIndex = previewTaskOrder.value
        ? previewTaskOrder.value.indexOf(payload.id)
        : (baseTasks.value.length ?? 0)
    previewTaskOrder.value = null
    props.drag.onDrop(e, (p) => emit('dropTask', p, props.column.id, targetIndex))
}

function onTaskDropAbove(payload, taskIndex) {
    const finalIndex = previewTaskOrder.value
        ? previewTaskOrder.value.indexOf(payload.id)
        : taskIndex
    previewTaskOrder.value = null
    emit('dropTask', payload, props.column.id, finalIndex)
}
</script>
