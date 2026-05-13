<template>
    <div class="tag-picker">
        <!-- Selected chips -->
        <div class="flex flex-wrap items-center gap-1.5">
            <TagChip
                v-for="tag in selected"
                :key="tag.id"
                :tag="tag"
                size="sm"
                removable
                @remove="detach(tag)"
            />
            <button
                type="button"
                class="tag-picker-add inline-flex items-center gap-1 rounded-full border border-white/10 bg-white/5 px-2 py-1 text-[10.5px] text-surface-300 hover:bg-white/10 hover:text-white transition-colors"
                @click="openPicker"
                ref="anchorEl"
            >
                <PlusIcon class="w-3 h-3" />
                {{ selected.length ? 'Añadir' : 'Etiquetas' }}
            </button>
        </div>

        <!-- Popover -->
        <Teleport to="body">
            <div
                v-if="open"
                class="tag-picker-popover liquid-glass liquid-glass-panel rounded-xl shadow-xl"
                :style="popoverStyle"
                @click.stop
            >
                <div class="px-3 pt-3 pb-2">
                    <input
                        ref="searchEl"
                        v-model="query"
                        type="text"
                        placeholder="Buscar o crear etiqueta…"
                        class="w-full bg-white/5 border border-white/10 rounded-md px-2.5 py-1.5 text-xs text-white placeholder:text-surface-500 focus:outline-none focus:ring-1 focus:ring-primary-500/40"
                        @keydown.enter.prevent="onEnter"
                        @keydown.escape="close"
                        @keydown.down.prevent="moveCursor(1)"
                        @keydown.up.prevent="moveCursor(-1)"
                    />
                </div>
                <div class="tag-picker-list max-h-56 overflow-y-auto px-1.5 pb-2 glass-scroll">
                    <button
                        v-for="(tag, i) in matches"
                        :key="tag.id"
                        type="button"
                        class="tag-picker-row w-full flex items-center gap-2 px-2 py-1.5 rounded-md text-left text-xs hover:bg-white/[0.06] transition-colors"
                        :class="{
                            'bg-white/[0.06]': i === cursor,
                            'opacity-50': isSelected(tag.id),
                        }"
                        @click="toggle(tag)"
                    >
                        <span class="w-2 h-2 rounded-full flex-shrink-0" :style="{ background: tag.color }" />
                        <span class="flex-1 truncate text-surface-100">{{ tag.name }}</span>
                        <CheckIcon v-if="isSelected(tag.id)" class="w-3.5 h-3.5 text-primary-400" />
                    </button>
                    <button
                        v-if="canCreate"
                        type="button"
                        class="tag-picker-row tag-picker-create w-full flex items-center gap-2 px-2 py-1.5 rounded-md text-left text-xs hover:bg-white/[0.06] transition-colors mt-0.5"
                        :class="{ 'bg-white/[0.06]': cursor === matches.length }"
                        @click="createAndAttach"
                    >
                        <PlusIcon class="w-3.5 h-3.5 text-primary-400" />
                        <span class="text-primary-300">Crear "{{ query.trim() }}"</span>
                    </button>
                    <p
                        v-if="!matches.length && !canCreate"
                        class="px-2 py-3 text-center text-[10.5px] text-surface-500"
                    >
                        No hay etiquetas.
                    </p>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, computed, watch, nextTick, onMounted, onBeforeUnmount } from 'vue'
import { PlusIcon, CheckIcon } from '@heroicons/vue/24/outline'
import TagChip from './TagChip.vue'
import { useTags, useCreateTag, useAttachTags, useDetachTags } from '@/composables/useTags'

const props = defineProps({
    targetType: { type: String, required: true }, // 'board' | 'task'
    targetId: { type: Number, required: true },
    boardId: { type: Number, default: null },
    modelValue: { type: Array, default: () => [] }, // current tags on the target
})

const emit = defineEmits(['update:modelValue', 'change'])

const { data: allTagsData } = useTags()
const createTag = useCreateTag()
const attachTags = useAttachTags()
const detachTags = useDetachTags()

const selected = computed(() => props.modelValue ?? [])
const allTags = computed(() => allTagsData.value?.data ?? [])

const open = ref(false)
const query = ref('')
const cursor = ref(0)
const anchorEl = ref(null)
const searchEl = ref(null)
const popoverStyle = ref({})

const matches = computed(() => {
    const q = query.value.trim().toLowerCase()
    if (!q) return allTags.value
    return allTags.value.filter(t => t.name.toLowerCase().includes(q))
})

const canCreate = computed(() => {
    const q = query.value.trim()
    if (!q) return false
    return !allTags.value.some(t => t.name.toLowerCase() === q.toLowerCase())
})

function isSelected(id) {
    return selected.value.some(t => t.id === id)
}

async function openPicker() {
    if (!anchorEl.value) return
    const rect = anchorEl.value.getBoundingClientRect()
    popoverStyle.value = {
        position: 'fixed',
        top: `${rect.bottom + 6}px`,
        left: `${rect.left}px`,
        minWidth: '240px',
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
    const total = matches.value.length + (canCreate.value ? 1 : 0)
    if (total === 0) return
    cursor.value = (cursor.value + delta + total) % total
}

async function onEnter() {
    if (cursor.value < matches.value.length) {
        await toggle(matches.value[cursor.value])
    } else if (canCreate.value) {
        await createAndAttach()
    }
}

async function toggle(tag) {
    if (isSelected(tag.id)) {
        await detach(tag)
    } else {
        await attach([tag.id], [])
    }
}

async function createAndAttach() {
    const name = query.value.trim()
    if (!name) return
    const created = await createTag.mutateAsync({ name })
    await attach([created.data.id], [])
    query.value = ''
}

async function attach(tagIds, tagNames) {
    const res = await attachTags.mutateAsync({
        target_type: props.targetType,
        target_id: props.targetId,
        tag_ids: tagIds,
        tag_names: tagNames,
        boardId: props.boardId,
    })
    emit('update:modelValue', res.data || [])
    emit('change', res.data || [])
}

async function detach(tag) {
    const res = await detachTags.mutateAsync({
        target_type: props.targetType,
        target_id: props.targetId,
        tag_ids: [tag.id],
        boardId: props.boardId,
    })
    emit('update:modelValue', res.data || [])
    emit('change', res.data || [])
}

function onDocClick(e) {
    if (!open.value) return
    const pop = document.querySelector('.tag-picker-popover')
    if (pop?.contains(e.target)) return
    if (anchorEl.value?.contains(e.target)) return
    close()
}

watch(() => allTags.value.length, () => { cursor.value = 0 })
watch(query, () => { cursor.value = 0 })

onMounted(() => document.addEventListener('click', onDocClick))
onBeforeUnmount(() => document.removeEventListener('click', onDocClick))
</script>
