import { defineAsyncComponent, markRaw } from 'vue'
import { ClipboardDocumentListIcon } from '@heroicons/vue/24/outline'
import { registerModule } from './registry'

registerModule({
    module: 'tasks',
    navItems: [
        { to: '/boards', label: 'Tareas', icon: markRaw(ClipboardDocumentListIcon), order: 20 },
    ],
    calendarSlot: {
        source: 'tasks',
        label: 'Tarea',
        icon: markRaw(ClipboardDocumentListIcon),
        color: '#60a5fa',
        order: 20,
        detailComponent: defineAsyncComponent(() => import('@/components/calendar/slots/TasksDayDetail.vue')),
        quickCreateComponent: defineAsyncComponent(() => import('@/components/calendar/slots/TasksQuickCreate.vue')),
    },
    actions: [
        {
            slot: 'drive-file-actions',
            label: 'Extraer tareas',
            icon: markRaw(ClipboardDocumentListIcon),
            prompt: 'Analiza este archivo y crea tareas accionables en el tablero de tareas. Extrae todas las acciones concretas que encuentres.',
            order: 10,
        },
    ],
})
