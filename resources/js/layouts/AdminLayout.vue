<template>
    <div class="layout-root">
        <div class="layout-shell">
            <!-- Floating Sidebar -->
            <aside class="sidebar liquid-glass liquid-glass-sidebar">
                <!-- Logo -->
                <div class="sidebar-brand">
                    <h1 class="text-xl font-bold text-white tracking-tight">
                        <span class="text-primary-400">life</span>OS
                    </h1>
                    <span class="ml-2 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-primary-300 bg-primary-500/10 border border-primary-500/20 rounded-full">
                        admin
                    </span>
                </div>

                <!-- Navigation -->
                <nav class="sidebar-nav">
                    <router-link
                        v-for="item in navItems"
                        :key="item.to"
                        :to="item.to"
                        class="nav-link"
                        :class="[isActive(item) ? 'nav-link-active' : '']"
                    >
                        <component :is="item.icon" class="w-5 h-5" />
                        {{ item.label }}
                    </router-link>
                </nav>

                <!-- Back to app -->
                <div class="px-3 pb-2">
                    <a href="/" class="w-full nav-link">
                        <ArrowLeftIcon class="w-5 h-5" />
                        Volver a la app
                    </a>
                </div>

                <!-- User -->
                <div class="sidebar-footer">
                    <div class="flex items-center gap-3">
                        <div class="glass-avatar">
                            {{ auth.user?.name?.charAt(0) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-surface-200 truncate">
                                {{ auth.user?.name }}
                            </p>
                        </div>
                        <button
                            @click="handleLogout"
                            class="p-1.5 text-surface-400 hover:text-danger-400 rounded-lg hover:bg-white/[0.06] transition-colors"
                            title="Cerrar sesión"
                        >
                            <ArrowRightStartOnRectangleIcon class="w-5 h-5" />
                        </button>
                    </div>
                </div>
            </aside>

            <!-- Main content -->
            <main class="main-content glass-scroll">
                <router-view />
            </main>
        </div>
    </div>
</template>

<script setup>
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import {
    HomeIcon,
    UsersIcon,
    ArrowLeftIcon,
    ArrowRightStartOnRectangleIcon,
} from '@heroicons/vue/24/outline'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

const navItems = [
    { to: '/admin', label: 'Dashboard', icon: HomeIcon, exact: true },
    { to: '/admin/users', label: 'Usuarios', icon: UsersIcon },
]

function isActive(item) {
    if (item.exact) return route.path === item.to
    return route.path === item.to || route.path.startsWith(item.to + '/')
}

async function handleLogout() {
    await auth.logout()
    router.push({ name: 'login' })
}
</script>
