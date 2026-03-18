<template>
    <div class="note-graph-view">
        <!-- Toolbar -->
        <div class="graph-toolbar">
            <div class="flex items-center gap-3">
                <router-link :to="{ name: 'notes' }" class="btn-icon" title="Volver al editor">
                    <ArrowLeftIcon class="w-4 h-4" />
                </router-link>
                <h3 class="text-sm font-semibold text-surface-300">Graph View</h3>
            </div>
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-2">
                    <label class="text-xs text-surface-500">Scope:</label>
                    <select v-model="scope" class="form-input text-xs py-1">
                        <option value="global">Global</option>
                        <option value="local">Local</option>
                    </select>
                </div>
                <div v-if="scope === 'local'" class="flex items-center gap-2">
                    <label class="text-xs text-surface-500">Depth:</label>
                    <input v-model.number="depth" type="range" min="1" max="5" class="w-20" />
                    <span class="text-xs text-surface-400">{{ depth }}</span>
                </div>
            </div>
        </div>

        <!-- Canvas -->
        <GraphCanvas
            v-if="graphData"
            :nodes="graphData.nodes"
            :edges="graphData.edges"
            @select-note="onSelectNote"
        />

        <div v-else class="flex items-center justify-center h-full">
            <div class="animate-spin w-6 h-6 border-2 border-primary-400 border-t-transparent rounded-full" />
        </div>
    </div>
</template>

<script setup>
import { ref, computed, inject } from 'vue'
import { useRouter } from 'vue-router'
import { useNoteGraph } from '@/composables/useNoteGraph'
import GraphCanvas from '@/components/notes/GraphCanvas.vue'
import { ArrowLeftIcon } from '@heroicons/vue/24/outline'

const router = useRouter()
const currentNote = inject('currentNote', ref(null))

const scope = ref('global')
const depth = ref(2)
const noteId = computed(() => scope.value === 'local' ? currentNote.value?.id : null)

const { data: graphData } = useNoteGraph(scope, noteId, depth)

function onSelectNote(node) {
    router.push({ name: 'note', params: { slug: node.slug } })
}
</script>
