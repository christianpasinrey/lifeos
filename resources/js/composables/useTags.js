import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import api from './useApi'

export function useTags() {
    return useQuery({
        queryKey: ['tags'],
        queryFn: () => api.get('/tags').then(r => r.data),
        staleTime: 30_000,
    })
}

export function useCreateTag() {
    const qc = useQueryClient()
    return useMutation({
        mutationFn: (data) => api.post('/tags', data).then(r => r.data),
        onSuccess: () => qc.invalidateQueries({ queryKey: ['tags'] }),
    })
}

export function useUpdateTag() {
    const qc = useQueryClient()
    return useMutation({
        mutationFn: ({ id, ...data }) => api.put(`/tags/${id}`, data).then(r => r.data),
        onSuccess: () => {
            qc.invalidateQueries({ queryKey: ['tags'] })
            qc.invalidateQueries({ queryKey: ['boards'] })
        },
    })
}

export function useDeleteTag() {
    const qc = useQueryClient()
    return useMutation({
        mutationFn: (id) => api.delete(`/tags/${id}`).then(r => r.data),
        onSuccess: () => {
            qc.invalidateQueries({ queryKey: ['tags'] })
            qc.invalidateQueries({ queryKey: ['boards'] })
        },
    })
}

/**
 * Attach tags to a board or task.
 * payload: { target_type: 'board'|'task', target_id, tag_ids?, tag_names?, replace? }
 */
export function useAttachTags() {
    const qc = useQueryClient()
    return useMutation({
        mutationFn: (payload) => api.post('/tags/attach', payload).then(r => r.data),
        onSuccess: (_, { target_type, boardId }) => {
            qc.invalidateQueries({ queryKey: ['tags'] })
            if (boardId) qc.invalidateQueries({ queryKey: ['boards', boardId] })
            else qc.invalidateQueries({ queryKey: ['boards'] })
        },
    })
}

export function useDetachTags() {
    const qc = useQueryClient()
    return useMutation({
        mutationFn: (payload) => api.post('/tags/detach', payload).then(r => r.data),
        onSuccess: (_, { boardId }) => {
            qc.invalidateQueries({ queryKey: ['tags'] })
            if (boardId) qc.invalidateQueries({ queryKey: ['boards', boardId] })
            else qc.invalidateQueries({ queryKey: ['boards'] })
        },
    })
}
