<template>
    <div class="board-cycles-tab">
        <!-- Header -->
        <div class="cycles-header">
            <div class="cycles-summary">
                <span class="cycles-summary-chip">
                    <ArrowPathIcon class="w-3.5 h-3.5" />
                    {{ cycles.length }} {{ cycles.length === 1 ? 'cycle' : 'cycles' }}
                </span>
                <span v-if="activeCount" class="cycles-summary-chip cycles-summary-chip-active">
                    <BoltIcon class="w-3.5 h-3.5" />
                    {{ activeCount }} {{ activeCount === 1 ? 'activo' : 'activos' }}
                </span>
                <span v-if="completedCount" class="cycles-summary-chip cycles-summary-chip-completed">
                    <CheckCircleIcon class="w-3.5 h-3.5" />
                    {{ completedCount }} {{ completedCount === 1 ? 'completado' : 'completados' }}
                </span>
            </div>
            <button class="btn-add" @click="creating = true">
                <PlusIcon class="w-4 h-4" />
                Cycle
            </button>
        </div>

        <!-- Empty -->
        <div v-if="!cycles.length" class="liquid-glass liquid-glass-panel cycles-empty">
            <ArrowPathIcon class="w-7 h-7 text-surface-600 mx-auto mb-2" />
            <p class="text-sm text-surface-400">Aún no hay cycles en este tablero.</p>
            <p class="text-xs text-surface-600 mt-1">
                Crea uno para agrupar tareas por sprint, milestone o release.
            </p>
        </div>

        <!-- Grid -->
        <div v-else class="cycles-grid">
            <article
                v-for="cycle in cycles"
                :key="cycle.id"
                class="cycle-card liquid-glass liquid-glass-card"
                @click="editing = cycle"
            >
                <!-- Title row -->
                <header class="cycle-card-header">
                    <div class="cycle-card-title">
                        <span class="cycle-card-dot" :style="{ background: cycle.color }" />
                        <span class="cycle-card-name">{{ cycle.name }}</span>
                    </div>
                    <span class="cycle-status-chip" :style="statusChipStyle(cycle)">
                        <component :is="statusIcon(cycle.status)" class="w-3 h-3" />
                        {{ statusLabel(cycle.status) }}
                    </span>
                </header>

                <!-- Description -->
                <p v-if="cycle.description" class="cycle-card-description">
                    {{ cycle.description }}
                </p>

                <!-- Meta row -->
                <footer class="cycle-card-footer">
                    <span class="cycle-meta-chip" :class="{ 'cycle-meta-chip-dim': !cycle.starts_on && !cycle.ends_on }">
                        <CalendarIcon class="w-3 h-3" />
                        <template v-if="cycle.starts_on || cycle.ends_on">
                            {{ formatDate(cycle.starts_on) }} → {{ formatDate(cycle.ends_on) }}
                        </template>
                        <template v-else>Sin fechas</template>
                    </span>
                    <span class="cycle-meta-chip cycle-meta-chip-count">
                        <ClipboardDocumentListIcon class="w-3 h-3" />
                        {{ cycle.tasks_count ?? 0 }} {{ (cycle.tasks_count ?? 0) === 1 ? 'tarea' : 'tareas' }}
                    </span>
                </footer>
            </article>
        </div>

        <!-- Create modal -->
        <CycleModal
            v-if="creating"
            :board-id="boardId"
            @close="creating = false"
            @saved="creating = false"
        />

        <!-- Edit modal -->
        <CycleModal
            v-if="editing"
            :board-id="boardId"
            :cycle="editing"
            @close="editing = null"
            @saved="editing = null"
        />
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import {
    ArrowPathIcon,
    BoltIcon,
    CheckCircleIcon,
    ClockIcon,
    PlusIcon,
    CalendarIcon,
    ClipboardDocumentListIcon,
} from '@heroicons/vue/24/outline'
import CycleModal from './CycleModal.vue'

const props = defineProps({
    boardId: { type: Number, required: true },
    cycles: { type: Array, default: () => [] },
})

const creating = ref(false)
const editing = ref(null)

const activeCount = computed(() => props.cycles.filter(c => c.status === 'active').length)
const completedCount = computed(() => props.cycles.filter(c => c.status === 'completed').length)

function statusIcon(s) {
    return { planned: ClockIcon, active: BoltIcon, completed: CheckCircleIcon }[s] || ClockIcon
}

function statusLabel(s) {
    return { planned: 'Planificado', active: 'Activo', completed: 'Completado' }[s] || s
}

function statusChipStyle(cycle) {
    const colors = {
        planned: ['rgba(148, 163, 184, 0.15)', 'rgba(148, 163, 184, 0.25)', 'rgb(203, 213, 225)'],
        active: ['rgba(16, 185, 129, 0.15)', 'rgba(16, 185, 129, 0.3)', 'rgb(110, 231, 183)'],
        completed: ['rgba(99, 102, 241, 0.15)', 'rgba(99, 102, 241, 0.3)', 'rgb(165, 180, 252)'],
    }
    const [bg, border, color] = colors[cycle.status] || colors.planned
    return { backgroundColor: bg, border: `1px solid ${border}`, color }
}

function formatDate(d) {
    if (!d) return '?'
    const date = new Date(d)
    return date.toLocaleDateString('es-ES', { day: 'numeric', month: 'short' })
}
</script>

<style scoped>
.board-cycles-tab {
    padding: 0.25rem 0 1rem;
}

.cycles-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1.25rem;
}

.cycles-summary {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.cycles-summary-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.375rem 0.75rem;
    border-radius: 9999px;
    border: 1px solid rgba(255, 255, 255, 0.08);
    background-color: rgba(255, 255, 255, 0.04);
    font-size: 11px;
    font-weight: 500;
    color: rgb(203, 213, 225);
}
.cycles-summary-chip-active {
    border-color: rgba(16, 185, 129, 0.3);
    background-color: rgba(16, 185, 129, 0.1);
    color: rgb(110, 231, 183);
}
.cycles-summary-chip-completed {
    border-color: rgba(99, 102, 241, 0.3);
    background-color: rgba(99, 102, 241, 0.1);
    color: rgb(165, 180, 252);
}

.cycles-empty {
    border-radius: 1rem;
    padding: 2.5rem 1.5rem;
    text-align: center;
}

.cycles-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 0.875rem;
}

.cycle-card {
    padding: 1rem 1.125rem;
    display: flex;
    flex-direction: column;
    gap: 0.625rem;
    cursor: pointer;
    transition: transform 0.15s ease, border-color 0.15s ease;
}
.cycle-card:hover {
    transform: translateY(-1px);
}

.cycle-card-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.75rem;
}

.cycle-card-title {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    min-width: 0;
    flex: 1;
}

.cycle-card-dot {
    width: 0.625rem;
    height: 0.625rem;
    border-radius: 9999px;
    flex-shrink: 0;
    box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.04);
}

.cycle-card-name {
    font-size: 0.9375rem;
    font-weight: 600;
    color: rgb(241, 245, 249);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.cycle-status-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.25rem 0.5rem;
    border-radius: 9999px;
    font-size: 10.5px;
    font-weight: 600;
    flex-shrink: 0;
    line-height: 1;
}

.cycle-card-description {
    font-size: 12.5px;
    color: rgb(148, 163, 184);
    line-height: 1.5;
    display: -webkit-box;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
    line-clamp: 2;
    overflow: hidden;
}

.cycle-card-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    margin-top: 0.25rem;
    padding-top: 0.625rem;
    border-top: 1px solid rgba(255, 255, 255, 0.05);
}

.cycle-meta-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.3125rem;
    padding: 0.25rem 0.5rem;
    border-radius: 0.375rem;
    background-color: rgba(255, 255, 255, 0.04);
    font-size: 10.5px;
    font-weight: 500;
    color: rgb(203, 213, 225);
    line-height: 1;
}
.cycle-meta-chip-dim {
    color: rgb(100, 116, 139);
}
.cycle-meta-chip-count {
    color: rgb(226, 232, 240);
    background-color: rgba(255, 255, 255, 0.06);
}
</style>
