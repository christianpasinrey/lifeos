<template>
    <div class="board-cycles-tab">
        <!-- Header -->
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2 text-sm text-surface-400">
                <ArrowPathIcon class="w-4 h-4" />
                <span>{{ cycles.length }} {{ cycles.length === 1 ? 'cycle' : 'cycles' }}</span>
            </div>
            <button class="btn-add" @click="creating = true">
                <PlusIcon class="w-4 h-4" />
                Cycle
            </button>
        </div>

        <!-- Empty -->
        <div v-if="!cycles.length" class="liquid-glass liquid-glass-panel rounded-xl p-8 text-center text-sm text-surface-500">
            Aún no hay cycles en este tablero. Crea uno para empezar a agrupar tareas por sprint, milestone o release.
        </div>

        <!-- Grid -->
        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
            <div
                v-for="cycle in cycles"
                :key="cycle.id"
                class="cycle-card liquid-glass liquid-glass-card cursor-pointer hover:scale-[1.01] transition-transform"
                @click="editing = cycle"
            >
                <div class="flex items-start justify-between gap-2">
                    <div class="flex items-center gap-2 min-w-0">
                        <span class="w-2.5 h-2.5 rounded-full flex-shrink-0" :style="{ background: cycle.color }" />
                        <span class="text-sm font-semibold text-surface-100 truncate">{{ cycle.name }}</span>
                    </div>
                    <span class="text-[10px] px-1.5 py-0.5 rounded-full" :class="statusBadge(cycle.status)">
                        {{ statusLabel(cycle.status) }}
                    </span>
                </div>
                <p v-if="cycle.description" class="text-xs text-surface-400 mt-2 line-clamp-2">{{ cycle.description }}</p>
                <div class="flex items-center justify-between mt-3 text-[11px] text-surface-500">
                    <span v-if="cycle.starts_on || cycle.ends_on">
                        {{ cycle.starts_on || '?' }} → {{ cycle.ends_on || '?' }}
                    </span>
                    <span v-else>Sin fechas</span>
                    <span class="font-medium text-surface-300">{{ cycle.tasks_count ?? 0 }} tareas</span>
                </div>
            </div>
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
import { ref } from 'vue'
import { ArrowPathIcon, PlusIcon } from '@heroicons/vue/24/outline'
import CycleModal from './CycleModal.vue'

defineProps({
    boardId: { type: Number, required: true },
    cycles: { type: Array, default: () => [] },
})

const creating = ref(false)
const editing = ref(null)

function statusBadge(s) {
    return {
        planned: 'bg-surface-500/15 text-surface-300',
        active: 'bg-emerald-500/15 text-emerald-300',
        completed: 'bg-indigo-500/15 text-indigo-300',
    }[s] || 'bg-surface-500/15 text-surface-300'
}
function statusLabel(s) {
    return { planned: 'Planificado', active: 'Activo', completed: 'Completado' }[s] || s
}
</script>
