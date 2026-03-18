<template>
    <Teleport to="body">
        <div v-if="visible" class="context-menu-overlay" @click="close" @contextmenu.prevent="close">
            <div
                ref="menuEl"
                class="context-menu liquid-glass liquid-glass-card"
                :style="{ top: position.top + 'px', left: position.left + 'px' }"
                @click.stop
            >
                <button
                    v-for="item in items"
                    :key="item.id"
                    class="context-menu-item"
                    :class="{ 'context-menu-danger': item.danger }"
                    @click="execute(item)"
                >
                    <component :is="item.icon" class="w-3.5 h-3.5 flex-shrink-0" />
                    <span>{{ item.label }}</span>
                </button>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { ref, nextTick } from 'vue'

const visible = ref(false)
const position = ref({ top: 0, left: 0 })
const items = ref([])
const menuEl = ref(null)

let currentCallback = null

function show(event, menuItems, callback) {
    event.preventDefault()
    items.value = menuItems
    currentCallback = callback
    visible.value = true

    nextTick(() => {
        // Adjust position to stay within viewport
        const el = menuEl.value
        let top = event.clientY
        let left = event.clientX

        if (el) {
            const rect = el.getBoundingClientRect()
            if (top + rect.height > window.innerHeight) top = window.innerHeight - rect.height - 8
            if (left + rect.width > window.innerWidth) left = window.innerWidth - rect.width - 8
        }

        position.value = { top, left }
    })
}

function close() {
    visible.value = false
    currentCallback = null
}

function execute(item) {
    const cb = currentCallback
    close()
    cb?.(item.id)
}

defineExpose({ show, close })
</script>
