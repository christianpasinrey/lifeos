<template>
    <div>
        <div class="boards-header">
            <h2 class="text-2xl font-bold text-surface-100">Tableros</h2>
            <button class="btn-add" @click="showCreate = true">
                <PlusIcon class="w-4 h-4" />
                Nuevo tablero
            </button>
        </div>

        <!-- Loading -->
        <div v-if="isLoading" class="flex justify-center py-20">
            <div class="animate-spin w-8 h-8 border-2 border-primary-400 border-t-transparent rounded-full" />
        </div>

        <!-- Empty state -->
        <div v-else-if="!boards?.length" class="text-center py-20">
            <ClipboardDocumentListIcon class="w-16 h-16 mx-auto text-surface-600 mb-4" />
            <p class="text-surface-400 text-lg">No tienes tableros aún</p>
            <p class="text-surface-500 text-sm mt-1">Crea uno para empezar a organizar tus tareas</p>
        </div>

        <!-- Board grid -->
        <div v-else class="boards-grid">
            <router-link
                v-for="board in boards"
                :key="board.id"
                :to="{ name: 'board', params: { id: board.id } }"
                class="board-card liquid-glass liquid-glass-card"
            >
                <div>
                    <h3 class="text-lg font-semibold text-surface-100">{{ board.name }}</h3>
                    <p v-if="board.description" class="text-sm text-surface-400 mt-2 line-clamp-2">
                        {{ board.description }}
                    </p>
                </div>

                <div class="board-card-stats">
                    <span class="board-card-stat">
                        <ViewColumnsIcon class="w-3.5 h-3.5" />
                        {{ board.columns_count ?? 0 }}
                    </span>
                    <span class="board-card-stat">
                        <ClipboardDocumentListIcon class="w-3.5 h-3.5" />
                        {{ board.tasks_count ?? 0 }} tareas
                    </span>
                    <span class="board-card-stat ml-auto">
                        <CalendarIcon class="w-3.5 h-3.5" />
                        {{ formatDate(board.created_at) }}
                    </span>
                </div>
            </router-link>
        </div>

        <!-- Create modal -->
        <BoardModal
            v-if="showCreate"
            @close="showCreate = false"
            @saved="showCreate = false"
        />
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useBoards } from '@/composables/useTasks'
import BoardModal from '@/components/tasks/BoardModal.vue'
import {
    PlusIcon,
    ClipboardDocumentListIcon,
    ViewColumnsIcon,
    CalendarIcon,
} from '@heroicons/vue/24/outline'

const showCreate = ref(false)
const { data, isLoading } = useBoards()

const boards = computed(() => data.value?.data ?? [])

function formatDate(d) {
    return new Date(d).toLocaleDateString('es-ES', { day: 'numeric', month: 'short' })
}
</script>
