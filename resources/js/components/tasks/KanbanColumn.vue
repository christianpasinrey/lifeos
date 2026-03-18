<template>
    <div
        class="kanban-column liquid-glass liquid-glass-card"
        :class="{ 'kanban-column-dragover': drag.dragOverId.value === `col-${column.id}` }"
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
        <div
            class="kanban-tasks glass-scroll"
            @dragover.prevent="onTaskAreaDragOver"
            @dragleave="onTaskAreaDragLeave"
            @drop.stop="onTaskAreaDrop"
        >
            <TaskCard
                v-for="(task, index) in column.tasks"
                :key="task.id"
                :task="task"
                :index="index"
                :column-id="column.id"
                :drag="drag"
                :custom-fields="customFields"
                @edit="$emit('editTask', task, column.id)"
                @drop-above="(payload) => $emit('dropTask', payload, column.id, index)"
            />

            <!-- Empty state / bottom drop zone -->
            <div
                v-if="!column.tasks?.length"
                class="kanban-empty"
                :class="{ 'kanban-empty-active': taskDropActive }"
            >
                <span class="text-xs text-surface-600">Arrastra aquí</span>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import TaskCard from './TaskCard.vue'
import { PlusIcon, PencilIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    column: { type: Object, required: true },
    boardId: { type: Number, required: true },
    drag: { type: Object, required: true },
    customFields: { type: Array, default: () => [] },
})

const emit = defineEmits(['editColumn', 'addTask', 'editTask', 'dropTask', 'dropColumn'])

const taskDropActive = ref(false)

function onColumnDragOver(e) {
    if (props.drag.dragging.value?.type === 'column') {
        props.drag.onDragOver(e, `col-${props.column.id}`)
    }
}

function onColumnDrop(e) {
    if (props.drag.dragging.value?.type === 'column') {
        props.drag.onDrop(e, (payload) => {
            emit('dropColumn', payload, props.column.sort_order)
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
    if (props.drag.dragging.value?.type === 'task') {
        const targetIndex = props.column.tasks?.length ?? 0
        props.drag.onDrop(e, (payload) => {
            emit('dropTask', payload, props.column.id, targetIndex)
        })
    }
}
</script>
