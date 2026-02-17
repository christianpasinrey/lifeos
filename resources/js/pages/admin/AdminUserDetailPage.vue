<template>
    <div class="p-8">
        <!-- Back link -->
        <router-link
            :to="{ name: 'admin-users' }"
            class="inline-flex items-center gap-1 text-sm text-surface-400 hover:text-surface-200 transition-colors mb-6"
        >
            <ArrowLeftIcon class="w-4 h-4" />
            Volver a usuarios
        </router-link>

        <div v-if="isLoading" class="text-surface-400 text-sm">Cargando...</div>

        <template v-else-if="userData">
            <!-- Section 1: Overview -->
            <div class="liquid-glass liquid-glass-card p-6 mb-6">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-full bg-primary-500/20 border border-primary-500/30 flex items-center justify-center text-xl font-bold text-primary-300">
                        {{ userData.user.name?.charAt(0) }}
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <h1 class="text-xl font-bold text-white">{{ userData.user.name }}</h1>
                            <span
                                v-if="userData.user.is_admin"
                                class="px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-primary-300 bg-primary-500/10 border border-primary-500/20 rounded-full"
                            >
                                Admin
                            </span>
                        </div>
                        <p class="text-sm text-surface-400">{{ userData.user.email }}</p>
                        <p class="text-xs text-surface-500 mt-1">Registrado: {{ userData.user.created_at }}</p>
                    </div>
                </div>
            </div>

            <!-- Section 2: Modules -->
            <div class="mb-6">
                <h2 class="section-title mb-4">Módulos</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div
                        v-for="mod in userData.modules"
                        :key="mod.slug"
                        class="liquid-glass liquid-glass-card p-4 transition-all"
                        :class="[
                            mod.is_active
                                ? 'ring-1 ring-primary-500/20'
                                : 'opacity-60'
                        ]"
                    >
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-sm font-semibold text-surface-200">{{ mod.name }}</h3>
                            <button
                                @click="handleToggle(mod.slug)"
                                :disabled="toggling"
                                class="toggle-switch"
                                :class="mod.is_active ? 'toggle-switch-on' : 'toggle-switch-off'"
                            >
                                <span
                                    class="toggle-knob"
                                    :style="{ left: mod.is_active ? '1.625rem' : '0.125rem' }"
                                />
                            </button>
                        </div>
                        <p class="text-xs text-surface-500">{{ mod.description }}</p>
                    </div>
                </div>
            </div>

            <!-- Section 3: Plans & Limits -->
            <div>
                <h2 class="section-title mb-4">Planes y Límites</h2>

                <div v-if="activeModules.length === 0" class="text-surface-500 text-sm">
                    No hay módulos activos para configurar.
                </div>

                <div v-else class="space-y-4">
                    <div
                        v-for="mod in activeModules"
                        :key="mod.slug"
                        class="liquid-glass liquid-glass-card p-5"
                    >
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-sm font-semibold text-surface-200">{{ mod.name }}</h3>
                            <select
                                v-model="planEdits[mod.slug].plan"
                                class="form-input form-input-sm w-auto"
                            >
                                <option value="free">Free</option>
                                <option value="premium">Premium</option>
                                <option value="custom">Custom</option>
                            </select>
                        </div>

                        <!-- Premium: no limits/features needed -->
                        <p v-if="planEdits[mod.slug].plan === 'premium'" class="text-xs text-accent-400">
                            Sin límites — todas las funciones habilitadas.
                        </p>

                        <!-- Free or Custom: show limits -->
                        <template v-else>
                            <!-- Limits -->
                            <div v-if="Object.keys(mod.free_limits).length" class="mb-4">
                                <p class="text-xs text-surface-400 mb-2 uppercase tracking-wider font-medium">Límites</p>
                                <div class="grid grid-cols-2 gap-3">
                                    <div v-for="(val, key) in mod.free_limits" :key="key">
                                        <label class="text-xs text-surface-500 block mb-1">{{ formatLimitKey(key) }}</label>
                                        <input
                                            type="number"
                                            v-model.number="planEdits[mod.slug].limits[key]"
                                            :readonly="planEdits[mod.slug].plan === 'free'"
                                            class="form-input form-input-sm w-full"
                                            :class="{ 'opacity-50 cursor-not-allowed': planEdits[mod.slug].plan === 'free' }"
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- Features -->
                            <div v-if="Object.keys(mod.free_features).length">
                                <p class="text-xs text-surface-400 mb-2 uppercase tracking-wider font-medium">Features</p>
                                <div class="grid grid-cols-2 gap-2">
                                    <label
                                        v-for="(val, key) in mod.free_features"
                                        :key="key"
                                        class="flex items-center gap-2 text-sm text-surface-300"
                                        :class="{ 'opacity-50': planEdits[mod.slug].plan === 'free' }"
                                    >
                                        <input
                                            type="checkbox"
                                            v-model="planEdits[mod.slug].features[key]"
                                            :disabled="planEdits[mod.slug].plan === 'free'"
                                            class="rounded border-surface-600 bg-white/5 text-primary-500 focus:ring-primary-500/30"
                                        />
                                        {{ formatFeatureKey(key) }}
                                    </label>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div class="flex justify-end">
                        <button
                            @click="handleSavePlans"
                            :disabled="saving"
                            class="btn-primary"
                        >
                            {{ saving ? 'Guardando...' : 'Guardar cambios' }}
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>

<script setup>
import { computed, watch, reactive } from 'vue'
import { useAdminUser, useToggleAdminModule, useUpdateAdminPlan } from '@/composables/useAdmin'
import { ArrowLeftIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    id: [String, Number],
})

const userId = computed(() => props.id)
const { data: userData, isLoading } = useAdminUser(userId)
const { mutate: toggleModule, isPending: toggling } = useToggleAdminModule()
const { mutate: updatePlan, isPending: saving } = useUpdateAdminPlan()

const activeModules = computed(() =>
    (userData.value?.modules ?? []).filter(m => m.is_active)
)

// Reactive edits state for plans
const planEdits = reactive({})

watch(
    () => userData.value?.modules,
    (modules) => {
        if (!modules) return
        for (const mod of modules) {
            if (!mod.is_active) continue
            planEdits[mod.slug] = {
                plan: mod.plan || 'free',
                limits: { ...(mod.limits || mod.free_limits) },
                features: { ...(mod.features || mod.free_features) },
            }
        }
    },
    { immediate: true }
)

function handleToggle(slug) {
    toggleModule({ userId: props.id, module: slug })
}

function handleSavePlans() {
    const modules = {}
    for (const mod of activeModules.value) {
        const edit = planEdits[mod.slug]
        if (!edit) continue
        modules[mod.slug] = {
            plan: edit.plan,
            limits: edit.plan === 'custom' ? edit.limits : undefined,
            features: edit.plan === 'custom' ? edit.features : undefined,
        }
    }
    updatePlan({ userId: props.id, modules })
}

function formatLimitKey(key) {
    return key.replace(/^max_/, '').replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase())
}

function formatFeatureKey(key) {
    return key.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase())
}
</script>
