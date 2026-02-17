import { defineAsyncComponent, markRaw } from 'vue'
import { SparklesIcon } from '@heroicons/vue/24/outline'
import { registerModule } from './registry'

registerModule({
    module: 'ai_coach',
    dashboardWidgets: [
        {
            component: defineAsyncComponent(() => import('@/components/dashboard/AiCoachWidget.vue')),
            order: 20,
        },
    ],
    sidebarWidgets: [
        {
            component: defineAsyncComponent(() => import('@/components/ai/CoachSidebarButton.vue')),
            order: 10,
        },
    ],
    actions: [
        {
            slot: 'board-toolbar',
            label: 'Generar con IA',
            icon: markRaw(SparklesIcon),
            emit: 'show-generator',
            order: 10,
        },
        {
            slot: 'finance-dashboard-toolbar',
            label: 'Analizar con IA',
            icon: markRaw(SparklesIcon),
            emit: 'show-analysis',
            order: 10,
        },
    ],
})
