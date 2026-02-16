<template>
    <div class="relative min-h-screen">
        <!-- Wallpaper -->
        <div class="fixed inset-0 z-0">
            <img
                src="/wallpaper.png"
                alt=""
                class="w-full h-full object-cover select-none"
                draggable="false"
            />
            <div class="absolute inset-0 wallpaper-overlay" />
        </div>

        <!-- Layout shell -->
        <div class="relative z-10 flex h-screen p-4 gap-4">
            <!-- Floating Sidebar -->
            <aside class="w-64 shrink-0 liquid-glass liquid-glass-sidebar flex flex-col">
                <!-- Logo -->
                <div class="p-6">
                    <h1 class="text-xl font-bold text-white tracking-tight">
                        <span class="text-primary-400">life</span>OS
                    </h1>
                </div>

                <!-- Navigation -->
                <nav class="flex-1 px-3 space-y-1">
                    <router-link
                        v-for="item in navItems"
                        :key="item.to"
                        :to="item.to"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200"
                        :class="[
                            isActive(item)
                                ? 'bg-primary-500/15 text-primary-400 shadow-[inset_0_0_12px_rgba(99,102,241,0.06)]'
                                : 'text-surface-400 hover:bg-white/[0.06] hover:text-surface-200'
                        ]"
                    >
                        <component :is="item.icon" class="w-5 h-5" />
                        {{ item.label }}
                    </router-link>
                </nav>

                <!-- Coach button -->
                <div class="px-3 pb-2">
                    <button
                        @click="showChat = !showChat"
                        class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200"
                        :class="showChat
                            ? 'bg-primary-500/15 text-primary-400 shadow-[inset_0_0_12px_rgba(99,102,241,0.06)]'
                            : 'text-surface-400 hover:bg-white/[0.06] hover:text-surface-200'"
                    >
                        <ChatBubbleLeftRightIcon class="w-5 h-5" />
                        Coach IA
                    </button>
                </div>

                <!-- User -->
                <div class="p-4 border-t border-white/[0.06]">
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
            <main class="flex-1 overflow-y-auto glass-scroll rounded-2xl">
                <router-view />
            </main>

            <!-- Chat panel -->
            <Transition name="slide-right">
                <aside
                    v-if="showChat"
                    class="w-96 shrink-0 liquid-glass liquid-glass-panel flex flex-col"
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
