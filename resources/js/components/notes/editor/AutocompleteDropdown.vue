<template>
    <Teleport to="body">
        <div
            v-if="visible"
            class="autocomplete-dropdown liquid-glass liquid-glass-card"
            :style="{ top: position.top + 'px', left: position.left + 'px' }"
        >
            <div v-if="loading" class="px-3 py-2 text-xs text-surface-500">Buscando...</div>
            <template v-else>
                <button
                    v-for="(item, i) in items"
                    :key="item.id || item.full_path || i"
                    class="autocomplete-item"
                    :class="{ active: i === selectedIndex }"
                    @mousedown.prevent="select(item)"
                >
                    <component :is="type === 'tag' ? HashtagIcon : DocumentTextIcon" class="w-3.5 h-3.5 text-surface-500 flex-shrink-0" />
                    <span class="truncate text-sm text-surface-300">{{ item.title || item.full_path || item.name }}</span>
                </button>
                <div v-if="!items.length && query" class="px-3 py-2 text-xs text-surface-600">Sin resultados</div>
            </template>
        </div>
    </Teleport>
</template>

<script setup>
import { ref } from 'vue'
import { DocumentTextIcon, HashtagIcon } from '@heroicons/vue/24/outline'
import api from '@/composables/useApi'

const visible = ref(false)
const position = ref({ top: 0, left: 0 })
const type = ref('note') // 'note' or 'tag'
const query = ref('')
const items = ref([])
const loading = ref(false)
const selectedIndex = ref(0)

let searchTimer = null

async function search(q) {
    query.value = q
    clearTimeout(searchTimer)
    if (!q) { items.value = []; return }

    searchTimer = setTimeout(async () => {
        loading.value = true
        try {
            if (type.value === 'note') {
                const res = await api.get('/notes/search', { params: { q } })
                items.value = res.data?.data ?? []
            } else {
                const res = await api.get('/note-tags')
                items.value = (res.data?.data ?? []).filter(t =>
                    t.full_path.toLowerCase().includes(q.toLowerCase())
                )
            }
        } catch { items.value = [] }
        loading.value = false
    }, 150)
}

function show(pos, searchType) {
    position.value = pos
    type.value = searchType
    visible.value = true
    selectedIndex.value = 0
    items.value = []
    query.value = ''
}

function hide() {
    visible.value = false
}

function select(item) {
    if (type.value === 'note') {
        document.execCommand('insertText', false, `${item.title}]]`)
    } else {
        document.execCommand('insertText', false, `${item.full_path || item.name}`)
    }
    hide()
}

function handleKeydown(e) {
    if (!visible.value) return false
    if (e.key === 'ArrowDown') { e.preventDefault(); selectedIndex.value = Math.min(selectedIndex.value + 1, items.value.length - 1); return true }
    if (e.key === 'ArrowUp') { e.preventDefault(); selectedIndex.value = Math.max(selectedIndex.value - 1, 0); return true }
    if (e.key === 'Enter') { e.preventDefault(); select(items.value[selectedIndex.value]); return true }
    if (e.key === 'Escape') { e.preventDefault(); hide(); return true }
    return false
}

defineExpose({ show, hide, search, handleKeydown, visible })
</script>
