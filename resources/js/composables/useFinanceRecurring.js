import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import api from './useApi'

export function useFinanceRecurring(activeOnly = true) {
    return useQuery({
        queryKey: ['finance', 'recurring', activeOnly],
        queryFn: () => api.get('/finance/recurring', { params: { active_only: activeOnly } }).then(r => r.data),
    })
}

export function useRecurringDetail(id) {
    return useQuery({
        queryKey: ['finance', 'recurring', id],
        queryFn: () => api.get(`/finance/recurring/${id}`).then(r => r.data),
        enabled: !!id,
    })
}

function invalidate(qc) {
    qc.invalidateQueries({ queryKey: ['finance', 'recurring'] })
}

export function useCreateRecurring() {
    const qc = useQueryClient()
    return useMutation({
        mutationFn: (data) => api.post('/finance/recurring', data).then(r => r.data),
        onSuccess: () => invalidate(qc),
    })
}

export function useUpdateRecurring() {
    const qc = useQueryClient()
    return useMutation({
        mutationFn: ({ id, data }) => api.put(`/finance/recurring/${id}`, data).then(r => r.data),
        onSuccess: () => invalidate(qc),
    })
}

export function useDeleteRecurring() {
    const qc = useQueryClient()
    return useMutation({
        mutationFn: (id) => api.delete(`/finance/recurring/${id}`).then(r => r.data),
        onSuccess: () => invalidate(qc),
    })
}
