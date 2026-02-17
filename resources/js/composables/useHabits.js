import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import { computed, unref } from 'vue'
import api from './useApi'

export function useHabitsToday(enabled) {
    return useQuery({
        queryKey: ['habits', 'today'],
        queryFn: () => api.get('/habits/today').then(r => r.data),
        ...(enabled !== undefined && { enabled }),
    })
}

export function useHabits() {
    return useQuery({
        queryKey: ['habits'],
        queryFn: () => api.get('/habits').then(r => r.data),
    })
}

export function useHabit(id) {
    return useQuery({
        queryKey: ['habits', id],
        queryFn: () => api.get(`/habits/${unref(id)}`).then(r => r.data),
        enabled: computed(() => !!unref(id)),
    })
}

export function useHabitStats(id) {
    return useQuery({
        queryKey: ['habits', id, 'stats'],
        queryFn: () => api.get(`/habits/${unref(id)}/stats`).then(r => r.data),
        enabled: computed(() => !!unref(id)),
    })
}

export function useToggleHabit() {
    const qc = useQueryClient()
    return useMutation({
        mutationFn: ({ habitId, value, notes }) =>
            api.post(`/habits/${habitId}/log`, { value, notes }).then(r => r.data),
        onSuccess: () => {
            qc.invalidateQueries({ queryKey: ['habits'] })
        },
    })
}

export function useCreateHabit() {
    const qc = useQueryClient()
    return useMutation({
        mutationFn: (data) => api.post('/habits', data).then(r => r.data),
        onSuccess: () => {
            qc.invalidateQueries({ queryKey: ['habits'] })
        },
    })
}

export function useUpdateHabit() {
    const qc = useQueryClient()
    return useMutation({
        mutationFn: ({ id, data }) => api.put(`/habits/${id}`, data).then(r => r.data),
        onSuccess: () => {
            qc.invalidateQueries({ queryKey: ['habits'] })
        },
    })
}

export function useDeleteHabit() {
    const qc = useQueryClient()
    return useMutation({
        mutationFn: (id) => api.delete(`/habits/${id}`).then(r => r.data),
        onSuccess: () => {
            qc.invalidateQueries({ queryKey: ['habits'] })
        },
    })
}
