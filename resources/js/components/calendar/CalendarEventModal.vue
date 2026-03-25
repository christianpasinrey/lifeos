<template>
    <Teleport to=".layout-root">
        <Transition name="modal-fade">
            <div class="calendar-event-modal-overlay" @click.self="$emit('close')">
                <div class="calendar-event-modal liquid-glass liquid-glass-panel rounded-[20px]">
                    <!-- Header -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-white/[0.06]">
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
                    <form @submit.prevent="handleSubmit" class="flex-1 overflow-y-auto px-6 py-5 glass-scroll">
                        <div class="grid grid-cols-2 gap-x-6 gap-y-4">
                            <!-- Title — full width -->
                            <div class="col-span-2">
                                <label class="form-label">Título</label>
                                <input
                                    v-model="form.title"
                                    class="form-input"
                                    placeholder="Nombre del evento"
                                    required
                                    ref="titleInput"
                                />
                            </div>

                            <!-- Description — full width -->
                            <div class="col-span-2">
                                <label class="form-label">Descripción</label>
                                <textarea
                                    v-model="form.description"
                                    class="form-input"
                                    rows="2"
                                    placeholder="Detalles del evento..."
                                />
                            </div>

                            <!-- Start date -->
                            <div>
                                <label class="form-label">Inicio</label>
                                <input
                                    v-model="form.start_at"
                                    :type="form.all_day ? 'date' : 'datetime-local'"
                                    class="form-input"
                                    required
                                />
                            </div>

                            <!-- End date (optional with toggle) -->
                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <label class="form-label mb-0">Fin</label>
                                    <label class="flex items-center gap-1.5 cursor-pointer">
                                        <span class="text-xs text-surface-500">Hora de fin</span>
                                        <button
                                            type="button"
                                            @click="showEndDate = !showEndDate"
                                            class="relative w-8 h-[18px] rounded-full transition-colors"
                                            :class="showEndDate ? 'bg-primary-500' : 'bg-white/10'"
                                        >
                                            <span
                                                class="absolute top-0.5 left-0.5 w-[14px] h-[14px] rounded-full bg-white transition-transform"
                                                :class="showEndDate ? 'translate-x-[14px]' : ''"
                                            />
                                        </button>
                                    </label>
                                </div>
                                <input
                                    v-if="showEndDate"
                                    v-model="form.end_at"
                                    :type="form.all_day ? 'date' : 'datetime-local'"
                                    class="form-input"
                                />
                                <div v-else class="form-input opacity-40 cursor-not-allowed select-none text-surface-500">
                                    Sin hora de fin
                                </div>
                            </div>

                            <!-- All day checkbox -->
                            <div class="col-span-2">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" v-model="form.all_day" class="form-checkbox" />
                                    <span class="text-sm text-surface-300">Todo el día</span>
                                </label>
                            </div>

                            <!-- Type -->
                            <div>
                                <label class="form-label">Tipo</label>
                                <CustomSelect v-model="form.type" :options="typeOptions" />
                            </div>

                            <!-- Reminder -->
                            <div>
                                <label class="form-label">Recordatorio</label>
                                <CustomSelect v-model="form.reminder" :options="reminderOptions" />
                            </div>

                            <!-- Color — full width -->
                            <div class="col-span-2">
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

                            <!-- Recurrence — full width -->
                            <div class="col-span-2">
                                <RecurrenceEditor v-model="form.recurrence_rule" />
                            </div>

                            <!-- Attendees — full width -->
                            <div class="col-span-2">
                                <label class="form-label">Invitados</label>
                                <div class="space-y-2">
                                    <div v-if="form.attendees.length" class="flex flex-wrap gap-1.5">
                                        <span
                                            v-for="(att, idx) in form.attendees"
                                            :key="idx"
                                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs bg-white/[0.08] text-surface-200 border border-white/[0.06]"
                                        >
                                            {{ att }}
                                            <button
                                                type="button"
                                                @click="form.attendees.splice(idx, 1)"
                                                class="hover:text-red-400 transition-colors"
                                            >
                                                <XMarkIcon class="w-3 h-3" />
                                            </button>
                                        </span>
                                    </div>
                                    <input
                                        v-model="attendeeInput"
                                        class="form-input"
                                        placeholder="Añadir invitado y pulsar Enter..."
                                        @keydown.enter.prevent="addAttendee"
                                        @keydown.,prevent="addAttendee"
                                    />
                                </div>
                            </div>

                            <!-- Location with map — full width -->
                            <div class="col-span-2">
                                <label class="form-label">Ubicación</label>
                                <LocationPicker
                                    v-model="form.location"
                                    v-model:latitude="form.latitude"
                                    v-model:longitude="form.longitude"
                                />
                            </div>
                        </div>
                    </form>

                    <!-- Footer -->
                    <div class="px-6 py-4 border-t border-white/[0.06] flex justify-end gap-3">
                        <button type="button" class="btn-secondary" @click="$emit('close')">
                            Cancelar
                        </button>
                        <button @click="handleSubmit" class="btn-primary" :disabled="saving">
                            {{ event ? 'Guardar' : 'Crear evento' }}
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { useCreateCalendarEvent, useUpdateCalendarEvent } from '@/composables/useCalendar'
import CustomSelect from '@/components/ui/CustomSelect.vue'
import RecurrenceEditor from '@/components/calendar/RecurrenceEditor.vue'
import LocationPicker from '@/components/ui/LocationPicker.vue'
import { CalendarDaysIcon, XMarkIcon } from '@heroicons/vue/24/outline'

const typeOptions = [
    { value: 'event', label: 'Evento' },
    { value: 'meeting', label: 'Reunión' },
    { value: 'time_block', label: 'Bloque de tiempo' },
]

const reminderOptions = [
    { value: 'none', label: 'Sin recordatorio' },
    { value: '5m', label: '5 minutos antes' },
    { value: '15m', label: '15 minutos antes' },
    { value: '30m', label: '30 minutos antes' },
    { value: '1h', label: '1 hora antes' },
    { value: '1d', label: '1 día antes' },
]

const props = defineProps({
    event: { type: Object, default: null },
    initialDate: { type: String, default: null },
})

const emit = defineEmits(['close', 'saved'])

const colorOptions = ['#6366f1', '#60a5fa', '#10b981', '#f59e0b', '#f43f5e', '#8b5cf6', '#ec4899']

const defaultStart = () => {
    if (props.initialDate) {
        // MonthView passes YYYY-MM-DD but datetime-local needs YYYY-MM-DDTHH:mm
        if (props.initialDate.length === 10) {
            const now = new Date()
            const mins = Math.ceil(now.getMinutes() / 15) * 15
            const h = mins === 60 ? now.getHours() + 1 : now.getHours()
            const m = mins === 60 ? 0 : mins
            return props.initialDate + 'T' +
                String(h).padStart(2, '0') + ':' +
                String(m).padStart(2, '0')
        }
        return props.initialDate
    }
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
    location: props.event?.location ?? props.event?.meta?.location ?? '',
    latitude: props.event?.latitude ?? null,
    longitude: props.event?.longitude ?? null,
    color: props.event?.color ?? '#6366f1',
    reminder: props.event?.reminder ?? 'none',
    recurrence_rule: props.event?.recurrence_rule ?? null,
    attendees: props.event?.attendees ?? [],
})

const showEndDate = ref(!!form.value.end_at)
const attendeeInput = ref('')
const saving = ref(false)
const titleInput = ref(null)

const createEvent = useCreateCalendarEvent()
const updateEvent = useUpdateCalendarEvent()

// Clear end_at when toggle is off
watch(showEndDate, (val) => {
    if (!val) form.value.end_at = ''
})

onMounted(() => {
    titleInput.value?.focus()
})

function addAttendee() {
    const name = attendeeInput.value.trim().replace(/,$/, '')
    if (name && !form.value.attendees.includes(name)) {
        form.value.attendees.push(name)
    }
    attendeeInput.value = ''
}

async function handleSubmit() {
    if (!form.value.title || saving.value) return
    saving.value = true
    try {
        const data = { ...form.value }
        if (!showEndDate.value || !data.end_at) data.end_at = null
        if (data.reminder === 'none') data.reminder = null
        if (!data.attendees.length) data.attendees = null

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
