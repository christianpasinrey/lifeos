import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import { computed, unref } from 'vue'
import api from './useApi'

export function useCustomFields(boardId) {
    return useQuery({
        queryKey: computed(() => ['boards', unref(boardId), 'custom-fields']),
        queryFn: () => api.get(`/boards/${unref(boardId)}/custom-fields`).then(r => r.data),
        enabled: computed(() => !!unref(boardId)),
    })
}

export function useCreateCustomField() {
    const qc = useQueryClient()
    return useMutation({
        mutationFn: ({ boardId, ...data }) => api.post(`/boards/${boardId}/custom-fields`, data).then(r => r.data),
        onSuccess: (_, { boardId }) => {
            qc.invalidateQueries({ queryKey: ['boards', boardId, 'custom-fields'] })
            qc.invalidateQueries({ queryKey: ['boards', boardId] })
        },
    })
}

export function useUpdateCustomField() {
    const qc = useQueryClient()
    return useMutation({
        mutationFn: ({ id, boardId, ...data }) => api.put(`/custom-fields/${id}`, data).then(r => r.data),
        onSuccess: (_, { boardId }) => {
            qc.invalidateQueries({ queryKey: ['boards', boardId, 'custom-fields'] })
            qc.invalidateQueries({ queryKey: ['boards', boardId] })
        },
    })
}

export function useDeleteCustomField() {
    const qc = useQueryClient()
    return useMutation({
        mutationFn: ({ id, boardId }) => api.delete(`/custom-fields/${id}`).then(r => r.data),
        onSuccess: (_, { boardId }) => {
            qc.invalidateQueries({ queryKey: ['boards', boardId, 'custom-fields'] })
            qc.invalidateQueries({ queryKey: ['boards', boardId] })
        },
    })
}

export function useReorderCustomFields() {
    const qc = useQueryClient()
    return useMutation({
        mutationFn: ({ boardId, order }) => api.put(`/boards/${boardId}/custom-fields/reorder`, { order }).then(r => r.data),
        onSuccess: (_, { boardId }) => {
            qc.invalidateQueries({ queryKey: ['boards', boardId, 'custom-fields'] })
        },
    })
}

export function useSetFieldValues() {
    const qc = useQueryClient()
    return useMutation({
        mutationFn: ({ taskId, boardId, values }) => api.put(`/tasks/${taskId}/field-values`, { values }).then(r => r.data),
        onSuccess: (_, { taskId, boardId }) => {
            qc.invalidateQueries({ queryKey: ['tasks', taskId] })
            qc.invalidateQueries({ queryKey: ['boards', boardId] })
        },
    })
}
