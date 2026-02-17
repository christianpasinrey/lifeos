<template>
    <div class="space-y-8">
        <!-- Header -->
        <header class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-sm uppercase tracking-[0.2em] text-primary-300/70">Almacenamiento</p>
                <h1 class="text-3xl font-semibold text-white mt-1">Drive</h1>
            </div>
            <div class="flex items-center gap-3">
                <label class="btn-add cursor-pointer">
                    <ArrowUpTrayIcon class="w-4 h-4" />
                    Subir archivo
                    <input type="file" class="hidden" multiple @change="handleUpload" />
                </label>
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
            <div class="flex flex-wrap items-center gap-x-8 gap-y-3">
                <div class="flex items-center gap-2">
                    <DocumentIcon class="w-5 h-5 text-primary-400" />
                    <span class="text-sm text-surface-300">
                        {{ stats.file_count }} archivo{{ stats.file_count !== 1 ? 's' : '' }}
                        <span v-if="stats.limit_files" class="text-surface-500">/ {{ stats.limit_files }}</span>
                    </span>
                </div>
                <div class="flex items-center gap-2">
                    <CircleStackIcon class="w-5 h-5 text-accent-400" />
                    <span class="text-sm text-surface-300">
                        {{ formatFileSize(stats.total_bytes) }}
                        <span v-if="stats.limit_storage_mb" class="text-surface-500">/ {{ stats.limit_storage_mb }} MB</span>
                    </span>
                </div>
                <div v-if="stats.limit_storage_mb" class="flex-1 min-w-[200px]">
                    <div class="h-1.5 rounded-full bg-white/10 overflow-hidden">
                        <div
                            class="h-full rounded-full transition-all"
                            :class="storagePercent > 90 ? 'bg-danger-400' : 'bg-primary-400'"
                            :style="{ width: Math.min(storagePercent, 100) + '%' }"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Upload progress -->
        <div v-if="uploadMutation.isPending.value" class="liquid-glass liquid-glass-card p-4">
            <div class="flex items-center gap-3">
                <div class="w-5 h-5 border-2 border-primary-400 border-t-transparent rounded-full animate-spin" />
                <span class="text-sm text-surface-300">Subiendo archivo...</span>
            </div>
        </div>

        <!-- Upload error -->
        <div v-if="uploadError" class="liquid-glass liquid-glass-card p-4 border border-danger-500/30">
            <p class="text-sm text-danger-400">{{ uploadError }}</p>
        </div>

        <!-- Search + category filter -->
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <div class="relative flex-1 max-w-md">
                <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-surface-500" />
                <input
                    v-model="search"
                    type="text"
                    placeholder="Buscar por nombre..."
                    class="form-input pl-9 w-full"
                />
            </div>
            <div class="flex flex-wrap gap-2">
                <button
                    v-for="cat in categoryTabs"
                    :key="cat.value"
                    class="px-3 py-1.5 rounded-lg text-xs font-medium transition border"
                    :class="activeCategory === cat.value
                        ? 'bg-primary-500/20 text-primary-200 border-primary-500/30'
                        : 'text-surface-400 border-white/10 hover:bg-white/[0.06]'"
                    @click="activeCategory = cat.value"
                >
                    {{ cat.label }}
                </button>
            </div>
        </div>

        <!-- Loading -->
        <div v-if="filesLoading" class="text-center py-16 text-surface-500">
            Cargando archivos...
        </div>

        <!-- Empty state -->
        <div v-else-if="files.length === 0" class="liquid-glass liquid-glass-card p-12 text-center">
            <CloudArrowUpIcon class="w-16 h-16 text-surface-600 mx-auto mb-4" />
            <h3 class="text-lg font-medium text-surface-300">
                {{ search || activeCategory !== 'all' ? 'Sin resultados' : 'Tu Drive esta vacio' }}
            </h3>
            <p class="text-sm text-surface-500 mt-2 max-w-sm mx-auto">
                {{ search || activeCategory !== 'all'
                    ? 'Prueba con otros filtros de busqueda.'
                    : 'Sube archivos con el boton de arriba o adjunta archivos a tus transacciones.'
                }}
            </p>
        </div>

        <!-- Grid view -->
        <div v-else-if="viewMode === 'grid'" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            <div
                v-for="file in files"
                :key="file.id"
                class="liquid-glass liquid-glass-card group cursor-pointer hover:border-primary-500/20 transition-all"
                @click="openPreview(file)"
            >
                <!-- Thumbnail area -->
                <div class="h-36 rounded-t-[inherit] overflow-hidden bg-white/[0.02] flex items-center justify-center">
                    <img
                        v-if="isImage(file.mime_type)"
                        :src="previewUrl(file)"
                        :alt="file.file_name"
                        class="w-full h-full object-cover"
                    />
                    <div v-else class="flex flex-col items-center gap-2 text-surface-500">
                        <component :is="fileIcon(file.mime_type)" class="w-10 h-10" />
                        <span class="text-xs uppercase font-medium tracking-wider">{{ fileExtension(file.file_name) }}</span>
                    </div>
                </div>
                <div class="p-4">
                    <p class="text-sm font-medium text-white truncate" :title="file.file_name">{{ file.file_name }}</p>
                    <div class="flex items-center justify-between mt-2">
                        <span class="text-xs text-surface-500">{{ formatFileSize(file.size) }}</span>
                        <span class="text-xs text-surface-500">{{ formatDate(file.created_at) }}</span>
                    </div>
                    <div class="flex gap-3 mt-3 opacity-0 group-hover:opacity-100 transition-opacity">
                        <a
                            :href="downloadUrl(file)"
                            class="text-xs font-medium text-primary-300 hover:text-primary-200 transition"
                            @click.stop
                        >
                            Descargar
                        </a>
                        <button
                            class="text-xs font-medium text-danger-400 hover:text-danger-300 transition"
                            @click.stop="handleDelete(file)"
                        >
                            Eliminar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- List view -->
        <div v-else class="liquid-glass liquid-glass-card overflow-hidden">
            <ul class="divide-y divide-white/5">
                <li
                    v-for="file in files"
                    :key="file.id"
                    class="flex items-center gap-4 px-5 py-4 hover:bg-white/[0.02] transition cursor-pointer"
                    @click="openPreview(file)"
                >
                    <!-- Mini thumbnail -->
                    <div class="w-10 h-10 rounded-lg overflow-hidden bg-white/[0.04] flex items-center justify-center shrink-0">
                        <img
                            v-if="isImage(file.mime_type)"
                            :src="previewUrl(file)"
                            :alt="file.file_name"
                            class="w-full h-full object-cover"
                        />
                        <component v-else :is="fileIcon(file.mime_type)" class="w-5 h-5 text-surface-500" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-white truncate">{{ file.file_name }}</p>
                        <p class="text-xs text-surface-500">{{ formatDate(file.created_at) }}</p>
                    </div>
                    <span class="text-xs text-surface-500 hidden sm:block shrink-0">{{ formatFileSize(file.size) }}</span>
                    <div class="flex gap-3 shrink-0">
                        <a
                            :href="downloadUrl(file)"
                            class="text-xs font-medium text-primary-300 hover:text-primary-200 transition"
                            @click.stop
                        >
                            Descargar
                        </a>
                        <button
                            class="text-xs font-medium text-danger-400 hover:text-danger-300 transition"
                            @click.stop="handleDelete(file)"
                        >
                            Eliminar
                        </button>
                    </div>
                </li>
            </ul>
        </div>

        <!-- Preview modal -->
        <Teleport to="body">
            <div v-if="previewFile" class="modal-overlay" @mousedown.self="closePreview">
                <div class="modal-backdrop" />
                <div class="modal-content liquid-glass liquid-glass-panel w-full max-w-4xl max-h-[90vh] flex flex-col">
                    <!-- Modal header -->
                    <div class="flex items-center justify-between p-5 border-b border-white/10 shrink-0">
                        <div class="flex items-center gap-3 min-w-0">
                            <component :is="fileIcon(previewFile.mime_type)" class="w-5 h-5 text-primary-400 shrink-0" />
                            <h2 class="text-base font-medium text-white truncate">{{ previewFile.file_name }}</h2>
                            <span class="text-xs text-surface-500 shrink-0">{{ formatFileSize(previewFile.size) }}</span>
                        </div>
                        <div class="flex items-center gap-3 shrink-0">
                            <a
                                :href="downloadUrl(previewFile)"
                                class="text-sm font-medium text-primary-300 hover:text-primary-200 transition"
                            >
                                Descargar
                            </a>
                            <button @click="closePreview" class="text-surface-400 hover:text-white transition text-xl leading-none">&times;</button>
                        </div>
                    </div>

                    <!-- Modal body -->
                    <div class="flex-1 overflow-auto p-5">
                        <!-- Image -->
                        <div v-if="isImage(previewFile.mime_type)" class="flex items-center justify-center">
                            <img
                                :src="previewUrl(previewFile)"
                                :alt="previewFile.file_name"
                                class="max-w-full max-h-[70vh] rounded-lg object-contain"
                            />
                        </div>

                        <!-- PDF -->
                        <div v-else-if="isPdf(previewFile.mime_type)">
                            <iframe
                                :src="previewUrl(previewFile)"
                                class="w-full rounded-lg border border-white/10"
                                style="height: 70vh"
                            />
                        </div>

                        <!-- Video -->
                        <div v-else-if="isVideo(previewFile.mime_type)" class="flex items-center justify-center">
                            <video
                                :src="previewUrl(previewFile)"
                                controls
                                class="max-w-full max-h-[70vh] rounded-lg"
                            />
                        </div>

                        <!-- Audio -->
                        <div v-else-if="isAudio(previewFile.mime_type)" class="flex items-center justify-center py-12">
                            <audio :src="previewUrl(previewFile)" controls class="w-full max-w-lg" />
                        </div>

                        <!-- Excel preview -->
                        <div v-else-if="isExcel(previewFile.mime_type)">
                            <div v-if="richPreviewLoading" class="text-center py-12 text-surface-500">
                                Cargando hoja de calculo...
                            </div>
                            <div v-else-if="excelSheets.length" class="space-y-4">
                                <div v-if="excelSheets.length > 1" class="flex flex-wrap gap-2">
                                    <button
                                        v-for="(sheet, i) in excelSheets"
                                        :key="i"
                                        class="px-3 py-1 rounded-lg text-xs font-medium transition border"
                                        :class="activeSheet === i
                                            ? 'bg-primary-500/20 text-primary-200 border-primary-500/30'
                                            : 'text-surface-400 border-white/10 hover:bg-white/[0.06]'"
                                        @click="activeSheet = i"
                                    >
                                        {{ sheet.name }}
                                    </button>
                                </div>
                                <div class="overflow-auto rounded-lg border border-white/10">
                                    <table class="w-full text-sm">
                                        <thead>
                                            <tr>
                                                <th
                                                    v-for="(cell, ci) in excelSheets[activeSheet].rows[0]"
                                                    :key="ci"
                                                    class="px-3 py-2 text-left text-xs font-medium text-surface-300 bg-white/[0.04] border-b border-white/10 whitespace-nowrap"
                                                >
                                                    {{ cell }}
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr
                                                v-for="(row, ri) in excelSheets[activeSheet].rows.slice(1)"
                                                :key="ri"
                                                class="border-b border-white/5 hover:bg-white/[0.02]"
                                            >
                                                <td
                                                    v-for="(cell, ci) in row"
                                                    :key="ci"
                                                    class="px-3 py-1.5 text-surface-300 whitespace-nowrap"
                                                >
                                                    {{ cell }}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div v-else class="text-center py-12 text-surface-500">
                                No se pudo leer el archivo.
                            </div>
                        </div>

                        <!-- Word preview -->
                        <div v-else-if="isWord(previewFile.mime_type)">
                            <div v-if="richPreviewLoading" class="text-center py-12 text-surface-500">
                                Cargando documento...
                            </div>
                            <div
                                v-else-if="wordHtml"
                                class="prose prose-invert prose-sm max-w-none p-6 rounded-lg border border-white/10 bg-white/[0.02]"
                                v-html="wordHtml"
                            />
                            <div v-else class="text-center py-12 text-surface-500">
                                No se pudo leer el documento.
                            </div>
                        </div>

                        <!-- Text / code preview -->
                        <div v-else-if="isText(previewFile.mime_type) || isCode(previewFile.file_name)">
                            <div v-if="richPreviewLoading" class="text-center py-12 text-surface-500">
                                Cargando contenido...
                            </div>
                            <pre
                                v-else
                                class="p-5 rounded-lg border border-white/10 bg-white/[0.02] text-sm text-surface-300 overflow-auto max-h-[70vh] whitespace-pre-wrap"
                            >{{ textContent }}</pre>
                        </div>

                        <!-- Fallback - no preview -->
                        <div v-else class="text-center py-16">
                            <component :is="fileIcon(previewFile.mime_type)" class="w-16 h-16 text-surface-600 mx-auto mb-4" />
                            <h3 class="text-lg font-medium text-surface-300">Vista previa no disponible</h3>
                            <p class="text-sm text-surface-500 mt-2">
                                {{ fileExtension(previewFile.file_name).toUpperCase() }} &mdash; {{ formatFileSize(previewFile.size) }}
                            </p>
                            <a
                                :href="downloadUrl(previewFile)"
                                class="inline-block mt-4 px-4 py-2 rounded-xl bg-primary-500/20 text-primary-200 text-sm font-medium hover:bg-primary-500/30 transition"
                            >
                                Descargar archivo
                            </a>
                        </div>

                        <!-- AI Analysis section -->
                        <div v-if="canAnalyze(previewFile)" class="mt-6 pt-6 border-t border-white/10">
                            <div class="flex items-center gap-2 mb-4">
                                <SparklesIcon class="w-5 h-5 text-primary-400" />
                                <h4 class="text-sm font-semibold text-white">Analizar con IA</h4>
                            </div>

                            <!-- Quick actions -->
                            <div class="flex flex-wrap gap-2 mb-3">
                                <button
                                    v-for="action in driveFileActions"
                                    :key="action.label"
                                    class="ai-chip"
                                    :disabled="analyzeMutation.isPending.value"
                                    @click="handleDriveAction(action, previewFile)"
                                >
                                    <component :is="action.icon" class="w-3.5 h-3.5" />
                                    {{ action.label }}
                                </button>
                                <button
                                    class="ai-chip"
                                    :disabled="analyzeMutation.isPending.value"
                                    @click="runAnalysis(previewFile, 'Resume el contenido de este archivo de forma concisa. Destaca los puntos clave.')"
                                >
                                    <DocumentTextIcon class="w-3.5 h-3.5" />
                                    Resumir
                                </button>
                            </div>

                            <!-- Custom prompt -->
                            <form @submit.prevent="submitCustomPrompt(previewFile)" class="flex gap-2">
                                <input
                                    v-model="aiPrompt"
                                    type="text"
                                    placeholder="Pregunta algo sobre este archivo..."
                                    class="form-input flex-1 text-sm"
                                    :disabled="analyzeMutation.isPending.value"
                                />
                                <button
                                    type="submit"
                                    class="px-4 py-2 rounded-xl bg-primary-600/80 hover:bg-primary-500/80 disabled:opacity-50 text-white text-sm font-medium transition"
                                    :disabled="!aiPrompt.trim() || analyzeMutation.isPending.value"
                                >
                                    Enviar
                                </button>
                            </form>

                            <!-- Loading -->
                            <div v-if="analyzeMutation.isPending.value" class="mt-4 flex items-center gap-3">
                                <div class="w-4 h-4 border-2 border-primary-400 border-t-transparent rounded-full animate-spin" />
                                <span class="text-sm text-surface-400">Analizando archivo...</span>
                            </div>

                            <!-- Response -->
                            <div v-if="aiResponse" class="mt-4 p-4 rounded-xl border border-primary-500/20 bg-primary-500/5">
                                <div class="flex items-center gap-2 mb-2">
                                    <SparklesIcon class="w-4 h-4 text-primary-400" />
                                    <span class="text-xs font-medium text-primary-300">Coach IA</span>
                                </div>
                                <div class="text-sm text-surface-200 whitespace-pre-wrap leading-relaxed" v-html="formatAiResponse(aiResponse)" />

                                <!-- Confirm action button -->
                                <div v-if="lastAnalysisAction" class="mt-4 pt-3 border-t border-primary-500/10 flex items-center gap-3">
                                    <button
                                        class="btn-primary text-sm"
                                        :disabled="confirmActionPending"
                                        @click="confirmAnalysisAction"
                                    >
                                        {{ confirmActionPending ? 'Procesando...' : lastAnalysisAction.label }}
                                    </button>
                                    <button
                                        class="btn-secondary text-sm"
                                        @click="lastAnalysisAction = null"
                                    >
                                        Descartar
                                    </button>
                                </div>

                                <!-- Confirm action success -->
                                <div v-if="confirmActionResult" class="mt-3 p-3 rounded-lg border border-accent-500/20 bg-accent-500/5 text-sm text-accent-300">
                                    {{ confirmActionResult }}
                                </div>
                            </div>

                            <!-- Error -->
                            <div v-if="aiError" class="mt-4 p-3 rounded-xl border border-danger-500/20 bg-danger-500/5">
                                <p class="text-sm text-danger-400">{{ aiError }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, computed, watch, nextTick } from 'vue'
import { useModuleRegistry } from '@/modules/registry'
import {
    Squares2X2Icon,
    ListBulletIcon,
    DocumentIcon,
    DocumentTextIcon,
    PhotoIcon,
    FilmIcon,
    MusicalNoteIcon,
    TableCellsIcon,
    CloudArrowUpIcon,
    CircleStackIcon,
    ArrowUpTrayIcon,
    MagnifyingGlassIcon,
    SparklesIcon,
} from '@heroicons/vue/24/outline'
import {
    useDriveFiles,
    useDriveStats,
    useUploadDriveFile,
    useDeleteDriveFile,
    useAnalyzeFile,
} from '@/composables/useStorage'

const { actionsForSlot } = useModuleRegistry()
const driveFileActions = actionsForSlot('drive-file-actions')

// ── State ──
const viewMode = ref('grid')
const search = ref('')
const activeCategory = ref('all')
const previewFile = ref(null)
const uploadError = ref('')

// Rich preview state
const richPreviewLoading = ref(false)
const excelSheets = ref([])
const activeSheet = ref(0)
const wordHtml = ref('')
const textContent = ref('')

// AI analysis state
const aiPrompt = ref('')
const aiResponse = ref('')
const aiError = ref('')
const analyzeMutation = useAnalyzeFile()
const lastAnalysisAction = ref(null)
const confirmActionPending = ref(false)
const confirmActionResult = ref('')

const categoryTabs = [
    { value: 'all', label: 'Todos' },
    { value: 'images', label: 'Imagenes' },
    { value: 'pdf', label: 'PDF' },
    { value: 'documents', label: 'Documentos' },
    { value: 'spreadsheets', label: 'Hojas de calculo' },
    { value: 'videos', label: 'Videos' },
    { value: 'audio', label: 'Audio' },
    { value: 'text', label: 'Texto' },
    { value: 'other', label: 'Otros' },
]

// ── Data ──
const filters = computed(() => ({
    search: search.value || undefined,
    category: activeCategory.value,
}))

const { data: filesData, isLoading: filesLoading } = useDriveFiles(filters)
const { data: statsData } = useDriveStats()
const uploadMutation = useUploadDriveFile()
const deleteMutation = useDeleteDriveFile()

const files = computed(() => filesData.value?.data ?? [])
const stats = computed(() => statsData.value?.data ?? { file_count: 0, total_bytes: 0 })

const storagePercent = computed(() => {
    if (!stats.value.limit_storage_mb) return 0
    const usedMb = stats.value.total_bytes / (1024 * 1024)
    return (usedMb / stats.value.limit_storage_mb) * 100
})

// ── Upload ──
async function handleUpload(event) {
    const fileList = event.target.files
    if (!fileList?.length) return
    uploadError.value = ''

    for (const file of fileList) {
        try {
            await uploadMutation.mutateAsync(file)
        } catch (err) {
            uploadError.value = err.response?.data?.message ?? `Error subiendo ${file.name}`
            break
        }
    }
    event.target.value = ''
}

// ── Delete ──
function handleDelete(file) {
    if (!confirm(`¿Eliminar "${file.file_name}"?`)) return
    deleteMutation.mutate(file.id)
}

// ── Preview ──
function openPreview(file) {
    previewFile.value = file
    resetRichPreview()

    nextTick(() => {
        if (isExcel(file.mime_type)) loadExcelPreview(file)
        else if (isWord(file.mime_type)) loadWordPreview(file)
        else if (isText(file.mime_type) || isCode(file.file_name)) loadTextPreview(file)
    })
}

function closePreview() {
    previewFile.value = null
    resetRichPreview()
}

function resetRichPreview() {
    richPreviewLoading.value = false
    excelSheets.value = []
    activeSheet.value = 0
    wordHtml.value = ''
    textContent.value = ''
    aiPrompt.value = ''
    aiResponse.value = ''
    aiError.value = ''
    lastAnalysisAction.value = null
    confirmActionPending.value = false
    confirmActionResult.value = ''
}

// Max 500KB for AI analysis
const MAX_ANALYZE_SIZE = 512000

function canAnalyze(file) {
    if (!file || file.size > MAX_ANALYZE_SIZE) return false
    const mime = file.mime_type
    return isText(mime) || isCode(file.file_name) || isExcel(mime) || isWord(mime) || isPdf(mime) || mime === 'text/csv'
}

function getExtractedContent() {
    if (textContent.value) return textContent.value
    if (wordHtml.value) return wordHtml.value.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim()
    if (excelSheets.value.length) {
        return excelSheets.value.map(sheet => {
            const header = `[Hoja: ${sheet.name}]`
            const rows = sheet.rows.map(r => r.join('\t')).join('\n')
            return header + '\n' + rows
        }).join('\n\n')
    }
    return null
}

async function runAnalysis(file, prompt, action = null) {
    aiResponse.value = ''
    aiError.value = ''
    lastAnalysisAction.value = null
    confirmActionResult.value = ''

    try {
        const result = await analyzeMutation.mutateAsync({
            mediaId: file.id,
            prompt,
            content: getExtractedContent(),
        })
        aiResponse.value = result.text

        if (action) {
            lastAnalysisAction.value = action
        }
    } catch (e) {
        aiError.value = e.response?.data?.message ?? 'Error al analizar el archivo.'
    }
}

function handleDriveAction(action, file) {
    runAnalysis(file, action.prompt, action.confirmAction || null)
}

function submitCustomPrompt(file) {
    const prompt = aiPrompt.value.trim()
    if (!prompt) return
    runAnalysis(file, prompt)
}

async function confirmAnalysisAction() {
    const action = lastAnalysisAction.value
    if (!action?.handler) return

    confirmActionPending.value = true
    confirmActionResult.value = ''
    aiError.value = ''

    try {
        const result = await action.handler({
            aiResponse: aiResponse.value,
            async reAnalyze(prompt) {
                const res = await analyzeMutation.mutateAsync({
                    mediaId: previewFile.value.id,
                    prompt,
                    content: getExtractedContent(),
                })
                aiResponse.value = res.text
                return res
            },
        })

        if (result.error) {
            aiError.value = result.error
        } else {
            confirmActionResult.value = result.message
            lastAnalysisAction.value = null
        }
    } catch (e) {
        aiError.value = e.response?.data?.message ?? 'Error al procesar la acción.'
    } finally {
        confirmActionPending.value = false
    }
}

function formatAiResponse(text) {
    if (!text) return ''
    // Hide the JSON data block from display
    let clean = text.replace(/TRANSACTIONS_JSON_START[\s\S]*?TRANSACTIONS_JSON_END/g, '')
    return clean
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
        .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
        .replace(/\*(.*?)\*/g, '<em>$1</em>')
        .replace(/\n/g, '<br>')
        .replace(/(<br>)+$/g, '')
}

async function loadExcelPreview(file) {
    richPreviewLoading.value = true
    try {
        const mod = await import('exceljs')
        const ExcelJS = mod.default || mod
        const response = await fetch(previewUrl(file), { credentials: 'include' })
        const buffer = await response.arrayBuffer()

        const workbook = new ExcelJS.Workbook()
        await workbook.xlsx.load(buffer)

        const sheets = []
        workbook.eachSheet((worksheet) => {
            const rows = []
            worksheet.eachRow({ includeEmpty: false }, (row) => {
                rows.push(row.values.slice(1).map(v => {
                    if (v === null || v === undefined) return ''
                    if (typeof v === 'object' && v.result !== undefined) return String(v.result)
                    if (typeof v === 'object' && v.text) return String(v.text)
                    if (typeof v === 'object' && v instanceof Date) return v.toLocaleDateString('es-ES')
                    if (typeof v === 'object') return JSON.stringify(v)
                    return String(v)
                }))
            })
            if (rows.length) {
                const maxCols = Math.max(...rows.map(r => r.length))
                rows.forEach(r => { while (r.length < maxCols) r.push('') })
                sheets.push({ name: worksheet.name, rows })
            }
        })

        excelSheets.value = sheets
    } catch (e) {
        console.error('Excel preview error:', e)
        excelSheets.value = []
    }
    richPreviewLoading.value = false
}

async function loadWordPreview(file) {
    richPreviewLoading.value = true
    try {
        const mammoth = await import('mammoth')
        const response = await fetch(previewUrl(file), { credentials: 'include' })
        const buffer = await response.arrayBuffer()
        const result = await mammoth.convertToHtml({ arrayBuffer: buffer })
        wordHtml.value = result.value
    } catch (e) {
        console.error('Word preview error:', e)
        wordHtml.value = ''
    }
    richPreviewLoading.value = false
}

async function loadTextPreview(file) {
    richPreviewLoading.value = true
    try {
        const response = await fetch(previewUrl(file), { credentials: 'include' })
        textContent.value = await response.text()
    } catch {
        textContent.value = 'No se pudo cargar el archivo.'
    }
    richPreviewLoading.value = false
}

// ── Helpers ──
function previewUrl(file) {
    return `/api/storage/files/${file.id}/preview`
}

function downloadUrl(file) {
    return `/api/storage/files/${file.id}/download`
}

function isImage(mime) { return mime?.startsWith('image/') }
function isPdf(mime) { return mime === 'application/pdf' }
function isVideo(mime) { return mime?.startsWith('video/') }
function isAudio(mime) { return mime?.startsWith('audio/') }
function isExcel(mime) {
    return mime && (
        mime.includes('sheet') || mime.includes('excel') ||
        mime === 'text/csv' ||
        mime === 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    )
}
function isWord(mime) {
    return mime && (
        mime.includes('word') || mime.includes('opendocument.text') ||
        mime === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    )
}
function isText(mime) { return mime?.startsWith('text/') && !mime.includes('csv') }
function isCode(name) {
    const ext = fileExtension(name)
    return ['js', 'ts', 'jsx', 'tsx', 'vue', 'php', 'py', 'rb', 'go', 'rs', 'java', 'css', 'scss', 'html', 'xml', 'json', 'yaml', 'yml', 'toml', 'md', 'sql', 'sh', 'bash', 'env'].includes(ext)
}

function fileIcon(mime) {
    if (!mime) return DocumentIcon
    if (mime.startsWith('image/')) return PhotoIcon
    if (mime.startsWith('video/')) return FilmIcon
    if (mime.startsWith('audio/')) return MusicalNoteIcon
    if (mime.includes('sheet') || mime.includes('excel') || mime.includes('csv')) return TableCellsIcon
    if (mime.includes('pdf') || mime.includes('word') || mime.includes('text') || mime.includes('document')) return DocumentTextIcon
    return DocumentIcon
}

function fileExtension(name) {
    return (name?.split('.').pop() || '').toLowerCase()
}

function formatFileSize(bytes) {
    if (!bytes) return '0 B'
    if (bytes < 1024) return bytes + ' B'
    if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB'
    if (bytes < 1073741824) return (bytes / 1048576).toFixed(1) + ' MB'
    return (bytes / 1073741824).toFixed(2) + ' GB'
}

function formatDate(dateStr) {
    return new Date(dateStr).toLocaleDateString('es-ES', { day: '2-digit', month: 'short', year: 'numeric' })
}
</script>
