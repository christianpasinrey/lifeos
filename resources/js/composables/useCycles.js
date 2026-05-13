import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import { computed, unref } from 'vue'
import api from './useApi'

// Accept either a ref, a raw value, or a getter function. unref() alone
// only unwraps refs — bare getters end up serialised into the URL.
function resolve(source) {
    return typeof source === 'function' ? source() : unref(source)
}

export function useCycles(boardId) {
    return useQuery({
        queryKey: computed(() => ['boards', resolve(boardId), 'cycles']),
        queryFn: () => api.get(`/boards/${resolve(boardId)}/cycles`).then(r => r.data),
        enabled: computed(() => !!resolve(boardId)),
        staleTime: 30_000,
    })
}

export function useCycleTasks(cycleId) {
    return useQuery({
        queryKey: computed(() => ['cycles', resolve(cycleId), 'tasks']),
        queryFn: () => api.get(`/cycles/${resolve(cycleId)}/tasks`).then(r => r.data),
        enabled: computed(() => !!resolve(cycleId)),
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
