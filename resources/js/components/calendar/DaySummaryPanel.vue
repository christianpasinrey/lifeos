<template>
    <Teleport to=".layout-root">
        <Transition name="slide-right">
            <aside class="calendar-panel-aside">
                <div class="liquid-glass liquid-glass-panel h-full flex flex-col rounded-[20px]">
                    <!-- Header -->
                    <div class="flex items-center justify-between px-5 py-4 border-b border-white/[0.06]">
                        <div class="flex items-center gap-3">
                            <h3 class="text-sm font-semibold text-white">{{ formattedDate }}</h3>
                            <button
                                @click="handleQuickCreateClick"
                                class="w-6 h-6 rounded-md bg-white/[0.06] hover:bg-white/[0.12] flex items-center justify-center transition-colors"
                            >
                                <PlusIcon class="w-3.5 h-3.5 text-surface-300" />
                            </button>
                        </div>
                        <button @click="$emit('close')" class="btn-close">
                            <XMarkIcon class="w-5 h-5" />
                        </button>
                    </div>

                    <!-- Content -->
                    <div class="flex-1 overflow-y-auto px-5 py-4 glass-scroll space-y-5">
                        <template v-for="state in activeSources" :key="state.source">
                            <div class="calendar-slot-section" :style="{ borderColor: state.color }">
                                <div class="flex items-center gap-2 mb-2.5">
                                    <component :is="state.icon" class="w-4 h-4" :style="{ color: state.color }" />
                                    <span class="text-xs font-semibold uppercase tracking-wider" :style="{ color: state.color }">
                                        {{ state.label }}s
                                    </span>
                                    <span class="text-xs text-surface-500">({{ dayEvents(state).length }})</span>
                                </div>
                                <component
                                    :is="state.detailComponent"
                                    :events="dayEvents(state)"
                                    :date="date"
                                    @event-click="$emit('event-click', $event)"
                                />
                            </div>
                        </template>

                        <div v-if="activeSources.length === 0" class="text-center py-8 text-surface-500 text-sm">
                            Sin eventos este día
                        </div>
                    </div>
                </div>
            </aside>
        </Transition>
    </Teleport>
</template>

<script setup>
import { computed } from 'vue'
import { PlusIcon, XMarkIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    date: { type: String, required: true },
    sourceStates: { type: Array, required: true },
})

const emit = defineEmits(['close', 'quick-create', 'event-click'])

const formattedDate = computed(() => {
    const d = new Date(props.date + 'T12:00:00')
    const days = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado']
    const months = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre']
    return `${days[d.getDay()]} ${d.getDate()} de ${months[d.getMonth()]}`
})

function dayEvents(state) {
    return state.events.filter(e => e.start.substring(0, 10) === props.date)
}

const activeSources = computed(() =>
    props.sourceStates.filter(s => dayEvents(s).length > 0)
)

function handleQuickCreateClick(e) {
    emit('quick-create', { x: e.clientX, y: e.clientY })
}
</script>
