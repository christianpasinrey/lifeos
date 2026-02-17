import { defineAsyncComponent, markRaw } from 'vue'
import { FolderIcon } from '@heroicons/vue/24/outline'
import { registerModule } from './registry'

registerModule({
    module: 'storage',
    navItems: [
        { to: '/drive', label: 'Archivos', icon: markRaw(FolderIcon), order: 40 },
    ],
    actions: [
        {
            slot: 'transaction-row-info',
            id: 'transaction-attachments',
            component: defineAsyncComponent(() => import('@/components/finance/TransactionAttachments.vue')),
            order: 10,
        },
        {
            slot: 'transaction-row-actions',
            id: 'transaction-attach-btn',
            component: defineAsyncComponent(() => import('@/components/finance/TransactionAttachButton.vue')),
            order: 10,
        },
    ],
})
