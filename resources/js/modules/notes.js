import { markRaw } from 'vue'
import { DocumentTextIcon } from '@heroicons/vue/24/outline'
import { registerModule } from './registry'

registerModule({
    module: 'notes',
    navItems: [
        { to: '/notes', label: 'Notas', icon: markRaw(DocumentTextIcon), order: 35 },
    ],
})
