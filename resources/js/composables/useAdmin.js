import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import { computed, unref } from 'vue'
import api from './useApi'

export function useAdminStats() {
    return useQuery({
        queryKey: ['admin', 'stats'],
        queryFn: () => api.get('/admin/stats').then(r => r.data),
    })
}

export function useAdminUsers() {
    return useQuery({
        queryKey: ['admin', 'users'],
        queryFn: () => api.get('/admin/users').then(r => r.data),
    })
}

export function useAdminUser(id) {
    return useQuery({
        queryKey: ['admin', 'users', id],
        queryFn: () => api.get(`/admin/users/${unref(id)}`).then(r => r.data),
        enabled: computed(() => !!unref(id)),
    })
}

export function useToggleAdminModule() {
    const qc = useQueryClient()
    return useMutation({
        mutationFn: ({ userId, module }) =>
            api.post(`/admin/users/${userId}/toggle-module`, { module }).then(r => r.data),
        onSuccess: (_data, variables) => {
            qc.invalidateQueries({ queryKey: ['admin', 'users', variables.userId] })
            qc.invalidateQueries({ queryKey: ['admin', 'stats'] })
        },
    })
}

export function useUpdateAdminPlan() {
    const qc = useQueryClient()
    return useMutation({
        mutationFn: ({ userId, modules }) =>
            api.put(`/admin/users/${userId}/plan`, { modules }).then(r => r.data),
        onSuccess: (_data, variables) => {
            qc.invalidateQueries({ queryKey: ['admin', 'users', variables.userId] })
        },
    })
}
