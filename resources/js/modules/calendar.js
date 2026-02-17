import { defineAsyncComponent, markRaw } from 'vue'
import { CalendarDaysIcon } from '@heroicons/vue/24/outline'
import { registerModule } from './registry'

registerModule({
    module: 'calendar',
    navItems: [
        { to: '/calendar', label: 'Calendario', icon: markRaw(CalendarDaysIcon), order: 15 },
    ],
    calendarSlot: {
        source: 'calendar',
        label: 'Evento',
        icon: markRaw(CalendarDaysIcon),
        color: '#6366f1',
        order: 10,
        detailComponent: defineAsyncComponent(() => import('@/components/calendar/slots/CalendarDayDetail.vue')),
        quickCreateComponent: defineAsyncComponent(() => import('@/components/calendar/CalendarEventModal.vue')),
    },
})
