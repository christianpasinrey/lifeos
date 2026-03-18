<template>
    <div class="p-8 max-w-2xl mx-auto">
        <div class="mb-8">
            <h1 class="page-title">Perfil</h1>
            <p class="page-subtitle">Gestiona tu cuenta y tokens de acceso</p>
        </div>

        <!-- User Info Section -->
        <div class="liquid-glass liquid-glass-card p-6 mb-6">
            <h2 class="text-lg font-semibold text-surface-200 mb-4">Información</h2>

            <form @submit.prevent="handleUpdateProfile" class="space-y-4">
                <div>
                    <label class="form-label">Nombre</label>
                    <input v-model="name" type="text" class="form-input" required />
                </div>
                <div>
                    <label class="form-label">Email</label>
                    <input v-model="email" type="email" class="form-input" required />
                </div>
                <p v-if="profileError" class="form-error">{{ profileError }}</p>
                <p v-if="profileSuccess" class="text-sm text-green-400">{{ profileSuccess }}</p>
                <button type="submit" :disabled="updateProfile.isPending.value" class="btn-primary">
                    {{ updateProfile.isPending.value ? 'Guardando...' : 'Guardar' }}
                </button>
            </form>
        </div>

        <!-- MCP Token Section -->
        <div class="liquid-glass liquid-glass-card p-6">
            <h2 class="text-lg font-semibold text-surface-200 mb-2">Token MCP</h2>
            <p class="text-sm text-surface-400 mb-4">
                Usa este token para conectar Claude Code o Claude Desktop con tu cuenta de LifeOS.
            </p>

            <!-- Token just generated -->
            <div v-if="newToken" class="space-y-3">
                <div class="p-3 rounded-lg bg-yellow-500/10 border border-yellow-500/20">
                    <p class="text-sm text-yellow-300 mb-2">Este token no se mostrará de nuevo. Cópialo ahora.</p>
                    <div class="flex gap-2">
                        <input
                            :value="newToken"
                            readonly
                            class="form-input flex-1 font-mono text-xs"
                        />
                        <button @click="copyToken" class="btn-secondary text-sm whitespace-nowrap">
                            {{ copied ? 'Copiado' : 'Copiar' }}
                        </button>
                    </div>
                </div>

                <div class="p-3 rounded-lg bg-surface-800/50">
                    <p class="text-xs text-surface-400 mb-1">Configuración para Claude Code (<code>.claude/settings.json</code>):</p>
                    <pre class="text-xs text-surface-300 overflow-x-auto">{{ claudeCodeConfig }}</pre>
                </div>
            </div>

            <!-- Token exists but not shown -->
            <div v-else-if="tokenStatus?.has_token" class="space-y-3">
                <p class="text-sm text-surface-300">
                    Token activo — creado el {{ formatDate(tokenStatus.created_at) }}
                </p>
                <div class="flex gap-3">
                    <button @click="handleGenerate" :disabled="generateToken.isPending.value" class="btn-primary text-sm">
                        Regenerar token
                    </button>
                    <button @click="handleRevoke" :disabled="revokeToken.isPending.value" class="btn-secondary text-sm">
                        Revocar
                    </button>
                </div>
            </div>

            <!-- No token -->
            <div v-else>
                <button @click="handleGenerate" :disabled="generateToken.isPending.value" class="btn-primary">
                    Generar Token MCP
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import { useProfile, useUpdateProfile, useMcpTokenStatus, useGenerateMcpToken, useRevokeMcpToken } from '@/composables/useProfile'

const { data: profile } = useProfile()
const updateProfile = useUpdateProfile()
const { data: tokenStatus } = useMcpTokenStatus()
const generateToken = useGenerateMcpToken()
const revokeToken = useRevokeMcpToken()

const name = ref('')
const email = ref('')
const profileError = ref('')
const profileSuccess = ref('')
const newToken = ref(null)
const copied = ref(false)

watch(profile, (p) => {
    if (p?.user) {
        name.value = p.user.name
        email.value = p.user.email
    }
}, { immediate: true })

const claudeCodeConfig = ref('')
watch(newToken, (token) => {
    if (token) {
        claudeCodeConfig.value = JSON.stringify({
            mcpServers: {
                lifeos: {
                    type: 'url',
                    url: 'https://lifeos.tailor-bytes.com/mcp',
                    headers: { Authorization: `Bearer ${token}` },
                },
            },
        }, null, 2)
    }
})

async function handleUpdateProfile() {
    profileError.value = ''
    profileSuccess.value = ''
    try {
        await updateProfile.mutateAsync({ name: name.value, email: email.value })
        profileSuccess.value = 'Perfil actualizado'
    } catch (e) {
        profileError.value = e.response?.data?.message || 'Error al actualizar'
    }
}

async function handleGenerate() {
    try {
        const result = await generateToken.mutateAsync()
        newToken.value = result.token
        copied.value = false
    } catch (e) {
        // handled by mutation
    }
}

async function handleRevoke() {
    newToken.value = null
    await revokeToken.mutateAsync()
}

function copyToken() {
    navigator.clipboard.writeText(newToken.value)
    copied.value = true
    setTimeout(() => { copied.value = false }, 2000)
}

function formatDate(dateStr) {
    if (!dateStr) return ''
    return new Date(dateStr).toLocaleDateString('es-ES', {
        day: 'numeric', month: 'long', year: 'numeric',
    })
}
</script>
