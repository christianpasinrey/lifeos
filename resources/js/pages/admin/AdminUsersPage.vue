<template>
    <div class="p-8">
        <div class="mb-8">
            <h1 class="page-title">Usuarios</h1>
            <p class="page-subtitle">Gestión de usuarios y módulos</p>
        </div>

        <div class="liquid-glass liquid-glass-card p-6">
            <div v-if="isLoading" class="text-surface-400 text-sm">Cargando...</div>

            <table v-else class="w-full">
                <thead>
                    <tr class="border-b border-white/[0.06]">
                        <th class="text-left py-3 px-4 text-xs font-medium text-surface-400 uppercase tracking-wider">Usuario</th>
                        <th class="text-left py-3 px-4 text-xs font-medium text-surface-400 uppercase tracking-wider">Email</th>
                        <th class="text-left py-3 px-4 text-xs font-medium text-surface-400 uppercase tracking-wider">Rol</th>
                        <th class="text-left py-3 px-4 text-xs font-medium text-surface-400 uppercase tracking-wider">Módulos</th>
                        <th class="text-right py-3 px-4 text-xs font-medium text-surface-400 uppercase tracking-wider">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="user in users"
                        :key="user.id"
                        class="border-b border-white/[0.04] hover:bg-white/[0.03] transition-colors"
                    >
                        <td class="py-3 px-4">
                            <div class="flex items-center gap-3">
                                <div class="glass-avatar text-sm">
                                    {{ user.name?.charAt(0) }}
                                </div>
                                <span class="text-sm text-surface-200 font-medium">{{ user.name }}</span>
                            </div>
                        </td>
                        <td class="py-3 px-4 text-sm text-surface-400">{{ user.email }}</td>
                        <td class="py-3 px-4">
                            <span
                                v-if="user.is_admin"
                                class="inline-flex items-center px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-primary-300 bg-primary-500/10 border border-primary-500/20 rounded-full"
                            >
                                Admin
                            </span>
                            <span v-else class="text-sm text-surface-500">Usuario</span>
                        </td>
                        <td class="py-3 px-4 text-sm text-surface-300">
                            {{ user.active_modules_count }} activos
                        </td>
                        <td class="py-3 px-4 text-right">
                            <router-link
                                :to="{ name: 'admin-user-detail', params: { id: user.id } }"
                                class="text-sm text-primary-400 hover:text-primary-300 transition-colors"
                            >
                                Configurar →
                            </router-link>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { useAdminUsers } from '@/composables/useAdmin'

const { data, isLoading } = useAdminUsers()
const users = computed(() => data.value ?? [])
</script>
