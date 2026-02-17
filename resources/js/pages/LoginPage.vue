<template>
    <div class="relative login-container">
        <div class="login-box">
            <!-- Logo -->
            <div class="login-logo">
                <h1 class="text-3xl font-bold text-white tracking-tight">
                    <span class="text-primary-400">life</span>OS
                </h1>
                <p class="mt-2 text-surface-400 text-sm">Tu hub de gestión personal</p>
            </div>

            <!-- Form -->
            <form @submit.prevent="handleLogin" class="liquid-glass liquid-glass-panel login-form" style="--glass-radius: 20px;">
                <div>
                    <label class="form-label">Email</label>
                    <input
                        v-model="email"
                        type="email"
                        required
                        autofocus
                        class="form-input"
                        placeholder="tu@email.com"
                    />
                </div>

                <div>
                    <label class="form-label">Contraseña</label>
                    <input
                        v-model="password"
                        type="password"
                        required
                        class="form-input"
                        placeholder="••••••••"
                    />
                </div>

                <p v-if="error" class="form-error">{{ error }}</p>

                <button
                    type="submit"
                    :disabled="loading"
                    class="w-full btn-primary"
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
