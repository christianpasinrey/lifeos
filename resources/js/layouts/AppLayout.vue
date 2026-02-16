<template>
    <div class="flex h-screen bg-surface-950">
        <!-- Sidebar -->
        <aside class="w-64 bg-surface-900 border-r border-surface-800 flex flex-col shrink-0">
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
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors"
                    :class="[
                        $route.path === item.to || $route.path.startsWith(item.to + '/')
                            ? 'bg-primary-500/10 text-primary-400'
                            : 'text-surface-400 hover:bg-surface-800 hover:text-surface-200'
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
                    class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors"
                    :class="showChat
                        ? 'bg-primary-500/10 text-primary-400'
                        : 'text-surface-400 hover:bg-surface-800 hover:text-surface-200'"
                >
                    <ChatBubbleLeftRightIcon class="w-5 h-5" />
                    Coach IA
                </button>
            </div>

            <!-- User -->
            <div class="p-4 border-t border-surface-800">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-primary-500/20 flex items-center justify-center">
                        <span class="text-sm font-medium text-primary-400">
                            {{ auth.user?.name?.charAt(0) }}
                        </span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-surface-200 truncate">
                            {{ auth.user?.name }}
                        </p>
                    </div>
                    <button
                        @click="handleLogout"
                        class="p-1.5 text-surface-400 hover:text-danger-400 rounded-lg hover:bg-surface-800 transition-colors"
                        title="Cerrar sesión"
                    >
                        <ArrowRightStartOnRectangleIcon class="w-5 h-5" />
                    </button>
                </div>
            </div>
        </aside>

        <!-- Main content -->
        <main class="flex-1 overflow-y-auto">
            <router-view />
        </main>

        <!-- Chat panel (slide from right) -->
        <aside
            v-if="showChat"
            class="w-96 bg-surface-900 border-l border-surface-800 flex flex-col shrink-0"
        >
            <ChatPanel @close="showChat = false" />
        </aside>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
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

async function handleLogout() {
    await auth.logout()
    router.push({ name: 'login' })
}
</script>
