<template>
    <div class="space-y-8">
        <header class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-sm uppercase tracking-[0.2em] text-primary-300/70">Almacenamiento</p>
                <h1 class="text-3xl font-semibold text-white mt-1">Mis archivos</h1>
                <p class="text-surface-400 mt-2 max-w-2xl">
                    Todos los archivos adjuntos a tus transacciones en un solo lugar.
                </p>
            </div>
            <div class="flex items-center gap-4">
                <button
                    class="p-2 rounded-lg border border-white/10 text-surface-300 hover:bg-white/10 transition"
                    :class="viewMode === 'grid' ? 'bg-white/10 text-white' : ''"
                    @click="viewMode = 'grid'"
                >
                    <Squares2X2Icon class="w-5 h-5" />
                </button>
                <button
                    class="p-2 rounded-lg border border-white/10 text-surface-300 hover:bg-white/10 transition"
                    :class="viewMode === 'list' ? 'bg-white/10 text-white' : ''"
                    @click="viewMode = 'list'"
                >
                    <ListBulletIcon class="w-5 h-5" />
                </button>
            </div>
        </header>

        <!-- Stats bar -->
        <div class="liquid-glass liquid-glass-card p-5">
            <div class="flex items-center gap-6">
                <div class="flex items-center gap-2">
                    <DocumentIcon class="w-5 h-5 text-primary-400" />
                    <span class="text-sm text-surface-300">{{ files.length }} archivos</span>
                </div>
                <div class="flex items-center gap-2">
                    <CloudArrowUpIcon class="w-5 h-5 text-accent-400" />
                    <span class="text-sm text-surface-300">{{ formatFileSize(totalSize) }} total</span>
                </div>
            </div>
        </div>

        <!-- Loading -->
        <div v-if="isLoading" class="text-center py-16 text-surface-500">
            Cargando archivos...
        </div>

        <!-- Empty state -->
        <div v-else-if="files.length === 0" class="liquid-glass liquid-glass-card p-12 text-center">
            <CloudArrowUpIcon class="w-16 h-16 text-surface-600 mx-auto mb-4" />
            <h3 class="text-lg font-medium text-surface-300">Sin archivos</h3>
            <p class="text-sm text-surface-500 mt-2 max-w-sm mx-auto">
                Adjunta archivos a tus transacciones desde la pagina de finanzas y apareceran aqui.
            </p>
        </div>

        <!-- Grid view -->
        <div v-else-if="viewMode === 'grid'" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            <div
                v-for="file in files"
                :key="file.id"
                class="liquid-glass liquid-glass-card p-5 flex flex-col gap-3"
            >
                <div class="flex items-center gap-3">
                    <div class="rounded-xl p-2.5 bg-primary-500/20">
                        <component :is="fileIcon(file.mime_type)" class="w-6 h-6 text-primary-400" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-white truncate">{{ file.file_name }}</p>
                        <p class="text-xs text-surface-500">{{ formatFileSize(file.size) }}</p>
                    </div>
                </div>
                <p class="text-xs text-surface-500">{{ formatDate(file.created_at) }}</p>
                <div class="flex gap-2 mt-auto">
                    <a
                        :href="`/api/storage/files/${file.id}/download`"
                        class="text-xs font-medium text-primary-300 hover:text-primary-200 transition"
                    >
                        Descargar
                    </a>
                    <button
                        class="text-xs font-medium text-danger-400 hover:text-danger-300 transition"
                        @click="handleDelete(file)"
                    >
                        Eliminar
                    </button>
                </div>
            </div>
        </div>

        <!-- List view -->
        <div v-else class="liquid-glass liquid-glass-card overflow-hidden">
            <ul class="divide-y divide-white/5">
                <li
                    v-for="file in files"
                    :key="file.id"
                    class="flex items-center gap-4 px-5 py-4 hover:bg-white/[0.02] transition"
                >
                    <div class="rounded-xl p-2 bg-primary-500/20">
                        <component :is="fileIcon(file.mime_type)" class="w-5 h-5 text-primary-400" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-white truncate">{{ file.file_name }}</p>
                        <p class="text-xs text-surface-500">{{ formatDate(file.created_at) }}</p>
                    </div>
                    <span class="text-xs text-surface-500 hidden sm:block">{{ formatFileSize(file.size) }}</span>
                    <div class="flex gap-3">
                        <a
                            :href="`/api/storage/files/${file.id}/download`"
                            class="text-xs font-medium text-primary-300 hover:text-primary-200 transition"
                        >
                            Descargar
                        </a>
                        <button
                            class="text-xs font-medium text-danger-400 hover:text-danger-300 transition"
                            @click="handleDelete(file)"
                        >
                            Eliminar
                        </button>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import {
    Squares2X2Icon,
    ListBulletIcon,
    DocumentIcon,
    CloudArrowUpIcon,
    DocumentTextIcon,
    PhotoIcon,
    FilmIcon,
} from '@heroicons/vue/24/outline'
import { useDriveFiles, useDeleteDriveFile } from '@/composables/useStorage'

const viewMode = ref('grid')

const { data: filesData, isLoading } = useDriveFiles()
const deleteMutation = useDeleteDriveFile()

const files = computed(() => filesData.value?.data ?? [])

const totalSize = computed(() =>
    files.value.reduce((sum, f) => sum + (f.size || 0), 0)
)

function fileIcon(mime) {
    if (!mime) return DocumentIcon
    if (mime.startsWith('image/')) return PhotoIcon
    if (mime.startsWith('video/')) return FilmIcon
    if (mime.includes('pdf') || mime.includes('text') || mime.includes('document')) return DocumentTextIcon
    return DocumentIcon
}

function formatFileSize(bytes) {
    if (!bytes) return '0 B'
    if (bytes < 1024) return bytes + ' B'
    if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB'
    return (bytes / 1048576).toFixed(1) + ' MB'
}

function formatDate(dateStr) {
    const d = new Date(dateStr)
    return d.toLocaleDateString('es-ES', { day: '2-digit', month: 'short', year: 'numeric' })
}

function handleDelete(file) {
    if (!confirm(`¿Eliminar "${file.file_name}"?`)) return
    deleteMutation.mutate(file.id)
}
</script>
