<template>
    <label class="text-xs font-medium text-primary-300 hover:text-primary-200 transition cursor-pointer">
        <PaperClipIcon class="w-3.5 h-3.5 inline" />
        <input type="file" class="hidden" @change="attachFile" />
    </label>
</template>

<script setup>
import { PaperClipIcon } from '@heroicons/vue/24/outline'
import { useUploadTransactionMedia } from '@/composables/useStorage'

const props = defineProps({
    transaction: { type: Object, required: true },
})

const uploadMedia = useUploadTransactionMedia()

function attachFile(event) {
    const file = event.target.files?.[0]
    if (!file) return
    uploadMedia.mutate({ transactionId: props.transaction.id, file })
    event.target.value = ''
}
</script>
