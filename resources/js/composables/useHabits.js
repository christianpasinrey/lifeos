import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import { computed, unref } from 'vue'
import api from './useApi'

export function useAnalyzeHabits() {
    return useMutation({
        mutationFn: (goals) => api.post('/habits/analyze', { goals }).then(r => r.data),
    })
}

export function useApplySuggestions() {
    const qc = useQueryClient()
    return useMutation({
        mutationFn: (habits) => api.post('/habits/apply-suggestions', { habits }).then(r => r.data),
        onSuccess: () => {
            qc.invalidateQueries({ queryKey: ['habits'] })
        },
    })
}

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

export function useHabitsWeek(startDate) {
    return useQuery({
        queryKey: ['habits', 'week', startDate],
        queryFn: () => api.get('/habits/week', { params: { start: unref(startDate) } }).then(r => r.data),
        enabled: computed(() => !!unref(startDate)),
    })
}

export function useToggleHabitDate() {
    const qc = useQueryClient()
    return useMutation({
        mutationFn: ({ habitId, date, value, notes }) =>
            api.post(`/habits/${habitId}/log/${date}`, { value, notes }).then(r => r.data),
        onSuccess: () => {
            qc.invalidateQueries({ queryKey: ['habits'] })
        },
    })
}

export function useHabitsCalendar(month) {
    return useQuery({
        queryKey: ['habits', 'calendar', month],
        queryFn: () => api.get('/habits/calendar', { params: { month: unref(month) } }).then(r => r.data),
        enabled: computed(() => !!unref(month)),
    })
}

export function useHabitSparklines() {
    return useQuery({
        queryKey: ['habits', 'sparklines'],
        queryFn: () => api.get('/habits/sparklines').then(r => r.data),
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

export function useHabitVacations(habitId) {
    return useQuery({
        queryKey: ['habits', habitId, 'vacations'],
        queryFn: () => api.get(`/habits/${unref(habitId)}/vacations`).then(r => r.data),
        enabled: computed(() => !!unref(habitId)),
    })
}

export function useCreateVacation() {
    const qc = useQueryClient()
    return useMutation({
        mutationFn: ({ habitId, data }) => api.post(`/habits/${habitId}/vacations`, data).then(r => r.data),
        onSuccess: () => {
            qc.invalidateQueries({ queryKey: ['habits'] })
        },
    })
}

export function useDeleteVacation() {
    const qc = useQueryClient()
    return useMutation({
        mutationFn: ({ habitId, vacationId }) => api.delete(`/habits/${habitId}/vacations/${vacationId}`).then(r => r.data),
        onSuccess: () => {
            qc.invalidateQueries({ queryKey: ['habits'] })
        },
    })
}

export function useHabitTemplates() {
    return useQuery({
        queryKey: ['habits', 'templates'],
        queryFn: () => api.get('/habits/templates').then(r => r.data),
    })
}

export function useApplyTemplate() {
    const qc = useQueryClient()
    return useMutation({
        mutationFn: (templateId) => api.post(`/habits/templates/${templateId}/apply`).then(r => r.data),
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
