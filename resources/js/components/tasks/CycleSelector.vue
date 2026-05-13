<template>
    <div class="cycle-selector">
        <!-- Trigger -->
        <button
            ref="anchorEl"
            type="button"
            class="cycle-selector-trigger"
            @click="openPicker"
        >
            <template v-if="selectedCycle">
                <span class="cycle-selector-dot" :style="{ background: selectedCycle.color }" />
                <span class="cycle-selector-name">{{ selectedCycle.name }}</span>
                <span class="cycle-selector-status" :class="`cycle-status-${selectedCycle.status}`">
                    {{ statusLabel(selectedCycle.status) }}
                </span>
                <button
                    type="button"
                    class="cycle-selector-clear"
                    title="Quitar cycle"
                    @click.stop="clear"
                >
                    <XMarkIcon class="w-3 h-3" />
                </button>
            </template>
            <template v-else>
                <ArrowPathIcon class="w-3.5 h-3.5 text-surface-500" />
                <span class="cycle-selector-placeholder">Sin cycle</span>
                <ChevronDownIcon class="w-3 h-3 text-surface-500 ml-auto" />
            </template>
        </button>

        <!-- Popover -->
        <Teleport to="body">
            <div
                v-if="open"
                class="cycle-picker-popover liquid-glass liquid-glass-panel"
                :style="popoverStyle"
                @click.stop
            >
                <div class="cycle-picker-search">
                    <input
                        ref="searchEl"
                        v-model="query"
                        type="text"
                        placeholder="Buscar cycle…"
                        class="cycle-picker-input"
                        @keydown.enter.prevent="onEnter"
                        @keydown.escape="close"
                        @keydown.down.prevent="moveCursor(1)"
                        @keydown.up.prevent="moveCursor(-1)"
                    />
                </div>
                <div class="cycle-picker-list glass-scroll">
                    <button
                        v-if="!query.trim()"
                        type="button"
                        class="cycle-picker-row cycle-picker-row-clear"
                        :class="{ 'cycle-picker-row-active': cursor === -1 }"
                        @click="clear"
                    >
                        <XMarkIcon class="w-3.5 h-3.5 text-surface-500" />
                        <span>— Sin cycle —</span>
                        <CheckIcon v-if="!modelValue" class="w-3.5 h-3.5 text-primary-400 ml-auto" />
                    </button>
                    <button
                        v-for="(cycle, i) in matches"
                        :key="cycle.id"
                        type="button"
                        class="cycle-picker-row"
                        :class="{ 'cycle-picker-row-active': i === cursor }"
                        @click="select(cycle.id)"
                    >
                        <span class="cycle-picker-dot" :style="{ background: cycle.color }" />
                        <span class="cycle-picker-name">{{ cycle.name }}</span>
                        <span class="cycle-picker-meta" :class="`cycle-status-${cycle.status}`">
                            {{ statusLabel(cycle.status) }}
                        </span>
                        <CheckIcon v-if="cycle.id === modelValue" class="w-3.5 h-3.5 text-primary-400" />
                    </button>
                    <p
                        v-if="!matches.length && query.trim()"
                        class="cycle-picker-empty"
                    >
                        Sin resultados.
                    </p>
                </div>
                <div class="cycle-picker-footer">
                    <button
                        type="button"
                        class="cycle-picker-create"
                        @click="openCreate"
                    >
                        <PlusIcon class="w-3.5 h-3.5" />
                        Crear nuevo cycle{{ query.trim() ? ` "${query.trim()}"` : '' }}…
                    </button>
                </div>
            </div>
        </Teleport>

        <!-- Inline create modal -->
        <CycleModal
            v-if="creating"
            :board-id="boardId"
            :initial-name="query.trim()"
            @close="creating = false"
            @created="onCycleCreated"
        />
    </div>
</template>

<script setup>
import { ref, computed, watch, nextTick, onMounted, onBeforeUnmount } from 'vue'
import {
    ArrowPathIcon, ChevronDownIcon, CheckIcon, PlusIcon, XMarkIcon,
} from '@heroicons/vue/24/outline'
import { useCycles } from '@/composables/useCycles'
import CycleModal from './CycleModal.vue'

const props = defineProps({
    boardId: { type: Number, required: true },
    modelValue: { type: Number, default: null },
})

const emit = defineEmits(['update:modelValue', 'change'])

const { data: cyclesData } = useCycles(() => props.boardId)
const cycles = computed(() => cyclesData.value?.data ?? [])

const selectedCycle = computed(() =>
    props.modelValue ? cycles.value.find(c => c.id === props.modelValue) || null : null,
)

const open = ref(false)
const creating = ref(false)
const query = ref('')
const cursor = ref(0)
const anchorEl = ref(null)
const searchEl = ref(null)
const popoverStyle = ref({})

const matches = computed(() => {
    const q = query.value.trim().toLowerCase()
    if (!q) return cycles.value
    return cycles.value.filter(c => c.name.toLowerCase().includes(q))
})

function statusLabel(s) {
    return { planned: 'Planificado', active: 'Activo', completed: 'Completado' }[s] || s
}

async function openPicker() {
    if (!anchorEl.value) return
    const rect = anchorEl.value.getBoundingClientRect()
    popoverStyle.value = {
        position: 'fixed',
        top: `${rect.bottom + 6}px`,
        left: `${rect.left}px`,
        minWidth: `${Math.max(rect.width, 260)}px`,
        zIndex: 9999,
    }
    open.value = true
    query.value = ''
    cursor.value = 0
    await nextTick()
    searchEl.value?.focus()
}

function close() {
    open.value = false
}

function moveCursor(delta) {
    const total = matches.value.length
    if (total === 0) return
    cursor.value = (cursor.value + delta + total) % total
}

function onEnter() {
    if (cursor.value >= 0 && matches.value[cursor.value]) {
        select(matches.value[cursor.value].id)
    } else if (query.value.trim()) {
        openCreate()
    }
}

function select(id) {
    emit('update:modelValue', id)
    emit('change', id)
    close()
}

function clear() {
    emit('update:modelValue', null)
    emit('change', null)
    close()
}

function openCreate() {
    close()
    creating.value = true
}

function onCycleCreated(cycle) {
    creating.value = false
    if (cycle?.id) {
        emit('update:modelValue', cycle.id)
        emit('change', cycle.id)
    }
}

function onDocClick(e) {
    if (!open.value) return
    const pop = document.querySelector('.cycle-picker-popover')
    if (pop?.contains(e.target)) return
    if (anchorEl.value?.contains(e.target)) return
    close()
}

watch(query, () => { cursor.value = 0 })

onMounted(() => document.addEventListener('click', onDocClick))
onBeforeUnmount(() => document.removeEventListener('click', onDocClick))
</script>

<style scoped>
.cycle-selector { width: 100%; }

.cycle-selector-trigger {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    width: 100%;
    padding: 0.5rem 0.625rem;
    border-radius: 0.5rem;
    border: 1px solid rgba(255, 255, 255, 0.08);
    background-color: rgba(255, 255, 255, 0.02);
    color: rgb(226, 232, 240);
    font-size: 0.8125rem;
    text-align: left;
    cursor: pointer;
    transition: background-color 0.15s, border-color 0.15s;
}
.cycle-selector-trigger:hover {
    background-color: rgba(255, 255, 255, 0.04);
    border-color: rgba(255, 255, 255, 0.12);
}

.cycle-selector-placeholder { color: rgb(148, 163, 184); font-size: 0.8125rem; }

.cycle-selector-dot {
    width: 0.5rem;
    height: 0.5rem;
    border-radius: 9999px;
    flex-shrink: 0;
}

.cycle-selector-name {
    font-weight: 500;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    max-width: 140px;
}

.cycle-selector-status {
    font-size: 10px;
    padding: 0.125rem 0.4375rem;
    border-radius: 9999px;
    margin-left: 0.25rem;
    line-height: 1;
    font-weight: 600;
}

.cycle-selector-clear {
    margin-left: auto;
    padding: 0.25rem;
    border-radius: 9999px;
    color: rgb(148, 163, 184);
    opacity: 0.7;
    transition: opacity 0.15s, color 0.15s, background-color 0.15s;
}
.cycle-selector-clear:hover {
    opacity: 1;
    color: rgb(248, 113, 113);
    background-color: rgba(248, 113, 113, 0.1);
}

.cycle-status-planned { background-color: rgba(148, 163, 184, 0.2); color: rgb(203, 213, 225); }
.cycle-status-active { background-color: rgba(16, 185, 129, 0.2); color: rgb(110, 231, 183); }
.cycle-status-completed { background-color: rgba(99, 102, 241, 0.2); color: rgb(165, 180, 252); }

.cycle-picker-popover {
    border-radius: 0.75rem;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
    overflow: hidden;
}
.cycle-picker-search { padding: 0.625rem 0.625rem 0.5rem; }
.cycle-picker-input {
    width: 100%;
    padding: 0.4375rem 0.625rem;
    border-radius: 0.375rem;
    border: 1px solid rgba(255, 255, 255, 0.1);
    background-color: rgba(255, 255, 255, 0.04);
    color: white;
    font-size: 0.75rem;
    outline: none;
}
.cycle-picker-input::placeholder { color: rgb(100, 116, 139); }
.cycle-picker-input:focus { border-color: rgba(96, 165, 250, 0.4); }

.cycle-picker-list {
    max-height: 240px;
    overflow-y: auto;
    padding: 0 0.375rem 0.375rem;
}

.cycle-picker-row {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    width: 100%;
    padding: 0.4375rem 0.5rem;
    border-radius: 0.375rem;
    text-align: left;
    font-size: 0.75rem;
    color: rgb(226, 232, 240);
    cursor: pointer;
    background: transparent;
    transition: background-color 0.15s;
}
.cycle-picker-row:hover,
.cycle-picker-row-active { background-color: rgba(255, 255, 255, 0.06); }

.cycle-picker-row-clear {
    color: rgb(148, 163, 184);
    margin-bottom: 0.25rem;
}

.cycle-picker-dot {
    width: 0.5rem;
    height: 0.5rem;
    border-radius: 9999px;
    flex-shrink: 0;
}

.cycle-picker-name { flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

.cycle-picker-meta {
    font-size: 10px;
    padding: 0.125rem 0.4375rem;
    border-radius: 9999px;
    line-height: 1;
    font-weight: 600;
}

.cycle-picker-empty {
    padding: 1rem 0.5rem;
    text-align: center;
    font-size: 11px;
    color: rgb(100, 116, 139);
}

.cycle-picker-footer {
    border-top: 1px solid rgba(255, 255, 255, 0.06);
    padding: 0.375rem;
}

.cycle-picker-create {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    width: 100%;
    padding: 0.5rem 0.625rem;
    border-radius: 0.375rem;
    font-size: 0.75rem;
    color: rgb(165, 180, 252);
    background: transparent;
    cursor: pointer;
    transition: background-color 0.15s;
}
.cycle-picker-create:hover { background-color: rgba(99, 102, 241, 0.1); }
</style>
