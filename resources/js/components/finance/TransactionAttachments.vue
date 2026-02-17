<template>
    <div v-if="transaction.media?.length" class="flex flex-wrap gap-2 mt-2">
        <span
            v-for="m in transaction.media"
            :key="m.id"
            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-white/[0.06] border border-white/10 text-xs text-surface-300"
        >
            <PaperClipIcon class="w-3.5 h-3.5 text-primary-400" />
            <a
                :href="`/api/storage/transactions/${transaction.id}/media/${m.id}/download`"
                class="hover:text-white transition truncate max-w-[120px]"
            >{{ m.file_name }}</a>
            <span class="text-surface-500">{{ formatFileSize(m.size) }}</span>
            <button @click="removeAttachment(m)" class="text-danger-400 hover:text-danger-300 ml-1">&times;</button>
        </span>
    </div>
</template>

<script setup>
import { PaperClipIcon } from '@heroicons/vue/24/outline'
import { useDeleteTransactionMedia } from '@/composables/useStorage'

const props = defineProps({
    transaction: { type: Object, required: true },
})

const deleteMedia = useDeleteTransactionMedia()

function formatFileSize(bytes) {
    if (bytes < 1024) return bytes + ' B'
    if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB'
    return (bytes / 1048576).toFixed(1) + ' MB'
}

function removeAttachment(media) {
    if (!confirm('¿Eliminar este archivo adjunto?')) return
    deleteMedia.mutate({ transactionId: props.transaction.id, mediaId: media.id })
}
</script>
