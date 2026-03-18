<template>
    <div class="layout-root">
        <!-- Layout shell -->
        <div class="layout-shell">
            <!-- Floating Sidebar -->
            <aside class="sidebar liquid-glass liquid-glass-sidebar">
                <!-- Logo -->
                <div class="sidebar-brand">
                    <h1 class="text-xl font-bold text-white tracking-tight">
                        <span class="text-primary-400">life</span>OS
                    </h1>
                </div>

                <!-- Navigation -->
                <nav class="sidebar-nav">
                    <router-link
                        :to="'/'"
                        class="nav-link"
                        :class="[isActive({ to: '/' }) ? 'nav-link-active' : '']"
                    >
                        <HomeIcon class="w-5 h-5" />
                        Dashboard
                    </router-link>
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

                <!-- Sidebar widgets (e.g. Coach button) -->
                <template v-for="widget in sidebarWidgets" :key="widget.order">
                    <component :is="widget.component" />
                </template>

                <!-- User -->
                <div class="sidebar-footer">
                    <div class="flex items-center gap-3">
                        <router-link to="/profile" class="glass-avatar" title="Perfil">
                            {{ auth.user?.name?.charAt(0) }}
                        </router-link>
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
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useModuleRegistry } from '@/modules/registry'
import {
    HomeIcon,
    ArrowRightStartOnRectangleIcon,
} from '@heroicons/vue/24/outline'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const { navItems, sidebarWidgets } = useModuleRegistry()

function isActive(item) {
    if (item.to === '/') return route.path === '/'
    return route.path === item.to || route.path.startsWith(item.to + '/')
}

async function handleLogout() {
    await auth.logout()
    router.push({ name: 'login' })
}
</script>
