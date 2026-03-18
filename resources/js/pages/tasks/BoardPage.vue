<template>
    <div class="board-page">
        <!-- Header -->
        <div class="board-header">
            <div class="flex items-center gap-3">
                <router-link :to="{ name: 'boards' }" class="text-surface-400 hover:text-surface-200 transition-colors">
                    <ArrowLeftIcon class="w-5 h-5" />
                </router-link>
                <h2 class="text-2xl font-bold text-surface-100">{{ board?.name }}</h2>
                <button class="btn-icon" @click="showEditBoard = true" title="Editar tablero">
                    <PencilIcon class="w-4 h-4" />
                </button>
                <button class="btn-icon" @click="showBoardSettings = true" title="Configuración del tablero">
                    <Cog6ToothIcon class="w-4 h-4" />
                </button>
            </div>
        </div>

        <!-- Loading -->
        <div v-if="isLoading" class="flex justify-center py-20">
            <div class="animate-spin w-8 h-8 border-2 border-primary-400 border-t-transparent rounded-full" />
        </div>

        <!-- Toolbar + Kanban -->
        <template v-else>
            <div class="kanban-toolbar">
                <!-- Stats -->
                <div class="flex items-center gap-2">
                    <div class="toolbar-stat">
                        <ViewColumnsIcon class="w-3.5 h-3.5" />
                        <span class="stat-value">{{ columns.length }}</span> columnas
                    </div>
                    <div class="toolbar-stat">
                        <ClipboardDocumentListIcon class="w-3.5 h-3.5" />
                        <span class="stat-value">{{ totalTasks }}</span> tareas
                    </div>
                    <div v-if="highPriorityCount" class="toolbar-stat" style="color: var(--color-danger-400); border-color: rgba(244, 63, 94, 0.15);">
                        <ExclamationTriangleIcon class="w-3.5 h-3.5" />
                        <span class="stat-value" style="color: var(--color-danger-300);">{{ highPriorityCount }}</span> urgentes
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center gap-2">
                    <template v-for="action in toolbarActions" :key="action.id || action.label">
                        <component v-if="action.component" :is="action.component" :board-id="boardId" :columns="columns" />
                        <button v-else class="btn-add" @click="onSlotAction(action)">
                            <component :is="action.icon" class="w-4 h-4" />
                            {{ action.label }}
                        </button>
                    </template>
                    <button class="btn-add" @click="showCreateColumn = true">
                        <PlusIcon class="w-4 h-4" />
                        Columna
                    </button>
                </div>
            </div>

            <div class="kanban-scroll glass-scroll">
                <div class="kanban-track">
                    <KanbanColumn
                        v-for="column in columns"
                        :key="column.id"
                        :column="column"
                        :board-id="boardId"
                        :drag="drag"
                        @edit-column="editColumn"
                        @add-task="addTask"
                        @edit-task="editTask"
                        @drop-task="handleDropTask"
                        @drop-column="handleDropColumn"
                    />
                </div>
            </div>
        </template>

        <!-- Modals -->
        <BoardModal
            v-if="showEditBoard"
            :board="board"
            @close="showEditBoard = false"
            @saved="showEditBoard = false"
        />
        <ColumnModal
            v-if="showCreateColumn"
            :board-id="boardId"
            @close="showCreateColumn = false"
            @saved="showCreateColumn = false"
        />
        <ColumnModal
            v-if="editingColumn"
            :board-id="boardId"
            :column="editingColumn"
            @close="editingColumn = null"
            @saved="editingColumn = null"
        />
        <TaskModal
            v-if="creatingTaskColumnId"
            :board-id="boardId"
            :column-id="creatingTaskColumnId"
            @close="creatingTaskColumnId = null"
            @saved="creatingTaskColumnId = null"
        />
        <TaskDetailPanel
            v-if="viewingTaskId"
            :task-id="viewingTaskId"
            :board-id="boardId"
            :columns="columns"
            @close="viewingTaskId = null"
        />
        <BoardSettingsModal
            v-if="showBoardSettings"
            :board="board"
            :board-id="boardId"
            @close="showBoardSettings = false"
        />
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRoute } from 'vue-router'
import { useBoard, useMoveTask, useReorderColumns } from '@/composables/useTasks'
import { useDrag } from '@/composables/useDrag'
import KanbanColumn from '@/components/tasks/KanbanColumn.vue'
import BoardModal from '@/components/tasks/BoardModal.vue'
import ColumnModal from '@/components/tasks/ColumnModal.vue'
import TaskModal from '@/components/tasks/TaskModal.vue'
import TaskDetailPanel from '@/components/tasks/TaskDetailPanel.vue'
import BoardSettingsModal from '@/components/tasks/BoardSettingsModal.vue'
import {
    ArrowLeftIcon,
    PlusIcon,
    PencilIcon,
    Cog6ToothIcon,
    ViewColumnsIcon,
    ClipboardDocumentListIcon,
    ExclamationTriangleIcon,
} from '@heroicons/vue/24/outline'
import { useModuleRegistry } from '@/modules/registry'

const route = useRoute()
const boardId = computed(() => Number(route.params.id))

const { data, isLoading } = useBoard(boardId)
const board = computed(() => data.value?.data ?? null)
const columns = computed(() => board.value?.columns ?? [])
const allTasks = computed(() => columns.value.flatMap(c => c.tasks ?? []))
const totalTasks = computed(() => allTasks.value.length)
const highPriorityCount = computed(() => allTasks.value.filter(t => t.priority === 'high').length)

const { actionsForSlot } = useModuleRegistry()
const toolbarActions = actionsForSlot('board-toolbar')
const drag = useDrag()
const moveTask = useMoveTask()
const reorderCols = useReorderColumns()

const showEditBoard = ref(false)
const showCreateColumn = ref(false)
const editingColumn = ref(null)
const creatingTaskColumnId = ref(null)
const viewingTaskId = ref(null)
const showBoardSettings = ref(false)

function editColumn(col) {
    editingColumn.value = col
}

function addTask(columnId) {
    creatingTaskColumnId.value = columnId
}

function editTask(task, columnId) {
    viewingTaskId.value = task.id
}

function handleDropTask(payload, targetColumnId, targetIndex) {
    if (payload.type !== 'task') return
    moveTask.mutate({
        id: payload.id,
        boardId: boardId.value,
        column_id: targetColumnId,
        sort_order: targetIndex,
    })
}

function handleDropColumn(payload, targetIndex) {
    if (payload.type !== 'column') return
    const newOrder = columns.value
        .map(c => c.id)
        .filter(id => id !== payload.id)
    newOrder.splice(targetIndex, 0, payload.id)
    reorderCols.mutate({ boardId: boardId.value, order: newOrder })
}

function onSlotAction(action) {
    if (action.emit) {
        window.dispatchEvent(new CustomEvent(action.emit))
    }
}
</script>
