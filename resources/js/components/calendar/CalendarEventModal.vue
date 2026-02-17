<template>
    <Teleport to=".layout-root">
        <Transition name="slide-right">
            <aside class="calendar-panel-aside">
                <div class="liquid-glass liquid-glass-panel h-full flex flex-col rounded-[20px]">
                    <!-- Header -->
                    <div class="flex items-center justify-between px-5 py-4 border-b border-white/[0.06]">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-primary-500/10 flex items-center justify-center">
                                <CalendarDaysIcon class="w-4 h-4 text-primary-400" />
                            </div>
                            <h3 class="text-sm font-semibold text-white">
                                {{ event ? 'Editar evento' : 'Nuevo evento' }}
                            </h3>
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
                                    placeholder="Nombre del evento"
                                    required
                                    ref="titleInput"
                                />
                            </div>

                            <div>
                                <label class="form-label">Descripción</label>
                                <textarea
                                    v-model="form.description"
                                    class="form-input"
                                    rows="2"
                                    placeholder="Detalles del evento..."
                                />
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="form-label">Inicio</label>
                                    <input
                                        v-model="form.start_at"
                                        :type="form.all_day ? 'date' : 'datetime-local'"
                                        class="form-input"
                                        required
                                    />
                                </div>
                                <div>
                                    <label class="form-label">Fin</label>
                                    <input
                                        v-model="form.end_at"
                                        :type="form.all_day ? 'date' : 'datetime-local'"
                                        class="form-input"
                                    />
                                </div>
                            </div>

                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" v-model="form.all_day" class="form-checkbox" />
                                <span class="text-sm text-surface-300">Todo el día</span>
                            </label>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="form-label">Tipo</label>
                                    <select v-model="form.type" class="form-input">
                                        <option value="event">Evento</option>
                                        <option value="meeting">Reunión</option>
                                        <option value="time_block">Bloque de tiempo</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="form-label">Ubicación</label>
                                    <input
                                        v-model="form.location"
                                        class="form-input"
                                        placeholder="Lugar..."
                                    />
                                </div>
                            </div>

                            <div>
                                <label class="form-label">Color</label>
                                <div class="flex gap-2 mt-1">
                                    <button
                                        v-for="c in colorOptions"
                                        :key="c"
                                        type="button"
                                        class="w-7 h-7 rounded-full transition-all"
                                        :class="form.color === c
                                            ? 'ring-2 ring-white ring-offset-2 ring-offset-transparent scale-110'
                                            : 'opacity-60 hover:opacity-100'"
                                        :style="{ backgroundColor: c }"
                                        @click="form.color = c"
                                    />
                                </div>
                            </div>
                        </div>
                    </form>

                    <!-- Footer -->
                    <div class="px-5 py-4 border-t border-white/[0.06] flex justify-end gap-3">
                        <button type="button" class="btn-secondary" @click="$emit('close')">
                            Cancelar
                        </button>
                        <button @click="handleSubmit" class="btn-primary" :disabled="saving">
                            {{ event ? 'Guardar' : 'Crear evento' }}
                        </button>
                    </div>
                </div>
            </aside>
        </Transition>
    </Teleport>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useCreateCalendarEvent, useUpdateCalendarEvent } from '@/composables/useCalendar'
import { CalendarDaysIcon, XMarkIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    event: { type: Object, default: null },
    initialDate: { type: String, default: null },
})

const emit = defineEmits(['close', 'saved'])

const colorOptions = ['#6366f1', '#60a5fa', '#10b981', '#f59e0b', '#f43f5e', '#8b5cf6', '#ec4899']

const defaultStart = () => {
    if (props.initialDate) return props.initialDate
    const now = new Date()
    return now.getFullYear() + '-' +
        String(now.getMonth() + 1).padStart(2, '0') + '-' +
        String(now.getDate()).padStart(2, '0') + 'T' +
        String(now.getHours()).padStart(2, '0') + ':00'
}

const form = ref({
    title: props.event?.title ?? '',
    description: props.event?.description ?? '',
    start_at: props.event?.start_at ?? defaultStart(),
    end_at: props.event?.end_at ?? '',
    all_day: props.event?.all_day ?? false,
    type: props.event?.type ?? 'event',
    location: props.event?.location ?? '',
    color: props.event?.color ?? '#6366f1',
})

const saving = ref(false)
const titleInput = ref(null)

const createEvent = useCreateCalendarEvent()
const updateEvent = useUpdateCalendarEvent()

onMounted(() => {
    titleInput.value?.focus()
})

async function handleSubmit() {
    if (!form.value.title || saving.value) return
    saving.value = true
    try {
        const data = { ...form.value }
        if (!data.end_at) data.end_at = null

        if (props.event?.meta?.native_id) {
            await updateEvent.mutateAsync({ id: props.event.meta.native_id, ...data })
        } else {
            await createEvent.mutateAsync(data)
        }
        emit('saved')
    } finally {
        saving.value = false
    }
}
</script>
