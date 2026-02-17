<template>
    <div class="flex flex-col h-full">
        <!-- Header -->
        <div class="chat-header">
            <div class="flex items-center gap-2">
                <button
                    v-if="view === 'chat'"
                    @click="goToList"
                    class="p-1 -ml-1 rounded-lg text-surface-400 hover:text-white hover:bg-white/5 transition-colors"
                >
                    <ChevronLeftIcon class="w-5 h-5" />
                </button>
                <div class="w-8 h-8 rounded-lg bg-primary-500/10 flex items-center justify-center">
                    <SparklesIcon class="w-4 h-4 text-primary-400" />
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-white">Coach</h3>
                    <p class="text-xs text-surface-400">
                        {{ view === 'list' ? 'Conversaciones' : (currentTitle || 'Nueva conversación') }}
                    </p>
                </div>
            </div>
            <button @click="$emit('close')" class="btn-close">
                <XMarkIcon class="w-5 h-5" />
            </button>
        </div>

        <!-- List View -->
        <template v-if="view === 'list'">
            <div class="conversation-list glass-scroll">
                <!-- New conversation button -->
                <button @click="startNewConversation" class="conversation-new-btn">
                    <PlusIcon class="w-4 h-4" />
                    <span>Nueva conversación</span>
                </button>

                <!-- Loading -->
                <div v-if="isLoadingConversations" class="chat-empty">
                    <span class="inline-flex gap-1">
                        <span class="loading-dot" style="animation-delay: 0ms" />
                        <span class="loading-dot" style="animation-delay: 150ms" />
                        <span class="loading-dot" style="animation-delay: 300ms" />
                    </span>
                </div>

                <!-- Empty state -->
                <div v-else-if="!conversations?.length" class="chat-empty">
                    <ChatBubbleLeftRightIcon class="w-10 h-10 text-surface-600 mx-auto mb-3" />
                    <p class="text-surface-400 text-sm">Aún no tienes conversaciones.</p>
                    <p class="text-surface-500 text-xs mt-1">Inicia una nueva para hablar con tu Coach.</p>
                </div>

                <!-- Conversation items -->
                <div
                    v-for="conv in conversations"
                    :key="conv.id"
                    class="conversation-item group"
                    @click="openConversation(conv)"
                >
                    <div class="conversation-item-icon">
                        <ChatBubbleLeftIcon class="w-4 h-4 text-surface-400" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-surface-200 truncate">{{ conv.title || 'Sin título' }}</p>
                        <p class="text-xs text-surface-500">{{ formatDate(conv.updated_at) }}</p>
                    </div>
                    <button
                        class="conversation-delete-btn group-hover:opacity-100"
                        @click.stop="deleteConversation(conv.id)"
                        title="Eliminar"
                    >
                        <TrashIcon class="w-4 h-4" />
                    </button>
                </div>
            </div>
        </template>

        <!-- Chat View -->
        <template v-else>
            <!-- Messages -->
            <div ref="messagesContainer" class="chat-messages glass-scroll">
                <div v-if="messages.length === 0 && !loadingMessages" class="chat-empty">
                    <SparklesIcon class="w-10 h-10 text-surface-600 mx-auto mb-3" />
                    <p class="text-surface-400 text-sm">Pregúntame sobre tus hábitos, pídeme crear uno nuevo, o dime qué quieres mejorar.</p>
                </div>

                <!-- Loading previous messages -->
                <div v-if="loadingMessages" class="chat-empty">
                    <span class="inline-flex gap-1">
                        <span class="loading-dot" style="animation-delay: 0ms" />
                        <span class="loading-dot" style="animation-delay: 150ms" />
                        <span class="loading-dot" style="animation-delay: 300ms" />
                    </span>
                    <p class="text-surface-500 text-xs mt-2">Cargando mensajes...</p>
                </div>

                <div
                    v-for="(msg, i) in messages"
                    :key="msg.id || i"
                    class="flex"
                    :class="msg.role === 'user' ? 'justify-end' : 'justify-start'"
                >
                    <div :class="msg.role === 'user' ? 'message-user' : 'message-assistant'">
                        <div v-html="formatMessage(msg.content)" />
                    </div>
                </div>

                <div v-if="sending" class="flex justify-start">
                    <div class="message-loading">
                        <span class="inline-flex gap-1">
                            <span class="loading-dot" style="animation-delay: 0ms" />
                            <span class="loading-dot" style="animation-delay: 150ms" />
                            <span class="loading-dot" style="animation-delay: 300ms" />
                        </span>
                    </div>
                </div>
            </div>

            <!-- Input -->
            <form @submit.prevent="sendMessage" class="chat-input-wrap">
                <div class="flex gap-2">
                    <input
                        v-model="input"
                        type="text"
                        :disabled="sending"
                        placeholder="Escribe un mensaje..."
                        class="chat-input"
                        @keydown.enter.prevent="sendMessage"
                    />
                    <button
                        type="submit"
                        :disabled="sending || !input.trim()"
                        class="px-3.5 py-2.5 bg-primary-600/80 hover:bg-primary-500/80 disabled:opacity-50 text-white rounded-xl transition-colors backdrop-blur-sm"
                    >
                        <PaperAirplaneIcon class="w-4 h-4" />
                    </button>
                </div>
            </form>
        </template>
    </div>
</template>

<script setup>
import { ref, nextTick, watch } from 'vue'
import { useQueryClient } from '@tanstack/vue-query'
import { useConversations, useConversationMessages, useDeleteConversation, useSendMessage } from '@/composables/useConversations'
import {
    SparklesIcon,
    XMarkIcon,
    PaperAirplaneIcon,
    ChevronLeftIcon,
    PlusIcon,
    TrashIcon,
    ChatBubbleLeftIcon,
    ChatBubbleLeftRightIcon,
} from '@heroicons/vue/24/outline'

defineEmits(['close'])

const queryClient = useQueryClient()

// View state: 'list' or 'chat'
const view = ref('list')
const input = ref('')
const sending = ref(false)
const messagesContainer = ref(null)

// Current conversation
const activeConversationId = ref(null)
const currentTitle = ref('')
const messages = ref([])
const loadingMessages = ref(false)

// TanStack queries
const { data: conversations, isLoading: isLoadingConversations } = useConversations()
const deleteConvMutation = useDeleteConversation()
const sendMutation = useSendMessage()

function formatMessage(text) {
    if (!text) return ''
    return text
        .replace(/\n/g, '<br>')
        .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
        .replace(/\*(.*?)\*/g, '<em>$1</em>')
}

function formatDate(dateStr) {
    if (!dateStr) return ''
    const date = new Date(dateStr)
    const now = new Date()
    const diffMs = now - date
    const diffMin = Math.floor(diffMs / 60000)
    const diffHrs = Math.floor(diffMs / 3600000)
    const diffDays = Math.floor(diffMs / 86400000)

    if (diffMin < 1) return 'Ahora'
    if (diffMin < 60) return `Hace ${diffMin}m`
    if (diffHrs < 24) return `Hace ${diffHrs}h`
    if (diffDays < 7) return `Hace ${diffDays}d`
    return date.toLocaleDateString('es', { day: 'numeric', month: 'short' })
}

async function scrollToBottom() {
    await nextTick()
    if (messagesContainer.value) {
        messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
    }
}

function startNewConversation() {
    activeConversationId.value = null
    currentTitle.value = ''
    messages.value = []
    view.value = 'chat'
}

async function openConversation(conv) {
    activeConversationId.value = conv.id
    currentTitle.value = conv.title || 'Sin título'
    messages.value = []
    loadingMessages.value = true
    view.value = 'chat'

    try {
        const { data } = await import('@/composables/useApi').then(m => m.default.get(`/ai/conversations/${conv.id}`))
        messages.value = (data.messages || []).map(m => ({
            id: m.id,
            role: m.role,
            content: m.content,
        }))
    } catch {
        messages.value = []
    } finally {
        loadingMessages.value = false
        scrollToBottom()
    }
}

function goToList() {
    view.value = 'list'
    queryClient.invalidateQueries({ queryKey: ['conversations'] })
}

async function deleteConversation(id) {
    try {
        await deleteConvMutation.mutateAsync(id)
        if (activeConversationId.value === id) {
            activeConversationId.value = null
            messages.value = []
            view.value = 'list'
        }
    } catch {
        // silently ignore
    }
}

async function sendMessage() {
    const text = input.value.trim()
    if (!text || sending.value) return

    messages.value.push({ role: 'user', content: text })
    input.value = ''
    sending.value = true
    scrollToBottom()

    try {
        const result = await sendMutation.mutateAsync({
            message: text,
            conversationId: activeConversationId.value,
        })

        messages.value.push({ role: 'assistant', content: result.text })

        // If this was a new conversation, store the returned ID
        if (!activeConversationId.value && result.conversation_id) {
            activeConversationId.value = result.conversation_id
        }
    } catch (e) {
        const errorMsg = e.response?.status === 429
            ? e.response.data?.message || 'Has alcanzado el límite. Intenta más tarde.'
            : 'Lo siento, hubo un error. Intenta de nuevo.'
        messages.value.push({ role: 'assistant', content: errorMsg })
    } finally {
        sending.value = false
        scrollToBottom()
    }
}
</script>
