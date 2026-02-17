<template>
    <div class="p-8">
        <div class="mb-8">
            <h1 class="page-title">Panel de Administración</h1>
            <p class="page-subtitle">Vista general del sistema</p>
        </div>

        <div v-if="isLoading" class="text-surface-400 text-sm">Cargando...</div>

        <div v-else class="dashboard-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
            <!-- Total Users -->
            <div class="liquid-glass liquid-glass-card glass-card">
                <div class="flex items-center gap-3 mb-3">
                    <div class="stat-icon-wrap bg-primary-500/10">
                        <UsersIcon class="w-5 h-5 text-primary-400" />
                    </div>
                    <span class="stat-label">Usuarios totales</span>
                </div>
                <p class="stat-value">{{ stats.total_users }}</p>
            </div>

            <!-- Admin Users -->
            <div class="liquid-glass liquid-glass-card glass-card">
                <div class="flex items-center gap-3 mb-3">
                    <div class="stat-icon-wrap bg-accent-500/10">
                        <ShieldCheckIcon class="w-5 h-5 text-accent-400" />
                    </div>
                    <span class="stat-label">Administradores</span>
                </div>
                <p class="stat-value">{{ stats.admin_users }}</p>
            </div>

            <!-- Active Modules -->
            <div class="liquid-glass liquid-glass-card glass-card">
                <div class="flex items-center gap-3 mb-3">
                    <div class="stat-icon-wrap bg-primary-500/10">
                        <CubeIcon class="w-5 h-5 text-primary-400" />
                    </div>
                    <span class="stat-label">Módulos activos</span>
                </div>
                <p class="stat-value">{{ stats.total_modules_active }}</p>
            </div>

            <!-- Premium Users -->
            <div class="liquid-glass liquid-glass-card glass-card">
                <div class="flex items-center gap-3 mb-3">
                    <div class="stat-icon-wrap bg-amber-500/10">
                        <StarIcon class="w-5 h-5 text-amber-400" />
                    </div>
                    <span class="stat-label">Usuarios premium</span>
                </div>
                <p class="stat-value">{{ stats.premium_users }}</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { useAdminStats } from '@/composables/useAdmin'
import {
    UsersIcon,
    ShieldCheckIcon,
    CubeIcon,
    StarIcon,
} from '@heroicons/vue/24/outline'

const { data, isLoading } = useAdminStats()
const stats = computed(() => data.value ?? {})
</script>
