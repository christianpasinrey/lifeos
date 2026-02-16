<template>
    <div class="relative min-h-screen flex items-center justify-center p-4">
        <!-- Wallpaper -->
        <div class="fixed inset-0 z-0">
            <img src="/wallpaper.png" alt="" class="w-full h-full object-cover select-none" draggable="false" />
            <div class="absolute inset-0 wallpaper-overlay" />
        </div>

        <div class="relative z-10 w-full max-w-sm">
            <!-- Logo -->
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-white tracking-tight">
                    <span class="text-primary-400">life</span>OS
                </h1>
                <p class="mt-2 text-surface-400 text-sm">Tu hub de gestión personal</p>
            </div>

            <!-- Form -->
            <form @submit.prevent="handleLogin" class="liquid-glass liquid-glass-panel p-6 space-y-4" style="--glass-radius: 20px;">
                <div>
                    <label class="block text-sm font-medium text-surface-300 mb-1.5">Email</label>
                    <input
                        v-model="email"
                        type="email"
                        required
                        autofocus
                        class="w-full px-3.5 py-2.5 bg-white/[0.05] border border-white/[0.08] rounded-lg text-surface-100 placeholder-surface-500 focus:outline-none focus:ring-2 focus:ring-primary-500/50 focus:border-transparent backdrop-blur-sm transition"
                        placeholder="tu@email.com"
                    />
                </div>

                <div>
                    <label class="block text-sm font-medium text-surface-300 mb-1.5">Contraseña</label>
                    <input
                        v-model="password"
                        type="password"
                        required
                        class="w-full px-3.5 py-2.5 bg-white/[0.05] border border-white/[0.08] rounded-lg text-surface-100 placeholder-surface-500 focus:outline-none focus:ring-2 focus:ring-primary-500/50 focus:border-transparent backdrop-blur-sm transition"
                        placeholder="••••••••"
                    />
                </div>

                <p v-if="error" class="text-danger-400 text-sm">{{ error }}</p>

                <button
                    type="submit"
                    :disabled="loading"
                    class="w-full py-2.5 px-4 bg-primary-600/80 hover:bg-primary-500/80 disabled:opacity-50 text-white font-medium rounded-lg transition-colors backdrop-blur-sm"
                >
                    {{ loading ? 'Entrando...' : 'Entrar' }}
                </button>
            </form>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const auth = useAuthStore()

const email = ref('chris@lifeos.test')
const password = ref('password')
const error = ref('')
const loading = ref(false)

async function handleLogin() {
    loading.value = true
    error.value = ''
    try {
        await auth.login(email.value, password.value)
        router.push({ name: 'dashboard' })
    } catch (e) {
        error.value = e.response?.data?.message || 'Error al iniciar sesión'
    } finally {
        loading.value = false
    }
}
</script>
