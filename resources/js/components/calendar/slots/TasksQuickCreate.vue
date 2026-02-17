<template>
    <Teleport to=".layout-root">
        <Transition name="slide-right">
            <aside class="calendar-panel-aside">
                <div class="liquid-glass liquid-glass-panel h-full flex flex-col rounded-[20px]">
                    <!-- Header -->
                    <div class="flex items-center justify-between px-5 py-4 border-b border-white/[0.06]">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-blue-500/10 flex items-center justify-center">
                                <ClipboardDocumentListIcon class="w-4 h-4 text-blue-400" />
                            </div>
                            <h3 class="text-sm font-semibold text-white">Nueva tarea</h3>
                        </div>
                        <button @click="$emit('close')" class="btn-close">
                            <XMarkIcon class="w-5 h-5" />
                        </button>
                    </div>

                    <!-- Form -->
                    <form @submit.prevent="handleSubmit" class="flex-1 overflow-y-auto px-5 py-4 glass-scroll">
                        <div class="space-y-4">
                            <div>
                                <label class="form-label">Título</label>
                                <input
                                    v-model="form.title"
                                    class="form-input"
                                    placeholder="Nombre de la tarea"
                                    required
                                    ref="titleInput"
                                />
                            </div>

                            <div>
                                <label class="form-label">Prioridad</label>
                                <CustomSelect v-model="form.priority" :options="priorityOptions" />
                            </div>

                            <div>
                                <label class="form-label">Fecha</label>
                                <input
                                    :value="date"
                                    class="form-input"
                                    type="date"
                                    disabled
                                />
                            </div>
                        </div>
                    </form>

                    <!-- Footer -->
                    <div class="px-5 py-4 border-t border-white/[0.06] flex justify-end gap-3">
                        <button type="button" class="btn-secondary" @click="$emit('close')">
                            Cancelar
                        </button>
                        <button @click="handleSubmit" class="btn-primary" :disabled="saving">
                            Crear tarea
                        </button>
                    </div>
                </div>
            </aside>
        </Transition>
    </Teleport>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useMutation, useQueryClient } from '@tanstack/vue-query'
import api from '@/composables/useApi'
import CustomSelect from '@/components/ui/CustomSelect.vue'
import { ClipboardDocumentListIcon, XMarkIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    date: { type: String, required: true },
})

const emit = defineEmits(['close', 'saved'])

const priorityOptions = [
    { value: 'medium', label: 'Media' },
    { value: 'low', label: 'Baja' },
    { value: 'high', label: 'Alta' },
    { value: 'urgent', label: 'Urgente' },
]

const form = ref({
    title: '',
    priority: 'medium',
})

const saving = ref(false)
const titleInput = ref(null)
const queryClient = useQueryClient()

const createTask = useMutation({
    mutationFn: (data) => api.post('/tasks/quick', data).then(r => r.data),
    onSuccess: () => {
        queryClient.invalidateQueries({ queryKey: ['calendar', 'source'] })
    },
})

onMounted(() => {
    titleInput.value?.focus()
})

async function handleSubmit() {
    if (!form.value.title || saving.value) return
    saving.value = true
    try {
        await createTask.mutateAsync({
            title: form.value.title,
            due_date: props.date,
            priority: form.value.priority,
        })
        emit('saved')
        emit('close')
    } finally {
        saving.value = false
    }
}
</script>
