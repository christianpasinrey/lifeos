import { defineAsyncComponent, markRaw } from 'vue'
import { SparklesIcon } from '@heroicons/vue/24/outline'
import { registerModule } from './registry'

registerModule({
    module: 'habits',
    navItems: [
        { to: '/habits', label: 'Hábitos', icon: markRaw(SparklesIcon), order: 10 },
    ],
    dashboardWidgets: [
        {
            component: defineAsyncComponent(() => import('@/components/dashboard/HabitsWidget.vue')),
            order: 10,
        },
    ],
    actions: [
        {
            slot: 'drive-file-actions',
            label: 'Sugerir hábitos',
            icon: markRaw(SparklesIcon),
            prompt: 'Analiza este archivo y sugiere hábitos que el usuario podría crear basándose en su contenido. Crea los que tengan más sentido.',
            order: 20,
        },
    ],
})
