<template>
    <div>
        <div class="file-item" @click="$emit('select', tag)">
            <HashtagIcon class="w-3.5 h-3.5 text-primary-400 flex-shrink-0" />
            <span class="truncate text-sm text-surface-300 flex-1">{{ tag.name }}</span>
            <span v-if="tag.notes_count" class="text-[0.625rem] text-surface-600 flex-shrink-0">{{ tag.notes_count }}</span>
        </div>
        <div v-if="tag.children?.length" class="ml-3 border-l border-white/[0.04] pl-1.5 space-y-0.5">
            <TagNode
                v-for="child in tag.children"
                :key="child.id"
                :tag="child"
                @select="$emit('select', $event)"
            />
        </div>
    </div>
</template>

<script setup>
import { HashtagIcon } from '@heroicons/vue/24/outline'

defineProps({
    tag: { type: Object, required: true },
})

defineEmits(['select'])
</script>
