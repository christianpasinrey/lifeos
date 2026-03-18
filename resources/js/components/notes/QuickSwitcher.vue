<template>
    <Teleport to="body">
        <div class="quick-switcher-overlay" @click.self="$emit('close')">
            <div class="quick-switcher liquid-glass liquid-glass-card">
                <div class="flex items-center gap-2 px-4 py-3 border-b border-white/[0.06]">
                    <MagnifyingGlassIcon class="w-4 h-4 text-surface-400 flex-shrink-0" />
                    <input
                        ref="input"
                        v-model="query"
                        class="bg-transparent border-none outline-none text-sm text-surface-100 w-full placeholder:text-surface-600"
                        placeholder="Buscar nota por título..."
                        @keydown.escape="$emit('close')"
                        @keydown.down.prevent="moveDown"
                        @keydown.up.prevent="moveUp"
                        @keydown.enter.prevent="selectCurrent"
                    />
                </div>

                <div class="max-h-64 overflow-y-auto glass-scroll">
                    <div v-if="results.length" class="py-1">
                        <button
                            v-for="(note, i) in results"
                            :key="note.id"
                            class="w-full flex items-center gap-2 px-4 py-2 text-left text-sm transition-colors"
                            :class="i === selectedIndex ? 'bg-primary-500/10 text-primary-300' : 'text-surface-300 hover:bg-white/5'"
                            @click="select(note)"
                        >
                            <DocumentTextIcon class="w-4 h-4 flex-shrink-0 text-surface-500" />
                            <span class="truncate">{{ note.title }}</span>
                            <span v-if="note.folder" class="text-xs text-surface-600 flex-shrink-0 ml-auto">{{ note.folder.name }}</span>
                        </button>
                    </div>
                    <div v-else-if="query" class="px-4 py-6 text-center text-xs text-surface-600">
                        Sin resultados
                    </div>
                    <div v-else class="px-4 py-6 text-center text-xs text-surface-600">
                        Escribe para buscar...
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import { useNoteSearch } from '@/composables/useNoteSearch'
import { MagnifyingGlassIcon, DocumentTextIcon } from '@heroicons/vue/24/outline'

const emit = defineEmits(['close', 'select'])

const input = ref(null)
const selectedIndex = ref(0)
const { query, data } = useNoteSearch()

const results = computed(() => data.value?.data ?? [])

function moveDown() {
    if (selectedIndex.value < results.value.length - 1) selectedIndex.value++
}

function moveUp() {
    if (selectedIndex.value > 0) selectedIndex.value--
}

function selectCurrent() {
    const note = results.value[selectedIndex.value]
    if (note) select(note)
}

function select(note) {
    emit('select', note)
    emit('close')
}

onMounted(() => nextTick(() => input.value?.focus()))
</script>
