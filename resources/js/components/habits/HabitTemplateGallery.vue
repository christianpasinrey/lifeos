<template>
    <Teleport to="body">
        <div class="modal-overlay">
            <div class="modal-backdrop" @click="$emit('close')" />
            <div class="modal-content liquid-glass liquid-glass-panel" style="--glass-radius: 20px; max-height: 80vh; overflow-y: auto;">
                <h2 class="section-title mb-5">Plantillas de hábitos</h2>

                <div v-if="isLoading" class="text-center py-8 text-surface-400">Cargando plantillas...</div>

                <div v-else>
                    <div v-for="(templates, category) in categories" :key="category" class="mb-6">
                        <h3 class="text-sm font-semibold text-surface-300 mb-3">{{ category }}</h3>
                        <div class="grid grid-cols-1 gap-2">
                            <button
                                v-for="tpl in templates"
                                :key="tpl.id"
                                @click="handleApply(tpl)"
                                :disabled="applying"
                                class="flex items-center gap-3 p-3 rounded-lg border border-white/[0.08] hover:border-white/[0.15] hover:bg-white/[0.04] transition-all text-left"
                            >
                                <span class="text-lg">{{ tpl.icon }}</span>
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm font-medium text-surface-200">{{ tpl.name }}</div>
                                    <div v-if="tpl.description" class="text-xs text-surface-400 truncate">{{ tpl.description }}</div>
                                </div>
                                <span class="text-xs text-surface-500">
                                    {{ tpl.type === 'numeric' ? `${tpl.target_value} ${tpl.unit}` : 'Sí/No' }}
                                </span>
                                <div class="w-2 h-2 rounded-full" :style="{ backgroundColor: tpl.color }" />
                            </button>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end pt-3">
                    <button @click="$emit('close')" class="btn-secondary">Cerrar</button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useHabitTemplates, useApplyTemplate } from '@/composables/useHabits'

const emit = defineEmits(['close', 'applied'])

const { data: templatesData, isLoading } = useHabitTemplates()
const { mutateAsync: applyTemplate } = useApplyTemplate()
const applying = ref(false)

const categories = computed(() => templatesData.value ?? {})

async function handleApply(template) {
    applying.value = true
    try {
        await applyTemplate(template.id)
        emit('applied')
    } catch (e) {
        console.error('Error applying template:', e)
    } finally {
        applying.value = false
    }
}
</script>
