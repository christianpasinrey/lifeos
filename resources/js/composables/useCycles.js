import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import { computed, unref } from 'vue'
import api from './useApi'

export function useCycles(boardId) {
    return useQuery({
        queryKey: computed(() => ['boards', unref(boardId), 'cycles']),
        queryFn: () => api.get(`/boards/${unref(boardId)}/cycles`).then(r => r.data),
        enabled: computed(() => !!unref(boardId)),
        staleTime: 30_000,
    })
}

export function useCycleTasks(cycleId) {
    return useQuery({
        queryKey: computed(() => ['cycles', unref(cycleId), 'tasks']),
        queryFn: () => api.get(`/cycles/${unref(cycleId)}/tasks`).then(r => r.data),
        enabled: computed(() => !!unref(cycleId)),
    })
}

export function useCreateCycle() {
    const qc = useQueryClient()
    return useMutation({
        mutationFn: ({ boardId, ...data }) => api.post(`/boards/${boardId}/cycles`, data).then(r => r.data),
        onSuccess: (_, { boardId }) => {
            qc.invalidateQueries({ queryKey: ['boards', boardId, 'cycles'] })
            qc.invalidateQueries({ queryKey: ['boards', boardId] })
        },
    })
}

export function useUpdateCycle() {
    const qc = useQueryClient()
    return useMutation({
        mutationFn: ({ id, boardId, ...data }) => api.put(`/cycles/${id}`, data).then(r => r.data),
        onSuccess: (_, { boardId }) => {
            qc.invalidateQueries({ queryKey: ['boards', boardId, 'cycles'] })
            qc.invalidateQueries({ queryKey: ['boards', boardId] })
        },
    })
}

export function useDeleteCycle() {
    const qc = useQueryClient()
    return useMutation({
        mutationFn: ({ id, boardId }) => api.delete(`/cycles/${id}`).then(r => r.data),
        onSuccess: (_, { boardId }) => {
            qc.invalidateQueries({ queryKey: ['boards', boardId, 'cycles'] })
            qc.invalidateQueries({ queryKey: ['boards', boardId] })
        },
    })
}
