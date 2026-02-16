<template>
    <div class="relative min-h-screen">
        <!-- Wallpaper -->
        <div class="layout-wallpaper">
            <img
                src="/wallpaper.png"
                alt=""
                class="w-full h-full object-cover select-none"
                draggable="false"
            />
            <div class="absolute inset-0 wallpaper-overlay" />
        </div>

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

                <!-- Coach button -->
                <div class="px-3 pb-2">
                    <button
                        @click="showChat = !showChat"
                        class="w-full nav-link"
                        :class="showChat ? 'nav-link-active' : ''"
                    >
                        <ChatBubbleLeftRightIcon class="w-5 h-5" />
                        Coach IA
                    </button>
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

            <!-- Chat panel -->
            <Transition name="slide-right">
                <aside
                    v-if="showChat"
                    class="chat-aside liquid-glass liquid-glass-panel"
                >
                    <ChatPanel @close="showChat = false" />
                </aside>
            </Transition>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import ChatPanel from '@/components/ai/ChatPanel.vue'
import {
    HomeIcon,
    SparklesIcon,
    ListBulletIcon,
    BanknotesIcon,
    UsersIcon,
    ChatBubbleLeftRightIcon,
    ArrowRightStartOnRectangleIcon,
} from '@heroicons/vue/24/outline'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const showChat = ref(false)

const navItems = [
    { to: '/', label: 'Dashboard', icon: HomeIcon },
    { to: '/habits', label: 'Hábitos', icon: SparklesIcon },
    { to: '/tasks', label: 'Tareas', icon: ListBulletIcon },
    { to: '/finances', label: 'Finanzas', icon: BanknotesIcon },
    { to: '/contacts', label: 'Contactos', icon: UsersIcon },
]

function isActive(item) {
    if (item.to === '/') return route.path === '/'
    return route.path === item.to || route.path.startsWith(item.to + '/')
}

async function handleLogout() {
    await auth.logout()
    router.push({ name: 'login' })
}
</script>
