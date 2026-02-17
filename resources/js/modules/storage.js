import { markRaw } from 'vue'
import { FolderIcon } from '@heroicons/vue/24/outline'
import { registerModule } from './registry'

registerModule({
    module: 'storage',
    navItems: [
        { to: '/drive', label: 'Archivos', icon: markRaw(FolderIcon), order: 40 },
    ],
})
