<template>
    <button class="btn-add" @click="showGenerator = true">
        <SparklesIcon class="w-4 h-4" />
        Generar con IA
    </button>

    <TaskGeneratorModal
        v-if="showGenerator"
        :board-id="boardId"
        :columns="columns"
        @close="showGenerator = false"
        @saved="showGenerator = false"
    />
</template>

<script setup>
import { ref } from 'vue'
import { SparklesIcon } from '@heroicons/vue/24/outline'
import TaskGeneratorModal from '@/components/tasks/TaskGeneratorModal.vue'

defineProps({
    boardId: { type: Number, required: true },
    columns: { type: Array, required: true },
})

const showGenerator = ref(false)
</script>
