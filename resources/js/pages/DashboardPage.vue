<template>
    <div class="p-8">
        <div class="mb-8">
            <h1 class="page-title">Buenos días, {{ auth.user?.name }} 👋</h1>
            <p class="page-subtitle">{{ formattedDate }}</p>
        </div>

        <!-- Quick Stats -->
        <div class="dashboard-grid">
            <div v-if="auth.hasModule('habits')" class="liquid-glass liquid-glass-card glass-card">
                <div class="flex items-center gap-3 mb-3">
                    <div class="stat-icon-wrap bg-primary-500/10">
                        <SparklesIcon class="w-5 h-5 text-primary-400" />
                    </div>
                    <span class="stat-label">Hábitos hoy</span>
                </div>
                <p class="stat-value">
                    {{ todayStats.completed }}/{{ todayStats.total }}
                </p>
                <div class="mt-2 progress-track">
                    <div
                        class="progress-fill"
                        :style="{ width: todayStats.percentage + '%' }"
                    />
                </div>
            </div>

            <div v-if="auth.hasModule('habits')" class="liquid-glass liquid-glass-card glass-card">
                <div class="flex items-center gap-3 mb-3">
                    <div class="stat-icon-wrap bg-accent-500/10">
                        <FireIcon class="w-5 h-5 text-accent-400" />
                    </div>
                    <span class="stat-label">Mejor racha</span>
                </div>
                <p class="stat-value">{{ todayStats.bestStreak }} días</p>
            </div>

            <div v-if="auth.hasModule('ai_coach')" class="liquid-glass liquid-glass-card glass-card">
                <div class="flex items-center gap-3 mb-3">
                    <div class="stat-icon-wrap bg-accent-500/10">
                        <ChatBubbleLeftRightIcon class="w-5 h-5 text-accent-400" />
                    </div>
                    <span class="stat-label">Coach AI</span>
                </div>
                <p class="text-sm text-surface-300">Pregúntale al coach sobre tus hábitos</p>
            </div>
        </div>

        <!-- Today's habits preview -->
        <div v-if="auth.hasModule('habits')" class="liquid-glass liquid-glass-card p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="section-title">Hábitos de hoy</h2>
                <router-link to="/habits" class="text-sm text-primary-400 hover:text-primary-300 transition-colors">
                    Ver todos →
                </router-link>
            </div>
            <div v-if="habitsLoading" class="text-surface-400 text-sm">Cargando...</div>
            <div v-else-if="habits.length === 0" class="text-surface-400 text-sm">
                No tienes hábitos aún. <router-link to="/habits" class="text-primary-400">Crea el primero</router-link>
            </div>
            <div v-else class="habits-list">
                <div
                    v-for="habit in habits.slice(0, 5)"
                    :key="habit.id"
                    class="flex items-center gap-3 p-3 rounded-lg hover:bg-white/[0.06] transition-colors"
                >
                    <button
                        v-if="habit.type === 'boolean'"
                        @click="toggleHabit(habit)"
                        class="w-6 h-6 rounded-md border-2 flex items-center justify-center transition-all"
                        :class="habit.completed_today
                            ? 'bg-accent-500 border-accent-500'
                            : 'border-surface-600 hover:border-primary-400'"
                    >
                        <CheckIcon v-if="habit.completed_today" class="w-4 h-4 text-white" />
                    </button>
                    <div
                        v-else
                        class="w-6 h-6 rounded-md flex items-center justify-center text-xs font-bold"
                        :style="{ backgroundColor: habit.color + '20', color: habit.color }"
                    >
                        #
                    </div>
                    <span class="flex-1 text-sm" :class="habit.completed_today ? 'habit-name-done' : 'text-surface-200'">
                        {{ habit.name }}
                    </span>
                    <span v-if="habit.current_streak > 0" class="habit-streak">
                        🔥 {{ habit.current_streak }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useHabitsToday, useToggleHabit } from '@/composables/useHabits'
import {
    SparklesIcon,
    FireIcon,
    CheckIcon,
    ChatBubbleLeftRightIcon,
} from '@heroicons/vue/24/outline'

const auth = useAuthStore()

const formattedDate = computed(() => {
    return new Date().toLocaleDateString('es-ES', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    })
})

const hasHabits = computed(() => auth.hasModule('habits'))
const { data: habitsData, isLoading: habitsLoading } = useHabitsToday(hasHabits)
const { mutate: toggle } = useToggleHabit()

const habits = computed(() => habitsData.value?.data ?? [])

const todayStats = computed(() => {
    const list = habits.value
    const total = list.length
    const completed = list.filter(h => h.completed_today).length
    const bestStreak = list.reduce((max, h) => Math.max(max, h.best_streak || 0), 0)
    return {
        total,
        completed,
        percentage: total > 0 ? Math.round((completed / total) * 100) : 0,
        bestStreak,
    }
})

function toggleHabit(habit) {
    toggle({ habitId: habit.id })
}
</script>
