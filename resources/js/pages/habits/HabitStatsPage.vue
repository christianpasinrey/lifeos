<template>
    <div class="p-8">
        <div class="mb-6">
            <button @click="$router.back()" class="back-link">
                <ArrowLeftIcon class="w-4 h-4" /> Volver
            </button>
            <h1 class="page-title" v-if="stats">{{ stats.habit?.name }}</h1>
        </div>

        <div v-if="isLoading" class="text-center py-12 text-surface-400">Cargando estadísticas...</div>

        <template v-else-if="stats">
            <!-- Streak cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                <div class="liquid-glass liquid-glass-card stat-card">
                    <p class="stat-value-primary">{{ stats.current_streak }}</p>
                    <p class="stat-label mt-1">Racha actual</p>
                </div>
                <div class="liquid-glass liquid-glass-card stat-card">
                    <p class="text-3xl font-bold text-accent-400">{{ stats.best_streak }}</p>
                    <p class="stat-label mt-1">Mejor racha</p>
                </div>
                <div class="liquid-glass liquid-glass-card stat-card">
                    <p class="stat-value">{{ stats.rate_7 }}%</p>
                    <p class="stat-label mt-1">Última semana</p>
                </div>
                <div class="liquid-glass liquid-glass-card stat-card">
                    <p class="stat-value">{{ stats.rate_30 }}%</p>
                    <p class="stat-label mt-1">Último mes</p>
                </div>
            </div>

            <!-- Numeric stats (if applicable) -->
            <div v-if="stats.trend" class="grid grid-cols-3 gap-4 mb-8">
                <div class="liquid-glass liquid-glass-card stat-card">
                    <p class="stat-value">{{ stats.average }}</p>
                    <p class="stat-label mt-1">Promedio</p>
                </div>
                <div class="liquid-glass liquid-glass-card stat-card">
                    <p class="text-2xl font-bold text-accent-400">{{ stats.max }}</p>
                    <p class="stat-label mt-1">Máximo</p>
                </div>
                <div class="liquid-glass liquid-glass-card stat-card">
                    <p class="text-2xl font-bold text-danger-400">{{ stats.min }}</p>
                    <p class="stat-label mt-1">Mínimo</p>
                </div>
            </div>

            <!-- Heatmap -->
            <div class="liquid-glass liquid-glass-card p-6">
                <h2 class="section-title mb-4">Actividad</h2>
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
