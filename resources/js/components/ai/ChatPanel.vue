<template>
    <div class="flex flex-col h-full">
        <!-- Header -->
        <div class="flex items-center justify-between px-4 py-3 border-b border-white/[0.06]">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-primary-500/10 flex items-center justify-center">
                    <SparklesIcon class="w-4 h-4 text-primary-400" />
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-white">Coach</h3>
                    <p class="text-xs text-surface-400">Tu asistente personal</p>
                </div>
            </div>
            <button @click="$emit('close')" class="p-1.5 text-surface-400 hover:text-surface-200 rounded-lg hover:bg-white/[0.06] transition-colors">
                <XMarkIcon class="w-5 h-5" />
            </button>
        </div>

        <!-- Messages -->
        <div ref="messagesContainer" class="flex-1 overflow-y-auto glass-scroll p-4 space-y-4">
            <div v-if="messages.length === 0" class="text-center py-8">
                <SparklesIcon class="w-10 h-10 text-surface-600 mx-auto mb-3" />
                <p class="text-surface-400 text-sm">Pregúntame sobre tus hábitos, pídeme crear uno nuevo, o dime qué quieres mejorar.</p>
            </div>

            <div
                v-for="(msg, i) in messages"
                :key="i"
                class="flex"
                :class="msg.role === 'user' ? 'justify-end' : 'justify-start'"
            >
                <div
                    class="max-w-[85%] px-3.5 py-2.5 rounded-2xl text-sm leading-relaxed"
                    :class="msg.role === 'user'
                        ? 'bg-primary-600/80 text-white rounded-br-md backdrop-blur-sm'
                        : 'bg-white/[0.06] text-surface-200 rounded-bl-md backdrop-blur-sm'"
                >
                    <div v-html="formatMessage(msg.content)" />
                </div>
            </div>

            <div v-if="loading" class="flex justify-start">
                <div class="bg-white/[0.06] text-surface-400 px-3.5 py-2.5 rounded-2xl rounded-bl-md text-sm">
                    <span class="inline-flex gap-1">
                        <span class="w-1.5 h-1.5 bg-surface-400 rounded-full animate-bounce" style="animation-delay: 0ms" />
                        <span class="w-1.5 h-1.5 bg-surface-400 rounded-full animate-bounce" style="animation-delay: 150ms" />
                        <span class="w-1.5 h-1.5 bg-surface-400 rounded-full animate-bounce" style="animation-delay: 300ms" />
                    </span>
                </div>
            </div>
        </div>

        <!-- Input -->
        <form @submit.prevent="sendMessage" class="p-3 border-t border-white/[0.06]">
            <div class="flex gap-2">
                <input
                    v-model="input"
                    type="text"
                    :disabled="loading"
                    placeholder="Escribe un mensaje..."
                    class="flex-1 px-3.5 py-2.5 bg-white/[0.05] border border-white/[0.08] rounded-xl text-sm text-surface-100 placeholder-surface-500 focus:outline-none focus:ring-2 focus:ring-primary-500/50 focus:border-transparent backdrop-blur-sm transition-colors"
                    @keydown.enter.prevent="sendMessage"
                />
                <button
                    type="submit"
                    :disabled="loading || !input.trim()"
                    class="px-3.5 py-2.5 bg-primary-600/80 hover:bg-primary-500/80 disabled:opacity-50 text-white rounded-xl transition-colors backdrop-blur-sm"
                >
                    <PaperAirplaneIcon class="w-4 h-4" />
                </button>
            </div>
        </form>
    </div>
</template>

<script setup>
import { ref, nextTick } from 'vue'
import api from '@/composables/useApi'
import { useQueryClient } from '@tanstack/vue-query'
import { SparklesIcon, XMarkIcon, PaperAirplaneIcon } from '@heroicons/vue/24/outline'

defineEmits(['close'])

const queryClient = useQueryClient()
const messages = ref([])
const input = ref('')
const loading = ref(false)
const messagesContainer = ref(null)

function formatMessage(text) {
    return text
        .replace(/\n/g, '<br>')
        .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
        .replace(/\*(.*?)\*/g, '<em>$1</em>')
}

async function scrollToBottom() {
    await nextTick()
    if (messagesContainer.value) {
        messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
    }
}

async function sendMessage() {
    const text = input.value.trim()
    if (!text || loading.value) return

    messages.value.push({ role: 'user', content: text })
    input.value = ''
    loading.value = true
    scrollToBottom()

    try {
        const { data } = await api.post('/ai/chat', { message: text })
        messages.value.push({ role: 'assistant', content: data.text })
        queryClient.invalidateQueries({ queryKey: ['habits'] })
    } catch (e) {
        messages.value.push({
            role: 'assistant',
            content: 'Lo siento, hubo un error. Intenta de nuevo.',
        })
    } finally {
        loading.value = false
        scrollToBottom()
    }
}
</script>
