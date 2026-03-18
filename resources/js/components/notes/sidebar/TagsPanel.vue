<template>
    <div class="tags-panel">
        <div class="px-2 py-1.5 mb-1">
            <span class="text-[0.625rem] font-semibold text-surface-500 uppercase tracking-wider">Tags</span>
        </div>

        <div v-if="tagsData?.data?.length" class="space-y-0.5">
            <TagNode
                v-for="tag in tagsData.data"
                :key="tag.id"
                :tag="tag"
                @select="$emit('select-tag', $event)"
            />
        </div>

        <div v-else class="px-2 py-4 text-center">
            <p class="text-xs text-surface-600">Sin tags</p>
        </div>
    </div>
</template>

<script setup>
import { useNoteTags } from '@/composables/useNoteTags'
import TagNode from './TagNode.vue'

const emit = defineEmits(['select-tag'])

const { data: tagsData } = useNoteTags()
</script>
