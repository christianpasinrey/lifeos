<template>
    <button
        class="px-4 py-2 rounded-xl text-sm font-medium bg-primary-500/20 text-primary-100 hover:bg-primary-500/30 transition"
        @click="showAnalysis = true"
    >
        Analizar con IA
    </button>

    <FinanceAnalysisModal
        v-if="showAnalysis"
        :initial-start-date="startDate"
        :initial-end-date="endDate"
        @close="showAnalysis = false"
    />
</template>

<script setup>
import { ref } from 'vue'
import FinanceAnalysisModal from '@/components/finance/FinanceAnalysisModal.vue'

defineProps({
    startDate: { type: String, default: '' },
    endDate: { type: String, default: '' },
})

const showAnalysis = ref(false)
</script>
