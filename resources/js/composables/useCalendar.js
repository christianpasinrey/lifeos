import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import { computed, unref } from 'vue'
import api from './useApi'

export function useCalendarEvents(start, end, sources) {
    return useQuery({
        queryKey: ['calendar', 'events', start, end, sources],
        queryFn: () => {
            const params = {
                start: unref(start),
                end: unref(end),
            }
            const s = unref(sources)
            if (s && s.length) {
                params.sources = s.join(',')
            }
            return api.get('/calendar/events', { params }).then(r => r.data)
        },
        enabled: computed(() => !!unref(start) && !!unref(end)),
    })
}

export function useCreateCalendarEvent() {
    const qc = useQueryClient()
    return useMutation({
        mutationFn: (data) => api.post('/calendar/events', data).then(r => r.data),
        onSuccess: () => {
            qc.invalidateQueries({ queryKey: ['calendar'] })
        },
    })
}

export function useUpdateCalendarEvent() {
    const qc = useQueryClient()
    return useMutation({
        mutationFn: ({ id, ...data }) => api.put(`/calendar/events/${id}`, data).then(r => r.data),
        onSuccess: () => {
            qc.invalidateQueries({ queryKey: ['calendar'] })
        },
    })
}

export function useDeleteCalendarEvent() {
    const qc = useQueryClient()
    return useMutation({
        mutationFn: (id) => api.delete(`/calendar/events/${id}`).then(r => r.data),
        onSuccess: () => {
            qc.invalidateQueries({ queryKey: ['calendar'] })
        },
    })
}
