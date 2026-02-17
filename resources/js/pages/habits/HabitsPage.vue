<template>
    <div class="p-8">
        <div class="habits-header">
            <div>
                <h1 class="page-title">Hábitos</h1>
                <p class="page-subtitle">
                    {{ completedCount }}/{{ habits.length }} completados hoy
                </p>
            </div>
            <div class="flex items-center gap-3">
                <button
                    @click="showModal = true; editingHabit = null"
                    class="btn-add"
                >
                    <PlusIcon class="w-4 h-4" />
                    Nuevo hábito
                </button>
            </div>
        </div>

        <!-- Progress bar -->
        <div class="mb-6">
            <div class="progress-lg">
                <div
                    class="progress-fill-gradient"
                    :style="{ width: progressPercentage + '%' }"
                />
            </div>
        </div>

        <!-- AI Analyze button -->
        <div v-if="hasAiAnalyzer && habits.length > 0" class="mb-4">
            <button
                @click="openAiAnalysis"
                class="btn-secondary text-xs"
            >
                <SparklesIcon class="w-4 h-4" />
                Analizar con IA
            </button>
        </div>

        <!-- Loading -->
        <div v-if="isLoading" class="text-center py-12 text-surface-400">Cargando hábitos...</div>

        <!-- Empty state -->
        <div v-else-if="habits.length === 0" class="text-center py-16">
            <SparklesIcon class="w-12 h-12 text-surface-600 mx-auto mb-6" />
            <h3 class="text-xl font-medium text-surface-300 mb-8">Sin hábitos aún</h3>
            <button
                @click="showModal = true"
                class="btn-add"
            >
                Crear primer hábito
            </button>
        </div>

        <!-- Habit list -->
        <div v-else>
            <!-- Grouped by routine -->
            <template v-if="hasRoutines && hasMultipleRoutines">
                <div v-for="group in routineGroups" :key="group.key" class="mb-6">
                    <button
                        @click="toggleRoutineCollapse(group.key)"
                        class="flex items-center gap-2 mb-3 text-sm font-medium text-surface-300 hover:text-surface-100 transition-colors"
                    >
                        <span class="transition-transform" :class="collapsedRoutines[group.key] ? '-rotate-90' : 'rotate-0'">▼</span>
                        {{ group.icon }} {{ group.label }}
                        <span class="text-surface-500">({{ group.habits.length }})</span>
                    </button>
                    <div v-show="!collapsedRoutines[group.key]" class="habits-list">
                        <HabitCard
                            v-for="habit in group.habits"
                            :key="habit.id"
                            :habit="habit"
                            :sparkline="sparklines[habit.id]"
                            @toggle="handleToggle"
                            @update-value="handleUpdateValue"
                            @save-notes="handleSaveNotes"
                            @edit="openEdit"
                            @delete="handleDelete"
                            @stats="goToStats"
                        />
                    </div>
                </div>
            </template>

            <!-- Flat list -->
            <div v-else class="habits-list">
                <HabitCard
                    v-for="habit in habits"
                    :key="habit.id"
                    :habit="habit"
                    :sparkline="sparklines[habit.id]"
                    @toggle="handleToggle"
                    @update-value="handleUpdateValue"
                    @save-notes="handleSaveNotes"
                    @edit="openEdit"
                    @delete="handleDelete"
                    @stats="goToStats"
                />
            </div>
        </div>

        <!-- Badge celebration -->
        <BadgeCelebrationOverlay :badge="pendingBadge" />

        <!-- Modal -->
        <HabitModal
            v-if="showModal"
            :habit="editingHabit"
            @close="showModal = false"
            @saved="showModal = false"
        />

        <!-- AI Analysis Modal -->
        <HabitAnalysisModal
            v-if="showAnalysis"
            @close="showAnalysis = false"
        />
    </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useHabitsToday, useToggleHabit, useHabitSparklines, useDeleteHabit } from '@/composables/useHabits'
import { useHabitFeatures } from '@/composables/useHabitFeatures'
import { useCelebration } from '@/composables/useCelebration'
import HabitCard from '@/components/habits/HabitCard.vue'
import HabitModal from '@/components/habits/HabitModal.vue'
import HabitAnalysisModal from '@/components/habits/HabitAnalysisModal.vue'
import BadgeCelebrationOverlay from '@/components/habits/BadgeCelebrationOverlay.vue'
import { PlusIcon, SparklesIcon } from '@heroicons/vue/24/outline'

const router = useRouter()
const showModal = ref(false)
const showAnalysis = ref(false)
const editingHabit = ref(null)
const { hasRoutines, hasSparklines, hasCelebrations, hasWeeklyView, hasAiAnalyzer } = useHabitFeatures()
const { celebrateHabit, celebrateBadge, pendingBadge } = useCelebration()

const { data: habitsData, isLoading } = useHabitsToday()
const { mutate: toggle } = useToggleHabit()
const { mutate: deleteHabit } = useDeleteHabit()
const { data: sparklinesData } = useHabitSparklines()
const sparklines = computed(() => sparklinesData.value ?? {})

const habits = computed(() => habitsData.value?.data ?? [])
const completedCount = computed(() => habits.value.filter(h => h.completed_today).length)
const progressPercentage = computed(() => {
    if (habits.value.length === 0) return 0
    return Math.round((completedCount.value / habits.value.length) * 100)
})

const routineOrder = [
    { key: 'morning', label: 'Mañana', icon: '🌅' },
    { key: 'afternoon', label: 'Tarde', icon: '☀️' },
    { key: 'evening', label: 'Noche', icon: '🌙' },
    { key: 'anytime', label: 'Cualquier momento', icon: '⏰' },
]

const collapsedRoutines = reactive({})

const hasMultipleRoutines = computed(() => {
    const routines = new Set(habits.value.map(h => h.routine || 'anytime'))
    return routines.size > 1
})

const routineGroups = computed(() => {
    return routineOrder
        .map(r => ({
            ...r,
            habits: habits.value.filter(h => (h.routine || 'anytime') === r.key),
        }))
        .filter(g => g.habits.length > 0)
})

function toggleRoutineCollapse(key) {
    collapsedRoutines[key] = !collapsedRoutines[key]
}

function openAiAnalysis() {
    showAnalysis.value = true
}

function handleToggle(habit, event) {
    toggle({ habitId: habit.id }, {
        onSuccess: (data) => {
            if (hasCelebrations.value && data.completed) {
                const el = event?.target?.closest?.('.habit-card')
                celebrateHabit(el)
                if (data.new_badges?.length) {
                    celebrateBadge(data.new_badges[0])
                }
            }
        },
    })
}

function handleUpdateValue(habit, value) {
    toggle({ habitId: habit.id, value })
}

function handleSaveNotes(habit, notes) {
    toggle({ habitId: habit.id, notes, value: habit.type === 'numeric' ? habit.today_value : undefined })
}

function openEdit(habit) {
    editingHabit.value = habit
    showModal.value = true
}

function handleDelete(habit) {
    deleteHabit(habit.id)
}

function goToStats(habit) {
    router.push({ name: 'habit-stats', params: { id: habit.id } })
}
</script>
