<template>
    <div
        class="task-card liquid-glass liquid-glass-card"
        :class="{
            'is-dragging': drag.dragging.value?.type === 'task' && drag.dragging.value?.id === task.id,
            'task-card-dragover': drag.dragOverId.value === `task-${task.id}`,
        }"
        :data-task-id="task.id"
        draggable="true"
        @dragstart.stop="drag.onDragStart($event, { type: 'task', id: task.id, sourceColumnId: columnId })"
        @dragend="drag.onDragEnd"
        @dragover.stop="onDragOver"
        @dragleave="drag.onDragLeave($event, `task-${task.id}`)"
        @drop.stop="onDrop"
        @click="$emit('edit')"
    >
        <!-- Top row: priority + due date -->
        <div class="flex items-center justify-between gap-2">
            <span class="task-priority" :class="`task-priority-${task.priority}`">
                {{ priorityLabel }}
            </span>
            <span v-if="task.due_date" class="task-due" :class="{ 'task-due-overdue': isOverdue }">
                <CalendarIcon class="w-3 h-3 inline -mt-px mr-0.5" />
                {{ formatDue(task.due_date) }}
            </span>
        </div>

        <!-- Title -->
        <p class="text-sm font-medium text-surface-100 mt-1.5 leading-snug">{{ task.title }}</p>

        <!-- Description -->
        <p v-if="task.description" class="text-xs text-surface-500 mt-1 line-clamp-2">
            {{ task.description }}
        </p>

        <!-- Tags -->
        <div v-if="task.tags?.length" class="flex flex-wrap gap-1 mt-2">
            <TagChip v-for="tag in task.tags" :key="tag.id" :tag="tag" size="xs" />
        </div>

        <!-- Custom field chips -->
        <div v-if="fieldChips.length" class="task-chips">
            <span
                v-for="chip in fieldChips"
                :key="chip.fieldId"
                class="task-chip"
                :title="chip.fieldName"
            >
                <span class="task-chip-dot" :style="{ background: chip.color }" />
                {{ chip.value }}
            </span>
        </div>

        <!-- Bottom row: cycle + rich body + attachments -->
        <div class="flex items-center gap-2 mt-2 text-surface-600">
            <CycleBadge v-if="cycle" :cycle="cycle" />
            <span v-if="task.body_html" class="flex items-center gap-0.5 text-[10px]" title="Tiene contenido enriquecido">
                <DocumentTextIcon class="w-3 h-3" />
            </span>
            <span v-if="task.attachments_count" class="flex items-center gap-1 text-[10px]">
                <PaperClipIcon class="w-3 h-3" />
                <span class="font-medium">{{ task.attachments_count }}</span>
            </span>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { CalendarIcon, PaperClipIcon, DocumentTextIcon } from '@heroicons/vue/24/outline'
import TagChip from './TagChip.vue'
import CycleBadge from './CycleBadge.vue'

const props = defineProps({
    task: { type: Object, required: true },
    index: { type: Number, required: true },
    columnId: { type: Number, required: true },
    drag: { type: Object, required: true },
    customFields: { type: Array, default: () => [] },
    cycles: { type: Array, default: () => [] },
})

const cycle = computed(() => {
    if (!props.task.cycle_id) return null
    return props.cycles.find(c => c.id === props.task.cycle_id) || null
})

const emit = defineEmits(['edit', 'dropAbove', 'taskDragOver'])

const priorityLabels = { low: 'Baja', medium: 'Media', high: 'Alta' }
const priorityLabel = computed(() => priorityLabels[props.task.priority] || 'Media')

const isOverdue = computed(() => {
    if (!props.task.due_date) return false
    return new Date(props.task.due_date) < new Date(new Date().toDateString())
})

// Color palette for chips based on field name hash
const chipColors = [
    '#6366f1', '#8b5cf6', '#a855f7', '#ec4899',
    '#14b8a6', '#06b6d4', '#0ea5e9', '#f59e0b',
    '#10b981', '#f97316', '#ef4444', '#84cc16',
]

function hashColor(str) {
    let hash = 0
    for (let i = 0; i < str.length; i++) hash = str.charCodeAt(i) + ((hash << 5) - hash)
    return chipColors[Math.abs(hash) % chipColors.length]
}

const fieldChips = computed(() => {
    const values = props.task.custom_field_values ?? []
    if (!values.length || !props.customFields.length) return []

    return values
        .map(fv => {
            const field = props.customFields.find(f => f.id === fv.field_id)
            if (!field || !fv.value) return null
            // Only show select-type fields as chips (text/url values are less useful as badges)
            if (field.type !== 'select' && field.type !== 'multi_select') return null
            return {
                fieldId: fv.field_id,
                fieldName: field.name,
                value: fv.value,
                color: hashColor(field.name),
            }
        })
        .filter(Boolean)
})

function formatDue(d) {
    return new Date(d).toLocaleDateString('es-ES', { day: 'numeric', month: 'short' })
}

function onDragOver(e) {
    if (props.drag.dragging.value?.type === 'task') {
        props.drag.onDragOver(e, `task-${props.task.id}`)
        emit('taskDragOver', props.task.id)
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
