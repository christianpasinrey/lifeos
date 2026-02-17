<template>
    <Teleport to=".layout-root">
        <Transition name="slide-right">
            <aside class="calendar-panel-aside">
                <div class="liquid-glass liquid-glass-panel h-full flex flex-col rounded-[20px]">
                    <!-- Header -->
                    <div class="flex items-center justify-between px-5 py-4 border-b border-white/[0.06]">
                        <span
                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium"
                            :style="{ backgroundColor: event.color + '20', color: event.color }"
                        >
                            <span class="w-2 h-2 rounded-full" :style="{ backgroundColor: event.color }" />
                            {{ sourceLabel }}
                        </span>
                        <button @click="$emit('close')" class="btn-close">
                            <XMarkIcon class="w-5 h-5" />
                        </button>
                    </div>

                    <!-- Content -->
                    <div class="flex-1 overflow-y-auto px-5 py-5 glass-scroll space-y-4">
                        <!-- Title -->
                        <h3 class="text-xl font-semibold text-surface-100">{{ event.title }}</h3>

                        <!-- Time -->
                        <div class="flex items-center gap-2.5 text-sm text-surface-300">
                            <div class="w-8 h-8 rounded-lg bg-white/[0.05] flex items-center justify-center">
                                <ClockIcon class="w-4 h-4 text-surface-400" />
                            </div>
                            <span>{{ formattedTime }}</span>
                        </div>

                        <!-- Location -->
                        <div v-if="event.meta?.location" class="flex items-center gap-2.5 text-sm text-surface-300">
                            <div class="w-8 h-8 rounded-lg bg-white/[0.05] flex items-center justify-center">
                                <MapPinIcon class="w-4 h-4 text-surface-400" />
                            </div>
                            <span>{{ event.meta.location }}</span>
                        </div>

                        <!-- Description -->
                        <div v-if="event.description" class="pt-2">
                            <p class="text-xs text-surface-500 mb-1">Descripción</p>
                            <p class="text-sm text-surface-300 whitespace-pre-line leading-relaxed">
                                {{ event.description }}
                            </p>
                        </div>

                        <!-- Meta info -->
                        <div v-if="metaInfo.length" class="pt-2 space-y-2">
                            <div
                                v-for="info in metaInfo"
                                :key="info.label"
                                class="flex items-center justify-between text-sm"
                            >
                                <span class="text-surface-500">{{ info.label }}</span>
                                <span class="text-surface-300">{{ info.value }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div v-if="isNativeEvent" class="px-5 py-4 border-t border-white/[0.06] flex justify-end gap-3">
                        <button @click="handleDelete" class="btn-secondary text-danger-400 text-xs" :disabled="deleting">
                            Eliminar
                        </button>
                        <button @click="$emit('edit', event)" class="btn-primary text-xs">
                            Editar
                        </button>
                    </div>
                </div>
            </aside>
        </Transition>
    </Teleport>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useDeleteCalendarEvent } from '@/composables/useCalendar'
import { XMarkIcon, ClockIcon, MapPinIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    event: { type: Object, required: true },
})

const emit = defineEmits(['close', 'edit', 'deleted'])

const deleting = ref(false)
const deleteEvent = useDeleteCalendarEvent()

const isNativeEvent = computed(() => props.event.source === 'calendar')

const sourceLabels = {
    calendar: 'Calendario',
    tasks: 'Tareas',
    habits: 'Hábitos',
    finance: 'Finanzas',
}

const sourceLabel = computed(() => sourceLabels[props.event.source] ?? props.event.source)

const formattedTime = computed(() => {
    const e = props.event
    if (e.all_day) return 'Todo el día'
    let time = e.start.substring(11, 16) || e.start
    if (e.end) time += ' - ' + (e.end.substring(11, 16) || e.end)
    return time
})

const metaInfo = computed(() => {
    const info = []
    const m = props.event.meta || {}

    if (m.priority) info.push({ label: 'Prioridad', value: m.priority })
    if (m.category) info.push({ label: 'Categoría', value: m.category })
    if (m.frequency) info.push({ label: 'Frecuencia', value: m.frequency })
    if (m.type) info.push({ label: 'Tipo', value: m.type })
    if (m.amount) info.push({ label: 'Monto', value: '$' + Number(m.amount).toFixed(2) })
    if (m.completed !== undefined) info.push({ label: 'Completado', value: m.completed ? 'Sí' : 'No' })

    return info
})

async function handleDelete() {
    if (!props.event.meta?.native_id) return
    deleting.value = true
    try {
        await deleteEvent.mutateAsync(props.event.meta.native_id)
        emit('deleted')
    } finally {
        deleting.value = false
    }
}
</script>
