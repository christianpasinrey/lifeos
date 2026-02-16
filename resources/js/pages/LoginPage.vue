<template>
    <div class="min-h-screen bg-surface-950 flex items-center justify-center p-4">
        <div class="w-full max-w-sm">
            <!-- Logo -->
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-white tracking-tight">
                    <span class="text-primary-400">life</span>OS
                </h1>
                <p class="mt-2 text-surface-400 text-sm">Tu hub de gestión personal</p>
            </div>

            <!-- Form -->
            <form @submit.prevent="handleLogin" class="bg-surface-900 rounded-2xl p-6 border border-surface-800 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-surface-300 mb-1.5">Email</label>
                    <input
                        v-model="email"
                        type="email"
                        required
                        autofocus
                        class="w-full px-3.5 py-2.5 bg-surface-800 border border-surface-700 rounded-lg text-surface-100 placeholder-surface-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition"
                        placeholder="tu@email.com"
                    />
                </div>

                <div>
                    <label class="block text-sm font-medium text-surface-300 mb-1.5">Contraseña</label>
                    <input
                        v-model="password"
                        type="password"
                        required
                        class="w-full px-3.5 py-2.5 bg-surface-800 border border-surface-700 rounded-lg text-surface-100 placeholder-surface-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition"
                        placeholder="••••••••"
                    />
                </div>

                <p v-if="error" class="text-danger-400 text-sm">{{ error }}</p>

                <button
                    type="submit"
                    :disabled="loading"
                    class="w-full py-2.5 px-4 bg-primary-600 hover:bg-primary-500 disabled:opacity-50 text-white font-medium rounded-lg transition-colors"
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
