import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/composables/useApi'

export const useAuthStore = defineStore('auth', () => {
    const user = ref(null)
    const checked = ref(false)

    const isAuthenticated = computed(() => !!user.value)
    const modules = computed(() => user.value?.modules ?? {})

    function hasModule(slug) {
        return slug in modules.value
    }

    function getModuleLimit(slug, key) {
        const mod = modules.value[slug]
        if (!mod) return undefined
        if (mod.plan === 'premium') return null // sin límite
        return mod.limits?.[key] ?? null
    }

    async function fetchUser() {
        try {
            const { data } = await api.get('/me')
            user.value = data
        } catch {
            user.value = null
        } finally {
            checked.value = true
        }
    }

    async function login(email, password) {
        await api.get('/sanctum/csrf-cookie', { baseURL: '/' })
        const { data } = await api.post('/login', { email, password })
        user.value = data
    }

    async function logout() {
        await api.post('/logout')
        user.value = null
    }

    return { user, checked, isAuthenticated, modules, hasModule, getModuleLimit, fetchUser, login, logout }
})
