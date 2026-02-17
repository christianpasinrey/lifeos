<template>
    <div class="liquid-glass liquid-glass-card glass-card cursor-pointer" @click="openCoach">
        <div class="flex items-center gap-3 mb-3">
            <div class="stat-icon-wrap bg-accent-500/10">
                <ChatBubbleLeftRightIcon class="w-5 h-5 text-accent-400" />
            </div>
            <span class="stat-label">Coach AI</span>
        </div>
        <p class="text-sm text-surface-300">Pregúntale al coach sobre tus hábitos</p>
    </div>
</template>

<script setup>
import { ChatBubbleLeftRightIcon } from '@heroicons/vue/24/outline'

function openCoach() {
    window.dispatchEvent(new CustomEvent('open-coach-chat'))
}
</script>
