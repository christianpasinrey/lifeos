<template>
    <Teleport to="body">
        <div
            v-if="visible"
            class="slash-menu liquid-glass liquid-glass-card"
            :style="{ top: position.top + 'px', left: position.left + 'px' }"
        >
            <button
                v-for="(cmd, i) in filteredCommands"
                :key="cmd.id"
                class="slash-menu-item"
                :class="{ active: i === selectedIndex }"
                @mousedown.prevent="execute(cmd)"
            >
                <span class="slash-menu-icon">{{ cmd.icon }}</span>
                <span class="slash-menu-label">{{ cmd.label }}</span>
            </button>
        </div>
    </Teleport>
</template>

<script setup>
import { ref, computed } from 'vue'

const visible = ref(false)
const position = ref({ top: 0, left: 0 })
const filter = ref('')
const selectedIndex = ref(0)

const commands = [
    { id: 'h1', icon: 'H1', label: 'Heading 1', insert: '# ' },
    { id: 'h2', icon: 'H2', label: 'Heading 2', insert: '## ' },
    { id: 'h3', icon: 'H3', label: 'Heading 3', insert: '### ' },
    { id: 'callout', icon: '💡', label: 'Callout', insert: '> [!TIP] \n> ' },
    { id: 'code', icon: '<>', label: 'Code Block', insert: '```\n\n```' },
    { id: 'table', icon: '▦', label: 'Table', insert: '| Col 1 | Col 2 |\n| --- | --- |\n| | |' },
    { id: 'divider', icon: '—', label: 'Divider', insert: '---' },
    { id: 'checkbox', icon: '☐', label: 'Checkbox', insert: '- [ ] ' },
    { id: 'bullet', icon: '•', label: 'Bullet List', insert: '- ' },
    { id: 'math', icon: '∑', label: 'Math Block', insert: '$$\n\n$$' },
]

const filteredCommands = computed(() => {
    if (!filter.value) return commands
    return commands.filter(c => c.label.toLowerCase().includes(filter.value.toLowerCase()))
})

function show(pos) {
    position.value = pos
    visible.value = true
    filter.value = ''
    selectedIndex.value = 0
}

function hide() {
    visible.value = false
}

function execute(cmd) {
    document.execCommand('insertText', false, cmd.insert)
    hide()
}

function handleKeydown(e) {
    if (!visible.value) return false
    if (e.key === 'ArrowDown') { e.preventDefault(); selectedIndex.value = Math.min(selectedIndex.value + 1, filteredCommands.value.length - 1); return true }
    if (e.key === 'ArrowUp') { e.preventDefault(); selectedIndex.value = Math.max(selectedIndex.value - 1, 0); return true }
    if (e.key === 'Enter') { e.preventDefault(); execute(filteredCommands.value[selectedIndex.value]); return true }
    if (e.key === 'Escape') { e.preventDefault(); hide(); return true }
    return false
}

defineExpose({ show, hide, handleKeydown, visible })
</script>
