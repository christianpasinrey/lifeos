<template>
    <div class="space-y-3">
        <h4 class="text-xs font-semibold text-surface-400 uppercase tracking-wider">Adjuntos</h4>

        <!-- Drop zone -->
        <div
            @dragover.prevent="dragOver = true"
            @dragleave="dragOver = false"
            @drop.prevent="handleDrop"
            class="border border-dashed rounded-xl p-4 text-center transition-all cursor-pointer"
            :class="dragOver ? 'border-primary-400 bg-primary-500/5' : 'border-white/10 hover:border-white/20'"
            @click="$refs.fileInput.click()"
        >
            <ArrowUpTrayIcon class="w-5 h-5 mx-auto text-surface-500 mb-1" />
            <p class="text-xs text-surface-500">Arrastra archivos o haz clic</p>
            <input ref="fileInput" type="file" class="hidden" multiple @change="handleFiles" />
        </div>

        <!-- Uploading indicator -->
        <div v-if="uploading" class="flex items-center gap-2 text-xs text-surface-400">
            <div class="animate-spin w-3 h-3 border border-primary-400 border-t-transparent rounded-full" />
            Subiendo...
        </div>

        <!-- Image grid -->
        <div v-if="imageAttachments.length" class="grid grid-cols-3 gap-2">
            <div
                v-for="att in imageAttachments"
                :key="att.id"
                class="relative group rounded-lg overflow-hidden aspect-square"
            >
                <img :src="att.url" :alt="att.name" class="w-full h-full object-cover" />
                <button
                    @click="remove(att.id)"
                    class="absolute top-1 right-1 w-5 h-5 rounded-full bg-black/60 text-white opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center"
                >
                    <XMarkIcon class="w-3 h-3" />
                </button>
            </div>
        </div>

        <!-- File list -->
        <div v-if="fileAttachments.length" class="space-y-1">
            <div
                v-for="att in fileAttachments"
                :key="att.id"
                class="flex items-center gap-2 px-3 py-2 rounded-lg bg-white/[0.03] group"
            >
                <DocumentIcon class="w-4 h-4 text-surface-500 flex-shrink-0" />
                <a
                    :href="att.url"
                    target="_blank"
                    class="text-xs text-surface-300 hover:text-primary-400 truncate flex-1"
                >{{ att.name }}</a>
                <span class="text-xs text-surface-600">{{ formatSize(att.size) }}</span>
                <button
                    @click="remove(att.id)"
                    class="text-surface-600 hover:text-danger-400 opacity-0 group-hover:opacity-100 transition-opacity"
                >
                    <XMarkIcon class="w-3.5 h-3.5" />
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useUploadAttachment, useDeleteAttachment } from '@/composables/useTasks'
import { ArrowUpTrayIcon, XMarkIcon, DocumentIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    taskId: { type: Number, required: true },
    boardId: { type: Number, required: true },
    attachments: { type: Array, default: () => [] },
})

const dragOver = ref(false)
const uploading = ref(false)
const fileInput = ref(null)

const uploadAttachment = useUploadAttachment()
const deleteAttachment = useDeleteAttachment()

const imageAttachments = computed(() =>
    props.attachments.filter(a => a.mime_type?.startsWith('image/'))
)

const fileAttachments = computed(() =>
    props.attachments.filter(a => !a.mime_type?.startsWith('image/'))
)

function formatSize(bytes) {
    if (!bytes) return ''
    if (bytes < 1024) return `${bytes} B`
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`
}

async function uploadFiles(files) {
    uploading.value = true
    try {
        for (const file of files) {
            await uploadAttachment.mutateAsync({
                taskId: props.taskId,
                boardId: props.boardId,
                file,
            })
        }
    } finally {
        uploading.value = false
    }
}

function handleDrop(e) {
    dragOver.value = false
    const files = e.dataTransfer?.files
    if (files?.length) uploadFiles(files)
}

function handleFiles(e) {
    const files = e.target.files
    if (files?.length) uploadFiles(files)
    e.target.value = ''
}

async function remove(attachmentId) {
    await deleteAttachment.mutateAsync({
        taskId: props.taskId,
        boardId: props.boardId,
        attachmentId,
    })
}
</script>
