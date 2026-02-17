<template>
    <div class="px-3 pb-2">
        <button
            @click="showChat = !showChat"
            class="w-full nav-link"
            :class="showChat ? 'nav-link-active' : ''"
        >
            <ChatBubbleLeftRightIcon class="w-5 h-5" />
            Coach IA
        </button>
    </div>

    <!-- Chat panel (fixed overlay) -->
    <Teleport to=".layout-root">
        <Transition name="slide-right">
            <aside v-if="showChat" class="chat-aside">
                <div class="liquid-glass liquid-glass-panel h-full flex flex-col rounded-[20px]">
                    <ChatPanel @close="showChat = false" />
                </div>
            </aside>
        </Transition>
    </Teleport>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { ChatBubbleLeftRightIcon } from '@heroicons/vue/24/outline'
import ChatPanel from '@/components/ai/ChatPanel.vue'

const showChat = ref(false)

function openCoachPanel() {
    showChat.value = true
}

onMounted(() => window.addEventListener('open-coach-chat', openCoachPanel))
onUnmounted(() => window.removeEventListener('open-coach-chat', openCoachPanel))
</script>
