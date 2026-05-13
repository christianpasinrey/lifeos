<template>
    <div class="board-page">
        <!-- Header -->
        <div class="board-header">
            <div class="flex items-center gap-3 flex-1 min-w-0">
                <router-link :to="{ name: 'boards' }" class="text-surface-400 hover:text-surface-200 transition-colors">
                    <ArrowLeftIcon class="w-5 h-5" />
                </router-link>
                <h2 class="text-2xl font-bold text-surface-100 truncate">{{ board?.name }}</h2>
                <button class="btn-icon" @click="showEditBoard = true" title="Editar tablero">
                    <PencilIcon class="w-4 h-4" />
                </button>
                <button class="btn-icon" @click="showBoardSettings = true" title="Configuración del tablero">
                    <Cog6ToothIcon class="w-4 h-4" />
                </button>
            </div>
        </div>

        <!-- Board tag bar -->
        <div v-if="board" class="board-tag-bar mt-2 mb-4">
            <TagPicker
                :target-type="'board'"
                :target-id="boardId"
                :board-id="boardId"
                :modelValue="board.tags || []"
            />
        </div>

        <!-- Tabs -->
        <div class="board-tabs flex items-center gap-1 mb-4 border-b border-white/[0.06]">
            <button
                v-for="t in tabs"
                :key="t.id"
                class="board-tab"
                :class="{ 'board-tab-active': activeTab === t.id }"
                @click="activeTab = t.id"
            >
                <component :is="t.icon" class="w-3.5 h-3.5" />
                {{ t.label }}
                <span v-if="t.count !== null" class="board-tab-count">{{ t.count }}</span>
            </button>
        </div>

        <!-- Loading -->
        <div v-if="isLoading" class="flex justify-center py-20">
            <div class="animate-spin w-8 h-8 border-2 border-primary-400 border-t-transparent rounded-full" />
        </div>

        <!-- Board tab content -->
        <template v-else-if="activeTab === 'board'">
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
                    <div v-if="cycles.length" class="toolbar-stat">
                        <ArrowPathIcon class="w-3.5 h-3.5" />
                        <span class="stat-value">{{ cycles.length }}</span> cycles
                    </div>
                    <div v-if="highPriorityCount" class="toolbar-stat" style="color: var(--color-danger-400); border-color: rgba(244, 63, 94, 0.15);">
                        <ExclamationTriangleIcon class="w-3.5 h-3.5" />
                        <span class="stat-value" style="color: var(--color-danger-300);">{{ highPriorityCount }}</span> urgentes
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center gap-2">
                    <!-- Cycle filter -->
                    <div v-if="cycles.length" class="board-cycle-filter">
                        <CustomSelect
                            :modelValue="cycleFilter ?? '__all__'"
                            :options="cycleFilterOptions"
                            @update:modelValue="setCycleFilter"
                        />
                    </div>
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
                        v-for="column in filteredColumns"
                        :key="column.id"
                        :column="column"
                        :board-id="boardId"
                        :drag="drag"
                        :custom-fields="customFields"
                        :cycles="cycles"
                        @edit-column="editColumn"
                        @add-task="addTask"
                        @edit-task="editTask"
                        @drop-task="handleDropTask"
                        @drop-column="handleDropColumn"
                    />
                </div>
            </div>
        </template>

        <!-- Cycles tab content -->
        <template v-else-if="activeTab === 'cycles'">
            <BoardCyclesTab :board-id="boardId" :cycles="cycles" />
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
import BoardCyclesTab from '@/components/tasks/BoardCyclesTab.vue'
import TagPicker from '@/components/tasks/TagPicker.vue'
import CustomSelect from '@/components/ui/CustomSelect.vue'
import {
    ArrowLeftIcon,
    PlusIcon,
    PencilIcon,
    Cog6ToothIcon,
    ViewColumnsIcon,
    ClipboardDocumentListIcon,
    ExclamationTriangleIcon,
    ArrowPathIcon,
    Squares2X2Icon,
} from '@heroicons/vue/24/outline'
import { useModuleRegistry } from '@/modules/registry'

const route = useRoute()
const boardId = computed(() => Number(route.params.id))

const { data, isLoading } = useBoard(boardId)
const board = computed(() => data.value?.data ?? null)
const columns = computed(() => board.value?.columns ?? [])
const customFields = computed(() => board.value?.custom_fields ?? [])
const cycles = computed(() => board.value?.cycles ?? [])
const allTasks = computed(() => columns.value.flatMap(c => c.tasks ?? []))
const totalTasks = computed(() => allTasks.value.length)
const highPriorityCount = computed(() => allTasks.value.filter(t => t.priority === 'high').length)

const activeTab = ref('board')
const cycleFilter = ref(null) // null = all, 0 = no cycle, N = cycle id

const cycleFilterOptions = computed(() => [
    { value: '__all__', label: 'Todos los cycles' },
    { value: 0, label: 'Sin cycle' },
    ...cycles.value.map(c => ({ value: c.id, label: c.name })),
])

function setCycleFilter(val) {
    cycleFilter.value = val === '__all__' ? null : Number(val)
}

const filteredColumns = computed(() => {
    if (cycleFilter.value === null) return columns.value
    return columns.value.map(col => ({
        ...col,
        tasks: (col.tasks ?? []).filter(t => {
            if (cycleFilter.value === 0) return !t.cycle_id
            return t.cycle_id === cycleFilter.value
        }),
    }))
})

const tabs = computed(() => [
    { id: 'board', label: 'Tablero', icon: Squares2X2Icon, count: null },
    { id: 'cycles', label: 'Cycles', icon: ArrowPathIcon, count: cycles.value.length || null },
])

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

<style scoped>
.board-tabs { padding-left: 0; }
.board-tab {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.5rem 0.75rem;
    font-size: 0.75rem;
    font-weight: 500;
    color: rgb(148 163 184);
    transition: color 0.15s;
    border-bottom: 2px solid transparent;
    cursor: pointer;
    background: transparent;
}
.board-tab:hover { color: rgb(203 213 225); }
.board-tab-active { color: white; border-color: rgb(96 165 250); }
.board-tab-count {
    margin-left: 0.25rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 18px;
    height: 18px;
    padding: 0 0.25rem;
    border-radius: 9999px;
    background-color: rgba(255, 255, 255, 0.1);
    font-size: 10px;
    color: rgb(203 213 225);
}
.board-cycle-filter { min-width: 180px; }
</style>
