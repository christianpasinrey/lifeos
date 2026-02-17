import { markRaw } from 'vue'
import { CalendarDaysIcon } from '@heroicons/vue/24/outline'
import { registerModule } from './registry'

registerModule({
    module: 'calendar',
    navItems: [
        { to: '/calendar', label: 'Calendario', icon: markRaw(CalendarDaysIcon), order: 15 },
    ],
})
