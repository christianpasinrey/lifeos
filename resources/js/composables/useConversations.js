import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import { computed, unref } from 'vue'
import api from './useApi'

export function useConversations() {
    return useQuery({
        queryKey: ['conversations'],
        queryFn: () => api.get('/ai/conversations').then(r => r.data),
    })
}

export function useConversationMessages(conversationId) {
    return useQuery({
        queryKey: ['conversations', conversationId, 'messages'],
        queryFn: () => api.get(`/ai/conversations/${unref(conversationId)}`).then(r => r.data),
        enabled: computed(() => !!unref(conversationId)),
    })
}

export function useDeleteConversation() {
    const qc = useQueryClient()
    return useMutation({
        mutationFn: (id) => api.delete(`/ai/conversations/${id}`).then(r => r.data),
        onSuccess: () => {
            qc.invalidateQueries({ queryKey: ['conversations'] })
        },
    })
}

export function useUpdateConversationTitle() {
    const qc = useQueryClient()
    return useMutation({
        mutationFn: ({ id, title }) => api.patch(`/ai/conversations/${id}`, { title }).then(r => r.data),
        onSuccess: () => {
            qc.invalidateQueries({ queryKey: ['conversations'] })
        },
    })
}

export function useSendMessage() {
    const qc = useQueryClient()
    return useMutation({
        mutationFn: ({ message, conversationId }) =>
            api.post('/ai/chat', {
                message,
                conversation_id: conversationId || null,
            }).then(r => r.data),
        onSuccess: () => {
            qc.invalidateQueries({ queryKey: ['conversations'] })
            qc.invalidateQueries({ queryKey: ['habits'] })
        },
    })
}
