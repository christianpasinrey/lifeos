<template>
    <div class="p-8">
        <div class="habits-header">
            <div>
                <h1 class="page-title">Hábitos</h1>
                <p class="page-subtitle">
                    {{ completedCount }}/{{ habits.length }} completados hoy
                </p>
            </div>
            <button
                @click="showModal = true; editingHabit = null"
                class="btn-add"
            >
                <PlusIcon class="w-4 h-4" />
                Nuevo hábito
            </button>
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

        <!-- Loading -->
        <div v-if="isLoading" class="text-center py-12 text-surface-400">Cargando hábitos...</div>

        <!-- Empty state -->
        <div v-else-if="habits.length === 0" class="text-center py-16">
            <SparklesIcon class="w-12 h-12 text-surface-600 mx-auto mb-4" />
            <h3 class="text-lg font-medium text-surface-300 mb-2">Sin hábitos aún</h3>
            <p class="text-surface-500 mb-6">Crea tu primer hábito o dile al coach qué quieres mejorar</p>
            <button
                @click="showModal = true"
                class="btn-add"
            >
                Crear primer hábito
            </button>
        </div>

        <!-- Habit list -->
        <div v-else class="habits-list">
            <HabitCard
                v-for="habit in habits"
                :key="habit.id"
                :habit="habit"
                @toggle="handleToggle"
                @update-value="handleUpdateValue"
                @edit="openEdit"
                @stats="goToStats"
            />
        </div>

        <!-- Modal -->
        <HabitModal
            v-if="showModal"
            :habit="editingHabit"
            @close="showModal = false"
            @saved="showModal = false"
        />
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useHabitsToday, useToggleHabit } from '@/composables/useHabits'
import HabitCard from '@/components/habits/HabitCard.vue'
import HabitModal from '@/components/habits/HabitModal.vue'
import { PlusIcon, SparklesIcon } from '@heroicons/vue/24/outline'

const router = useRouter()
const showModal = ref(false)
const editingHabit = ref(null)

const { data: habitsData, isLoading } = useHabitsToday()
const { mutate: toggle } = useToggleHabit()

const habits = computed(() => habitsData.value?.data ?? [])
const completedCount = computed(() => habits.value.filter(h => h.completed_today).length)
const progressPercentage = computed(() => {
    if (habits.value.length === 0) return 0
    return Math.round((completedCount.value / habits.value.length) * 100)
})

function handleToggle(habit) {
    toggle({ habitId: habit.id })
}

function handleUpdateValue(habit, value) {
    toggle({ habitId: habit.id, value })
}

function openEdit(habit) {
    editingHabit.value = habit
    showModal.value = true
}

function goToStats(habit) {
    router.push({ name: 'habit-stats', params: { id: habit.id } })
}
</script>
