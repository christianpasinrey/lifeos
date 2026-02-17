<template>
    <div
        class="task-card liquid-glass liquid-glass-card"
        :class="{
            'is-dragging': drag.dragging.value?.type === 'task' && drag.dragging.value?.id === task.id,
            'task-card-dragover': drag.dragOverId.value === `task-${task.id}`,
        }"
        draggable="true"
        @dragstart.stop="drag.onDragStart($event, { type: 'task', id: task.id, sourceColumnId: columnId })"
        @dragend="drag.onDragEnd"
        @dragover.stop="onDragOver"
        @dragleave="drag.onDragLeave($event, `task-${task.id}`)"
        @drop.stop="onDrop"
        @click="$emit('edit')"
    >
        <!-- Priority indicator -->
        <div class="flex items-center justify-between gap-2">
            <span class="task-priority" :class="`task-priority-${task.priority}`">
                {{ priorityLabel }}
            </span>
            <span v-if="task.due_date" class="task-due" :class="{ 'task-due-overdue': isOverdue }">
                {{ formatDue(task.due_date) }}
            </span>
        </div>

        <p class="text-sm font-medium text-surface-100 mt-1.5 leading-snug">{{ task.title }}</p>
        <p v-if="task.description" class="text-xs text-surface-500 mt-1 line-clamp-2">
            {{ task.description }}
        </p>
    </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    task: { type: Object, required: true },
    index: { type: Number, required: true },
    columnId: { type: Number, required: true },
    drag: { type: Object, required: true },
})

const emit = defineEmits(['edit', 'dropAbove'])

const priorityLabels = { low: 'Baja', medium: 'Media', high: 'Alta' }
const priorityLabel = computed(() => priorityLabels[props.task.priority] || 'Media')

const isOverdue = computed(() => {
    if (!props.task.due_date) return false
    return new Date(props.task.due_date) < new Date(new Date().toDateString())
})

function formatDue(d) {
    return new Date(d).toLocaleDateString('es-ES', { day: 'numeric', month: 'short' })
}

function onDragOver(e) {
    if (props.drag.dragging.value?.type === 'task') {
        props.drag.onDragOver(e, `task-${props.task.id}`)
    }
}

function onDrop(e) {
    if (props.drag.dragging.value?.type === 'task') {
        props.drag.onDrop(e, (payload) => {
            emit('dropAbove', payload)
        })
    }
}
</script>
