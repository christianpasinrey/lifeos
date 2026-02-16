<template>
    <div class="p-8">
        <div class="mb-6">
            <button @click="$router.back()" class="text-surface-400 hover:text-surface-200 text-sm mb-2 inline-flex items-center gap-1 transition-colors">
                <ArrowLeftIcon class="w-4 h-4" /> Volver
            </button>
            <h1 class="text-2xl font-bold text-white" v-if="stats">{{ stats.habit?.name }}</h1>
        </div>

        <div v-if="isLoading" class="text-center py-12 text-surface-400">Cargando estadísticas...</div>

        <template v-else-if="stats">
            <!-- Streak cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                <div class="liquid-glass liquid-glass-card p-4 text-center">
                    <p class="text-3xl font-bold text-primary-400">{{ stats.current_streak }}</p>
                    <p class="text-sm text-surface-400 mt-1">Racha actual</p>
                </div>
                <div class="liquid-glass liquid-glass-card p-4 text-center">
                    <p class="text-3xl font-bold text-accent-400">{{ stats.best_streak }}</p>
                    <p class="text-sm text-surface-400 mt-1">Mejor racha</p>
                </div>
                <div class="liquid-glass liquid-glass-card p-4 text-center">
                    <p class="text-3xl font-bold text-white">{{ stats.rate_7 }}%</p>
                    <p class="text-sm text-surface-400 mt-1">Última semana</p>
                </div>
                <div class="liquid-glass liquid-glass-card p-4 text-center">
                    <p class="text-3xl font-bold text-white">{{ stats.rate_30 }}%</p>
                    <p class="text-sm text-surface-400 mt-1">Último mes</p>
                </div>
            </div>

            <!-- Numeric stats (if applicable) -->
            <div v-if="stats.trend" class="grid grid-cols-3 gap-4 mb-8">
                <div class="liquid-glass liquid-glass-card p-4 text-center">
                    <p class="text-2xl font-bold text-white">{{ stats.average }}</p>
                    <p class="text-sm text-surface-400 mt-1">Promedio</p>
                </div>
                <div class="liquid-glass liquid-glass-card p-4 text-center">
                    <p class="text-2xl font-bold text-accent-400">{{ stats.max }}</p>
                    <p class="text-sm text-surface-400 mt-1">Máximo</p>
                </div>
                <div class="liquid-glass liquid-glass-card p-4 text-center">
                    <p class="text-2xl font-bold text-danger-400">{{ stats.min }}</p>
                    <p class="text-sm text-surface-400 mt-1">Mínimo</p>
                </div>
            </div>

            <!-- Heatmap -->
            <div class="liquid-glass liquid-glass-card p-6">
                <h2 class="text-lg font-semibold text-white mb-4">Actividad</h2>
                <HeatmapCalendar :data="stats.calendar" :color="stats.habit?.color" />
            </div>
        </template>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useHabitStats } from '@/composables/useHabits'
import HeatmapCalendar from '@/components/habits/HeatmapCalendar.vue'
import { ArrowLeftIcon } from '@heroicons/vue/24/outline'

const route = useRoute()
const habitId = computed(() => route.params.id)
const { data: stats, isLoading } = useHabitStats(habitId)
</script>
